import pygetwindow as gw
import time
import tkinter as tk
from tkinter import messagebox
import sys


def czekaj_na_okno(nazwa_okna, timeout=3):
    """
    Czeka, aż aktywne okno będzie miało określoną nazwę.

    :param nazwa_okna: Nazwa okna, na które ma czekać skrypt.
    :param timeout: Maksymalny czas oczekiwania (w sekundach).
    :return: True, jeśli okno się pojawiło, kończy działanie skryptu w przeciwnym razie.
    """
    start_time = time.time()  # Pobranie czasu początkowego
    while time.time() - start_time < timeout:  # Pętla działa maksymalnie przez `timeout` sekund
        aktywne_okno = gw.getActiveWindow()  # Pobiera aktywne okno
        if aktywne_okno and nazwa_okna in aktywne_okno.title:  # Sprawdza, czy tytuł okna zawiera nazwę
            return True  # Okno zostało znalezione
        time.sleep(0.1)  # Odczekanie 100 ms przed kolejnym sprawdzeniem

    # Wyświetlenie komunikatu, jeśli okno nie zostało znalezione
    root = tk.Tk()
    root.withdraw()  # Ukrywa główne okno Tkintera
    messagebox.showerror("Błąd", f"Nie znaleziono okna '{nazwa_okna}' w czasie {timeout} sekund.")
    sys.exit(1)  # Kończy działanie skryptu z kodem błędu 1
