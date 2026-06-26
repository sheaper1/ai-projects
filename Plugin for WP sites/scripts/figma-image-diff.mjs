#!/usr/bin/env node
// Дешёвый дифф картинок в одном Figma-файле: левый кластер (старый) vs правый (новый).
// Сравнивает imageRef (хэш содержимого картинки) — пиксели не рендерим, ИИ не зовём.
//
// Запуск:  node scripts/figma-image-diff.mjs [FILE_KEY]
// Токен берётся из .env (FIGMA_TOKEN=...). Default FILE_KEY = p1HKLfoMcOwtVUD5rI9V3P.

import fs from 'node:fs';
import path from 'node:path';

const ROOT = path.resolve(import.meta.dirname, '..');

// --- читаем .env вручную (без зависимостей) ---
function readEnv(name) {
  if (process.env[name]) return process.env[name];
  try {
    const txt = fs.readFileSync(path.join(ROOT, '.env'), 'utf8');
    const m = txt.match(new RegExp('^' + name + '\\s*=\\s*(.+)$', 'm'));
    return m ? m[1].trim().replace(/^["']|["']$/g, '') : null;
  } catch { return null; }
}

const TOKEN = readEnv('FIGMA_TOKEN');
const KEY = process.argv[2] || 'p1HKLfoMcOwtVUD5rI9V3P';
const PAGE = process.argv[3] || 'UI Design'; // сравниваем кластеры в пределах одной страницы
if (!TOKEN) {
  console.error('Нет FIGMA_TOKEN в .env. Заведи токен: Figma → Settings → Security → Personal access tokens.');
  process.exit(1);
}

console.error(`Тяну файл ${KEY} из Figma API…`);
const res = await fetch(`https://api.figma.com/v1/files/${KEY}?depth=20`, {
  headers: { 'X-Figma-Token': TOKEN },
});
if (!res.ok) {
  console.error(`Figma API ${res.status}: ${await res.text()}`);
  process.exit(1);
}
const doc = (await res.json()).document;

// --- собираем все image-филлы с координатой X ---
const imgs = []; // {imageRef, node, page, x}
function walk(node, page, screen) {
  const x = node.absoluteBoundingBox?.x;
  if (Array.isArray(node.fills)) {
    for (const f of node.fills) {
      if (f.type === 'IMAGE' && f.imageRef && f.visible !== false) {
        imgs.push({ imageRef: f.imageRef, node: node.name, id: node.id, page, screen, x: x ?? null });
      }
    }
  }
  if (node.children) for (const c of node.children) walk(c, page, screen ?? node.name);
}
const pageNode = doc.children.find(p => p.name === PAGE);
if (!pageNode) {
  console.error(`Страница «${PAGE}» не найдена. Есть: ${doc.children.map(p => p.name).join(', ')}`);
  process.exit(1);
}
for (const screen of (pageNode.children || [])) walk(screen, pageNode.name, screen.name);

if (!imgs.length) {
  console.error('Картинок (image fills) в файле не нашлось.');
  process.exit(1);
}

// --- авто-граница левый/правый кластер: самый большой разрыв по X ---
const withX = imgs.filter(i => i.x != null);
const xs = [...new Set(withX.map(i => i.x))].sort((a, b) => a - b);
let gap = -1, thr = null;
for (let i = 1; i < xs.length; i++) {
  if (xs[i] - xs[i - 1] > gap) { gap = xs[i] - xs[i - 1]; thr = (xs[i] + xs[i - 1]) / 2; }
}

const left = withX.filter(i => i.x < thr);
const right = withX.filter(i => i.x >= thr);
const L = new Set(left.map(i => i.imageRef));
const R = new Set(right.map(i => i.imageRef));

const newRefs = [...R].filter(r => !L.has(r));
const goneRefs = [...L].filter(l => !R.has(l));

// представитель ноды для каждого нового хэша (правый кластер)
const byRef = (arr, ref) => arr.find(i => i.imageRef === ref);

console.log('\n=== ДИФФ КАРТИНОК (Figma imageRef) ===');
console.log(`Граница лево/право по X ≈ ${Math.round(thr)} (разрыв ${Math.round(gap)}px)`);
console.log(`Картинок слева: ${left.length} (уник ${L.size}) | справа: ${right.length} (уник ${R.size})\n`);

console.log(`🆕 НОВЫЕ картинки справа (нет в левом): ${newRefs.length}`);
for (const ref of newRefs) {
  const it = byRef(right, ref);
  console.log(`   • экран «${it.screen}» → нода «${it.node}» (id ${it.id})  ref=${ref.slice(0, 12)}…`);
}

console.log(`\n❌ Картинки, которых НЕТ справа (были слева): ${goneRefs.length}`);
for (const ref of goneRefs) {
  const it = byRef(left, ref);
  console.log(`   • экран «${it.screen}» → нода «${it.node}» (id ${it.id})  ref=${ref.slice(0, 12)}…`);
}

console.log(`\nИтог: новых ${newRefs.length}, пропавших ${goneRefs.length}, общих ${[...R].filter(r => L.has(r)).length}.`);

// --- рендерим превью изменённых нод (новые справа), чтобы видеть глазами ---
const newIds = newRefs.map(ref => byRef(right, ref).id);
if (newIds.length) {
  const u = `https://api.figma.com/v1/images/${KEY}?ids=${encodeURIComponent(newIds.join(','))}&format=png&scale=1`;
  const r2 = await fetch(u, { headers: { 'X-Figma-Token': TOKEN } });
  const data = await r2.json();
  console.log('\n🖼  Превью новых картинок:');
  for (const id of newIds) console.log(`   ${id}: ${data.images?.[id] || 'нет рендера'}`);
  fs.writeFileSync(path.join(import.meta.dirname, '.figma-diff-previews.json'), JSON.stringify(data.images || {}, null, 2));
}
