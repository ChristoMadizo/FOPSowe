import mysql.connector
import sys
import os
import csv
import subprocess

# Dane do logowania do bazy danych
db_config = {
    'host': '192.168.101.240',  # Adres IP serwera bazy danych
    'user': 'kmadzia',  # Login do bazy danych
    'password': 'PLdpzwZ]gvj_W5SZ',  # Hasło do bazy danych
    'database': 'prosto',  # Podaj odpowiednią nazwę bazy danych
    'port': 3306  # Port, na którym działa MySQL
}

# Pobranie numeru zlecenia przekazanego z pliku BAT lub innego źródła
if len(sys.argv) > 1:
    order_number = sys.argv[1]  # Numer zlecenia przekazywany z pliku BAT lub jako argument
else:
    print("Brak numeru zlecenia. Skrypt wymaga numeru zlecenia jako argument.")
    exit(1)

# Pobranie ścieżki katalogu, w którym znajduje się skrypt Python
script_dir = os.path.dirname(os.path.realpath(__file__))

# Pełna ścieżka do pliku skrypt_sql.sql (plik musi być w tym samym katalogu)
sql_file_path = os.path.join(script_dir, 'PobierzDaneZlecenia.sql')

# Ścieżka do pliku CSV na VM
output_dir_vm = '/home/kmadzia/SKRYPTY/SalesInvoicesFromProstoToFakt'  # Zmieniona ścieżka na VM
output_file_vm = os.path.join(output_dir_vm, 'zlecenie.csv')

# Wczytanie zapytania SQL z pliku
try:
    with open(sql_file_path, 'r') as file:
        sql_query = file.read()

except FileNotFoundError:
    print(f"Plik SQL nie został znaleziony: {sql_file_path}")
    exit(1)

try:
    # Połączenie z bazą danych
    connection = mysql.connector.connect(**db_config)
    cursor = connection.cursor()

    # Wykonanie zapytania z parametrem, zachowując bezpieczne przekazywanie argumentu
    cursor.execute(sql_query, (order_number,))  # Przekazanie numeru zlecenia jako parametr
    results = cursor.fetchall()  # Pobranie wszystkich wyników zapytania
    column_names = [desc[0] for desc in cursor.description]  # Pobranie nazw kolumn

    # Zapisywanie wyników do pliku CSV na VM
    os.makedirs(output_dir_vm, exist_ok=True)  # Tworzymy katalog, jeśli nie istnieje
    with open(output_file_vm, 'w', newline='', encoding='utf-8') as csvfile:
        csvwriter = csv.writer(csvfile, delimiter=',')
        csvwriter.writerow(column_names)  # Zapisujemy nagłówki kolumn
        csvwriter.writerows(results)  # Zapisujemy dane

    print(f"Wynik zapytania SQL zapisano na VM do pliku: {output_file_vm}")

except mysql.connector.Error as e:
    print(f"Błąd podczas połączenia z bazą danych: {e}")

except Exception as e:
    print(f"Błąd: {e}")

finally:
    # Zamknięcie połączenia z bazą danych
    if 'cursor' in locals():
        cursor.close()
    if 'connection' in locals() and connection.is_connected():
        connection.close()

#subprocess.run(["python", r"C:\Users\Krzysztof\Desktop\SKRYPTY\SALESInvoicesFromProstoToFakt\CzyTowarOrazNIPistnieje.py"])   - URUCHAMIA SIĘ PRZEZ BAT
