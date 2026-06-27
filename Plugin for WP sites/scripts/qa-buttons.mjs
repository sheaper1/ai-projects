// Детектор кнопок для моего QA: на каждой странице ловит то, что поймал клиент,
// но мой числовой visual-qa пропускал:
//   S6 — кнопка без href (или href="#") → «no connection / links missing»
//   S7 — у кнопки нет hover-эффекта (симулируем наведение, сравниваем стили)
//   S5 — высота кнопки на mobile вне допуска
// Запуск: node scripts/qa-buttons.mjs            (все страницы)
//         node scripts/qa-buttons.mjs <url...>   (свои url)
// Выход: JSON-отчёт в stdout + краткая сводка дефектов в stderr.

import puppeteer from 'puppeteer';

const BASE = 'https://rosenberger.digirelation.dev';
const PAGES = [
	'/', '/ueber-mich/', '/tippgeber/', '/immobilie-verkaufen/',
	'/immobilie-vermieten/', '/immobilienbewertung/', '/references/',
	'/kontakt/', '/blog/', '/region/feldkirch/',
];

// Что считаем кнопкой (CTA), а не любой ссылкой.
const BTN_SEL = [
	'.wp-block-button__link',
	'.wp-element-button',
	'a[class*="__button"]',
	'a[class*="__cta"]',
	'button[class*="__button"]',
].join( ',' );

// Допуск высоты кнопки на mobile (px). Дизайн Figma: 56px (десктоп). На mobile
// клиент жалуется на «размер кнопок» — флагуем явные выбросы.
const MOBILE_MIN = 40;
const MOBILE_MAX = 64;

const urls = ( process.argv.slice( 2 ).filter( ( a ) => /^https?:|^\//.test( a ) ) )
	.map( ( u ) => ( u.startsWith( 'http' ) ? u : BASE + u ) );
const targets = urls.length ? urls : PAGES.map( ( p ) => BASE + p );

const snap = ( el ) => {
	const cs = getComputedStyle( el );
	return {
		bg: cs.backgroundColor, color: cs.color, opacity: cs.opacity,
		transform: cs.transform, boxShadow: cs.boxShadow,
		borderColor: cs.borderColor, textDecoration: cs.textDecorationLine,
	};
};

const browser = await puppeteer.launch( { headless: 'new' } );
const report = [];

for ( const url of targets ) {
	const page = await browser.newPage();
	const entry = { url, buttons: [], errors: [] };
	try {
		// --- Desktop проход: href + hover ---
		await page.setViewport( { width: 1440, height: 900 } );
		await page.goto( url, { waitUntil: 'networkidle2', timeout: 45000 } );

		const btns = await page.evaluate( ( sel ) => {
			const seen = [];
			document.querySelectorAll( sel ).forEach( ( el, i ) => {
				el.setAttribute( 'data-qa-btn', String( i ) );
				const rect = el.getBoundingClientRect();
				seen.push( {
					idx: i,
					text: ( el.textContent || '' ).trim().slice( 0, 40 ),
					tag: el.tagName.toLowerCase(),
					href: el.getAttribute( 'href' ),
					cls: el.className,
					visible: rect.width > 0 && rect.height > 0,
					hD: Math.round( rect.height ),
				} );
			} );
			return seen;
		}, BTN_SEL );

		for ( const b of btns ) {
			if ( ! b.visible ) continue;
			const elSel = `[data-qa-btn="${ b.idx }"]`;
			// hover-эффект: снимок до/после наведения
			const before = await page.$eval( elSel, snap );
			await page.hover( elSel ).catch( () => {} );
			await new Promise( ( r ) => setTimeout( r, 350 ) );
			const after = await page.$eval( elSel, snap );
			const hoverChanged = JSON.stringify( before ) !== JSON.stringify( after );
			// сбрасываем hover
			await page.mouse.move( 0, 0 );

			const isLink = b.tag === 'a';
			const noHref = isLink && ( ! b.href || b.href === '#' || b.href.trim() === '' );

			b.S6_noHref = noHref;
			b.S7_noHover = ! hoverChanged;
			report.push( null ); // placeholder, real push below
			entry.buttons.push( b );
		}

		// --- Mobile проход: высота кнопок ---
		await page.setViewport( { width: 390, height: 844 } );
		await page.reload( { waitUntil: 'networkidle2', timeout: 45000 } );
		const mob = await page.evaluate( ( sel ) => {
			const out = {};
			document.querySelectorAll( sel ).forEach( ( el, i ) => {
				const r = el.getBoundingClientRect();
				if ( r.width > 0 && r.height > 0 ) out[ i ] = Math.round( r.height );
			} );
			return out;
		}, BTN_SEL );
		for ( const b of entry.buttons ) {
			b.hM = mob[ b.idx ] ?? null;
			b.S5_badMobile = b.hM != null && ( b.hM < MOBILE_MIN || b.hM > MOBILE_MAX );
		}
	} catch ( e ) {
		entry.errors.push( String( e.message || e ) );
	}
	await page.close();
	report.push( entry );
}
await browser.close();

const clean = report.filter( Boolean );
console.log( JSON.stringify( clean, null, 2 ) );

// Сводка дефектов в stderr
let total = 0;
for ( const e of clean ) {
	const noHref = e.buttons.filter( ( b ) => b.S6_noHref );
	const noHover = e.buttons.filter( ( b ) => b.S7_noHover );
	const badMob = e.buttons.filter( ( b ) => b.S5_badMobile );
	if ( e.errors.length ) console.error( `\n⚠ ${ e.url }: ${ e.errors.join( '; ' ) }` );
	if ( ! noHref.length && ! noHover.length && ! badMob.length ) {
		console.error( `✓ ${ e.url } — кнопок ${ e.buttons.length }, дефектов 0` );
		continue;
	}
	console.error( `\n● ${ e.url } (кнопок ${ e.buttons.length }):` );
	noHref.forEach( ( b ) => console.error( `   S6 нет href: "${ b.text }" [${ b.cls }]` ) );
	noHover.forEach( ( b ) => console.error( `   S7 нет hover: "${ b.text }" [${ b.cls }]` ) );
	badMob.forEach( ( b ) => console.error( `   S5 mobile h=${ b.hM }px: "${ b.text }" [${ b.cls }]` ) );
	total += noHref.length + noHover.length + badMob.length;
}
console.error( `\nИТОГО дефектов кнопок: ${ total }` );
