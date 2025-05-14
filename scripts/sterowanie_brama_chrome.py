from playwright.sync_api import sync_playwright

with sync_playwright() as p:
    # Uruchom WebKit (bazujący na Edge) w trybie headless
    browser = p.chromium.launch(headless=True)
  # Możesz ustawić headless=False, aby widzieć, co się dzieje
    context = browser.new_context()

    # Ustawienie lokalizacji
    context.set_geolocation({"latitude": 49.75341344778113, "longitude": 18.62088816781903})  # Wstaw swoje współrzędne
    context.grant_permissions(["geolocation"])


    # Utwórz stronę
    page = context.new_page()

    # Otwórz stronę logowania
    page.goto("https://brama.fops.pl/m/#supla/gate")

    # Opóźnienie 3 sekundy przed rozpoczęciem logowania
    page.wait_for_timeout(2000)

    # Wciśnij TAB, aby przejść do pola loginu (ustawiamy kursor)
    page.press("body", "Tab")

    # Wprowadź login
    page.keyboard.type("robot")

    # Wciśnij TAB, aby przejść do pola hasła
    page.press("body", "Tab")

    # Wprowadź hasło
    page.keyboard.type("Szw@rnoDzieucha2")

    # Wciśnij Enter, aby wysłać formularz
    page.keyboard.press("Enter")

    # Opcjonalnie poczekaj 3 sekundy na reakcję po zalogowaniu
    page.wait_for_timeout(3000)


    # Opcjonalnie poczekaj 3 sekundy na reakcję po kliknięciu
    page.wait_for_timeout(3000)

    # Sprawdzenie, czy przycisk jest widoczny
    button_visible = page.is_visible('button#gate_open')
    print(f'Przycisk jest widoczny: {button_visible}')

    # Jeśli przycisk jest widoczny, kliknij go
    #if button_visible:
    page.click('button#gate_open', force=True)

    # Opcjonalnie poczekaj 3 sekundy na reakcję po kliknięciu
    page.wait_for_timeout(3000)

    # Nie zamykaj przeglądarki - po zakończeniu kodu przeglądarka pozostanie otwarta
    # browser.close()  # Usuń lub zakomentuj, jeśli chcesz, aby przeglądarka została otwarta
