// Записывает мета-поля и featured-изображения для объектов CPT property
// через Code Snippets (ACF блокирует запись meta в WP REST). Идемпотентно,
// можно перезапускать. Сниппет создаётся, триггерится и обезвреживается.
//
// Запуск: node scripts/seed-property-meta.mjs

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

// Прямой вызов REST (без префикса /wp/v2) — для эндпоинтов code-snippets.
const api = async ( path, opts = {} ) => {
	const res = await fetch( `${ BASE }${ path }`, {
		...opts,
		headers: { Authorization: AUTH, 'Content-Type': 'application/json', ...( opts.headers || {} ) },
	} );
	const text = await res.text();
	let body; try { body = JSON.parse( text ); } catch { body = text; }
	return { status: res.status, body };
};

// Желаемая мета по заголовку объекта. status: Verkauft → попадает в sold-showcase.
const WANT = {
	'Moderne 4-Zimmer-Wohnung in Feldkirch':   { price: 'Auf Anfrage',  area: 'ca. 130 m²', plot: 'ca. 250 m²', rooms: '4', status: 'Verkauft',   thumb: 'rosenberger-card-1' },
	'Einfamilienhaus mit Garten in Dornbirn':  { price: '€ 680.000',    area: 'ca. 220 m²', plot: 'ca. 540 m²', rooms: '6', status: 'Verkauft',   thumb: 'rosenberger-card-2' },
	'Ruhige 3-Zimmer-Wohnung in Bludenz':      { price: 'Auf Anfrage',  area: 'ca. 85 m²',  plot: 'ca. 180 m²', rooms: '3', status: 'Verkauft',   thumb: 'rosenberger-card-3' },
	'Exklusives Penthouse am Bodensee':        { price: '€ 1.250.000',  area: 'ca. 180 m²', plot: 'ca. 300 m²', rooms: '5', status: 'Verfügbar',  thumb: 'rosenberger-card-1' },
	'Erschlossenes Baugrundstück in Hohenems': { price: 'Auf Anfrage',  area: 'ca. 800 m²', plot: 'ca. 800 m²', rooms: '',  status: 'Verfügbar',  thumb: 'rosenberger-card-2' },
	'Gepflegtes Reihenhaus in Feldkirch':      { price: '€ 520.000',    area: 'ca. 160 m²', plot: 'ca. 320 m²', rooms: '5', status: 'Reserviert', thumb: 'rosenberger-card-3' },
};

const mediaId = async ( slug ) => {
	const r = await api( `/wp-json/wp/v2/media?slug=${ encodeURIComponent( slug ) }&per_page=1` );
	return Array.isArray( r.body ) && r.body[ 0 ] ? r.body[ 0 ].id : 0;
};

console.log( '\n📋 Сопоставляю объекты...' );
const props = await api( '/wp-json/wp/v2/property?per_page=100&status=any' );
if ( ! Array.isArray( props.body ) ) { console.error( 'Нет объектов property' ); process.exit( 1 ); }

const rows = [];
for ( const p of props.body ) {
	const title = p.title?.rendered?.replace( /&[^;]+;/g, ( e ) => ( { '&amp;': '&', '&#8211;': '–' }[ e ] || e ) ) || '';
	const want = WANT[ title ];
	if ( ! want ) { console.warn( `  ⚠ нет правила для: ${ title }` ); continue; }
	const thumbId = await mediaId( want.thumb );
	rows.push( { id: p.id, ...want, thumbId } );
	console.log( `  • ${ p.id } ${ title } → ${ want.status } (thumb ${ thumbId })` );
}

// PHP-сниппет: update_post_meta + set_post_thumbnail.
const rowsJson = JSON.stringify( rows.map( r => ( {
	id: r.id, price: r.price, area: r.area, plot: r.plot, rooms: r.rooms, status: r.status, thumb: r.thumbId,
} ) ) ).replace( /'/g, "\\'" );
const php = `<?php
$rows = json_decode( '${ rowsJson }', true );
if ( ! is_array( $rows ) ) { return; }
foreach ( $rows as $r ) {
	update_post_meta( $r['id'], 'property_price',      $r['price'] );
	update_post_meta( $r['id'], 'property_area',       $r['area'] );
	update_post_meta( $r['id'], 'property_plot_area',  $r['plot'] );
	update_post_meta( $r['id'], 'property_rooms',      $r['rooms'] );
	update_post_meta( $r['id'], 'property_status',     $r['status'] );
	if ( ! empty( $r['thumb'] ) ) { set_post_thumbnail( $r['id'], (int) $r['thumb'] ); }
}
`;

const NAME = 'Library: property meta seed (temporary)';

// Обезвредить прежние одноимённые.
const list = await api( '/wp-json/code-snippets/v1/snippets' );
if ( Array.isArray( list.body ) ) {
	for ( const s of list.body ) {
		if ( s.name === NAME ) {
			await api( `/wp-json/code-snippets/v1/snippets/${ s.id }`, { method: 'POST', body: JSON.stringify( { active: false, code: '// removed' } ) } );
		}
	}
}

console.log( '\n📝 Создаю сниппет...' );
const created = await api( '/wp-json/code-snippets/v1/snippets', {
	method: 'POST',
	body: JSON.stringify( { name: NAME, desc: 'Разовая запись меты property. Обезвреживается автоматически.', code: php, scope: 'global', active: true } ),
} );
const snipId = created.body && created.body.id;
if ( ! snipId ) { console.error( 'Не удалось создать сниппет:', JSON.stringify( created.body ).slice( 0, 300 ) ); process.exit( 1 ); }
console.log( `  ✓ Сниппет #${ snipId } активен` );

// Триггерим выполнение (global scope исполняется на загрузке) и обезвреживаем.
for ( let i = 0; i < 3; i++ ) await fetch( BASE + '/' ).catch( () => {} );
await api( `/wp-json/code-snippets/v1/snippets/${ snipId }`, { method: 'POST', body: JSON.stringify( { active: false, code: '// removed' } ) } );
console.log( `  ✓ Сниппет #${ snipId } обезврежен` );

console.log( `\n✅ Мета записана для ${ rows.length } объектов (${ rows.filter( r => r.status === 'Verkauft' ).length } Verkauft).` );
