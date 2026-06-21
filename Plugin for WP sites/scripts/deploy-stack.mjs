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

const MIME = { svg: 'image/svg+xml', jpg: 'image/jpeg', jpeg: 'image/jpeg', png: 'image/png', webp: 'image/webp' };

const ensureMedia = async ( slug, file, ext = 'svg' ) => {
	const found = await api( `/wp-json/wp/v2/media?slug=${ encodeURIComponent( slug ) }&per_page=1` );
	if ( Array.isArray( found.body ) && found.body[ 0 ] ) return found.body[ 0 ];
	const filename = `${ slug }.${ ext }`;
	const res = await fetch( `${ BASE }/wp-json/wp/v2/media`, {
		method: 'POST',
		headers: {
			Authorization: AUTH,
			'Content-Type': MIME[ ext ] || 'application/octet-stream',
			'Content-Disposition': `attachment; filename="${ filename }"`,
		},
		body: readFileSync( file ),
	} );
	const body = await res.json();
	if ( ! res.ok ) throw new Error( `Не удалось загрузить ${ filename }: ${ body.message || res.status }` );
	return body;
};

const ensureSvgMedia = ( slug, file ) => ensureMedia( slug, file, 'svg' );

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
		'cta_text' => 'Termin vereinbaren',
		'cta_url'  => '/kontakt/',
	) );
}
// Миграция старого демонстрационного CTA; произвольный текст клиента не трогаем.
$contacts = get_option( 'rosenberger_contacts', array() );
if ( isset( $contacts['cta_text'] ) && 'Kontakt' === $contacts['cta_text'] ) {
	$contacts['cta_text'] = 'Termin vereinbaren';
	update_option( 'rosenberger_contacts', $contacts );
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
	if ( ! themeActive || ! coreActive ) throw new Error( 'Тема или project-core не активировались' );

	const iconDir = resolve( root, 'projects/rosenberger/media/icons' );
	const ratingMedia = await ensureSvgMedia( 'rosenberger-google-rating', resolve( root, 'projects/rosenberger/media/google-rating.svg' ) );
	const iconFiles = [ 'price', 'callback', 'pressure', 'commission', 'hidden' ];
	const iconMedia = [];
	for ( const name of iconFiles ) {
		iconMedia.push( await ensureSvgMedia( `rosenberger-icon-${ name }`, resolve( iconDir, `${ name }.svg` ) ) );
	}

	const cardDir = resolve( root, 'projects/rosenberger/media/cards' );
	const cardMedia = [];
	for ( const n of [ 1, 2, 3 ] ) {
		cardMedia.push( await ensureMedia( `rosenberger-card-${ n }`, resolve( cardDir, `card-${ n }.jpg` ), 'jpg' ) );
	}

	const aboutMedia = await ensureMedia( 'rosenberger-about-bg', resolve( root, 'projects/rosenberger/media/about/about-bg.webp' ), 'webp' );
	const figmaSoldShowcaseUrl = 'https://www.figma.com/api/mcp/asset/61d143ff-edab-476b-9409-602908ec4e7a';
	const figmaReferralUrl = 'https://www.figma.com/api/mcp/asset/11225be6-4f8b-406a-a944-82585aaa43ee';
	const figmaConsultationBgUrl = 'https://www.figma.com/api/mcp/asset/0bbf51fc-6133-4432-812d-0e34f66da8ff';

	// Тестовая страница повторяет секции Figma. Повторный деплой не дублирует блоки.
	const pages = await api( '/wp-json/wp/v2/pages?slug=hero-cover-test&context=edit' );
	if ( Array.isArray( pages.body ) && pages.body[ 0 ] ) {
		const page = pages.body[ 0 ];
		let raw = page.content && page.content.raw ? page.content.raw : '';
		let changed = false;
		if ( ! raw.includes( 'wp:library/trust-bar' ) ) {
			raw += `\n\n<!-- wp:library/trust-bar ${ JSON.stringify( { badgeId: ratingMedia.id, badgeUrl: ratingMedia.source_url } ) } /-->`;
			changed = true;
		} else if ( ! raw.includes( 'badgeUrl' ) ) {
			raw = raw.replace( '<!-- wp:library/trust-bar /-->', `<!-- wp:library/trust-bar ${ JSON.stringify( { badgeId: ratingMedia.id, badgeUrl: ratingMedia.source_url } ) } /-->` );
			changed = true;
		}
		if ( ! raw.includes( 'wp:library/pain-points' ) ) {
			const defaults = [
				[ 'Preisversprechen, die nicht halten', 'Ein hoher Wunschpreis bringt dem Makler den Auftrag. Danach steht das Inserat monatelang und der Preis wird Stück für Stück gesenkt.' ],
				[ 'Makler, die nicht zurückrufen', 'Nach der Unterschrift kommen keine Rückmeldungen mehr, und Sie erfahren wochenlang nichts über den Stand.' ],
				[ 'Druck statt Beratung', 'Sie sollen sich schnell entscheiden, weil angeblich andere Käufer schon warten.' ],
				[ 'Unklare Provision', 'Was der Verkauf kostet und was darin enthalten ist, bleibt bis zum Schluss vage.' ],
				[ 'Übergang', 'Was der Verkauf kostet und was darin enthalten ist, bleibt bis zum Schluss vage.' ],
			];
			const items = defaults.map( ( [ title, text ], i ) => ( { title, text, iconId: iconMedia[ i ].id, iconUrl: iconMedia[ i ].source_url } ) );
			raw += `\n\n<!-- wp:library/pain-points ${ JSON.stringify( { items } ) } /-->`;
			changed = true;
		}
		{
			const cardDefaults = [
				[ 'Immobilie verkaufen', 'Von der Bewertung über die Vermarktung bis zur Übergabe übernehme ich den ganzen Verkauf für Sie.' ],
				[ 'Immobilienbewertung', 'Sie erfahren realistisch, was Ihre Immobilie wert ist, ohne überzogene Versprechen und ohne Verpflichtung.' ],
				[ 'Immobilie vermieten', 'Sie bekommen sorgfältig ausgewählte Mieter, und ich kümmere mich um Bonität, Vertrag und Übergabe.' ],
			];
			const cardsAttr = cardDefaults.map( ( [ title, text ], i ) => ( {
				title,
				text,
				buttonText: 'Erfahren Sie mehr',
				buttonUrl: '#',
				imageId: cardMedia[ i ].id,
				imageUrl: cardMedia[ i ].source_url,
			} ) );
			const cardsComment = `<!-- wp:library/cards-stack ${ JSON.stringify( { cards: cardsAttr } ) } /-->`;
			if ( ! raw.includes( 'wp:library/cards-stack' ) ) {
				raw += `\n\n${ cardsComment }`;
				changed = true;
			} else if ( ! raw.includes( 'rosenberger-card-' ) ) {
				raw = raw.replace( '<!-- wp:library/cards-stack /-->', cardsComment );
				changed = true;
			}
		}
		if ( ! raw.includes( 'wp:library/about' ) ) {
			raw += `\n\n<!-- wp:library/about ${ JSON.stringify( { backgroundId: aboutMedia.id, backgroundUrl: aboutMedia.source_url } ) } /-->`;
			changed = true;
		}
		if ( ! raw.includes( 'wp:library/process-steps' ) ) {
			raw += `\n\n<!-- wp:library/process-steps /-->`;
			changed = true;
		}
		if ( ! raw.includes( 'wp:library/sold-showcase' ) ) {
			raw += `\n\n<!-- wp:library/sold-showcase ${ JSON.stringify( {
				imageUrl: figmaSoldShowcaseUrl,
				buttonUrl: '#',
				ctaUrl: '#',
			} ) } /-->`;
			changed = true;
		}
		if ( ! raw.includes( 'wp:library/referral-cta' ) ) {
			raw += `\n\n<!-- wp:library/referral-cta ${ JSON.stringify( {
				imageUrl: figmaReferralUrl,
				buttonUrl: '#',
			} ) } /-->`;
			changed = true;
		}
		if ( ! raw.includes( 'wp:library/faq-section' ) ) {
			raw += `\n\n<!-- wp:library/faq-section /-->`;
			changed = true;
		}
		if ( ! raw.includes( 'wp:library/consultation-cta' ) ) {
			raw += `\n\n<!-- wp:library/consultation-cta ${ JSON.stringify( {
				backgroundUrl: figmaConsultationBgUrl,
				buttonUrl: '#',
			} ) } /-->`;
			changed = true;
		}
		if ( changed ) {
			await api( `/wp-json/wp/v2/pages/${ page.id }`, {
				method: 'POST',
				body: JSON.stringify( { content: raw } ),
			} );
			console.log( 'Секции тестовой страницы синхронизированы.' );
		}
	}

	console.log( 'Тема rosenberger активна:', themeActive ? '✅' : '❌' );
	console.log( 'Плагин rosenberger-core активен:', coreActive ? '✅' : '❌' );

	await neutralizeLibrarySnippets();

	if ( ! themeActive || ! coreActive ) { console.log( '\n⚠️  Что-то не активировалось — проверь вручную.' ); process.exit( 1 ); }
	console.log( '\n✅ Проект развёрнут: тема rosenberger + плагин rosenberger-core. Сниппеты обезврежены.' );
	console.log( 'Настройки сайта: ' + BASE + '/wp-admin/admin.php?page=rosenberger-settings' );
	console.log( 'Тест-страница:   ' + BASE + '/hero-cover-test/' );
};

main().catch( ( e ) => { console.error( e ); process.exit( 1 ); } );
