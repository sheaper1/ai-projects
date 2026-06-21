// Оптимизация Figma-ассетов: JPEG → WebP + resize, загрузка в WP.
// Запуск: node scripts/optimize-and-upload.cjs

const { readFileSync, writeFileSync } = require('fs');
const { resolve } = require('path');
const sharp = require('sharp');

const root = resolve(__dirname, '..');
const tmp  = resolve(root, '.figma-tmp');

const env = {};
for (const line of readFileSync(resolve(root, '.env'), 'utf8').split(/\r?\n/)) {
  const m = line.match(/^([A-Z_]+)=(.*)$/);
  if (m) env[m[1]] = m[2];
}
const BASE = env.WP_URL.replace(/\/$/, '');
const AUTH = 'Basic ' + Buffer.from(`${env.WP_USER}:${env.WP_APP_PASSWORD}`).toString('base64');

const assets = [
  { slug: 'region-feldkirch',  src: 'region-feldkirch.jpg',  maxSize: 1400, quality: 82 },
  { slug: 'region-dornbirn',   src: 'region-dornbirn.jpg',   maxSize: 1400, quality: 82 },
  { slug: 'region-bludenz',    src: 'region-bludenz.jpg',    maxSize: 1400, quality: 82 },
  { slug: 'property-sample-1', src: 'property-1.jpg',        maxSize: 1200, quality: 80 },
  { slug: 'property-sample-2', src: 'property-2.jpg',        maxSize: 1200, quality: 80 },
  { slug: 'property-sample-3', src: 'property-3.jpg',        maxSize: 1200, quality: 80 },
  { slug: 'property-sample-4', src: 'property-4.jpg',        maxSize: 1200, quality: 80 },
];

const wpFetch = (path, opts = {}) =>
  fetch(`${BASE}${path}`, {
    ...opts,
    headers: { Authorization: AUTH, ...(opts.headers || {}) },
  });

async function run() {
  console.log('Оптимизация и загрузка ассетов...\n');
  const results = {};

  for (const { slug, src, maxSize, quality } of assets) {
    const srcPath = resolve(tmp, src);
    const meta = await sharp(srcPath).metadata();
    const longSide = Math.max(meta.width, meta.height);
    const scale    = longSide > maxSize ? maxSize / longSide : 1;
    const newW     = Math.round(meta.width  * scale);
    const newH     = Math.round(meta.height * scale);

    const webpBuf = await sharp(srcPath)
      .resize(newW, newH)
      .webp({ quality, effort: 5 })
      .toBuffer();

    const oldKb  = Math.round(readFileSync(srcPath).length / 1024);
    const newKb  = Math.round(webpBuf.length / 1024);
    const saving = Math.round((1 - newKb / oldKb) * 100);

    console.log(`${slug}: ${meta.width}×${meta.height} → ${newW}×${newH}  |  ${oldKb}KB → ${newKb}KB (-${saving}%)`);

    writeFileSync(resolve(tmp, slug + '.webp'), webpBuf);

    // Удаляем старую версию
    const check = await wpFetch(`/wp-json/wp/v2/media?slug=${encodeURIComponent(slug)}&per_page=1`);
    const existing = await check.json();
    if (Array.isArray(existing) && existing[0]) {
      await wpFetch(`/wp-json/wp/v2/media/${existing[0].id}?force=true`, { method: 'DELETE' });
      console.log(`  ✗ удалена старая (id=${existing[0].id})`);
    }

    // Загружаем WebP
    const r = await wpFetch('/wp-json/wp/v2/media', {
      method: 'POST',
      headers: {
        'Content-Type': 'image/webp',
        'Content-Disposition': `attachment; filename="${slug}.webp"`,
      },
      body: webpBuf,
    });
    const body = await r.json();
    if (!r.ok) throw new Error(`${slug}: ${body.message || r.status}`);
    console.log(`  ✓ id=${body.id}  ${body.source_url}\n`);
    results[slug] = { id: body.id, url: body.source_url };
  }

  console.log('=== Media IDs ===');
  for (const [slug, { id }] of Object.entries(results)) {
    console.log(`  ${slug}: ${id}`);
  }
}

run().catch(e => { console.error(e.message); process.exit(1); });
