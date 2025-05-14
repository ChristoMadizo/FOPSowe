from playwright.sync_api import sync_playwright
import time

with sync_playwright() as p:
    browser = p.chromium.launch(headless=True)
    page = browser.new_page()

    # 1. Wejdź na stronę
    page.goto("https://ha.fops.pl")

    # 2. Odczekaj 1 sekundę
    time.sleep(3)

    # Pobranie całej zawartości strony
    #html_content = page.content()
    # Wyświetlenie w konsoli
    #print("\nZawartość strony widziana przez Playwright:")
    #print(html_content)

    # 3. Poślij tekst "kmadzia"
    page.keyboard.type("kmadzia")

    # 4. Poślij Tab
    page.keyboard.press("Tab")

    # 5. Poślij tekst "1BigZiemni@k"
    page.keyboard.type("1BigZiemni@k")

    # 6. Poślij Enter
    page.keyboard.press("Enter")

    # 7. Odczekaj 3 sekundy po zalogowaniu
    time.sleep(3)

    # 8. Przejście przez kolejne poziomy Shadow DOM
    home_assistant_main = page.evaluate_handle("document.querySelector('home-assistant').shadowRoot.querySelector('home-assistant-main')")
    ha_drawer = home_assistant_main.evaluate_handle("el => el.shadowRoot.querySelector('ha-drawer')")
    partial_panel_resolver = ha_drawer.evaluate_handle("el => el.querySelector('partial-panel-resolver')")
    ha_panel_lovelace = partial_panel_resolver.evaluate_handle("el => el.querySelector('ha-panel-lovelace')")
    hui_root = ha_panel_lovelace.evaluate_handle("el => el.shadowRoot.querySelector('hui-root')")
    hui_view_container = hui_root.evaluate_handle("el => el.shadowRoot.querySelector('hui-view-container')")
    hui_view = hui_view_container.evaluate_handle("el => el.querySelector('hui-view')")
    hui_masonry_view = hui_view.evaluate_handle("el => el.querySelector('hui-masonry-view')")
    columns_div = hui_masonry_view.evaluate_handle("el => el.shadowRoot.querySelector('div#columns')")
    column_div = columns_div.evaluate_handle("el => el.querySelector('div.column')")
    hui_card = column_div.evaluate_handle("el => el.querySelector('hui-card')")
    hui_entities_card = hui_card.evaluate_handle("el => el.querySelector('hui-entities-card')")
    ha_card = hui_entities_card.evaluate_handle("el => el.shadowRoot.querySelector('ha-card')")
    states_div = ha_card.evaluate_handle("el => el.querySelector('#states')")
    sensor_entity_row = states_div.evaluate_handle("el => el.querySelector('hui-sensor-entity-row')")
    generic_entity_row = sensor_entity_row.evaluate_handle("el => el.shadowRoot.querySelector('hui-generic-entity-row')")

    # 9. Pobranie temperatury i jej oczyszczenie
    temperatura_raw = generic_entity_row.evaluate("el => el.innerText")
    temperatura_czujnik_czarny = float(temperatura_raw.replace("°C", "").replace(",", ".").strip())

    browser.close()

    # Wyświetlenie wartości w konsoli
    print(temperatura_czujnik_czarny)