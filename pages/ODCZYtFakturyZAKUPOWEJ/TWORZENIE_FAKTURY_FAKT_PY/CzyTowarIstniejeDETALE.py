import tkinter as tk
from tkinter import ttk
import csv
from tkinter import messagebox
import subprocess

# Funkcja aktualizująca zmienną zlecenie_baza_aktualne
def aktualizuj_zlecenie_baza(macierz, row_frames):
    zlecenie_baza_aktualne = []

    for index, row_frame in enumerate(row_frames):
        # Pobieramy wartość z kolumny 4 (indeks 3) z formularza (OptionMenu)
        selected_towar = row_frame.winfo_children()[4].winfo_children()[0].cget('text').strip()

        # Pobieramy dane z macierzy (załóżmy, że kolumny są w porządku)
        row = macierz[index + 1]  # +1 bo indeks w formularzu zaczyna się od 1
        row_aktualizowane = list(row)  # Tworzymy kopię wiersza

        # Aktualizujemy kolumnę 7 (indeks 6) wartością z formularza (kolumna 4)
        row_aktualizowane[6] = selected_towar

        # Dodajemy zaktualizowany wiersz do nowej zmiennej
        zlecenie_baza_aktualne.append(row_aktualizowane)

    return zlecenie_baza_aktualne

def zapisz_do_csv(zlecenie_baza_aktualne, sciezka_do_pliku):
    naglowki = ["LP", "Prost", "Towar", "Opis", "Jm", "Ilosc", "Wartosc netto", "Waluta"]  # Przykładowe nagłówki, dostosuj do swoich danych

    # Otwieramy plik w trybie 'w', co oznacza zapisanie nowego pliku lub nadpisanie istniejącego
    with open(sciezka_do_pliku, mode='w', newline='', encoding='utf-8') as file:
        writer = csv.writer(file, delimiter=',', quotechar='"', quoting=csv.QUOTE_MINIMAL)

        # Zapisujemy nagłówki
        writer.writerow(naglowki)

        # Zapisujemy dane z tabeli (od drugiego wiersza)
        for row in zlecenie_baza_aktualne:
            writer.writerow(row)

    print(f"Zapisano dane do pliku: {sciezka_do_pliku}")

def czy_towar_istnieje(macierz, towary_baza):
    towary_baza_cleaned = [str(item[0]).strip("'").lower() for item in towary_baza]
    results = []
    suma_ilosci = 0
    suma_wartosci = 0
    waluta = None

    root = tk.Tk()
    root.title("Sprawdzanie towarów")

    screen_width = root.winfo_screenwidth()
    screen_height = root.winfo_screenheight()
    window_width = 1300
    window_height = 600

    position_top = int(screen_height / 2 - window_height / 2)
    position_left = int(screen_width / 2 - window_width / 2)

    root.geometry(f'{window_width}x{window_height}+{position_left}+{position_top}')

    form_frame = ttk.Frame(root, borderwidth=2, relief="solid")
    form_frame.pack(padx=10, pady=50, fill='both', expand=True)

    def sprawdz_wszystkie_zielone():
        for row_frame in row_frames:
            if row_frame.cget('background') != 'lightgreen':
                return False
        return True

    def on_focus_out(event, text_entry, option_var, towary_baza_cleaned):
        value = text_entry.get().strip()
        if value:
            # Sprawdzanie, czy kod istnieje w bazie
            exists_in_list = value.lower() in towary_baza_cleaned

            # Ustawianie tła w zależności od istnienia w bazie
            row_frame = text_entry.master
            if exists_in_list:
                row_frame.config(background="lightgreen")
                for widget in row_frame.winfo_children():
                    widget.config(background="lightgreen")
                option_var.set(value)
            else:
                row_frame.config(background="lightpink")
                for widget in row_frame.winfo_children():
                    widget.config(background="lightpink")
                option_var.set('')
        else:
            option_var.set('')

    row_frames = []

    for index, row in enumerate(macierz[1:], start=1):
        towar = row[6].lower()
        exists_in_list = towar in towary_baza_cleaned
        row_color = 'lightgreen' if exists_in_list else 'lightpink'

        row_frame = tk.Frame(form_frame, background=row_color, borderwidth=1, relief="solid")
        row_frame.grid(row=index, column=0, columnspan=10, sticky="ew", padx=2, pady=2)

        row_frames.append(row_frame)

        row_frame.columnconfigure(0, minsize=30)
        row_frame.columnconfigure(1, minsize=200)
        row_frame.columnconfigure(2, minsize=200)
        row_frame.columnconfigure(3, minsize=300)
        row_frame.columnconfigure(4, minsize=50)
        row_frame.columnconfigure(5, minsize=100)
        row_frame.columnconfigure(6, minsize=10)
        row_frame.columnconfigure(7, minsize=5)
        row_frame.columnconfigure(8, minsize=15)
        row_frame.columnconfigure(9, minsize=5)

        lp_label = tk.Label(row_frame, text=str(index), background=row_color, borderwidth=1, relief="solid", anchor="w",
                            width=5)
        lp_label.grid(row=0, column=0, sticky='w', padx=5, pady=2)

        prost_label = tk.Label(row_frame, text=row[5], background=row_color, borderwidth=1, relief="solid", anchor="w",
                               width=28)
        prost_label.grid(row=0, column=1, sticky='w', padx=5, pady=2)

        towar_label = tk.Label(row_frame,
                               text=f"Wiersz {index}: {towar} - {'istnieje' if exists_in_list else 'nie istnieje'}",
                               background=row_color, borderwidth=1, relief="solid", anchor="w", width=28)
        towar_label.grid(row=0, column=2, sticky='w', padx=5, pady=2)

        text_entry = tk.Entry(row_frame, background='lightgray', width=15)
        text_entry.grid(row=0, column=5, padx=5, pady=2, sticky='w')

        if not row[6]:
            filtr = row[5][:5].lower()
        else:
            filtr = row[6][:5].lower()

        filtered_options = [item[0] for item in towary_baza if item[0].lower().startswith(filtr)]

        selected_option = tk.StringVar(root)
        if filtered_options:
            selected_option.set(filtered_options[0])

        option_frame = tk.Frame(row_frame, background=row_color, borderwidth=1, relief="solid")
        option_frame.grid(row=0, column=3, padx=5, pady=2, sticky='w')

        towar_optionmenu = ttk.OptionMenu(option_frame, selected_option, *filtered_options)
        towar_optionmenu.pack(padx=1, pady=1)

        text_entry.bind("<FocusOut>",
                        lambda event, entry=text_entry, opt=selected_option: on_focus_out(event, entry, opt,
                                                                                          towary_baza_cleaned))

        try:
            if len(row) > 8:
                ilosc = float(row[8])
                ilosc_label = tk.Label(row_frame, text=f"{ilosc:.2f}", background=row_color, borderwidth=1,
                                       relief="solid", anchor="e", width=10)
                suma_ilosci += ilosc
            else:
                ilosc_label = tk.Label(row_frame, text="Brak danych", background=row_color, borderwidth=1,
                                       relief="solid", anchor="e", width=10)
        except ValueError:
            ilosc_label = tk.Label(row_frame, text="0.00", background=row_color, borderwidth=1, relief="solid",
                                   anchor="e", width=10)
        ilosc_label.grid(row=0, column=6, sticky='e', padx=5, pady=2)

        jm_label = tk.Label(row_frame, text=row[12] if len(row) > 12 else "Brak", background=row_color, borderwidth=1,
                            relief="solid", anchor="w", width=5)
        jm_label.grid(row=0, column=7, sticky='w', padx=5, pady=2)

        try:
            wartosc_netto = float(row[10])
            wartosc_label = tk.Label(row_frame, text=f"{wartosc_netto:.2f}", background=row_color, borderwidth=1,
                                     relief="solid", anchor="e", width=10)
            suma_wartosci += wartosc_netto
        except ValueError:
            wartosc_label = tk.Label(row_frame, text="0.00", background=row_color, borderwidth=1, relief="solid",
                                     anchor="e", width=15)
        wartosc_label.grid(row=0, column=8, sticky='e', padx=5, pady=2)

        waluta_label = tk.Label(row_frame, text=row[11] if len(row) > 11 else "Brak", background=row_color,
                                borderwidth=1, relief="solid", anchor="w", width=5)
        waluta_label.grid(row=0, column=9, sticky='w', padx=5, pady=2)

        if waluta is None and len(row) > 11:
            waluta = row[11]

        def update_status(selected_option, row_frame, towar_label):
            if selected_option.get():
                row_frame.config(background="lightgreen")
                for widget in row_frame.winfo_children():
                    widget.config(background="lightgreen")
                towar_label.config(text=selected_option.get())
                update_button_state()

        selected_option.trace("w",
                              lambda *args, opt=selected_option, rf=row_frame, tl=towar_label: update_status(opt, rf,
                                                                                                             tl))

        results.append((index, selected_option))

    suma_label = tk.Label(root, text=f"Podsumowanie ilości: {suma_ilosci:.2f}", background='lightblue',
                          font=('Arial', 14), anchor='w')
    suma_label.pack(pady=5)

    suma_wartosci_label = tk.Label(root,
                                   text=f"Podsumowanie wartość netto: {suma_wartosci:.2f} {waluta if waluta else ''}",
                                   background='lightblue', font=('Arial', 14), anchor='w')
    suma_wartosci_label.pack(pady=5)

    def on_button_click():
        # Zaktualizowanie zlecenia_baza_aktualne po kliknięciu przycisku
        zlecenie_baza_aktualne = aktualizuj_zlecenie_baza(macierz, row_frames)
        print("Zaktualizowane zlecenie_baza_aktualne:", zlecenie_baza_aktualne)
        # Zapisujemy dane do pliku CSV
        sciezka_do_pliku = r"\\SEKRET-ANIA-079\Users\Sekretariat\Desktop\FV w pdf\Scripts\InvoiceFromPROSTO\zlecenie.csv"
        zapisz_do_csv(zlecenie_baza_aktualne, sciezka_do_pliku)
        # Zamknięcie okna
        root.destroy()


    button = ttk.Button(root, text="Twórz fakturę", command=on_button_click)
    button.pack(pady=10)
    button.config(state="disabled")

    def update_button_state():
        if sprawdz_wszystkie_zielone():
            button.config(state="normal")
        else:
            button.config(state="disabled")

    root.after(1000, update_button_state)

    root.mainloop()
