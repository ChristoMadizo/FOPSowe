const { chromium } = require('playwright');

(async () => {
    console.log("Łączenie do istniejącej instancji Chrome...");

    try {
        // Połącz się do otwartej przeglądarki
        const browser = await chromium.connectOverCDP('http://localhost:9222');
        const context = browser.contexts()[0];

        if (!context) {
            console.error("Brak dostępnego kontekstu przeglądarki!");
            await browser.close();
            process.exit(1);
        }

        // Znajdź otwartą stronę, która nie jest pustą kartą
        let page = context.pages().find(p => !p.url().includes('chrome://'));

        if (!page) {
            console.error("Nie znaleziono otwartej strony aplikacji!");
            await browser.close();
            process.exit(1);
        }

        console.log("Połączono do otwartej przeglądarki!");
        console.log("Aktualna strona:", page.url());

        // Pobranie danych z iframe
        const foreignObjectsDiv = await page.$('#foreignobjects');

        if (!foreignObjectsDiv) {
            console.error("Nie znaleziono elementu #foreignobjects!");
            await browser.close();
            process.exit(1);
        }

        const iframeElement = await foreignObjectsDiv.$('iframe#id_4_myframe');

        if (!iframeElement) {
            console.error("Nie znaleziono iframe!");
            await browser.close();
            process.exit(1);
        }

        const frame = await iframeElement.contentFrame();

        if (!frame) {
            console.error("Nie udało się uzyskać dostępu do iframe!");
            await browser.close();
            process.exit(1);
        }

        // Oczekiwanie na załadowanie elementu
        await frame.waitForFunction(() => {
            const element = document.querySelector('svg text#id_242_input_label');
            return element && element.textContent !== 'XXX';
        });

        const targetTextElement = await frame.$('svg text#id_242_input_label');

        if (!targetTextElement) {
            console.error("Nie znaleziono elementu text#id_242_input_label!");
            await browser.close();
            process.exit(1);
        }

        const text = await targetTextElement.evaluate(el => el.textContent);
        console.log('id_242_input_label:', text);

        // Zamknięcie połączenia z przeglądarką
        await browser.close();
        process.exit(0);

    } catch (error) {
        console.error("Błąd krytyczny:", error);
        process.exit(1);
    }
})();
