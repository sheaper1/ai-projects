// Создаёт / обновляет страницу «Immobilie verkaufen» через WP REST API.
// Запуск: node scripts/import-immobilie-verkaufen.mjs
//
// Источник дизайна: Figma p1HKLfoMcOwtVUD5rI9V3P, страница UI Design,
// фрейм immobilie-verkaufen 536:1972 (desktop) / 543:4571 (mobile).
//
// Сборка идёт этапами. Этап 1 (этот файл сейчас): Hero + trust-bar + problem-cards.
// Этапы 2–3 добавляются ниже в pageContent по мере готовности секций.

import { readFileSync } from 'node:fs';
import { resolve, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = resolve( dirname( fileURLToPath( import.meta.url ) ), '..' );

const env = {};
for ( const line of readFileSync( resolve( root, '.env' ), 'utf8' ).split( /\r?\n/ ) ) {
	const m = line.match( /^([A-Z_]+)=(.*)$/ );
	if ( m ) env[ m[ 1 ] ] = m[ 2 ];
}

const BASE = env.WP_URL.replace( /\/$/, '' );
const AUTH = 'Basic ' + Buffer.from( `${ env.WP_USER }:${ env.WP_APP_PASSWORD }` ).toString( 'base64' );
const MIME = { svg: 'image/svg+xml', webp: 'image/webp', jpg: 'image/jpeg', png: 'image/png' };

const api = async ( path, opts = {} ) => {
	const res = await fetch( `${ BASE }${ path }`, {
		...opts,
		headers: { Authorization: AUTH, 'Content-Type': 'application/json', ...( opts.headers || {} ) },
	} );
	const text = await res.text();
	let body; try { body = JSON.parse( text ); } catch { body = text; }
	return { status: res.status, ok: res.ok, body };
};

const ensureMediaLocal = async ( slug, relPath, ext ) => {
	const found = await api( `/wp-json/wp/v2/media?slug=${ encodeURIComponent( slug ) }&per_page=1` );
	if ( Array.isArray( found.body ) && found.body[ 0 ] ) {
		console.log( `  ✓ ${ slug } (id=${ found.body[ 0 ].id })` );
		return found.body[ 0 ];
	}
	const buf = readFileSync( resolve( root, relPath ) );
	const res = await fetch( `${ BASE }/wp-json/wp/v2/media`, {
		method: 'POST',
		headers: {
			Authorization: AUTH,
			'Content-Type': MIME[ ext ] || 'application/octet-stream',
			'Content-Disposition': `attachment; filename="${ slug }.${ ext }"`,
		},
		body: buf,
	} );
	const body = await res.json();
	if ( ! res.ok ) throw new Error( `Ошибка загрузки ${ slug }: ${ body.message || res.status }` );
	console.log( `  ↑ загружен ${ slug } (id=${ body.id })` );
	return body;
};

const findMedia = async ( slug ) => {
	const r = await api( `/wp-json/wp/v2/media?slug=${ encodeURIComponent( slug ) }&per_page=1` );
	const item = Array.isArray( r.body ) ? r.body[ 0 ] : null;
	if ( item ) console.log( `  ✓ ${ slug } (id=${ item.id })` );
	else console.warn( `  ⚠  не найдено: ${ slug }` );
	return item;
};

const u  = m => m ? m.source_url : '';
const id = m => m ? m.id : 0;

// ---------------------------------------------------------------------------
// Шаг 1 — медиа
// ---------------------------------------------------------------------------
console.log( '\n📦 Медиа...' );
const M = 'projects/rosenberger/media';
const [ heroBg, badge, icPrice, icCallback, icEffort, icTrust ] = await Promise.all( [
	ensureMediaLocal( 'rosenberger-iv-hero',          `${ M }/iv-hero.webp`,             'webp' ),
	findMedia( 'rosenberger-google-rating' ),
	ensureMediaLocal( 'rosenberger-iv-pain-price',    `${ M }/icons/iv-pain-price.svg`,    'svg' ),
	ensureMediaLocal( 'rosenberger-iv-pain-callback', `${ M }/icons/iv-pain-callback.svg`, 'svg' ),
	ensureMediaLocal( 'rosenberger-iv-pain-effort',   `${ M }/icons/iv-pain-effort.svg`,   'svg' ),
	ensureMediaLocal( 'rosenberger-iv-pain-trust',    `${ M }/icons/iv-pain-trust.svg`,    'svg' ),
] );

// ---------------------------------------------------------------------------
// Шаг 2 — блочная разметка
// ---------------------------------------------------------------------------
console.log( '\n📄 Собираю разметку...' );

const pageContent = [

	`<!-- wp:library/page-hero ${ JSON.stringify( {
		headingStart:  'Immobilie verkaufen ',
		headingItalic: 'in Vorarlberg',
		headingEnd:    '',
		subtitle:      'Sie verkaufen ohne Druck und ohne Aufwand. \nAlles dazwischen übernehme ich, Ihr Termin bei mir dauert rund eine Stunde.',
		buttonText:    'Jetzt verkaufen',
		buttonUrl:     '/kontakt/',
		disclaimer:    '',
		imageId:       id( heroBg ),
		imageUrl:      u( heroBg ),
	} ) } /-->`,

	`<!-- wp:library/trust-bar ${ JSON.stringify( {
		badgeId:  id( badge ),
		badgeUrl: u( badge ),
		rating:   '4.5',
		items:    [ 'Persönlich von mir betreut', 'Schnelle Rückmeldung', 'Vor Ort in ganz Vorarlberg' ],
	} ) } /-->`,

	`<!-- wp:library/problem-cards ${ JSON.stringify( {
		heading:       'Was einen<br>Verkauf ',
		headingItalic: 'zäh macht',
		intro:         'Ein Verkauf kommt selten zum bequemsten Zeitpunkt. Erbe, Scheidung, Umzug im Alter oder ein neuer Job, der Anlass entscheidet selten der Eigentümer selbst. Und dann fängt der eigentliche Ärger oft erst an.',
		items: [
			{
				title:   'Der Wunschpreis,<br>der nur den Auftrag bringt',
				text:    'Ein Makler verspricht eine hohe Zahl, sichert sich den Auftrag, und Monate später wird der Preis Stück für Stück gesenkt. Am Markt gilt das Objekt dann schnell als verbrannt.',
				iconId:  id( icPrice ),
				iconUrl: u( icPrice ),
			},
			{
				title:   'Der Makler,<br>der abtaucht',
				text:    'Nach der Unterschrift kommen keine Rückmeldungen mehr, und Sie erfahren wochenlang nichts über den Stand.',
				iconId:  id( icCallback ),
				iconUrl: u( icCallback ),
			},
			{
				title:   'Der Aufwand,<br>den keiner sieht',
				text:    'Ein Privatverkauf bindet schnell hundert Stunden und mehr, mit Besichtigungen, Unterlagen, Behördengängen und Verhandlungen.',
				iconId:  id( icEffort ),
				iconUrl: u( icEffort ),
			},
			{
				title:   'Das Gefühl, über den Tisch gezogen zu werden',
				text:    'Unklare Provision, Druck zur schnellen Entscheidung und die Sorge, den Markt nicht zu überblicken.',
				iconId:  id( icTrust ),
				iconUrl: u( icTrust ),
			},
		],
	} ) } /-->`,

].join( '\n\n' );

// ---------------------------------------------------------------------------
// Шаг 3 — создать / обновить страницу
// ---------------------------------------------------------------------------
console.log( '\n📝 Создаю / обновляю страницу «Immobilie verkaufen»...' );

const pages = await api( '/wp-json/wp/v2/pages?slug=immobilie-verkaufen&status=any&per_page=1' );
let pageId;

if ( Array.isArray( pages.body ) && pages.body[ 0 ] ) {
	pageId = pages.body[ 0 ].id;
	const r = await api( `/wp-json/wp/v2/pages/${ pageId }`, {
		method: 'POST',
		body: JSON.stringify( { content: pageContent, status: 'publish' } ),
	} );
	if ( ! r.ok ) throw new Error( `Ошибка обновления: ${ JSON.stringify( r.body ).slice( 0, 300 ) }` );
	console.log( `  ✓ Обновлена id=${ pageId }` );
} else {
	const r = await api( '/wp-json/wp/v2/pages', {
		method: 'POST',
		body: JSON.stringify( { title: 'Immobilie verkaufen', slug: 'immobilie-verkaufen', content: pageContent, status: 'publish' } ),
	} );
	if ( ! r.ok ) throw new Error( `Ошибка создания: ${ JSON.stringify( r.body ).slice( 0, 300 ) }` );
	pageId = r.body.id;
	console.log( `  ✓ Создана id=${ pageId }` );
}

console.log( `\n✅ Этап 1 готов! ${ BASE }/immobilie-verkaufen/` );
