// Ставит и активирует Complianz (cookie-consent для AT/DE) одноразовым Code Snippet.
// Фазы (сниппет global-scope, прогоняется на нескольких загрузках):
//   1) установка плагина complianz-gdpr из wp.org, 2) активация,
//   3) базовый конфиг: регион EU (DSGVO), opt-in.
// Финальный визард (регион Österreich, генерация Datenschutz/Cookie-доков) — в WP-админке.
// Запуск: node scripts/setup-complianz.mjs
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
	const res = await fetch( `${ BASE }${ path }`, {
		...opts,
		headers: { Authorization: AUTH, 'Content-Type': 'application/json', ...( opts.headers || {} ) },
	} );
	const text = await res.text();
	let body; try { body = JSON.parse( text ); } catch { body = text; }
	return { status: res.status, ok: res.ok, body };
};
const sleep = ( ms ) => new Promise( ( r ) => setTimeout( r, ms ) );
const SNIPPET = 'Library: complianz install (temporary)';

const phpCode = `
if ( ! function_exists( 'get_plugins' ) ) require_once ABSPATH . 'wp-admin/includes/plugin.php';
$slug = 'complianz-gdpr';
$file = '';
foreach ( get_plugins() as $f => $d ) { if ( strpos( $f, $slug . '/' ) === 0 ) { $file = $f; break; } }

// Фаза 1: установка из wp.org
if ( ! $file ) {
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/misc.php';
	require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
	require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
	$api = plugins_api( 'plugin_information', array( 'slug' => $slug, 'fields' => array( 'sections' => false ) ) );
	if ( ! is_wp_error( $api ) && ! empty( $api->download_link ) ) {
		$up = new Plugin_Upgrader( new Automatic_Upgrader_Skin() );
		$up->install( $api->download_link );
		update_option( 'rosenberger_cmplz_log', 'installed ' . gmdate( 'c' ) );
	} else {
		update_option( 'rosenberger_cmplz_log', 'install_failed: ' . ( is_wp_error( $api ) ? $api->get_error_message() : 'no download_link' ) );
	}
	return;
}

// Фаза 2: активация
if ( ! is_plugin_active( $file ) ) {
	$r = activate_plugin( $file );
	update_option( 'rosenberger_cmplz_log', is_wp_error( $r ) ? 'activate_failed: ' . $r->get_error_message() : 'activated ' . gmdate( 'c' ) );
	return;
}

// Фаза 3: базовый конфиг (плагин уже загружен в этом запросе)
if ( get_option( 'rosenberger_cmplz_done' ) ) return;
$applied = array();
if ( function_exists( 'cmplz_update_option' ) ) {
	// Регион EU = DSGVO/opt-in (Австрия входит). Сигнатуры разнятся по версиям — пробуем обе.
	try { cmplz_update_option( 'regions', array( 'eu' => 1 ) ); $applied[] = 'regions'; } catch ( \\Throwable $e ) {}
}
update_option( 'rosenberger_cmplz_done', 1 );
update_option( 'rosenberger_cmplz_log', 'configured(' . implode( ',', $applied ) . ') active=' . ( is_plugin_active( $file ) ? '1' : '0' ) . ' file=' . $file );
`;

const neutralize = async () => {
	const list = await api( '/wp-json/code-snippets/v1/snippets' );
	if ( ! Array.isArray( list.body ) ) return;
	for ( const s of list.body ) {
		if ( s.name === SNIPPET && ( s.active || s.code !== '// removed' ) ) {
			await api( `/wp-json/code-snippets/v1/snippets/${ s.id }`, {
				method: 'POST', body: JSON.stringify( { active: false, code: '// removed' } ),
			} );
			console.log( `  обезврежен сниппет #${ s.id }` );
		}
	}
};

console.log( '🛠  Ставлю Complianz...' );
if ( ! env.WP_URL ) throw new Error( 'нет WP_URL в .env' );

await neutralize();
const created = await api( '/wp-json/code-snippets/v1/snippets', {
	method: 'POST',
	body: JSON.stringify( { name: SNIPPET, desc: 'Установка/активация Complianz. Обезвреживается.', code: phpCode, scope: 'global', active: true } ),
} );
if ( ! ( created.body && created.body.id ) ) { console.log( JSON.stringify( created.body ).slice( 0, 400 ) ); throw new Error( 'не удалось создать сниппет' ); }
console.log( `  сниппет создан #${ created.body.id }, триггерю фазы...` );

// Несколько загрузок: установка → активация → конфиг.
for ( let i = 0; i < 8; i++ ) {
	await fetch( BASE + '/' ).catch( () => {} );
	await sleep( 2500 );
}
await neutralize();

// Проверка активации.
const plugins = await api( '/wp-json/wp/v2/plugins' );
let status = 'неизвестно';
if ( Array.isArray( plugins.body ) ) {
	const c = plugins.body.find( ( p ) => /complianz/i.test( p.plugin || '' ) || /Complianz/i.test( ( p.name || '' ) ) );
	status = c ? `${ c.status } (${ c.plugin })` : 'НЕ найден';
}
console.log( `\n📋 Complianz: ${ status }` );
console.log( '   Дальше — в WP Admin → Complianz → мастер: регион Österreich/EU, opt-in, сгенерировать Datenschutz/Cookie-доки.' );
