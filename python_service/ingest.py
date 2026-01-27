import os
import glob
from langchain_community.document_loaders import PyPDFLoader
# --- FIX IS HERE: Old Import Style ---
from langchain.text_splitter import RecursiveCharacterTextSplitter
from langchain_community.vectorstores import Chroma
from langchain_community.embeddings import SentenceTransformerEmbeddings

# 1. Setup
PDF_FOLDER = "./pdfs"
DB_FOLDER = "./chroma_db"

# 2. Prepare Brain
print("Initializing Database...")
embedding_function = SentenceTransformerEmbeddings(model_name="all-MiniLM-L6-v2")
db = Chroma(persist_directory=DB_FOLDER, embedding_function=embedding_function)

# 3. Find PDFs
pdf_files = glob.glob(os.path.join(PDF_FOLDER, "*.pdf"))
total_files = len(pdf_files)
print(f"Found {total_files} PDFs to process.")

# 4. Process Loop
text_splitter = RecursiveCharacterTextSplitter(chunk_size=1000, chunk_overlap=100)

for index, pdf_path in enumerate(pdf_files):
    try:
        print(f"[{index + 1}/{total_files}] Processing: {os.path.basename(pdf_path)}...")
        
        loader = PyPDFLoader(pdf_path)
        documents = loader.load()
        
        if not documents:
            continue

        chunks = text_splitter.split_documents(documents)
        
        if chunks:
            db.add_documents(chunks)
            print(f"   -> Added {len(chunks)} chunks.")
            
    except Exception as e:
        print(f"   ERROR with {pdf_path}: {e}")

print("--------------------------------------------------")
print("SUCCESS: Ingestion Complete!")