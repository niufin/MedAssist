import time
from fastapi import FastAPI, HTTPException
from pydantic import BaseModel
from openai import OpenAI
import os
import sys
import json
import io
import base64
import re
import pdfplumber
import pytesseract
from PIL import Image, ImageFilter, ImageOps
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

class ReportResponse(BaseModel):
    text: str
    method: str | None = None
    score: float | None = None
    warnings: list[str] = []

client = OpenAI(
    base_url="https://openrouter.ai/api/v1",
    api_key=OPENROUTER_API_KEY,
)

def _safe_str(v) -> str:
    try:
        return str(v)
    except Exception:
        return ""

def _normalize_whitespace(text: str) -> str:
    return " ".join((text or "").split())

def _alnum_ratio(text: str) -> float:
    s = "".join((text or "").split())
    if not s:
        return 0.0
    ok = sum(1 for ch in s if ch.isalnum())
    return ok / max(1, len(s))

def _ocr_quality_score(text: str) -> float:
    t = (text or "").strip()
    if not t:
        return 0.0
    ln = len(t)
    ratio = _alnum_ratio(t)
    return (min(ln, 2000) / 2000.0) * 0.65 + ratio * 0.35

def _otsu_threshold(gray: Image.Image) -> int:
    hist = gray.histogram()
    if len(hist) < 256:
        hist = (hist + [0] * 256)[:256]
    total = sum(hist)
    if total <= 0:
        return 180
    sum_total = 0.0
    for i, h in enumerate(hist[:256]):
        sum_total += i * h

    sum_b = 0.0
    w_b = 0.0
    max_var = -1.0
    threshold = 180
    for i in range(256):
        w_b += hist[i]
        if w_b == 0:
            continue
        w_f = total - w_b
        if w_f == 0:
            break
        sum_b += i * hist[i]
        m_b = sum_b / w_b
        m_f = (sum_total - sum_b) / w_f
        var_between = w_b * w_f * (m_b - m_f) * (m_b - m_f)
        if var_between > max_var:
            max_var = var_between
            threshold = i
    return int(threshold)

def _prepare_image_for_ocr(img: Image.Image, variant: str) -> Image.Image:
    img = ImageOps.exif_transpose(img)
    if img.mode not in ("RGB", "L"):
        img = img.convert("RGB")

    if variant == "gray":
        gray = img.convert("L")
        gray = ImageOps.autocontrast(gray)
        gray = gray.filter(ImageFilter.MedianFilter(size=3))
        gray = gray.filter(ImageFilter.UnsharpMask(radius=2, percent=160, threshold=3))
        return gray

    if variant == "bw":
        gray = _prepare_image_for_ocr(img, "gray")
        thr = _otsu_threshold(gray)
        bw = gray.point(lambda p, t=thr: 255 if p >= t else 0).convert("L")
        return bw

    if variant == "gray2x":
        gray = _prepare_image_for_ocr(img, "gray")
        w, h = gray.size
        max_side = max(w, h)
        if max_side < 1400:
            scale = 2.0
        else:
            scale = min(2.0, 2600 / max_side)
        if scale != 1.0:
            gray = gray.resize((max(1, int(w * scale)), max(1, int(h * scale))), Image.Resampling.LANCZOS)
        return gray

    return _prepare_image_for_ocr(img, "gray")

def _crop_to_content(gray: Image.Image) -> Image.Image:
    try:
        inv = ImageOps.invert(gray.convert("L"))
        bbox = inv.getbbox()
        if bbox:
            x0, y0, x1, y1 = bbox
            pad = 12
            x0 = max(0, x0 - pad)
            y0 = max(0, y0 - pad)
            x1 = min(gray.size[0], x1 + pad)
            y1 = min(gray.size[1], y1 + pad)
            if (x1 - x0) > 40 and (y1 - y0) > 40:
                return gray.crop((x0, y0, x1, y1))
    except Exception:
        pass
    return gray

def _tesseract_ocr(img: Image.Image) -> tuple[str, float | None]:
    config = os.getenv("REPORT_TESSERACT_CONFIG") or "--oem 3 --psm 6 --dpi 300"
    lang = (os.getenv("REPORT_TESSERACT_LANG") or "eng").strip()
    try:
        from pytesseract import Output
        d = pytesseract.image_to_data(img, lang=lang, config=config, output_type=Output.DICT)
        confs = []
        for c in d.get("conf", []) or []:
            try:
                v = float(c)
            except Exception:
                continue
            if v >= 0:
                confs.append(v)
        avg_conf = (sum(confs) / len(confs)) if confs else None
    except Exception:
        avg_conf = None

    text = pytesseract.image_to_string(img, lang=lang, config=config)
    return _safe_str(text), avg_conf

def _vision_ocr_from_pil(img: Image.Image) -> str:
    model = (os.getenv("REPORT_VISION_MODEL") or "openai/gpt-4o-mini").strip()
    buf = io.BytesIO()
    rgb = ImageOps.exif_transpose(img)
    if rgb.mode != "RGB":
        rgb = rgb.convert("RGB")
    rgb.save(buf, format="JPEG", quality=92, optimize=True)
    b64 = base64.b64encode(buf.getvalue()).decode("ascii")
    data_url = "data:image/jpeg;base64," + b64
    messages = [
        {
            "role": "system",
            "content": "Extract all readable text from the medical report image. If the image contains tables, format them as Markdown tables. Return the content exactly as it appears, correcting for any OCR errors or artifacts. Do not include any conversational text.",
        },
        {
            "role": "user",
            "content": [
                {"type": "text", "text": "Perform OCR on this image and output only the text."},
                {"type": "image_url", "image_url": {"url": data_url}},
            ],
        },
    ]
    completion = client.chat.completions.create(
        model=model,
        messages=messages,
        temperature=0.0,
        top_p=1,
    )
    return _safe_str(completion.choices[0].message.content or "")

def _ocr_image_best_effort(path: str) -> tuple[str, str, float, list[str]]:
    warnings: list[str] = []
    img = Image.open(path)
    mode = (os.getenv("REPORT_OCR_MODE") or "auto").strip().lower()
    if mode == "vision":
        try:
            vtext = _normalize_whitespace(_vision_ocr_from_pil(img))
            vscore = _ocr_quality_score(vtext)
            if vscore < 0.12:
                warnings.append("low_ocr_score")
            return vtext, "vision", float(vscore), warnings
        except Exception:
            warnings.append("vision_ocr_failed")
    variants = ["gray", "bw", "gray2x"]
    best = {"text": "", "score": 0.0, "method": "tesseract", "conf": None}


    for v in variants:
        prepared = _prepare_image_for_ocr(img, v)
        prepared = _crop_to_content(prepared)
        text, conf = _tesseract_ocr(prepared)
        cleaned = _normalize_whitespace(text)
        score = _ocr_quality_score(cleaned)
        if conf is not None:
            score = score * 0.85 + min(max(conf / 100.0, 0.0), 1.0) * 0.15
        if score > best["score"]:
            best = {"text": cleaned, "score": score, "method": f"tesseract:{v}", "conf": conf}

    if best["score"] < 0.12:
        warnings.append("low_ocr_score")

    if mode == "auto" and best["score"] < float(os.getenv("REPORT_OCR_VISION_THRESHOLD") or "0.8"):
        try:
            vtext = _normalize_whitespace(_vision_ocr_from_pil(img))
            vscore = _ocr_quality_score(vtext)
            if vscore > best["score"]:
                return vtext, "vision_fallback", float(vscore), list(dict.fromkeys(warnings + ["used_vision_fallback"]))
        except Exception:
            warnings.append("vision_ocr_failed")

    return best["text"], best["method"], float(best["score"]), warnings

def _extract_text_from_pdf(path: str) -> tuple[str, str, float, list[str]]:
    warnings: list[str] = []
    extracted_text = ""
    try:
        with pdfplumber.open(path) as pdf:
            for page in pdf.pages:
                text = page.extract_text()
                if text:
                    extracted_text += text + "\n"
    except Exception:
        extracted_text = ""

    cleaned = _normalize_whitespace(extracted_text)
    if len(cleaned) >= 200:
        return cleaned, "pdfplumber", _ocr_quality_score(cleaned), warnings

    try:
        import fitz  # PyMuPDF
        doc = fitz.open(path)
        max_pages = int(os.getenv("REPORT_OCR_MAX_PAGES") or "5")
        dpi = int(os.getenv("REPORT_OCR_PDF_DPI") or "220")
        zoom = dpi / 72.0
        mat = fitz.Matrix(zoom, zoom)
        parts: list[str] = []
        for i in range(min(doc.page_count, max_pages)):
            page = doc.load_page(i)
            pix = page.get_pixmap(matrix=mat, alpha=False)
            img_bytes = pix.tobytes("png")
            img = Image.open(io.BytesIO(img_bytes))
            text, method, score, w = _ocr_image_best_effort_from_image(img)
            parts.append(text)
            warnings.extend(w)
        merged = _normalize_whitespace("\n".join([p for p in parts if p]))
        score = _ocr_quality_score(merged)
        if merged:
            return merged, "pdf_ocr", score, warnings
    except Exception:
        warnings.append("pdf_ocr_failed")

    return cleaned, "pdfplumber", _ocr_quality_score(cleaned), warnings

def _ocr_image_best_effort_from_image(img: Image.Image) -> tuple[str, str, float, list[str]]:
    warnings: list[str] = []
    mode = (os.getenv("REPORT_OCR_MODE") or "auto").strip().lower()
    if mode == "vision":
        try:
            vtext = _normalize_whitespace(_vision_ocr_from_pil(img))
            vscore = _ocr_quality_score(vtext)
            if vscore < 0.12:
                warnings.append("low_ocr_score")
            return vtext, "vision", float(vscore), warnings
        except Exception:
            warnings.append("vision_ocr_failed")

    variants = ["gray", "bw", "gray2x"]
    best = {"text": "", "score": 0.0, "method": "tesseract", "conf": None}

    for v in variants:
        prepared = _prepare_image_for_ocr(img, v)
        prepared = _crop_to_content(prepared)
        text, conf = _tesseract_ocr(prepared)
        cleaned = _normalize_whitespace(text)
        score = _ocr_quality_score(cleaned)
        if conf is not None:
            score = score * 0.85 + min(max(conf / 100.0, 0.0), 1.0) * 0.15
        if score > best["score"]:
            best = {"text": cleaned, "score": score, "method": f"tesseract:{v}", "conf": conf}

    if best["score"] < 0.12:
        warnings.append("low_ocr_score")

    if mode == "auto" and best["score"] < float(os.getenv("REPORT_OCR_VISION_THRESHOLD") or "0.8"):
        try:
            vtext = _normalize_whitespace(_vision_ocr_from_pil(img))
            vscore = _ocr_quality_score(vtext)
            if vscore > best["score"]:
                return vtext, "vision_fallback", float(vscore), list(dict.fromkeys(warnings + ["used_vision_fallback"]))
        except Exception:
            warnings.append("vision_ocr_failed")

    return best["text"], best["method"], float(best["score"]), warnings

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
    try:
        ext = os.path.splitext(request.file_path)[1].lower()
        if ext == ".pdf":
            text, method, score, warnings = _extract_text_from_pdf(request.file_path)
            return {"text": text, "method": method, "score": score, "warnings": warnings}
        if ext in [".jpg", ".jpeg", ".png", ".bmp", ".tif", ".tiff"]:
            text, method, score, warnings = _ocr_image_best_effort(request.file_path)
            return {"text": text, "method": method, "score": score, "warnings": warnings}
        return {"error": "Unsupported file format."}
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
