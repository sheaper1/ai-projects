// Безопасный апдейтер ОТДЕЛЬНЫХ файлов плагина propstack-real-estate на staging.
// Пишет только перечисленные файлы через одноразовый Code Snippet и обезвреживает
// его (как deploy-stack). Плагин НЕ активирует, ничего тяжёлого не делает —
// поэтому не роняет сайт (в отличие от прежнего установщика всего плагина).
//
// Запуск: node scripts/push-propstack-files.mjs

import { readFileSync } from 'node:fs';
import { resolve, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = resolve( dirname( fileURLToPath( import.meta.url ) ), '..' );
const env = {};
for ( const line of readFileSync( resolve( root, '.env' ), 'utf8' ).split( /\r?\n/ ) ) {
	const m = line.match( /^([A-Z_]+)=(.*)$/ );
	if ( m ) env[ m[ 1 ] ] = m[ 2 ];
}
const BASE = env.WP_URL.replace( /\/$/, '' );
const AUTH = 'Basic ' + Buffer.from( `${ env.WP_USER }:${ env.WP_APP_PASSWORD }` ).toString( 'base64' );
const api = async ( path, opts = {} ) => {
	const res = await fetch( `${ BASE }${ path }`, { ...opts, headers: { Authorization: AUTH, 'Content-Type': 'application/json', ...( opts.headers || {} ) } } );
	const text = await res.text();
	let body; try { body = JSON.parse( text ); } catch { body = text; }
	return { status: res.status, body };
};

// Относительные пути внутри плагина, которые обновляем.
const FILES = [
	'includes/class-api-client.php',
	'includes/class-field-mapper.php',
	'includes/class-sync-service.php',
];
const pluginDir = resolve( root, 'projects/rosenberger/plugin/propstack-real-estate' );
const entries = FILES.map( ( rel ) => `\t'${ rel }' => '${ readFileSync( resolve( pluginDir, rel ) ).toString( 'base64' ) }',` ).join( '\n' );

const code = `$dir = WP_PLUGIN_DIR . '/propstack-real-estate';
$files = array(
${ entries }
);
foreach ( $files as $rel => $b64 ) { $d = $dir . '/' . $rel; if ( is_dir( dirname( $d ) ) ) { file_put_contents( $d, base64_decode( $b64 ) ); } }`;

const NAME = 'Library: PROPSTACK file update (temporary)';
const neutralize = async () => {
	const list = await api( '/wp-json/code-snippets/v1/snippets' );
	if ( ! Array.isArray( list.body ) ) return;
	for ( const s of list.body ) {
		if ( s.name === NAME && ( s.active || s.code !== '// removed' ) ) {
			await api( `/wp-json/code-snippets/v1/snippets/${ s.id }`, { method: 'POST', body: JSON.stringify( { active: false, code: '// removed' } ) } );
			console.log( `Обезврежен сниппет #${ s.id }` );
		}
	}
};

const main = async () => {
	await neutralize();
	const created = await api( '/wp-json/code-snippets/v1/snippets', {
		method: 'POST',
		body: JSON.stringify( { name: NAME, desc: 'Одноразовое обновление файлов плагина Propstack. Обезвреживается автоматически.', code, scope: 'global', active: true } ),
	} );
	console.log( 'Сниппет создан:', created.status, 'id=', created.body && created.body.id );
	if ( ! ( created.body && created.body.id ) ) { throw new Error( 'Не удалось создать сниппет' ); }
	// Дать сниппету выполниться на загрузке страницы.
	const sleep = ( ms ) => new Promise( ( r ) => setTimeout( r, ms ) );
	for ( let i = 0; i < 5; i++ ) { await fetch( BASE + '/' ).catch( () => {} ); await sleep( 1200 ); }
	await neutralize();
	console.log( '✅ Файлы плагина обновлены, сниппет обезврежен.' );
};
main().catch( ( e ) => { console.error( e ); process.exit( 1 ); } );
