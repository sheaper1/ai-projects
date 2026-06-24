// E2E: 3-шаговая форма Tipp einsenden → submit → редирект /danke/.
import puppeteer from 'puppeteer';

const URL = process.argv[2] || 'https://rosenberger.digirelation.dev/tippgeber/';
const browser = await puppeteer.launch({ headless: 'new', args: ['--no-sandbox'] });
const page = await browser.newPage();
const logs = [];
page.on('pageerror', (e) => logs.push('[pageerror] ' + e.message));
await page.goto(URL, { waitUntil: 'networkidle2', timeout: 60000 });
await new Promise((r) => setTimeout(r, 600));

async function clickByText(re) {
  return page.evaluate((rs) => {
    const rx = new RegExp(rs);
    const visible = [...document.querySelectorAll('.tip-form__panel:not([hidden]) [data-next], .tip-form__panel:not([hidden]) [type="submit"], .tip-form__panel:not([hidden]) [data-back]')];
    const b = visible.find((x) => rx.test(x.textContent));
    if (b) { b.click(); return true; }
    return false;
  }, re.source);
}

// step1: adresse
await page.type('#tf-adresse', 'Goethestraße 12, 6800 Feldkirch');
await page.select('#tf-objektart', 'Haus').catch(() => {});
await clickByText(/Weiter/);
await new Promise((r) => setTimeout(r, 400));
// step2: optional
await page.select('#tf-bezug', 'Nachbar / Bekannter').catch(() => {});
await page.type('#tf-situation', 'Nachbar zieht weg, denkt über Verkauf nach.').catch(() => {});
await clickByText(/Weiter/);
await new Promise((r) => setTimeout(r, 400));
// step3: contact
await page.select('#tf-anrede', 'Herr').catch(() => {});
await page.type('#tf-vorname', 'Test');
await page.type('#tf-nachname', 'Tippgeber');
await page.type('#tf-email', 'qa-tipform@example.com');
await page.type('#tf-telefon', '+43 660 1234567');
const visibleStep = await page.evaluate(() => document.querySelector('.tip-form__panel:not([hidden])').getAttribute('data-step'));
await clickByText(/absenden/);

let outcome = 'timeout';
for (let i = 0; i < 20; i++) {
  await new Promise((r) => setTimeout(r, 600));
  if (/\/danke\/?$/.test(page.url())) { outcome = 'redirect /danke/'; break; }
  const err = await page.evaluate(() => { const e = document.querySelector('[data-tf-error]'); return e && !e.hidden; }).catch(() => false);
  if (err) { outcome = 'submit-error'; break; }
}
console.log('visible step before submit:', visibleStep, '| outcome:', outcome, '| url:', page.url());
logs.forEach((l) => console.log(l));
await browser.close();
process.exit(/redirect/.test(outcome) ? 0 : 2);
