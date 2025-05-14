import firebirdsql
import csv
import re

# Ścieżka do bazy danych Firebird
db_path = r'/C:\KOPIA_FAKT_AKTUALNA\0002BAZA.FDB'
dsn = f'localhost:{db_path}'  # Poprawny format DSN z protokołem

# Połączenie z bazą danych
conn = firebirdsql.connect(dsn=dsn, user='SYSDBA', password='masterkey')  # Podaj odpowiednie dane logowania

# Zapytanie SQL
query = "SELECT NIP FROM TAB_KONT"

# Wykonanie zapytania
cur = conn.cursor()
cur.execute(query)

# Ścieżka do pliku CSV
csv_file = r"\\SEKRET-ANIA-079\Users\Sekretariat\Desktop\FV w pdf\Scripts\InvoiceFromPROSTO\kontrahenci.csv"

# Zapisanie wyników do pliku CSV
with open(csv_file, mode='w', newline='', encoding='utf-8') as file:
    writer = csv.writer(file)
    writer.writerow(['NIP'])  # Nagłówek poprawiony z 'NAZWA' na 'NIP'

    for row in cur:
        nip = row[0] if row[0] else ""  # Obsługa wartości NULL
        clean_nip = re.sub(r'\D', '', nip)  # Usunięcie wszystkich znaków nienumerycznych
        writer.writerow([clean_nip])

# Zamknięcie połączenia
cur.close()
conn.close()

print(f'Wynik zapytania zapisany do {csv_file}')
