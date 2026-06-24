// Создаёт / обновляет страницу «Kontakt» через WP REST API.
// Запуск: node scripts/import-kontakt.mjs
// Перед этим: node scripts/deploy-stack.mjs (блок) + node scripts/setup-contact-form.mjs (форма).
// Блок contact-section сам находит WPForms-форму по slug `kontakt` и контакты из настроек сайта.

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

const pageContent = `<!-- wp:library/contact-section ${ JSON.stringify( {
	align:         'full',
	headingItalic: 'Jetzt Kontakt',
	headingRest:   ' aufnehmen',
	lead:          'Schreiben Sie uns kurz, worum es geht. Wir melden uns schnell zurück <br>und besprechen gemeinsam den besten nächsten Schritt für Ihr Vorhaben.',
	cardTitle:     'Contact Information',
	formId:        0,
	lat:           47.2466,
	lng:           9.5851,
} ) } /-->`;

console.log( '\n📝 Создаю / обновляю страницу «Kontakt»...' );
const pages = await api( '/wp-json/wp/v2/pages?slug=kontakt&status=any&per_page=1' );
let pageId;
if ( Array.isArray( pages.body ) && pages.body[ 0 ] ) {
	pageId = pages.body[ 0 ].id;
	const r = await api( `/wp-json/wp/v2/pages/${ pageId }`, {
		method: 'POST', body: JSON.stringify( { content: pageContent, status: 'publish' } ),
	} );
	if ( ! r.ok ) throw new Error( `Ошибка обновления: ${ JSON.stringify( r.body ).slice( 0, 300 ) }` );
	console.log( `  ✓ Обновлена существующая страница id=${ pageId }` );
} else {
	const r = await api( '/wp-json/wp/v2/pages', {
		method: 'POST', body: JSON.stringify( { title: 'Kontakt', slug: 'kontakt', content: pageContent, status: 'publish' } ),
	} );
	if ( ! r.ok ) throw new Error( `Ошибка создания: ${ JSON.stringify( r.body ).slice( 0, 300 ) }` );
	pageId = r.body.id;
	console.log( `  ✓ Создана страница id=${ pageId }` );
}
console.log( `\n✅ Готово! Страница: ${ BASE }/kontakt/` );
console.log( `   Редактор: ${ BASE }/wp-admin/post.php?post=${ pageId }&action=edit` );
