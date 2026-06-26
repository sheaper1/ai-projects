/**
 * Наполняет ОДИН реальный объект (CPT property) полным набором данных из макета
 * «single object», чтобы проверить шаблон single-property на живом URL.
 *
 * - грузит фото галереи + портрет маклера в медиатеку (идемпотентно по slug);
 * - ставит title / Objektbeschreibung / featured image через REST;
 * - пишет все property_* мета + данные маклера через Code Snippets (ACF блокирует REST-meta).
 *
 * Запуск: node scripts/seed-single-object.mjs
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

const POST_ID = 88; // einfamilienhaus-...-dornbirn → используем как демо-объект.
const MEDIA = resolve( root, 'projects/rosenberger/media/property' );
const MIME = { webp: 'image/webp' };

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

const BESCHREIBUNG = `Diese Wohnung liegt im zweiten Obergeschoss eines gepflegten Mehrparteienhauses aus dem Jahr 2019. Der offene Wohn- und Essbereich öffnet sich über große Fensterflächen zum Südbalkon. Drei weitere Räume lassen sich als Schlaf-, Kinder- oder Arbeitszimmer nutzen. Das Gebäude wird über einen Aufzug erschlossen und ist barrierefrei zugänglich. Zur Wohnung gehören ein Kellerabteil und ein Tiefgaragenstellplatz mit Vorrüstung für eine Wallbox.

Lage
Die Höchsterstraße liegt verkehrsberuhigt und doch zentrumsnah. Kindergarten, Volksschule und Nahversorger sind fußläufig erreichbar, der Bahnhof Dornbirn ist in wenigen Minuten erreicht.

Ausstattung
Echtholzparkett in den Wohnräumen, Fußbodenheizung, dreifachverglaste Fenster mit elektrischen Rollläden und eine hochwertige Einbauküche prägen den Innenausbau.

Sonstiges
Die monatlichen Betriebskosten sind unten aufgeführt. Eine Provisionsteilung sowie der Energieausweis liegen vor und werden im persönlichen Gespräch erläutert.`;

const META = {
	property_heading: 'Helle 4-Zimmer-Wohnung mit Bergblick<br>in Dornbirn',   // <br> как в Figma (2 строки)
	property_object_nr: 'RB-2048',
	property_address: 'Höchsterstraße 24, 6850 Dornbirn · Vorarlberg, Österreich',
	property_object_type: 'Eigentumswohnung',
	property_category: 'Wohnen · Kauf',
	property_status: 'Verfügbar',
	property_price: '685.000 €',
	property_price_sub: '5.805 €/m² · zzgl. Kaufnebenkosten',
	property_short_desc: 'Sonnendurchflutete Vier-Zimmer-Wohnung in ruhiger Lage von Dornbirn, mit großzügigem Balkon nach Süden und freiem Blick auf die Berge. Bezugsfertig ab Herbst 2026.',
	property_area: '118 m²',
	property_usable_area: '132 m²',
	property_plot_area: '— (Wohnung)',
	property_rooms: '4',
	property_bedrooms: '2',
	property_bathrooms: '2',
	property_toilets: '2',
	property_floor: '2. Obergeschoss',
	property_floors_total: '4',
	property_year: '2019',
	property_balcony_area: '14 m²',
	property_balcony_orientation: 'Süd',
	property_loggia_area: '---',
	property_loggia_count: '0',
	property_garden_area: '---',
	property_orientation: 'Süd-West',
	property_flooring: 'Parkett, Fliesen',
	property_lat: '47.4125',
	property_lng: '9.7417',
	property_poi: 'Fußweg zu ÖPNV | 4 Min | transit\nnächste Autobahn | 6 Min | highway\nnächster HBF | 7 Min | train\nnächster Flughafen | 35 Min | plane',
	// Аккордеоны — ЧЕРНОВИК (нет в Figma), клиент правит.
	property_acc_condition: 'Baujahr 2019, neuwertiger Zustand. Aufzug vorhanden, barrierefrei zugänglich. Keller­abteil und Tiefgaragenstellplatz mit Wallbox-Vorrüstung inklusive.',
	property_acc_equipment: 'Echtholzparkett, Fußbodenheizung, dreifachverglaste Fenster mit elektrischen Rollläden, hochwertige Einbauküche, Südbalkon.',
	property_acc_layout: 'Offener Wohn-/Essbereich, Küche, 2 Schlafzimmer, Arbeitszimmer, 2 Badezimmer, Vorraum, Abstellraum.',
	property_acc_prices: 'Kaufpreis 685.000 €. Betriebskosten ca. 220 €/Monat. Provisionsteilung möglich.',
	property_acc_energy: 'Energieausweis liegt vor: HWB 38 kWh/m²a, Klasse B.',
};

const AGENT = {
	agent_name: 'Alex Rosenberger',
	agent_role: 'Ihr Ansprechpartner',
	agent_phone: '+43 660 1234567',
	agent_email: 'name@rosenberger.at',
};

async function run() {
	console.log( '🖼  Lade Bilder…' );
	const hero = await ensureMedia( 'rosenberger-prop-dornbirn-hero', resolve( MEDIA, 'gallery/hero.webp' ) );
	const g1 = await ensureMedia( 'rosenberger-prop-dornbirn-1', resolve( MEDIA, 'gallery/img-1.webp' ) );
	const g2 = await ensureMedia( 'rosenberger-prop-dornbirn-2', resolve( MEDIA, 'gallery/img-2.webp' ) );
	const g3 = await ensureMedia( 'rosenberger-prop-dornbirn-3', resolve( MEDIA, 'gallery/img-3.webp' ) );
	const portrait = await ensureMedia( 'rosenberger-agent-portrait', resolve( MEDIA, 'agent-portrait.webp' ) );
	const galleryIds = [ hero.id, g1.id, g2.id, g3.id ].join( ',' );
	console.log( '   gallery ids:', galleryIds, '| portrait:', portrait.source_url );

	console.log( '✏️  REST: Titel / Beschreibung / Beitragsbild…' );
	// Подзаголовки Lage/Ausstattung/Sonstiges → отдельные heading-блоки (жирные).
	const SUBHEADS = [ 'Lage', 'Ausstattung', 'Sonstiges' ];
	const content = BESCHREIBUNG.split( '\n\n' ).map( block => {
		const lines = block.split( '\n' );
		if ( SUBHEADS.includes( lines[ 0 ].trim() ) ) {
			const head = `<!-- wp:heading {"level":4} --><h4 class="wp-block-heading">${ lines[ 0 ].trim() }</h4><!-- /wp:heading -->`;
			const body = lines.slice( 1 ).join( '<br>' );
			return body ? `${ head }\n<!-- wp:paragraph --><p>${ body }</p><!-- /wp:paragraph -->` : head;
		}
		return `<!-- wp:paragraph --><p>${ block.replace( /\n/g, '<br>' ) }</p><!-- /wp:paragraph -->`;
	} ).join( '\n' );
	const upd = await api( `/wp-json/wp/v2/property/${ POST_ID }`, {
		method: 'POST',
		body: JSON.stringify( {
			title: 'Helle 4-Zimmer-Wohnung mit Bergblick in Dornbirn',
			content,
			excerpt: META.property_short_desc,
			featured_media: hero.id,
		} ),
	} );
	console.log( '   status', upd.status );

	console.log( '📝 Code Snippet: Meta + Makler…' );
	const payload = {
		meta: META,
		gallery: galleryIds,
		agent: { ...AGENT, agent_portrait: portrait.source_url },
	};
	const b64 = Buffer.from( JSON.stringify( payload ), 'utf8' ).toString( 'base64' );
	const code = `$d = json_decode(base64_decode('${ b64 }'), true);
$pid = ${ POST_ID };
foreach ($d['meta'] as $k=>$v) { update_post_meta($pid,$k,wp_slash($v)); }
update_post_meta($pid,'property_gallery',$d['gallery']);
$c = get_option('rosenberger_contacts', array());
foreach ($d['agent'] as $k=>$v) { $c[$k]=$v; }
update_option('rosenberger_contacts',$c);`;

	const res = await api( '/wp-json/code-snippets/v1/snippets', {
		method: 'POST',
		body: JSON.stringify( { title: 'seed: single object', code, active: true, scope: 'global' } ),
	} );
	const snipId = res.body?.id;
	if ( ! snipId ) { console.warn( '   ⚠ snippet fehlgeschlagen:', JSON.stringify( res.body ).slice( 0, 200 ) ); return; }
	await fetch( `${ BASE }/objekte/?seed_trigger=${ Date.now() }` );
	await api( `/wp-json/code-snippets/v1/snippets/${ snipId }`, {
		method: 'POST', body: JSON.stringify( { active: false, code: '' } ),
	} );
	console.log( `   ✓ Meta geschrieben (snip #${ snipId })` );
	console.log( `\n✅ Fertig: ${ BASE }/objekte/einfamilienhaus-mit-garten-in-dornbirn/` );
}

run().catch( e => { console.error( e.message ); process.exit( 1 ); } );
