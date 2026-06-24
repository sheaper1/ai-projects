// Создаёт / обновляет страницу «Danke» (thank you) через WP REST API.
// Запуск: node scripts/import-danke.mjs
// Иконки карточек загружает deploy-stack.mjs под slug'ами:
//   rosenberger-danke-icon-email / -phone / -steps
// Поэтому сначала: node scripts/deploy-stack.mjs, затем этот скрипт.

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
	if ( item ) {
		console.log( `  ✓ ${ slug } (id=${ item.id })` );
		return item;
	}
	console.warn( `  ⚠  медиа не найдено: ${ slug } — карточка будет без иконки (запусти deploy-stack.mjs)` );
	return null;
};

console.log( '\n📦 Иконки карточек...' );
const [ iconEmail, iconPhone, iconSteps ] = await Promise.all( [
	findMedia( 'rosenberger-danke-icon-email' ),
	findMedia( 'rosenberger-danke-icon-phone' ),
	findMedia( 'rosenberger-danke-icon-steps' ),
] );

const u  = ( m ) => m ? m.source_url : '';
const id = ( m ) => m ? m.id : 0;

console.log( '\n📄 Собираю разметку...' );

const pageContent = `<!-- wp:library/thank-you ${ JSON.stringify( {
	align:         'full',
	headingItalic: 'Vielen Dank',
	headingRest:   'für Ihre Anfrage!',
	lead:          'Ihre Nachricht wurde erfolgreich versendet. Ich melde mich persönlich bei Ihnen.',
	buttonText:    'Zur Startseite',
	buttonUrl:     '/',
	cards: [
		{ title: 'Bestätigung per E-Mail', text: 'Sie erhalten in Kürze eine Bestätigungsmail mit allen Details Ihrer Anfrage.', iconId: id( iconEmail ), iconUrl: u( iconEmail ) },
		{ title: 'Ich melde mich bei Ihnen', text: 'Ich prüfe Ihre Anfrage und melde mich telefonisch oder per E-Mail bei Ihnen.', iconId: id( iconPhone ), iconUrl: u( iconPhone ) },
		{ title: 'Nächste Schritte', text: 'Im persönlichen Gespräch klären wir Ihre Fragen, besprechen Ihre Immobilie und vereinbaren bei Bedarf einen Termin vor Ort.', iconId: id( iconSteps ), iconUrl: u( iconSteps ) },
	],
} ) } /-->`;

console.log( '\n📝 Создаю / обновляю страницу «Danke»...' );

const pages = await api( '/wp-json/wp/v2/pages?slug=danke&status=any&per_page=1' );
let pageId;

if ( Array.isArray( pages.body ) && pages.body[ 0 ] ) {
	pageId = pages.body[ 0 ].id;
	const r = await api( `/wp-json/wp/v2/pages/${ pageId }`, {
		method: 'POST',
		body: JSON.stringify( { content: pageContent, status: 'publish', template: '' } ),
	} );
	if ( ! r.ok ) throw new Error( `Ошибка обновления: ${ JSON.stringify( r.body ).slice( 0, 300 ) }` );
	console.log( `  ✓ Обновлена существующая страница id=${ pageId }` );
} else {
	const r = await api( '/wp-json/wp/v2/pages', {
		method: 'POST',
		body: JSON.stringify( { title: 'Danke', slug: 'danke', content: pageContent, status: 'publish' } ),
	} );
	if ( ! r.ok ) throw new Error( `Ошибка создания: ${ JSON.stringify( r.body ).slice( 0, 300 ) }` );
	pageId = r.body.id;
	console.log( `  ✓ Создана страница id=${ pageId }` );
}

console.log( `\n✅ Готово! Страница: ${ BASE }/danke/` );
console.log( `   Редактор: ${ BASE }/wp-admin/post.php?post=${ pageId }&action=edit` );
