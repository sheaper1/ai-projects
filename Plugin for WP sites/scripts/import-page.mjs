// Data-driven генератор страниц: ОДИН движок + спека страницы (scripts/pages/<slug>.mjs).
// Обобщает однотипные import-<slug>.mjs: общий код (env / api / медиа / create-or-update)
// живёт здесь один раз, а в спеке остаётся только контент конкретной страницы.
//
// Запуск:
//   node scripts/import-page.mjs <spec>          — создать/обновить страницу на staging
//   node scripts/import-page.mjs <spec> --dry    — собрать разметку офлайн (без сети и без записи)
//   npm run page -- <spec>            / npm run page -- <spec> --dry
// <spec> — имя файла в scripts/pages/ без расширения (например uber-mich).
//
// Спека scripts/pages/<slug>.mjs экспортирует:
//   title  : string                              — заголовок страницы
//   slug   : string                              — slug страницы
//   media  : { key: { slug, remote?, ext? } }    — remote → залить если нет; без remote → только найти
//   blocks : (m) => [ { name, attrs } ]          — m[key] = { id, url } (0/'' если медиа нет)
//
// Старые import-<slug>.mjs специально НЕ трогаются — это путь отката.

import { readFileSync } from 'node:fs';
import { resolve, dirname } from 'node:path';
import { fileURLToPath, pathToFileURL } from 'node:url';

const root = resolve( dirname( fileURLToPath( import.meta.url ) ), '..' );

const args = process.argv.slice( 2 );
const dry = args.includes( '--dry' );
const specName = args.find( ( a ) => ! a.startsWith( '--' ) );

if ( ! specName ) {
	console.error( 'Укажи спеку: node scripts/import-page.mjs <spec> [--dry]' );
	process.exit( 1 );
}

const spec = await import( pathToFileURL( resolve( root, 'scripts/pages', `${ specName }.mjs` ) ).href );
for ( const k of [ 'title', 'slug', 'blocks' ] ) {
	if ( ! ( k in spec ) ) throw new Error( `Спека «${ specName }» не экспортирует «${ k }»` );
}

const NULL_MEDIA = { id: 0, url: '' };

// --- Разрешение медиа -------------------------------------------------------
// В --dry медиа не трогаем сеть: подставляем нули, чтобы проверить структуру разметки.
let media = {};
const mediaKeys = Object.keys( spec.media || {} );

if ( dry ) {
	media = Object.fromEntries( mediaKeys.map( ( k ) => [ k, NULL_MEDIA ] ) );
} else {
	const env = {};
	for ( const line of readFileSync( resolve( root, '.env' ), 'utf8' ).split( /\r?\n/ ) ) {
		const mm = line.match( /^([A-Z_]+)=(.*)$/ );
		if ( mm ) env[ mm[ 1 ] ] = mm[ 2 ];
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

	// Ищет медиа по slug; при наличии remote — заливает, если ещё нет. Возвращает { id, url }.
	const resolveOne = async ( key, { slug, remote, ext = 'jpg' } ) => {
		const found = await api( `/wp-json/wp/v2/media?slug=${ encodeURIComponent( slug ) }&per_page=1` );
		const existing = Array.isArray( found.body ) ? found.body[ 0 ] : null;
		if ( existing ) {
			console.log( `  ✓ ${ slug } (id=${ existing.id })` );
			return { id: existing.id, url: existing.source_url };
		}
		if ( ! remote ) {
			console.warn( `  ⚠  медиа не найдено: ${ slug } — блок будет без изображения` );
			return NULL_MEDIA;
		}
		console.log( `  ↑ загружаю ${ slug }...` );
		const buf = await fetch( remote ).then( ( r ) => r.arrayBuffer() ).then( ( b ) => Buffer.from( b ) );
		const MIME = { jpg: 'image/jpeg', png: 'image/png', webp: 'image/webp' };
		const res = await fetch( `${ BASE }/wp-json/wp/v2/media`, {
			method: 'POST',
			headers: {
				Authorization: AUTH,
				'Content-Type': MIME[ ext ] || 'image/jpeg',
				'Content-Disposition': `attachment; filename="${ slug }.${ ext }"`,
			},
			body: buf,
		} );
		const body = await res.json();
		if ( ! res.ok ) throw new Error( `Ошибка загрузки ${ slug }: ${ body.message || res.status }` );
		console.log( `  ✓ ${ slug } загружен (id=${ body.id })` );
		return { id: body.id, url: body.source_url };
	};

	console.log( '\n📦 Медиа...' );
	const resolved = await Promise.all( mediaKeys.map( ( k ) => resolveOne( k, spec.media[ k ] ) ) );
	media = Object.fromEntries( mediaKeys.map( ( k, i ) => [ k, resolved[ i ] ] ) );

	// Замыкаем api в верхнюю область для шага создания страницы.
	globalThis.__api = api;
	globalThis.__BASE = BASE;
}

// --- Сборка разметки --------------------------------------------------------
const blocks = spec.blocks( media );
const content = blocks
	.map( ( b ) => `<!-- wp:library/${ b.name } ${ JSON.stringify( b.attrs ) } /-->` )
	.join( '\n\n' );

if ( dry ) {
	console.log( `✓ Спека «${ specName }» собрана: ${ blocks.length } блок(ов), ${ content.length } символов разметки.` );
	console.log( blocks.map( ( b ) => `   - library/${ b.name }` ).join( '\n' ) );
	process.exit( 0 );
}

// --- Создать / обновить страницу --------------------------------------------
const api = globalThis.__api;
const BASE = globalThis.__BASE;

console.log( `\n📝 Создаю / обновляю страницу «${ spec.title }»...` );
const pages = await api( `/wp-json/wp/v2/pages?slug=${ encodeURIComponent( spec.slug ) }&status=any&per_page=1` );
let pageId;

if ( Array.isArray( pages.body ) && pages.body[ 0 ] ) {
	pageId = pages.body[ 0 ].id;
	const r = await api( `/wp-json/wp/v2/pages/${ pageId }`, {
		method: 'POST',
		body: JSON.stringify( { content, status: 'publish' } ),
	} );
	if ( ! r.ok ) throw new Error( `Ошибка обновления: ${ JSON.stringify( r.body ).slice( 0, 300 ) }` );
	console.log( `  ✓ Обновлена существующая страница id=${ pageId }` );
} else {
	const r = await api( '/wp-json/wp/v2/pages', {
		method: 'POST',
		body: JSON.stringify( { title: spec.title, slug: spec.slug, content, status: 'publish' } ),
	} );
	if ( ! r.ok ) throw new Error( `Ошибка создания: ${ JSON.stringify( r.body ).slice( 0, 300 ) }` );
	pageId = r.body.id;
	console.log( `  ✓ Создана страница id=${ pageId }` );
}

console.log( `\n✅ Готово! Страница: ${ BASE }/${ spec.slug }/` );
console.log( `   Редактор: ${ BASE }/wp-admin/post.php?post=${ pageId }&action=edit` );
