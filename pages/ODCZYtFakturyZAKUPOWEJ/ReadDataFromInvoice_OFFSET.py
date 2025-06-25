import pdfplumber
import re
import json

# Ścieżka do pliku PDF
pdf_path = r'/home/kmadzia/www/pages/ODCZYtFakturyZAKUPOWEJ/FS 337_2025.pdf'


def parse_number(value):
    """ Konwertuje liczby z separatorem tysięcznym na format liczbowy. """
    return float(value.replace(" ", "").replace(",", "."))


def is_integer(value):
    """ Sprawdza, czy wartość jest liczbą całkowitą. """
    return value.isdigit()


def extract_data_from_pdf(pdf_path):
    with pdfplumber.open(pdf_path) as pdf:
        text_lines = [page.extract_text().split("\n") for page in pdf.pages if page.extract_text()]
        text_lines = [line for page in text_lines for line in page]  # Spłaszczamy listę

    data = {}

    # Pobieranie numeru NIP
    for line in text_lines:
        if "NIP:" in line:
            data["NIP"] = re.search(r"NIP:\s*([\d-]+)", line).group(1)
            data["NIP"] = data["NIP"].replace("-", "")
            break  

    for line in text_lines:
        if "Faktura VAT" in line:
            match = re.search(r"Faktura VAT\s*(\d+/\d+)", line)
            if match:
                data["numer_faktury"] = match.group(1)
                break

    # Pobieranie terminu płatności
    for line in text_lines:
        if line.startswith("W terminie"):
            match = re.search(r"(\d{2}\.\d{2}\.\d{4})", line)  # Szuka daty w formacie DD.MM.YYYY
            if match:
                data["termin_platnosci"] = match.group(1)
            

    # Pobieranie daty wystawienia
    for i, line in enumerate(text_lines):
        if "PL95 1050 1083 1000 0090 3045 3253 Data wystawienia:" in line:
            data["data_wystawienia"] = text_lines[i + 1].strip()
            break

    # Pobieranie daty zakończenia dostawy/usług
    for i, line in enumerate(text_lines):
        if "ING Bank Śląski SA - Oddział w Cieszynie ul.Mennicza" in line:
            data["data_dostawy"] = text_lines[i + 1].strip()
            break

    # Pobieranie zamówień
    orders = []
    parsing_orders = False

    for i, line in enumerate(text_lines):
        if "netto" in line:
            parsing_orders = True
            continue
        if "według stawki VAT wartość netto kwota VAT wartość brutto" in line:
            break
        if parsing_orders:
            parts = line.split()
            if len(parts) >= 8 and is_integer(parts[0]):  # Sprawdzamy, czy pierwsza wartość to liczba
                lp = int(parts[0])
                opis = " ".join(parts[1:-7])  # Opis to wszystko między Lp a liczbami

                # Pobranie kolejnej linijki jako dodatkowy opis
                dodatkowy_opis = text_lines[i + 1].strip() if i + 1 < len(text_lines) else ""

                # Sprawdzamy, czy dodatkowy opis nie jest "Ciąg dalszy na następnej stronie"
                if dodatkowy_opis.startswith("Ciąg dalszy na następnej stronie"):
                    # Znalezienie następnej pozycji w tekście
                    for j in range(i + 2, len(text_lines)):
                        if is_integer(text_lines[j].split()[0]):  # Jeśli nowa linia zaczyna się od liczby (Lp)
                            dodatkowy_opis = text_lines[j - 1].strip()  # Pobieramy opis z wiersza nad nową pozycją
                            break

                opis = f"{opis} {dodatkowy_opis}"

                ilosc = parse_number(parts[-7])  # Ilość to przedostatnia wartość
                jednostka_miary = parts[-6]
                cena = parse_number(parts[-5])
                stawka_vat = parse_number(parts[-4])
                wartosc_netto = parse_number(parts[-3])
                vat = parse_number(parts[-2])
                wartosc_brutto = parse_number(parts[-1])

                orders.append({
                    "lp": lp,
                    "opis": opis,
                    "ilosc": ilosc,
                    "jednostka_miary": jednostka_miary,
                    "cena": cena,
                    "stawka_vat": stawka_vat,
                    "wartosc_netto": wartosc_netto,
                    "vat": vat,
                    "wartosc_brutto": wartosc_brutto,
                })

    data["zamowienia"] = orders

    # Pobieranie sumy faktury
    for i, line in enumerate(text_lines):
        if "Razem:" in line:
            parts = text_lines[i].split()
            if len(parts) >= 4:
                data["razem_netto"] = parse_number(parts[1])
                data["razem_vat"] = parse_number(parts[2])
                data["razem_brutto"] = parse_number(parts[3])
            break

    json_data = json.dumps(data, ensure_ascii=False, indent=4)  # `ensure_ascii=False` obsługuje polskie znaki

    print(json_data)  # Wyświetlenie JSON

    return json_data


# Wywołanie funkcji
extract_data_from_pdf(pdf_path)
