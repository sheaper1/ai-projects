// QA-ГЕЙТ — детерминированная проверка секции ПО ЧИСЛАМ, без единого токена LLM.
// Идея: эталон секции замораживается локально один раз (golden-снапшот живого
// корректного состояния ИЛИ дизайн-spec из Figma), дальше сверка — чистый скрипт.
// Чистая секция → PASS за секунды и даром; агент включается ТОЛЬКО на находку.
//
// Команды:
//   node scripts/qa-gate.mjs snapshot <url> --sel <css> --slug <slug>
//        → снимает живую секцию на 1440/1920/375 и пишет эталон .visual/golden/<slug>.json
//   node scripts/qa-gate.mjs check <url> --sel <css> --slug <slug> [--json out]
//        → сверяет живую секцию с эталоном, печатает дефекты, exit 1 если есть
//
// Что ловит (всё детерминированно, точнее зрения):
//   • vw-дрейф (leftPct на 1440 ≠ 1920 — отступ захардкожен в px)
//   • вес/размер/шрифт/трекинг/межстрочный, потерю <br>, выравнивание, ширину
//   • цвет (computed) — невидим на скриншоте
//   • потерянный/изменённый текст; растяжение/object-fit картинок; скрытые карусели
//
// Эталон в git (как .qa-state.json): общий, переживает сессии.

import { execFileSync } from 'node:child_process';
import { writeFileSync, readFileSync, existsSync, mkdirSync } from 'node:fs';
import { resolve, dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = resolve( dirname( fileURLToPath( import.meta.url ) ), '..' );
const goldenDir = join( root, 'qa-golden' );   // коммитим в git (общий эталон, как .qa-state.json)

const argv = process.argv.slice( 2 );
const cmd = argv[ 0 ];
const url = argv.find( ( a ) => /^https?:\/\//.test( a ) );
const val = ( n, d ) => { const i = argv.indexOf( `--${ n }` ); return i >= 0 && argv[ i + 1 ] ? argv[ i + 1 ] : d; };
const sel = val( 'sel', null );
const slug = val( 'slug', null );
const jsonOut = val( 'json', null );

if ( ! cmd || ! url || ! slug ) {
	console.error( 'Использование: qa-gate.mjs <snapshot|check> <url> --sel <css> --slug <slug>' );
	process.exit( 2 );
}

// --- снять живую секцию на трёх ширинах (переиспользуем qa-extract) ----------
const extract = ( width ) => {
	const out = execFileSync( 'node', [
		join( root, 'scripts', 'qa-extract.mjs' ), url, '--width', String( width ),
		...( sel ? [ '--sel', sel ] : [] ),
	], { encoding: 'utf8', stdio: [ 'ignore', 'pipe', 'ignore' ], maxBuffer: 64 * 1024 * 1024 } );
	return JSON.parse( out );
};

const grab = () => ( { w1440: extract( 1440 ), w1920: extract( 1920 ), w375: extract( 375 ) } );

// Компактный «отпечаток» текста для эталона/сверки.
const fp = ( t ) => ( {
	text: t.text, tag: t.tag,
	fontSize: t.fontSize, fontWeight: t.fontWeight, fontFamily: t.fontFamily,
	lineHeight: t.lineHeight, letterSpacing: t.letterSpacing, color: t.color,
	textAlign: t.textAlign, brCount: t.brCount, w: t.w, x: t.x,
} );

if ( cmd === 'snapshot' ) {
	const g = grab();
	if ( g.w1440.missing ) { console.error( `⚠ Селектор не найден: ${ sel }` ); process.exit( 2 ); }
	// Hero-инвариант: для full-bleed секции leftPct заголовка должен быть равен на
	// 1440 и 1920 (vw-инсет). Замеряем дельту в эталоне — если ~0, секция hero и при
	// check дрейф будет дефектом; если велика (центрированный контейнер) — не hero,
	// дрейф не проверяем. Так гейт сам отличает hero от обычной секции.
	const heroDrift = {};
	const p1920 = Object.fromEntries( g.w1920.texts.map( ( t ) => [ t.text, t.leftPct ] ) );
	g.w1440.texts.filter( ( t ) => /^h[1-6]$/.test( t.tag ) ).forEach( ( t ) => {
		if ( p1920[ t.text ] != null && Math.abs( t.leftPct ) < 100 && Math.abs( p1920[ t.text ] ) < 100 ) {
			heroDrift[ t.text ] = Math.round( Math.abs( t.leftPct - p1920[ t.text ] ) * 10 ) / 10;
		}
	} );
	const golden = {
		slug, url, sel, savedAt: '(stamp-after)',
		desktop: { texts: g.w1440.texts.map( fp ), images: g.w1440.images, controls: g.w1440.controls },
		heroDrift,   // текст заголовка → |Δ leftPct| 1440↔1920 в корректном состоянии
		mobile: { texts: g.w375.texts.map( fp ), images: g.w375.images },
	};
	if ( ! existsSync( goldenDir ) ) mkdirSync( goldenDir, { recursive: true } );
	writeFileSync( join( goldenDir, `${ slug }.json` ), JSON.stringify( golden, null, 2 ) + '\n' );
	console.error( `✅ эталон сохранён: qa-golden/${ slug }.json (desktop ${ golden.desktop.texts.length } текстов, mobile ${ golden.mobile.texts.length })` );
	process.exit( 0 );
}

if ( cmd !== 'check' ) { console.error( `Неизвестная команда: ${ cmd }` ); process.exit( 2 ); }

// --- check -------------------------------------------------------------------
const goldenPath = join( goldenDir, `${ slug }.json` );
if ( ! existsSync( goldenPath ) ) { console.error( `Нет эталона: ${ goldenPath }. Сначала snapshot.` ); process.exit( 2 ); }
const golden = JSON.parse( readFileSync( goldenPath, 'utf8' ).replace( /^﻿/, '' ) );
const live = grab();
if ( live.w1440.missing ) { console.error( `⚠ Секция исчезла с лайва: ${ sel }` ); process.exit( 1 ); }

const defects = [];
const add = ( where, el, field, expected, actual ) => defects.push( { where, el: ( el || '' ).slice( 0, 40 ), field, expected, actual } );

const px = ( v ) => { const n = parseFloat( v ); return Number.isFinite( n ) ? n : null; };
const near = ( a, b, tol ) => { const x = px( a ), y = px( b ); return x === null || y === null ? a === b : Math.abs( x - y ) <= tol; };

// индекс эталона по тексту (с поддержкой дублей)
const indexByText = ( arr ) => { const m = new Map(); arr.forEach( ( t ) => { const k = t.text; ( m.get( k ) || m.set( k, [] ).get( k ) ).push( t ); } ); return m; };

// есть ли на лайве текст, начинающийся так же (допуск на перестановку/конкатенацию
// динамических лейблов сторонних форм — иначе ложные «потерян/лишний»).
const head20 = ( s ) => ( s || '' ).slice( 0, 20 );
const someStartsWith = ( arr, s ) => { const h = head20( s ); return arr.some( ( l ) => head20( l.text ) === h || l.text.includes( s ) || s.includes( l.text ) ); };

function compareTexts( where, goldTexts, liveTexts ) {
	const liveIdx = indexByText( liveTexts );
	for ( const g of goldTexts ) {
		const cand = liveIdx.get( g.text );
		if ( ! cand || ! cand.length ) {
			// потерян ТОЛЬКО если нет даже похожего по началу (реальная потеря текста/<br>)
			if ( ! someStartsWith( liveTexts, g.text ) ) add( where, g.text, 'текст', 'есть в эталоне', '— потерян/изменён' );
			continue;
		}
		const l = cand.shift();           // ближайший по порядку
		// ТИПОГРАФИКА — стабильна, проверяем на всех текстах
		if ( ! near( g.fontSize, l.fontSize, 0.6 ) ) add( where, g.text, 'fontSize', g.fontSize, l.fontSize );
		if ( String( g.fontWeight ) !== String( l.fontWeight ) ) add( where, g.text, 'fontWeight', g.fontWeight, l.fontWeight );
		if ( g.fontFamily !== l.fontFamily ) add( where, g.text, 'fontFamily', g.fontFamily, l.fontFamily );
		if ( ! near( g.letterSpacing, l.letterSpacing, 0.3 ) ) add( where, g.text, 'letterSpacing', g.letterSpacing, l.letterSpacing );
		if ( ! near( g.lineHeight, l.lineHeight, 1.5 ) ) add( where, g.text, 'lineHeight', g.lineHeight, l.lineHeight );
		if ( g.brCount !== l.brCount ) add( where, g.text, '<br>', g.brCount, l.brCount );
		if ( g.textAlign !== l.textAlign ) add( where, g.text, 'textAlign', g.textAlign, l.textAlign );
		if ( g.color !== l.color ) add( where, g.text, 'color', g.color, l.color );
		// ПОЗИЦИЯ/ШИРИНА — только структурные заголовки/цитаты (формы/спаны шумят),
		// та же ширина вьюпорта → тот же x ожидается (регресс к эталону).
		if ( /^(h[1-6]|blockquote)$/.test( g.tag ) ) {
			if ( g.x != null && l.x != null && Math.abs( g.x ) < 4000 && ! near( g.x, l.x, 3 ) ) add( where, g.text, 'x (позиция)', g.x, l.x );
			if ( ! near( g.w, l.w, 4 ) ) add( where, g.text, 'width', g.w, l.w );
		}
	}
}

// 1) desktop по эталону (1440)
compareTexts( 'desktop', golden.desktop.texts, live.w1440.texts );
// 2) mobile по эталону (375) — только типографика, mobile перетекает
compareTexts( 'mobile', golden.mobile.texts, live.w375.texts );

// 3) hero-инвариант: ТОЛЬКО для заголовков, что в эталоне были vw-стабильны (heroDrift~0).
const live1920 = Object.fromEntries( live.w1920.texts.map( ( t ) => [ t.text, t.leftPct ] ) );
const live1440 = Object.fromEntries( live.w1440.texts.map( ( t ) => [ t.text, t.leftPct ] ) );
for ( const [ text, gd ] of Object.entries( golden.heroDrift || {} ) ) {
	if ( gd > 1 ) continue;                       // в эталоне не vw-стабилен → не hero, не проверяем
	const a = live1440[ text ], b = live1920[ text ];
	if ( a != null && b != null && Math.abs( a - b ) > 1 ) {
		add( 'desktop', text, 'hero vw-дрейф', `≈0% (vw-инсет)`, `1440:${ a }% vs 1920:${ b }% (захардкожен в px)` );
	}
}

// 4) картинки (desktop): растяжение/object-fit
const gImg = golden.desktop.images || [], lImg = live.w1440.images || [];
gImg.forEach( ( gi, i ) => {
	const li = lImg[ i ]; if ( ! li ) { add( 'desktop', gi.src, 'картинка', gi.src, '— пропала' ); return; }
	if ( gi.objectFit !== li.objectFit ) add( 'desktop', gi.src, 'object-fit', gi.objectFit, li.objectFit );
	if ( gi.fullBleed !== li.fullBleed ) add( 'desktop', gi.src, 'full-bleed', gi.fullBleed, li.fullBleed );
	if ( ! near( gi.w, li.w, 4 ) ) add( 'desktop', gi.src, 'img-width', gi.w, li.w );
} );

// 5) карусели/пагинация: была видима в эталоне → стала скрыта
const gCtl = golden.desktop.controls || [], lCtl = live.w1440.controls || [];
gCtl.forEach( ( gc ) => {
	if ( gc.hidden ) return;
	const match = lCtl.find( ( c ) => c.cls === gc.cls );
	if ( ! match ) add( 'desktop', gc.cls, 'контрол', 'видим', '— исчез' );
	else if ( match.hidden ) add( 'desktop', gc.cls, 'контрол', 'видим', 'скрыт (не листается)' );
} );

// --- вывод -------------------------------------------------------------------
if ( jsonOut ) writeFileSync( jsonOut, JSON.stringify( { slug, clean: defects.length === 0, defects }, null, 2 ) + '\n' );

if ( defects.length === 0 ) {
	console.error( `\n✅ PASS — ${ slug }: расхождений с эталоном нет (desktop+mobile+vw).` );
	process.exit( 0 );
}
console.error( `\n❌ ${ defects.length } находок — ${ slug }:` );
for ( const d of defects ) console.error( `  [${ d.where }] "${ d.el }" · ${ d.field }: ожидалось ${ d.expected } → факт ${ d.actual }` );
process.exit( 1 );
