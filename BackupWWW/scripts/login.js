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

    // Oczekiwanie na załadowanie pierwszego elementu
    await frame2.waitForFunction(() => {
        const element = document.querySelector('svg text#id_255_input_label');
        return element && element.textContent !== 'XXX';
    });

    const targetTextElement = await frame2.$('svg text#id_255_input_label');
    const text = await targetTextElement.evaluate(el => el.textContent);
    console.log('Text z id_255_input_label:', text);

    // Oczekiwanie na drugi element
    await frame2.waitForFunction(() => {
        const element = document.querySelector('svg text#id_242_input_label');
        return element && element.textContent !== 'XXX';
    });

    const targetTextElement2 = await frame2.$('svg text#id_242_input_label');
    const text2 = await targetTextElement2.evaluate(el => el.textContent);
    console.log('id_242_input_label:', text2);

    // Kliknięcie przełącznika
    await frame.waitForTimeout(TIMEOUT_DURATION * 10);
    await frame2.waitForSelector('#id_226_switch');
    await frame2.evaluate(() => {
        document.getElementById("id_226_switch").dispatchEvent(new Event("click"));
    });

    console.log("Kliknięto przełącznik!");

    await frame2.waitForTimeout(TIMEOUT_DURATION * 2);

    // Zapisywanie kontekstów do pliku
    const contextsInfo = JSON.stringify(browser.contexts().map(ctx => ctx.pages().map(p => p.url())), null, 2);
    fs.writeFileSync('contexts_info.txt', contextsInfo);

    console.log("Sesja zostaje otwarta, przeglądarka nie zostanie zamknięta.");
})();
