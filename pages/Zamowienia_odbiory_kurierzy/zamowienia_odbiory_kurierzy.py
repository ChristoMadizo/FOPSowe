# -*- coding: utf-8 -*-
import sys
import requests
import fitz  # PyMuPDF
import os
import pdfplumber
import pytesseract
from pdf2image import convert_from_path
from PIL import Image
import re

# Pobranie argumentu URL
if len(sys.argv) < 2:
    print("Brak argumentu URL. U¿ycie: python zamowienia_odbiory_kurierzy.py <URL>")
    sys.exit(1)

url = sys.argv[1]

# Pobranie pliku PDF
def download_pdf(url):
    local_filename = "/tmp/temp_file.pdf"
    response = requests.get(url)
    with open(local_filename, "wb") as file:
        file.write(response.content)
    return local_filename

# Obracanie stron PDF
def rotate_pdf(pdf_path, degrees=180):
    doc = fitz.open(pdf_path)
    for page in doc:
        page.set_rotation(degrees)
    rotated_pdf_path = "/tmp/temp_file_rotated.pdf"
    doc.save(rotated_pdf_path)
    doc.close()
    return rotated_pdf_path

# Metody ekstrakcji tekstu
def extract_text_pdfplumber(pdf_path):
    text = ""
    with pdfplumber.open(pdf_path) as pdf:
        for page in pdf.pages:
            page_text = page.extract_text()
            if page_text:
                text += page_text + "\n"
    return text

def extract_text_pymupdf(pdf_path):
    doc = fitz.open(pdf_path)
    text = "\n".join([page.get_text("text") for page in doc])
    doc.close()
    return text

def extract_text_ocr(pdf_path):
    images = convert_from_path(pdf_path)
    text = ""
    for i, image in enumerate(images):
        page_text = pytesseract.image_to_string(image, lang="pol")
        text += f"\n\n=== Strona {i+1} ===\n\n" + page_text
    return text

# Pobranie PDF
pdf_path = download_pdf(url)
pdf_path_rotated = rotate_pdf(pdf_path)

# Ekstrakcja tekstu ró¿nymi metodami
extracted_text_pdfplumber = extract_text_pdfplumber(pdf_path)
extracted_text_pymupdf = extract_text_pymupdf(pdf_path)

extracted_text_reversed_pdfplumber = extract_text_pdfplumber(pdf_path_rotated)
extracted_text_reversed_pymupdf = extract_text_pymupdf(pdf_path_rotated)

#extracted_text_ocr = extract_text_ocr(pdf_path)
#extracted_text_reversed_ocr = extract_text_ocr(pdf_path_rotated)

# Usuniêcie plików PDF
os.remove(pdf_path)
os.remove(pdf_path_rotated)

# Lista metod ekstrakcji
extracted_texts = {
    "pdfplumber": extracted_text_pdfplumber,
    "pymupdf": extracted_text_pymupdf,
    "pdfplumber_reversed": extracted_text_reversed_pdfplumber,
    "pymupdf_reversed": extracted_text_reversed_pymupdf,
    #"ocr": extracted_text_ocr,
    #"ocr_reversed": extracted_text_reversed_ocr
}

# Szukanie pierwszego tekstu, gdzie wystêpuje kurier
kurier = None
found_text = None

for method, text in extracted_texts.items():
    for courier in ["GLS", "DPD", "DHL", "MIG", "InPost", "PPL", "Fedex", "Raben", "UPS", "Toptrans"]:
        if courier.lower() in text.lower():
            kurier = courier
            found_text = text
            break
    if kurier:
        break

if kurier:
    print(f"Kurier to {kurier}")

    print(found_text)

    # Szukanie numeru listu przewozowego dla danego kuriera
    if kurier == "GLS":
        matches = re.findall(r"\b\d{12}\b", found_text)
    elif kurier == "DPD":
        matches = re.findall(r"\b[A-Z]{2}-DPD-[A-Z0-9\-]+", found_text)
    elif kurier == "DHL":
        matches = re.findall(r"Nr przesy³ki:\s*(\d+)", found_text)
    elif kurier == "MIG":
        matches = re.findall(r"Shipment number\s*(\d+)", found_text)
    elif kurier == "InPost":
        matches = re.findall(r"\b\d{24}\b", found_text)
    elif kurier == "PPL":
        matches = re.findall(r"(?i)\bPPL identifier\b.*?(\d+)", found_text)
    elif kurier in ["Fedex", "QEOA", "TNT"]:
        matches = re.findall(r"\b\d{4} \d{4} \d{4}\b", found_text)
    elif kurier == "Raben":
        matches = re.findall(r"\b\d{15}\b", found_text)
    elif kurier == "UPS":
        matches = re.findall(r"TRACKING #:\s*(.+)", found_text)
    elif kurier == "Toptrans":
        matches = re.findall(r"Podací èíslo:\s*(.+)", found_text)
    else:
        matches = []

    # Wyœwietlenie numerów przewozowych
    if matches:
        for match in matches:
            print(f"{kurier}|{match.strip()}")
    else:
        print(f"Nie znaleziono numeru przewozowego dla {kurier}.")
else:
    print("Nie znaleziono kuriera w ¿adnym z tekstów.")
