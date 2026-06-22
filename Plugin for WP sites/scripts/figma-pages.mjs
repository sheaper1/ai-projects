// Из сохранённого ответа get_metadata (дерево фреймов страницы Figma) строит
// markdown-таблицу «страница → node-id desktop / mobile». Один раз на проект.
//
// Зачем: get_metadata по странице отдаёт 900K+ XML (в контекст не лезет, сохраняется
// в tool-result файл). Этот скрипт парсит его детерминированно и пэйрит desktop
// (ширина ≥ 700) с mobile (< 700, имя «… mobile/MOBILE») по имени — чтобы и слабая
// модель не писала ad-hoc парсер по огромному XML.
//
// Запуск:
//   1) Агент: get_metadata(fileKey) без nodeId → id страницы (напр. 142:2).
//   2) Агент: get_metadata(fileKey, nodeId=<page id>) → ответ сохранится в файл.
//   3) node scripts/figma-pages.mjs "<путь к сохранённому tool-result>"
//
// Вывод — готовая таблица для вставки в карту страниц проекта (синонимы RU/EN
// дописываются руками).

import { readFileSync } from 'node:fs';

const file = process.argv[ 2 ];
if ( ! file ) {
	console.error( 'Укажи путь к сохранённому tool-result get_metadata:\n  node scripts/figma-pages.mjs "<...>.txt"' );
	process.exit( 1 );
}

// tool-result = JSON-массив [{type,text}] с XML внутри; иначе — сырой текст.
let txt = readFileSync( file, 'utf8' );
try {
	const arr = JSON.parse( txt );
	if ( Array.isArray( arr ) ) txt = arr.map( ( p ) => p.text || '' ).join( '' );
} catch { /* уже сырой XML */ }

// Прямые дети canvas/страницы = верхнеуровневые фреймы (отступ ровно 2 пробела).
const re = /^  <frame id="([^"]+)" name="([^"]+)" x="[^"]*" y="[^"]*" width="([0-9.]+)" height="([0-9.]+)"/;
const frames = [];
for ( const line of txt.split( /\r?\n/ ) ) {
	const m = line.match( re );
	if ( m ) frames.push( { id: m[ 1 ], name: m[ 2 ], w: Math.round( +m[ 3 ] ), h: Math.round( +m[ 4 ] ) } );
}
if ( ! frames.length ) {
	console.error( 'Не нашёл верхнеуровневых фреймов. Это точно ответ get_metadata по странице (а не по узлу)?' );
	process.exit( 1 );
}

// Нормализация имени для пэйринга: вниз регистр, убрать "mobile", схлопнуть пробелы.
const norm = ( n ) => n.toLowerCase().replace( /\bmobile\b/g, '' ).replace( /\s+/g, ' ' ).trim();
const desktops = frames.filter( ( f ) => f.w >= 700 );
const mobiles = frames.filter( ( f ) => f.w < 700 );
const mobileBy = new Map();
for ( const m of mobiles ) mobileBy.set( norm( m.name ), m.id );

const rows = desktops.map( ( d ) => ( {
	name: d.name,
	desktop: d.id,
	mobile: mobileBy.get( norm( d.name ) ) || '—',
} ) );

console.log( '| Страница (Figma) | синонимы (RU/EN) | desktop | mobile |' );
console.log( '|---|---|---|---|' );
for ( const r of rows ) {
	console.log( `| ${ r.name } |  | \`${ r.desktop }\` | ${ r.mobile === '—' ? '—' : '`' + r.mobile + '`' } |` );
}

const lonelyMobiles = mobiles.filter( ( m ) => ! desktops.some( ( d ) => norm( d.name ) === norm( m.name ) ) );
console.log( `\n# desktop-фреймов: ${ desktops.length }, mobile: ${ mobiles.length }, без пары mobile: ${ rows.filter( ( r ) => r.mobile === '—' ).length }` );
if ( lonelyMobiles.length ) console.log( `# mobile без desktop: ${ lonelyMobiles.map( ( m ) => m.name ).join( ', ' ) }` );
console.log( '# Допиши синонимы RU/EN руками; junk/WIP-фреймы (Menu/Button/версии) удали.' );
