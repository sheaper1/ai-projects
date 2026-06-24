/**
 * Seed 4 региона-лендинга (CPT region): Bludenz, Bregenz, Dornbirn, Feldkirch.
 * Структура одинаковая; имя города предзаполнено в контенте (post_content блоками),
 * объекты фильтруются по property-city/reference-city = slug записи.
 *
 * Идемпотентно по slug. Тексты — ЧЕРНОВИК (клиент правит в Gutenberg / Meta-Box).
 *
 *   node scripts/seed-regions.mjs
 */

import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve( fileURLToPath( new URL( '.', import.meta.url ) ), '..' );
const env = Object.fromEntries(
	fs.readFileSync( path.join( root, '.env' ), 'utf8' ).split( /\r?\n/ ).filter( ( l ) => l && ! l.startsWith( '#' ) ).map( ( l ) => {
		const i = l.indexOf( '=' );
		return [ l.slice( 0, i ).trim(), l.slice( i + 1 ).trim() ];
	} )
);
const BASE = ( env.WP_URL || '' ).replace( /\/$/, '' );
const AUTH = 'Basic ' + Buffer.from( env.WP_USER + ':' + env.WP_APP_PASSWORD ).toString( 'base64' );

async function api( route, opts = {} ) {
	const res = await fetch( BASE + '/wp-json' + route, { ...opts, headers: { Authorization: AUTH, 'Content-Type': 'application/json', ...( opts.headers || {} ) } } );
	const t = await res.text();
	let d;
	try { d = JSON.parse( t ); } catch { d = t; }
	if ( ! res.ok ) console.warn( `  ⚠ ${ res.status } ${ route }` );
	return d;
}

async function mediaMap() {
	const map = {};
	for ( let p = 1; p <= 3; p++ ) {
		const items = await api( `/wp/v2/media?per_page=60&page=${ p }&media_type=image&_fields=id,slug,source_url` );
		if ( ! Array.isArray( items ) || ! items.length ) break;
		items.forEach( ( m ) => ( map[ m.slug ] = { id: m.id, url: m.source_url } ) );
	}
	return map;
}

// Идемпотентная загрузка локального файла в медиатеку по slug.
async function uploadMedia( slug, filePath, mime = 'image/webp' ) {
	const found = await api( `/wp/v2/media?slug=${ slug }&_fields=id,source_url` );
	if ( Array.isArray( found ) && found.length ) return { id: found[ 0 ].id, url: found[ 0 ].source_url };
	const buf = fs.readFileSync( filePath );
	const res = await fetch( BASE + '/wp-json/wp/v2/media', {
		method: 'POST',
		headers: {
			Authorization: AUTH,
			'Content-Type': mime,
			'Content-Disposition': `attachment; filename="${ slug }.webp"`,
		},
		body: buf,
	} );
	const d = await res.json();
	if ( d && d.id && d.slug !== slug ) {
		await api( `/wp/v2/media/${ d.id }`, { method: 'POST', body: j( { slug } ) } );
	}
	return d && d.id ? { id: d.id, url: d.source_url } : {};
}

async function ensureTerm( tax, slug, name ) {
	const found = await api( `/wp/v2/${ tax }?slug=${ slug }&_fields=id` );
	if ( Array.isArray( found ) && found.length ) return found[ 0 ].id;
	const c = await api( `/wp/v2/${ tax }`, { method: 'POST', body: JSON.stringify( { name, slug } ) } );
	return c.id;
}

// Обложка hero = Beitragsbild записи (клиент заменит). Демо-фото — реальные
// панорамы городов из Figma-дизайна (media/regions/hero-<city>.webp).
const CITIES = [
	{ slug: 'bludenz', name: 'Bludenz' },
	{ slug: 'bregenz', name: 'Bregenz' },
	{ slug: 'dornbirn', name: 'Dornbirn' },
	{ slug: 'feldkirch', name: 'Feldkirch' },
];

const j = ( o ) => JSON.stringify( o );

function content( city, media ) {
	const introImg = media[ 'rosenberger-iv-split' ] || media[ 'rosenberger-vm-split1' ] || {};
	const icon = ( s ) => ( media[ s ] || {} ).url || '';
	const ctaBg = '/wp-content/themes/rosenberger/assets/property/cta-bg.webp';

	const badge = media[ 'rosenberger-google-rating' ] || {};
	const trust = `<!-- wp:library/trust-bar ${ j( { badgeId: badge.id || 0, badgeUrl: badge.url || '' } ) } /-->`;

	const intro = `<!-- wp:library/split-cta ${ j( {
		heading: 'Sie suchen einen Immobilienmakler',
		headingItalic: `in ${ city.name }?`,
		text: `Ob Sie verkaufen, kaufen oder einfach wissen wollen, was Ihre Immobilie wert ist – Sie wollen jemanden, der den Markt in ${ city.name } wirklich kennt und ehrlich mit Ihnen umgeht. Ich kenne die Lagen vor Ort und sage Ihnen offen, was realistisch ist, beim Verkauf, beim Kauf oder bei der Bewertung – ohne Wunschzahl, die nur den Auftrag bringen soll.`,
		buttonText: 'Kostenlos beraten lassen',
		buttonUrl: '/kontakt/',
		imageId: introImg.id || 0,
		imageUrl: introImg.url || '',
	} ) } /-->`;

	const services = `<!-- wp:library/problem-cards ${ j( {
		heading: `Womit ich Sie in ${ city.name }`,
		headingItalic: 'unterstütze',
		intro: '',
		items: [
			{ title: 'Immobilie verkaufen', text: `Von der Bewertung über die Vermarktung bis zur Übergabe übernehme ich den ganzen Verkauf Ihrer Immobilie in ${ city.name }.`, iconId: 0, iconUrl: icon( 'rosenberger-card-icon-house' ) },
			{ title: 'Immobilienbewertung', text: `Sie erfahren realistisch, was Ihre Immobilie in ${ city.name } wert ist, ohne überzogene Versprechen und ohne Verpflichtung.`, iconId: 0, iconUrl: icon( 'rosenberger-card-icon-evaluation' ) },
			{ title: 'Immobilie vermieten', text: 'Sie bekommen sorgfältig ausgewählte Mieter, und ich kümmere mich um Bonität, Vertrag und Übergabe.', iconId: 0, iconUrl: icon( 'rosenberger-card-icon-valet' ) },
		],
	} ) } /-->`;

	const aboutBg = media[ 'rosenberger-about-bg' ] || {};
	const about = `<!-- wp:library/about ${ j( {
		titleMain: 'Darauf können Sie sich verlassen',
		text: 'Ich war selbst Käufer und habe erlebt, wie zäh und unehrlich der Ablauf sein kann. Genau das mache ich anders.',
		buttonText: 'Mehr über mich',
		buttonUrl: '/uber-mich/',
		backgroundId: aboutBg.id || 0,
		backgroundUrl: aboutBg.url || '',
		columns: 4,
		items: [
			{ title: 'Ehrliche Bewertung', text: 'Sie bekommen einen realistischen Preis, der zum Markt passt, keinen Wunschpreis, der nur den Auftrag bringen soll.' },
			{ title: 'Schnelle, persönliche Rückmeldung', text: 'Sie hören von mir, ohne nachfragen zu müssen.' },
			{ title: 'Kein Verkaufsdruck', text: 'Sie entscheiden in Ihrem Tempo, nicht in meinem.' },
			{ title: 'Ein Ansprechpartner', text: 'Vom ersten Anruf bis zur Unterschrift sprechen Sie mit mir, nicht mit wechselnden Mitarbeitern.' },
		],
	} ) } /-->`;

	const process = `<!-- wp:library/process-steps /-->`;

	const sold = `<!-- wp:library/region-properties ${ j( {
		source: 'reference', heading: 'Verkauft in', appendCity: true,
		subtitle: '', buttonText: 'Alle Referenzen ansehen', buttonUrl: '/references/', limit: 6,
	} ) } /-->`;

	const reviews = `<!-- wp:library/testimonials /-->`;

	const objects = `<!-- wp:library/region-properties ${ j( {
		source: 'property', heading: 'Aktuelle Objekte in', appendCity: true,
		subtitle: `Sie suchen in ${ city.name }? Hier sehen Sie, was ich gerade vermittle.`,
		buttonText: 'Alle Objekte ansehen', buttonUrl: '/objekte/', limit: 6,
	} ) } /-->`;

	const faq = `<!-- wp:library/faq-section ${ j( { heading: `Häufige Fragen zu ${ city.name }` } ) } /-->`;

	const cta = `<!-- wp:library/consultation-cta ${ j( { buttonUrl: '/kontakt/', backgroundUrl: ctaBg } ) } /-->`;

	return [ trust, intro, services, about, process, sold, reviews, objects, faq, cta ].join( '\n\n' );
}

async function findRegion( slug ) {
	const items = await api( `/wp/v2/region?slug=${ slug }&status=any&_fields=id` );
	return Array.isArray( items ) && items.length ? items[ 0 ].id : 0;
}

async function main() {
	if ( ! BASE ) throw new Error( 'WP_URL не задан' );
	const media = await mediaMap();
	// Google-бейдж (SVG) может быть вне основной выборки — догружаем по slug.
	for ( const slug of [ 'rosenberger-google-rating', 'rosenberger-about-bg' ] ) {
		if ( ! media[ slug ] ) {
			const b = await api( `/wp/v2/media?slug=${ slug }&_fields=id,source_url` );
			if ( Array.isArray( b ) && b[ 0 ] ) media[ slug ] = { id: b[ 0 ].id, url: b[ 0 ].source_url };
		}
	}

	for ( const city of CITIES ) {
		await ensureTerm( 'property-city', city.slug, city.name );
		await ensureTerm( 'reference-city', city.slug, city.name );
	}

	for ( const city of CITIES ) {
		const heroFile = path.join( root, `projects/rosenberger/media/regions/hero-${ city.slug }.webp` );
		const hero     = fs.existsSync( heroFile ) ? await uploadMedia( `rosenberger-region-hero-${ city.slug }`, heroFile ) : {};
		const cover    = hero.id || 0;
		console.log( '  hero', city.slug, '→ media #' + ( hero.id || '—' ) );
		const payload = {
			title: city.name,
			slug: city.slug,
			status: 'publish',
			featured_media: cover,
			content: content( city, media ),
			meta: {
				region_subtitle: `Ehrlich beraten in ${ city.name }, ob Sie verkaufen, kaufen oder den Wert Ihrer Immobilie wissen wollen.`,
				region_button_text: 'Kostenlos beraten lassen',
				region_button_url: '/kontakt/',
				region_note: 'Unverbindlich und kostenlos',
			},
		};
		const existing = await findRegion( city.slug );
		if ( existing ) {
			await api( `/wp/v2/region/${ existing }`, { method: 'POST', body: j( payload ) } );
			console.log( '  ~ обновлён регион', city.slug, '#' + existing );
		} else {
			const created = await api( '/wp/v2/region', { method: 'POST', body: j( payload ) } );
			const id = created.id || ( await findRegion( city.slug ) );
			console.log( '  + создан регион', city.slug, '#' + id );
		}
	}

	console.log( `\n✅ Готово. Пример: ${ BASE }/region/bludenz/` );
}

main().catch( ( e ) => { console.error( e ); process.exit( 1 ); } );
