// QA-движок сравнения: Figma-эталон (get_metadata) ↔ живые числа (qa-extract).
// Джойн по ТЕКСТУ + детерминированный дифф. Логика в коде, чтобы агент не мог
// «объяснить» дефект — он получает готовый список и только подтверждает/сортирует.
//
// Запуск:
//   node scripts/qa-compare.mjs --figma <get_metadata.txt|.xml> \
//        --live1440 .visual/p-1440.json --live1920 .visual/p-1920.json \
//        [--json out.json]
//
// figma-файл = сохранённый ответ get_metadata по node-id ФРЕЙМА (не страницы).
// Формат: JSON [{type,text}] с XML внутри, либо сырой XML.

import { readFileSync, writeFileSync } from 'node:fs';

const args = process.argv.slice( 2 );
const val = ( n, d ) => { const i = args.indexOf( `--${ n }` ); return i >= 0 && args[ i + 1 ] ? args[ i + 1 ] : d; };
const figmaFile = val( 'figma', null );
const live1440File = val( 'live1440', null );
const live1920File = val( 'live1920', null );
const jsonOut = val( 'json', null );
if ( ! figmaFile || ! live1440File ) { console.error( 'Нужны --figma и --live1440' ); process.exit( 1 ); }

// ключ джойна: только буквы/цифры (умляуты — буквы). Игнорит пробелы/<br>/пунктуацию.
// Префикс 40 симв.: qa-extract режет текст до 80, Figma даёт полный — иначе длинные
// абзацы не совпадут (висят разом в live-only и figma-only).
const key = ( s ) => ( s || '' ).toLowerCase().replace( /[^0-9a-zà-ÿœæß]/gi, '' ).slice( 0, 40 );
const norm = ( s ) => ( s || '' ).replace( /\s+/g, ' ' ).trim();

// ── Парсер get_metadata. ВАЖНО про координаты Figma: ────────────────────────
// frame/instance задают локальную систему (дети относительны) → их x,y копим.
// group НЕ задаёт систему («прозрачен») → его x,y игнорим, дети остаются в
// координатах родителя. Иначе обёртка-группа двоит смещение (hero уезжает).
function parseFigma( file ) {
	let txt = readFileSync( file, 'utf8' );
	try { const a = JSON.parse( txt ); if ( Array.isArray( a ) ) txt = a.map( ( p ) => p.text || '' ).join( '' ); } catch { /* raw */ }

	const texts = [], bands = [], sections = [];   // sections = верхнеуровневые секции-блоки
	let frameW = 0, frameH = 0, rootSeen = false;
	const stack = [ { cx: 0, cy: 0 } ];

	for ( const raw of txt.split( /\r?\n/ ) ) {
		const line = raw.trim();
		if ( ! line ) continue;
		if ( line.startsWith( '</' ) ) { if ( stack.length > 1 ) stack.pop(); continue; }
		const m = line.match( /^<([a-z-]+)\s+id="[^"]*"\s+name="((?:[^"\\]|\\.)*)"\s+x="(-?[0-9.]+)"\s+y="(-?[0-9.]+)"\s+width="([0-9.]+)"\s+height="([0-9.]+)"/i );
		if ( ! m ) continue;
		const [ , tag, name, xs, ys, ws, hs ] = m;
		const top = stack[ stack.length - 1 ];
		const ax = top.cx + parseFloat( xs ), ay = top.cy + parseFloat( ys );
		const w = parseFloat( ws ), h = parseFloat( hs );
		const selfClosed = /\/>\s*$/.test( line );

		if ( ! rootSeen ) { rootSeen = true; frameW = w; frameH = h; if ( ! selfClosed ) stack.push( { cx: 0, cy: 0, root: true } ); continue; }

		// Верхнеуровневый блок (родитель = корень) = секция страницы.
		if ( top.root && /frame|instance/.test( tag ) ) sections.push( { name, y0: ay, y1: ay + h, label: '' } );

		if ( tag === 'text' ) texts.push( { text: norm( name ), x: ax, y: ay, w, h } );
		else if ( w >= frameW * 0.9 && h >= 200 ) bands.push( { y: ay, h } );   // полноширинная секция-band

		if ( ! selfClosed ) {
			// Figma экспортит группы как <frame name="Group …"> — они «прозрачны»
			// (не задают локальную систему). Иначе обёртка-группа двоит смещение.
			const transparent = /^Group\b/i.test( name );
			stack.push( transparent ? { cx: top.cx, cy: top.cy } : { cx: ax, cy: ay } );
		}
	}
	// Метка секции = её первый (верхний) текст; иначе имя фрейма.
	sections.sort( ( a, b ) => a.y0 - b.y0 );
	for ( const s of sections ) {
		const inside = texts.filter( ( t ) => t.y >= s.y0 - 1 && t.y < s.y1 ).sort( ( a, b ) => a.y - b.y );
		s.label = inside[ 0 ] ? inside[ 0 ].text.slice( 0, 38 ) : s.name;
	}
	return { frameW, frameH, texts, bands, sections };
}

const fig = parseFigma( figmaFile );
const live1440 = JSON.parse( readFileSync( live1440File, 'utf8' ) );
const live1920 = live1920File ? JSON.parse( readFileSync( live1920File, 'utf8' ) ) : null;

const idx = ( arr ) => { const m = new Map(); for ( const t of arr ) { const k = key( t.text ); if ( k && ! m.has( k ) ) m.set( k, t ); } return m; };
const liveBy = idx( live1440.texts );
const live19By = live1920 ? idx( live1920.texts ) : new Map();
const figBy = idx( fig.texts );
const lhPx = ( t ) => { const v = parseFloat( t.lineHeight ); return Number.isFinite( v ) ? v : ( t.fontSize || 16 ) * 1.2; };
const HERO_H = 900;   // высота hero-фрейма в дизайне Rosenberger
const secOf = ( y ) => ( fig.sections.find( ( s ) => y >= s.y0 - 1 && y < s.y1 )?.label ) || 'прочее';

// ── 1. Совпавшие → дельты ────────────────────────────────────────────────────
const brMiss = [], geom = [], vwDrift = [], order = [];
let matchedN = 0;
for ( const [ k, ft ] of figBy ) {
	const lt = liveBy.get( k );
	if ( ! lt ) continue;
	matchedN++;
	order.push( { text: ft.text.slice( 0, 40 ), figY: ft.y, liveY: lt.y } );
	const figLeftPct = ( ft.x / fig.frameW ) * 100;
	const figLines = Math.max( 1, Math.round( ft.h / lhPx( lt ) ) );
	const multiline = figLines > 1 || lt.lines > 1;

	// перенос: ТОЛЬКО для заголовков/цитат (абзацы законно перетекают, не дефект).
	// В дизайне N строк, на лайве меньше и нет явного <br> → потерян брейк.
	const isHead = /^h[1-6]$/.test( lt.tag ) || lt.tag === 'blockquote';
	if ( isHead && figLines > 1 && lt.lines < figLines && lt.brCount === 0 )
		brMiss.push( { sec: secOf( ft.y ), text: ft.text.slice( 0, 45 ), figLines, liveLines: lt.lines } );

	// ширина: значима только для многострочных (бокс реально ограничивает перенос)
	const dW = Math.round( lt.w - ft.w );
	if ( multiline && Math.abs( dW ) > 12 )
		geom.push( { sec: secOf( ft.y ), text: ft.text.slice( 0, 45 ), figW: Math.round( ft.w ), liveW: Math.round( lt.w ), dW } );

	// vw-дрейф: hero-элемент (figma y<HERO_H) должен масштабироваться. Сигнал —
	// x НЕ меняется между 1440 и 1920 (захардкожен px вместо vw).
	if ( ft.y < HERO_H && live19By.has( k ) ) {
		const lt19 = live19By.get( k );
		if ( Math.abs( lt19.x - lt.x ) < 2 && lt.x > 8 )
			vwDrift.push( { sec: secOf( ft.y ), text: ft.text.slice( 0, 40 ), figLeftPct: +figLeftPct.toFixed( 1 ), x: Math.round( lt.x ), live1440: lt.leftPct, live1920: lt19.leftPct } );
	}
}

// ── 1b. Порядок секций: на лайве y должен расти в том же порядке, что в Figma ──
// Идём по элементам в порядке дизайна (figY) и ловим, где live-y «откатывается»
// ниже уже пройденного — значит блок переехал выше, чем задумано (свап секций).
const orderFlags = [];
const seq = [ ...order ].sort( ( a, b ) => a.figY - b.figY );
let maxLiveY = -Infinity, anchor = null;
for ( const e of seq ) {
	if ( e.liveY < maxLiveY - 40 )                 // допуск 40px (одна строка/ряд)
		orderFlags.push( { text: e.text, after: anchor?.text?.slice( 0, 30 ), figY: Math.round( e.figY ), liveY: Math.round( e.liveY ) } );
	if ( e.liveY > maxLiveY ) { maxLiveY = e.liveY; anchor = e; }
}

// ── 2. Непарные тексты ───────────────────────────────────────────────────────
const tiny = ( s ) => key( s ).length < 5;
const figmaOnly = [ ...figBy.values() ].filter( ( t ) => ! liveBy.has( key( t.text ) ) && ! tiny( t.text ) ).map( ( t ) => t.text.slice( 0, 60 ) );
// Хром (меню/футер/нав) исключаем: его текст в Figma лежит в неразвёрнутых
// компонентах-инстансах, поэтому он всегда «live-only» и шумит. Оставляем
// контентные узлы — среди них и видно реальные ЛИШНИЕ секции (напр. «Warum ich Makler»).
const chrome = /menu|menü|kontakt|impressum|datenschutz|inhalt springen|©|cookie|leistungen|standorte|entdecken|nützliche/i;
const isChromeNode = ( t ) => /footer|header|site-|menu|nav|widget/i.test( t.cls );
const liveOnly = live1440.texts
	.filter( ( t ) => ! figBy.has( key( t.text ) ) && ! tiny( t.text ) && ! [ 'span', 'a', 'li' ].includes( t.tag ) && ! chrome.test( t.text ) && ! isChromeNode( t ) )
	.map( ( t ) => `${ t.tag } "${ t.text.slice( 0, 55 ) }"` );

// ── 3. Вес шрифта: выброс среди КОНТЕНТНЫХ абзацев (без хрома) ────────────────
// только длинная контентная копия (>55 симв.) — отсекает лейблы/контакты/подписи,
// где weight 400/600 легитимен. Среди длинных абзацев тело статьи = 300, выброс = жирнее.
// Hero-зона (y < высоты hero-фрейма) ИСКЛЮЧЕНА: hero-копия по дизайну Medium/500,
// а не body-300 — иначе ложный выброс. Вес hero сверяет критик/Фаза 2.
const isChrome = ( t ) => /footer|header|site-|menu|nav|widget|logo|hero/i.test( t.cls );
const body = live1440.texts.filter( ( t ) => t.tag === 'p' && t.fontSize >= 15 && t.fontSize <= 26 && t.text.length > 55 && t.y > 1009 && ! isChrome( t ) );
const wCount = {};
for ( const t of body ) wCount[ t.fontWeight ] = ( wCount[ t.fontWeight ] || 0 ) + 1;
const domW = Object.entries( wCount ).sort( ( a, b ) => b[ 1 ] - a[ 1 ] )[ 0 ]?.[ 0 ];
const weightOutliers = body.filter( ( t ) => t.fontWeight !== domW && +t.fontWeight > +domW )   // только ЖИРНЕЕ — это и есть «толще дизайна»
	.map( ( t ) => { const f = figBy.get( key( t.text ) ); return { sec: f ? secOf( f.y ) : 'прочее', weight: t.fontWeight, expected: domW, size: t.fontSize, text: t.text.slice( 0, 45 ) }; } );

// ── 3b. Вес ЗАГОЛОВКОВ: выброс среди крупных h1–h3 ────────────────────────────
// Заголовки секций держат единый вес (обычно 400). Заголовок легче ИЛИ тяжелее
// остальных = дефект (часто хардкод font-weight в SCSS блока). Ловит то, что
// body-проверка (только <p>) пропускает — напр. consultation-cta 300 vs сиблинги 400.
const heads = live1440.texts.filter( ( t ) => /^h[1-3]$/.test( t.tag ) && t.fontSize >= 40 && ! isChrome( t ) );
const hCount = {};
for ( const t of heads ) hCount[ t.fontWeight ] = ( hCount[ t.fontWeight ] || 0 ) + 1;
const domHW = Object.entries( hCount ).sort( ( a, b ) => b[ 1 ] - a[ 1 ] )[ 0 ]?.[ 0 ];
const headWeightOutliers = ! domHW ? [] : heads.filter( ( t ) => t.fontWeight !== domHW )
	.map( ( t ) => ( { sec: ( t.cls.match( /([a-z][a-z-]*)__/ ) || [ , 'прочее' ] )[ 1 ], weight: t.fontWeight, expected: domHW, size: t.fontSize, text: t.text.slice( 0, 40 ) } ) );

// ── 4. Картинки full-bleed: подозрение только если в дизайне НЕТ band'ов ──────
const imgFlags = live1440.images.filter( ( im ) => im.fullBleed ).map( ( im ) =>
	( { src: im.src, widthPct: im.widthPct, designBands: fig.bands.length } ) );

// ── Отчёт ────────────────────────────────────────────────────────────────────
const out = { figma: { frameW: fig.frameW, frameH: fig.frameH, texts: fig.texts.length, bands: fig.bands.length }, liveDocH: live1440.docHeight, matchedN, vwDrift, brMiss, geom, orderFlags, weightOutliers, headWeightOutliers, liveOnly, figmaOnly, imgFlags };
if ( jsonOut ) writeFileSync( jsonOut, JSON.stringify( out, null, 2 ) );

const P = ( ...a ) => console.log( ...a );
P( `\n# QA-COMPARE по блокам  (figma ${ fig.frameW }×${ fig.frameH } ↔ live docH ${ live1440.docHeight }; слоёв ${ fig.texts.length }, совпало ${ matchedN })` );

// Все находки-«по блоку» с секцией → группируем по label секции.
const byBlock = new Map();
const add = ( sec, line ) => { if ( ! byBlock.has( sec ) ) byBlock.set( sec, [] ); byBlock.get( sec ).push( line ); };
vwDrift.forEach( ( d ) => add( d.sec, `vw-отступ: hero «${ d.text }» x=${ d.x }px фикс (Figma ${ d.figLeftPct }% → на 1920 ${ d.live1920 }%) — должен быть vw` ) );
brMiss.forEach( ( d ) => add( d.sec, `перенос: «${ d.text }» Figma ${ d.figLines } стр. → лайв ${ d.liveLines } (нет <br>)` ) );
geom.forEach( ( d ) => add( d.sec, `ширина: «${ d.text }» Figma ${ d.figW } → лайв ${ d.liveW } (Δ${ d.dW > 0 ? '+' : '' }${ d.dW })` ) );
weightOutliers.forEach( ( d ) => add( d.sec, `вес: «${ d.text }» weight ${ d.weight } вместо ${ d.expected }` ) );

// Печать по порядку секций сверху вниз; секции без дефектов помечаем «ок».
P( `\n## Дефекты по блокам (сверху вниз):` );
let nDef = 0;
for ( const s of fig.sections ) {
	const items = byBlock.get( s.label );
	if ( ! items || ! items.length ) continue;
	nDef += items.length;
	P( `\n  ▸ Блок «${ s.label }»  (y ${ Math.round( s.y0 ) }):` );
	items.forEach( ( l ) => P( `      🔴 ${ l }` ) );
}
if ( byBlock.has( 'прочее' ) ) { P( `\n  ▸ Прочее (вне секций / футер):` ); byBlock.get( 'прочее' ).forEach( ( l ) => P( `      🔴 ${ l }` ) ); }
if ( ! nDef && ! byBlock.has( 'прочее' ) ) P( '  — измеримых дефектов внутри блоков нет' );

const sec = ( title, arr, fn ) => { P( `\n## ${ title }` ); arr.length ? arr.slice( 0, 25 ).forEach( ( x ) => P( '  ' + fn( x ) ) ) : P( '  — нет' ); };
sec( `🔴 ВЕС ЗАГОЛОВКОВ (выброс vs большинство ${ domHW }):`, headWeightOutliers, ( d ) => `[${ d.sec }] «${ d.text }» weight ${ d.weight } вместо ${ d.expected } (size ${ d.size })` );
sec( '🟠 ПОРЯДОК блоков (на лайве переехал выше дизайна):', orderFlags, ( d ) => `"${ d.text }" выше "${ d.after }" (figY ${ d.figY } → liveY ${ d.liveY })` );
sec( '🟠 LIVE-ONLY (на лайве есть, в Figma НЕТ — лишняя секция / реальный контент vs плейсхолдер):', liveOnly, ( t ) => t );
sec( '🟠 FIGMA-ONLY (в дизайне есть, на лайве НЕТ — потеряно / свёрнутый аккордеон):', figmaOnly, ( t ) => `"${ t }"` );
sec( '🟠 КАРТИНКИ full-bleed:', imgFlags, ( im ) => `${ im.src } (${ im.widthPct }%) — band'ов в дизайне: ${ im.designBands }${ im.designBands === 0 ? ' → ПОДОЗРЕНИЕ на растяжение' : ' (вероятно штатный band)' }` );
// скрытые контролы каруселей, но без хрома (моб. меню/шапка скрыты штатно)
const hiddenCtl = ( live1440.controls || [] ).filter( ( c ) => c.hidden && ! /menu|site-|header|nav-toggle/i.test( c.cls ) );
sec( '🔴 СКРЫТЫЕ контролы каруселей (не листается / нет пагинации):', hiddenCtl, ( c ) => `[${ c.cls }] display:${ c.display } — пагинация/стрелка скрыта` );
P( `\n## СТРУКТУРА: Figma H ${ fig.frameH } vs live docH ${ live1440.docHeight } (Δ${ live1440.docHeight - fig.frameH }) | секций в дизайне ${ fig.sections.length }, live-only ${ liveOnly.length }, figma-only ${ figmaOnly.length }` );
