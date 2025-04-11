const przelaczWentylacje = require('./functions.js');
//console.log(typeof przelaczWentylacje);


const { chromium } = require('playwright');
const { exec } = require('child_process');
const fs = require('fs');

const TIMEOUT_DURATION = 1000; // Możesz dostosować czas oczekiwania
const DEBUG_PORT = 9222; // Port debugowania CDP

(async () => {
    console.log("Sprawdzam, czy Chrome działa w trybie debugowania...");

    let browser;
    let context;
    let page;

    try {
        // Sprawdzenie, czy Chrome działa w trybie debugowania
        const chromeRunning = await new Promise((resolve) => {
            exec(`tasklist /FI "IMAGENAME eq chrome.exe"`, (err, stdout) => {
                resolve(stdout.includes("chrome.exe")); // Zwraca true, jeśli Chrome działa
            });
        });

        if (chromeRunning) {
            console.log("Chrome już działa. Łączenie do istniejącej instancji...");
            browser = await chromium.connectOverCDP(`http://localhost:${DEBUG_PORT}`);
            context = browser.contexts()[0];

            if (!context) {
                console.error("Brak dostępnego kontekstu przeglądarki!");
                await browser.close();
                process.exit(1);
            }

            // Znajdź otwartą stronę, która nie jest pustą kartą
            page = context.pages().find(p => !p.url().includes('chrome://'));

            if (!page) {
                console.error("Nie znaleziono otwartej strony aplikacji!");
                await browser.close();
                process.exit(1);
            }

            console.log("Połączono z istniejącą stroną:", page.url());

        } else {
            console.log("Nie wykryto uruchomionej instancji Chrome. Uruchamiam nową...");
            exec(`start chrome --remote-debugging-port=${DEBUG_PORT} --user-data-dir="C:\\ChromeDebug"`, (error) => {
                if (error) {
                    console.error("Błąd przy uruchamianiu Chrome:", error);
                    process.exit(1);
                }
                console.log("Chrome uruchomiony w trybie debugowania.");
            });

            // Poczekaj na uruchomienie Chrome
            await new Promise(resolve => setTimeout(resolve, 2000));
            browser = await chromium.connectOverCDP(`http://localhost:${DEBUG_PORT}`);
            context = browser.contexts()[0] || await browser.newContext();
            page = await context.newPage();

            console.log("Rozpoczęto interakcję ze stroną...");

            // Otwórz stronę docelową i wykonaj logowanie
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
        }

        // Operacje wykonywane w obu scenariuszach (po logowaniu lub połączeniu ze stroną)
        await page.waitForSelector('#foreignobjects', { state: 'attached', timeout: 1000 }); // Poczekaj na załadowanie `#foreignobjects`
        const foreignObjectsDiv = await page.$('#foreignobjects');
        if (!foreignObjectsDiv) {
            throw new Error("Nie znaleziono elementu #foreignobjects.");
        }

        // Pobranie kolejnego iframe w foreignObjects
        const iframeElement2 = await foreignObjectsDiv.$('iframe#id_4_myframe');
        if (!iframeElement2) {
            throw new Error("Nie znaleziono iframe #id_4_myframe w #foreignobjects.");
        }
        const frame2 = await iframeElement2.contentFrame();
        if (!frame2) {
            throw new Error("Nie udało się uzyskać contentFrame dla iframe #id_4_myframe.");
        }
        console.log("Załadowano drugi iframe!");

        // Iteracja przez wszystkie identyfikatory w InputLabels
        InputLabels = {
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
            if (InputLabels.hasOwnProperty(key)) {
                const label = InputLabels[key];
                try {
                    const text = await frame2.evaluate((label) => {
                        const element = document.querySelector(`svg text#${label}`);
                        return element ? element.textContent : null;
                    }, label);
        
                    if (text) {
                        console.log(`${label}:`, text);
                        // Aktualizacja wartości w obiekcie InputLabels
                        InputLabels[key] = text;
                    } else {
                        console.log(`Element ${label} nie istnieje lub ma tekst 'XXX'.`);
                        // Opcjonalnie możesz przypisać domyślną wartość
                        InputLabels[key] = "Brak tekstu";
                    }
                } catch (error) {
                    console.error(`Błąd podczas przetwarzania ${label}:`, error);
                    // W przypadku błędu możesz ustawić wartość jako null lub inną
                    InputLabels[key] = "Błąd";
                }
            }
        }
        

            //sprawdza stan przełącznika PRZED DECYZJĄ CZY PRZEŁĄCZAĆ WENTYLACJĘ
            const fillColor = await frame2.evaluate(() => {
            const switchElement = document.getElementById("id_226_bckg_area");
            return switchElement ? switchElement.getAttribute('fill') : null;
        });

        if (fillColor === '#55ff00') {
            console.log(`id_226_switch: WŁĄCZONE`);
            wentylacja_state="ON";
        } else if (fillColor === '#999999') {
            console.log(`id_226_switch: WYŁĄCZONE`);
            wentylacja_state="OFF";
        } else {
            console.log('Nieznany stan switcha, fill:', fillColor);
        }

        //DECYZJA czy zmienić stan wentylacji------------------------------------------------------
        switch (wentylacja_state) {
            case "ON":
                if (InputLabels.label1 > 20) {            //ZMIENIĆ TEMP!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
                    console.log("cieplo");
                    //#region KLIKNIĘCIE przełącznika
                    await frame2.waitForTimeout(TIMEOUT_DURATION * 10);
                    await frame2.waitForSelector('#id_226_switch');
                    await frame2.evaluate(() => {
                    document.getElementById("id_226_switch").dispatchEvent(new Event("click"));
                    wentylacja_state="OFF";   //zmiana stanu wentylacji na OFF
                    });
                } else {
                    console.log("zimno");
                }
                break;
            case "OFF":
                console.log("jest OFF");
                break;
            default:
                console.log("Nieznany stan wentylacji.");
        }

        console.log("To mój test" + InputLabels.label5);

             



        //console.log("zmieniono stan!")

            /*
        //sprawdza stan przełącznika PO DECYZJI CZY PRZEŁĄCZAĆ
            fillColor = await frame2.evaluate(() => {
            const switchElement = document.getElementById("id_226_bckg_area");
            return switchElement ? switchElement.getAttribute('fill') : null;
        });*/

        console.log('Wartość atrybutu fill:', fillColor);

        if (fillColor === '#55ff00') {
            console.log(`id_226_switch: WŁĄCZONE`);
            wentylacja_state="ON";
        } else if (fillColor === '#999999') {
            console.log(`id_226_switch: WYŁĄCZONE`);
            wentylacja_state="OFF";
        } else {
            console.log('Nieznany stan switcha, fill:', fillColor);
        }

        console.log('FINISH');

    } catch (error) {
        console.error("Wystąpił błąd:", error);
        if (browser) {
            await browser.close();
        }
        process.exit(1);
    }

// Zapisywanie kontekstów do pliku
    const contextsInfo = JSON.stringify(browser.contexts().map(ctx => ctx.pages().map(p => p.url())), null, 2);
    fs.writeFileSync('contexts_info.txt', contextsInfo);
    console.log("Sesja zostaje otwarta, przeglądarka nie zostanie zamknięta.");
    await browser.close();
    console.log("Przeglądarka została zamknięta.");


        /*
        // KLIKNIĘCIE przełącznika
        await frame.waitForTimeout(TIMEOUT_DURATION * 10);
        await frame2.waitForSelector('#id_226_switch');
        await frame2.evaluate(() => {
            document.getElementById("id_226_switch").dispatchEvent(new Event("click"));
        });*/

})();
