// Батч-обёртка над qa-gate.mjs: морозит/проверяет ВСЕ секции из реестра
// qa-golden/sections.json одной командой. Регресс-сторож всей библиотеки даром.
//
//   node scripts/qa-all.mjs snapshot   → снять эталоны всех секций реестра
//   node scripts/qa-all.mjs check       → проверить все; exit 1 если где-то находки
//
// Добавил блок в sections.json → он автоматически попадает в обе команды.

import { execFileSync } from 'node:child_process';
import { readFileSync } from 'node:fs';
import { resolve, dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = resolve( dirname( fileURLToPath( import.meta.url ) ), '..' );
const cmd = process.argv[ 2 ];
if ( cmd !== 'snapshot' && cmd !== 'check' ) {
	console.error( 'Использование: qa-all.mjs <snapshot|check>' );
	process.exit( 2 );
}

const reg = JSON.parse( readFileSync( join( root, 'qa-golden', 'sections.json' ), 'utf8' ).replace( /^﻿/, '' ) );
const base = ( reg.base || '' ).replace( /\/$/, '' );

let fails = 0;
const failed = [];
for ( const s of reg.sections ) {
	const url = /^https?:/.test( s.url ) ? s.url : base + s.url;
	try {
		execFileSync( 'node', [ join( root, 'scripts', 'qa-gate.mjs' ), cmd, url, '--sel', s.sel, '--slug', s.slug ],
			{ stdio: [ 'ignore', 'ignore', 'inherit' ], maxBuffer: 64 * 1024 * 1024 } );
	} catch {
		fails++; failed.push( s.slug );      // qa-gate вышел с кодом 1 (находки) или 2 (ошибка)
	}
}

console.error( `\n──────────\n${ cmd === 'snapshot' ? 'Заморожено' : 'Проверено' }: ${ reg.sections.length } секций · ${ fails ? '❌ с находками/ошибкой: ' + failed.join( ', ' ) : '✅ все чисто' }` );
process.exit( fails ? 1 : 0 );
