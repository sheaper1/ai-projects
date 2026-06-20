// Деплой проекта на staging БЕЗ SFTP: блочная тема rosenberger (вид, блоки внутри)
// + плагин проекта rosenberger-core (данные/логика: настройки, CPT).
// Модель «копия в проект»: всё per-project и изолировано.
//
// Code Snippets — одноразовый установщик: пишет файлы темы и плагина, активирует
// тему и плагин, сносит старый общий плагин library-blocks, затем обезвреживается.
//
// Запуск: node scripts/deploy-stack.mjs

import { readFileSync, readdirSync, statSync } from 'node:fs';
import { resolve, dirname, relative } from 'node:path';
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
	const res = await fetch( `${ BASE }${ path }`, {
		...opts,
		headers: { Authorization: AUTH, 'Content-Type': 'application/json', ...( opts.headers || {} ) },
	} );
	const text = await res.text();
	let body; try { body = JSON.parse( text ); } catch { body = text; }
	return { status: res.status, body };
};

const walk = ( dir ) => readdirSync( dir ).flatMap( ( name ) => {
	const p = resolve( dir, name );
	return statSync( p ).isDirectory() ? walk( p ) : [ p ];
} );

// собрать файлы каталога в { относительный_путь: base64 }, пропуская src/
const collect = ( dir ) => {
	const out = {};
	for ( const abs of walk( dir ) ) {
		const rel = relative( dir, abs ).split( '\\' ).join( '/' );
		if ( rel.includes( '/src/' ) ) continue;
		out[ rel ] = readFileSync( abs ).toString( 'base64' );
	}
	return out;
};

const themeFiles  = collect( resolve( root, 'projects/rosenberger/theme' ) );
const pluginFiles = collect( resolve( root, 'projects/rosenberger/plugin/rosenberger-core' ) );

const phpArray = ( obj ) => Object.entries( obj ).map( ( [ k, v ] ) => `\t'${ k }' => '${ v }',` ).join( '\n' );

const snippetCode = `$theme_dir  = get_theme_root() . '/rosenberger';
$plugin_dir = WP_PLUGIN_DIR . '/rosenberger-core';
$theme_files = array(
${ phpArray( themeFiles ) }
);
$plugin_files = array(
${ phpArray( pluginFiles ) }
);
foreach ( $theme_files as $rel => $b64 ) { $d = $theme_dir . '/' . $rel; wp_mkdir_p( dirname( $d ) ); file_put_contents( $d, base64_decode( $b64 ) ); }
foreach ( $plugin_files as $rel => $b64 ) { $d = $plugin_dir . '/' . $rel; wp_mkdir_p( dirname( $d ) ); file_put_contents( $d, base64_decode( $b64 ) ); }
if ( get_option( 'stylesheet' ) !== 'rosenberger' ) { switch_theme( 'rosenberger' ); }
require_once ABSPATH . 'wp-admin/includes/plugin.php';
if ( ! is_plugin_active( 'rosenberger-core/rosenberger-core.php' ) ) { activate_plugin( 'rosenberger-core/rosenberger-core.php' ); }
// Разовый посев примера контактов (не перезатирает правки клиента).
if ( false === get_option( 'rosenberger_contacts' ) ) {
	add_option( 'rosenberger_contacts', array(
		'phone'    => '+43 5572 123456',
		'email'    => 'office@rosenberger.at',
		'address'  => 'Bregenz, Vorarlberg',
		'hours'    => 'Mo-Fr 9:00-17:00',
		'cta_text' => 'Kontakt',
		'cta_url'  => '/kontakt/',
	) );
}
// сносим старый общий плагин (модель сменилась на «всё в проекте»)
if ( is_plugin_active( 'library-blocks/library-blocks.php' ) ) { deactivate_plugins( 'library-blocks/library-blocks.php' ); }
$old = WP_PLUGIN_DIR . '/library-blocks';
if ( is_dir( $old ) ) {
	$it = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $old, FilesystemIterator::SKIP_DOTS ), RecursiveIteratorIterator::CHILD_FIRST );
	foreach ( $it as $f ) { $f->isDir() ? @rmdir( $f->getPathname() ) : @unlink( $f->getPathname() ); }
	@rmdir( $old );
}`;

const INSTALLER = 'Library: STACK installer (temporary)';

const neutralizeLibrarySnippets = async () => {
	const list = await api( '/wp-json/code-snippets/v1/snippets' );
	if ( ! Array.isArray( list.body ) ) return;
	for ( const s of list.body ) {
		if ( s.name && s.name.startsWith( 'Library:' ) && ( s.active || s.code !== '// removed' ) ) {
			await api( `/wp-json/code-snippets/v1/snippets/${ s.id }`, {
				method: 'POST',
				body: JSON.stringify( { active: false, code: '// removed' } ),
			} );
			console.log( `Обезврежен сниппет #${ s.id } (${ s.name })` );
		}
	}
};

const main = async () => {
	console.log( `Файлов темы: ${ Object.keys( themeFiles ).length }, файлов плагина: ${ Object.keys( pluginFiles ).length }` );
	await neutralizeLibrarySnippets();

	const created = await api( '/wp-json/code-snippets/v1/snippets', {
		method: 'POST',
		body: JSON.stringify( {
			name: INSTALLER,
			desc: 'Одноразовый установщик темы и плагина проекта. Обезвреживается автоматически.',
			code: snippetCode,
			scope: 'global',
			active: true,
		} ),
	} );
	console.log( 'Установщик создан:', created.status, 'id=', created.body && created.body.id );
	if ( ! ( created.body && created.body.id ) ) { console.log( JSON.stringify( created.body ).slice( 0, 400 ) ); throw new Error( 'Не удалось создать установщик' ); }

	await fetch( BASE + '/' );
	await new Promise( ( r ) => setTimeout( r, 1500 ) );
	await fetch( BASE + '/' );

	const themes = await api( '/wp-json/wp/v2/themes?status=active' );
	const themeActive = Array.isArray( themes.body ) && themes.body.some( ( t ) => t.stylesheet === 'rosenberger' );
	const plugins = await api( '/wp-json/wp/v2/plugins' );
	const coreActive = Array.isArray( plugins.body ) && plugins.body.some(
		( p ) => p.plugin === 'rosenberger-core/rosenberger-core' && p.status === 'active'
	);

	console.log( 'Тема rosenberger активна:', themeActive ? '✅' : '❌' );
	console.log( 'Плагин rosenberger-core активен:', coreActive ? '✅' : '❌' );

	await neutralizeLibrarySnippets();

	if ( ! themeActive || ! coreActive ) { console.log( '\n⚠️  Что-то не активировалось — проверь вручную.' ); process.exit( 1 ); }
	console.log( '\n✅ Проект развёрнут: тема rosenberger + плагин rosenberger-core. Сниппеты обезврежены.' );
	console.log( 'Настройки сайта: ' + BASE + '/wp-admin/admin.php?page=rosenberger-settings' );
	console.log( 'Тест-страница:   ' + BASE + '/hero-cover-test/' );
};

main().catch( ( e ) => { console.error( e ); process.exit( 1 ); } );
