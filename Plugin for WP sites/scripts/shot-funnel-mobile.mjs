// Мобильный скрин фаннел-формы: начальный шаг + контактный шаг (много полей).
import puppeteer from 'puppeteer';
import sharp from 'sharp';
import { resolve } from 'node:path';

const URL = process.argv[2];
const name = process.argv[3] || 'funnel';
const browser = await puppeteer.launch({ headless: 'new', args: ['--no-sandbox'] });
const page = await browser.newPage();
await page.emulateMediaFeatures([{ name: 'prefers-reduced-motion', value: 'reduce' }]);
await page.setViewport({ width: 375, height: 800, deviceScaleFactor: 2, isMobile: true, hasTouch: true });
await page.goto(URL, { waitUntil: 'networkidle2', timeout: 60000 });
await new Promise((r) => setTimeout(r, 800));

async function shot(tag) {
  const el = await page.$('.wp-block-library-tipper-form');
  const raw = resolve('.visual', `.${name}-${tag}-raw.png`);
  await el.screenshot({ path: raw });
  const out = resolve('.visual', `${name}-${tag}.png`);
  await sharp(raw).resize({ width: 750 }).png().toFile(out);
  console.log(out);
}

await shot('step1');

// прокликать до контактного шага
let step = 0;
while (step < 30) {
  const s = await page.evaluate(() => ({
    hasContact: !!document.getElementById('irvContact'),
    hasPlz: !!document.getElementById('irvPlz'),
    hasRange: !!document.getElementById('irvRg'),
    opts: document.querySelectorAll('#irvCard .opt').length,
  }));
  if (s.hasContact) break;
  if (s.hasPlz) { await page.type('#irvPlz', '6900'); await page.evaluate(() => [...document.querySelectorAll('#irvCard .btn')].find((b) => /Weiter/i.test(b.textContent))?.click()); }
  else if (s.hasRange) { await page.evaluate(() => [...document.querySelectorAll('#irvCard .btn')].find((b) => /Weiter/i.test(b.textContent))?.click()); }
  else if (s.opts > 0) {
    await page.evaluate(() => document.querySelector('#irvCard .opt')?.click());
    await new Promise((r) => setTimeout(r, 150));
    const multi = await page.evaluate(() => document.querySelectorAll('#irvCard .opt.sel').length > 0 && !document.getElementById('irvContact'));
    if (multi) await page.evaluate(() => [...document.querySelectorAll('#irvCard .btn')].find((b) => /Weiter/i.test(b.textContent))?.click());
  }
  else { await page.evaluate(() => [...document.querySelectorAll('#irvCard .btn')][0]?.click()); }
  await new Promise((r) => setTimeout(r, 250));
  step++;
}
await page.evaluate(() => document.querySelector('.wp-block-library-tipper-form').scrollIntoView());
await new Promise((r) => setTimeout(r, 300));
await shot('contact');
await browser.close();
