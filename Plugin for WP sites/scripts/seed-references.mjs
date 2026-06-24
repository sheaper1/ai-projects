/**
 * Seed-скрипт: демо-референсы (CPT reference) — проданные объекты + отзыв клиента.
 *
 *   node scripts/seed-references.mjs           — создаёт/обновляет референсы
 *   node scripts/seed-references.mjs --reset   — сначала удаляет существующие
 *
 * - грузит фото в медиатеку (идемпотентно по slug, переиспользует media/property|cards);
 * - ставит title / Objektbeschreibung / featured image / термины через REST;
 * - пишет property_* + reference_* мета + property_gallery через Code Snippets
 *   (ACF блокирует REST-meta на staging).
 *
 * Тексты Objektbeschreibung — ЧЕРНОВИК (нет в Figma), клиент правит.
 * Требует .env: WP_URL, WP_USER, WP_APP_PASSWORD
 */

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
const MEDIA = resolve( root, 'projects/rosenberger/media' );
const MIME = { webp: 'image/webp', jpg: 'image/jpeg' };

const api = async ( path, opts = {} ) => {
	const res = await fetch( `${ BASE }${ path }`, {
		...opts,
		headers: { Authorization: AUTH, 'Content-Type': 'application/json', ...( opts.headers || {} ) },
	} );
	const text = await res.text();
	let body; try { body = JSON.parse( text ); } catch { body = text; }
	return { status: res.status, body };
};

const ensureMedia = async ( slug, file, ext = 'webp' ) => {
	const found = await api( `/wp-json/wp/v2/media?slug=${ encodeURIComponent( slug ) }&per_page=1` );
	if ( Array.isArray( found.body ) && found.body[ 0 ] ) return found.body[ 0 ];
	const res = await fetch( `${ BASE }/wp-json/wp/v2/media`, {
		method: 'POST',
		headers: {
			Authorization: AUTH,
			'Content-Type': MIME[ ext ],
			'Content-Disposition': `attachment; filename="${ slug }.${ ext }"`,
		},
		body: readFileSync( file ),
	} );
	const body = await res.json();
	if ( ! res.ok ) throw new Error( `media ${ slug }: ${ body.message || res.status }` );
	return body;
};

const ensureTerm = async ( taxonomy, name ) => {
	const slug = name.toLowerCase()
		.replace( /[äöü]/g, c => ( { ä: 'ae', ö: 'oe', ü: 'ue' } )[ c ] )
		.replace( /\s+/g, '-' ).replace( /[^a-z0-9-]/g, '' );
	const existing = await api( `/wp-json/wp/v2/${ taxonomy }?slug=${ slug }&per_page=1` );
	if ( Array.isArray( existing.body ) && existing.body[ 0 ] ) return existing.body[ 0 ].id;
	const created = await api( `/wp-json/wp/v2/${ taxonomy }`, { method: 'POST', body: JSON.stringify( { name, slug } ) } );
	if ( ! created.body?.id ) throw new Error( `term "${ name }": ${ JSON.stringify( created.body ) }` );
	return created.body.id;
};

const DESC = ( ort ) => `Diese Immobilie wurde von uns erfolgreich vermittelt. Der offene Wohn- und Essbereich, große Fensterflächen und eine durchdachte Raumaufteilung haben die Käufer überzeugt.

Lage
Ruhige und doch zentrumsnahe Lage in ${ ort }. Kindergarten, Schule und Nahversorger sind fußläufig erreichbar.

Ausstattung
Hochwertige Materialien, moderne Haustechnik und ein gepflegtes Gesamtbild prägen das Objekt.

Sonstiges
Die Übergabe verlief reibungslos und termingerecht. Gerne begleiten wir auch Ihren Verkauf.`;

const SAMPLES = [
	{
		title: 'Helle 4-Zimmer-Wohnung mit Bergblick in Dornbirn',
		excerpt: 'Sonnendurchflutete Vier-Zimmer-Wohnung in ruhiger Lage von Dornbirn, mit großzügigem Südbalkon und freiem Blick auf die Berge. Erfolgreich vermittelt.',
		type: 'Wohnung', city: 'Dornbirn', featured: 'property/gallery/hero.webp', galleryFiles: [ 'property/gallery/hero.webp', 'property/gallery/img-1.webp', 'property/gallery/img-2.webp' ],
		meta: {
			property_object_type: 'Eigentumswohnung', property_status: 'Verkauft',
			property_address: 'Dornbirn · Vorarlberg, Österreich',
			property_price: '685.000 €', property_price_sub: '5.805 €/m² · zzgl. Kaufnebenkosten',
			property_area: '118 m²', property_rooms: '4', property_bedrooms: '2',
			property_bathrooms: '2', property_floor: '2. OG', property_year: '2019',
			property_plot_area: '—',
		},
		ref: { reference_quote: 'Die Zusammenarbeit war von Anfang bis Ende professionell und persönlich. Unsere Wohnung wurde schnell und zum fairen Preis vermittelt – wir fühlten uns jederzeit bestens betreut.', reference_author: 'Karsten G.', reference_location: 'Dornbirn', reference_rating: '5' },
	},
	{
		title: 'Einfamilienhaus mit Garten in Feldkirch',
		excerpt: 'Gepflegtes Einfamilienhaus mit großem Garten und Doppelgarage in beliebter Wohnlage. Innerhalb weniger Wochen verkauft.',
		type: 'Haus', city: 'Feldkirch', featured: 'cards/card-1.jpg', galleryFiles: [ 'cards/card-1.jpg', 'property/gallery/img-2.webp', 'property/gallery/img-3.webp' ],
		meta: {
			property_object_type: 'Einfamilienhaus', property_status: 'Verkauft',
			property_address: 'Feldkirch · Vorarlberg, Österreich',
			property_price: '720.000 €', property_price_sub: 'zzgl. Kaufnebenkosten',
			property_area: '210 m²', property_rooms: '6', property_bedrooms: '4',
			property_bathrooms: '2', property_floor: '—', property_year: '2008',
			property_plot_area: 'ca. 650 m²',
		},
		ref: { reference_quote: 'Kompetent, ehrlich und immer erreichbar. Wir hätten uns keinen besseren Makler für den Verkauf unseres Familienhauses wünschen können.', reference_author: 'Anna M.', reference_location: 'Feldkirch', reference_rating: '5' },
	},
	{
		title: 'Moderne 3-Zimmer-Wohnung in Bregenz',
		excerpt: 'Neuwertige Drei-Zimmer-Wohnung mit Seeblick und Tiefgaragenstellplatz. Diskret und erfolgreich vermittelt.',
		type: 'Wohnung', city: 'Bregenz', featured: 'cards/card-2.jpg', galleryFiles: [ 'cards/card-2.jpg', 'property/gallery/img-1.webp', 'property/gallery/hero.webp' ],
		meta: {
			property_object_type: 'Eigentumswohnung', property_status: 'Verkauft',
			property_address: 'Bregenz · Vorarlberg, Österreich',
			property_price: '540.000 €', property_price_sub: 'zzgl. Kaufnebenkosten',
			property_area: '92 m²', property_rooms: '3', property_bedrooms: '2',
			property_bathrooms: '1', property_floor: '3. OG', property_year: '2017',
			property_plot_area: '—',
		},
		ref: { reference_quote: 'Schnelle Rückmeldungen, klare Kommunikation und ein top Ergebnis. Sehr empfehlenswert!', reference_author: 'John S.', reference_location: 'Bregenz', reference_rating: '5' },
	},
	{
		title: 'Sonniges Baugrundstück in Bludenz',
		excerpt: 'Erschlossenes Baugrundstück in ruhiger Hanglage mit Fernsicht. Käufer und Verkäufer waren rundum zufrieden.',
		type: 'Grundstück', city: 'Bludenz', featured: 'cards/card-3.jpg', galleryFiles: [ 'cards/card-3.jpg', 'property/gallery/img-3.webp' ],
		meta: {
			property_object_type: 'Baugrundstück', property_status: 'Verkauft',
			property_address: 'Bludenz · Vorarlberg, Österreich',
			property_price: 'Auf Anfrage', property_price_sub: '',
			property_area: '', property_rooms: '', property_bedrooms: '',
			property_bathrooms: '', property_floor: '', property_year: '',
			property_plot_area: 'ca. 800 m²',
		},
		ref: { reference_quote: 'Der gesamte Ablauf war transparent und unkompliziert. Wir würden jederzeit wieder auf diese Betreuung setzen.', reference_author: 'Familie K.', reference_location: 'Bludenz', reference_rating: '5' },
	},
];

async function deleteAll() {
	console.log( '🗑  Lösche bestehende Referenzen…' );
	let page = 1, deleted = 0;
	while ( true ) {
		const r = await api( `/wp-json/wp/v2/reference?per_page=100&page=${ page }&status=any` );
		if ( ! Array.isArray( r.body ) || ! r.body.length ) break;
		for ( const p of r.body ) { await api( `/wp-json/wp/v2/reference/${ p.id }?force=true`, { method: 'DELETE' } ); deleted++; }
		page++;
	}
	console.log( `   Gelöscht: ${ deleted }\n` );
}

const extOf = ( f ) => ( f.endsWith( '.jpg' ) ? 'jpg' : 'webp' );

async function run() {
	if ( process.argv.includes( '--reset' ) ) await deleteAll();

	console.log( '📋 Taxonomien…' );
	const typeIds = {}, cityIds = {};
	for ( const s of SAMPLES ) {
		if ( ! typeIds[ s.type ] ) typeIds[ s.type ] = await ensureTerm( 'reference-type', s.type );
		if ( ! cityIds[ s.city ] ) cityIds[ s.city ] = await ensureTerm( 'reference-city', s.city );
	}

	console.log( '🖼  Bilder…' );
	const mediaCache = {};
	const getMedia = async ( file ) => {
		if ( mediaCache[ file ] ) return mediaCache[ file ];
		const slug = 'rosenberger-ref-' + file.replace( /[\/.]/g, '-' );
		const m = await ensureMedia( slug, resolve( MEDIA, file ), extOf( file ) );
		mediaCache[ file ] = m;
		return m;
	};

	console.log( '🏆 Referenzen…' );
	const writes = [];
	for ( const s of SAMPLES ) {
		const featured = await getMedia( s.featured );
		const gallery = [];
		for ( const f of s.galleryFiles ) gallery.push( ( await getMedia( f ) ).id );

		// Найти по slug или создать.
		const wantSlug = s.title.toLowerCase()
			.replace( /[äöü]/g, c => ( { ä: 'ae', ö: 'oe', ü: 'ue' } )[ c ] )
			.replace( /[^a-z0-9]+/g, '-' ).replace( /^-|-$/g, '' );
		const found = await api( `/wp-json/wp/v2/reference?slug=${ wantSlug }&per_page=1&status=any` );
		let id = Array.isArray( found.body ) && found.body[ 0 ] ? found.body[ 0 ].id : 0;

		const content = DESC( s.city ).split( '\n\n' ).map( block => {
			const lines = block.split( '\n' );
			if ( [ 'Lage', 'Ausstattung', 'Sonstiges' ].includes( lines[ 0 ].trim() ) ) {
				const head = `<!-- wp:heading {"level":3} --><h3 class="wp-block-heading">${ lines[ 0 ].trim() }</h3><!-- /wp:heading -->`;
				const body = lines.slice( 1 ).join( '<br>' );
				return body ? `${ head }\n<!-- wp:paragraph --><p>${ body }</p><!-- /wp:paragraph -->` : head;
			}
			return `<!-- wp:paragraph --><p>${ block.replace( /\n/g, '<br>' ) }</p><!-- /wp:paragraph -->`;
		} ).join( '\n' );

		const body = {
			title: s.title, excerpt: s.excerpt, content, status: 'publish',
			featured_media: featured.id,
			'reference-type': [ typeIds[ s.type ] ],
			'reference-city': [ cityIds[ s.city ] ],
		};
		const res = id
			? await api( `/wp-json/wp/v2/reference/${ id }`, { method: 'POST', body: JSON.stringify( body ) } )
			: await api( `/wp-json/wp/v2/reference`, { method: 'POST', body: JSON.stringify( body ) } );
		id = res.body?.id || id;
		console.log( `   ${ id ? '✓' : '✗' } [${ s.type }/${ s.city }] ${ s.title } → id=${ id }` );
		if ( id ) writes.push( { id, meta: { ...s.meta, ...s.ref }, gallery: gallery.join( ',' ) } );
	}

	console.log( '\n📝 Meta via Code Snippets…' );
	const b64 = Buffer.from( JSON.stringify( writes ), 'utf8' ).toString( 'base64' );
	const code = `$rows = json_decode(base64_decode('${ b64 }'), true);
foreach ($rows as $row) {
  foreach ($row['meta'] as $k=>$v) { update_post_meta($row['id'], $k, wp_slash($v)); }
  update_post_meta($row['id'], 'property_gallery', $row['gallery']);
}`;
	const res = await api( '/wp-json/code-snippets/v1/snippets', {
		method: 'POST',
		body: JSON.stringify( { title: 'seed: references meta', code, active: true, scope: 'global' } ),
	} );
	const snipId = res.body?.id;
	if ( ! snipId ) { console.warn( '   ⚠ snippet fehlgeschlagen:', JSON.stringify( res.body ).slice( 0, 200 ) ); return; }
	await fetch( `${ BASE }/?seed_trigger=${ Date.now() }` );
	await api( `/wp-json/code-snippets/v1/snippets/${ snipId }`, { method: 'POST', body: JSON.stringify( { active: false, code: '' } ) } );
	console.log( `   ✓ Meta geschrieben (snip #${ snipId })` );

	console.log( `\n✅ Fertig. Archiv: ${ BASE }/references/` );
}

run().catch( e => { console.error( e.message ); process.exit( 1 ); } );
