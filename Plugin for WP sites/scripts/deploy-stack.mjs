// Деплой проекта на staging БЕЗ SFTP: блочная тема rosenberger (блоки внутри неё).
// Модель «копия в проект»: блоки живут в теме, отдельного плагина нет.
// Code Snippets — одноразовый установщик: пишет файлы темы, активирует тему,
// сносит старый плагin library-blocks, затем обезвреживается.
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

// --- файлы темы (включая blocks/ с build/ и assets/, без src/) ---
const themeDir = resolve( root, 'projects/rosenberger' );
const themeFiles = {};
for ( const abs of walk( themeDir ) ) {
	const rel = relative( themeDir, abs ).split( '\\' ).join( '/' );
	if ( rel.includes( '/src/' ) ) continue; // исходники на сервер не нужны
	themeFiles[ rel ] = readFileSync( abs ).toString( 'base64' );
}

const phpArray = ( obj ) => Object.entries( obj ).map( ( [ k, v ] ) => `\t'${ k }' => '${ v }',` ).join( '\n' );

const snippetCode = `$theme_dir = get_theme_root() . '/rosenberger';
$theme_files = array(
${ phpArray( themeFiles ) }
);
foreach ( $theme_files as $rel => $b64 ) { $d = $theme_dir . '/' . $rel; wp_mkdir_p( dirname( $d ) ); file_put_contents( $d, base64_decode( $b64 ) ); }
if ( get_option( 'stylesheet' ) !== 'rosenberger' ) { switch_theme( 'rosenberger' ); }
// Сносим старый общий плагин (модель сменилась на «блоки в теме»).
require_once ABSPATH . 'wp-admin/includes/plugin.php';
if ( is_plugin_active( 'library-blocks/library-blocks.php' ) ) { deactivate_plugins( 'library-blocks/library-blocks.php' ); }
$pd = WP_PLUGIN_DIR . '/library-blocks';
if ( is_dir( $pd ) ) {
	$it = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $pd, FilesystemIterator::SKIP_DOTS ), RecursiveIteratorIterator::CHILD_FIRST );
	foreach ( $it as $f ) { $f->isDir() ? @rmdir( $f->getPathname() ) : @unlink( $f->getPathname() ); }
	@rmdir( $pd );
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
	console.log( `Файлов темы: ${ Object.keys( themeFiles ).length }` );
	await neutralizeLibrarySnippets();

	const created = await api( '/wp-json/code-snippets/v1/snippets', {
		method: 'POST',
		body: JSON.stringify( {
			name: INSTALLER,
			desc: 'Одноразовый установщик темы. Обезвреживается автоматически.',
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
	const pluginGone = Array.isArray( plugins.body ) && ! plugins.body.some(
		( p ) => p.plugin === 'library-blocks/library-blocks' && p.status === 'active'
	);

	console.log( 'Тема rosenberger активна:', themeActive ? '✅' : '❌' );
	console.log( 'Плагин library-blocks убран:', pluginGone ? '✅' : '❌' );

	await neutralizeLibrarySnippets();

	if ( ! themeActive ) { console.log( '\n⚠️  Тема не активна — проверь вручную.' ); process.exit( 1 ); }
	console.log( '\n✅ Проект развёрнут: блоки внутри темы rosenberger. Плагин и сниппеты убраны.' );
	console.log( 'Тест-страница:', BASE + '/hero-cover-test/' );
};

main().catch( ( e ) => { console.error( e ); process.exit( 1 ); } );
