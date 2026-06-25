// QA-экстрактор: снимает с живого DOM точные числа по каждому видимому
// текстовому элементу + картинкам, чтобы сверять с дизайном ПО ЧИСЛАМ, а не
// на глаз. Вывод — JSON (stdout) + краткий human-репорт (stderr).
//
// Запуск: node scripts/qa-extract.mjs <url> [--width 1440] [--json out.json]
//
// Почему так: дефекты вроде «левый отступ в vw не тот», «колонка шире»,
// «описание жирнее», «нет <br>» НЕВИДИМЫ на ужатом скриншоте — их видно только
// в числах. DOM отдаёт их точно и даром через getComputedStyle/getBoundingRect.

import puppeteer from 'puppeteer';
import { writeFileSync } from 'node:fs';

const args = process.argv.slice( 2 );
const url = args.find( ( a ) => /^https?:\/\//.test( a ) );
const val = ( n, d ) => { const i = args.indexOf( `--${ n }` ); return i >= 0 && args[ i + 1 ] ? args[ i + 1 ] : d; };
if ( ! url ) { console.error( 'Укажи URL' ); process.exit( 1 ); }
const width = parseInt( val( 'width', '1440' ), 10 );
const jsonOut = val( 'json', null );
const isMobile = width <= 480;

const browser = await puppeteer.launch( { headless: 'new', args: [ '--no-sandbox' ] } );
const page = await browser.newPage();
await page.setViewport( { width, height: 1000, deviceScaleFactor: 1, isMobile, hasTouch: isMobile } );
await page.goto( url, { waitUntil: 'networkidle2', timeout: 60000 } );
// прокрутка — догрузить lazy + триггернуть scroll-reveal, иначе элементы пустые
await page.evaluate( async () => {
	await new Promise( ( res ) => {
		let y = 0; const t = setInterval( () => {
			window.scrollBy( 0, 500 ); y += 500;
			if ( y >= document.body.scrollHeight ) { clearInterval( t ); window.scrollTo( 0, 0 ); res(); }
		}, 80 );
	} );
} );
await new Promise( ( r ) => setTimeout( r, 700 ) );

const data = await page.evaluate( ( vw ) => {
	const norm = ( s ) => ( s || '' ).replace( /\s+/g, ' ' ).trim();
	const round = ( n ) => Math.round( n * 10 ) / 10;

	const texts = [];
	const seen = new Set();
	document.querySelectorAll( 'h1,h2,h3,h4,h5,h6,p,span,a,li,blockquote,figcaption,button,label' ).forEach( ( el ) => {
		const r = el.getBoundingClientRect();
		if ( r.width === 0 || r.height === 0 ) return;          // невидимое
		// Полный textContent (а не только прямой текст): иначе заголовок с
		// вложенным <span>/<br> обрезается и не джойнится с Figma.
		const text = norm( el.textContent );
		if ( ! text || text.length < 2 ) return;
		const key = el.tagName + '|' + text + '|' + Math.round( r.top );
		if ( seen.has( key ) ) return; seen.add( key );
		const cs = getComputedStyle( el );
		texts.push( {
			text: text.slice( 0, 80 ),
			tag: el.tagName.toLowerCase(),
			cls: ( el.className && typeof el.className === 'string' ? el.className : '' ).slice( 0, 60 ),
			x: round( r.left ), y: round( r.top + window.scrollY ),
			w: round( r.width ), h: round( r.height ),
			leftPct: round( ( r.left / vw ) * 100 ),       // отступ слева в % ширины (vw-инвариант)
			rightPct: round( ( ( vw - r.right ) / vw ) * 100 ),
			fontSize: parseFloat( cs.fontSize ),
			fontWeight: cs.fontWeight,
			lineHeight: cs.lineHeight,
			letterSpacing: cs.letterSpacing,
			fontFamily: cs.fontFamily.split( ',' )[ 0 ].replace( /["']/g, '' ),
			color: cs.color,
			textAlign: cs.textAlign,
			padL: parseFloat( cs.paddingLeft ), padT: parseFloat( cs.paddingTop ),
			brCount: el.querySelectorAll( ':scope > br' ).length,
			lines: Math.max( 1, Math.round( r.height / ( parseFloat( cs.lineHeight ) || parseFloat( cs.fontSize ) * 1.2 ) ) ),
		} );
	} );

	const images = [];
	document.querySelectorAll( 'img' ).forEach( ( el ) => {
		const r = el.getBoundingClientRect();
		if ( r.width === 0 ) return;
		const cs = getComputedStyle( el );
		images.push( {
			src: ( el.currentSrc || el.src || '' ).split( '/' ).pop().slice( 0, 50 ),
			alt: norm( el.alt ).slice( 0, 40 ),
			x: round( r.left ), w: round( r.width ), h: round( r.height ),
			natW: el.naturalWidth, natH: el.naturalHeight,
			widthPct: round( ( r.width / vw ) * 100 ),
			objectFit: cs.objectFit,
			fullBleed: r.width >= vw * 0.98,          // растянута на весь экран?
		} );
	} );

	// Контролы каруселей/слайдеров: ловим скрытую пагинацию/стрелки (частый дефект
	// «отзывы не листаются»). Числа/скриншот этого не дают — нужен DOM.
	const controls = [];
	document.querySelectorAll( '[class*="dots"],[class*="pagination"],[class*="arrow"],[class*="__nav"],[class*="prev"],[class*="next"]' ).forEach( ( el ) => {
		const cs = getComputedStyle( el ); const r = el.getBoundingClientRect();
		controls.push( {
			cls: ( typeof el.className === 'string' ? el.className : '' ).slice( 0, 50 ),
			display: cs.display,
			hidden: cs.display === 'none' || cs.visibility === 'hidden' || r.width === 0,
			kids: el.children.length,
		} );
	} );

	return { url: location.href, vw, docHeight: document.body.scrollHeight, texts, images, controls };
}, width );

await browser.close();

if ( jsonOut ) { writeFileSync( jsonOut, JSON.stringify( data, null, 2 ) ); }
process.stdout.write( JSON.stringify( data ) );

// --- краткий человекочитаемый срез в stderr ---
const e = console.error;
e( `\n=== QA-extract: ${ data.url }  (vw=${ data.vw }, h=${ data.docHeight }) ===` );
e( `\nЗАГОЛОВКИ (tag h*):` );
data.texts.filter( ( t ) => /^h[1-6]$/.test( t.tag ) ).forEach( ( t ) =>
	e( `  ${ t.tag } "${ t.text }" | size ${ t.fontSize } weight ${ t.fontWeight } | <br>×${ t.brCount } lines ${ t.lines } | leftPct ${ t.leftPct }% x ${ t.x }` ) );
e( `\nЦИТАТЫ / blockquote:` );
data.texts.filter( ( t ) => t.tag === 'blockquote' || t.cls.includes( 'quote' ) ).forEach( ( t ) =>
	e( `  "${ t.text }" | <br>×${ t.brCount } lines ${ t.lines } | w ${ t.w } leftPct ${ t.leftPct }%` ) );
e( `\nКАРТИНКИ:` );
data.images.forEach( ( im ) =>
	e( `  ${ im.src } | ${ im.w }×${ im.h } widthPct ${ im.widthPct }% fit ${ im.objectFit } ${ im.fullBleed ? 'FULL-BLEED' : 'box' }` ) );
e( `\nКОНТРОЛЫ каруселей (скрытые = подозрение на «не листается»):` );
( data.controls || [] ).forEach( ( c ) => e( `  ${ c.hidden ? '⚠ СКРЫТ' : 'видим' } [${ c.cls }] display:${ c.display } kids:${ c.kids }` ) );
e( `\nВес шрифта по абзацам (p) — ищем где жирнее ожидаемого:` );
data.texts.filter( ( t ) => t.tag === 'p' ).slice( 0, 20 ).forEach( ( t ) =>
	e( `  weight ${ t.fontWeight } size ${ t.fontSize } | "${ t.text.slice( 0, 50 ) }"` ) );
