// Создаёт / обновляет страницу «Tippgeber» через WP REST API.
// Запуск: node scripts/import-tippgeber.mjs
//
// Новые блоки (создаются при первом запуске):
//   page-hero, how-it-works, provision-callout, tipper-types, tipper-form
// Переиспользуются: trust-bar, faq-section, consultation-cta
//
// WPForms form ID: задать wpformsId в tipper-form после создания формы в WPForms.

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

const findMedia = async ( slug ) => {
	const r = await api( `/wp-json/wp/v2/media?slug=${ encodeURIComponent( slug ) }&per_page=1` );
	const item = Array.isArray( r.body ) ? r.body[ 0 ] : null;
	if ( item ) { console.log( `  ✓ ${ slug } (id=${ item.id })` ); return item; }
	console.warn( `  ⚠  не найдено: ${ slug }` );
	return null;
};

const ensureMediaRemote = async ( slug, remoteUrl, ext = 'jpg' ) => {
	const found = await api( `/wp-json/wp/v2/media?slug=${ encodeURIComponent( slug ) }&per_page=1` );
	const existing = Array.isArray( found.body ) ? found.body[ 0 ] : null;
	if ( existing ) { console.log( `  ✓ ${ slug } (id=${ existing.id })` ); return existing; }
	console.log( `  ↑ загружаю ${ slug }...` );
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
// Шаг 1 — медиа (Figma CDN URLs действительны 7 дней)
// ---------------------------------------------------------------------------
// Fresh URLs from get_design_context on node 2005:2951 (2026-06-23)
const FIGMA_HERO_BG       = 'https://www.figma.com/api/mcp/asset/82c49da0-1dd0-4f90-ae11-ae2edc95209c'; // imgRectangle67
const FIGMA_PROVISION_BG  = 'https://www.figma.com/api/mcp/asset/6e526143-be25-4f73-b2d0-d5af465cb209'; // imgFrame1984077938
const FIGMA_BOTTOM_CTA_BG = 'https://www.figma.com/api/mcp/asset/23ef79ec-37ee-436f-a4f8-1990ad431403'; // imgFrame1984077934
const FIGMA_ICON_INFO     = 'https://www.figma.com/api/mcp/asset/c19ee62a-d380-48d5-8aa0-1706daf69b28'; // screenshot node 2005:3011
const FIGMA_ICON_COIN     = 'https://www.figma.com/api/mcp/asset/06d3128b-4812-4649-92dd-d15f15423cb8'; // screenshot node 2005:3030
const FIGMA_TYPES_PRIVATE = 'https://www.figma.com/api/mcp/asset/27e13b2a-a937-4f97-a999-ba4e59f3ee53'; // imgBlogImage
const FIGMA_TYPES_PROF    = 'https://www.figma.com/api/mcp/asset/f6942dfe-be21-4015-8a35-c29ba138a12f'; // imgBlogImage1
const FIGMA_ICON_PHONE    = 'https://www.figma.com/api/mcp/asset/1f9f50ea-3575-4638-9d72-5330422b282e'; // screenshot node 2005:3018

console.log( '\n📦 Медиа...' );
const [
	heroBg,
	provisionBg,
	bottomCtaBg,
	iconInfo,
	iconCoin,
	typesPrivate,
	typesProf,
	iconPhone,
	badge,
	ctaBg,
] = await Promise.all( [
	ensureMediaRemote( 'rosenberger-tippgeber-hero-bg',      FIGMA_HERO_BG,       'jpg' ),
	ensureMediaRemote( 'rosenberger-provision-bg',           FIGMA_PROVISION_BG,  'jpg' ),
	ensureMediaRemote( 'rosenberger-tippgeber-cta-bg',       FIGMA_BOTTOM_CTA_BG, 'jpg' ),
	ensureMediaRemote( 'rosenberger-icon-info',              FIGMA_ICON_INFO,     'png' ),
	ensureMediaRemote( 'rosenberger-icon-coin',              FIGMA_ICON_COIN,     'png' ),
	ensureMediaRemote( 'rosenberger-tipper-privatpersonen',  FIGMA_TYPES_PRIVATE, 'jpg' ),
	ensureMediaRemote( 'rosenberger-tipper-beruflich',       FIGMA_TYPES_PROF,    'jpg' ),
	ensureMediaRemote( 'rosenberger-icon-phone',             FIGMA_ICON_PHONE,    'png' ),
	findMedia( 'rosenberger-google-rating' ),
	findMedia( 'rosenberger-consultation-bg' ),
] );

const u  = m => m ? m.source_url : '';
const id = m => m ? m.id : 0;

// ---------------------------------------------------------------------------
// Шаг 2 — блочная разметка
// ---------------------------------------------------------------------------
console.log( '\n📄 Собираю разметку...' );

const faqItems = [
	{
		question: 'Wie schnell melden Sie sich, nachdem ich einen Tipp eingesendet habe?',
		answer:   'Innerhalb von 24 Stunden, in den meisten Fällen schneller. Sie erfahren von mir, wie ich vorgehe und was die nächsten Schritte sind.',
		open:     true,
	},
	{
		question: 'Wann wird die Provision ausgezahlt?',
		answer:   'Sobald der Kaufpreis bei mir eingegangen ist, überweise ich Ihren Anteil. Den Zeitpunkt legen wir vorher schriftlich fest.',
		open:     false,
	},
	{
		question: 'Was sage ich, falls der Eigentümer fragt, woher ich von ihm weiß?',
		answer:   'Das entscheiden Sie. Ob ich Ihren Namen nenne oder nicht, klären wir vorab. Ich gehe diskret vor.',
		open:     false,
	},
	{
		question: 'Was, wenn ich nur vermute, dass jemand verkaufen will?',
		answer:   'Das reicht vollkommen. Ein Hinweis auf die Situation, die Adresse oder das Viertel – ich kläre das Weitere.',
		open:     false,
	},
	{
		question: 'Kann ich mehrere Tipps abgeben?',
		answer:   'Ja, so viele Sie möchten. Für jeden erfolgreichen Verkauf erhalten Sie 10 % der Provision.',
		open:     false,
	},
];

const pageContent = [

	`<!-- wp:library/page-hero ${ JSON.stringify( {
		headingStart:  'Kennen Sie jemanden, der seine ',
		headingItalic: 'Immobilie verkaufen ',
		headingEnd:    'möchte?',
		subtitle:      'Geben Sie mir den Tipp. \nBei erfolgreichem Verkauf erhalten Sie 10 Prozent meiner Verkäuferprovision.',
		buttonText:    'Jetzt Tipp einsenden',
		buttonUrl:     '#tipper-form',
		disclaimer:    'Unverbindlich und kostenlos',
		imageId:       id( heroBg ),
		imageUrl:      u( heroBg ),
	} ) } /-->`,

	`<!-- wp:library/trust-bar ${ JSON.stringify( {
		badgeId:  id( badge ),
		badgeUrl: u( badge ),
		items:    [ 'Persönlich von mir betreut', 'Schnelle Rückmeldung', 'Vor Ort in ganz Vorarlberg' ],
	} ) } /-->`,

	`<!-- wp:library/how-it-works ${ JSON.stringify( {
		heading: 'So funktioniert es',
		lead:    'Sie geben den Hinweis weiter. Ich übernehme den Rest.',
		items: [
			{
				iconId:  id( iconInfo ),
				iconUrl: u( iconInfo ),
				title:   'Sie geben<br>mir den Tipp',
				text:    'Adresse oder Lage der Immobilie und ein kurzer Hinweis auf die Situation. Per Formular oder am Telefon.',
			},
			{
				iconId:  id( iconPhone ),
				iconUrl: u( iconPhone ),
				title:   'Ich kontaktiere<br>den Eigentümer',
				text:    'Diskret und ohne Verkaufsdruck. Ob ich Ihren Namen erwähne, entscheiden Sie.',
			},
			{
				iconId:  id( iconCoin ),
				iconUrl: u( iconCoin ),
				title:   'Bei Verkauf<br>erhalten Sie Ihre Provision',
				text:    'Sobald der Kaufpreis bei mir eingegangen ist, überweise ich Ihren Anteil. Den Zeitpunkt legen wir vorher fest.',
			},
		],
	} ) } /-->`,

	`<!-- wp:library/provision-callout ${ JSON.stringify( {
		label:     'Ihre Provision',
		value:     '10 %',
		subtitle:  'Sie erhalten zehn Prozent der Provision, die der Eigentümer mir für den Verkauf zahlt.',
		finePrint: '* Die genauen Konditionen halten wir vor dem ersten Tipp im Vertrag fest.',
		imageId:   id( provisionBg ),
		imageUrl:  u( provisionBg ),
	} ) } /-->`,

	`<!-- wp:library/tipper-types ${ JSON.stringify( {
		headingStart:  'Wer als ',
		headingItalic: 'Tippgeber ',
		headingLine2:  'in Frage kommt',
		lead:          'Jeder, der von einer Verkaufsabsicht weiß oder eine vermutet.',
		items: [
			{
				imageId:  id( typesPrivate ),
				imageUrl: u( typesPrivate ),
				title:    'Privatpersonen',
				text:     'Nachbarn, Freunde, Bekannte oder Familienmitglieder, die wissen, dass jemand über einen Verkauf nachdenkt oder sich in einer Lebenssituation befindet, die einen Verkauf wahrscheinlich macht.',
			},
			{
				imageId:  id( typesProf ),
				imageUrl: u( typesProf ),
				title:    'Berufliche Kontakte',
				text:     'Notare, Steuerberater, Anwälte, Bankmitarbeiter, Finanzberater, Hausverwalter, Handwerker und andere, die beruflich Einblick in Eigentümersituationen haben.',
			},
		],
	} ) } /-->`,

	`<!-- wp:library/tip-form ${ JSON.stringify( {
		heading:   'Tipp einsenden',
		lead:      'Drei Schritte, drei Minuten. Ich melde mich innerhalb von 24 Stunden bei Ihnen.',
		formSlug:  'tippgeber',
		wpformsId: 0,
	} ) } /-->`,

	`<!-- wp:library/faq-section ${ JSON.stringify( {
		heading: 'Häufige Fragen',
		items:   faqItems,
	} ) } /-->`,

	`<!-- wp:library/consultation-cta ${ JSON.stringify( {
		heading:       '',
		headingItalic: 'Noch Fragen zum Tippgeber-Programm?',
		text:          'Rufen Sie mich an. Wir klären alle offenen Punkte am Telefon.',
		buttonText:    'Jetzt anrufen',
		buttonUrl:     'tel:+436991177750',
		backgroundId:  id( bottomCtaBg ),
		backgroundUrl: u( bottomCtaBg ),
	} ) } /-->`,

].join( '\n\n' );

// ---------------------------------------------------------------------------
// Шаг 3 — создать / обновить страницу
// ---------------------------------------------------------------------------
console.log( '\n📝 Создаю / обновляю страницу «Tippgeber»...' );

const pages = await api( '/wp-json/wp/v2/pages?slug=tippgeber&status=any&per_page=1' );
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
		body: JSON.stringify( { title: 'Tippgeber', slug: 'tippgeber', content: pageContent, status: 'publish' } ),
	} );
	if ( ! r.ok ) throw new Error( `Ошибка создания: ${ JSON.stringify( r.body ).slice( 0, 300 ) }` );
	pageId = r.body.id;
	console.log( `  ✓ Создана id=${ pageId }` );
}

console.log( `\n✅ Готово! ${ BASE }/tippgeber/` );
console.log( `\n⚠  WPForms: создай форму по WPFORMS-SETUP.md, вставь ID в блок tipper-form через редактор.` );
