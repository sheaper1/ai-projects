// Создаёт / обновляет страницу «Immobilie vermieten» через WP REST API.
// Запуск: node scripts/import-immobilie-vermieten.mjs
//
// Источник: Figma p1HKLfoMcOwtVUD5rI9V3P, UI Design, immobilie-vermieten
// 537:2700 (desktop) / 548:2123 (mobile). Собрано по плейбуку AGENTS §6a:
// почти всё — переиспользование блоков, новый код = опция imageLeft у split-cta.

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
const MIME = { svg: 'image/svg+xml', webp: 'image/webp', jpg: 'image/jpeg', png: 'image/png' };

const api = async ( path, opts = {} ) => {
	const res = await fetch( `${ BASE }${ path }`, {
		...opts,
		headers: { Authorization: AUTH, 'Content-Type': 'application/json', ...( opts.headers || {} ) },
	} );
	const text = await res.text();
	let body; try { body = JSON.parse( text ); } catch { body = text; }
	return { status: res.status, ok: res.ok, body };
};

const ensureMediaLocal = async ( slug, relPath, ext ) => {
	const found = await api( `/wp-json/wp/v2/media?slug=${ encodeURIComponent( slug ) }&per_page=1` );
	if ( Array.isArray( found.body ) && found.body[ 0 ] ) {
		console.log( `  ✓ ${ slug } (id=${ found.body[ 0 ].id })` );
		return found.body[ 0 ];
	}
	const buf = readFileSync( resolve( root, relPath ) );
	const res = await fetch( `${ BASE }/wp-json/wp/v2/media`, {
		method: 'POST',
		headers: {
			Authorization: AUTH,
			'Content-Type': MIME[ ext ] || 'application/octet-stream',
			'Content-Disposition': `attachment; filename="${ slug }.${ ext }"`,
		},
		body: buf,
	} );
	const body = await res.json();
	if ( ! res.ok ) throw new Error( `Ошибка загрузки ${ slug }: ${ body.message || res.status }` );
	console.log( `  ↑ загружен ${ slug } (id=${ body.id })` );
	return body;
};

const findMedia = async ( slug ) => {
	const r = await api( `/wp-json/wp/v2/media?slug=${ encodeURIComponent( slug ) }&per_page=1` );
	const item = Array.isArray( r.body ) ? r.body[ 0 ] : null;
	if ( item ) console.log( `  ✓ ${ slug } (id=${ item.id })` );
	else console.warn( `  ⚠  не найдено: ${ slug }` );
	return item;
};

const u = m => m ? m.source_url : '';
const id = m => m ? m.id : 0;

console.log( '\n📦 Медиа...' );
const M = 'projects/rosenberger/media';
const [ hero, ctaBg, split1, split2, badge, icTenant, icEffort, icRent, icFit ] = await Promise.all( [
	ensureMediaLocal( 'rosenberger-vm-hero',        `${ M }/iv-vm-hero.webp`,             'webp' ),
	ensureMediaLocal( 'rosenberger-vm-cta-bg',      `${ M }/iv-vm-cta-bg.webp`,           'webp' ),
	ensureMediaLocal( 'rosenberger-vm-split1',      `${ M }/iv-vm-split1.webp`,           'webp' ),
	ensureMediaLocal( 'rosenberger-vm-split2',      `${ M }/iv-vm-split2.webp`,           'webp' ),
	findMedia( 'rosenberger-google-rating' ),
	ensureMediaLocal( 'rosenberger-vm-pain-tenant', `${ M }/icons/iv-vm-pain-tenant.svg`, 'svg' ),
	ensureMediaLocal( 'rosenberger-vm-pain-effort', `${ M }/icons/iv-vm-pain-effort.svg`, 'svg' ),
	ensureMediaLocal( 'rosenberger-vm-pain-rent',   `${ M }/icons/iv-vm-pain-rent.svg`,   'svg' ),
	ensureMediaLocal( 'rosenberger-vm-pain-fit',    `${ M }/icons/iv-vm-pain-fit.svg`,    'svg' ),
] );

console.log( '\n📄 Собираю разметку...' );
const pageContent = [

	`<!-- wp:library/page-hero ${ JSON.stringify( {
		headingStart:  'Wohnung oder Haus vermieten<br>',
		headingItalic: 'in Vorarlberg',
		headingEnd:    '',
		subtitle:      'Sie vermieten an sorgfältig geprüfte Mieter, ohne den Aufwand \nund ohne das Risiko, an die Falschen zu geraten.',
		buttonText:    'Kostenlos beraten lassen',
		buttonUrl:     '/kontakt/',
		disclaimer:    'Unverbindlich und kostenlos',
		imageId:       id( hero ),
		imageUrl:      u( hero ),
	} ) } /-->`,

	`<!-- wp:library/trust-bar ${ JSON.stringify( {
		badgeId:  id( badge ),
		badgeUrl: u( badge ),
		rating:   '4.5',
		items:    [ 'Persönlich von mir betreut', 'Schnelle Rückmeldung', 'Vor Ort in ganz Vorarlberg' ],
	} ) } /-->`,

	`<!-- wp:library/problem-cards ${ JSON.stringify( {
		heading:       'Was Sie als<br>Vermieter ',
		headingItalic: 'fürchten',
		intro:         'Eine Wohnung zu vermieten klingt einfach, bis der falsche Mieter einzieht.',
		items: [
			{ title: 'Der falsche Mieter', text: 'Gefälschte Arbeitsverträge und geschönte Selbstauskünfte sind keine Seltenheit. Ist der falsche Mieter erst drin, dauert eine Räumung schnell ein halbes Jahr und länger.', iconId: id( icTenant ), iconUrl: u( icTenant ) },
			{ title: 'Der Aufwand der Selbstvermietung', text: 'Inserate, Anfragen, Besichtigungen, Bonität, Vertrag und Übergabe, alles bleibt an Ihnen hängen.', iconId: id( icEffort ), iconUrl: u( icEffort ) },
			{ title: 'Die Sorge um die Miete', text: 'Zahlt der Mieter zuverlässig, geht er ordentlich mit dem Objekt um und passt er überhaupt dazu?', iconId: id( icRent ), iconUrl: u( icRent ) },
			{ title: 'Unklarheit beim Bestellerprinzip', text: 'Seit der Änderung 2023 zahlt, wer den Makler beauftragt. Was das für Sie als Vermieter konkret heißt, geht in der Diskussion oft unter.', iconId: id( icFit ), iconUrl: u( icFit ) },
		],
	} ) } /-->`,

	`<!-- wp:library/split-cta ${ JSON.stringify( {
		heading:       'Was ich für Sie als<br>Vermieter ',
		headingItalic: 'übernehme',
		text:          'Ich kümmere mich um den kompletten Weg zum Mieter. Die fünf Schritte sehen Sie weiter unten, am Ende haben Sie einen geprüften Mieter und mussten sich um nichts dazwischen kümmern.',
		buttonText:    'Kostenlos beraten lassen',
		buttonUrl:     '/kontakt/',
		imageId:       id( split1 ),
		imageUrl:      u( split1 ),
	} ) } /-->`,

	`<!-- wp:library/split-cta ${ JSON.stringify( {
		heading:       'Wie ich Ihre<br>Mieter ',
		headingItalic: 'auswähle',
		text:          'Ich prüfe Bonität und Unterlagen, führe ein persönliches Gespräch und achte darauf, dass der Mieter zum Objekt passt. Lieber eine Besichtigung mehr als ein Mietausfall. Diese Sorgfalt am Anfang erspart Ihnen den Ärger danach.',
		buttonText:    '',
		imageId:       id( split2 ),
		imageUrl:      u( split2 ),
		imageLeft:     true,
	} ) } /-->`,

	`<!-- wp:library/process-steps ${ JSON.stringify( {
		heading:       'Ihre Vermietung<br>',
		headingItalic: 'in fünf Schritten',
		subtext:       '',
		buttonText:    '',
		steps: [
			{ number: '01', title: 'Erstgespräch',              text: 'Wir sprechen über Ihr Objekt und Ihre Vorstellungen, ohne Verpflichtung.' },
			{ number: '02', title: 'Mietzins-Einschätzung',     text: 'Ich schätze einen marktgerechten Mietzins ein, nicht zu hoch und nicht zu tief.' },
			{ number: '03', title: 'Vermarktung und Anfragen',  text: 'Ich übernehme Inserate, Anfragen und die Besichtigungstermine.' },
			{ number: '04', title: 'Mieterauswahl',             text: 'Bonitätsprüfung, persönliches Gespräch und die Prüfung der Unterlagen.' },
			{ number: '05', title: 'Vertrag und Übergabe',      text: 'Rechtssicherer Mietvertrag und eine dokumentierte Übergabe mit Protokoll.' },
		],
	} ) } /-->`,

	`<!-- wp:library/testimonials ${ JSON.stringify( {
		heading:       'Das sagen Menschen,<br>',
		headingItalic: 'die mit mir gearbeitet haben',
		limit:         3,
		minRating:     4,
	} ) } /-->`,

	`<!-- wp:library/faq-section ${ JSON.stringify( {
		heading: 'Häufige Fragen<br>zur <em>Vermietung</em>',
		items: [
			{ question: 'Wie viel Miete kann ich verlangen?', answer: 'Das hängt von Lage, Größe und Zustand ab. Ich schätze Ihnen einen marktgerechten Mietzins ein, der sich auch erzielen lässt.', open: true },
			{ question: 'Wie lange dauert die Mietersuche?', answer: 'In der Regel wenige Wochen. Nach dem Erstgespräch und der Bewertung sage ich Ihnen, was für Ihr Objekt realistisch ist.', open: false },
			{ question: 'Was, wenn der Mieter nicht zahlt?', answer: 'Genau das beuge ich mit sorgfältiger Auswahl und Bonitätsprüfung vor. Ich wähle Mieter so aus, dass Zahlungsausfälle möglichst gar nicht erst entstehen.', open: false },
			{ question: 'Was kostet mich die Vermietung, und wer zahlt die Provision?', answer: 'Seit dem Bestellerprinzip 2023 zahlt, wer den Makler beauftragt. Was das konkret für Sie bedeutet, bespreche ich offen, bevor Sie sich entscheiden.', open: false },
		],
	} ) } /-->`,

	`<!-- wp:library/consultation-cta ${ JSON.stringify( {
		heading:       '',
		headingItalic: 'Ihr kostenloses Erstgespräch',
		text:          'Persönlich und völlig unverbindlich. Ich finde den passenden Mieter, Sie behalten den Kopf frei.',
		buttonText:    'Kostenlos beraten lassen',
		buttonUrl:     '/kontakt/',
		backgroundId:  id( ctaBg ),
		backgroundUrl: u( ctaBg ),
	} ) } /-->`,

].join( '\n\n' );

console.log( '\n📝 Создаю / обновляю страницу «Immobilie vermieten»...' );
const pages = await api( '/wp-json/wp/v2/pages?slug=immobilie-vermieten&status=any&per_page=1' );
let pageId;
if ( Array.isArray( pages.body ) && pages.body[ 0 ] ) {
	pageId = pages.body[ 0 ].id;
	const r = await api( `/wp-json/wp/v2/pages/${ pageId }`, { method: 'POST', body: JSON.stringify( { content: pageContent, status: 'publish' } ) } );
	if ( ! r.ok ) throw new Error( `Ошибка обновления: ${ JSON.stringify( r.body ).slice( 0, 300 ) }` );
	console.log( `  ✓ Обновлена id=${ pageId }` );
} else {
	const r = await api( '/wp-json/wp/v2/pages', { method: 'POST', body: JSON.stringify( { title: 'Immobilie vermieten', slug: 'immobilie-vermieten', content: pageContent, status: 'publish' } ) } );
	if ( ! r.ok ) throw new Error( `Ошибка создания: ${ JSON.stringify( r.body ).slice( 0, 300 ) }` );
	pageId = r.body.id;
	console.log( `  ✓ Создана id=${ pageId }` );
}

console.log( `\n✅ Готово! ${ BASE }/immobilie-vermieten/` );
