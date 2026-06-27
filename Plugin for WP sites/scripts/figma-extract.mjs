// Быстрый экстрактор Figma через REST (токен из .env). Тянет поддерево ноды ОДНИМ
// запросом, в Node достаёт компактные данные и кэширует — чтобы НЕ гонять MCP
// (get_metadata=970k, по-нодные get_screenshot/download_assets) и не жечь контекст.
//
// Использование:
//   node scripts/figma-extract.mjs <nodeId> [--text|--images|--geo] [--grep "слово"]
//     --text   (деф.) все TEXT-ноды: текст + bbox
//     --images все ноды с IMAGE-fill: bbox + imageRef
//     --geo    все именованные фреймы: bbox (для align/ширины)
//     --grep   фильтр по подстроке (в тексте/имени)
// Кэш полного дерева: .figma-cache/<nodeId>.json (gitignore).

import { readFileSync, writeFileSync, mkdirSync, existsSync } from 'node:fs';

const env = Object.fromEntries( readFileSync( '.env', 'utf8' ).split( '\n' )
	.filter( ( l ) => l.includes( '=' ) && ! l.trim().startsWith( '#' ) )
	.map( ( l ) => { const i = l.indexOf( '=' ); return [ l.slice( 0, i ).trim(), l.slice( i + 1 ).trim() ]; } ) );
const TOKEN = env.FIGMA_TOKEN;
const FILE = env.FIGMA_FILE || 'p1HKLfoMcOwtVUD5rI9V3P';

const args = process.argv.slice( 2 );
const nodeId = args.find( ( a ) => /^\d+[:-]\d+$/.test( a ) );
if ( ! nodeId ) { console.error( 'Укажи nodeId, напр. 2126:4658' ); process.exit( 1 ); }
const mode = args.includes( '--images' ) ? 'images' : args.includes( '--geo' ) ? 'geo' : 'text';
const grep = ( () => { const i = args.indexOf( '--grep' ); return i >= 0 ? ( args[ i + 1 ] || '' ).toLowerCase() : ''; } )();

if ( ! existsSync( '.figma-cache' ) ) mkdirSync( '.figma-cache' );
const cacheFile = `.figma-cache/${ nodeId.replace( ':', '-' ) }.json`;

let doc;
if ( existsSync( cacheFile ) ) {
	doc = JSON.parse( readFileSync( cacheFile, 'utf8' ) );
	console.error( `(кэш ${ cacheFile })` );
} else {
	const url = `https://api.figma.com/v1/files/${ FILE }/nodes?ids=${ encodeURIComponent( nodeId ) }`;
	const r = await fetch( url, { headers: { 'X-Figma-Token': TOKEN } } );
	if ( ! r.ok ) { console.error( 'Figma API', r.status, ( await r.text() ).slice( 0, 200 ) ); process.exit( 1 ); }
	const j = await r.json();
	doc = j.nodes[ nodeId ]?.document;
	if ( ! doc ) { console.error( 'нет ноды' ); process.exit( 1 ); }
	writeFileSync( cacheFile, JSON.stringify( doc ) );
	console.error( `(загружено и закэшировано → ${ cacheFile })` );
}

const out = [];
( function walk( n ) {
	if ( ! n ) return;
	const bb = n.absoluteBoundingBox;
	const box = bb ? `${ Math.round( bb.x ) },${ Math.round( bb.y )} ${ Math.round( bb.width ) }x${ Math.round( bb.height ) }` : '';
	if ( mode === 'text' && n.type === 'TEXT' ) {
		out.push( { id: n.id, box, text: ( n.characters || '' ).replace( /\n/g, ' ⏎ ' ) } );
	} else if ( mode === 'images' && ( n.fills || [] ).some( ( f ) => f.type === 'IMAGE' ) ) {
		const ref = ( n.fills || [] ).find( ( f ) => f.type === 'IMAGE' )?.imageRef;
		out.push( { id: n.id, box, name: n.name, imageRef: ref } );
	} else if ( mode === 'geo' && ( n.type === 'FRAME' || n.type === 'INSTANCE' ) ) {
		out.push( { id: n.id, box, name: n.name } );
	}
	( n.children || [] ).forEach( walk );
} )( doc );

const rows = grep
	? out.filter( ( o ) => ( ( o.text || '' ) + ( o.name || '' ) ).toLowerCase().includes( grep ) )
	: out;
console.error( `${ mode }: ${ rows.length }/${ out.length } нод${ grep ? ` (grep "${ grep }")` : '' }\n` );
for ( const r of rows ) {
	if ( mode === 'text' ) console.log( `[${ r.box }] ${ r.text }` );
	else console.log( `${ r.id } [${ r.box }] ${ r.name }${ r.imageRef ? ' ref=' + r.imageRef : '' }` );
}
