# -*- coding: utf-8 -*-
import sys
import requests
import fitz  # PyMuPDF
import os
import pdfplumber

# Pobieranie argumentu URL z wiersza poleceń
if len(sys.argv) < 2:
    print("Brak argumentu URL. Użycie: python zamowienia_odbiory_kurierzy.py <URL>")
    sys.exit(1)

url = sys.argv[1]  # URL przekazany z PHP

#url='https://prosto.fops.pl/index.php/files/download/383071/Ys6rhy4zTp7DQdeIMtjK4KCFbqihV8Yv7ONDiWJ2yq6vllT1UvcGj8rBDyyPs51C?disposition=inline&csrf=41ce7a0814a5af47c22eaa3e14e0ffb5de30e5f85236fc7a&session=68526fe53e0cc3-52463160'

# Pobieranie pliku PDF
def download_pdf(url):
    local_filename = "/tmp/temp_file.pdf"  # Zapis do katalogu tymczasowego
    response = requests.get(url)
    with open(local_filename, "wb") as file:
        file.write(response.content)
    return local_filename

# Ekstrakcja tekstu z PDF
import fitz
import os

import pdfplumber

def extract_text_pdfplumber(pdf_path):
    text = ""
    with pdfplumber.open(pdf_path) as pdf:
        for page in pdf.pages:
            page_text = page.extract_text()
            if page_text:
                text += page_text + "\n"
    return text

from pdf2image import convert_from_path
import pytesseract
import os

def extract_text_pymupdf(pdf_path):
    doc = fitz.open(pdf_path)
    text = "\n".join([page.get_text("text") for page in doc])
    doc.close()
    #os.remove(pdf_path)  # Usunięcie tymczasowego pliku PDF
    return text



def extract_text_ocr(pdf_path):
    images = convert_from_path(pdf_path)
    text = ""
    for i, image in enumerate(images):
        page_text = pytesseract.image_to_string(image, lang="pol")  # język polski
        text += f"\n\n=== Strona {i+1} ===\n\n" + page_text
    return text

from pdf2image import convert_from_path
from PIL import Image
import pytesseract
import os



def extract_text_ocr_rotated(pdf_path, rotation_degrees=90):   #na razie nie używane - była próba, bo PPL CZ są obrócone
    images = convert_from_path(pdf_path)
    text = ""
    for i, img in enumerate(images):
        # Obróć stronę przed OCR
        rotated_img = img.rotate(rotation_degrees, expand=True)

        # Rozpoznaj tekst
        page_text = pytesseract.image_to_string(rotated_img, lang="pol")
        text += f"\n\n=== Strona {i+1} ===\n\n{page_text}"

    return text




# Użycie:
pdf_path = download_pdf(url)
extracted_text = extract_text_pymupdf(pdf_path)    #domyślny sposób ekstrakcji tekstu
if not extracted_text.strip():                     #jeśli domyślny sposób da pusty rezultat ruszaj z OCR
    extracted_text = extract_text_ocr(pdf_path)
    #print('użyto OCR')

os.remove(pdf_path)  # Usunięcie tymczasowego pliku PDF

print(extracted_text)





# Analiza tekstu
import re



if "GLS" in extracted_text:
    lines = extracted_text.split("\n")
    target_line = None
    print("Kurier to GLS")

    for i, line in enumerate(
        lines
    ):  # Szuka w trzeciej linijce pod napisem "Your GLS Track ID:"
        if "Your GLS Track ID:" in line:  # Znalezienie frazy
            if i + 3 < len(lines):  # Sprawdzenie, czy istnieje trzeci wiersz poniżej
                target_line = lines[i + 3]  # Pobranie trzeciego wiersza
                print(f"GLS|{target_line}")  # Zwracamy GLS + numer przesyłki
                break
    if (
        not target_line
    ):  # Jeśli nie znaleziono GLS Track ID, przeszukujemy cały tekst pod kątem 12-znakowej liczby
        matches = re.findall(
            r"\b\d{12}\b", extracted_text
        )  # Znalezienie wszystkich 12-znakowych liczb
        if matches:
            for match in matches:
                print(f"GLS|{match}")  # Zwracamy każdą znalezioną liczbę
elif "DPD" in extracted_text:
    lines = extracted_text.split("\n")
    dpd_results = []  # Lista do przechowywania wyników
    print("Kurier to DPD")

    for line in lines:  # Przeszukiwanie każdej linijki
        if "-DPD-" in line:  # Szukanie wystąpienia "-DPD-"
            dpd_results.append(f"DPD|{line.strip()}")  # Dodanie wyniku do listy

    # Wyświetlenie wszystkich znalezionych numerów
    if dpd_results:
        print(
            "\n".join(dpd_results)
        )  # Łączymy wyniki w jeden string z podziałem na linie
    import re
elif "DHL" in extracted_text:
    print("Kurier to DHL")
    lines = extracted_text.split("\n")
    results = []  # Lista do przechowywania wyników

    pattern = (
        r"Nr przesyłki:\s*(\d+)"  # Wzorzec do wyłapywania liczby po "Nr przesyłki:"
    )

    for line in lines:  # Przeszukiwanie każdej linijki
        match = re.search(pattern, line)  # Szukanie numeru przesyłki
        if match:
            results.append(
                f"DHL|{match.group(1)}"
            )  # Dodanie znalezionego numeru przesyłki

    # Wyświetlenie wszystkich znalezionych numerów
    if results:
        print("\n".join(results))  # Łączymy wyniki w jeden string z podziałem na linie
elif "MIG" in extracted_text:
    print("Kurier to MIG")
    lines = extracted_text.split("\n")
    results = []  # Lista do przechowywania wyników
    for i, line in enumerate(lines):  # Przeszukiwanie każdej linijki
        if "Shipment number" in line:  # Znalezienie frazy "Shipment number"
            if i + 1 < len(lines):  # Sprawdzenie, czy istnieje następny wiersz
                results.append(
                    f"MIG|{lines[i + 1].strip()}"
                )  # Dodanie do listy, usunięcie zbędnych spacji
    # Wyświetlenie wszystkich znalezionych numerów
    if results:
        print("\n".join(results))  # Łączymy wyniki w jeden string z podziałem na linie
elif "inpost" in extracted_text.lower():
    print("Kurier to Inpost")
    lines = extracted_text.split("\n")
    results = []  # Lista do przechowywania wyników
    pattern = r"\b\d{24}\b"  # Wzorzec do znalezienia ciągu 24 cyfr

    for line in lines:  # Przeszukiwanie każdej linijki
        match = re.search(pattern, line)  # Szukanie pasującego ciągu 24 cyfr
        if match:
            results.append(
                f"InPost|{match.group()[:20]}"
            )  # Pobranie pierwszych 20 znaków
    # Wyświetlenie wszystkich znalezionych numerów
    if results:
        print("\n".join(results))  # Łączymy wyniki w jeden string z podziałem na linie
elif "ppl" in extracted_text.lower():  # PDF PO ŚCIĄGNIĘCIU NIE JEST KLIKALNY!!! 
    print("Kurier to PPL") 
    lines = extracted_text.split("\n")
    results = []  # Lista do przechowywania wyników
    print("Proba z PPL")

    # Szukanie "Weight:" (ignorując wielkość liter)
    for line in lines:
        if "weight:" in line.lower():
            match = re.search(r"(?i)\bweight\b.*?(\d+)", line)  # Ignoruje wielko�� liter, szuka dowolnej liczby obok "Weight"
            if match:
                results.append(f"PPL|{match.group(1)}")  # Dodanie wyniku do listy

    # Jeśli lista `results` jest pusta, szukamy 11-cyfrowej liczby po której jest znak '-' -> to wersja dla PPL CZ
    if not results:  
            for line in lines:                  
                match = re.search(r"\b\d{11}(?=-)", line)  # Szukamy liczby 11-cyfrowej przed "-"               
                if match:
                    results.append(f"PPL|{match.group()}")  # Dodanie wyniku do listy
    # Wyświetlenie wszystkich znalezionych wartości
    if results:
        print("\n".join(results))  # Łączymy wyniki w jeden string z podziałem na linie


elif "QEOA" in extracted_text or "Fedex" in extracted_text or "TNT" in extracted_text:
    print("Kurier to Fedex/Qeoa")
    lines = extracted_text.split("\n")
    results = []  # Lista do przechowywania wyników

    pattern = r"\b\d{4} \d{4} \d{4}\b"  # Wzorzec dla liczby w formacie XXXX XXXX XXXX

    for line in lines:  # Przeszukiwanie każdej linijki
        match = re.search(pattern, line)  # Szukanie pasującej liczby
        if match:
            formatted_number = match.group().replace(" ", "")  # Usunięcie spacji
            results.append(f"Fedex|{formatted_number}")  # Dodanie do wyników
    # Wyświetlenie wszystkich znalezionych numerów
    if results:
        print("\n".join(results))  # Łączymy wyniki w jeden string z podziałem na linie
elif "raben" in extracted_text.lower():
    print("Kurier to Raben")
    lines = extracted_text.split("\n")
    results = []  # Lista do przechowywania wyników
    for line in lines:  # Przeszukiwanie każdej linijki
        match = re.search(r"\d{15}", line)  # Szukanie dokładnie 15-cyfrowej liczby
        if match:  # Poprawione wcięcie — teraz sprawdzane wewnątrz pętli
            results.append(f"Raben|{match.group()}")  # Zwracamy znalezioną liczbę
    # Wyświetlenie wszystkich znalezionych wartości
    if results:
        print("\n".join(results))  # Łączymy wyniki w jeden string z podziałem na linie
elif "ups" in extracted_text.lower():
    print("Kurier to UPS")
    lines = extracted_text.split("\n")
    results = []  # Lista do przechowywania wyników
    
    for line in lines:  # Przeszukiwanie każdej linijki
        match = re.search(r"TRACKING #:\s*(.+)", line)  # Szukanie "TRACKING #:" i pobranie reszty wiersza
        if match:
            results.append(f"UPS|{match.group(1).strip()}")  # Usunięcie zbędnych białych znaków

    # Wyświetlenie wszystkich znalezionych wartości
    if results:  # Teraz `results` jest poprawnie zdefiniowane
        print("\n".join(results))  # Łączymy wyniki w jeden string z podziałem na linie
elif "toptrans" in extracted_text.lower():
    print("Kurier to Toptrans")
    lines = extracted_text.split("\n")
    results = []  # Lista do przechowywania wyników
    for line in lines:  # Przeszukiwanie każdej linijki
        match = re.search(r"Podací číslo:\s*(.+)", line)  # Szukanie "Podací číslo:" i pobranie reszty wiersza
        if match:
            results.append(f"Toptrans|{match.group(1).strip()}")  # Usunięcie zbędnych białych znaków
    # Wyświetlenie wszystkich znalezionych wartości
    if results:
        print("\n".join(results))  # Łączymy wyniki w jeden string z podziałem na linie
else:
     print('Nie znaleziono kuriera')
