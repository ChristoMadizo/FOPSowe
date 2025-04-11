async function przelaczWentylacje(frame2) {
    console.log("Próbuję włączyć wentylację...");

    // Poczekaj na przełącznik
    await frame2.waitForSelector('#id_226_switch');
    
    // Kliknięcie na przełącznik
    await frame2.evaluate(() => {
        const switchElement = document.getElementById("id_226_switch");
        if (switchElement) {
            switchElement.dispatchEvent(new Event("click"));
            console.log("Wentylacja została włączona!");
        } else {
            console.error("Przełącznik wentylacji nie został znaleziony.");
        }
    });
}

module.exports = przelaczWentylacje;



/*

async function przelaczWentylacje(frame2) {
    console.log("Próbuję włączyć wentylację...");

    // Poczekaj na przełącznik
    await frame2.waitForSelector('#id_226_switch');
    
    // Kliknięcie na przełącznik
    await frame2.evaluate(() => {
        const switchElement = document.getElementById("id_226_switch");
        if (switchElement) {
            switchElement.dispatchEvent(new Event("click"));
            console.log("Wentylacja została włączona!");
        } else {
            console.error("Przełącznik wentylacji nie został znaleziony.");
        }
    });
}

module.exports = przelaczWentylacje;

*/