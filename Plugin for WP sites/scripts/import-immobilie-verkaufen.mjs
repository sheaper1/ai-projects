// Создаёт / обновляет страницу «Immobilie verkaufen» через WP REST API.
// Запуск: node scripts/import-immobilie-verkaufen.mjs
//
// Источник дизайна: Figma p1HKLfoMcOwtVUD5rI9V3P, страница UI Design,
// фрейм immobilie-verkaufen 536:1972 (desktop) / 543:4571 (mobile).
//
// Сборка идёт этапами. Этап 1 (этот файл сейчас): Hero + trust-bar + problem-cards.
// Этапы 2–3 добавляются ниже в pageContent по мере готовности секций.

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

const u  = m => m ? m.source_url : '';
const id = m => m ? m.id : 0;

// ---------------------------------------------------------------------------
// Шаг 1 — медиа
// ---------------------------------------------------------------------------
console.log( '\n📦 Медиа...' );
const M = 'projects/rosenberger/media';
const [ heroBg, splitImg, ctaBg, badge, icPrice, icCallback, icEffort, icTrust ] = await Promise.all( [
	ensureMediaLocal( 'rosenberger-iv-hero',          `${ M }/iv-hero.webp`,             'webp' ),
	ensureMediaLocal( 'rosenberger-iv-split',         `${ M }/iv-split.webp`,            'webp' ),
	ensureMediaLocal( 'rosenberger-iv-cta-bg',        `${ M }/iv-cta-bg.webp`,           'webp' ),
	findMedia( 'rosenberger-google-rating' ),
	ensureMediaLocal( 'rosenberger-iv-pain-price',    `${ M }/icons/iv-pain-price.svg`,    'svg' ),
	ensureMediaLocal( 'rosenberger-iv-pain-callback', `${ M }/icons/iv-pain-callback.svg`, 'svg' ),
	ensureMediaLocal( 'rosenberger-iv-pain-effort',   `${ M }/icons/iv-pain-effort.svg`,   'svg' ),
	ensureMediaLocal( 'rosenberger-iv-pain-trust',    `${ M }/icons/iv-pain-trust.svg`,    'svg' ),
] );

// ---------------------------------------------------------------------------
// Шаг 2 — блочная разметка
// ---------------------------------------------------------------------------
console.log( '\n📄 Собираю разметку...' );

const pageContent = [

	`<!-- wp:library/page-hero ${ JSON.stringify( {
		headingStart:  'Immobilie verkaufen ',
		headingItalic: 'in Vorarlberg',
		headingEnd:    '',
		subtitle:      'Sie verkaufen ohne Druck und ohne Aufwand. \nAlles dazwischen übernehme ich, Ihr Termin bei mir dauert rund eine Stunde.',
		buttonText:    'Jetzt verkaufen',
		buttonUrl:     '/kontakt/',
		disclaimer:    '',
		imageId:       id( heroBg ),
		imageUrl:      u( heroBg ),
	} ) } /-->`,

	`<!-- wp:library/trust-bar ${ JSON.stringify( {
		badgeId:  id( badge ),
		badgeUrl: u( badge ),
		rating:   '4.5',
		items:    [ 'Persönlich von mir betreut', 'Schnelle Rückmeldung', 'Vor Ort in ganz Vorarlberg' ],
	} ) } /-->`,

	`<!-- wp:library/problem-cards ${ JSON.stringify( {
		heading:       'Was einen<br>Verkauf ',
		headingItalic: 'zäh macht',
		intro:         'Ein Verkauf kommt selten zum bequemsten Zeitpunkt. Erbe, Scheidung, Umzug im Alter oder ein neuer Job, der Anlass entscheidet selten der Eigentümer selbst. Und dann fängt der eigentliche Ärger oft erst an.',
		items: [
			{
				title:   'Der Wunschpreis,<br>der nur den Auftrag bringt',
				text:    'Ein Makler verspricht eine hohe Zahl, sichert sich den Auftrag, und Monate später wird der Preis Stück für Stück gesenkt. Am Markt gilt das Objekt dann schnell als verbrannt.',
				iconId:  id( icPrice ),
				iconUrl: u( icPrice ),
			},
			{
				title:   'Der Makler,<br>der abtaucht',
				text:    'Nach der Unterschrift kommen keine Rückmeldungen mehr, und Sie erfahren wochenlang nichts über den Stand.',
				iconId:  id( icCallback ),
				iconUrl: u( icCallback ),
			},
			{
				title:   'Der Aufwand,<br>den keiner sieht',
				text:    'Ein Privatverkauf bindet schnell hundert Stunden und mehr, mit Besichtigungen, Unterlagen, Behördengängen und Verhandlungen.',
				iconId:  id( icEffort ),
				iconUrl: u( icEffort ),
			},
			{
				title:   'Das Gefühl, über den Tisch gezogen zu werden',
				text:    'Unklare Provision, Druck zur schnellen Entscheidung und die Sorge, den Markt nicht zu überblicken.',
				iconId:  id( icTrust ),
				iconUrl: u( icTrust ),
			},
		],
	} ) } /-->`,

	`<!-- wp:library/split-cta ${ JSON.stringify( {
		heading:       'Sie verkaufen,<br>',
		headingItalic: 'ich mache den Rest',
		text:          'Ich übernehme den ganzen Verkauf. Die acht Schritte sehen Sie weiter unten, vom Erstgespräch bis zur Übergabe ist alles mein Teil.<br><br>Ihr Aufwand liegt am Ende bei rund einer Stunde, beim Erstgespräch und bei Ihrer Unterschrift.',
		buttonText:    'Jetzt verkaufen',
		buttonUrl:     '/kontakt/',
		imageId:       id( splitImg ),
		imageUrl:      u( splitImg ),
	} ) } /-->`,

	`<!-- wp:library/process-steps ${ JSON.stringify( {
		heading:       'Ihr Verkauf<br>',
		headingItalic: 'in acht Schritten',
		subtext:       '',
		buttonText:    '',
		steps: [
			{ number: '01', title: 'Erstgespräch',           text: 'Wir lernen uns kennen und sprechen über Ihre Immobilie und Ihre Situation, ohne Verpflichtung.' },
			{ number: '02', title: 'Ehrliche Bewertung',     text: 'Ich schätze Ihre Immobilie vor Ort realistisch ein und erkläre nachvollziehbar, wie der Preis zustande kommt.' },
			{ number: '03', title: 'Strategie',              text: 'Wir legen Zielpreis, Zielgruppe und Vermarktungsweg fest und besprechen, wie wir vorgehen.' },
			{ number: '04', title: 'Aufbereitung',           text: 'Ich besorge die Unterlagen, bereite Ihr Objekt auf und erstelle ein vollständiges Exposé mit guten Fotos.' },
			{ number: '05', title: 'Vermarktung',            text: 'Ihr Objekt erscheint dort, wo Käufer in Vorarlberg suchen, sauber und vollständig dargestellt.' },
			{ number: '06', title: 'Besichtigungen',         text: 'Ich führe die Besichtigungen und prüfe vorher, wer es ernst meint und finanzieren kann.' },
			{ number: '07', title: 'Verhandlung und Notar',  text: 'Ich verhandle für Sie und stimme den Kaufvertrag mit dem Notar ab.' },
			{ number: '08', title: 'Übergabe und Ummeldung', text: 'Vom Übergabeprotokoll über die Schlüssel bis zu den Ab- und Ummeldungen begleite ich den letzten Schritt.' },
		],
	} ) } /-->`,

	`<!-- wp:library/sold-showcase ${ JSON.stringify( {
		heading:       'Erfolgreich verkauft ',
		headingItalic: 'in Vorarlberg',
		ctaText:       'Alle Referenzen ansehen',
		ctaUrl:        '/referenzen/',
	} ) } /-->`,

	`<!-- wp:library/testimonials ${ JSON.stringify( {
		heading:       'Das sagen Menschen,',
		headingItalic: 'die mit mir gearbeitet haben',
		limit:         3,
		minRating:     4,
	} ) } /-->`,

	`<!-- wp:library/faq-section ${ JSON.stringify( {
		heading: 'Häufige Fragen<br>zum <em>Verkauf</em>',
		items: [
			{
				question: 'Was kostet mich der Verkauf?',
				answer:   'Die Provision hängt von Objekt und Aufwand ab. Was auf Sie zukommt, sage ich Ihnen offen, bevor Sie sich entscheiden. Keine versteckten Kosten und keine Überraschungen im Vertrag.',
				open:     true,
			},
			{
				question: 'Wie lange dauert ein Verkauf?',
				answer:   'Das hängt von Objekt, Lage und Preis ab. Eine konkrete Einschätzung für Ihren Fall gebe ich Ihnen schon im Erstgespräch.',
				open:     false,
			},
			{
				question: 'Kann ich nicht einfach privat verkaufen und die Provision sparen?',
				answer:   'Können Sie. Rechnen Sie aber mit hundert Stunden und mehr, mit der Prüfung der Interessenten und mit dem Risiko, den Preis falsch anzusetzen. Genau das nehme ich Ihnen ab.',
				open:     false,
			},
			{
				question: 'Was, wenn ich es mir anders überlege und doch nicht verkaufe?',
				answer:   'Dann ist das so. Sie entscheiden in Ihrem Tempo, ich setze Sie nicht unter Druck.',
				open:     false,
			},
			{
				question: 'Wer kommt zu den Besichtigungen?',
				answer:   'Ich prüfe vorab, wer ernsthaftes Interesse hat und finanzieren kann. So bleibt Ihnen der Besichtigungs-Tourismus erspart.',
				open:     false,
			},
		],
	} ) } /-->`,

	`<!-- wp:library/consultation-cta ${ JSON.stringify( {
		heading:       '',
		headingItalic: 'Ihr kostenloses Erstgespräch',
		text:          'Persönlich und völlig unverbindlich. Ich verkaufe in ganz Vorarlberg, von Feldkirch über Dornbirn und Bregenz bis Bludenz.',
		buttonText:    'Kostenlos beraten lassen',
		buttonUrl:     '/kontakt/',
		backgroundId:  id( ctaBg ),
		backgroundUrl: u( ctaBg ),
	} ) } /-->`,

].join( '\n\n' );

// ---------------------------------------------------------------------------
// Шаг 3 — создать / обновить страницу
// ---------------------------------------------------------------------------
console.log( '\n📝 Создаю / обновляю страницу «Immobilie verkaufen»...' );

const pages = await api( '/wp-json/wp/v2/pages?slug=immobilie-verkaufen&status=any&per_page=1' );
let pageId;

if ( Array.isArray( pages.body ) && pages.body[ 0 ] ) {
	pageId = pages.body[ 0 ].id;
	const r = await api( `/wp-json/wp/v2/pages/${ pageId }`, {
		method: 'POST',
		body: JSON.stringify( { content: pageContent, status: 'publish' } ),
	} );
	if ( ! r.ok ) throw new Error( `Ошибка обновления: ${ JSON.stringify( r.body ).slice( 0, 300 ) }` );
	console.log( `  ✓ Обновлена id=${ pageId }` );
} else {
	const r = await api( '/wp-json/wp/v2/pages', {
		method: 'POST',
		body: JSON.stringify( { title: 'Immobilie verkaufen', slug: 'immobilie-verkaufen', content: pageContent, status: 'publish' } ),
	} );
	if ( ! r.ok ) throw new Error( `Ошибка создания: ${ JSON.stringify( r.body ).slice( 0, 300 ) }` );
	pageId = r.body.id;
	console.log( `  ✓ Создана id=${ pageId }` );
}

console.log( `\n✅ Готово! ${ BASE }/immobilie-verkaufen/` );
