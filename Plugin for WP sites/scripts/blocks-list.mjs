// Печатает каталог блоков из block.json (library + тема проекта) — чтобы перед
// созданием нового блока ИИ гарантированно видел, что уже есть, и переиспользовал
// похожий, а не лепил с нуля. Источник — сами block.json (всегда актуально, в
// отличие от рукописного blocks-registry.md).
//
// Запуск: node scripts/blocks-list.mjs   (по умолчанию library + projects/rosenberger/theme)

import { readdirSync, existsSync, readFileSync } from 'node:fs';
import { resolve, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = resolve( dirname( fileURLToPath( import.meta.url ) ), '..' );
const roots = [ 'library', 'projects/rosenberger/theme' ];

for ( const rel of roots ) {
	const dir = resolve( root, rel, 'blocks' );
	if ( ! existsSync( dir ) ) continue;
	console.log( `\n=== ${ rel }/blocks ===` );
	for ( const slug of readdirSync( dir ).sort() ) {
		const bj = resolve( dir, slug, 'block.json' );
		if ( ! existsSync( bj ) ) continue;
		let m;
		try { m = JSON.parse( readFileSync( bj, 'utf8' ) ); } catch { continue; }
		const kw = ( m.keywords || [] ).join( ', ' );
		console.log( `• ${ slug }  [${ m.category || '?' }]  ${ m.title || '' }` );
		if ( m.description ) console.log( `    ${ m.description }` );
		if ( kw ) console.log( `    keywords: ${ kw }` );
	}
}
console.log( '\nПохож на нужный → копируй и допили (см. AGENTS.md §6). Нет → создавай новый.' );
