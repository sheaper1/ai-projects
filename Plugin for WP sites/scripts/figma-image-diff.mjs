#!/usr/bin/env node
// Полный дифф картинок между двумя версиями дизайна в ОДНОМ Figma-файле.
// Слева — оригинал, справа — копия с правками (дубликат, сдвинут по X).
// Метод: пара экранов (старый↔новый) по Y, сравнение картинок ПО ПОЗИЦИИ через
// хэш imageRef. Покрывает все экраны разом, ноль токенов ИИ.
//
// Запуск:  node scripts/figma-image-diff.mjs [FILE_KEY] [Имя_страницы] [--png]
//   --png  — отрендерить превью изменённых нод в scripts/.figma-diff/
//
// ВАЖНО: set-дифф «новые хэши на весь файл» НЕ годится — одно фото может стоять
// на нескольких экранах, и подмена на конкретной карточке теряется. Нужна именно
// позиционная сверка пары экранов. Поэтому читаем дерево на полную глубину.

import fs from 'node:fs';
import path from 'node:path';

const ROOT = path.resolve(import.meta.dirname, '..');

function readEnv(name) {
  if (process.env[name]) return process.env[name];
  try {
    const txt = fs.readFileSync(path.join(ROOT, '.env'), 'utf8');
    const m = txt.match(new RegExp('^' + name + '\\s*=\\s*(.+)$', 'm'));
    return m ? m[1].trim().replace(/^["']|["']$/g, '') : null;
  } catch { return null; }
}

const TOKEN = readEnv('FIGMA_TOKEN');
const argv = process.argv.slice(2);
const WANT_PNG = argv.includes('--png');
const pos = argv.filter((a) => !a.startsWith('--'));
const KEY = pos[0] || 'p1HKLfoMcOwtVUD5rI9V3P';
const PAGE = pos[1] || 'UI Design';
if (!TOKEN) { console.error('Нет FIGMA_TOKEN в .env (scope file_content:read).'); process.exit(1); }

console.error(`Тяну файл ${KEY} из Figma API…`);
const res = await fetch(`https://api.figma.com/v1/files/${KEY}`, { headers: { 'X-Figma-Token': TOKEN } });
if (!res.ok) { console.error(`Figma API ${res.status}: ${await res.text()}`); process.exit(1); }
const doc = (await res.json()).document;
const pageNode = doc.children.find((p) => p.name === PAGE);
if (!pageNode) {
  console.error(`Страница «${PAGE}» не найдена. Есть: ${doc.children.map((p) => p.name).join(', ')}`);
  process.exit(1);
}

// Картинки внутри экрана, отсортированные по (y,x) — стабильный порядок для сверки.
function imagesOf(screen) {
  const sx = screen.absoluteBoundingBox?.x ?? 0, sy = screen.absoluteBoundingBox?.y ?? 0;
  const out = [];
  (function rec(n) {
    if (Array.isArray(n.fills)) for (const f of n.fills)
      if (f.type === 'IMAGE' && f.imageRef)
        out.push({ name: n.name, id: n.id, ref: f.imageRef,
          y: Math.round((n.absoluteBoundingBox?.y ?? sy) - sy),
          x: Math.round((n.absoluteBoundingBox?.x ?? sx) - sx) });
    if (n.children) for (const c of n.children) rec(c);
  })(screen);
  out.sort((a, b) => a.y - b.y || a.x - b.x);
  return out;
}

const screens = (pageNode.children || [])
  .filter((s) => s.absoluteBoundingBox)
  .map((s) => ({ name: s.name, id: s.id, x: Math.round(s.absoluteBoundingBox.x), y: Math.round(s.absoluteBoundingBox.y), imgs: imagesOf(s) }))
  .filter((s) => s.imgs.length);

// Левый/правый кластер по наибольшему разрыву X между экранами.
const xs = [...new Set(screens.map((s) => s.x))].sort((a, b) => a - b);
let gap = -1, thr = null;
for (let i = 1; i < xs.length; i++) if (xs[i] - xs[i - 1] > gap) { gap = xs[i] - xs[i - 1]; thr = (xs[i] + xs[i - 1]) / 2; }
const lefts = screens.filter((s) => s.x < thr);
const rights = screens.filter((s) => s.x >= thr);
if (!rights.length) { console.error('Правого кластера (копии) не нашлось — нечего сравнивать.'); process.exit(1); }

// Пара для нового экрана = левый с тем же Y (дубликат сохраняет координаты);
// тай-брейк по базовому имени (без хвостовых цифр) и числу картинок.
const baseName = (n) => n.replace(/[\s\d]+$/, '').trim().toLowerCase();
function pairFor(r) {
  const cand = lefts.map((l) => ({ l, score:
    (Math.abs(l.y - r.y) <= 3 ? 0 : Math.abs(l.y - r.y)) +
    (baseName(l.name) === baseName(r.name) ? 0 : 1000) +
    Math.abs(l.imgs.length - r.imgs.length) * 50 }))
    .sort((a, b) => a.score - b.score)[0];
  return cand ? cand.l : null;
}

const changes = []; // {screen, name, id, oldRef, newRef, y}
const pairs = [];
for (const r of rights) {
  const l = pairFor(r);
  if (!l) { pairs.push({ r, l: null, diff: [] }); continue; }
  const diff = [];
  const n = Math.min(l.imgs.length, r.imgs.length);
  for (let i = 0; i < n; i++) {
    if (l.imgs[i].ref !== r.imgs[i].ref) {
      const c = { screen: r.name, pair: l.name, name: r.imgs[i].name, id: r.imgs[i].id, oldRef: l.imgs[i].ref, newRef: r.imgs[i].ref, y: r.imgs[i].y };
      diff.push(c); changes.push(c);
    }
  }
  pairs.push({ r, l, diff, lenMismatch: l.imgs.length !== r.imgs.length });
}

console.log(`\n=== ДИФФ КАРТИНОК · страница «${PAGE}» · ${lefts.length} старых / ${rights.length} новых экранов ===`);
for (const p of pairs) {
  if (!p.l) { console.log(`\n⚠  «${p.r.name}» — пары слева не найдено`); continue; }
  const tag = p.diff.length ? `${p.diff.length} измен.` : 'без изменений';
  console.log(`\n• «${p.r.name}» ↔ «${p.l.name}»  (${p.r.imgs.length} картинок, ${tag})${p.lenMismatch ? '  ⚠ разное число картинок' : ''}`);
  for (const c of p.diff) console.log(`    y=${String(c.y).padStart(5)}  «${c.name}»  ${c.oldRef.slice(0, 8)} → ${c.newRef.slice(0, 8)}  newid=${c.id}`);
}

// Уникальные изменённые картинки (по новому хэшу) — без дублей desktop/mobile.
const uniq = [...new Map(changes.map((c) => [c.newRef, c])).values()];
console.log(`\nИТОГ: изменённых картинок ${changes.length} (уникальных ${uniq.length}) на ${pairs.filter((p) => p.diff.length).length} экранах.`);

if (WANT_PNG && uniq.length) {
  const ids = uniq.map((c) => c.id);
  const ru = `https://api.figma.com/v1/images/${KEY}?ids=${encodeURIComponent(ids.join(','))}&format=png&scale=1`;
  const data = await (await fetch(ru, { headers: { 'X-Figma-Token': TOKEN } })).json();
  const dir = path.join(ROOT, 'scripts', '.figma-diff');
  fs.mkdirSync(dir, { recursive: true });
  for (const c of uniq) {
    const url = data.images?.[c.id]; if (!url) continue;
    const buf = Buffer.from(await (await fetch(url)).arrayBuffer());
    const safe = `${c.screen}-${c.name}`.replace(/[^\w-]+/g, '_').slice(0, 60);
    fs.writeFileSync(path.join(dir, `${safe}.png`), buf);
  }
  console.log(`🖼  Превью изменённых картинок → scripts/.figma-diff/ (${uniq.length} шт.)`);
}
