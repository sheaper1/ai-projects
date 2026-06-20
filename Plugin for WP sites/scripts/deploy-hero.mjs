// Деплой блока Hero на staging через Code Snippets REST API (smoke-тест).
// Читает доступы из .env, кладёт файлы блока на сервер, регистрирует блок,
// создаёт тестовую страницу и проверяет, что render.php отдал разметку.
//
// Запуск: node scripts/deploy-hero.mjs

import { readFileSync } from 'node:fs';
import { resolve, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = resolve( dirname( fileURLToPath( import.meta.url ) ), '..' );

// --- .env ---
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
	let body;
	try { body = JSON.parse( text ); } catch { body = text; }
	return { status: res.status, body };
};

// --- файлы блока -> base64 ---
const b64 = ( rel ) => readFileSync( resolve( root, 'library/blocks/hero', rel ) ).toString( 'base64' );
const files = {
	'block.json': b64( 'block.json' ),
	'render.php': b64( 'render.php' ),
	'build/index.js': b64( 'build/index.js' ),
	'build/style-index.css': b64( 'build/style-index.css' ),
	'build/index.asset.php': b64( 'build/index.asset.php' ),
};

// --- PHP-сниппет: пишет файлы в uploads/library-blocks/hero и регистрирует блок ---
const filesPhp = Object.entries( files )
	.map( ( [ rel, data ] ) => `\t\t'${ rel }' => '${ data }',` )
	.join( '\n' );

const snippetCode = `$up = wp_upload_dir();
$dir = trailingslashit( $up['basedir'] ) . 'library-blocks/hero';
$files = array(
${ filesPhp }
);
if ( ! file_exists( $dir . '/block.json' ) ) {
	wp_mkdir_p( $dir . '/build' );
	foreach ( $files as $rel => $b64 ) {
		file_put_contents( $dir . '/' . $rel, base64_decode( $b64 ) );
	}
}
add_action( 'init', function () use ( $dir ) {
	if ( file_exists( $dir . '/block.json' ) ) {
		register_block_type( $dir );
	}
} );`;

const SNIPPET_NAME = 'Library: Hero block (auto-deploy)';

const main = async () => {
	// 1. удалить прежний сниппет с тем же именем (идемпотентность)
	const list = await api( '/wp-json/code-snippets/v1/snippets' );
	if ( Array.isArray( list.body ) ) {
		for ( const s of list.body ) {
			if ( s.name === SNIPPET_NAME ) {
				await api( `/wp-json/code-snippets/v1/snippets/${ s.id }`, { method: 'DELETE' } );
				console.log( `Удалён старый сниппет #${ s.id }` );
			}
		}
	}

	// 2. создать сниппет
	const created = await api( '/wp-json/code-snippets/v1/snippets', {
		method: 'POST',
		body: JSON.stringify( {
			name: SNIPPET_NAME,
			desc: 'Авто-деплой блока library/hero для smoke-теста.',
			code: snippetCode,
			scope: 'global',
			active: true,
		} ),
	} );
	console.log( 'Создание сниппета:', created.status, JSON.stringify( created.body ).slice( 0, 300 ) );
	const id = created.body && created.body.id;
	if ( ! id ) throw new Error( 'Не удалось создать сниппет' );
	// active:true при создании уже активирует сниппет — отдельный /activate не нужен.

	// 3. создать тестовую страницу с блоком
	const content = `<!-- wp:library/hero -->
<!-- wp:heading {"level":1} -->
<h1 class="wp-block-heading">Тестовый Hero (smoke)</h1>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Проверка серверного рендера блока library/hero.</p>
<!-- /wp:paragraph -->
<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button">Кнопка</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons -->
<!-- /wp:library/hero -->`;

	const page = await api( '/wp-json/wp/v2/pages', {
		method: 'POST',
		body: JSON.stringify( { title: 'Hero smoke-test', status: 'publish', content } ),
	} );
	console.log( 'Создание страницы:', page.status, 'id=', page.body && page.body.id );
	const link = page.body && page.body.link;
	if ( ! link ) throw new Error( 'Не удалось создать страницу' );

	// 5. прочитать отрендеренную страницу и проверить маркер блока
	const html = await ( await fetch( link ) ).text();
	const ok = html.includes( 'wp-block-library-hero' );
	console.log( '\nСтраница:', link );
	console.log( ok ? '✅ Блок отрендерен (найден .wp-block-library-hero)' : '❌ Маркер блока не найден' );
	if ( ! ok ) {
		const idx = html.indexOf( 'Тестовый Hero' );
		console.log( 'Фрагмент:', html.slice( Math.max( 0, idx - 200 ), idx + 200 ) );
	}
};

main().catch( ( e ) => { console.error( e ); process.exit( 1 ); } );
