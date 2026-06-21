/**
 * Seed-скрипт: создаёт демо-объекты (CPT property) через WP REST API.
 *
 * Использование:
 *   node scripts/seed-properties.mjs           — добавляет объекты
 *   node scripts/seed-properties.mjs --reset   — сначала удаляет все существующие
 *
 * Требует: .env с WP_URL, WP_USER, WP_APP_PASSWORD
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

const api = ( path, opts = {} ) =>
	fetch( `${ BASE }/wp-json/wp/v2${ path }`, {
		...opts,
		headers: {
			Authorization: AUTH,
			'Content-Type': 'application/json',
			...( opts.headers || {} ),
		},
	} ).then( r => r.json() );

// ── Helpers ──────────────────────────────────────────────────────────────────

async function ensureTerm( taxonomy, name, parent = 0 ) {
	const slug = name
		.toLowerCase()
		.replace( /[äöü]/g, c => ( { ä: 'ae', ö: 'oe', ü: 'ue' } )[ c ] )
		.replace( /\s+/g, '-' )
		.replace( /[^a-z0-9-]/g, '' );

	const existing = await api( `/${ taxonomy }?slug=${ slug }&per_page=1` );
	if ( Array.isArray( existing ) && existing[ 0 ] ) return existing[ 0 ].id;

	const payload = { name, slug };
	if ( parent ) payload.parent = parent;
	const created = await api( `/${ taxonomy }`, { method: 'POST', body: JSON.stringify( payload ) } );
	if ( ! created.id ) throw new Error( `Не удалось создать термин "${ name }": ${ JSON.stringify( created ) }` );
	return created.id;
}

async function deleteAll() {
	console.log( '🗑  Удаляем существующие объекты...' );
	let page = 1;
	let deleted = 0;
	while ( true ) {
		const posts = await api( `/property?per_page=100&page=${ page }&status=any` );
		if ( ! Array.isArray( posts ) || ! posts.length ) break;
		for ( const p of posts ) {
			await api( `/property/${ p.id }?force=true`, { method: 'DELETE' } );
			deleted++;
		}
		page++;
	}
	console.log( `   Удалено: ${ deleted }\n` );
}

// ── Данные ───────────────────────────────────────────────────────────────────

const TYPES  = [ 'Wohnung', 'Haus', 'Grundstück', 'Gewerbe' ];
const CITIES = [ 'Feldkirch', 'Dornbirn', 'Bludenz', 'Bregenz', 'Hohenems', 'Lustenau' ];

const SAMPLES = [
	{
		title:   'Moderne 4-Zimmer-Wohnung in Feldkirch',
		excerpt: 'Helle und großzügige Wohnung in ruhiger Lage mit Balkon und Tiefgaragenstellplatz. Neuwertig, sofort beziehbar.',
		type:    'Wohnung', city: 'Feldkirch',
		meta: { property_price: 'Auf Anfrage', property_area: 'ca. 130 m²', property_rooms: '4', property_status: 'Verfügbar' },
	},
	{
		title:   'Einfamilienhaus mit Garten in Dornbirn',
		excerpt: 'Gepflegtes Einfamilienhaus mit großem Garten, Garage und modernisierter Küche. Ideal für Familien.',
		type:    'Haus', city: 'Dornbirn',
		meta: { property_price: '€ 680.000', property_area: 'ca. 220 m²', property_rooms: '6', property_status: 'Verfügbar' },
	},
	{
		title:   'Ruhige 3-Zimmer-Wohnung in Bludenz',
		excerpt: 'Gemütliche Wohnung mit Bergblick, Loggia und Kellerabteil. Sehr gepflegte Anlage mit Aufzug.',
		type:    'Wohnung', city: 'Bludenz',
		meta: { property_price: 'Auf Anfrage', property_area: 'ca. 85 m²', property_rooms: '3', property_status: 'Reserviert' },
	},
	{
		title:   'Exklusives Penthouse am Bodensee',
		excerpt: 'Einzigartiges Penthouse mit Panoramablick auf den Bodensee. Hochwertige Ausstattung, große Dachterrasse.',
		type:    'Wohnung', city: 'Bregenz',
		meta: { property_price: '€ 1.250.000', property_area: 'ca. 180 m²', property_rooms: '5', property_status: 'Verfügbar' },
	},
	{
		title:   'Erschlossenes Baugrundstück in Hohenems',
		excerpt: 'Sonnige Hanglage mit Fernsicht. Bebauungsplan liegt vor, alle Erschließungen vorhanden.',
		type:    'Grundstück', city: 'Hohenems',
		meta: { property_price: 'Auf Anfrage', property_area: 'ca. 800 m²', property_rooms: '', property_status: 'Verfügbar' },
	},
	{
		title:   'Gepflegtes Reihenhaus in Feldkirch',
		excerpt: 'Modernes Reihenhaus mit 3 Etagen, Gartenanteil und Doppelgarage. Energiesparsam und sofort beziehbar.',
		type:    'Haus', city: 'Feldkirch',
		meta: { property_price: '€ 520.000', property_area: 'ca. 160 m²', property_rooms: '5', property_status: 'Verfügbar' },
	},
];

// ── Main ─────────────────────────────────────────────────────────────────────

// Запись мета через Code Snippets (ACF может блокировать meta в WP REST API)
async function writeMeta( postIds, samples ) {
	const phpLines = postIds.map( ( id, i ) => {
		const m = samples[ i ].meta;
		return Object.entries( m ).map( ( [ k, v ] ) =>
			`update_post_meta(${ id },'${ k }',wp_slash('${ v.replace( /'/g, "\\'" ) }'));`
		).join( ' ' );
	} ).join( '\n' );

	const res = await api( '/wp-json/code-snippets/v1/snippets', {
		method: 'POST',
		body: JSON.stringify( { title: 'seed: write property meta', code: phpLines, active: true, scope: 'global' } ),
	} );
	const snipId = res.body?.id ?? res.id;
	if ( ! snipId ) { console.warn( '   ⚠ Snippet создать не удалось, мета не записана через CS.' ); return; }

	// Триггерим страницу чтобы сниппет выполнился
	await fetch( `${ BASE }/objekte/?seed_trigger=${ Date.now() }` );

	// Деактивируем и очищаем
	await api( `/wp-json/code-snippets/v1/snippets/${ snipId }`, {
		method: 'POST', body: JSON.stringify( { active: false, code: '' } ),
	} );
	console.log( `   ✓ Мета записана через Code Snippets (snip #${ snipId })` );
}

async function run() {
	const reset = process.argv.includes( '--reset' );
	if ( reset ) await deleteAll();

	console.log( '📋 Проверяем/создаём таксономии...' );
	const typeIds = {};
	for ( const name of TYPES ) {
		typeIds[ name ] = await ensureTerm( 'property-type', name );
		console.log( `   Typ "${ name }" → id=${ typeIds[ name ] }` );
	}
	const cityIds = {};
	for ( const name of CITIES ) {
		cityIds[ name ] = await ensureTerm( 'property-city', name );
		console.log( `   Ort "${ name }" → id=${ cityIds[ name ] }` );
	}

	console.log( '\n🏠 Создаём объекты...' );
	const createdIds = [];
	for ( const s of SAMPLES ) {
		const body = {
			title:           s.title,
			excerpt:         s.excerpt,
			status:          'publish',
			'property-type': [ typeIds[ s.type ] ],
			'property-city': [ cityIds[ s.city ] ],
		};
		const created = await api( '/property', { method: 'POST', body: JSON.stringify( body ) } );
		if ( created.id ) {
			console.log( `   ✓ [${ s.type }/${ s.city }] "${ s.title }" → id=${ created.id }` );
			createdIds.push( created.id );
		} else {
			console.error( `   ✗ Ошибка: ${ JSON.stringify( created ) }` );
			createdIds.push( null );
		}
	}

	// Записываем мета-поля через Code Snippets (REST API meta может блокироваться ACF)
	console.log( '\n📝 Записываем мета-поля...' );
	const validPairs = createdIds.map( ( id, i ) => [ id, SAMPLES[ i ] ] ).filter( ( [ id ] ) => id );
	await writeMeta( validPairs.map( ( [ id ] ) => id ), validPairs.map( ( [ , s ] ) => s ) );

	console.log( `\n✅ Готово! Архив объектов: ${ BASE }/objekte/` );
}

run().catch( e => { console.error( e.message ); process.exit( 1 ); } );
