import { mkdirSync, existsSync } from 'node:fs';
import { resolve } from 'node:path';
import puppeteer from 'puppeteer';
import sharp from 'sharp';

const url = process.argv[2];
const name = process.argv[3] || 'shot';
const mobile = process.argv.includes('--mobile');
const width = mobile ? 375 : 1440;
const outDir = resolve('.visual');
if (!existsSync(outDir)) mkdirSync(outDir, { recursive: true });

const browser = await puppeteer.launch({ headless: 'new', args: ['--no-sandbox'] });
const page = await browser.newPage();
await page.emulateMediaFeatures([{ name: 'prefers-reduced-motion', value: 'reduce' }]);
await page.setViewport({ width, height: 1000, deviceScaleFactor: 1.5, isMobile: mobile, hasTouch: mobile });
await page.goto(url, { waitUntil: 'networkidle2', timeout: 60000 });
await new Promise((r) => setTimeout(r, 1200));
const raw = resolve(outDir, `.${name}-raw.png`);
await page.screenshot({ path: raw, fullPage: true });
await browser.close();

const meta = await sharp(raw).metadata();
const CHUNK = 1800;
const scale = Math.min(1, 1300 / meta.width);
const n = Math.ceil(meta.height / CHUNK);
const files = [];
for (let i = 0; i < n; i++) {
  const top = i * CHUNK;
  const h = Math.min(CHUNK, meta.height - top);
  const out = resolve(outDir, `${name}-${i}.png`);
  await sharp(raw).extract({ left: 0, top, width: meta.width, height: h })
    .resize({ width: Math.round(meta.width * scale) }).png().toFile(out);
  files.push(out);
}
console.log(`${meta.width}x${meta.height} -> ${n} files`);
files.forEach((f) => console.log(f));
