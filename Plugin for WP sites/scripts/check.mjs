// Предполётные проверки перед коммитом/пушем (страховка для обоих ИИ).
//   1. В индексе/трекинге нет запретного: .env, build/, node_modules/, ключи, кэш деплоя.
//   2. php -l на изменённых PHP (если php доступен; иначе предупреждение, не ошибка).
//
// Запуск:  node scripts/check.mjs            — изменения против HEAD + staged
//          node scripts/check.mjs --range origin/main..HEAD   — диапазон (для pre-push)

import { execSync } from 'node:child_process';
import { resolve } from 'node:path';

const run = ( cmd ) => execSync( cmd, { encoding: 'utf8' } ).trim();
const runSafe = ( cmd ) => { try { return run( cmd ); } catch { return ''; } };

// Git-пути отсчитываются от корня репозитория (он может быть выше cwd проекта).
const repoRoot = runSafe( 'git rev-parse --show-toplevel' ) || process.cwd();

const args = process.argv.slice( 2 );
const rangeIdx = args.indexOf( '--range' );
const range = rangeIdx >= 0 ? args[ rangeIdx + 1 ] : null;

let problems = 0;
const fail = ( msg ) => { console.error( `✗ ${ msg }` ); problems++; };

// --- 1. Запретные пути среди отслеживаемых файлов -------------------------
const FORBIDDEN = [
	/(^|\/)\.env($|\.)/,
	/(^|\/)build\//,
	/(^|\/)node_modules\//,
	/(^|\/)\.deploy-cache\.json$/,
	/(^|\/)\.figma-tmp\//,
	/\.(key|pem|p12)$/,
	/(^|\/)wp-config\.php$/,
];
const tracked = runSafe( 'git ls-files' ).split( '\n' ).filter( Boolean );
for ( const f of tracked ) {
	if ( FORBIDDEN.some( ( re ) => re.test( f ) ) ) fail( `запретный файл в git: ${ f }` );
}

// --- 2. php -l на изменённых PHP -----------------------------------------
const changed = ( range
	? runSafe( `git diff --name-only ${ range }` )
	: [ runSafe( 'git diff --name-only HEAD' ), runSafe( 'git diff --cached --name-only' ) ].join( '\n' )
).split( '\n' ).filter( Boolean );
const phpFiles = [ ...new Set( changed.filter( ( f ) => f.endsWith( '.php' ) ) ) ];

const phpAvailable = runSafe( 'php -v' ) !== '';
if ( phpFiles.length && ! phpAvailable ) {
	console.warn( `⚠ php не найден — пропускаю php -l (${ phpFiles.length } файл(ов)).` );
} else {
	for ( const f of phpFiles ) {
		try {
			execSync( `php -l "${ resolve( repoRoot, f ) }"`, { stdio: 'pipe' } );
		} catch ( e ) {
			fail( `php -l: ${ f }\n${ ( e.stdout || e.stderr || '' ).toString().trim() }` );
		}
	}
}

if ( problems ) {
	console.error( `\n❌ Проверки не пройдены (${ problems }). Push/commit лучше остановить.` );
	process.exit( 1 );
}
console.log( `✅ Проверки пройдены (PHP: ${ phpFiles.length }, tracked: ${ tracked.length }).` );
