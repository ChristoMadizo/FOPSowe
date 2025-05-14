const przelaczWentylacje = require('./functions.js');
const { chromium } = require('playwright');
const { exec } = require('child_process');
const fs = require('fs');

const TIMEOUT_DURATION = 1000;
const DEBUG_PORT = 9222;

(async () => {
    console.log("Sprawdzam, czy Chrome działa w trybie debugowania...");

    let browser;
    let context;
    let page;
    let wentylacja_state = null;

    try {
        const chromeRunning = await new Promise((resolve) => {
            exec(`tasklist /FI "IMAGENAME eq chrome.exe"`, (err, stdout) => {
                resolve(stdout.includes("chrome.exe"));
            });
        });

        if (chromeRunning) {
            console.log("Chrome już działa. Łączenie do istniejącej instancji...");
            browser = await chromium.connectOverCDP(`http://localhost:${DEBUG_PORT}`);
            context = browser.contexts()[0];

            if (!context) {
                throw new Error("Brak dostępnego kontekstu przeglądarki!");
            }

            page = context.pages().find(p => !p.url().includes('chrome://'));
            if (!page) {
                throw new Error("Nie znaleziono otwartej strony aplikacji!");
            }

            console.log("Połączono z istniejącą stroną:", page.url());

        } else {
            console.log("Nie wykryto uruchomionej instancji Chrome. Uruchamiam nową...");
            exec(`start chrome --remote-debugging-port=${DEBUG_PORT} --user-data-dir="C:\\ChromeDebug"`);

            await new Promise(resolve => setTimeout(resolve, 2000));
            browser = await chromium.connectOverCDP(`http://localhost:${DEBUG_PORT}`);
            context = browser.contexts()[0] || await browser.newContext();
            page = await context.newPage();

            await page.goto('http://192.168.101.89');
            console.log("Otworzono stronę logowania.");

            await page.waitForSelector('iframe#id_4_myframe');
            const frameElement = await page.$('iframe#id_4_myframe');
            const frame = await frameElement.contentFrame();

            console.log("Załadowano iframe!");
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
        }

        await page.waitForSelector('#foreignobjects', { state: 'attached', timeout: 1000 });
        const foreignObjectsDiv = await page.$('#foreignobjects');
        const iframeElement2 = await foreignObjectsDiv.$('iframe#id_4_myframe');
        const frame2 = await iframeElement2.contentFrame();
        console.log("Załadowano drugi iframe!");

        let InputLabels = {
            label1: 'id_20_input_label',
            label2: 'id_27_input_label',
            label3: 'id_35_input_label',
            label4: 'id_41_input_label',
            label5: 'id_143_input_label',
            label6: 'id_197_input_label',
            label7: 'id_255_input_label',
            label8: 'id_242_input_label'
        };

        for (const key in InputLabels) {
            const label = InputLabels[key];
            try {
                const text = await frame2.evaluate((label) => {
                    const element = document.querySelector(`svg text#${label}`);
                    return element ? element.textContent : null;
                }, label);

                InputLabels[key] = text || "Brak tekstu";
                console.log(`${label}:`, InputLabels[key]);
            } catch (error) {
                console.error(`Błąd podczas przetwarzania ${label}:`, error);
                InputLabels[key] = "Błąd";
            }
        }

        const fillColor = await frame2.evaluate(() => {
            const switchElement = document.getElementById("id_226_bckg_area");
            return switchElement ? switchElement.getAttribute('fill') : null;
        });

        if (fillColor === '#55ff00') {
            console.log(`id_226_switch: WŁĄCZONE`);
            wentylacja_state = "ON";
        } else if (fillColor === '#999999') {
            console.log(`id_226_switch: WYŁĄCZONE`);
            wentylacja_state = "OFF";
        } else {
            console.log('Nieznany stan switcha:', fillColor);
            wentylacja_state = "UNKNOWN";
        }

        // KONWERSJA: label1 może być np. "25.4°C" — usuń jednostki
        const tempStr = InputLabels.label1.replace(/[^\d.,-]/g, '').replace(',', '.');
        const tempValue = parseFloat(tempStr);

        // DECYZJA O PRZEŁĄCZENIU
        if (wentylacja_state === "ON" && tempValue > 22.5) {
            console.log("Temperatura powyżej progu. Wyłączam wentylację.");
            await frame2.waitForTimeout(TIMEOUT_DURATION * 2);
            await frame2.waitForSelector('#id_226_switch');
            await frame2.evaluate(() => {
                document.getElementById("id_226_switch").dispatchEvent(new Event("click"));
            });
            wentylacja_state = "OFF";
        } else {
            console.log("Wentylacja pozostaje bez zmian.");
        }

        // ZAPIS KONTEXTU
        const contextsInfo = JSON.stringify(browser.contexts().map(ctx => ctx.pages().map(p => p.url())), null, 2);
        fs.writeFileSync('contexts_info.txt', contextsInfo);
        console.log("Zapisano sesję kontekstów.");

        await browser.close();
        console.log("Przeglądarka została zamknięta.");
        console.log("FINISH");

    } catch (error) {
        console.error("Wystąpił błąd:", error);
        if (browser) await browser.close();
        process.exit(1);
    }

})();
