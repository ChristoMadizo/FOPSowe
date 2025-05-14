from playwright.sync_api import sync_playwright
import time
from datetime import datetime, timedelta
import sys
import json



if len(sys.argv) > 1:  # odbieram z PHP jako parametr datę ostatniej modyfikacji switch'a
    last_switch_modification_date = sys.argv[1]
    last_switch_on_date = sys.argv[2]
    temp_wewnetrzna_czarny_czujnik = float(sys.argv[3])
else:
    # Jeśli brak argumentów, wyświetl komunikat w logach (nie trafia do PHP)
    print("Nie przekazano daty jako argumentu.", file=sys.stderr)

#last_switch_modification_date = '2025-05-05'
#last_switch_on_date = '2025-05-05'

#last_switch_modification_date='2025-03-01 14:00:00'
#last_switch_on_date='2025-03-01 14:00:00'

#print(3 <= datetime.now().hour < 16)

#current_time = datetime.now()
#current_hour = current_time.hour
#print(current_hour)

TIMEOUT_DURATION = 0.5  # sekundy
dni_wolne = ["2025-05-01", "2025-05-03", "2025-06-16", "2025-08-15"]

def wait(duration=TIMEOUT_DURATION):
    time.sleep(duration)

# Funkcja DECYZYJNA
def check_centrala_state(TEMP_ZADANA, temp_wewnetrzna_czarny_czujnik, temp_pow_zewn, last_switch_modification_date, last_switch_on_date, dni_wolne, wentylacja_state, frame2, switch_element_wyd1):
    TIME_FORMAT = "%Y-%m-%d %H:%M:%S"
    current_time = datetime.now()
    current_hour = current_time.hour
    current_date = current_time.strftime("%Y-%m-%d")
    current_weekday = current_time.strftime("%A")

    match True:
        case _ if current_date in dni_wolne:
            if wentylacja_state == "ON":
                frame2.evaluate('''(el) => el.dispatchEvent(new Event("click"))''', switch_element_wyl)
                return "Wyłączam wentylację - to dzień wolny."
            return "Zostawiam wyłączoną - to dzień wolny."

        case _ if current_weekday in ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday']:
            last_switch_modification_date = datetime.strptime(last_switch_modification_date, TIME_FORMAT)
            if (current_time - last_switch_modification_date) < timedelta(hours=2):
                return "Nie zmieniam stanu. Od ostatniego przełączenia nie minęły 2 godziny. (" + str(
                    last_switch_modification_date) + ")."

            elif 3 <= current_hour < 16:
                #print('wszedlem miedzy 3 i 16')
                #print('temp_pow_zewn: ' + str(temp_pow_zewn) + ' temp_wewn_schody:'+str(temp_wewn_schody) + 'TEMP_ZADANA: '+str(TEMP_ZADANA) )
                match True:
                    case _ if temp_pow_zewn <= 16 and temp_wewnetrzna_czarny_czujnik > TEMP_ZADANA:
                        #print('wszedlem do case _ if temp_pow_zewn < 22 and temp_wewn_schody > TEMP_ZADANA')
                        if wentylacja_state == "ON":
                            return "Zostawiam włączoną. Dzień roboczy, godzina między 3 i 16, na zewnątrz jest poniżej 16 stopni (" + str(temp_pow_zewn) + "), a temperatura wewnątrz (" + str(temp_wewnetrzna_czarny_czujnik) + ") przekracza temperaturę zadaną (" + str(TEMP_ZADANA) + ")."
                        else:
                            frame2.evaluate('''(el) => el.dispatchEvent(new Event("click"))''', switch_element_wyd1)
                            return "Włączam centralę. Dzień roboczy, godzina między 3 i 16, na zewnątrz jest poniżej 16 stopni (" + str(temp_pow_zewn) + "), a temperatura wewnątrz (" + str(temp_wewnetrzna_czarny_czujnik) + ") przekracza temperaturę zadaną (" + str(TEMP_ZADANA) + ")."
                    case _ if temp_pow_zewn > 16 and 12 <= current_hour < 16:
                        last_switch_on_date = datetime.strptime(last_switch_on_date, TIME_FORMAT)
                        if (current_time - last_switch_on_date) > timedelta(hours=2):
                            if wentylacja_state == "ON":
                                return "Zostawiam włączoną. Dzień roboczy, godzina między 12 i 16, temp. zewn >16 (" + str(temp_pow_zewn) + "), od ostatniego wietrzenia minęły co najmniej 2 godziny (" + str(
                                    last_switch_on_date) + ")."
                            else:
                                frame2.evaluate('''(el) => el.dispatchEvent(new Event("click"))''', switch_element_wyd1)
                                return "Włączam centralę. Dzień roboczy, godzina między 12 i 16 (" + str(
                                    current_hour) + "), temp. zewn >16 (" + str(temp_pow_zewn) + "), od ostatniego wietrzenia minęły co najmniej 2 godziny."
                        else:
                            if wentylacja_state == "ON":
                                frame2.evaluate('''(el) => el.dispatchEvent(new Event("click"))''', switch_element_wyl)
                                return "Wyłączam wentylację. Dzień roboczy, godzina między 12 i 16 (" + str(
                                    current_hour) + "), temp. zewn >16 (" + str(temp_pow_zewn) + "), od ostatniego wietrzenia nie minęły 2 godziny (" + str(
                                    last_switch_on_date) + ")."
                            else:
                                return "Zostawiam wyłączoną. Dzień roboczy, godzina między 12 i 16 (" + str(
                                    current_hour) + "), temp. zewn >16 (" + str(temp_pow_zewn) + "), od ostatniego wietrzenia nie minęły 2 godziny (" + str(
                                    last_switch_on_date) + ")."
            elif 16 <= current_hour or current_hour < 3:
                if wentylacja_state == "ON":  # Wentylacja włączona w niewłaściwych godzinach
                    frame2.evaluate('''(el) => el.dispatchEvent(new Event("click"))''', switch_element_wyl)
                    return "Wyłączam centralę. Jest godzina między 16 i 3 (a dokładnie " + str(
                        current_hour) + "), więc centrala powinna być wyłączona."
                return "Zostawiam wyłączoną. Jest godzina między 16 i 3 (a dokładnie " + str(
                    current_hour) + "), więc centrala powinna być wyłączona."
            else:
                if wentylacja_state == "ON":  # Wentylacja włączona w niewłaściwych godzinach
                    frame2.evaluate('''(el) => el.dispatchEvent(new Event("click"))''', switch_element_wyl)
                    return "Wyłączam centralę. Jest weekend, więc centrala powinna być wyłączona."
                return "Zostawiam wyłączoną. Jest weekend, więc centrala powinna być wyłączona."
    return "Brak dopasowania – nie zmieniam stanu."


with sync_playwright() as p:
    browser = p.chromium.launch(headless=True)  # headless=True jeśli chcesz bez GUI
    page = browser.new_page()

    page.goto("http://192.168.101.89")

    page.wait_for_selector('iframe#id_4_myframe')
    frame_element = page.query_selector('iframe#id_4_myframe')
    frame = frame_element.content_frame()

    wait(TIMEOUT_DURATION * 2)
    page.keyboard.press('Enter')
    wait()

    for _ in range(10):
        page.keyboard.press('Backspace')
        wait(0.05)

    page.keyboard.type('2222')
    wait()

    page.keyboard.press('Tab')
    wait()
    page.keyboard.press('Tab')
    wait()

    page.keyboard.press(' ')
    wait()
    page.click("text=admin")
    wait()
    page.keyboard.press('Tab')
    wait()
    page.keyboard.press('Enter')

    time.sleep(10)

    # Czekaj na pojawienie się drugiego iframe
    page.wait_for_selector('#foreignobjects', timeout=5000, state='attached')
    foreign_objects_div = page.query_selector('#foreignobjects')
    iframe_element2 = foreign_objects_div.query_selector('iframe#id_4_myframe')
    frame2 = iframe_element2.content_frame()

    # Pobieramy wszystkie wymagane dane w jednym kroku
    InputLabels = {
        "label1": "id_20_input_label",  #
        "label2": "id_27_input_label",  # temp. WEWN.schody
        "label3": "id_35_input_label",  # przykładowe inne ID
        "label4": "id_41_input_label",  # Temp. ZEWN.
        "label5": "id_143_input_label",  # temp. WEWN.kanał
        "label6": "id_197_input_label",  # TEMP_ZADANA
        "label7": "id_255_input_label",  # przykładowe inne ID
        "label8": "id_242_input_label",  # przykładowe inne ID
    }

    # Zbieramy wartości z elementów HTML
    for key, label_id in InputLabels.items():
        try:
            # Odczytujemy tekst z odpowiedniego ID
            text = frame2.evaluate(f'''
                () => {{
                    const el = document.querySelector("svg text#{label_id}");
                    return el ? el.textContent : null;
                }}
            ''')
            # Zapisujemy odczytaną wartość
            InputLabels[key] = text or "Brak tekstu"
        except Exception as e:
            InputLabels[key] = "Błąd"

    # Odczytujemy interesujące nas temperatury i wartości
    TEMP_ZADANA = float(InputLabels["label6"]) if InputLabels["label6"] != "Brak tekstu" else None
    temp_wewn_kanal = float(InputLabels["label5"]) if InputLabels["label5"] != "Brak tekstu" else None
    temp_wewn_schody = float(InputLabels["label2"]) if InputLabels["label2"] != "Brak tekstu" else None
    temp_pow_zewn = float(InputLabels["label4"]) if InputLabels["label4"] != "Brak tekstu" else None


    # temperaturę z czujnika mobilnego (czarny albo biały) pobieram osobno z PHP, bo tu był problem z synchronizacją

    # Kolor przełącznika WYD1
    fill_color = frame2.evaluate(''' 
        () => {
            const el = document.getElementById("id_19_bckg_area");
            return el ? el.getAttribute("fill") : null;
        }
    ''')

    if fill_color == "#55ff00":
        wentylacja_state = "ON"
    elif fill_color == "#999999":
        wentylacja_state = "OFF"
    else:
        wentylacja_state = "UNKNOWN"

    # Znajdź element przełącznika za pomocą id
    #switch_element_pozw_prac = frame2.query_selector('#id_226_switch') to jest id przełącznika POZW.PRACY - tego nie używamy
    switch_element_wyd1 = frame2.query_selector('#id_19_switch') #id przełącznika WYD1
    switch_element_auto=frame2.query_selector('#id_24_switch') #id przełącznika AUTO
    switch_element_wyl = frame2.query_selector('#id_22_switch')  # id przełącznika WYL

    # Wywołanie funkcji decyzyjnej z dynamicznymi wartościami
    decision = check_centrala_state(TEMP_ZADANA, temp_wewnetrzna_czarny_czujnik, temp_pow_zewn,
                                    last_switch_modification_date, last_switch_on_date,
                                    dni_wolne, wentylacja_state=wentylacja_state,
                                    frame2=frame2, switch_element_wyd1=switch_element_wyd1)

    # Przygotuj dane do zwrócenia w formacie JSON
    output_data = {
        "date_time": datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
        "id_20_input_label": InputLabels["label1"],
        "id_27_input_label": InputLabels["label2"],  # temp_wewn_schody
        "id_35_input_label": InputLabels["label3"],
        "id_41_input_label": InputLabels["label4"],  # temp_pow_zewn
        "id_143_input_label": InputLabels["label5"],  # temp_wewn_kanal
        "id_197_input_label": InputLabels["label6"],  # temp_zadana
        "id_255_input_label": InputLabels["label7"],
        "id_242_input_label": InputLabels["label8"],
        "wentylacja_state": wentylacja_state,
        "akcja": (
            "switch_on" if "Włączam" in decision else
            "switch_off" if "Wyłączam" in decision else
            "no_switch_modification"
        ),
        "decision": decision,
        "temperatura_czujnik_czarny": str(temp_wewnetrzna_czarny_czujnik)    #kowertujemy na string
    }


    # Wypisz dane w formacie JSON (dla PHP)
    print(json.dumps(output_data))

    #print(decision)

    browser.close()
