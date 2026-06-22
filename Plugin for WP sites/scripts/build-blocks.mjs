// Сборка всех блоков в указанном каталоге (<root>/blocks/<slug>/src -> .../build).
// Запуск: node scripts/build-blocks.mjs [relRoot] [--force]
//   по умолчанию: projects/rosenberger/theme
//   эталоны:      node scripts/build-blocks.mjs library
//
// Инкрементально: блок пересобирается только если его src новее build.
// Флаг --force пересобирает всё.

import { readdirSync, existsSync, statSync } from 'node:fs';
import { execSync } from 'node:child_process';
import { resolve, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = resolve( dirname( fileURLToPath( import.meta.url ) ), '..' );
const args = process.argv.slice( 2 );
const force = args.includes( '--force' );
const rel = args.find( ( a ) => ! a.startsWith( '--' ) ) || 'projects/rosenberger/theme';
const blocksDir = resolve( root, rel, 'blocks' );

if ( ! existsSync( blocksDir ) ) {
	console.error( `Нет каталога блоков: ${ rel }/blocks` );
	process.exit( 1 );
}

// Самый свежий mtime среди файлов каталога (рекурсивно). 0 если каталога нет/пуст.
const newestMtime = ( dir ) => {
	if ( ! existsSync( dir ) ) return 0;
	let max = 0;
	for ( const name of readdirSync( dir ) ) {
		const p = resolve( dir, name );
		const st = statSync( p );
		const m = st.isDirectory() ? newestMtime( p ) : st.mtimeMs;
		if ( m > max ) max = m;
	}
	return max;
};

let built = 0;
let skipped = 0;
for ( const name of readdirSync( blocksDir ) ) {
	const srcAbs = resolve( blocksDir, name, 'src' );
	if ( ! existsSync( srcAbs ) ) continue;
	const outAbs = resolve( blocksDir, name, 'build' );

	if ( ! force && existsSync( outAbs ) && newestMtime( outAbs ) >= newestMtime( srcAbs ) ) {
		console.log( `· Пропуск ${ name } (build актуален)` );
		skipped++;
		continue;
	}

	const src = `${ rel }/blocks/${ name }/src`;
	const out = `${ rel }/blocks/${ name }/build`;
	console.log( `\n▶ Сборка ${ name }` );
	execSync(
		`npx wp-scripts build --webpack-src-dir=${ src } --output-path=${ out }`,
		{ cwd: root, stdio: 'inherit' }
	);
	built++;
}
console.log( `\n✅ Готово (собрано ${ built }, пропущено ${ skipped }). Полная пересборка: --force` );
