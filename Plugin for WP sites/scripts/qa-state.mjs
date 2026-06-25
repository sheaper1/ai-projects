// Кэш состояния QA: «прошла ли секция/блок полный QA при ТЕКУЩЕМ коде».
// Ключ — хэш (исходник блока + theme.json проекта). Поменялся блок ИЛИ глобальный
// токен → хэш слетел → нужен полный QA заново. Так упрощённый QA на неизменном
// общем блоке безопасен: глобальное уже проверено, регресс по токенам не пропустим.
//
// Команды:
//   node scripts/qa-state.mjs check <slug>          → FULL | SIMPLE (по совпадению хэша)
//   node scripts/qa-state.mjs pass  <slug> [--page p] → записать текущий хэш как «прошло»
//   node scripts/qa-state.mjs list                  → все записи
//
// Состояние: .qa-state.json в корне проекта (коммитим — оно общее, как и блоки).

import { createHash } from 'node:crypto';
import { readFileSync, writeFileSync, existsSync, readdirSync, statSync } from 'node:fs';
import { resolve, dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = resolve( dirname( fileURLToPath( import.meta.url ) ), '..' );
const STATE = resolve( root, '.qa-state.json' );

const [ cmd, slug ] = process.argv.slice( 2 );
const pageArg = ( () => { const i = process.argv.indexOf( '--page' ); return i >= 0 ? process.argv[ i + 1 ] : null; } )();

const load = () => { try { return JSON.parse( readFileSync( STATE, 'utf8' ) ); } catch { return {}; } };
const save = ( o ) => writeFileSync( STATE, JSON.stringify( o, null, 2 ) + '\n' );

// Найти папку блока по slug в активных проектах.
function blockDir( s ) {
	const projects = join( root, 'projects' );
	if ( ! existsSync( projects ) ) return null;
	for ( const p of readdirSync( projects ) ) {
		const d = join( projects, p, 'theme', 'blocks', s );
		if ( existsSync( join( d, 'block.json' ) ) ) return { dir: d, theme: join( projects, p, 'theme', 'theme.json' ) };
	}
	return null;
}

// Собрать все значимые файлы блока (рекурсивно src/ + корневые) + theme.json → хэш.
function collectFiles( dir ) {
	const files = [];
	const walk = ( d ) => {
		for ( const name of readdirSync( d ).sort() ) {
			if ( name === 'build' || name === 'node_modules' ) continue;
			const full = join( d, name );
			if ( statSync( full ).isDirectory() ) walk( full );
			else files.push( full );
		}
	};
	walk( dir );
	return files;
}

function hashBlock( s ) {
	const b = blockDir( s );
	if ( ! b ) return null;
	const h = createHash( 'sha256' );
	for ( const f of collectFiles( b.dir ) ) { h.update( f.replace( root, '' ) ); h.update( readFileSync( f ) ); }
	if ( existsSync( b.theme ) ) h.update( readFileSync( b.theme ) );   // глобальные токены
	return h.digest( 'hex' ).slice( 0, 16 );
}

if ( cmd === 'list' ) {
	const st = load();
	const keys = Object.keys( st );
	if ( ! keys.length ) { console.log( 'qa-state: записей нет.' ); process.exit( 0 ); }
	for ( const k of keys.sort() ) console.log( `  ${ k }  ${ st[ k ].hash }  ${ st[ k ].passedAt }${ st[ k ].page ? '  @' + st[ k ].page : '' }` );
	process.exit( 0 );
}

if ( ! slug ) { console.error( 'Укажи slug. Пример: node scripts/qa-state.mjs check pain-points' ); process.exit( 1 ); }
const cur = hashBlock( slug );
if ( ! cur ) { console.error( `Блок не найден: ${ slug }` ); process.exit( 1 ); }

if ( cmd === 'check' ) {
	const rec = load()[ slug ];
	if ( rec && rec.hash === cur ) { console.log( `SIMPLE  (${ slug } прошёл полный QA при этом коде: ${ rec.passedAt }) → упрощённый QA, глобальное пропускаем` ); }
	else { console.log( `FULL    (${ slug } ${ rec ? 'изменён с прошлого QA' : 'не проходил QA' }) → полный QA` ); }
	process.exit( 0 );
}

if ( cmd === 'pass' ) {
	const st = load();
	st[ slug ] = { hash: cur, passedAt: new Date().toISOString().slice( 0, 10 ), page: pageArg || undefined };
	save( st );
	console.log( `✅ ${ slug } отмечен «прошёл полный QA» (hash ${ cur }${ pageArg ? ', стр. ' + pageArg : '' }).` );
	process.exit( 0 );
}

console.error( 'Команды: check <slug> | pass <slug> [--page p] | list' );
process.exit( 1 );
