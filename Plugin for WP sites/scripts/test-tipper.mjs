// E2E-проверка Tippgeber-фаннела: прокликать до контактного шага, отправить,
// убедиться что WPForms принял (редирект на /danke/ или success-событие).
import puppeteer from 'puppeteer';

const URL = process.argv[2] || 'https://rosenberger.digirelation.dev/tippgeber/';
const browser = await puppeteer.launch({ headless: 'new', args: ['--no-sandbox'] });
const page = await browser.newPage();
const logs = [];
page.on('console', (m) => logs.push('[console] ' + m.text()));
page.on('pageerror', (e) => logs.push('[pageerror] ' + e.message));
await page.goto(URL, { waitUntil: 'networkidle2', timeout: 60000 });
await new Promise((r) => setTimeout(r, 800));

const state = () => page.evaluate(() => {
  const card = document.getElementById('irvCard');
  return {
    hasContact: !!document.getElementById('irvContact'),
    hasPlz: !!document.getElementById('irvPlz'),
    hasRange: !!document.getElementById('irvRg'),
    opts: document.querySelectorAll('#irvCard .opt').length,
    hasWeiter: !!(card && [...card.querySelectorAll('.btn')].find((b) => /Weiter|absenden|erhalten/i.test(b.textContent))),
    q: (card && card.querySelector('.q,.confirm h2,h1')) ? card.querySelector('.q,.confirm h2,h1').textContent.trim() : '',
    confirm: !!document.querySelector('#irvCard .confirm'),
  };
});

let step = 0;
while (step < 30) {
  const s = await state();
  if (s.hasContact || s.confirm) break;
  if (s.hasPlz) {
    await page.type('#irvPlz', '6900');
    await page.evaluate(() => [...document.querySelectorAll('#irvCard .btn')].find((b) => /Weiter/i.test(b.textContent))?.click());
  } else if (s.hasRange) {
    await page.evaluate(() => [...document.querySelectorAll('#irvCard .btn')].find((b) => /Weiter/i.test(b.textContent))?.click());
  } else if (s.opts > 0) {
    await page.evaluate(() => document.querySelector('#irvCard .opt')?.click());
    // multi-select: clicking toggles; if a Weiter exists, click it to advance
    await new Promise((r) => setTimeout(r, 150));
    const still = await page.evaluate(() => document.querySelectorAll('#irvCard .opt.sel').length > 0 && !!document.getElementById('irvContact') === false);
    if (still) await page.evaluate(() => [...document.querySelectorAll('#irvCard .btn')].find((b) => /Weiter/i.test(b.textContent))?.click());
  } else {
    await page.evaluate(() => [...document.querySelectorAll('#irvCard .btn')].find((b) => b.textContent)?.click());
  }
  await new Promise((r) => setTimeout(r, 250));
  step++;
}

const reached = await state();
console.log('reached step', step, JSON.stringify(reached));
if (!reached.hasContact) { console.log('❌ не дошёл до контактного шага'); logs.slice(-10).forEach((l) => console.log(l)); await browser.close(); process.exit(1); }

// Заполнить контактную форму
await page.select('#irvAnrede', 'Herr').catch(() => {});
await page.type('#irvVorname', 'Test');
await page.type('#irvNachname', 'Tippgeber');
await page.type('#irvEmail', 'qa-tipp@example.com');
await page.type('#irvTelefon', '+43 660 1234567');

const before = page.url();
await page.evaluate(() => document.getElementById('irvSubmit')?.click());

// ждём редиректа на /danke/ ИЛИ success-confirm
let outcome = 'timeout';
for (let i = 0; i < 20; i++) {
  await new Promise((r) => setTimeout(r, 600));
  const u = page.url();
  if (/\/danke\/?$/.test(u)) { outcome = 'redirect /danke/'; break; }
  const conf = await page.evaluate(() => !!document.querySelector('#irvCard .confirm')).catch(() => false);
  if (conf) { outcome = 'inline-confirm'; break; }
  const err = await page.evaluate(() => { const e = document.getElementById('irvSubmitErr'); return e && e.style.display !== 'none'; }).catch(() => false);
  if (err) { outcome = 'submit-error'; break; }
}
console.log('submit outcome:', outcome, '| url:', page.url());
logs.slice(-8).forEach((l) => console.log(l));
await browser.close();
process.exit(/redirect|inline-confirm/.test(outcome) ? 0 : 2);
