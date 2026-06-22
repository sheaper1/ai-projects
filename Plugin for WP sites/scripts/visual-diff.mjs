// Визуальная сверка реализации со макетом: скриншот staging ↔ PNG из Figma.
//
// Зачем: финальный QA «похоже ли на Figma» — единственное судейское звено, не
// прикрытое скриптом. Этот тул превращает его в конкретный артефакт: heatmap
// расхождений + числа по зонам, чтобы даже модель послабее точно знала, ГДЕ
// разъехалось, а не сравнивала две картинки на глаз с нуля.
//
// Запуск:
//   node scripts/visual-diff.mjs <url> --width 1440 --out .visual/home
//   node scripts/visual-diff.mjs <url> --figma media/figma/home-1440.png --out .visual/home
//   node scripts/visual-diff.mjs <url> --width 375 --figma .../home-375.png   # mobile
//
// Без --figma: только снимает staging (capture). С --figma: ещё дифф + side+heatmap.
// Это СОВЕТНИК, не push-гейт: дизайн↔браузер шумны по природе (живой контент,
// рендер шрифтов), жёсткий порог ломал бы легитимный адаптив. Смотри артефакты.

import puppeteer from 'puppeteer';
import sharp from 'sharp';
import { mkdirSync } from 'node:fs';
import { dirname, resolve } from 'node:path';

const args = process.argv.slice( 2 );
const flag = ( name, def ) => {
	const i = args.indexOf( `--${ name }` );
	return i >= 0 ? args[ i + 1 ] : def;
};
const url = args.find( ( a ) => ! a.startsWith( '--' ) );
if ( ! url ) {
	console.error( 'Укажи URL: node scripts/visual-diff.mjs <url> [--width N] [--figma png] [--out prefix]' );
	process.exit( 1 );
}
const width = parseInt( flag( 'width', '1440' ), 10 );
const out = resolve( process.cwd(), flag( 'out', '.visual/shot' ) );
const figma = flag( 'figma', null );
const threshold = parseInt( flag( 'threshold', '28' ), 10 ); // 0..255 на канал
const bands = parseInt( flag( 'bands', '6' ), 10 );
const SCORE_W = 360; // даунскейл для оценки: гасит шум шрифтов/антиалиаса
mkdirSync( dirname( out ), { recursive: true } );

// --- 1. Скриншот staging --------------------------------------------------
const stagingPng = `${ out }-staging.png`;
const isMobile = width <= 480;
const browser = await puppeteer.launch( { headless: 'new', args: [ '--no-sandbox' ] } );
try {
	const page = await browser.newPage();
	await page.setViewport( { width, height: 900, deviceScaleFactor: 1, isMobile, hasTouch: isMobile } );
	await page.goto( url, { waitUntil: 'networkidle2', timeout: 60000 } );
	// Прокрутка вниз — догрузить lazy-картинки, затем наверх.
	await page.evaluate( async () => {
		await new Promise( ( res ) => {
			let y = 0;
			const t = setInterval( () => {
				window.scrollBy( 0, 600 );
				y += 600;
				if ( y >= document.body.scrollHeight ) { clearInterval( t ); window.scrollTo( 0, 0 ); res(); }
			}, 60 );
		} );
	} );
	await new Promise( ( r ) => setTimeout( r, 800 ) ); // дать дорисоваться
	await page.screenshot( { path: stagingPng, fullPage: true } );
	console.log( `📸 staging → ${ stagingPng } (w=${ width }${ isMobile ? ', mobile' : '' })` );
} finally {
	await browser.close();
}

if ( ! figma ) process.exit( 0 );

// --- 2. Дифф против Figma-PNG ---------------------------------------------
// Оба → ширина SCORE_W, плоский фон белый, сырые RGB.
const rawAt = async ( file, w ) => {
	const { data, info } = await sharp( file )
		.flatten( { background: '#ffffff' } )
		.resize( { width: w } )
		.raw()
		.toBuffer( { resolveWithObject: true } );
	return { data, w: info.width, h: info.height };
};

const A = await rawAt( figma, SCORE_W );      // макет
const B = await rawAt( stagingPng, SCORE_W ); // реализация
const H = Math.min( A.h, B.h );
const heat = Buffer.alloc( SCORE_W * H * 3 );
const bandMiss = new Array( bands ).fill( 0 );
const bandTot = new Array( bands ).fill( 0 );
let miss = 0;

for ( let y = 0; y < H; y++ ) {
	const band = Math.min( bands - 1, Math.floor( ( y / H ) * bands ) );
	for ( let x = 0; x < SCORE_W; x++ ) {
		const i = ( y * SCORE_W + x ) * 3;
		const d = ( Math.abs( A.data[ i ] - B.data[ i ] )
			+ Math.abs( A.data[ i + 1 ] - B.data[ i + 1 ] )
			+ Math.abs( A.data[ i + 2 ] - B.data[ i + 2 ] ) ) / 3;
		bandTot[ band ]++;
		if ( d > threshold ) {
			miss++; bandMiss[ band ]++;
			heat[ i ] = 255; heat[ i + 1 ] = 40; heat[ i + 2 ] = 40; // красный
		} else {
			const g = Math.round( B.data[ i ] * 0.3 + 178 ); // приглушённый фон
			heat[ i ] = heat[ i + 1 ] = heat[ i + 2 ] = g;
		}
	}
}

const pct = ( n, d ) => ( d ? ( 100 * n / d ).toFixed( 1 ) : '0.0' );
const diffPng = `${ out }-diff.png`;
await sharp( heat, { raw: { width: SCORE_W, height: H, channels: 3 } } ).png().toFile( diffPng );

// Side-by-side: макет | реализация (по 480px, выровнены по высоте).
const sideW = 480;
const a2 = sharp( figma ).flatten( { background: '#fff' } ).resize( { width: sideW } );
const b2 = sharp( stagingPng ).flatten( { background: '#fff' } ).resize( { width: sideW } );
const [ am, bm ] = [ await a2.metadata(), await b2.metadata() ];
const sideH = Math.max( am.height, bm.height );
const sidePng = `${ out }-side.png`;
await sharp( { create: { width: sideW * 2 + 8, height: sideH, channels: 3, background: '#fff' } } )
	.composite( [
		{ input: await a2.png().toBuffer(), left: 0, top: 0 },
		{ input: await b2.png().toBuffer(), left: sideW + 8, top: 0 },
	] )
	.png()
	.toFile( sidePng );

// --- 3. Отчёт -------------------------------------------------------------
console.log( `\n🎯 Расхождение с макетом: ${ pct( miss, SCORE_W * H ) }% пикселей (порог ${ threshold })` );
console.log( `   высоты: макет ${ A.h }px, staging ${ B.h }px (при w=${ SCORE_W }) — дельта ${ A.h - B.h }px` );
console.log( '   по зонам сверху вниз:' );
for ( let b = 0; b < bands; b++ ) {
	const p = parseFloat( pct( bandMiss[ b ], bandTot[ b ] ) );
	const bar = '█'.repeat( Math.round( p / 4 ) ) || '·';
	console.log( `     зона ${ b + 1 }/${ bands }: ${ p.toFixed( 1 ).padStart( 5 ) }%  ${ bar }` );
}
console.log( `\n   side  → ${ sidePng }` );
console.log( `   heat  → ${ diffPng }   (красное = разъехалось; смотри зоны с высоким %)` );
console.log( '\n   ⚠ это советник: сверь side/heat глазами, числа лишь подсказывают ГДЕ смотреть.' );
