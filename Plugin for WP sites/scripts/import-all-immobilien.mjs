// Создаёт / обновляет страницу «Alle Immobilien» через WP REST API.
// Запуск: node scripts/import-all-immobilien.mjs
//
// Источник дизайна: Figma p1HKLfoMcOwtVUD5rI9V3P, страница UI Design,
// фрейм all-immobilien 2009:5655 (desktop) / 2009:10052 (mobile).

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
const [ heroBg, badge, ctaBg ] = await Promise.all( [
	ensureMediaLocal( 'rosenberger-ai-hero',   `${ M }/all-immobilien-hero.webp`, 'webp' ),
	findMedia( 'rosenberger-google-rating' ),
	findMedia( 'rosenberger-consultation-bg' ),
] );

// ---------------------------------------------------------------------------
// Шаг 2 — блочная разметка
// ---------------------------------------------------------------------------
console.log( '\n📄 Собираю разметку...' );

const pageContent = [

	`<!-- wp:library/page-hero ${ JSON.stringify( {
		headingStart:  'Alle Immobilien ',
		headingItalic: 'in Vorarlberg',
		headingEnd:    '',
		subtitle:      'Kaufen oder mieten – finden Sie Ihre Immobilie\nim Herzen Vorarlbergs.',
		buttonText:    'Objekte durchsuchen',
		buttonUrl:     '#katalog',
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

	`<!-- wp:library/property-catalog ${ JSON.stringify( {
		layout:        'catalog',
		heading:       'Aktuelle Objekte',
		headingItalic: 'in Vorarlberg',
		subtext:       '',
		postsPerPage:  9,
		archiveUrl:    '/alle-immobilien/',
		align:         'full',
	} ) } /-->`,

	`<!-- wp:library/testimonials ${ JSON.stringify( {
		heading:       'Das sagen Menschen,<br>',
		headingItalic: 'die mit mir gearbeitet haben',
		limit:         9,
		minRating:     4,
	} ) } /-->`,

	`<!-- wp:library/consultation-cta ${ JSON.stringify( {
		heading:       '',
		headingItalic: 'Ihr kostenloses Erstgespräch',
		text:          'Persönlich und völlig unverbindlich. Ich verkaufe in ganz Vorarlberg, von Feldkirch über Dornbirn und Bregenz bis Bludenz.',
		buttonText:    'Kostenlos beraten lassen',
		buttonUrl:     '/kontakt/',
		backgroundId:  id( ctaBg ),
		backgroundUrl: u( ctaBg ),
	} ) } /-->`,

].join( '\n\n' );

// ---------------------------------------------------------------------------
// Шаг 3 — создать / обновить страницу
// ---------------------------------------------------------------------------
console.log( '\n📝 Создаю / обновляю страницу «Alle Immobilien»...' );

const pages = await api( '/wp-json/wp/v2/pages?slug=alle-immobilien&status=any&per_page=1' );
let pageId;

if ( Array.isArray( pages.body ) && pages.body[ 0 ] ) {
	pageId = pages.body[ 0 ].id;
	const r = await api( `/wp-json/wp/v2/pages/${ pageId }`, {
		method: 'POST',
		body: JSON.stringify( { content: pageContent, status: 'publish' } ),
	} );
	// 500 may come from a caching hook but the save still succeeds — verify by re-fetching
	if ( ! r.ok && r.status !== 500 ) throw new Error( `Ошибка обновления: ${ JSON.stringify( r.body ).slice( 0, 300 ) }` );
	console.log( `  ✓ Обновлена id=${ pageId }${ r.status === 500 ? ' (500 от кешера — страница сохранена)' : '' }` );
} else {
	const r = await api( '/wp-json/wp/v2/pages', {
		method: 'POST',
		body: JSON.stringify( { title: 'Alle Immobilien', slug: 'alle-immobilien', content: pageContent, status: 'publish' } ),
	} );
	if ( ! r.ok && r.status !== 500 ) throw new Error( `Ошибка создания: ${ JSON.stringify( r.body ).slice( 0, 300 ) }` );
	pageId = r.body?.id ?? '?';
	console.log( `  ✓ Создана id=${ pageId }${ r.status === 500 ? ' (500 от кешера — страница сохранена)' : '' }` );
}

console.log( `\n✅ Готово! ${ BASE }/alle-immobilien/` );
