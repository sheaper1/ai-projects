// Полная установка стека на staging БЕЗ SFTP: плагин library-blocks + тема rosenberger.
// Code Snippets используется как ОДНОРАЗОВЫЙ установщик: пишет файлы, активирует
// плагин и тему, после чего сниппет удаляется. В рабочем состоянии — только плагин и тема.
//
// Запуск: node scripts/deploy-stack.mjs

import { readFileSync, readdirSync, statSync, existsSync } from 'node:fs';
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

const toRel = ( base, abs ) => relative( base, abs ).split( '\\' ).join( '/' );

// --- файлы плагина: main php + все блоки (без src/) ---
const pluginFiles = {};
pluginFiles[ 'library-blocks.php' ] = readFileSync( resolve( root, 'library/library-blocks.php' ) ).toString( 'base64' );
const blocksDir = resolve( root, 'library/blocks' );
for ( const abs of walk( blocksDir ) ) {
	const rel = toRel( blocksDir, abs );
	if ( rel.includes( '/src/' ) ) continue; // исходники на сервер не нужны
	pluginFiles[ `blocks/${ rel }` ] = readFileSync( abs ).toString( 'base64' );
}

// --- файлы темы ---
const themeFiles = {};
const themeDir = resolve( root, 'projects/rosenberger' );
for ( const abs of walk( themeDir ) ) {
	themeFiles[ toRel( themeDir, abs ) ] = readFileSync( abs ).toString( 'base64' );
}

const phpArray = ( obj ) => Object.entries( obj ).map( ( [ k, v ] ) => `\t'${ k }' => '${ v }',` ).join( '\n' );

const snippetCode = `$plugin_dir = WP_PLUGIN_DIR . '/library-blocks';
$theme_dir  = get_theme_root() . '/rosenberger';
$plugin_files = array(
${ phpArray( pluginFiles ) }
);
$theme_files = array(
${ phpArray( themeFiles ) }
);
foreach ( $plugin_files as $rel => $b64 ) { $d = $plugin_dir . '/' . $rel; wp_mkdir_p( dirname( $d ) ); file_put_contents( $d, base64_decode( $b64 ) ); }
foreach ( $theme_files as $rel => $b64 ) { $d = $theme_dir . '/' . $rel; wp_mkdir_p( dirname( $d ) ); file_put_contents( $d, base64_decode( $b64 ) ); }
require_once ABSPATH . 'wp-admin/includes/plugin.php';
if ( ! is_plugin_active( 'library-blocks/library-blocks.php' ) ) { activate_plugin( 'library-blocks/library-blocks.php' ); }
if ( get_option( 'stylesheet' ) !== 'rosenberger' ) { switch_theme( 'rosenberger' ); }`;

const INSTALLER = 'Library: STACK installer (temporary)';

// В этой версии Code Snippets DELETE через REST не работает (500), поэтому наши
// сниппеты «обезвреживаем»: деактивируем и очищаем код (становятся инертными).
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
	console.log( `Файлов плагина: ${ Object.keys( pluginFiles ).length }, файлов темы: ${ Object.keys( themeFiles ).length }` );

	await neutralizeLibrarySnippets();

	const created = await api( '/wp-json/code-snippets/v1/snippets', {
		method: 'POST',
		body: JSON.stringify( {
			name: INSTALLER,
			desc: 'Одноразовый установщик плагина и темы. Удаляется автоматически.',
			code: snippetCode,
			scope: 'global',
			active: true,
		} ),
	} );
	console.log( 'Установщик создан:', created.status, 'id=', created.body && created.body.id );
	if ( ! ( created.body && created.body.id ) ) { console.log( JSON.stringify( created.body ).slice( 0, 400 ) ); throw new Error( 'Не удалось создать установщик' ); }

	// триггерим выполнение (сниппет активен — отрабатывает на загрузке)
	await fetch( BASE + '/' );
	await new Promise( ( r ) => setTimeout( r, 1500 ) );
	await fetch( BASE + '/' );

	// проверка
	const plugins = await api( '/wp-json/wp/v2/plugins' );
	const pluginActive = Array.isArray( plugins.body ) && plugins.body.some(
		( p ) => p.plugin === 'library-blocks/library-blocks' && p.status === 'active'
	);
	const themes = await api( '/wp-json/wp/v2/themes?status=active' );
	const themeActive = Array.isArray( themes.body ) && themes.body.some( ( t ) => t.stylesheet === 'rosenberger' );

	console.log( 'Плагин library-blocks активен:', pluginActive ? '✅' : '❌' );
	console.log( 'Тема rosenberger активна:', themeActive ? '✅' : '❌' );

	// чистим установщик и устаревшие пер-блочные сниппеты
	await neutralizeLibrarySnippets();

	if ( ! pluginActive || ! themeActive ) {
		console.log( '\n⚠️  Что-то не активировалось — проверь вручную.' );
		process.exit( 1 );
	}
	console.log( '\n✅ Стек установлен: плагин + тема. Code Snippets очищен.' );
	console.log( 'Тест-страница:', BASE + '/hero-cover-test/' );
};

main().catch( ( e ) => { console.error( e ); process.exit( 1 ); } );
