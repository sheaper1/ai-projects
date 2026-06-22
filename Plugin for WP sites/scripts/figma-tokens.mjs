// Маппинг «сырое значение из Figma → токен theme.json» + сканер хардкодов.
//
// Зачем: Figma-фрейм почти не использует переменные — дизайн отдаёт сырые hex/px.
// Перенос в блок = ручной маппинг значения в токен, и тут проскакивают хардкоды.
// Скрипт делает маппинг детерминированно (только парс theme.json, без Figma/MCP),
// чтобы не перечитывать theme.json в контекст ИИ и ловить хардкоды до коммита.
//
// Запуск:
//   node scripts/figma-tokens.mjs "#f8f5f3" 24px 1.5rem 724px   — резолв значений
//   node scripts/figma-tokens.mjs --scan                        — линт SCSS на hex
//   node scripts/figma-tokens.mjs --scan projects/<...>/theme   — другой проект
//
// Резолв всегда exit 0 (это справочник). Скан: exit 1, если найден hex-литерал,
// для которого ЕСТЬ токен (явная правка-в-токен); неизвестный hex — только warn.

import { readdirSync, existsSync, statSync, readFileSync } from 'node:fs';
import { resolve, dirname, relative } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = resolve( dirname( fileURLToPath( import.meta.url ) ), '..' );

// --- Загрузка токенов из theme.json активного проекта ----------------------
const themeJsonPath = resolve( root, 'projects/rosenberger/theme/theme.json' );
const theme = JSON.parse( readFileSync( themeJsonPath, 'utf8' ) );
const settings = theme.settings || {};

// rem-эквивалент длины (база 16px). '24px' -> 1.5, '1.5rem' -> 1.5.
const toRem = ( raw ) => {
	const s = String( raw ).trim().toLowerCase();
	let m = s.match( /^(-?\d*\.?\d+)px$/ );
	if ( m ) return parseFloat( m[ 1 ] ) / 16;
	m = s.match( /^(-?\d*\.?\d+)rem$/ );
	if ( m ) return parseFloat( m[ 1 ] );
	return null;
};

// #abc -> #aabbcc; всё в нижний регистр для сравнения
const normHex = ( hex ) => {
	let h = hex.toLowerCase();
	if ( /^#[0-9a-f]{3}$/.test( h ) ) h = '#' + h.slice( 1 ).split( '' ).map( ( ch ) => ch + ch ).join( '' );
	return h;
};

// hex -> [токены] (contrast и primary делят один #142335 — показываем оба)
const colorMap = new Map();
for ( const c of settings.color?.palette || [] ) {
	const key = normHex( c.color );
	if ( ! colorMap.has( key ) ) colorMap.set( key, [] );
	colorMap.get( key ).push( `--wp--preset--color--${ c.slug }` );
}

// rem-число -> [{ kind, token }] (размеры шрифта и интервалы могут совпасть)
const lengthMap = new Map();
const addLength = ( sizeRaw, kind, token ) => {
	const rem = toRem( sizeRaw );
	if ( rem === null ) return; // clamp()/vw и пр. — не сводятся к одному числу
	const key = rem.toFixed( 4 );
	if ( ! lengthMap.has( key ) ) lengthMap.set( key, [] );
	lengthMap.get( key ).push( { kind, token } );
};
for ( const f of settings.typography?.fontSizes || [] ) {
	addLength( f.size, 'font-size', `--wp--preset--font-size--${ f.slug }` );
}
for ( const s of settings.spacing?.spacingSizes || [] ) {
	addLength( s.size, 'spacing', `--wp--preset--spacing--${ s.slug }` );
}
for ( const [ name, val ] of Object.entries( settings.custom?.radius || {} ) ) {
	addLength( val, 'radius', `--wp--custom--radius--${ name }` );
}
for ( const [ name, val ] of Object.entries( settings.custom?.layout || {} ) ) {
	addLength( val, 'layout', `--wp--custom--layout--${ name }` );
}

// --- Режим резолва значений ------------------------------------------------
const resolveValue = ( raw ) => {
	const s = raw.trim();
	const hex = s.match( /^#[0-9a-fA-F]{3,8}$/ );
	if ( hex ) {
		const tokens = colorMap.get( normHex( s ) );
		return tokens
			? `${ s}  →  ${ tokens.map( ( t ) => `var(${ t })` ).join( '  |  ' ) }`
			: `${ s}  →  ✗ токена нет (хардкод цвета — заведи пресет в theme.json)`;
	}
	const rem = toRem( s );
	if ( rem !== null ) {
		const hits = lengthMap.get( rem.toFixed( 4 ) );
		if ( hits && hits.length ) {
			const list = hits.map( ( h ) => `var(${ h.token })  [${ h.kind }]` ).join( '  |  ' );
			return `${ s}  →  ${ list }`;
		}
		return `${ s}  →  ✗ токена нет (нестандартный размер — Hero допускает vw/fig())`;
	}
	return `${ s}  →  ? не значение цвета/длины (пропускаю)`;
};

// --- Режим скана SCSS на hex-литералы --------------------------------------
const scanScss = ( scanRel ) => {
	const dir = resolve( root, scanRel, 'blocks' );
	if ( ! existsSync( dir ) ) {
		console.error( `Нет каталога блоков: ${ scanRel }/blocks` );
		process.exit( 1 );
	}
	const files = [];
	const walk = ( d ) => {
		for ( const name of readdirSync( d ) ) {
			const p = resolve( d, name );
			if ( statSync( p ).isDirectory() ) {
				if ( name === 'build' ) continue;
				walk( p );
			} else if ( /\.(scss|css)$/.test( name ) ) {
				files.push( p );
			}
		}
	};
	walk( dir );

	let violations = 0; // hex c известным токеном — править обязательно
	let warnings = 0;   // неизвестный hex — на усмотрение (градиенты/alpha)
	const hexRe = /#[0-9a-fA-F]{3,8}\b/g;

	for ( const file of files ) {
		const rel = relative( root, file ).split( '\\' ).join( '/' );
		const lines = readFileSync( file, 'utf8' ).split( /\r?\n/ );
		let inBlockComment = false;
		lines.forEach( ( rawLine, i ) => {
			let line = rawLine;
			if ( inBlockComment ) {
				const end = line.indexOf( '*/' );
				if ( end === -1 ) return;
				line = line.slice( end + 2 );
				inBlockComment = false;
			}
			line = line.replace( /\/\*.*?\*\//g, '' );      // инлайн /* */
			const open = line.indexOf( '/*' );
			if ( open !== -1 ) { inBlockComment = true; line = line.slice( 0, open ); }
			line = line.replace( /\/\/.*$/, '' );             // // комментарий
			if ( line.includes( 'url(' ) ) line = line.replace( /url\([^)]*\)/g, '' ); // @import шрифтов

			const found = line.match( hexRe );
			if ( ! found ) return;
			for ( const hex of found ) {
				const tokens = colorMap.get( normHex( hex ) );
				if ( tokens ) {
					const sugg = tokens.map( ( t ) => `var(${ t })` ).join( ' | ' );
					console.log( `✗ ${ rel }:${ i + 1 }  ${ hex } → есть токен ${ sugg }, используй его` );
					violations++;
				} else {
					console.log( `⚠ ${ rel }:${ i + 1 }  ${ hex } — нет токена (alpha/градиент? иначе заведи пресет)` );
					warnings++;
				}
			}
		} );
	}

	if ( ! violations && ! warnings ) {
		console.log( `✅ Хардкод-цветов в SCSS не найдено (${ files.length } файлов).` );
	} else {
		console.log( `\nИтог: ${ violations } нарушение(й) с токеном, ${ warnings } warn (без токена).` );
	}
	process.exit( violations ? 1 : 0 );
};

// --- Точка входа -----------------------------------------------------------
const args = process.argv.slice( 2 );
if ( args[ 0 ] === '--scan' ) {
	scanScss( args[ 1 ] || 'projects/rosenberger/theme' );
} else if ( args.length ) {
	for ( const a of args ) console.log( resolveValue( a ) );
} else {
	console.log( 'Маппинг значений Figma → токены theme.json.\n' );
	console.log( '  node scripts/figma-tokens.mjs "#f8f5f3" 24px 1.5rem   — резолв значений' );
	console.log( '  node scripts/figma-tokens.mjs --scan                  — линт SCSS на hex' );
}
