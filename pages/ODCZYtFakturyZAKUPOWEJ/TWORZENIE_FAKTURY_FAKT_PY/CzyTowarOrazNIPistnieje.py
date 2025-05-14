import csv
import tkinter as tk
from tkinter import messagebox
from tkinter import ttk
from CzyTowarIstniejeDETALE import czy_towar_istnieje
import subprocess


# Funkcja do wczytywania danych z pliku CSV do zmiennej
def load_csv_to_matrix(file_path):
    with open(file_path, 'r', newline='', encoding='utf-8') as csvfile:
        reader = csv.reader(csvfile, delimiter=',')
        matrix = [row for row in reader]  # Wczytanie wszystkich wierszy do listy
    return matrix


import sys
import tkinter as tk
from tkinter import messagebox


def czy_nip_jest_w_bazie(nip, kontrahenci_baza):
    # Przechodzimy przez kontrahentów i sprawdzamy, czy NIP istnieje
    for row in kontrahenci_baza:
        # Zakładając, że NIP jest w drugiej kolumnie (indeks 1) w każdym wierszu
        if row[0] == nip:
            return "NIP istnieje"

    # Jeśli NIP nie istnieje, wyświetlamy komunikat w oknie MessageBox i przerywamy skrypt
    root = tk.Tk()  # Tworzymy główne okno tkinter (będzie ukryte)
    root.withdraw()  # Ukrywamy główne okno, bo nie jest potrzebne
    messagebox.showerror("Błąd", "NIP nie istnieje")  # Wyświetlamy okno z komunikatem
    sys.exit(1)  # Przerywamy skrypt z kodem błędu 1



##############################################################################################################


##############################################################################################################


# Ścieżki do plików CSV
zlecenie_file_path = r"\\SEKRET-ANIA-079\Users\Sekretariat\Desktop\FV w pdf\Scripts\InvoiceFromPROSTO\zlecenie.csv"
kontrahenci_file_path = r"\\SEKRET-ANIA-079\Users\Sekretariat\Desktop\FV w pdf\Scripts\InvoiceFromPROSTO\kontrahenci.csv"
towary_file_path = r"\\SEKRET-ANIA-079\Users\Sekretariat\Desktop\FV w pdf\Scripts\InvoiceFromPROSTO\lista_towarow.csv"

# Wczytanie danych do zmiennych
zlecenie_baza = load_csv_to_matrix(zlecenie_file_path)
kontrahenci_baza = load_csv_to_matrix(kontrahenci_file_path)
towary_baza = load_csv_to_matrix(towary_file_path)


# Wyświetlenie przykładowych wyników (możesz usunąć te linie, jeśli nie są potrzebne)
#print("Zlecenie Baza:", zlecenie_baza)
#print("Kontrahenci Baza:", kontrahenci_baza)
#print("Towary Baza:", towary_baza)



# Przykład użycia:
# Załóżmy, że 'kontrahenci_baza' to już wczytana lista z pliku CSV.
nip_to_check = zlecenie_baza[1][1]  # NIP z drugiego wiersza i drugiej kolumny
CzyNIPistnieje = czy_nip_jest_w_bazie(nip_to_check, kontrahenci_baza)

print(CzyNIPistnieje)

CzyTowarIstnieje = czy_towar_istnieje(zlecenie_baza,towary_baza)   #wywołanie funkcji z TABELKĄ TOWARÓW

# Wyświetlanie pytania o fakturę
if messagebox.askquestion("Tworzyć fakturę?", "Czy chcesz tworzyć fakturę w FAKT?") == 'yes':
    print("uruchaiam testowo")
    subprocess.run(["python", r"C:\Users\Krzysztof\Desktop\SKRYPTY\SALESInvoicesFromProstoToFakt\CreateFAKTInvoice.py"],stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
else:
    print("Proces zakończony.")





