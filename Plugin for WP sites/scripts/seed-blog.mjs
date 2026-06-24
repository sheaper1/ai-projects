/**
 * Seed-демо блога: страница «Blog» (page_for_posts) + рубрика + 7 статей с
 * обложками, контентом (h2/h3 для оглавления) и категориями.
 *
 * Идемпотентно: посты/страница/рубрика ищутся по slug и обновляются, не плодятся.
 * Обложки и body-изображения берутся из уже загруженной медиатеки (по slug).
 *
 *   node scripts/seed-blog.mjs
 */

import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve( fileURLToPath( new URL( '.', import.meta.url ) ), '..' );
const env = Object.fromEntries(
	fs
		.readFileSync( path.join( root, '.env' ), 'utf8' )
		.split( /\r?\n/ )
		.filter( ( l ) => l && ! l.startsWith( '#' ) )
		.map( ( l ) => {
			const i = l.indexOf( '=' );
			return [ l.slice( 0, i ).trim(), l.slice( i + 1 ).trim() ];
		} )
);

const BASE = ( env.WP_URL || '' ).replace( /\/$/, '' );
const AUTH = 'Basic ' + Buffer.from( env.WP_USER + ':' + env.WP_APP_PASSWORD ).toString( 'base64' );

async function api( route, opts = {} ) {
	const res = await fetch( BASE + '/wp-json' + route, {
		...opts,
		headers: { Authorization: AUTH, 'Content-Type': 'application/json', ...( opts.headers || {} ) },
	} );
	const text = await res.text();
	let data;
	try {
		data = JSON.parse( text );
	} catch {
		data = text;
	}
	if ( ! res.ok ) {
		// Известный сайд-эффект: 500 при сохранении, но контент сохраняется.
		console.warn( `  ⚠ ${ res.status } ${ route } — проверю результат` );
	}
	return data;
}

// Карта slug → {id, url} по медиатеке.
async function mediaMap() {
	const map = {};
	for ( let page = 1; page <= 3; page++ ) {
		const items = await api( `/wp/v2/media?per_page=60&page=${ page }&media_type=image&_fields=id,slug,source_url` );
		if ( ! Array.isArray( items ) || ! items.length ) break;
		items.forEach( ( m ) => ( map[ m.slug ] = { id: m.id, url: m.source_url } ) );
	}
	return map;
}

const COVER_SLUGS = [
	'rosenberger-ref-property-gallery-hero-webp',
	'rosenberger-prop-dornbirn-hero',
	'rosenberger-ref-property-gallery-img-1-webp',
	'rosenberger-bw-hero',
	'rosenberger-vm-hero',
	'rosenberger-iv-hero',
	'rosenberger-ref-cards-card-1-jpg',
	'rosenberger-prop-dornbirn-2',
];
const BODY_SLUG = 'rosenberger-ref-property-gallery-img-2-webp';

function body( bodyImgUrl ) {
	const p = ( t ) =>
		`<!-- wp:paragraph --><p>${ t }</p><!-- /wp:paragraph -->`;
	const h2 = ( t ) => `<!-- wp:heading --><h2 class="wp-block-heading">${ t }</h2><!-- /wp:heading -->`;
	const h3 = ( t ) => `<!-- wp:heading {"level":3} --><h3 class="wp-block-heading">${ t }</h3><!-- /wp:heading -->`;
	const img = bodyImgUrl
		? `<!-- wp:image --><figure class="wp-block-image"><img src="${ bodyImgUrl }" alt=""/></figure><!-- /wp:image -->`
		: '';
	const lorem =
		'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur.';
	return [
		p( 'Der Immobilienmarkt in Vorarlberg verändert sich stetig. In diesem Beitrag zeigen wir die wichtigsten Entwicklungen und worauf Eigentümer jetzt achten sollten.' ),
		h2( 'Der aktuelle Markt im Überblick' ),
		p( lorem ),
		h3( 'Angebot und Nachfrage' ),
		p( lorem ),
		h3( 'Preisentwicklung der letzten Jahre' ),
		p( lorem ),
		img,
		h2( 'Worauf Eigentümer achten sollten' ),
		p( lorem ),
		h3( 'Die richtige Vorbereitung' ),
		p( lorem ),
		h2( 'Fazit' ),
		p( lorem ),
	].join( '\n' );
}

const POSTS = [
	{ slug: 'gruenflaechen-revolution', title: 'Die Grünflächen-Revolution: ist das lebende Gebäude die Zukunft der Städte?' },
	{ slug: 'immobilie-verkaufen-leitfaden', title: 'Immobilie verkaufen in Vorarlberg: der komplette Leitfaden' },
	{ slug: 'richtiger-verkaufspreis', title: 'Wie Sie den richtigen Verkaufspreis für Ihr Haus finden' },
	{ slug: 'wohnung-vermieten-fehler', title: 'Wohnung vermieten: 7 Fehler, die Sie vermeiden sollten' },
	{ slug: 'energieausweis-2026', title: 'Energieausweis 2026: was Eigentümer jetzt wissen müssen' },
	{ slug: 'home-staging', title: 'Home Staging: so verkaufen Sie schneller und teurer' },
	{ slug: 'nachhaltiges-bauen', title: 'Nachhaltiges Bauen: Trends und Förderungen in Österreich' },
];

async function findBySlug( type, slug ) {
	const items = await api( `/wp/v2/${ type }?slug=${ slug }&status=publish,draft,any&_fields=id` );
	return Array.isArray( items ) && items.length ? items[ 0 ].id : 0;
}

async function ensureCategory() {
	const found = await api( '/wp/v2/categories?slug=immobilienwissen&_fields=id' );
	if ( Array.isArray( found ) && found.length ) return found[ 0 ].id;
	const c = await api( '/wp/v2/categories', { method: 'POST', body: JSON.stringify( { name: 'Immobilienwissen', slug: 'immobilienwissen' } ) } );
	return c.id;
}

async function main() {
	if ( ! BASE ) throw new Error( 'WP_URL не задан в .env' );
	const media = await mediaMap();
	const covers = COVER_SLUGS.map( ( s ) => media[ s ]?.id ).filter( Boolean );
	const bodyUrl = media[ BODY_SLUG ]?.url || '';
	const catId = await ensureCategory();
	console.log( 'Рубрика Immobilienwissen:', catId, '| обложек:', covers.length );

	// Страница «Blog» + назначить как page_for_posts.
	let blogId = await findBySlug( 'pages', 'blog' );
	if ( ! blogId ) {
		const pg = await api( '/wp/v2/pages', { method: 'POST', body: JSON.stringify( { title: 'Blog', slug: 'blog', status: 'publish', content: '' } ) } );
		blogId = pg.id || ( await findBySlug( 'pages', 'blog' ) );
	}
	await api( '/wp/v2/settings', { method: 'POST', body: JSON.stringify( { page_for_posts: blogId, show_on_front: 'page' } ) } );
	console.log( 'Страница Blog:', blogId, '→ page_for_posts' );

	let i = 0;
	for ( const post of POSTS ) {
		const payload = {
			title: post.title,
			slug: post.slug,
			status: 'publish',
			content: body( bodyUrl ),
			excerpt: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt.',
			categories: [ catId ],
			featured_media: covers[ i % covers.length ] || 0,
		};
		const existing = await findBySlug( 'posts', post.slug );
		if ( existing ) {
			await api( `/wp/v2/posts/${ existing }`, { method: 'POST', body: JSON.stringify( payload ) } );
			console.log( '  ~ обновлён', post.slug, '#' + existing );
		} else {
			const created = await api( '/wp/v2/posts', { method: 'POST', body: JSON.stringify( payload ) } );
			const id = created.id || ( await findBySlug( 'posts', post.slug ) );
			console.log( '  + создан', post.slug, '#' + id );
		}
		i++;
	}

	console.log( '\n✅ Готово. Blog:', BASE + '/blog/' );
}

main().catch( ( e ) => {
	console.error( e );
	process.exit( 1 );
} );
