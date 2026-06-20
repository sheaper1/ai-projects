// Универсальный деплой блока библиотеки на staging через Code Snippets REST.
// Заливает ВСЕ файлы блока (block.json, render.php, build/*, assets/*),
// регистрирует editor-скрипт/стиль явно (с рабочим URL), создаёт/обновляет
// тест-страницу с блоком и проверяет рендер.
//
// Запуск: node scripts/deploy-block.mjs <slug>
//   напр.: node scripts/deploy-block.mjs hero-cover

import { readFileSync, readdirSync, statSync, existsSync } from 'node:fs';
import { resolve, dirname, relative } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = resolve( dirname( fileURLToPath( import.meta.url ) ), '..' );
const slug = process.argv[ 2 ];
if ( ! slug ) { console.error( 'Укажи slug блока: node scripts/deploy-block.mjs <slug>' ); process.exit( 1 ); }

const blockDir = resolve( root, 'library/blocks', slug );

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
	let body; try { body = JSON.parse( text ); } catch { body = text; }
	return { status: res.status, body };
};

// --- собрать все файлы блока (рекурсивно) в base64 ---
const walk = ( dir ) => readdirSync( dir ).flatMap( ( name ) => {
	const p = resolve( dir, name );
	return statSync( p ).isDirectory() ? walk( p ) : [ p ];
} );

const files = {};
for ( const abs of walk( blockDir ) ) {
	let rel = relative( blockDir, abs ).split( '\\' ).join( '/' );
	let buf = readFileSync( abs );
	// из block.json убираем editorScript/style — регистрируем их вручную.
	if ( rel === 'block.json' ) {
		const json = JSON.parse( buf.toString( 'utf8' ) );
		delete json.editorScript;
		delete json.style;
		buf = Buffer.from( JSON.stringify( json, null, '\t' ) );
	}
	files[ rel ] = buf.toString( 'base64' );
}

const filesPhp = Object.entries( files )
	.map( ( [ rel, data ] ) => `\t'${ rel }' => '${ data }',` )
	.join( '\n' );

const handle = `library-${ slug }`;
const snippetCode = `$up = wp_upload_dir();
$dir = trailingslashit( $up['basedir'] ) . 'library-blocks/${ slug }';
$url = trailingslashit( $up['baseurl'] ) . 'library-blocks/${ slug }';
$files = array(
${ filesPhp }
);
foreach ( $files as $rel => $b64 ) {
	$dest = $dir . '/' . $rel;
	wp_mkdir_p( dirname( $dest ) );
	file_put_contents( $dest, base64_decode( $b64 ) );
}
add_action( 'init', function () use ( $dir, $url ) {
	if ( ! file_exists( $dir . '/block.json' ) ) { return; }
	$asset = file_exists( $dir . '/build/index.asset.php' )
		? include $dir . '/build/index.asset.php'
		: array( 'dependencies' => array(), 'version' => '1' );
	wp_register_script( '${ handle }-editor', $url . '/build/index.js', $asset['dependencies'], $asset['version'], true );
	wp_register_style( '${ handle }-style', $url . '/build/style-index.css', array(), $asset['version'] );
	register_block_type( $dir, array(
		'editor_script' => '${ handle }-editor',
		'style'         => '${ handle }-style',
	) );
	// Пробрасываем дефолтный фон в редактор, чтобы блок не выглядел пустым.
	if ( file_exists( $dir . '/assets/hero-bg.webp' ) ) {
		wp_add_inline_script(
			'${ handle }-editor',
			"window.libraryBlockDefaults=window.libraryBlockDefaults||{};window.libraryBlockDefaults['${ slug }']={bg:" . wp_json_encode( $url . '/assets/hero-bg.webp' ) . "};",
			'after'
		);
	}
} );
${ slug === 'hero-cover'
	? `// Полноэкранный Hero сам себе шапка — прячем заголовок темы на таких страницах.
add_action( 'wp_head', function () {
	if ( is_singular() && has_block( 'library/hero-cover' ) ) {
		echo '<style id="hero-cover-title-hide">.page-header{display:none!important;}</style>';
	}
} );`
	: '' }`;

const SNIPPET_NAME = `Library: ${ slug } block (auto-deploy)`;
const blockName = `library/${ slug }`;

const main = async () => {
	// удалить прежний сниппет с тем же именем
	const list = await api( '/wp-json/code-snippets/v1/snippets' );
	if ( Array.isArray( list.body ) ) {
		for ( const s of list.body ) {
			if ( s.name === SNIPPET_NAME ) {
				await api( `/wp-json/code-snippets/v1/snippets/${ s.id }`, { method: 'DELETE' } );
				console.log( `Удалён старый сниппет #${ s.id }` );
			}
		}
	}

	const created = await api( '/wp-json/code-snippets/v1/snippets', {
		method: 'POST',
		body: JSON.stringify( {
			name: SNIPPET_NAME,
			desc: `Авто-деплой блока ${ blockName }.`,
			code: snippetCode,
			scope: 'global',
			active: true,
		} ),
	} );
	console.log( 'Сниппет:', created.status, 'id=', created.body && created.body.id );
	if ( ! ( created.body && created.body.id ) ) { console.log( JSON.stringify( created.body ).slice( 0, 400 ) ); throw new Error( 'Сниппет не создан' ); }

	// Дефолтный фон — в медиатеку (идемпотентно), чтобы был виден в редакторе.
	let bgAttr = '';
	const bgPath = resolve( blockDir, 'assets/hero-bg.webp' );
	if ( existsSync( bgPath ) ) {
		const mediaSlug = `library-${ slug }-bg`;
		const found = await api( `/wp-json/wp/v2/media?slug=${ mediaSlug }` );
		let media = Array.isArray( found.body ) && found.body[ 0 ];
		if ( ! media ) {
			const up = await fetch( `${ BASE }/wp-json/wp/v2/media`, {
				method: 'POST',
				headers: {
					Authorization: AUTH,
					'Content-Type': 'image/webp',
					'Content-Disposition': `attachment; filename="${ mediaSlug }.webp"`,
				},
				body: readFileSync( bgPath ),
			} );
			media = await up.json();
			// зафиксировать slug
			if ( media && media.id ) {
				await api( `/wp-json/wp/v2/media/${ media.id }`, { method: 'POST', body: JSON.stringify( { slug: mediaSlug, title: `${ slug } background` } ) } );
			}
			console.log( 'Фон загружен в медиатеку: id=', media && media.id );
		} else {
			console.log( 'Фон уже в медиатеке: id=', media.id );
		}
		if ( media && media.id ) {
			bgAttr = `,"backgroundUrl":${ JSON.stringify( media.source_url ) },"backgroundId":${ media.id }`;
		}
	}

	// тест-страница: самозакрывающийся динамический блок (контент строит render.php)
	const pageSlug = `${ slug }-test`;
	const content = `<!-- wp:${ blockName } {"align":"full"${ bgAttr }} /-->`;
	const existing = await api( `/wp-json/wp/v2/pages?slug=${ pageSlug }&status=publish` );
	const existId = Array.isArray( existing.body ) && existing.body[ 0 ] && existing.body[ 0 ].id;
	const path = existId ? `/wp-json/wp/v2/pages/${ existId }` : '/wp-json/wp/v2/pages';
	const base = existId
		? { content }
		: { title: `${ slug } test`, slug: pageSlug, status: 'publish', content };

	// Чистый шаблон без шапки/заголовка темы (Elementor Canvas), с откатом.
	let page = await api( path, { method: 'POST', body: JSON.stringify( { ...base, template: 'elementor_canvas' } ) } );
	if ( page.status >= 400 ) {
		console.log( 'Шаблон elementor_canvas недоступен, ставлю без шаблона.' );
		page = await api( path, { method: 'POST', body: JSON.stringify( base ) } );
	}
	console.log( existId ? 'Страница обновлена:' : 'Страница создана:', page.status, 'id=', page.body && page.body.id );
	const link = page.body && page.body.link;

	const html = await ( await fetch( link ) ).text();
	const ok = html.includes( `wp-block-${ slug.replace( '/', '-' ) }` ) || html.includes( `wp-block-library-${ slug }` );
	console.log( '\nСтраница:', link );
	console.log( ok ? '✅ Блок отрендерен сервером' : '❌ Маркер блока не найден' );
};

main().catch( ( e ) => { console.error( e ); process.exit( 1 ); } );
