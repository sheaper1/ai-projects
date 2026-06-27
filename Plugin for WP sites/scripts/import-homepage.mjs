// Создаёт / обновляет главную страницу и ставит front page.
// Загружает недостающие медиа, создаёт homepage.
// Запуск: node scripts/import-homepage.mjs

import { readFileSync, existsSync } from 'node:fs';
import { resolve, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = resolve( dirname( fileURLToPath( import.meta.url ) ), '..' );

const readEnv = ( file ) => {
	const env = {};
	for ( const line of readFileSync( resolve( root, file ), 'utf8' ).split( /\r?\n/ ) ) {
		const m = line.match( /^([A-Z_]+)=(.*)$/ );
		if ( m ) env[ m[ 1 ] ] = m[ 2 ];
	}
	return env;
};
const prod = readEnv( '.env' );

const authHeader = ( { WP_USER, WP_APP_PASSWORD } ) =>
	'Basic ' + Buffer.from( `${ WP_USER }:${ WP_APP_PASSWORD }` ).toString( 'base64' );

const PROD_BASE = prod.WP_URL.replace( /\/$/, '' );
const PROD_AUTH = authHeader( prod );

const MIME = { svg: 'image/svg+xml', webp: 'image/webp', jpg: 'image/jpeg', jpeg: 'image/jpeg', png: 'image/png' };

// ---------------------------------------------------------------------------
// Хелперы
// ---------------------------------------------------------------------------
const api = async ( base, auth, path, opts = {} ) => {
	const res = await fetch( `${ base }${ path }`, {
		...opts,
		headers: { Authorization: auth, 'Content-Type': 'application/json', ...( opts.headers || {} ) },
	} );
	const text = await res.text();
	let body; try { body = JSON.parse( text ); } catch { body = text; }
	return { status: res.status, ok: res.ok, body };
};

// Загрузить файл в медиатеку WP. Сначала проверяет, нет ли уже по slug.
const ensureMedia = async ( slug, getBuffer, ext ) => {
	const found = await api( PROD_BASE, PROD_AUTH, `/wp-json/wp/v2/media?slug=${ encodeURIComponent( slug ) }&per_page=1` );
	if ( Array.isArray( found.body ) && found.body[ 0 ] ) {
		console.log( `  ✓ ${ slug } (id=${ found.body[ 0 ].id })` );
		return found.body[ 0 ];
	}
	const buf = await getBuffer();
	const res = await fetch( `${ PROD_BASE }/wp-json/wp/v2/media`, {
		method: 'POST',
		headers: {
			Authorization: PROD_AUTH,
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

const localBuf  = ( path ) => () => readFileSync( path );
const remoteBuf = ( url )  => () => fetch( url ).then( r => r.arrayBuffer() ).then( b => Buffer.from( b ) );

// ---------------------------------------------------------------------------
// Шаг 1 — загрузить недостающие медиа на продакшн
// ---------------------------------------------------------------------------
console.log( '\n📦 Медиа — загружаю недостающие...' );

const heroBg = await ensureMedia(
	'library-hero-cover-bg',
	localBuf( resolve( root, 'library/blocks/hero-cover/assets/hero-bg.webp' ) ),
	'webp'
);

// Карточки регионов на главной — обновлённые фото (slug -v2), исходники в media/home.
const regionFeldkirch = await ensureMedia(
	'region-feldkirch-v2',
	localBuf( resolve( root, 'projects/rosenberger/media/home/region-feldkirch.webp' ) ),
	'webp'
);
const regionBludenz = await ensureMedia(
	'region-bludenz-v2',
	localBuf( resolve( root, 'projects/rosenberger/media/home/region-bludenz.webp' ) ),
	'webp'
);
const regionDornbirn = await ensureMedia(
	'region-dornbirn-v2',
	localBuf( resolve( root, 'projects/rosenberger/media/home/region-dornbirn.webp' ) ),
	'webp'
);
const regionBregenz = await ensureMedia(
	'region-bregenz-v2',
	localBuf( resolve( root, 'projects/rosenberger/media/home/region-bregenz.webp' ) ),
	'webp'
);

const arrowNext = await ensureMedia(
	'icon-arrow-next',
	localBuf( resolve( root, 'projects/rosenberger/media/icons/arrow-next.svg' ) ),
	'svg'
);
const arrowPrev = await ensureMedia(
	'icon-arrow-prev',
	localBuf( resolve( root, 'projects/rosenberger/media/icons/arrow-prev.svg' ) ),
	'svg'
);

// Уже загруженные при деплое — просто читаем ID
const getMedia = async ( slug ) => {
	const r = await api( PROD_BASE, PROD_AUTH, `/wp-json/wp/v2/media?slug=${ encodeURIComponent( slug ) }&per_page=1` );
	if ( ! ( Array.isArray( r.body ) && r.body[ 0 ] ) ) throw new Error( `Медиа не найдено: ${ slug }` );
	console.log( `  ✓ ${ slug } (id=${ r.body[ 0 ].id })` );
	return r.body[ 0 ];
};

const iconEvaluation = await getMedia( 'rosenberger-card-icon-evaluation' );
const iconValet      = await getMedia( 'rosenberger-card-icon-valet' );
const iconHouse      = await getMedia( 'rosenberger-card-icon-house' );

const badge       = await getMedia( 'rosenberger-google-rating' );
// Иконки pain-points — точные SVG из Figma (клиентский QA S8: прежние были не из
// макета). Slug -v2 + ensureMedia, чтобы залить заменённые ассеты.
const ic = ( slug, file ) => ensureMedia( slug, localBuf( resolve( root, 'projects/rosenberger/media/icons/' + file ) ), 'svg' );
const iconPrice   = await ic( 'rosenberger-icon-price-v2',      'price-v2.svg' );
const iconCb      = await ic( 'rosenberger-icon-callback-v2',   'callback-v2.svg' );
const iconPress   = await ic( 'rosenberger-icon-pressure-v2',   'pressure-v2.svg' );
const iconComm    = await ic( 'rosenberger-icon-commission-v2', 'commission-v2.svg' );
const iconHidden  = await ic( 'rosenberger-icon-hidden-v2',     'hidden-v2.svg' );
const card1       = await getMedia( 'rosenberger-card-1' );
const card2       = await getMedia( 'rosenberger-card-2' );
const card3       = await getMedia( 'rosenberger-card-3' );
const aboutBg     = await ensureMedia(
	'rosenberger-about-bg-v2',
	localBuf( resolve( root, 'projects/rosenberger/media/home/about-bg.webp' ) ),
	'webp'
);
const referralImg = await ensureMedia(
	'rosenberger-referral',
	localBuf( resolve( root, 'projects/rosenberger/media/home/referral.webp' ) ),
	'webp'
);
const ctaBg = await ensureMedia(
	'rosenberger-consultation-bg',
	localBuf( resolve( root, 'projects/rosenberger/media/home/cta-bg.webp' ) ),
	'webp'
);

// Sold-showcase image — загружаем из local папки
const soldShowcaseImg = await ensureMedia(
	'rosenberger-sold-showcase',
	localBuf( resolve( root, 'projects/rosenberger/media/home/sold-showcase.webp' ) ),
	'webp'
);

// ---------------------------------------------------------------------------
// Шаг 2 — собрать Gutenberg-разметку страницы
// ---------------------------------------------------------------------------
console.log( '\n📄 Собираю разметку главной...' );

const u = ( m ) => m.source_url;
const id = ( m ) => m.id;

const pageContent = [
	`<!-- wp:library/hero-cover {"align":"full","backgroundUrl":"${ u( heroBg ) }","backgroundId":${ id( heroBg ) }} /-->`,

	`<!-- wp:library/trust-bar {"badgeId":${ id( badge ) },"badgeUrl":"${ u( badge ) }"} /-->`,

	`<!-- wp:library/pain-points {"items":[${ [
		[ 'Preisversprechen, die nicht halten', 'Ein hoher Wunschpreis bringt dem Makler den Auftrag. Danach steht das Inserat monatelang und der Preis wird Stück für Stück gesenkt.', iconPrice ],
		[ 'Makler, die nicht zurückrufen', 'Nach der Unterschrift kommen keine Rückmeldungen mehr, und Sie erfahren wochenlang nichts über den Stand.', iconCb ],
		[ 'Druck statt Beratung', 'Sie sollen sich schnell entscheiden, weil angeblich andere Käufer schon warten.', iconPress ],
		[ 'Unklare Provision', 'Was der Verkauf kostet und was darin enthalten ist, bleibt bis zum Schluss vage.', iconComm ],
		[ 'Übergang', 'Was der Verkauf kostet und was darin enthalten ist, bleibt bis zum Schluss vage.', iconHidden ],
	].map( ( [ title, text, icon ] ) => JSON.stringify( { title, text, iconId: id( icon ), iconUrl: u( icon ) } ) ).join( ',' ) }]} /-->`,

	`<!-- wp:library/cards-stack {"ctaUrl":"/kontakt/","cards":[${ [
		[ 'Immobilie verkaufen', 'Von der Bewertung über die Vermarktung bis zur Übergabe übernehme ich den ganzen Verkauf für Sie.', card1, iconEvaluation, '/immobilie-verkaufen/' ],
		[ 'Immobilienbewertung', 'Sie erfahren realistisch, was Ihre Immobilie wert ist, ohne überzogene Versprechen und ohne Verpflichtung.', card2, iconValet, '/immobilienbewertung/' ],
		[ 'Immobilie vermieten', 'Sie bekommen sorgfältig ausgewählte Mieter, und ich kümmere mich um Bonität, Vertrag und Übergabe.', card3, iconHouse, '/immobilie-vermieten/' ],
	].map( ( [ title, text, img, icon, url ] ) => JSON.stringify( {
		title, text,
		buttonText: 'Erfahren Sie mehr', buttonUrl: url,
		imageId: id( img ), imageUrl: u( img ),
		mobileIconId: id( icon ), mobileIconUrl: u( icon ),
	} ) ).join( ',' ) }]} /-->`,

	`<!-- wp:library/about {"backgroundId":${ id( aboutBg ) },"backgroundUrl":"${ u( aboutBg ) }","buttonUrl":"/ueber-mich/"} /-->`,

	`<!-- wp:library/testimonials {"heading":"Das sagen Menschen,","headingItalic":"die mit mir gearbeitet haben"} /-->`,

	`<!-- wp:library/region-grid {"align":"full","heading":"Vor Ort in ganz","headingItalic":"Vorarlberg","subtext":"Ich kenne die Lagen, die Preise und die Besonderheiten vor Ort,\\nvon Feldkirch bis Bregenz.","regions":[${ [
		[ regionFeldkirch, 'Feldkirch',  '/objekte-ort/feldkirch/' ],
		[ regionBludenz,   'Bludenz',    '/objekte-ort/bludenz/'   ],
		[ regionDornbirn,  'Dornbirn',   '/objekte-ort/dornbirn/'  ],
		[ regionBregenz,   'Bregenz',    '/objekte-ort/bregenz/'   ],
	].map( ( [ m, label, url ] ) => JSON.stringify( { mediaId: id( m ), mediaUrl: u( m ), label, url } ) ).join( ',' ) }]} /-->`,

	`<!-- wp:library/process-steps {"buttonUrl":"/kontakt/"} /-->`,

	`<!-- wp:library/referral-cta {"imageId":${ id( referralImg ) },"imageUrl":"${ u( referralImg ) }","buttonUrl":"/tippgeber/"} /-->`,

	`<!-- wp:library/faq-section /-->`,

	`<!-- wp:library/consultation-cta {"backgroundId":${ id( ctaBg ) },"backgroundUrl":"${ u( ctaBg ) }","buttonUrl":"/kontakt/"} /-->`,
].join( '\n\n' );

// ---------------------------------------------------------------------------
// Шаг 3 — создать / обновить страницу «Главная»
// ---------------------------------------------------------------------------
console.log( '\n🏠 Создаю главную страницу...' );

const pages = await api( PROD_BASE, PROD_AUTH, '/wp-json/wp/v2/pages?slug=home&status=any&per_page=1' );
let homepageId;

if ( Array.isArray( pages.body ) && pages.body[ 0 ] ) {
	homepageId = pages.body[ 0 ].id;
	await api( PROD_BASE, PROD_AUTH, `/wp-json/wp/v2/pages/${ homepageId }`, {
		method: 'POST',
		body: JSON.stringify( { content: pageContent, status: 'publish' } ),
	} );
	console.log( `  ✓ Обновлена существующая страница id=${ homepageId }` );
} else {
	const created = await api( PROD_BASE, PROD_AUTH, '/wp-json/wp/v2/pages', {
		method: 'POST',
		body: JSON.stringify( { title: 'Home', slug: 'home', content: pageContent, status: 'publish' } ),
	} );
	if ( ! created.ok ) throw new Error( `Не удалось создать страницу: ${ JSON.stringify( created.body ).slice( 0, 300 ) }` );
	homepageId = created.body.id;
	console.log( `  ✓ Создана страница id=${ homepageId }` );
}

// ---------------------------------------------------------------------------
// Шаг 4 — назначить как фронт-пейдж
// ---------------------------------------------------------------------------
console.log( '\n⚙️  Назначаю как front page...' );

const snippet = await api( PROD_BASE, PROD_AUTH, '/wp-json/code-snippets/v1/snippets', {
	method: 'POST',
	body: JSON.stringify( {
		name: 'Library: set front page (once)',
		code: `update_option('show_on_front','page'); update_option('page_on_front',${ homepageId });`,
		scope: 'global',
		active: true,
	} ),
} );
if ( snippet.body && snippet.body.id ) {
	await fetch( PROD_BASE + '/' ).catch( () => {} );
	await api( PROD_BASE, PROD_AUTH, `/wp-json/code-snippets/v1/snippets/${ snippet.body.id }`, {
		method: 'POST',
		body: JSON.stringify( { active: false, code: '// removed' } ),
	} );
	console.log( `  ✓ Front page = id ${ homepageId }` );
}

// ---------------------------------------------------------------------------
// Шаг 5 — заполнить настройки сайта (если ещё не заполнены)
// ---------------------------------------------------------------------------
console.log( '\n⚙️  Настройки сайта (seeding contacts)...' );
const settingsSnippet = `
if ( false === get_option('rosenberger_contacts') ) {
    add_option('rosenberger_contacts', [
        'phone'    => '+43 699 11 777 505',
        'email'    => 'office@rosenberger.immo',
        'address'  => 'ROSENBERGER Immobilien GmbH, Drevesstraße 2/1, 6800 Feldkirch',
        'hours'    => 'Mo–Fr 9:00–17:00',
        'cta_text' => 'Kontakt',
        'cta_url'  => '/kontakt/',
    ]);
}`;
const s2 = await api( PROD_BASE, PROD_AUTH, '/wp-json/code-snippets/v1/snippets', {
	method: 'POST',
	body: JSON.stringify( { name: 'Library: seed contacts (once)', code: settingsSnippet, scope: 'global', active: true } ),
} );
if ( s2.body && s2.body.id ) {
	await fetch( PROD_BASE + '/' ).catch( () => {} );
	await api( PROD_BASE, PROD_AUTH, `/wp-json/code-snippets/v1/snippets/${ s2.body.id }`, {
		method: 'POST',
		body: JSON.stringify( { active: false, code: '// removed' } ),
	} );
	console.log( `  ✓ Contacts seeded` );
}

console.log( `\n✅ Готово! Главная: ${ PROD_BASE }/` );
console.log( `   Настройки: ${ PROD_BASE }/wp-admin/admin.php?page=rosenberger-settings` );
