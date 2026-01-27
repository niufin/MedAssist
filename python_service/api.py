import time
from fastapi import FastAPI, HTTPException
from pydantic import BaseModel
from openai import OpenAI
import os
import sys
import json
import re
import pdfplumber
import pytesseract
from PIL import Image
from dotenv import load_dotenv
import threading

# --- CONFIGURATION ---
sys.stdout.reconfigure(encoding='utf-8')
sys.stderr.reconfigure(encoding='utf-8')
os.environ["ANONYMIZED_TELEMETRY"] = "False"

# Load python_service/.env if present
load_dotenv()

# --- TESSERACT PATH (Update if needed) ---
tesseract_path = os.getenv("TESSERACT_PATH") or r"C:\Program Files\Tesseract-OCR\tesseract.exe"
if os.path.exists(tesseract_path):
    pytesseract.pytesseract.tesseract_cmd = tesseract_path
else:
    print("⚠️ WARNING: Tesseract OCR not found. Image reading might fail.")

# --- LIBRARIES ---
from langchain_community.vectorstores import Chroma, FAISS
from langchain_community.embeddings import SentenceTransformerEmbeddings

OPENROUTER_API_KEY = os.getenv("OPENROUTER_API_KEY") or os.getenv("OPENAI_API_KEY")
DB_FOLDER = os.getenv("CHROMA_DB_DIR") or "./chroma_db"
FAISS_FOLDER = os.getenv("FAISS_DB_DIR") or "./faiss_db"

AI_MODELS = [
    "openai/gpt-4o-mini",
    "google/gemini-2.0-flash-exp:free",
    "meta-llama/llama-3.3-70b-instruct:free",
    "deepseek/deepseek-r1:free",
    "qwen/qwen-2.5-vl-7b-instruct:free",
    "xiaomi/mimo-v2-flash:free"
]

AI_FINAL_MODEL = (os.getenv("AI_FINAL_MODEL") or "").strip()

app = FastAPI()

print("Loading Local Memory (ChromaDB)...")
db_lock = threading.Lock()
embedding_function = None
db_backend = None
try:
    embedding_function = SentenceTransformerEmbeddings(model_name="all-MiniLM-L6-v2")
    db = Chroma(persist_directory=DB_FOLDER, embedding_function=embedding_function)
    db_backend = "chroma"
    print("Memory Loaded!")
except Exception as e:
    print(f"CRITICAL ERROR LOADING MEMORY: {e}")
    try:
        print(f"Trying to load existing FAISS index from {os.path.abspath(FAISS_FOLDER)}...")
        embedding_function = SentenceTransformerEmbeddings(model_name="all-MiniLM-L6-v2")
        db = FAISS.load_local(FAISS_FOLDER, embedding_function, allow_dangerous_deserialization=True)
        db_backend = "faiss"
        print("Loaded existing FAISS index from disk.")
    except Exception as e2:
        print(f"CRITICAL ERROR LOADING FAISS MEMORY: {e2}")
        db = None
        db_backend = None

class ChatRequest(BaseModel):
    current_input: str
    history: list = []
    mode: str = "chat"
    patient_age: str = "Unknown" 
    patient_gender: str = "Unknown"
    patient_symptoms: str = "" 

class ReportRequest(BaseModel):
    file_path: str

client = OpenAI(
    base_url="https://openrouter.ai/api/v1",
    api_key=OPENROUTER_API_KEY,
)

def load_memory_db():
    global db, embedding_function, db_backend
    with db_lock:
        embedding_function = SentenceTransformerEmbeddings(model_name="all-MiniLM-L6-v2")
        try:
            os.makedirs(DB_FOLDER, exist_ok=True)
            db = Chroma(persist_directory=DB_FOLDER, embedding_function=embedding_function)
            db_backend = "chroma"
            return True
        except Exception:
            if os.path.isdir(FAISS_FOLDER):
                db = FAISS.load_local(FAISS_FOLDER, embedding_function, allow_dangerous_deserialization=True)
                db_backend = "faiss"
                return True
            raise

def get_doc_chunks_count():
    if db is None:
        return None
    try:
        if db_backend == "faiss":
            idx = getattr(db, "index", None)
            return int(idx.ntotal) if idx is not None else None
        col = getattr(db, "_collection", None)
        if col is None:
            return None
        return int(col.count())
    except Exception:
        return None

@app.get("/health")
def health():
    return {"status": "ok"}

@app.get("/status")
def status():
    return {
        "backend": db_backend,
        "doc_chunks": get_doc_chunks_count(),
        "indexing_state": {
            "is_indexing": False,
            "progress": None,
            "total_files": len([f for f in os.listdir("./pdfs") if f.lower().endswith(".pdf")]) if os.path.isdir("./pdfs") else 0,
        },
    }

@app.post("/admin/reload-memory")
def admin_reload_memory():
    try:
        load_memory_db()
        return {"ok": True, "message": "Memory reloaded", "doc_chunks": get_doc_chunks_count()}
    except Exception as e:
        return {"ok": False, "message": str(e), "doc_chunks": None}

@app.post("/admin/restart")
def admin_restart():
    try:
        load_memory_db()
        return {"ok": True, "message": "Restart requested (soft reload complete)"}
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

# --- QUERY CLEANER (Fixes 'Male Infertility' bug) ---
def clean_search_query(text):
    text = text.lower()
    ignore_words = [
        r"\bmale\b", r"\bfemale\b", r"\bman\b", r"\bwoman\b", 
        r"\bpatient\b", r"\byear old\b", r"\byears old\b", 
        r"\byo\b", r"\bgentleman\b", r"\blady\b", r"\bchild\b",
        r"\b\d+\b" # Remove raw numbers
    ]
    for pattern in ignore_words:
        text = re.sub(pattern, "", text)
    
    # Remove fillers
    text = re.sub(r'[^\w\s]', '', text)
    return " ".join(text.split())

def retrieve_documents(retriever, query: str):
    if hasattr(retriever, "get_relevant_documents"):
        return retriever.get_relevant_documents(query)
    if hasattr(retriever, "invoke"):
        return retriever.invoke(query)
    if callable(retriever):
        return retriever(query)
    return []

def extract_age_int(age_text: str):
    digits = "".join([c for c in (age_text or "") if c.isdigit()])
    if not digits:
        return None
    try:
        age_val = int(digits)
    except Exception:
        return None
    if 0 < age_val <= 120:
        return age_val
    return None

def route_retrieval(patient_age: str, patient_gender: str, patient_symptoms: str, current_input: str):
    router_model = AI_MODELS[0] if AI_MODELS else "openai/gpt-4o-mini"
    user_payload = {
        "patient_age": patient_age or "Unknown",
        "patient_gender": patient_gender or "Unknown",
        "patient_symptoms": patient_symptoms or "",
        "current_input": current_input or "",
    }
    messages = [
        {
            "role": "system",
            "content": (
                "You are a clinical retrieval router.\n"
                "Choose which guideline PDFs/specialties to consult based on symptoms.\n"
                "Return ONLY valid JSON with this schema:\n"
                "{\n"
                '  "source_keywords": ["gastroenterology","neurology","cardiology"],\n'
                '  "queries": ["...", "..."]\n'
                "}\n"
                "Rules:\n"
                "- queries must be short, medical, and exclude demographics.\n"
                "- include likely organ-system terms (e.g., GI, neuro).\n"
                "- if abdominal pain/nausea/vomiting/diarrhea present, include gastroenterology.\n"
                "- if headache/meningism/vertigo present, include neurology.\n"
            ),
        },
        {"role": "user", "content": json.dumps(user_payload)},
    ]
    try:
        completion = client.chat.completions.create(
            model=router_model,
            messages=messages,
            temperature=0.0,
            top_p=1,
        )
        text = (completion.choices[0].message.content or "").strip()
        m = re.search(r"\{[\s\S]*\}", text)
        if not m:
            return {"source_keywords": [], "queries": []}
        obj = json.loads(m.group(0))
        kws = obj.get("source_keywords", []) if isinstance(obj, dict) else []
        qs = obj.get("queries", []) if isinstance(obj, dict) else []
        if not isinstance(kws, list):
            kws = []
        if not isinstance(qs, list):
            qs = []
        kws = [str(k).strip().lower() for k in kws if str(k).strip()][:6]
        qs = [str(q).strip() for q in qs if str(q).strip()][:4]
        return {"source_keywords": kws, "queries": qs}
    except Exception:
        return {"source_keywords": [], "queries": []}

@app.post("/read-report")
def read_report(request: ReportRequest):
    print(f"\n--- PROCESSING REPORT: {request.file_path} ---")
    extracted_text = ""
    try:
        ext = os.path.splitext(request.file_path)[1].lower()
        if ext == ".pdf":
            with pdfplumber.open(request.file_path) as pdf:
                for page in pdf.pages:
                    text = page.extract_text()
                    if text: extracted_text += text + "\n"
        elif ext in [".jpg", ".jpeg", ".png", ".bmp"]:
            text = pytesseract.image_to_string(Image.open(request.file_path))
            extracted_text += text
        else:
            return {"error": "Unsupported file format."}
        
        cleaned_text = " ".join(extracted_text.split())
        return {"text": cleaned_text}
    except Exception as e:
        return {"error": str(e)}

@app.post("/chat")
def chat_endpoint(request: ChatRequest):
    print(f"\n--- NEW MESSAGE ({request.mode}) ---")
    try:
        if db is None:
            return {"response": "System Error: Memory DB not loaded.", "sources": [], "model": "Error"}

        age_val = extract_age_int(request.patient_age)
        is_pediatric = age_val is not None and age_val < 18
        is_adult = age_val is not None and age_val >= 18

        raw_input = f"{request.patient_symptoms} {request.current_input}".strip()
        cleaned_keywords = clean_search_query(raw_input)

        routing = route_retrieval(request.patient_age, request.patient_gender, request.patient_symptoms, request.current_input)
        source_keywords = routing.get("source_keywords") or []
        router_queries = routing.get("queries") or []

        age_hint = "Adult" if is_adult else ("Pediatric" if is_pediatric else "")
        t = raw_input.lower()
        has_gi = any(k in t for k in ["abdominal", "epigastric", "dyspepsia", "heartburn", "reflux", "vomit", "nausea", "diarr", "constipat", "jaundice", "gas", "bloating", "melena", "hematemesis"])
        has_neuro = any(k in t for k in ["headache", "mening", "vertigo", "seiz", "syncope", "weakness", "stroke", "neck stiffness", "photophobia"])
        has_cardio = any(k in t for k in ["chest pain", "palpit", "dyspnea", "ecg", "mi", "heart failure"])

        for kw in source_keywords:
            if not isinstance(kw, str):
                continue
        source_keywords = [str(k).strip().lower() for k in source_keywords if str(k).strip()]
        if has_gi and "gastro" not in source_keywords:
            source_keywords.append("gastro")
        if has_neuro and "neuro" not in source_keywords:
            source_keywords.append("neuro")
        if has_cardio and "cardio" not in source_keywords:
            source_keywords.append("cardio")
        source_keywords = list(dict.fromkeys(source_keywords))[:6]
        queries = []
        if has_gi:
            queries.append("abdominal pain dyspepsia gastroenterology")
        if has_neuro:
            queries.append("headache meningitis neurology")
        if has_cardio:
            queries.append("chest pain acute coronary syndrome cardiology")
        if isinstance(router_queries, list):
            queries.extend([str(q).strip() for q in router_queries if str(q).strip()])
        if cleaned_keywords:
            queries.append(cleaned_keywords)
        queries = list(dict.fromkeys([q for q in queries if q]))[:5]

        print(f"🔎 Retrieval Router: age_hint={age_hint} source_keywords={source_keywords} queries={queries}")

        retriever = db.as_retriever(
            search_type="similarity" if request.mode == "final" else "mmr",
            search_kwargs={"k": 18},
        )
        docs_all = []
        seen = set()
        for q in queries:
            q2 = f"{age_hint} {q}".strip()[:500]
            for d in retrieve_documents(retriever, q2):
                src = os.path.basename(d.metadata.get("source", "Unknown PDF"))
                key = (src, (d.page_content or "")[:200])
                if key in seen:
                    continue
                seen.add(key)
                docs_all.append(d)

        def doc_allowed(d):
            src = os.path.basename(d.metadata.get("source", "Unknown PDF")).lower()
            if is_adult and ("paediatric" in src or "pediatric" in src):
                return False
            if is_pediatric and ("adult" in src):
                return True
            return True

        docs_filtered = [d for d in docs_all if doc_allowed(d)]
        docs_filtered = sorted(
            docs_filtered,
            key=lambda d: (
                os.path.basename(d.metadata.get("source", "Unknown PDF")),
                str(d.metadata.get("page", "")),
                (d.page_content or "")[:120],
            ),
        )
        if source_keywords:
            kw_map = {
                "gastro": ["gastro", "gastroenterology", "gastrointestinal", "bleed", "liver", "jaundice", "gall", "bile", "duct"],
                "neuro": ["neuro", "neurology", "headache", "mening", "stroke", "seiz", "epilep"],
                "cardio": ["cardio", "cardiology", "ecg", "acs", "myocard", "heart_failure", "hypertension"],
            }
            def matches_kw(d):
                src = os.path.basename(d.metadata.get("source", "Unknown PDF")).lower()
                for kw in source_keywords:
                    patterns = kw_map.get(kw, [kw])
                    if any(p in src for p in patterns):
                        return True
                return False
            preferred = [d for d in docs_filtered if matches_kw(d)]
            fallback = [d for d in docs_filtered if d not in preferred]
            docs = (preferred + fallback)[:8]
            if len(docs) < 4:
                docs = docs_filtered[:8]
        else:
            docs = docs_filtered[:8]
        if not docs:
            docs = docs_all[:6]

        sources_list = []
        context_blocks = []
        for d in docs:
            source_name = os.path.basename(d.metadata.get("source", "Unknown PDF"))
            content_clean = d.page_content.replace("\n", " ").strip()
            
            # --- FIX: XML TAGS PREVENT LEAKAGE INTO CHAT ---
            context_blocks.append(f"<document title='{source_name}'>\n{content_clean}\n</document>")
            
            sources_list.append({"source": source_name, "content": content_clean})

        context_text = "\n".join(context_blocks)

        system_prompt = f"""You are a senior clinical copilot assisting an experienced physician.

Patient: {request.patient_age} | {request.patient_gender}
Core Symptoms: {request.patient_symptoms}

CONTEXT FROM DATABASE:
{context_text}

--------------------------------------------------
OUTPUT RULES (STRICT):
1) Clinician-facing only. Do NOT write patient-facing education or use second-person ("you/your").
2) Be concise. Prefer bullets. Avoid long paragraphs and generic lists.
3) No citations/filenames/tags (the UI shows sources separately).
4) Use standard medical abbreviations when appropriate (Hx, PE, DDx, r/o, Rx, PRN, PO).
5) If information is missing, ask only the highest-yield clarifying questions.
6) Always include can't-miss differentials and red flags when relevant.
7) Do not apply pediatric guidance to adult patients unless clearly indicated.
--------------------------------------------------
RESPONSE FORMAT (chat mode):
- Problem representation (1 line)
- DDx (prioritized; brief rationale; include can't-miss)
- Key questions (max 5)
- Focused exam (max 5)
- Workup (targeted)
- Initial plan (pragmatic)
- Red flags / disposition
"""

        if request.mode == "final":
            system_prompt += """
FINAL OUTPUT (final mode):
- Assessment (primary Dx + key DDx)
- Investigations
- Treatment/Management plan
- Red flags / disposition
"""

        messages = [{"role": "system", "content": system_prompt}]
        clean_history = [{"role": m["role"], "content": m["content"]} for m in request.history]
        messages.extend(clean_history)
        messages.append({"role": "user", "content": request.current_input})

        ai_response = "Error."
        model_used = "Unknown"

        if request.mode == "final":
            model = AI_FINAL_MODEL or (AI_MODELS[0] if AI_MODELS else "openai/gpt-4o-mini")
            print(f"Trying {model}...")
            completion = client.chat.completions.create(
                model=model,
                messages=messages,
                temperature=0.0,
                top_p=1,
            )
            ai_response = completion.choices[0].message.content
            model_used = model
            print(f"✅ Answered by: {model}")
        else:
            for model in AI_MODELS:
                try:
                    print(f"Trying {model}...")
                    completion = client.chat.completions.create(
                        model=model,
                        messages=messages,
                        temperature=0.0,
                        top_p=1,
                    )
                    ai_response = completion.choices[0].message.content
                    model_used = model
                    print(f"✅ Answered by: {model}")
                    break
                except Exception as e:
                    print(f"⚠️ Failed ({model}): {e}")
                    time.sleep(1)
                    continue

        return {"response": ai_response, "sources": sources_list, "model": model_used}

    except Exception as e:
        print(f"ERROR: {e}")
        return {"response": f"System Error: {str(e)}", "sources": [], "model": "Error"}
