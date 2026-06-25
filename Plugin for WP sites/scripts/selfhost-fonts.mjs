// Самохостинг Google Fonts (DSGVO: не отдаём IP в Google).
// Качает woff2 нужных подмножеств (latin + latin-ext) и пишет локальный fonts.css.
// Запуск: node scripts/selfhost-fonts.mjs
import { mkdir, writeFile } from 'node:fs/promises';
import { execSync } from 'node:child_process';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const ROOT = path.resolve( fileURLToPath( import.meta.url ), '../..' );
const FONT_DIR = path.join( ROOT, 'projects/rosenberger/theme/assets/fonts' );
const CSS_OUT = path.join( ROOT, 'projects/rosenberger/theme/assets/css/fonts.css' );
const UA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0 Safari/537.36';
const KEEP = new Set( [ 'latin', 'latin-ext' ] ); // немецкий сайт: кириллица/вьетнам не нужны

const FAMILIES = [
	{
		name: 'Nunito Sans',
		slug: 'nunito-sans',
		url: 'https://fonts.googleapis.com/css2?family=Nunito+Sans:ital,wght@0,400;0,500;0,600;0,700;1,300&display=swap',
	},
	{
		name: 'Roboto Flex',
		slug: 'roboto-flex',
		url: 'https://fonts.googleapis.com/css2?family=Roboto+Flex:wght@400;500;600;800&display=swap',
	},
];

const fetchCss = ( url ) => execSync( `curl -s -A "${ UA }" "${ url }"`, { encoding: 'utf8' } );

// Разбираем CSS на блоки @font-face, цепляя комментарий-подмножество перед блоком.
function parseFaces( css ) {
	const faces = [];
	const re = /\/\*\s*([a-z-]+)\s*\*\/\s*@font-face\s*{([^}]+)}/g;
	let m;
	while ( ( m = re.exec( css ) ) ) {
		const subset = m[ 1 ];
		const body = m[ 2 ];
		const weight = ( body.match( /font-weight:\s*([0-9]+)/ ) || [] )[ 1 ];
		const style = ( body.match( /font-style:\s*([a-z]+)/ ) || [] )[ 1 ] || 'normal';
		const src = ( body.match( /url\((https:\/\/[^)]+\.woff2)\)/ ) || [] )[ 1 ];
		const range = ( body.match( /unicode-range:\s*([^;]+);/ ) || [] )[ 1 ];
		const stretch = ( body.match( /font-stretch:\s*([^;]+);/ ) || [] )[ 1 ];
		if ( src && weight ) faces.push( { subset, weight, style, src, range, stretch } );
	}
	return faces;
}

await mkdir( FONT_DIR, { recursive: true } );
await mkdir( path.dirname( CSS_OUT ), { recursive: true } );

let cssBlocks = [ '/* Локально захостенные шрифты бренда (DSGVO: без Google CDN). Генерится scripts/selfhost-fonts.mjs */\n' ];

for ( const fam of FAMILIES ) {
	const faces = parseFaces( fetchCss( fam.url ) ).filter( ( f ) => KEEP.has( f.subset ) );
	console.log( `${ fam.name }: ${ faces.length } граней (latin/latin-ext)` );
	for ( const f of faces ) {
		const file = `${ fam.slug }-${ f.weight }-${ f.style }-${ f.subset }.woff2`;
		execSync( `curl -s -o "${ path.join( FONT_DIR, file ) }" "${ f.src }"` );
		cssBlocks.push(
			`@font-face {\n` +
			`  font-family: '${ fam.name }';\n` +
			`  font-style: ${ f.style };\n` +
			`  font-weight: ${ f.weight };\n` +
			( f.stretch ? `  font-stretch: ${ f.stretch };\n` : '' ) +
			`  font-display: swap;\n` +
			`  src: url('../fonts/${ file }') format('woff2');\n` +
			( f.range ? `  unicode-range: ${ f.range };\n` : '' ) +
			`}`
		);
	}
}

await writeFile( CSS_OUT, cssBlocks.join( '\n' ) + '\n', 'utf8' );
console.log( `\n✅ fonts.css → ${ path.relative( ROOT, CSS_OUT ) }` );
