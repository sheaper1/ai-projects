// Создаёт/обновляет юридические страницы Impressum и Datenschutz на staging.
// Контент — СТАНДАРТНЫЙ ПЛЕЙСХОЛДЕР (клиент заменяет реальными данными).
// Текст в core-блоках (group constrained + heading/paragraph), без кастомных блоков.
// Запуск: node scripts/import-legal.mjs

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

// --- Хелперы разметки -------------------------------------------------------
const h = ( level, text ) =>
	`<!-- wp:heading {"level":${ level }} -->\n<h${ level } class="wp-block-heading">${ text }</h${ level }>\n<!-- /wp:heading -->`;
const p = ( text ) => `<!-- wp:paragraph -->\n<p>${ text }</p>\n<!-- /wp:paragraph -->`;
const note = ( text ) =>
	`<!-- wp:paragraph {"className":"is-style-default"} -->\n<p><em>${ text }</em></p>\n<!-- /wp:paragraph -->`;

// Контейнер: constrained (contentSize из theme.json) + верхний отступ под fixed-шапку.
const wrap = ( inner ) =>
	`<!-- wp:group {"layout":{"type":"constrained"},"style":{"spacing":{"padding":{"top":"160px","bottom":"var:preset|spacing|r-section","left":"var:custom|layout|gutter","right":"var:custom|layout|gutter"}}}} -->\n` +
	`<div class="wp-block-group" style="padding-top:160px;padding-bottom:var(--wp--preset--spacing--r-section);padding-left:var(--wp--custom--layout--gutter);padding-right:var(--wp--custom--layout--gutter)">\n` +
	inner.join( '\n\n' ) +
	`\n</div>\n<!-- /wp:group -->`;

// --- Impressum (Österreich: §5 ECG / §25 MedienG) ---------------------------
const impressum = wrap( [
	h( 1, 'Impressum' ),
	note( 'Hinweis: Dies ist ein Platzhalter-Impressum. Bitte ersetzen Sie alle Angaben durch die tatsächlichen Unternehmensdaten.' ),
	h( 2, 'Medieninhaber und Herausgeber' ),
	p( 'ROSENBERGER Immobilien<br>Alexander Rosenberger' ),
	p( 'Musterstraße 1<br>6900 Bregenz<br>Vorarlberg, Österreich' ),
	h( 2, 'Kontakt' ),
	p( 'Telefon: +43 5572 123456<br>E-Mail: office@rosenberger.at' ),
	h( 2, 'Unternehmensgegenstand' ),
	p( 'Immobilienmakler' ),
	h( 2, 'Angaben gemäß §5 ECG und §14 UGB' ),
	p( 'Berufsbezeichnung: Immobilienmakler (verliehen in Österreich)<br>UID-Nummer: ATU00000000<br>GISA-Zahl: 00000000<br>Mitglied der Wirtschaftskammer Vorarlberg, Fachgruppe der Immobilien- und Vermögenstreuhänder' ),
	h( 2, 'Gewerbebehörde' ),
	p( 'Bezirkshauptmannschaft Bregenz' ),
	h( 2, 'Berufsrechtliche Vorschriften' ),
	p( 'Gewerbeordnung (GewO), Maklergesetz (MaklerG), Standes- und Ausübungsregeln der Immobilienmakler. Abrufbar unter <a href="https://www.ris.bka.gv.at" rel="noreferrer noopener" target="_blank">www.ris.bka.gv.at</a>.' ),
	h( 2, 'Haftung für Inhalte' ),
	p( 'Die Inhalte dieser Website wurden mit größtmöglicher Sorgfalt erstellt. Für die Richtigkeit, Vollständigkeit und Aktualität der Inhalte kann jedoch keine Gewähr übernommen werden.' ),
	h( 2, 'Urheberrecht' ),
	p( 'Die auf dieser Website veröffentlichten Inhalte unterliegen dem österreichischen Urheberrecht. Jede Verwertung außerhalb der Grenzen des Urheberrechts bedarf der vorherigen schriftlichen Zustimmung.' ),
] );

// --- Datenschutzerklärung (DSGVO) -------------------------------------------
const datenschutz = wrap( [
	h( 1, 'Datenschutzerklärung' ),
	note( 'Hinweis: Dies ist eine Platzhalter-Datenschutzerklärung. Bitte vor der Veröffentlichung rechtlich prüfen und an die tatsächlichen Verarbeitungsvorgänge anpassen.' ),
	h( 2, 'Verantwortlicher' ),
	p( 'Verantwortlich für die Datenverarbeitung im Sinne der DSGVO ist:<br>ROSENBERGER Immobilien, Alexander Rosenberger<br>Musterstraße 1, 6900 Bregenz, Österreich<br>E-Mail: office@rosenberger.at' ),
	h( 2, 'Allgemeines' ),
	p( 'Der Schutz Ihrer personenbezogenen Daten ist uns ein wichtiges Anliegen. Wir verarbeiten Ihre Daten ausschließlich auf Grundlage der gesetzlichen Bestimmungen (DSGVO, TKG 2003).' ),
	h( 2, 'Erhebung und Speicherung personenbezogener Daten' ),
	p( 'Personenbezogene Daten werden nur erhoben, wenn Sie uns diese im Rahmen einer Anfrage oder Kontaktaufnahme freiwillig mitteilen. Die Daten werden ausschließlich zur Bearbeitung Ihres Anliegens verwendet.' ),
	h( 2, 'Kontaktformular' ),
	p( 'Wenn Sie uns über das Kontaktformular Anfragen zukommen lassen, werden Ihre Angaben aus dem Anfrageformular inklusive der von Ihnen dort angegebenen Kontaktdaten zwecks Bearbeitung der Anfrage und für den Fall von Anschlussfragen bei uns gespeichert.' ),
	h( 2, 'Server-Logfiles' ),
	p( 'Der Provider dieser Website erhebt und speichert automatisch Informationen in sogenannten Server-Logfiles, die Ihr Browser automatisch übermittelt (Browsertyp, Betriebssystem, Referrer-URL, Hostname, Uhrzeit der Anfrage, IP-Adresse).' ),
	h( 2, 'Cookies' ),
	p( 'Diese Website verwendet Cookies, um die Nutzung der Website zu ermöglichen und zu verbessern. Sie können Ihren Browser so einstellen, dass Sie über das Setzen von Cookies informiert werden und Cookies nur im Einzelfall erlauben.' ),
	h( 2, 'Ihre Rechte' ),
	p( 'Ihnen stehen grundsätzlich die Rechte auf Auskunft, Berichtigung, Löschung, Einschränkung, Datenübertragbarkeit und Widerspruch zu. Wenn Sie glauben, dass die Verarbeitung Ihrer Daten gegen das Datenschutzrecht verstößt, können Sie sich bei der Datenschutzbehörde (<a href="https://www.dsb.gv.at" rel="noreferrer noopener" target="_blank">www.dsb.gv.at</a>) beschweren.' ),
	h( 2, 'Kontakt zum Datenschutz' ),
	p( 'Bei Fragen zur Erhebung, Verarbeitung oder Nutzung Ihrer personenbezogenen Daten wenden Sie sich bitte an: office@rosenberger.at' ),
] );

// --- Создать / обновить -----------------------------------------------------
const upsert = async ( title, slug, content ) => {
	const found = await api( `/wp-json/wp/v2/pages?slug=${ encodeURIComponent( slug ) }&status=any&per_page=1` );
	if ( Array.isArray( found.body ) && found.body[ 0 ] ) {
		const id = found.body[ 0 ].id;
		await api( `/wp-json/wp/v2/pages/${ id }`, { method: 'POST', body: JSON.stringify( { content, status: 'publish' } ) } );
		console.log( `  ✓ Обновлена «${ title }» id=${ id }` );
		return id;
	}
	const r = await api( '/wp-json/wp/v2/pages', { method: 'POST', body: JSON.stringify( { title, slug, content, status: 'publish' } ) } );
	const id = r.body && r.body.id;
	console.log( `  ${ id ? '✓ Создана' : '✗ Ошибка' } «${ title }» id=${ id || JSON.stringify( r.body ).slice( 0, 200 ) }` );
	return id;
};

console.log( '\n📝 Юридические страницы...' );
const impId = await upsert( 'Impressum', 'impressum', impressum );
const dsId = await upsert( 'Datenschutz', 'datenschutz', datenschutz );

// Проверка результата (REST на staging может отдать 500, но контент сохраняется).
for ( const [ slug, id ] of [ [ 'impressum', impId ], [ 'datenschutz', dsId ] ] ) {
	const r = await api( `/wp-json/wp/v2/pages/${ id }?_fields=content.rendered` );
	const len = r.body && r.body.content ? r.body.content.rendered.length : 0;
	console.log( `  • /${ slug }/ — контент ${ len } символов` );
}

console.log( `\n✅ Готово.\n   ${ BASE }/impressum/\n   ${ BASE }/datenschutz/` );
