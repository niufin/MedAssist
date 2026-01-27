import os
import re
import json
import pdfplumber


def extract_medicines_from_text(text):
    candidates = set()
    pattern = re.compile(
        r'\b(?:Tab\.|Tablet|Cap\.|Capsule|Syr\.|Syrup|Inj\.|Injection|Susp\.|Suspension|Drop|Drops|Inhaler|Cream|Oint\.|Ointment)\s+'
        r'([A-Za-z][A-Za-z0-9\s\-\+\(\)\/%]*?)\s+'
        r'(\d+(?:\.\d+)?\s?(?:mg|mcg|g|IU|ml|mg\/ml|mcg\/ml|mg\/5ml|mcg\/actuation|mcg\/puff|%))',
        re.IGNORECASE,
    )
    for match in pattern.finditer(text):
        name = match.group(0).strip()
        strength = match.group(2).strip()
        if len(name) < 300:
            candidates.add((name, strength))
    return candidates


def scan_pdfs(pdfs_dir):
    found = set()
    if not os.path.isdir(pdfs_dir):
        return found
    for root, _, files in os.walk(pdfs_dir):
        for fname in files:
            path = os.path.join(root, fname)
            lower = fname.lower()
            try:
                if lower.endswith(".pdf"):
                    with pdfplumber.open(path) as pdf:
                        for page in pdf.pages:
                            text = page.extract_text() or ""
                            if text:
                                found.update(extract_medicines_from_text(text))
                elif lower.endswith((".htm", ".html", ".txt")):
                    with open(path, "r", encoding="utf-8", errors="ignore") as f:
                        text = f.read()
                        found.update(extract_medicines_from_text(text))
            except Exception:
                continue
    return found


def load_base_nlem(data_dir):
    base_path = os.path.join(data_dir, "medicines_nlem.json")
    if not os.path.exists(base_path):
        return []
    try:
        with open(base_path, "r", encoding="utf-8") as f:
            return json.load(f)
    except Exception:
        return []


def build_extended_list():
    base_dir = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
    data_dir = os.path.join(base_dir, "data")
    pdfs_dir = os.path.join(base_dir, "pdfs")

    base_list = load_base_nlem(data_dir)
    existing_keys = {(m.get("name", ""), m.get("strength", "")) for m in base_list}

    extracted = scan_pdfs(pdfs_dir)
    extended = list(base_list)

    for name, strength in sorted(extracted):
        key = (name, strength)
        if key in existing_keys:
            continue
        item = {
            "name": name,
            "strength": strength,
            "type": "Unknown",
            "class": "Unknown",
        }
        extended.append(item)
        existing_keys.add(key)

    out_path = os.path.join(data_dir, "medicines_extended.json")
    with open(out_path, "w", encoding="utf-8") as f:
        json.dump(extended, f, ensure_ascii=False, indent=2)


if __name__ == "__main__":
    build_extended_list()

