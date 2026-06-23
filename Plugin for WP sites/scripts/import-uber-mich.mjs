// Создаёт / обновляет страницу «Über mich» через WP REST API.
// Запуск: node scripts/import-uber-mich.mjs
// Изображения страницы нужно предварительно загрузить в медиатеку под slug'ами:
//   rosenberger-bio-portrait   — портрет для hero (правая колонка)
//   rosenberger-founder-photo  — фото для секции «Vertrauen» (правая колонка)
//   rosenberger-quote-cover    — фото для секции-цитаты на весь экран
// Для consultation-cta переиспользуется «rosenberger-consultation-bg» (уже есть).
// Иконки ценностей переиспользуются: rosenberger-card-icon-house / -evaluation / -valet.

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

const api = async ( path, opts = {} ) => {
	const res = await fetch( `${ BASE }${ path }`, {
		...opts,
		headers: { Authorization: AUTH, 'Content-Type': 'application/json', ...( opts.headers || {} ) },
	} );
	const text = await res.text();
	let body; try { body = JSON.parse( text ); } catch { body = text; }
	return { status: res.status, ok: res.ok, body };
};

// Ищет медиа по slug, возвращает { id, source_url } или null.
const findMedia = async ( slug ) => {
	const r = await api( `/wp-json/wp/v2/media?slug=${ encodeURIComponent( slug ) }&per_page=1` );
	const item = Array.isArray( r.body ) ? r.body[ 0 ] : null;
	if ( item ) {
		console.log( `  ✓ ${ slug } (id=${ item.id })` );
		return item;
	}
	console.warn( `  ⚠  медиа не найдено: ${ slug } — блок будет без изображения` );
	return null;
};

// Загружает медиа из удалённого URL если ещё нет по slug.
const ensureMediaRemote = async ( slug, remoteUrl, ext = 'jpg' ) => {
	const found = await api( `/wp-json/wp/v2/media?slug=${ encodeURIComponent( slug ) }&per_page=1` );
	const existing = Array.isArray( found.body ) ? found.body[ 0 ] : null;
	if ( existing ) {
		console.log( `  ✓ ${ slug } (id=${ existing.id })` );
		return existing;
	}
	console.log( `  ↑ загружаю ${ slug } из Figma CDN...` );
	const buf = await fetch( remoteUrl ).then( r => r.arrayBuffer() ).then( b => Buffer.from( b ) );
	const MIME = { jpg: 'image/jpeg', png: 'image/png', webp: 'image/webp' };
	const res = await fetch( `${ BASE }/wp-json/wp/v2/media`, {
		method: 'POST',
		headers: {
			Authorization: AUTH,
			'Content-Type': MIME[ ext ] || 'image/jpeg',
			'Content-Disposition': `attachment; filename="${ slug }.${ ext }"`,
		},
		body: buf,
	} );
	const body = await res.json();
	if ( ! res.ok ) throw new Error( `Ошибка загрузки ${ slug }: ${ body.message || res.status }` );
	console.log( `  ✓ ${ slug } загружен (id=${ body.id })` );
	return body;
};

// ---------------------------------------------------------------------------
// Шаг 1 — медиа
// ---------------------------------------------------------------------------
console.log( '\n📦 Медиа...' );
// Figma CDN URLs (действительны 7 дней с момента get_design_context)
const FIGMA_BIO_PORTRAIT   = 'https://www.figma.com/api/mcp/asset/4db22e32-2c0a-40cf-84dd-a904e15c6e8e';
const FIGMA_QUOTE_COVER    = 'https://www.figma.com/api/mcp/asset/ac5e6cac-485b-433f-8a34-5cec385657c0';
const FIGMA_FOUNDER_PHOTO  = 'https://www.figma.com/api/mcp/asset/952ad1e2-2a49-4f29-87c8-2d2ca910be6c';

const [
	bioPortrait,
	founderPhoto,
	quoteCover,
	ctaBg,
	badge,
	iconHouse,
	iconEvaluation,
	iconValet,
] = await Promise.all( [
	ensureMediaRemote( 'rosenberger-bio-portrait',  FIGMA_BIO_PORTRAIT,  'png' ),
	ensureMediaRemote( 'rosenberger-founder-photo', FIGMA_FOUNDER_PHOTO, 'png' ),
	ensureMediaRemote( 'rosenberger-quote-cover',   FIGMA_QUOTE_COVER,   'png' ),
	findMedia( 'rosenberger-consultation-bg' ),
	findMedia( 'rosenberger-google-rating' ),
	findMedia( 'rosenberger-card-icon-house' ),
	findMedia( 'rosenberger-card-icon-evaluation' ),
	findMedia( 'rosenberger-card-icon-valet' ),
] );

const u  = ( m ) => m ? m.source_url : '';
const id = ( m ) => m ? m.id : 0;

// ---------------------------------------------------------------------------
// Шаг 2 — Gutenberg-разметка страницы
// ---------------------------------------------------------------------------
console.log( '\n📄 Собираю разметку...' );

const pageContent = [

	`<!-- wp:library/bio-hero {` + JSON.stringify( {
		label:      'Über mich',
		name:       'Alex Rosenberger',
		jobTitle:   'Immobilienmakler in Vorarlberg',
		bio:        'Ich kenne Vorarlbergs Immobilienmarkt aus zwei Perspektiven: als Makler, der seit 2021 hier tätig ist, und als jemand, der selbst auf der Käuferseite gesessen hat und erlebt hat, wie es nicht sein sollte.',
		nameCredit: 'Alexander\nRosenberger',
		imageId:    id( bioPortrait ),
		imageUrl:   u( bioPortrait ),
	} ).replace( /^{|}$/g, '' ) + `} /-->`,

	`<!-- wp:library/trust-bar {` + JSON.stringify( {
		badgeId:  id( badge ),
		badgeUrl: u( badge ),
		items: [ 'Persönlich von mir betreut', 'Schnelle Rückmeldung', 'Vor Ort in ganz Vorarlberg' ],
	} ).replace( /^{|}$/g, '' ) + `} /-->`,

	`<!-- wp:library/founder-story {` + JSON.stringify( {
		heading: 'Warum ich Makler<br>geworden bin',
		lead:    'Bevor ich 2021 ROSENBERGER Immobilien gegründet habe, war ich selbst auf der Suche nach einer Immobilie in Vorarlberg.',
		body:    'Was ich dabei erlebt habe, hat mich mehr geformt als jede Ausbildung danach. Ich habe Makler erlebt, die im Erstgespräch Preise nannten, die der Markt nie bestätigt hat. Ich habe auf Rückrufe gewartet, die nicht kamen. Ich habe Exposés gesehen, die mit dem tatsächlichen Objekt kaum noch etwas zu tun hatten.',
		quote:   'Das war keine Ausnahme.\nDas war die Regel.',
	} ).replace( /^{|}$/g, '' ) + `} /-->`,

	`<!-- wp:library/quote-cover {` + JSON.stringify( {
		text:     'Ihr Immobilienmakler\nin Vorarlberg',
		imageId:  id( quoteCover ),
		imageUrl: u( quoteCover ),
	} ).replace( /^{|}$/g, '' ) + `} /-->`,

	`<!-- wp:library/founder-bio {` + JSON.stringify( {
		heading:    'Was Eigentümer brauchen, ist kein Immobilienmakler, sondern Vertrauen.',
		paragraphs: [
			'Irgendwann habe ich aufgehört, darauf zu warten, dass jemand diese Arbeit anders macht. Ich habe begonnen, selbst darüber nachzudenken, was ein Makler einem Eigentümer schuldet und was er einem Käufer schuldet.',
			'2021 habe ich ROSENBERGER Immobilien gegründet. Nicht um ein weiteres Maklerbüro zu eröffnen. Sondern weil ich einen konkreten Grund hatte: Ich wusste, wie es sich anfühlt, wenn ein Makler seine Arbeit nicht macht. Und ich wusste, wie es sich anfühlen sollte.',
			'Diese Erfahrung sitzt in jeder Bewertung, die ich abgebe, und in jedem Gespräch, das ich führe. Sie erinnert mich daran, was für Sie auf dem Spiel steht, wenn ich Ihre Immobilie vermarkte.',
		],
		imageId:  id( founderPhoto ),
		imageUrl: u( founderPhoto ),
	} ).replace( /^{|}$/g, '' ) + `} /-->`,

	`<!-- wp:library/value-cards {` + JSON.stringify( {
		cards: [
			{ title: 'Ehrliche Bewertung',            text: 'Ich sage Ihnen, was der Markt für Ihre Immobilie zahlt. Nicht was ich sagen müsste, um den Auftrag zu bekommen.',        iconId: id( iconHouse ),      iconUrl: u( iconHouse )      },
			{ title: 'Direkte Erreichbarkeit',         text: 'Sie erreichen mich auf meiner persönlichen Mobilnummer. Ich melde mich, ohne dass Sie nachfragen müssen.',               iconId: id( iconEvaluation ), iconUrl: u( iconEvaluation ) },
			{ title: 'Rund eine Stunde Aufwand für Sie', text: 'Das Erstgespräch. Den Rest übernehme ich. Von der Bewertung bis zur Ummeldung nach der Übergabe.',                    iconId: id( iconValet ),      iconUrl: u( iconValet )      },
		],
	} ).replace( /^{|}$/g, '' ) + `} /-->`,

	`<!-- wp:library/promise-list {` + JSON.stringify( {
		heading: 'Was Sie<br>von mir erwarten dürfen',
		items: [
			{ number: '01', title: 'Schriftliche Wertmitteilung<br>nach der Bewertung',       text: 'Nach unserem Gespräch erhalten Sie von mir eine schriftliche Einschätzung mit Bewertungsbasis und Vergleichsdaten. Keine mündliche Zahl, die sich später nicht mehr nachvollziehen lässt.' },
			{ number: '02', title: 'Status-Updates ohne Nachfragen',                        text: 'Sie erfahren regelmäßig, wie viele Anfragen eingegangen sind, welche Besichtigungen stattgefunden haben und wo die Vermarktung steht. Ich melde mich bei Ihnen, nicht umgekehrt.' },
			{ number: '03', title: 'Geprüfte Interessenten,<br>kein Besichtigungstourismus',  text: 'Ich filtere vor jeder Besichtigung, wer ernsthaftes Interesse hat und wer nicht. Ihr Zuhause zeige ich nur Menschen, die kaufen können und wollen.' },
			{ number: '04', title: 'Ein Ansprechpartner von Anfang bis Ende',               text: 'Sie sprechen vom ersten Gespräch bis zur Schlüsselübergabe ausschließlich mit mir. Kein wechselndes Personal, keine Weiterleitung an einen Kollegen.' },
		],
	} ).replace( /^{|}$/g, '' ) + `} /-->`,

	`<!-- wp:library/consultation-cta {` + JSON.stringify( {
		heading:       '',
		headingItalic: 'Lernen Sie mich kennen',
		text:          'Ich bin in Feldkirch ansässig und für Eigentümer in ganz Vorarlberg tätig. Ein erstes Gespräch kostet nichts und verpflichtet Sie zu nichts. Sie erfahren, was Ihre Immobilie wert ist und wie ich den Verkauf für Sie abwickle.',
		buttonText:    'Kostenlos beraten lassen',
		buttonUrl:     '/kontakt/',
		backgroundId:  id( ctaBg ),
		backgroundUrl: u( ctaBg ),
	} ).replace( /^{|}$/g, '' ) + `} /-->`,

].join( '\n\n' );

// ---------------------------------------------------------------------------
// Шаг 3 — создать / обновить страницу
// ---------------------------------------------------------------------------
console.log( '\n📝 Создаю / обновляю страницу «Über mich»...' );

const pages = await api( '/wp-json/wp/v2/pages?slug=uber-mich&status=any&per_page=1' );
let pageId;

if ( Array.isArray( pages.body ) && pages.body[ 0 ] ) {
	pageId = pages.body[ 0 ].id;
	const r = await api( `/wp-json/wp/v2/pages/${ pageId }`, {
		method: 'POST',
		body: JSON.stringify( { content: pageContent, status: 'publish' } ),
	} );
	if ( ! r.ok ) throw new Error( `Ошибка обновления: ${ JSON.stringify( r.body ).slice( 0, 300 ) }` );
	console.log( `  ✓ Обновлена существующая страница id=${ pageId }` );
} else {
	const r = await api( '/wp-json/wp/v2/pages', {
		method: 'POST',
		body: JSON.stringify( { title: 'Über mich', slug: 'uber-mich', content: pageContent, status: 'publish' } ),
	} );
	if ( ! r.ok ) throw new Error( `Ошибка создания: ${ JSON.stringify( r.body ).slice( 0, 300 ) }` );
	pageId = r.body.id;
	console.log( `  ✓ Создана страница id=${ pageId }` );
}

console.log( `\n✅ Готово! Страница: ${ BASE }/${ 'uber-mich' }/` );
console.log( `   Редактор: ${ BASE }/wp-admin/post.php?post=${ pageId }&action=edit` );
