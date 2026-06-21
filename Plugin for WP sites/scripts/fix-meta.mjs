import { readFileSync } from 'node:fs';
import { resolve, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = resolve( dirname( fileURLToPath( import.meta.url ) ), '..' );
const env = {};
for ( const line of readFileSync( resolve( root, '.env' ), 'utf8' ).split( /\r?\n/ ) ) {
	const m = line.match( /^([A-Z_]+)=(.*)$/ ); if ( m ) env[ m[ 1 ] ] = m[ 2 ];
}
const BASE = env.WP_URL.replace( /\/$/, '' );
const AUTH = 'Basic ' + Buffer.from( `${ env.WP_USER }:${ env.WP_APP_PASSWORD }` ).toString( 'base64' );

const api = ( path, opts = {} ) =>
	fetch( `${ BASE }${ path }`, {
		...opts,
		headers: { Authorization: AUTH, 'Content-Type': 'application/json', ...( opts.headers || {} ) },
	} );

// Записываем мета напрямую через Code Snippets
const pairs = [
	[ 929, 'Auf Anfrage', 'ca. 130 m²', '4',  'Verfügbar'  ],
	[ 930, '€ 680.000',   'ca. 220 m²', '6',  'Verfügbar'  ],
	[ 931, 'Auf Anfrage', 'ca. 85 m²',  '3',  'Reserviert' ],
	[ 932, '€ 1.250.000', 'ca. 180 m²', '5',  'Verfügbar'  ],
	[ 933, 'Auf Anfrage', 'ca. 800 m²', '',   'Verfügbar'  ],
	[ 934, '€ 520.000',   'ca. 160 m²', '5',  'Verfügbar'  ],
];

const phpLines = pairs.map( ( [ id, price, area, rooms, status ] ) =>
	`update_post_meta(${ id },'property_price',wp_slash('${ price.replace( /'/g, "\\'" ) }'));` +
	`update_post_meta(${ id },'property_area', wp_slash('${ area.replace( /'/g, "\\'" ) }'));` +
	`update_post_meta(${ id },'property_rooms','${ rooms }');` +
	`update_post_meta(${ id },'property_status','${ status }');`
).join( '\n' );

const code = phpLines;

// 1. Создаём активный сниппет
const res = await api( '/wp-json/code-snippets/v1/snippets', {
	method: 'POST',
	body: JSON.stringify( { title: 'FIX property meta', code, active: true, scope: 'global' } ),
} ).then( r => r.json() );

const snipId = res.id;
if ( ! snipId ) { console.error( 'Snippet error:', JSON.stringify( res ) ); process.exit( 1 ); }
console.log( `Snippet ${ snipId } created and active` );

// 2. Триггерим любую страницу WP чтобы сниппет сработал
const html = await fetch( BASE + '/objekte/?flush=' + Date.now() ).then( r => r.text() );
console.log( 'WP page triggered (length', html.length, ')' );

// 3. Проверяем get_post_meta напрямую через ещё один сниппет-читалку
const readCode = `
$pid = 929;
header('Content-Type: application/json');
echo json_encode([
  'property_price'  => get_post_meta($pid,'property_price',true),
  'property_area'   => get_post_meta($pid,'property_area',true),
  'property_rooms'  => get_post_meta($pid,'property_rooms',true),
  'property_status' => get_post_meta($pid,'property_status',true),
]);
`;

const readRes = await api( '/wp-json/code-snippets/v1/snippets', {
	method: 'POST',
	body: JSON.stringify( { title: 'READ property meta', code: readCode, active: true, scope: 'global' } ),
} ).then( r => r.json() );
const readId = readRes.id;
await fetch( BASE + '/?read_trigger=' + Date.now() );
console.log( 'Read snippet triggered' );

// 4. Деактивируем оба
for ( const id of [ snipId, readId ] ) {
	await api( `/wp-json/code-snippets/v1/snippets/${ id }`, {
		method: 'POST', body: JSON.stringify( { active: false, code: '' } ),
	} );
}
console.log( 'Both snippets deactivated' );

// 5. Проверяем через WP REST meta
const check = await api( '/wp-json/wp/v2/property/929?_fields=meta' ).then( r => r.json() );
console.log( 'REST meta:', JSON.stringify( check ) );
