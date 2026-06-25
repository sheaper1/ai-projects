// Скриншот страницы/секции staging для визуальной сверки с Figma.
// Кодирует приём «снять → сравнить с макетом → починить» одной командой,
// чтобы не писать puppeteer вручную каждый раз (и не упираться в лимиты картинок).
//
// Примеры:
//   npm run shot -- https://site/seite                       # десктоп 1440, вьюпорт
//   npm run shot -- https://site/seite --sel ".wp-block-x"   # только секция
//   npm run shot -- https://site/seite --mobile --full       # моб. 375, вся страница (нарежет)
//   npm run shot -- https://site/seite --w 1280 --name hero  # своя ширина + имя файла
//
// Файлы → .visual/ (gitignore). Широкие ужимаются до 1300px, высокие (вся
// страница) нарезаются на части ~1800px — иначе просмотрщик/лимит API отклонит.

import { mkdirSync, existsSync } from 'node:fs';
import { resolve, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';
import puppeteer from 'puppeteer';
import sharp from 'sharp';

const root = resolve( dirname( fileURLToPath( import.meta.url ) ), '..' );
const args = process.argv.slice( 2 );
const url = args.find( ( a ) => /^https?:\/\//.test( a ) );
if ( ! url ) {
	console.error( 'Укажи URL. Пример: npm run shot -- https://site/seite --sel ".wp-block-x"' );
	process.exit( 1 );
}
const flag = ( name ) => args.includes( `--${ name }` );
const val = ( name, def ) => {
	const i = args.indexOf( `--${ name }` );
	return i >= 0 && args[ i + 1 ] ? args[ i + 1 ] : def;
};

const sel = val( 'sel', '' );
const mobile = flag( 'mobile' );
const full = flag( 'full' );
const width = mobile ? 375 : parseInt( val( 'w', '1440' ), 10 );
const name = val( 'name', 'shot' );
const MAXW = 1300;
const CHUNK = 1800;

const outDir = resolve( root, '.visual' );
if ( ! existsSync( outDir ) ) mkdirSync( outDir, { recursive: true } );

const browser = await puppeteer.launch( { headless: 'new', args: [ '--no-sandbox' ] } );
const page = await browser.newPage();
await page.setViewport( { width, height: mobile ? 800 : 1000, deviceScaleFactor: mobile ? 2 : 1.5, isMobile: mobile, hasTouch: mobile } );
await page.goto( url, { waitUntil: 'networkidle2', timeout: 60000 } );

// Прокрутить всю страницу до низа и обратно — иначе scroll-reveal (opacity:0 до
// IntersectionObserver) и lazy-фоны не подгрузятся, и секции ниже первого экрана
// снимутся ПУСТЫМИ. Без этого Phase-2 снимок региона/карточек был серым.
await page.evaluate( async () => {
	const sleep = ( ms ) => new Promise( ( r ) => setTimeout( r, ms ) );
	const step = Math.max( 400, window.innerHeight * 0.8 );
	for ( let y = 0; y < document.body.scrollHeight; y += step ) { window.scrollTo( 0, y ); await sleep( 120 ); }
	window.scrollTo( 0, 0 );
	await sleep( 200 );
} );
await new Promise( ( r ) => setTimeout( r, 900 ) );

const raw = resolve( outDir, `.${ name }-raw.png` );

if ( sel ) {
	const el = await page.$( sel );
	if ( ! el ) { console.error( `Селектор не найден: ${ sel }` ); await browser.close(); process.exit( 1 ); }
	await el.screenshot( { path: raw } );
} else {
	await page.screenshot( { path: raw, fullPage: full } );
}
await browser.close();

const meta = await sharp( raw ).metadata();
const written = [];

if ( meta.height > CHUNK * 1.3 ) {
	// Высокий (вся страница) → нарезаем по вертикали.
	const n = Math.ceil( meta.height / CHUNK );
	for ( let i = 0; i < n; i++ ) {
		const top = i * CHUNK;
		const h = Math.min( CHUNK, meta.height - top );
		const out = resolve( outDir, `${ name }-${ i }.png` );
		await sharp( raw ).extract( { left: 0, top, width: meta.width, height: h } )
			.resize( { width: Math.min( meta.width, MAXW ) } ).toFile( out );
		written.push( out );
	}
} else {
	const out = resolve( outDir, `${ name }.png` );
	let img = sharp( raw );
	if ( meta.width > MAXW ) img = img.resize( { width: MAXW } );
	await img.toFile( out );
	written.push( out );
}

console.log( `📸 ${ meta.width }×${ meta.height } → ${ written.length } файл(ов):` );
written.forEach( ( w ) => console.log( '  ' + w ) );
console.log( 'Открой их в контекст (Read) и сверь с Figma-нодой.' );
