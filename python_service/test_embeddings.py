
import sys
print(sys.executable)
try:
    from langchain_huggingface import HuggingFaceEmbeddings
    from langchain_community.vectorstores import FAISS
    print("FAISS and Embeddings imported successfully")

    embedding_function = HuggingFaceEmbeddings(model_name="all-MiniLM-L6-v2")
    test_vec = embedding_function.embed_query("hello world")
    print(f"Test vector length: {len(test_vec)}")

    print("Initializing FAISS...")
    faiss_index = FAISS.from_texts(texts=["hello world"], embedding=embedding_function)
    print("FAISS initialized!")
    
    print("Initializing Chroma...")
    import chromadb
    from langchain_community.vectorstores import Chroma
    DB_FOLDER = "./chroma_db_test"
    if not os.path.exists(DB_FOLDER):
        os.makedirs(DB_FOLDER)
    
    db = Chroma(persist_directory=DB_FOLDER, embedding_function=embedding_function)
    print("Chroma initialized!")
    db.add_texts(["hello world"], ids=["1"])
    print("Added text to Chroma.")
    if hasattr(db, 'persist'):
        db.persist()
    print("Persisted Chroma.")

except Exception as e:
    print(f"Error: {e}")
