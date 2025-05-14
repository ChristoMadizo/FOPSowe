import csv
import pygetwindow as gw
import pyautogui
from UstalenieStawkiVAT import get_vat_and_arrows
import sys
from CzekajNaOkno import czekaj_na_okno
import pyperclip

# 1. Pobranie danych z pliku CSV do zmiennej "macierz"
csv_file_path = r'\\SEKRET-ANIA-079\Users\Sekretariat\Desktop\FV w pdf\Scripts\InvoiceFromPROSTO\zlecenie.csv'

macierz = []

try:
    with open(csv_file_path, mode='r', encoding='utf-8') as csvfile:
        csvreader = csv.reader(csvfile, delimiter=',')
        for row in csvreader:
            macierz.append(row)
    print("Dane zostały pomyślnie pobrane do zmiennej 'macierz'.")
except Exception as e:
    print(f"Nie udało się wczytać pliku: {e}")

# 2. Liczba wierszy w pliku (bez nagłówków)
Liczba_wierszy = len(macierz) - 1  # Odejmujemy 1, ponieważ pierwszy wiersz to nagłówek
print(f"Liczba wierszy w pliku (bez nagłówków): {Liczba_wierszy}")

# 3. Aktywacja okna "Fakt - wersja"
try:
    # Szuka okna o tytule zawierającym "Fakt - wersja"
    window = gw.getWindowsWithTitle('Fakt - wersja')

    if window:
        window[0].activate()  # Aktywuj pierwsze okno, które pasuje do tytułu
        print("Okno 'Fakt - wersja' zostało aktywowane.")
    else:
        print("Nie znaleziono okna o tytule 'Fakt - wersja'.")
except Exception as e:
    print(f"Nie udało się aktywować okna lub wysłać klawisza: {e}")


sleep_short=0.2
sleep_long=1

#pobiera dane z macierzy (nagłówek dokumentu)
NIP = macierz[1][1]
DataZamowienia=macierz[1][3]
Zamowienie=macierz[1][4]
Waluta=macierz[1][11]
KrajKontrahenta=macierz[1][13]


IloscStrzalekVAT=get_vat_and_arrows(KrajKontrahenta) #ustala ilosc strzalek do stawki VAT

#wprowadza NIP kontrahenta i otwiera okno dodawania pozycji
pyautogui.press('insert')  #dodaje fakturę
czekaj_na_okno('Dokument sprzedaży')
pyautogui.write(NIP,interval=0.05)
pyautogui.sleep(2)

for nr_pozycji in range(1, Liczba_wierszy + 1):
    #pobiera dane z macierzy (pozycje dokumentu)
    UwagiDoZamowienia=macierz[nr_pozycji][5]
    NazwaFakt=macierz[nr_pozycji][6]
    Ilosc=float(macierz[nr_pozycji][8].replace(',', '.'))  # Konwersja na liczbę zmiennoprzecinkową
    Ilosc=str(round(Ilosc,2))
    #Ilosc="{:.2f}".format(Ilosc).replace('.', '') # usuwa kropkę, bo powoduje dopisanie dwóch zer przy pyautogui.write

    Cena = float(macierz[nr_pozycji][9].replace(',', '.'))  # Zamienia przecinki na kropki i konwertuje na float
    Cena = round(Cena, 2)  # Zaokrągla do dwóch miejsc po przecinku
    Cena = "{:.2f}".format(Cena).replace('.', '') #usuwa kropkę, bo powoduje dopisanie dwóch zer przy pyautogui.write

    pyautogui.press('insert') #WSTAWIA POZYCJĘ
    czekaj_na_okno('Pozycja na fakturze')
    pyautogui.sleep(sleep_long)

    pyperclip.copy(NazwaFakt)  # Kopiuje NazwaFakt do schowka - zamiast "write", bo był problem z polskimi znakami
    pyautogui.hotkey('ctrl', 'v')  # Wkleja tekst

    pyautogui.sleep(sleep_long)

    #pyautogui.press('tab',presses=8) #przechodzi do pola ilości - chyba nie trzeba, sam się tam ustawia jak wybierze item na liście

    #TU DOPISAĆ SPRAWDZANIE CZY ITEM JEST W BAZIE? To chyba zawartość tej kontrolki

    pyautogui.sleep(sleep_short)
    pyautogui.hotkey('ctrl','a') #zaznacza ilosc

    pyautogui.write(Ilosc,interval=0.05) #wkleja ilosc
    pyautogui.sleep(sleep_short)
    pyautogui.press('tab') #przechodzi do pola ceny
    pyautogui.sleep(sleep_short)
    #pyautogui.hotkey('ctrl','a') #zaznacza cenę
    #pyautogui.sleep(sleep_short)


    pyautogui.sleep(sleep_short)
    pyautogui.write(str(Cena),interval=0.01) #wpisuje cenę

    pyautogui.press('tab',presses=2) #przechodzi do pola stawki VAT
    pyautogui.sleep(sleep_short)
    pyautogui.press('up',presses=30) #na samą góre listy stawek VAT
    pyautogui.sleep(sleep_short)
    pyautogui.press('down',presses=IloscStrzalekVAT)
    pyautogui.sleep(sleep_short)



    if NIP=='CZ05372275': #jeśli to FOPS CZ to upust jest 11%
        pyautogui.press('tab',presses=7) #przechodzi do pola upust
        pyautogui.write('11', interval=0.05)  # wpisuje cenę
        pyautogui.sleep(sleep_short)
        pyautogui.press('tab', presses=27)  # przechodzi do pola uwagi
        pyautogui.sleep(sleep_short)
        pyperclip.copy(UwagiDoZamowienia)  # Kopiuje UwagiDoZamowienia do schowka - zamiast "write", bo był problem z polskimi znakami
        pyautogui.hotkey('ctrl', 'v')  # Wkleja tekst
        #pyautogui.write(UwagiDoZamowienia, interval=0.01)  # wpisuje uwagi (nazwę z PROSTO)
        pyautogui.press('F9') #zapisuje pozycję
        czekaj_na_okno
    else:  #jeśli to nie FOPS CZ
        pyautogui.press('tab', presses=4)
        pyautogui.sleep(sleep_short)
        pyperclip.copy(
        UwagiDoZamowienia)  # Kopiuje UwagiDoZamowienia do schowka - zamiast "write", bo był problem z polskimi znakami
        pyautogui.hotkey('ctrl', 'v')  # Wkleja tekst
        #pyautogui.write(UwagiDoZamowienia, interval=0.01)  # wpisuje uwagi (nazwę z PROSTO)
        pyautogui.press('F9')  # zapisuje pozycję


czekaj_na_okno('Dokument sprzeda')
pyautogui.press('tab',presses=7)  #kończy dodawać pozycje, idzie do reszty nagłówka dokumentu - data dostawy
pyautogui.press('tab', presses=1) #idzie do daty VAT
pyautogui.press('tab', presses=5)  # idzie do Rejestru
pyautogui.press('tab', presses=1)  # idzie do rodzaju platnosci
pyautogui.press('tab', presses=1)  # idzie do terminu platnosci (dni)

if Waluta != 'PLN': #jeśli waluta to nie PLN, to ustawia odpowiednią
    pyautogui.press('tab', presses=11)  # idzie do walut
    pyautogui.sleep(sleep_short) #otwiera okno walut
    pyautogui.press('space')
    czekaj_na_okno('Dokument - waluty')
    pyautogui.press('space') #zaznacza, że dokument jest w walucie
    pyautogui.press('tab', presses=3)  # idzie do pola z nazwą waluty
    pyautogui.write(Waluta, interval=0.05) #wpisuje kod waluty
    pyautogui.press('F9')  # Zapisuje walutę

pyautogui.press('F9')  #Zapisuje fakture
pyautogui.sleep(sleep_short)
pyautogui.press('esc')  #anuluje wydruk








