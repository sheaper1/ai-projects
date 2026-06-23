// Создаёт / обновляет страницу «Immobilienbewertung» через WP REST API.
// Запуск: node scripts/import-immobilienbewertung.mjs
//
// Источник: Figma p1HKLfoMcOwtVUD5rI9V3P, UI Design, immobilienbewertung
// 537:3196 (desktop) / 600:1648 (mobile). Собрано по плейбуку AGENTS §6a.
//
// ⚠️  ЧЕРНОВИКИ — требуют ревью пользователя:
//   - Ответы на FAQ вопросы 2–5 (в Figma свёрнуты, тексты не видны)
//   - Заголовок Process Steps (в Figma написано "Vermietung/fünf Schritten" —
//     явная ошибка копирования; поставлено "Bewertung/drei Schritten")
//   - Заголовок FAQ (в Figma "zur Vermietung" — ошибка; поставлено "zur Bewertung")
//   - Slug CTA-кнопок (/kontakt/ — уточнить актуальность)
//   - Многошаговая форма (секция 8) заменена на consultation-cta — сделать позже

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
const [
	hero, split1, split2, aboutBg,
	badge, ctaBg,
	icSpanne, icErklaerung, icVerpflichtung, icDatenschutz,
] = await Promise.all( [
	ensureMediaLocal( 'rosenberger-bw-hero',            `${ M }/iv-bw-hero.webp`,                       'webp' ),
	ensureMediaLocal( 'rosenberger-bw-split1',          `${ M }/iv-bw-split1.webp`,                     'webp' ),
	ensureMediaLocal( 'rosenberger-bw-split2',          `${ M }/iv-bw-split2.webp`,                     'webp' ),
	ensureMediaLocal( 'rosenberger-bw-about-bg',        `${ M }/iv-bw-about-bg.webp`,                   'webp' ),
	findMedia( 'rosenberger-google-rating' ),
	findMedia( 'rosenberger-vm-cta-bg' ),
	ensureMediaLocal( 'rosenberger-bw-icon-spanne',     `${ M }/icons/iv-bw-icon-spanne.svg`,           'svg' ),
	ensureMediaLocal( 'rosenberger-bw-icon-erklaerung', `${ M }/icons/iv-bw-icon-erklaerung.svg`,       'svg' ),
	ensureMediaLocal( 'rosenberger-bw-icon-verpflichtung', `${ M }/icons/iv-bw-icon-verpflichtung.svg`, 'svg' ),
	ensureMediaLocal( 'rosenberger-bw-icon-datenschutz', `${ M }/icons/iv-bw-icon-datenschutz.svg`,    'svg' ),
] );

console.log( '\n📄 Собираю разметку...' );
const pageContent = [

	`<!-- wp:library/page-hero ${ JSON.stringify( {
		headingStart:  'Immobilienbewertung<br>',
		headingItalic: 'in Vorarlberg',
		headingEnd:    '',
		subtitle:      'Was Ihre Immobilie heute am Markt wert ist, ehrlich und realistisch eingeschätzt. Ohne Wunschzahl, ohne Verpflichtung und ohne dass Ihre Daten bei zehn Maklern landen.',
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

	`<!-- wp:library/split-cta ${ JSON.stringify( {
		heading:       'Warum eine ehrliche<br>Bewertung ',
		headingItalic: 'zählt',
		text:          'Vermutlich schätzen Sie Ihre Immobilie höher ein, als sie heute am Markt erzielt. Verständlich, weil Erinnerungen und Ihre eigene Arbeit darin mitschwingen. Ein zu hoher Preis klingt zuerst gut, kostet aber Zeit und am Ende Geld, weil das Objekt zu lange am Markt steht.\n\nGenauso wenig hilft Ihnen eine Zahl, die nur schön klingt, damit ich Sie als Kunden gewinne. Sie brauchen keinen Wunschpreis, sondern eine Einschätzung, auf die Sie sich verlassen können.',
		buttonText:    'Kostenlos beraten lassen',
		buttonUrl:     '/kontakt/',
		imageId:       id( split1 ),
		imageUrl:      u( split1 ),
	} ) } /-->`,

	`<!-- wp:library/problem-cards ${ JSON.stringify( {
		heading:       'Was Sie bei<br>mir bekommen',
		headingItalic: '',
		intro:         '',
		items: [
			{ title: 'Eine Spanne,\ndie zum Markt passt', text: 'Ein Wert auf Basis vergleichbarer Objekte in Vorarlberg, nicht auf Basis Ihrer oder meiner Wunschvorstellung.', iconId: id( icSpanne ), iconUrl: u( icSpanne ) },
			{ title: 'Eine nachvollziehbare Erklärung', text: 'Sie verstehen, wie der Wert zustande kommt, statt eine Zahl ohne Begründung in der Hand zu halten.', iconId: id( icErklaerung ), iconUrl: u( icErklaerung ) },
			{ title: 'Keine\nVerpflichtung', text: 'Sie sind danach zu nichts verpflichtet. Die Bewertung steht für sich.', iconId: id( icVerpflichtung ), iconUrl: u( icVerpflichtung ) },
			{ title: 'Ein Ansprechpartner, keine Datenweitergabe', text: 'Sie sprechen mit mir, nicht mit einem Formular, das Ihre Daten weiterverkauft.', iconId: id( icDatenschutz ), iconUrl: u( icDatenschutz ) },
		],
	} ) } /-->`,

	`<!-- wp:library/split-cta ${ JSON.stringify( {
		heading:       'Wie ich ',
		headingItalic: 'bewerte',
		text:          'Ich sehe mir Ihre Immobilie vor Ort an, vergleiche sie mit ähnlichen Objekten in der Region und beziehe die aktuelle Marktlage in Vorarlberg ein. Dazu kommen Lage, Zustand und Ausstattung.\n\nSie sehen jeden Schritt der Einschätzung und können nachvollziehen, woher der Wert kommt und mit welchen Annahmen ich rechne.',
		buttonText:    '',
		imageId:       id( split2 ),
		imageUrl:      u( split2 ),
		imageLeft:     true,
	} ) } /-->`,

	`<!-- wp:library/about ${ JSON.stringify( {
		titleMain:     'Wann sich eine Bewertung lohnt',
		text:          '',
		buttonText:    '',
		backgroundId:  id( aboutBg ),
		backgroundUrl: u( aboutBg ),
		columns:       3,
		items: [
			{ title: 'Vor dem Verkauf',            text: 'Damit Sie wissen, was realistisch ist, bevor Sie inserieren.' },
			{ title: 'Bei einer Erbschaft',        text: 'Für eine faire Aufteilung und die Auszahlung in der Erbengemeinschaft.' },
			{ title: 'Bei einer Scheidung',        text: 'Wenn das gemeinsame Eigentum sauber auseinandergesetzt werden muss.' },
			{ title: 'Bei einer Finanzierung',     text: 'Wenn die Bank einen aktuellen Wert verlangt.' },
			{ title: 'Bei einer Schenkung oder Übergabe', text: 'Wenn es um die Übergabe an die nächste Generation geht.' },
			{ title: 'Einfach um es zu wissen',   text: 'Weil es gut ist, den Wert seiner Immobilie zu kennen, auch ohne konkreten Anlass.' },
		],
	} ) } /-->`,

	// ⚠️ ЧЕРНОВИК заголовка: в Figma написано "Ihre Vermietung in fünf Schritten"
	// (ошибка копирования), исправлено на "Ihre Bewertung in drei Schritten"
	`<!-- wp:library/process-steps ${ JSON.stringify( {
		heading:       'Ihre Bewertung<br>',
		headingItalic: 'in drei Schritten',
		subtext:       '',
		buttonText:    '',
		steps: [
			{ number: '01', title: 'Anfrage',           text: 'Sie schildern mir kurz Ihre Immobilie, telefonisch oder über das Formular.' },
			{ number: '02', title: 'Termin vor Ort',    text: 'Ich sehe mir das Objekt an und stelle die Fragen, die für den Wert wichtig sind.' },
			{ number: '03', title: 'Ihre Einschätzung', text: 'Ich übergebe Ihnen eine nachvollziehbare Werteinschätzung, mit der Sie weiterplanen können.' },
		],
	} ) } /-->`,

	// ⚠️ Секция 8 (многошаговая форма) — заменена на consultation-cta, сделать позже
	`<!-- wp:library/consultation-cta ${ JSON.stringify( {
		heading:       'Ihre kostenlose Bewertung',
		headingItalic: 'anfragen',
		text:          'In unter einer Minute. Ich melde mich danach persönlich bei Ihnen.',
		buttonText:    'Jetzt anfragen',
		buttonUrl:     '/kontakt/',
		backgroundId:  id( ctaBg ),
		backgroundUrl: u( ctaBg ),
	} ) } /-->`,

	`<!-- wp:library/testimonials ${ JSON.stringify( {
		heading:       'Das sagen Menschen,<br>',
		headingItalic: 'die mit mir gearbeitet haben',
		limit:         3,
		minRating:     4,
	} ) } /-->`,

	// ⚠️ ЧЕРНОВИК FAQ: в Figma видно только ответ #1; ответы 2–5 — черновик,
	// требуют проверки и редакции пользователем.
	// ⚠️ Заголовок в Figma написано "zur Vermietung" (ошибка); исправлено на "zur Bewertung"
	`<!-- wp:library/faq-section ${ JSON.stringify( {
		heading: 'Häufige Fragen<br>zur <em>Bewertung</em>',
		items: [
			{ question: 'Was kostet die Bewertung?',                answer: 'Nichts. Die Einschätzung ist kostenlos und unverbindlich.',                                                                                                          open: true },
			{ question: 'Bin ich danach zu etwas verpflichtet?',   answer: 'Nein. Nach der Bewertung sind Sie zu nichts verpflichtet. Sie entscheiden, was als Nächstes passiert.',                                                              open: false },
			{ question: 'Wie genau ist die Einschätzung?',         answer: 'Die Einschätzung basiert auf vergleichbaren Verkäufen in der Region und der aktuellen Marktlage. Sie ist ein fundierter Richtwert, kein verbindliches Gutachten.', open: false },
			{ question: 'Werden meine Daten weitergegeben?',       answer: 'Nein. Ihre Daten werden nicht weitergegeben. Sie sprechen nur mit mir.',                                                                                              open: false },
			{ question: 'Wie lange dauert es?',                    answer: 'Nach Ihrem Kontakt melde ich mich innerhalb von 24 Stunden. Die Einschätzung selbst besprechen wir beim Termin vor Ort.',                                            open: false },
		],
	} ) } /-->`,

].join( '\n\n' );

console.log( '\n📝 Создаю / обновляю страницу «Immobilienbewertung»...' );
const pages = await api( '/wp-json/wp/v2/pages?slug=immobilienbewertung&status=any&per_page=1' );
let pageId;
if ( Array.isArray( pages.body ) && pages.body[ 0 ] ) {
	pageId = pages.body[ 0 ].id;
	const r = await api( `/wp-json/wp/v2/pages/${ pageId }`, { method: 'POST', body: JSON.stringify( { content: pageContent, status: 'publish' } ) } );
	if ( ! r.ok ) throw new Error( `Ошибка обновления: ${ JSON.stringify( r.body ).slice( 0, 300 ) }` );
	console.log( `  ✓ Обновлена id=${ pageId }` );
} else {
	const r = await api( '/wp-json/wp/v2/pages', { method: 'POST', body: JSON.stringify( { title: 'Immobilienbewertung', slug: 'immobilienbewertung', content: pageContent, status: 'publish' } ) } );
	if ( ! r.ok ) throw new Error( `Ошибка создания: ${ JSON.stringify( r.body ).slice( 0, 300 ) }` );
	pageId = r.body.id;
	console.log( `  ✓ Создана id=${ pageId }` );
}

console.log( `\n✅ Готово! ${ BASE }/immobilienbewertung/` );
