// Строит карту страниц нового дизайна из Figma REST API (токен из .env).
// GET file page (depth=2) → топ-фреймы с именами/размерами → пары desktop/mobile.
import { readFileSync } from 'node:fs';
const env = Object.fromEntries(readFileSync('.env','utf8').split('\n')
  .filter(l=>l.includes('=')&&!l.trim().startsWith('#')).map(l=>{const i=l.indexOf('=');return [l.slice(0,i).trim(), l.slice(i+1).trim()];}));
const TOKEN = env.FIGMA_TOKEN;
const FILE = 'p1HKLfoMcOwtVUD5rI9V3P';
const PAGE = '142:3';
const r = await fetch(`https://api.figma.com/v1/files/${FILE}/nodes?ids=${encodeURIComponent(PAGE)}&depth=2`, { headers:{ 'X-Figma-Token': TOKEN } });
if(!r.ok){ console.error('Figma API', r.status, await r.text()); process.exit(1); }
const j = await r.json();
const page = j.nodes[PAGE]?.document;
if(!page){ console.error('нет страницы'); process.exit(1); }
const frames = (page.children||[]).filter(c=>c.type==='FRAME'||c.type==='INSTANCE'||c.type==='COMPONENT')
  .map(c=>({ id:c.id, name:c.name, x:Math.round(c.absoluteBoundingBox?.x??0), y:Math.round(c.absoluteBoundingBox?.y??0), w:Math.round(c.absoluteBoundingBox?.width??0), h:Math.round(c.absoluteBoundingBox?.height??0) }))
  .sort((a,b)=> a.x-b.x || a.y-b.y);
console.log('Всего топ-фреймов:', frames.length);
for(const f of frames) console.log(`${f.id}\t${f.x}\t${f.w}x${f.h}\t${f.name}`);
