// Батч-экспорт нод Figma в картинки через REST /v1/images (токен из .env).
// Один запрос на МНОГО нод вместо по-нодного download_assets. Качает в .visual/.
//
// Использование:
//   node scripts/figma-export.mjs <id1> [<id2> ...] [--format svg|png|jpg] [--scale 2]
// Печатает локальные пути.

import { readFileSync, writeFileSync, mkdirSync, existsSync } from 'node:fs';

const env = Object.fromEntries( readFileSync( '.env', 'utf8' ).split( '\n' )
	.filter( ( l ) => l.includes( '=' ) && ! l.trim().startsWith( '#' ) )
	.map( ( l ) => { const i = l.indexOf( '=' ); return [ l.slice( 0, i ).trim(), l.slice( i + 1 ).trim() ]; } ) );
const TOKEN = env.FIGMA_TOKEN;
const FILE = env.FIGMA_FILE || 'p1HKLfoMcOwtVUD5rI9V3P';

const args = process.argv.slice( 2 );
const ids = args.filter( ( a ) => /^\d+[:-]\d+$/.test( a ) ).map( ( a ) => a.replace( '-', ':' ) );
if ( ! ids.length ) { console.error( 'Укажи node-id(ы)' ); process.exit( 1 ); }
const val = ( n, d ) => { const i = args.indexOf( '--' + n ); return i >= 0 && args[ i + 1 ] ? args[ i + 1 ] : d; };
const format = val( 'format', 'png' );
const scale = val( 'scale', '2' );

const url = `https://api.figma.com/v1/images/${ FILE }?ids=${ encodeURIComponent( ids.join( ',' ) ) }&format=${ format }&scale=${ scale }`;
const r = await fetch( url, { headers: { 'X-Figma-Token': TOKEN } } );
const j = await r.json();
if ( ! r.ok || j.err ) { console.error( 'Figma API', r.status, j.err || '' ); process.exit( 1 ); }

if ( ! existsSync( '.visual' ) ) mkdirSync( '.visual' );
for ( const id of ids ) {
	const link = j.images[ id ];
	if ( ! link ) { console.error( `нет рендера для ${ id }` ); continue; }
	const buf = Buffer.from( await ( await fetch( link ) ).arrayBuffer() );
	const path = `.visual/fx-${ id.replace( ':', '-' ) }.${ format }`;
	writeFileSync( path, buf );
	console.log( path );
}
