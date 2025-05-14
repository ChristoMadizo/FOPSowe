import firebirdsql
import csv

# Ścieżka do bazy danych Firebird
db_path = r'/C:\KOPIA_FAKT_AKTUALNA\0002BAZA.FDB'
dsn = f'localhost:{db_path}'  # Poprawny format DSN z protokołem

# Połączenie z bazą danych
conn = firebirdsql.connect(dsn=dsn, user='SYSDBA', password='masterkey')  # Podaj odpowiednie dane logowania

# Zapytanie SQL
query = "SELECT NAZWA FROM TAB_TOWA"

# Wykonanie zapytania
cur = conn.cursor()
cur.execute(query)

# Ścieżka do pliku CSV
csv_file = r"\\SEKRET-ANIA-079\Users\Sekretariat\Desktop\FV w pdf\Scripts\InvoiceFromPROSTO\lista_towarow.csv"

# Zapisanie wyników do pliku CSV
with open(csv_file, mode='w', newline='', encoding='utf-8') as file:
    writer = csv.writer(file)
    writer.writerow(['NAZWA'])  # Nagłówek
    for row in cur:
        writer.writerow(row)

# Zamknięcie połączenia
cur.close()
conn.close()

print(f'Wynik zapytania zapisany do {csv_file}')
