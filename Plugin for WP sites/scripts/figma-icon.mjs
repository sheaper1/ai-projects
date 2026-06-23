// Чистит SVG-экспорт иконки из Figma (download_assets defaultFormat=svg).
// Экспорт под-ноды Figma тащит за собой фон холста (rect fill="#1E1E1E") и
// геометрию родительских фреймов/карточек (большие rect). Скрипт убирает этот
// мусор, оставляя саму иконку — чтобы не делать это вручную.
//
// Запуск: node scripts/figma-icon.mjs path/to/icon.svg [ещё.svg ...]
// Правит файлы на месте (перезаписывает почищенным).

import { readFileSync, writeFileSync } from 'node:fs';

const files = process.argv.slice( 2 );
if ( ! files.length ) {
	console.error( 'Укажи SVG-файл(ы): node scripts/figma-icon.mjs media/icons/x.svg' );
	process.exit( 1 );
}

for ( const file of files ) {
	let svg = readFileSync( file, 'utf8' );
	const before = svg.length;

	// 1. Убрать фон холста Figma и крупные rect'ы родительских фреймов/карточек
	//    (ширина >= 200 = не иконка). Самозакрывающиеся <rect .../>.
	svg = svg.replace( /<rect\b[^>]*\/>/g, ( tag ) => {
		if ( /fill="#1E1E1E"/i.test( tag ) ) return '';
		const w = tag.match( /\bwidth="([\d.]+)"/ );
		if ( w && parseFloat( w[ 1 ] ) >= 200 ) return '';
		const h = tag.match( /\bheight="([\d.]+)"/ );
		if ( h && parseFloat( h[ 1 ] ) >= 200 ) return '';
		return tag;
	} );

	// 2. Схлопнуть пустые группы-обёртки (без атрибутов transform/clip) и пустые строки.
	let prev;
	do {
		prev = svg;
		svg = svg.replace( /<g\b(?:\s+id="[^"]*")?\s*>\s*<\/g>/g, '' );
	} while ( svg !== prev );
	svg = svg.replace( /\n\s*\n+/g, '\n' );

	writeFileSync( file, svg );
	console.log( `✓ ${ file }: ${ before } → ${ svg.length } байт` );
}
