// Детектор горизонтального оверфлоу для моего QA: ловит класс дефектов «обрезано
// по ширине / горизонтальный скролл» (клиентский QA: «footer is cutted»,
// «not optimised header»), который числовой visual-qa пропускал.
// На каждой странице × вьюпорт меряет documentElement.scrollWidth - clientWidth,
// и если есть оверфлоу — находит САМЫЙ широкий выходящий за вьюпорт элемент.
// Запуск: node scripts/qa-overflow.mjs            (все страницы)
//         node scripts/qa-overflow.mjs <url...>
import puppeteer from 'puppeteer';

const BASE = 'https://rosenberger.digirelation.dev';
const PAGES = [
	'/', '/ueber-mich/', '/tippgeber/', '/immobilie-verkaufen/',
	'/immobilie-vermieten/', '/immobilienbewertung/', '/references/',
	'/kontakt/', '/blog/', '/region/feldkirch/',
];
const VIEWPORTS = [ 390, 768, 834, 1024, 1280, 1440 ];

const urls = process.argv.slice( 2 ).filter( ( a ) => /^https?:|^\//.test( a ) )
	.map( ( u ) => ( u.startsWith( 'http' ) ? u : BASE + u ) );
const targets = urls.length ? urls : PAGES.map( ( p ) => BASE + p );

const browser = await puppeteer.launch( { headless: 'new' } );
const page = await browser.newPage();
let total = 0;

for ( const url of targets ) {
	const hits = [];
	for ( const w of VIEWPORTS ) {
		await page.setViewport( { width: w, height: 900 } );
		await page.goto( url + '?v=' + w, { waitUntil: 'networkidle2', timeout: 45000 } );
		const r = await page.evaluate( ( vw ) => {
			const de = document.documentElement;
			const ovf = de.scrollWidth - de.clientWidth;
			if ( ovf <= 1 ) return { ovf: 0 };
			// ищем самый выходящий за правый край элемент
			let worst = null, worstR = vw;
			document.querySelectorAll( '*' ).forEach( ( el ) => {
				const b = el.getBoundingClientRect();
				if ( b.width > 0 && b.right > worstR + 1 ) {
					worstR = b.right;
					worst = ( el.className && typeof el.className === 'string' )
						? '.' + el.className.split( ' ' )[ 0 ] : el.tagName.toLowerCase();
				}
			} );
			return { ovf, worst, worstR: Math.round( worstR ) };
		}, w );
		if ( r.ovf > 1 ) hits.push( `${ w }px: +${ r.ovf }px (${ r.worst } → ${ r.worstR })` );
	}
	if ( hits.length ) {
		console.error( `● ${ url }` );
		hits.forEach( ( h ) => console.error( `   ${ h }` ) );
		total += hits.length;
	} else {
		console.error( `✓ ${ url } — оверфлоу нет` );
	}
}
await browser.close();
console.error( `\nИТОГО оверфлоу: ${ total }` );
