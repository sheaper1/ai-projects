// Загрузка медиа-ассетов в WP Media Library (idempotent — slug-проверка).
// Запуск: node scripts/upload-media.mjs

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

const MIME = { jpg: 'image/jpeg', jpeg: 'image/jpeg', png: 'image/png', svg: 'image/svg+xml', webp: 'image/webp' };

const upload = async ( slug, file, ext = 'jpg' ) => {
	// Проверяем — вдруг уже загружено
	const check = await fetch( `${ BASE }/wp-json/wp/v2/media?slug=${ encodeURIComponent( slug ) }&per_page=1`, {
		headers: { Authorization: AUTH },
	} );
	const existing = await check.json();
	if ( Array.isArray( existing ) && existing[ 0 ] ) {
		console.log( `  ✓ ${ slug } — уже есть (id=${ existing[ 0 ].id })` );
		return existing[ 0 ];
	}

	const filename = `${ slug }.${ ext }`;
	const res = await fetch( `${ BASE }/wp-json/wp/v2/media`, {
		method: 'POST',
		headers: {
			Authorization: AUTH,
			'Content-Type': MIME[ ext ] || 'application/octet-stream',
			'Content-Disposition': `attachment; filename="${ filename }"`,
		},
		body: readFileSync( file ),
	} );
	const body = await res.json();
	if ( ! res.ok ) throw new Error( `Ошибка ${ filename }: ${ body.message || res.status }` );
	console.log( `  ↑ ${ slug } загружен (id=${ body.id }) — ${ body.source_url }` );
	return body;
};

const tmp = resolve( root, '.figma-tmp' );

const media = [
	// Регионы (блок region-grid)
	{ slug: 'region-feldkirch', file: `${ tmp }/region-feldkirch.jpg` },
	{ slug: 'region-dornbirn',  file: `${ tmp }/region-dornbirn.jpg` },
	{ slug: 'region-bludenz',   file: `${ tmp }/region-bludenz.jpg` },
	// Фото объектов недвижимости (блок property-grid / CPT property)
	{ slug: 'property-sample-1', file: `${ tmp }/property-1.jpg` },
	{ slug: 'property-sample-2', file: `${ tmp }/property-2.jpg` },
	{ slug: 'property-sample-3', file: `${ tmp }/property-3.jpg` },
	{ slug: 'property-sample-4', file: `${ tmp }/property-4.jpg` },
];

console.log( `Загрузка ${ media.length } файлов на ${ BASE }...\n` );
const results = {};
for ( const { slug, file } of media ) {
	try {
		const m = await upload( slug, file );
		results[ slug ] = { id: m.id, url: m.source_url };
	} catch ( e ) {
		console.error( `  ✗ ${ slug }: ${ e.message }` );
	}
}

console.log( '\n=== Media IDs (для block defaults) ===' );
for ( const [ slug, { id, url } ] of Object.entries( results ) ) {
	console.log( `${ slug }: id=${ id }` );
}
