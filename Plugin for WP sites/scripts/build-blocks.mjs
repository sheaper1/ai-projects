// Сборка всех блоков в указанном каталоге (<root>/blocks/<slug>/src -> .../build).
// Запуск: node scripts/build-blocks.mjs [relRoot]
//   по умолчанию: projects/rosenberger
//   эталоны:      node scripts/build-blocks.mjs library

import { readdirSync, existsSync } from 'node:fs';
import { execSync } from 'node:child_process';
import { resolve, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = resolve( dirname( fileURLToPath( import.meta.url ) ), '..' );
const rel = process.argv[ 2 ] || 'projects/rosenberger';
const blocksDir = resolve( root, rel, 'blocks' );

if ( ! existsSync( blocksDir ) ) {
	console.error( `Нет каталога блоков: ${ rel }/blocks` );
	process.exit( 1 );
}

for ( const name of readdirSync( blocksDir ) ) {
	const src = `${ rel }/blocks/${ name }/src`;
	if ( ! existsSync( resolve( root, src ) ) ) continue;
	const out = `${ rel }/blocks/${ name }/build`;
	console.log( `\n▶ Сборка ${ name }` );
	execSync(
		`npx wp-scripts build --webpack-src-dir=${ src } --output-path=${ out }`,
		{ cwd: root, stdio: 'inherit' }
	);
}
console.log( '\n✅ Готово' );
