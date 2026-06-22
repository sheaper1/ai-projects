// Сверка эталонов library/blocks/<slug> с проектными копиями theme/blocks/<slug>.
// Показывает, где блоки разошлись (src/render.php/block.json), игнорируя build/.
// Запуск: node scripts/diff-blocks.mjs [projectThemeRel]
//   по умолчанию проект: projects/rosenberger/theme
//
// Это диагностика дрейфа «копия в проект»: расхождения ожидаемы (проектный форк),
// но скрипт даёт быстрый обзор, что именно отличается, чтобы ничего не потерять.

import { readdirSync, existsSync, statSync, readFileSync } from 'node:fs';
import { createHash } from 'node:crypto';
import { resolve, dirname, relative } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = resolve( dirname( fileURLToPath( import.meta.url ) ), '..' );
const themeRel = process.argv[ 2 ] || 'projects/rosenberger/theme';
const libDir = resolve( root, 'library/blocks' );
const themeDir = resolve( root, themeRel, 'blocks' );

const IGNORE = ( rel ) => rel.startsWith( 'build/' ) || rel.includes( '/build/' );

// { относительный_путь: sha1 } для всех файлов блока, кроме build/.
const hashTree = ( dir ) => {
	const out = {};
	const walk = ( d ) => {
		for ( const name of readdirSync( d ) ) {
			const p = resolve( d, name );
			if ( statSync( p ).isDirectory() ) { walk( p ); continue; }
			const rel = relative( dir, p ).split( '\\' ).join( '/' );
			if ( IGNORE( rel ) ) continue;
			out[ rel ] = createHash( 'sha1' ).update( readFileSync( p ) ).digest( 'hex' );
		}
	};
	walk( dir );
	return out;
};

const libBlocks = existsSync( libDir ) ? readdirSync( libDir ).filter( ( n ) => statSync( resolve( libDir, n ) ).isDirectory() ) : [];
const themeBlocks = existsSync( themeDir ) ? readdirSync( themeDir ).filter( ( n ) => statSync( resolve( themeDir, n ) ).isDirectory() ) : [];
const all = [ ...new Set( [ ...libBlocks, ...themeBlocks ] ) ].sort();

let drift = 0;
for ( const slug of all ) {
	const inLib = libBlocks.includes( slug );
	const inTheme = themeBlocks.includes( slug );
	if ( ! inLib ) { console.log( `⚠ ${ slug }: только в theme (нет эталона в library)` ); drift++; continue; }
	if ( ! inTheme ) { console.log( `⚠ ${ slug }: только в library (не скопирован в проект)` ); drift++; continue; }

	const lib = hashTree( resolve( libDir, slug ) );
	const theme = hashTree( resolve( themeDir, slug ) );
	const files = [ ...new Set( [ ...Object.keys( lib ), ...Object.keys( theme ) ] ) ].sort();
	const diffs = files.filter( ( f ) => lib[ f ] !== theme[ f ] );
	if ( diffs.length ) {
		console.log( `≠ ${ slug }: расходятся ${ diffs.length } файл(ов):` );
		for ( const f of diffs ) {
			const tag = ! lib[ f ] ? 'только theme' : ! theme[ f ] ? 'только library' : 'изменён';
			console.log( `    ${ f } (${ tag })` );
		}
		drift++;
	}
}

if ( ! drift ) console.log( '✅ library и theme идентичны (без учёта build/).' );
else console.log( `\nВсего блоков с расхождениями: ${ drift } из ${ all.length }.` );
