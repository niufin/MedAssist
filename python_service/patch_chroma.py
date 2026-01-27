
import os

target_file = r"C:\Python314\Lib\site-packages\chromadb\config.py"

new_content = """try:
    from pydantic_settings import BaseSettings
except ImportError:
    from pydantic import BaseSettings
from typing import List

TELEMETRY_WHITELISTED_SETTINGS = [
    "chroma_db_impl",
    "chroma_api_impl",
    "chroma_server_ssl_enabled",
]


class Settings(BaseSettings):
    environment: str = ""

    chroma_db_impl: str = "duckdb"
    chroma_api_impl: str = "local"

    clickhouse_host: str = None
    clickhouse_port: str = None

    persist_directory: str = ".chroma"

    chroma_server_host: str = None
    chroma_server_http_port: str = None
    chroma_server_ssl_enabled: bool = False
    chroma_server_grpc_port: str = None
    chroma_server_cors_allow_origins: List[str] = []

    anonymized_telemetry: bool = True
"""

try:
    with open(target_file, "w") as f:
        f.write(new_content)
    print("Successfully patched chromadb/config.py")
except Exception as e:
    print(f"Failed to patch: {e}")
