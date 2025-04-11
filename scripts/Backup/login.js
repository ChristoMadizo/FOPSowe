const { chromium } = require('playwright');
const { exec } = require('child_process');
const fs = require('fs');

const TIMEOUT_DURATION = 1000; // Możesz dostosować czas oczekiwania
const DEBUG_PORT = 9222; // Port debugowania CDP

(async () => {
    console.log("Uruchamiam Chrome w trybie debugowania...");

    // Sprawdź, czy Chrome już działa w trybie debugowania
    exec(`tasklist /FI "IMAGENAME eq chrome.exe"`, (err, stdout) => {
        if (!stdout.includes("chrome.exe")) {
            console.log("Nie wykryto działającej instancji Chrome. Uruchamiam nową...");
            exec(`start chrome --remote-debugging-port=${DEBUG_PORT} --user-data-dir="C:\\ChromeDebug"`, (error) => {
                if (error) {
                    console.error("Błąd przy uruchamianiu Chrome:", error);
                    return;
                }
                console.log("Chrome uruchomiony w trybie debugowania.");
            });
        } else {
            console.log("Chrome już działa.");
        }
    });

    // Poczekaj chwilę, żeby Chrome się uruchomił
    await new Promise(resolve => setTimeout(resolve, 3000));

    const browser = await chromium.connectOverCDP(`http://localhost:${DEBUG_PORT}`);
    const context = browser.contexts()[0] || await browser.newContext();
    const page = await context.newPage();

    await page.goto('http://192.168.101.89');
    console.log("Otworzono stronę logowania.");

    // Jeśli strona ma iframe, poczekaj na załadowanie
    await page.waitForSelector('iframe#id_4_myframe');
    const frameElement = await page.$('iframe#id_4_myframe');
    const frame = await frameElement.contentFrame();
    console.log("Załadowano iframe!");

    // Interakcja z formularzem logowania
    await frame.waitForTimeout(TIMEOUT_DURATION * 2);
    await page.keyboard.press('Enter');
    await frame.waitForTimeout(TIMEOUT_DURATION);

    for (let i = 0; i < 10; i++) {
        await page.keyboard.press('Backspace');
    }

    await frame.waitForTimeout(TIMEOUT_DURATION);
    await page.keyboard.type('2222');
    await frame.waitForTimeout(TIMEOUT_DURATION);

    await page.keyboard.press('Tab');
    await frame.waitForTimeout(TIMEOUT_DURATION);
    await page.keyboard.press('Tab');
    await frame.waitForTimeout(TIMEOUT_DURATION);

    await page.keyboard.press('Space');
    await frame.waitForTimeout(TIMEOUT_DURATION);
    await page.click('text=admin');
    await frame.waitForTimeout(TIMEOUT_DURATION);
    await page.keyboard.press('Tab');
    await frame.waitForTimeout(TIMEOUT_DURATION);
    await page.keyboard.press('Enter');

    console.log("Logowanie zakończone!");

    // Pobranie danych z kolejnego iframe
    await frame.waitForTimeout(TIMEOUT_DURATION);
    const foreignObjectsDiv = await page.$('#foreignobjects');
    const iframeElement2 = await foreignObjectsDiv.$('iframe#id_4_myframe');
    const frame2 = await iframeElement2.contentFrame();


    await frame2.waitForTimeout(5000); // 5 seconds pause

 // Iteracja przez wszystkie identyfikatory w InputLabels
// Lista id labels do sczytania:
const InputLabels = {
    label1: 'id_20_input_label',
    label2: 'id_27_input_label',
    label3: 'id_35_input_label',
    label4: 'id_41_input_label',
    label5: 'id_143_input_label',
    label6: 'id_197_input_label',
    label7: 'id_255_input_label',
    label8: 'id_242_input_label'
};



await frame2.waitForTimeout(5000); // 5-second pause

// Iteracja przez wszystkie identyfikatory w InputLabels
for (const key in InputLabels) {
    if (InputLabels.hasOwnProperty(key)) {
        const label = InputLabels[key]; // Pobranie wartości dla klucza

      //  console.log(`Właśnie wszedłem do pętli dla klucza: ${key}, wartości: ${label}`);
        try {
            // Pobranie elementu za pomocą evaluate
            const text = await frame2.evaluate((label) => {
                const element = document.querySelector(`svg text#${label}`);
                return element ? element.textContent : null; // Zwracamy textContent lub null
            }, label); // Przekazujemy label jako parametr do evaluate

            // Logowanie wyniku
            if (text) {
                console.log(`${label}:`, text); // Logowanie w formacie 'id_255_input_label: wartość tekstowa'
            } else {
                console.log(`Element ${label} nie istnieje lub ma tekst 'XXX'.`);
            }
        } catch (error) {
            console.error(`Błąd podczas przetwarzania ${label}:`, error);
        }
    }
}

    //vvvvvvvvvvvvvvvvvvvvvvvvvvvvvvprzesłanie zmiennej InputLabels do PHPvvvvvvvvvvvvvvvvvvvvvvvvvvvvvv
    /* WYRZUCIŁEM Z KODU, BO TRZEBA BY TO PRZEKAZAĆ DO INDEX.PHP, A NIE DO WENTYLACJI
    // Wysyłanie danych do PHP za pomocą fetch API
   
    // Wysyłanie danych do PHP jako JSON
    fetch('http://localhost/km_www/index.php?page=wentylacja', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(InputLabels) // Przekazujemy dane w formie JSON
    })
    .then(response => response.json())
    .then(data => {
        console.log('Odpowiedź z PHP:', data);
    })
    .catch(error => {
        console.error('Błąd:', error);
    });

    //^^^^^^^^^^^^^^^^^^przesłanie zmiennej InputLabels do PHP^^^^^^^^^^^^^^^^^^^^^^  */

/*
    // Oczekiwanie na załadowanie pierwszego elementu
    await frame2.waitForFunction(() => {
        const element = document.querySelector('svg text#id_255_input_label');
        return element && element.textContent !== 'XXX';
    });
    const targetTextElement = await frame2.$('svg text#id_255_input_label');
    const text = await targetTextElement.evaluate(el => el.textContent);
    console.log('id_255_input_label:', text); */

/*
    // Oczekiwanie na drugi element
    await frame2.waitForFunction(() => {
        const element = document.querySelector('svg text#id_242_input_label');
        return element && element.textContent !== 'XXX';
    });

    const targetTextElement2 = await frame2.$('svg text#id_242_input_label');
    const text2 = await targetTextElement2.evaluate(el => el.textContent);
    console.log('id_242_input_label:', text2);  */

    // KLIKNIĘCIE przełącznika
    await frame.waitForTimeout(TIMEOUT_DURATION * 10);
    await frame2.waitForSelector('#id_226_switch');
    await frame2.evaluate(() => {
        document.getElementById("id_226_switch").dispatchEvent(new Event("click"));
    });

    console.log("Kliknięto przełącznik!");

    
    //pobieranie stanu PRZEŁĄCZNIKA WENTYLACJI
        const fillColor = await frame2.evaluate(() => {
            const switchElement = document.getElementById("id_226_bckg_area");
            return switchElement ? switchElement.getAttribute('fill') : null;
        });

        console.log('Wartość atrybutu fill:', fillColor); // Wyświetlenie wartości atrybutu "fill" w konsoli    
        // Sprawdzenie stanu na podstawie koloru
        if (fillColor === '#55ff00') { // Zielony kolor oznacza stan "ON"
            wentylacja_state="ON";
            console.log(`id_226_switch: WŁĄCZONE`);
        } else if (fillColor === '#999999') { // Czerwony kolor oznacza stan "OFF"
            wentylacja_state="OFF";
            console.log(`id_226_switch: WYŁĄCZONE`);
        } else {
                console.log('Nieznany stan switcha, fill:', fillColor);
            }
    



    console.log('FINISH');
    await frame2.waitForTimeout(TIMEOUT_DURATION * 2);

    // Zapisywanie kontekstów do pliku
    const contextsInfo = JSON.stringify(browser.contexts().map(ctx => ctx.pages().map(p => p.url())), null, 2);
    fs.writeFileSync('contexts_info.txt', contextsInfo);







    console.log("Sesja zostaje otwarta, przeglądarka nie zostanie zamknięta.");


    await browser.close();
    console.log("Przeglądarka została zamknięta.");
})();
