// Враппер для Claude Code хуков (PostToolUse / Stop). Кросс-платформенный (Node),
// чтобы не зависеть от шелла на Windows.
//
// PostToolUse (Write|Edit|MultiEdit): из stdin берёт tool_input.file_path,
//   и если файл внутри папки блока — гоняет lint-blocks --block по нему.
// Stop: при отсутствии file_path делает полный прогон lint-blocks.
//   Гард stop_hook_active защищает от зацикливания.
//
// Контракт хуков: exit 2 + текст в stderr → Claude видит ошибку и сам исправляет.
// При успехе — тихий exit 0 (без шума на обычных ходах диалога).

import { spawnSync } from 'node:child_process';
import { resolve, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';

const scriptsDir = dirname( fileURLToPath( import.meta.url ) );

let raw = '';
try {
	raw = await new Promise( ( res ) => {
		let buf = '';
		process.stdin.on( 'data', ( c ) => ( buf += c ) );
		process.stdin.on( 'end', () => res( buf ) );
		process.stdin.on( 'error', () => res( buf ) );
	} );
} catch { /* нет stdin — полный прогон */ }

let payload = {};
try { payload = JSON.parse( raw || '{}' ); } catch { payload = {}; }

// Защита от зацикливания Stop-хука.
if ( payload.stop_hook_active ) process.exit( 0 );

const filePath = payload?.tool_input?.file_path || null;

// Редактирование вне блока — не наша зона, выходим тихо.
if ( filePath && ! /[\\/]blocks[\\/]/.test( filePath ) ) process.exit( 0 );

const args = [ resolve( scriptsDir, 'lint-blocks.mjs' ) ];
if ( filePath ) args.push( '--block', filePath );

const r = spawnSync( process.execPath, args, { encoding: 'utf8' } );

if ( r.status !== 0 ) {
	process.stderr.write( ( r.stdout || '' ) + ( r.stderr || '' ) );
	process.stderr.write( '\n⛔ Структурные ошибки блока — исправь перед продолжением.\n' );
	process.exit( 2 );
}
process.exit( 0 );
