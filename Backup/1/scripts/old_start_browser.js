const { chromium } = require('playwright');
const fs = require('fs');

const TIMEOUT_DURATION = 500; // Timeout duration in milliseconds

(async () => {
  const browser = await chromium.launch({ headless: false });
  const context = await browser.newContext();
  const page = await context.newPage();
  await page.goto('http://192.168.101.89');

  await page.waitForSelector('#id_4_myframe');
  const frameElement = await page.$('#id_4_myframe');
  const frame = await frameElement.contentFrame();

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

  await frame.waitForTimeout(TIMEOUT_DURATION);
  const foreignObjectsDiv = await page.$('#foreignobjects');
  const iframeElement2 = await foreignObjectsDiv.$('iframe#id_4_myframe');
  const frame2 = await iframeElement2.contentFrame();

  await frame2.waitForFunction(() => {
    const element = document.querySelector('svg text#id_255_input_label');
    return element && element.textContent !== 'XXX';
  });

  const targetTextElement = await frame2.$('svg text#id_255_input_label');
  const text = await targetTextElement.evaluate(el => el.textContent);
  console.log(text);

  await frame2.waitForFunction(() => {
    const element = document.querySelector('svg text#id_242_input_label');
    return element && element.textContent !== 'XXX';
  });

  const targetTextElement2 = await frame2.$('svg text#id_242_input_label');
  const text2 = await targetTextElement2.evaluate(el => el.textContent);
  console.log('id_242_input_label:', text2);

  await frame.waitForTimeout(TIMEOUT_DURATION * 10);

  await frame2.waitForSelector('#id_226_switch');
  await frame2.evaluate(() => {
    document.getElementById("id_226_switch").dispatchEvent(new Event("click"));
  });

  console.log("Kliknięto przełącznik!");

  await frame2.waitForTimeout(TIMEOUT_DURATION * 2);

  // **Zapisz sesję do pliku**
  const storageState = await context.storageState();
  fs.writeFileSync('browser_state.json', JSON.stringify(storageState));

  console.log("Sesja zapisana. Przeglądarka zostaje otwarta!");

  // **Nie zamykamy przeglądarki**
  process.stdin.resume();
})();
