const { chromium } = require('playwright');
const { exec } = require('child_process');
const fs = require('fs');
const TIMEOUT_DURATION = 1000; // Czas oczekiwania
const DEBUG_PORT = 9222; // Port debugowania CDP

(async () => {
    console.log("Sprawdzam, czy Chrome działa w trybie debugowania...");

    // Sprawdź, czy Chrome jest uruchomiony na porcie DEBUG_PORT
    exec(`curl http://localhost:${DEBUG_PORT}/json`, async (err, stdout) => {
        if (err || !stdout) {
            console.log("Nie wykryto działającej instancji Chrome w trybie debugowania. Uruchamiam nową...");
            exec(`google-chrome --remote-debugging-port=${DEBUG_PORT} --user-data-dir="/tmp/ChromeDebug" --headless --disable-gpu &`, (error) => {
                if (error) {
                    console.error("Błąd przy uruchamianiu Chrome:", error);
                    return;
                }
                console.log("Chrome uruchomiony w trybie debugowania.");
            });

            // Poczekaj na uruchomienie Chrome
            await new Promise(resolve => setTimeout(resolve, 5000));
        } else {
            console.log("Chrome już działa w trybie debugowania.");
        }

        try {
            const browser = await chromium.connectOverCDP(`http://localhost:${DEBUG_PORT}`);
            const context = browser.contexts()[0] || await browser.newContext();
            const page = await context.newPage();

            // Przejdź do wybranej strony
            await page.goto('http://192.168.101.89');
            console.log("Otworzono stronę logowania.");

            // Załaduj iframe i pracuj z formularzem
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
            await page.keyboard.press('Space');

            console.log("Dane zostały wpisane!");

            // Obsługa danych w iframe
            const InputLabels = {
                label1: 'id_20_input_label',
                label2: 'id_27_input_label',
            };

            for (const key in InputLabels) {
                if (InputLabels.hasOwnProperty(key)) {
                    const label = InputLabels[key];
                    try {
                        const text = await frame.evaluate((label) => {
                            const element = document.querySelector(`svg text#${label}`);
                            return element ? element.textContent : null;
                        }, label);
                        console.log(`${label}:`, text || "Nie znaleziono danych.");
                    } catch (error) {
                        console.error(`Błąd dla ${label}:`, error);
                    }
                }
            }

            // Przeglądarka pozostaje otwarta
            console.log("Sesja została otwarta. Przeglądarka nie zostanie zamknięta.");
        } catch (error) {
            console.error("Błąd podczas połączenia z Chrome:", error);
        }
    });
})();
