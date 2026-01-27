import os
import sys
from langchain_community.vectorstores import FAISS
from langchain_community.embeddings import SentenceTransformerEmbeddings

try:
    print("Initializing Embeddings...")
    embedding_function = SentenceTransformerEmbeddings(model_name="all-MiniLM-L6-v2")
    print("Embeddings Initialized.")

    texts = ["This is a test document.", "Another test document."]
    metas = [{"source": "test1"}, {"source": "test2"}]
    
    print("Creating FAISS index...")
    faiss_index = FAISS.from_texts(texts=texts, embedding=embedding_function, metadatas=metas)
    print("FAISS Index Created Successfully!")
    
    print("Testing Search...")
    docs = faiss_index.similarity_search("test", k=1)
    print(f"Search Result: {docs[0].page_content}")

except Exception as e:
    print(f"ERROR: {e}")
    import traceback
    traceback.print_exc()
