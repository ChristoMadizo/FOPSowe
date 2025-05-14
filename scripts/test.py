import fdb

# Parametry połączenia
host = 'localhost'
port = 3050
database_path = '/opt/firebird/FAKT_LIVE_COPY/0002BAZA.FDB'
user = 'KRZYSIEK'
password = 'Bielawa55'

# Połączenie z bazą
try:
    con = fdb.connect(
        host=host,
        port=port,
        database=database_path,
        user=user,
        password=password,
        charset='UTF8'
    )
    print("Połączono z bazą danych Firebird!")

    # Wykonanie zapytania
    cur = con.cursor()
    cur.execute("SELECT * FROM TAB_FAKT")

    # Pobranie i wyświetlenie wyników
    rows = cur.fetchall()
    for row in rows:
        print(row)

    # Zamknięcie połączenia
    cur.close()
    con.close()

except fdb.DatabaseError as e:
    print("Błąd połączenia z bazą danych:", e)
