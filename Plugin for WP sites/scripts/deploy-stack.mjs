// Р”РµРїР»РѕР№ РїСЂРѕРµРєС‚Р° РЅР° staging Р‘Р•Р— SFTP: Р±Р»РѕС‡РЅР°СЏ С‚РµРјР° rosenberger (РІРёРґ, Р±Р»РѕРєРё РІРЅСѓС‚СЂРё)
// + РїР»Р°РіРёРЅ РїСЂРѕРµРєС‚Р° rosenberger-core (РґР°РЅРЅС‹Рµ/Р»РѕРіРёРєР°: РЅР°СЃС‚СЂРѕР№РєРё, CPT).
// РњРѕРґРµР»СЊ В«РєРѕРїРёСЏ РІ РїСЂРѕРµРєС‚В»: РІСЃС‘ per-project Рё РёР·РѕР»РёСЂРѕРІР°РЅРѕ.
//
// Code Snippets вЂ” РѕРґРЅРѕСЂР°Р·РѕРІС‹Р№ СѓСЃС‚Р°РЅРѕРІС‰РёРє: РїРёС€РµС‚ С„Р°Р№Р»С‹ С‚РµРјС‹ Рё РїР»Р°РіРёРЅР°, Р°РєС‚РёРІРёСЂСѓРµС‚
// С‚РµРјСѓ Рё РїР»Р°РіРёРЅ, СЃРЅРѕСЃРёС‚ СЃС‚Р°СЂС‹Р№ РѕР±С‰РёР№ РїР»Р°РіРёРЅ library-blocks, Р·Р°С‚РµРј РѕР±РµР·РІСЂРµР¶РёРІР°РµС‚СЃСЏ.
//
// Р—Р°РїСѓСЃРє: node scripts/deploy-stack.mjs

import { readFileSync, readdirSync, statSync, existsSync, writeFileSync } from 'node:fs';
import { createHash } from 'node:crypto';
import { resolve, dirname, relative } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = resolve( dirname( fileURLToPath( import.meta.url ) ), '..' );
// Opt-in: заливать только изменённые файлы (диф против локального кэша хэшей).
// По умолчанию — полная заливка (поведение не меняется). Нет кэша → тоже полная.
const onlyChanged = process.argv.includes( '--changed' );
const cacheFile = resolve( root, '.deploy-cache.json' );

const env = {};
for ( const line of readFileSync( resolve( root, '.env' ), 'utf8' ).split( /\r?\n/ ) ) {
	const m = line.match( /^([A-Z_]+)=(.*)$/ );
	if ( m ) env[ m[ 1 ] ] = m[ 2 ];
}
console.log( '🚀 deploy →', env.WP_URL );
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
	if ( ! res.ok ) throw new Error( `РќРµ СѓРґР°Р»РѕСЃСЊ Р·Р°РіСЂСѓР·РёС‚СЊ ${ filename }: ${ body.message || res.status }` );
	return body;
};

const ensureSvgMedia = ( slug, file ) => ensureMedia( slug, file, 'svg' );

const walk = ( dir ) => readdirSync( dir ).flatMap( ( name ) => {
	const p = resolve( dir, name );
	return statSync( p ).isDirectory() ? walk( p ) : [ p ];
} );

// СЃРѕР±СЂР°С‚СЊ С„Р°Р№Р»С‹ РєР°С‚Р°Р»РѕРіР° РІ { РѕС‚РЅРѕСЃРёС‚РµР»СЊРЅС‹Р№_РїСѓС‚СЊ: base64 }, РїСЂРѕРїСѓСЃРєР°СЏ src/
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

const buildSnippetCode = ( themeSubset, pluginSubset ) => `$theme_dir  = get_theme_root() . '/rosenberger';
$plugin_dir = WP_PLUGIN_DIR . '/rosenberger-core';
$theme_files = array(
${ phpArray( themeSubset ) }
);
$plugin_files = array(
${ phpArray( pluginSubset ) }
);
foreach ( $theme_files as $rel => $b64 ) { $d = $theme_dir . '/' . $rel; wp_mkdir_p( dirname( $d ) ); file_put_contents( $d, base64_decode( $b64 ) ); }
foreach ( $plugin_files as $rel => $b64 ) { $d = $plugin_dir . '/' . $rel; wp_mkdir_p( dirname( $d ) ); file_put_contents( $d, base64_decode( $b64 ) ); }
if ( get_option( 'stylesheet' ) !== 'rosenberger' ) { switch_theme( 'rosenberger' ); }
require_once ABSPATH . 'wp-admin/includes/plugin.php';
if ( ! is_plugin_active( 'rosenberger-core/rosenberger-core.php' ) ) { activate_plugin( 'rosenberger-core/rosenberger-core.php' ); }
// Р Р°Р·РѕРІС‹Р№ РїРѕСЃРµРІ РїСЂРёРјРµСЂР° РєРѕРЅС‚Р°РєС‚РѕРІ (РЅРµ РїРµСЂРµР·Р°С‚РёСЂР°РµС‚ РїСЂР°РІРєРё РєР»РёРµРЅС‚Р°).
if ( false === get_option( 'rosenberger_contacts' ) ) {
	add_option( 'rosenberger_contacts', array(
		'phone'    => '+43 699 11 777 505',
		'email'    => 'office@rosenberger.immo',
		'address'  => 'ROSENBERGER Immobilien GmbH, Drevesstraße 2/1, 6800 Feldkirch',
		'hours'    => 'Mo-Fr 9:00-17:00',
		'cta_text' => 'Kontakt',
		'cta_url'  => '/kontakt/',
	) );
}
// РњРёРіСЂР°С†РёСЏ СЃС‚Р°СЂС‹С… placeholder-РєРѕРЅС‚Р°РєС‚РѕРІ РЅР° СЂРµР°Р»СЊРЅС‹Рµ (С‚РѕР»СЊРєРѕ Р·Р°РІРѕРґСЃРєРёРµ Р·РЅР°С‡РµРЅРёСЏ, РЅРµ РїСЂР°РІРєРё РєР»РёРµРЅС‚Р°).
$contacts_fix = get_option( 'rosenberger_contacts', array() );
$contacts_changed = false;
if ( isset( $contacts_fix['phone'] ) && '+43 5572 123456' === $contacts_fix['phone'] ) { $contacts_fix['phone'] = '+43 699 11 777 505'; $contacts_changed = true; }
if ( isset( $contacts_fix['email'] ) && 'office@rosenberger.at' === $contacts_fix['email'] ) { $contacts_fix['email'] = 'office@rosenberger.immo'; $contacts_changed = true; }
if ( isset( $contacts_fix['address'] ) && 'Bregenz, Vorarlberg' === $contacts_fix['address'] ) { $contacts_fix['address'] = 'ROSENBERGER Immobilien GmbH, Drevesstraße 2/1, 6800 Feldkirch'; $contacts_changed = true; }
if ( $contacts_changed ) { update_option( 'rosenberger_contacts', $contacts_fix ); }
// РњРёРіСЂР°С†РёСЏ СЃС‚Р°СЂРѕРіРѕ РґРµРјРѕРЅСЃС‚СЂР°С†РёРѕРЅРЅРѕРіРѕ CTA; РїСЂРѕРёР·РІРѕР»СЊРЅС‹Р№ С‚РµРєСЃС‚ РєР»РёРµРЅС‚Р° РЅРµ С‚СЂРѕРіР°РµРј.
$contacts = get_option( 'rosenberger_contacts', array() );
if ( isset( $contacts['cta_text'] ) && 'Termin vereinbaren' === $contacts['cta_text'] ) {
	$contacts['cta_text'] = 'Kontakt';   // шапка по Figma 2009:3068 = «Kontakt»
	update_option( 'rosenberger_contacts', $contacts );
}
// СЃРЅРѕСЃРёРј СЃС‚Р°СЂС‹Р№ РѕР±С‰РёР№ РїР»Р°РіРёРЅ (РјРѕРґРµР»СЊ СЃРјРµРЅРёР»Р°СЃСЊ РЅР° В«РІСЃС‘ РІ РїСЂРѕРµРєС‚РµВ»)
if ( is_plugin_active( 'library-blocks/library-blocks.php' ) ) { deactivate_plugins( 'library-blocks/library-blocks.php' ); }
$old = WP_PLUGIN_DIR . '/library-blocks';
if ( is_dir( $old ) ) {
	$it = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $old, FilesystemIterator::SKIP_DOTS ), RecursiveIteratorIterator::CHILD_FIRST );
	foreach ( $it as $f ) { $f->isDir() ? @rmdir( $f->getPathname() ) : @unlink( $f->getPathname() ); }
	@rmdir( $old );
}`;

// --- Диф-деплой (opt-in --changed) --------------------------------------
const hashMap = ( obj ) => {
	const out = {};
	for ( const [ k, v ] of Object.entries( obj ) ) out[ k ] = createHash( 'sha1' ).update( v ).digest( 'hex' );
	return out;
};
const themeHashes  = hashMap( themeFiles );
const pluginHashes = hashMap( pluginFiles );

// Подмножество { rel: base64 } для файлов, чей хэш отличается от прошлого деплоя.
const changedSubset = ( files, hashes, prev ) => {
	const out = {};
	for ( const rel of Object.keys( files ) ) {
		if ( ! prev || prev[ rel ] !== hashes[ rel ] ) out[ rel ] = files[ rel ];
	}
	return out;
};
const readCache = () => {
	if ( ! onlyChanged || ! existsSync( cacheFile ) ) return null;
	try { return JSON.parse( readFileSync( cacheFile, 'utf8' ) ); } catch { return null; }
};
const writeCache = () => {
	try { writeFileSync( cacheFile, JSON.stringify( { theme: themeHashes, plugin: pluginHashes } ) ); } catch {}
};

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
			console.log( `РћР±РµР·РІСЂРµР¶РµРЅ СЃРЅРёРїРїРµС‚ #${ s.id } (${ s.name })` );
		}
	}
};

const main = async () => {
	console.log( `Р¤Р°Р№Р»РѕРІ С‚РµРјС‹: ${ Object.keys( themeFiles ).length }, С„Р°Р№Р»РѕРІ РїР»Р°РіРёРЅР°: ${ Object.keys( pluginFiles ).length }` );

	// Полная заливка по умолчанию; --changed + кэш → только изменённые файлы.
	const cache = readCache();
	let themeSubset = themeFiles, pluginSubset = pluginFiles;
	if ( cache ) {
		themeSubset  = changedSubset( themeFiles, themeHashes, cache.theme );
		pluginSubset = changedSubset( pluginFiles, pluginHashes, cache.plugin );
		const n = Object.keys( themeSubset ).length + Object.keys( pluginSubset ).length;
		console.log( `Диф-режим (--changed): к заливке ${ n } изменённых файлов.` );
	} else if ( onlyChanged ) {
		console.log( 'Кэш не найден — полная заливка (кэш создастся после успеха).' );
	}
	const snippetCode = buildSnippetCode( themeSubset, pluginSubset );

	await neutralizeLibrarySnippets();

	const created = await api( '/wp-json/code-snippets/v1/snippets', {
		method: 'POST',
		body: JSON.stringify( {
			name: INSTALLER,
			desc: 'РћРґРЅРѕСЂР°Р·РѕРІС‹Р№ СѓСЃС‚Р°РЅРѕРІС‰РёРє С‚РµРјС‹ Рё РїР»Р°РіРёРЅР° РїСЂРѕРµРєС‚Р°. РћР±РµР·РІСЂРµР¶РёРІР°РµС‚СЃСЏ Р°РІС‚РѕРјР°С‚РёС‡РµСЃРєРё.',
			code: snippetCode,
			scope: 'global',
			active: true,
		} ),
	} );
	console.log( 'РЈСЃС‚Р°РЅРѕРІС‰РёРє СЃРѕР·РґР°РЅ:', created.status, 'id=', created.body && created.body.id );
	if ( ! ( created.body && created.body.id ) ) { console.log( JSON.stringify( created.body ).slice( 0, 400 ) ); throw new Error( 'РќРµ СѓРґР°Р»РѕСЃСЊ СЃРѕР·РґР°С‚СЊ СѓСЃС‚Р°РЅРѕРІС‰РёРє' ); }

	// Сниппет в global scope исполняется на загрузке страницы. Триггерим и поллим
	// статус активации (вместо слепой паузы) до 30с — обычно готово за 1-2 итерации.
	const sleep = ( ms ) => new Promise( ( r ) => setTimeout( r, ms ) );
	let themeActive = false, coreActive = false;
	const deadline = Date.now() + 30000;
	while ( Date.now() < deadline ) {
		await fetch( BASE + '/' ).catch( () => {} );
		const themes = await api( '/wp-json/wp/v2/themes?status=active' );
		themeActive = Array.isArray( themes.body ) && themes.body.some( ( t ) => t.stylesheet === 'rosenberger' );
		const plugins = await api( '/wp-json/wp/v2/plugins' );
		coreActive = Array.isArray( plugins.body ) && plugins.body.some(
			( p ) => p.plugin === 'rosenberger-core/rosenberger-core' && p.status === 'active'
		);
		if ( themeActive && coreActive ) break;
		await sleep( 1000 );
	}
	if ( ! themeActive || ! coreActive ) throw new Error( 'Тема или project-core не активировались (таймаут 30с)' );

	// Файлы темы/плагина залиты и активны — фиксируем кэш хэшей для следующего --changed.
	writeCache();

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

	// Страница 404 — фоновое фото; страница Danke — иконки карточек-шагов.
	await ensureMedia( 'rosenberger-404-building', resolve( root, 'projects/rosenberger/media/404/building.webp' ), 'webp' );
	const dankeDir = resolve( root, 'projects/rosenberger/media/danke' );
	for ( const name of [ 'email', 'phone', 'steps' ] ) {
		await ensureSvgMedia( `rosenberger-danke-icon-${ name }`, resolve( dankeDir, `${ name }.svg` ) );
	}

	const aboutMedia = await ensureMedia( 'rosenberger-about-bg', resolve( root, 'projects/rosenberger/media/about/about-bg.webp' ), 'webp' );
	// РђСЃСЃРµС‚С‹ РёР· РјРµРґРёР°С‚РµРєРё WP (Р° РЅРµ РїСЂРѕС‚СѓС…Р°СЋС‰РёРµ Figma-CDN URL) вЂ” РїСЂР°РІРёР»Рѕ В«РєР°СЂС‚РёРЅРєРё С‚РѕР»СЊРєРѕ РёР· РјРµРґРёР°С‚РµРєРёВ».
		const homeDir = resolve( root, 'projects/rosenberger/media/home' );
		const referralMedia = await ensureMedia( 'rosenberger-referral', resolve( homeDir, 'referral.webp' ), 'webp' );
		const consultationBgMedia = await ensureMedia( 'rosenberger-consultation-bg', resolve( homeDir, 'cta-bg.webp' ), 'webp' );
		
	// РўРµСЃС‚РѕРІР°СЏ СЃС‚СЂР°РЅРёС†Р° РїРѕРІС‚РѕСЂСЏРµС‚ СЃРµРєС†РёРё Figma. РџРѕРІС‚РѕСЂРЅС‹Р№ РґРµРїР»РѕР№ РЅРµ РґСѓР±Р»РёСЂСѓРµС‚ Р±Р»РѕРєРё.
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
				[ 'Preisversprechen, die nicht halten', 'Ein hoher Wunschpreis bringt dem Makler den Auftrag. Danach steht das Inserat monatelang und der Preis wird StГјck fГјr StГјck gesenkt.' ],
				[ 'Makler, die nicht zurГјckrufen', 'Nach der Unterschrift kommen keine RГјckmeldungen mehr, und Sie erfahren wochenlang nichts Гјber den Stand.' ],
				[ 'Druck statt Beratung', 'Sie sollen sich schnell entscheiden, weil angeblich andere KГ¤ufer schon warten.' ],
				[ 'Unklare Provision', 'Was der Verkauf kostet und was darin enthalten ist, bleibt bis zum Schluss vage.' ],
				[ 'Гњbergang', 'Was der Verkauf kostet und was darin enthalten ist, bleibt bis zum Schluss vage.' ],
			];
			const items = defaults.map( ( [ title, text ], i ) => ( { title, text, iconId: iconMedia[ i ].id, iconUrl: iconMedia[ i ].source_url } ) );
			raw += `\n\n<!-- wp:library/pain-points ${ JSON.stringify( { items } ) } /-->`;
			changed = true;
		}
		{
			const cardDefaults = [
				[ 'Immobilie verkaufen', 'Von der Bewertung Гјber die Vermarktung bis zur Гњbergabe Гјbernehme ich den ganzen Verkauf fГјr Sie.' ],
				[ 'Immobilienbewertung', 'Sie erfahren realistisch, was Ihre Immobilie wert ist, ohne Гјberzogene Versprechen und ohne Verpflichtung.' ],
				[ 'Immobilie vermieten', 'Sie bekommen sorgfГ¤ltig ausgewГ¤hlte Mieter, und ich kГјmmere mich um BonitГ¤t, Vertrag und Гњbergabe.' ],
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
				ctaUrl: '#',
			} ) } /-->`;
			changed = true;
		}
		if ( ! raw.includes( 'wp:library/referral-cta' ) ) {
			raw += `\n\n<!-- wp:library/referral-cta ${ JSON.stringify( {
				imageId: referralMedia.id,
					imageUrl: referralMedia.source_url,
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
				backgroundId: consultationBgMedia.id,
					backgroundUrl: consultationBgMedia.source_url,
				buttonUrl: '#',
			} ) } /-->`;
			changed = true;
		}
		if ( changed ) {
			await api( `/wp-json/wp/v2/pages/${ page.id }`, {
				method: 'POST',
				body: JSON.stringify( { content: raw } ),
			} );
			console.log( 'РЎРµРєС†РёРё С‚РµСЃС‚РѕРІРѕР№ СЃС‚СЂР°РЅРёС†С‹ СЃРёРЅС…СЂРѕРЅРёР·РёСЂРѕРІР°РЅС‹.' );
		}
	}

	console.log( 'РўРµРјР° rosenberger Р°РєС‚РёРІРЅР°:', themeActive ? 'вњ…' : 'вќЊ' );
	console.log( 'РџР»Р°РіРёРЅ rosenberger-core Р°РєС‚РёРІРµРЅ:', coreActive ? 'вњ…' : 'вќЊ' );

	await neutralizeLibrarySnippets();

	if ( ! themeActive || ! coreActive ) { console.log( '\nвљ пёЏ  Р§С‚Рѕ-С‚Рѕ РЅРµ Р°РєС‚РёРІРёСЂРѕРІР°Р»РѕСЃСЊ вЂ” РїСЂРѕРІРµСЂСЊ РІСЂСѓС‡РЅСѓСЋ.' ); process.exit( 1 ); }
	console.log( '\nвњ… РџСЂРѕРµРєС‚ СЂР°Р·РІС‘СЂРЅСѓС‚: С‚РµРјР° rosenberger + РїР»Р°РіРёРЅ rosenberger-core. РЎРЅРёРїРїРµС‚С‹ РѕР±РµР·РІСЂРµР¶РµРЅС‹.' );
	console.log( 'РќР°СЃС‚СЂРѕР№РєРё СЃР°Р№С‚Р°: ' + BASE + '/wp-admin/admin.php?page=rosenberger-settings' );
	console.log( 'РўРµСЃС‚-СЃС‚СЂР°РЅРёС†Р°:   ' + BASE + '/hero-cover-test/' );
};

main().catch( ( e ) => { console.error( e ); process.exit( 1 ); } );
