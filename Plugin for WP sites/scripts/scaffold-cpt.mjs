/**
 * scaffold-cpt.mjs — генерирует PHP-файлы для нового CPT в плагине проекта.
 *
 * Использование:
 *   node scripts/scaffold-cpt.mjs <config.json> [--dry-run]
 *
 * Пример:
 *   node scripts/scaffold-cpt.mjs scripts/cpt-templates/property.json
 *
 * Флаги:
 *   --dry-run  — показать генерированный код в консоль без записи файлов
 *
 * Формат config.json — смотри scripts/cpt-templates/example.json
 */

import { readFileSync, writeFileSync, existsSync, mkdirSync } from 'node:fs';
import { resolve, dirname, basename } from 'node:path';
import { fileURLToPath } from 'node:url';

const ROOT    = resolve( dirname( fileURLToPath( import.meta.url ) ), '..' );
const ARGS    = process.argv.slice( 2 );
const DRY_RUN = ARGS.includes( '--dry-run' );
const CFG_ARG = ARGS.find( a => ! a.startsWith( '--' ) );

if ( ! CFG_ARG ) {
	console.error( 'Использование: node scripts/scaffold-cpt.mjs <config.json> [--dry-run]' );
	console.error( 'Пример конфига: scripts/cpt-templates/example.json' );
	process.exit( 1 );
}

const cfgPath = resolve( ROOT, CFG_ARG );
if ( ! existsSync( cfgPath ) ) {
	console.error( `Файл конфига не найден: ${ cfgPath }` );
	process.exit( 1 );
}

/** @type {CPTConfig} */
const cfg = JSON.parse( readFileSync( cfgPath, 'utf8' ) );

// ── Валидация конфига ─────────────────────────────────────────────────────────

const required = [ 'slug', 'singular', 'plural', 'urlSlug', 'pluginDir' ];
for ( const key of required ) {
	if ( ! cfg[ key ] ) {
		console.error( `В конфиге обязательно поле "${ key }"` );
		process.exit( 1 );
	}
}

const {
	slug,           // 'property'
	singular,       // 'Objekt'
	plural,         // 'Objekte'
	urlSlug,        // 'objekte'
	pluginDir,      // 'projects/rosenberger/plugin/rosenberger-core'
	icon        = 'dashicons-admin-post',
	menuPosition = 5,
	supports    = [ 'title', 'editor', 'thumbnail', 'excerpt', 'revisions' ],
	taxonomies  = [],
	metaFields  = [],
	textdomain  = 'rosenberger-core',
} = cfg;

const INCLUDES = resolve( ROOT, pluginDir, 'includes' );
const MAIN_PHP = resolve( ROOT, pluginDir, `${ basename( pluginDir ) }.php` );
const CamelSlug = slug.replace( /-(\w)/g, ( _, c ) => c.toUpperCase() ); // property → property

// ── Генерация cpt.php ─────────────────────────────────────────────────────────

function genCptPhp() {
	const taxRegistrations = taxonomies.map( tax => {
		const labelsObj = {
			name:          tax.plural,
			singular_name: tax.singular,
			add_new_item:  `${ tax.singular } hinzufügen`,
			edit_item:     `${ tax.singular } bearbeiten`,
			search_items:  `${ tax.plural } suchen`,
			all_items:     `Alle ${ tax.plural }`,
			menu_name:     tax.plural,
		};
		const taxLabels = Object.entries( labelsObj )
			.map( ( [ k, v ] ) => `\t\t\t\t'${ k }' => '${ v }',` )
			.join( '\n' );

		return `
\t// ── Taxonomy: ${ tax.singular } (${ tax.slug }) ${ '─'.repeat( Math.max( 1, 56 - tax.singular.length ) ) }
\tregister_taxonomy(
\t\t'${ tax.slug }',
\t\t'${ slug }',
\t\tarray(
\t\t\t'labels'            => array(
${ taxLabels }
\t\t\t),
\t\t\t'hierarchical'      => ${ tax.hierarchical ? 'true' : 'false' },
\t\t\t'public'            => true,
\t\t\t'show_in_rest'      => true,
\t\t\t'show_admin_column' => true,
\t\t\t'rewrite'           => array( 'slug' => '${ tax.urlSlug }' ),
\t\t)
\t);`;
	} ).join( '\n' );

	const metaLines = metaFields.map( f => {
		return `\t\t'${ f.key }' => '${ f.description ?? f.label }',`;
	} ).join( '\n' );

	return `<?php
/**
 * CPT «${ plural }» и связанные таксономии.
 * Данные — в плагине, чтобы пережили смену темы.
 *
 * @package ${ textdomain }
 */

defined( 'ABSPATH' ) || exit;

add_action( 'init', function () {

\t// ── CPT: ${ plural } (${ slug }) ${ '─'.repeat( Math.max( 1, 54 - plural.length ) ) }
\tregister_post_type(
\t\t'${ slug }',
\t\tarray(
\t\t\t'labels'       => array(
\t\t\t\t'name'               => '${ plural }',
\t\t\t\t'singular_name'      => '${ singular }',
\t\t\t\t'add_new_item'       => '${ singular } hinzufügen',
\t\t\t\t'edit_item'          => '${ singular } bearbeiten',
\t\t\t\t'new_item'           => 'Neue${ endsWithR( singular ) ? 'r' : 's' } ${ singular }',
\t\t\t\t'view_item'          => '${ singular } ansehen',
\t\t\t\t'search_items'       => '${ plural } suchen',
\t\t\t\t'not_found'          => 'Keine ${ plural } gefunden',
\t\t\t\t'not_found_in_trash' => 'Keine ${ plural } im Papierkorb',
\t\t\t\t'all_items'          => 'Alle ${ plural }',
\t\t\t),
\t\t\t'public'       => true,
\t\t\t'has_archive'  => true,
\t\t\t'menu_icon'    => '${ icon }',
\t\t\t'menu_position'=> ${ menuPosition },
\t\t\t'supports'     => array( ${ supports.map( s => `'${ s }'` ).join( ', ' ) } ),
\t\t\t'show_in_rest' => true,
\t\t\t'rewrite'      => array( 'slug' => '${ urlSlug }' ),
\t\t)
\t);
${ taxRegistrations }
${ metaFields.length ? `
\t// ── Мета-поля ${ '─'.repeat( 67 ) }
\t$meta_fields = array(
${ metaLines }
\t);

\tforeach ( \\$meta_fields as \\$key => \\$description ) {
\t\tregister_post_meta(
\t\t\t'${ slug }',
\t\t\t\\$key,
\t\t\tarray(
\t\t\t\t'type'              => 'string',
\t\t\t\t'description'       => \\$description,
\t\t\t\t'default'           => '',
\t\t\t\t'single'            => true,
\t\t\t\t'sanitize_callback' => 'sanitize_text_field',
\t\t\t\t'auth_callback'     => fn() => current_user_can( 'edit_posts' ),
\t\t\t\t'show_in_rest'      => true,
\t\t\t)
\t\t);
\t}` : '' }
} );
`;
}

function endsWithR( s ) {
	const last = s.trim().slice( -1 ).toLowerCase();
	// Грубая эвристика для немецкого артикля «Neuer/Neues»
	return last === 'l' || last === 'r';
}

// ── Генерация meta-box.php ────────────────────────────────────────────────────

function genMetaBoxPhp() {
	if ( ! metaFields.length ) return null;

	const slugU      = slug.replace( /-/g, '_' );
	const nonceName  = `save_${ slugU }_meta`;
	const nonceField = `${ slugU }_meta_nonce`;
	const saveHook   = `save_post_${ slug }`;
	const fnPrefix   = textdomain.replace( /-/g, '_' );
	const fnHtmlName = `${ fnPrefix }_${ slugU }_meta_box_html`;

	// Имя PHP-переменной: без префикса slug (property_price → price)
	const varName = ( f ) => {
		const prefix = new RegExp( `^${ slugU }_` );
		return f.key.replace( prefix, '' ).replace( /[^a-z0-9]/gi, '_' );
	};

	const phpVars = metaFields.map( f => {
		const vn = varName( f );
		return `\t$${ vn } = get_post_meta( $post->ID, '${ f.key }', true )${ f.default ? ` ?: '${ f.default }'` : '' };`;
	} ).join( '\n' );

	const phpInputs = metaFields.map( f => {
		const vn = varName( f );
		if ( f.type === 'select' && f.options ) {
			const optArr = f.options.map( o => `'${ o }'` ).join( ', ' );
			const optsPhp = `\t\t\t\t\t<?php foreach ( array( ${ optArr } ) as $opt ) : ?>\n\t\t\t\t\t\t<option value="<?php echo esc_attr( $opt ); ?>" <?php selected( $${ vn }, $opt ); ?>><?php echo esc_html( $opt ); ?></option>\n\t\t\t\t\t<?php endforeach; ?>`;
			return `\t\t\t<div>\n\t\t\t\t<label for="${ f.key }">${ f.label }</label>\n\t\t\t\t<select id="${ f.key }" name="${ f.key }">\n${ optsPhp }\n\t\t\t\t</select>\n\t\t\t</div>`;
		}
		const ph = f.placeholder ? ` placeholder="${ f.placeholder }"` : '';
		return `\t\t\t<div>\n\t\t\t\t<label for="${ f.key }">${ f.label }</label>\n\t\t\t\t<input type="text" id="${ f.key }" name="${ f.key }" value="<?php echo esc_attr( $${ vn } ); ?>"${ ph } />\n\t\t\t</div>`;
	} ).join( '\n' );

	const saveLines = metaFields.map( f => {
		return `\tupdate_post_meta( $post_id, '${ f.key }', sanitize_text_field( wp_unslash( $_POST['${ f.key }'] ?? '' ) ) );`;
	} ).join( '\n' );

	return `<?php
/**
 * Meta box «${ singular }-Details» для CPT ${ slug }.
 *
 * @package ${ textdomain }
 */

defined( 'ABSPATH' ) || exit;

add_action( 'add_meta_boxes', function () {
\tadd_meta_box(
\t\t'${ slug }-details',
\t\t'${ singular }-Details',
\t\t'${ fnHtmlName }',
\t\t'${ slug }',
\t\t'normal',
\t\t'high'
\t);
} );

function ${ fnHtmlName }( WP_Post $post ): void {
\twp_nonce_field( '${ nonceName }', '${ nonceField }' );

${ phpVars }
\t?>
\t<style>
\t.pmb-grid { display:grid; grid-template-columns:1fr 1fr; gap:16px; padding:8px 0 4px; }
\t.pmb-grid label { display:block; font-weight:600; font-size:12px; text-transform:uppercase;
\t                   letter-spacing:.05em; color:#747c86; margin-bottom:4px; }
\t.pmb-grid input,.pmb-grid select { width:100%; box-sizing:border-box; }
\t</style>
\t<div class="pmb-grid">
${ phpInputs }
\t</div>
\t<?php
}

add_action( '${ saveHook }', function ( int $post_id ): void {
\tif (
\t\t! isset( $_POST['${ nonceField }'] ) ||
\t\t! wp_verify_nonce( sanitize_key( $_POST['${ nonceField }'] ), '${ nonceName }' ) ||
\t\t( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ||
\t\t! current_user_can( 'edit_post', $post_id )
\t) {
\t\treturn;
\t}

${ saveLines }
} );
`;
}

// ── Запись / вывод ────────────────────────────────────────────────────────────

const cptPhp  = genCptPhp();
const metaPhp = genMetaBoxPhp();

const cptFile  = resolve( INCLUDES, `${ slug }-cpt.php` );
const metaFile = resolve( INCLUDES, `${ slug }-meta-box.php` );

if ( DRY_RUN ) {
	console.log( '\n=== ' + cptFile + ' ===\n' );
	console.log( cptPhp );
	if ( metaPhp ) {
		console.log( '\n=== ' + metaFile + ' ===\n' );
		console.log( metaPhp );
	}
} else {
	if ( ! existsSync( INCLUDES ) ) mkdirSync( INCLUDES, { recursive: true } );

	writeFileSync( cptFile, cptPhp, 'utf8' );
	console.log( '✓ Записан: ' + cptFile );

	if ( metaPhp ) {
		writeFileSync( metaFile, metaPhp, 'utf8' );
		console.log( '✓ Записан: ' + metaFile );
	}
}

// ── Чеклист ───────────────────────────────────────────────────────────────────

console.log( `
📋 Что сделать вручную:
1. Добавить в ${ basename( MAIN_PHP ) }:
     require_once __DIR__ . '/includes/${ slug }-cpt.php';${ metaPhp ? `\n     require_once __DIR__ . '/includes/${ slug }-meta-box.php';` : '' }
2. Сбросить перемалинки (Settings → Permalinks → Save) после первого деплоя.
3. Создать блок отображения мета (render.php):
     projects/<project>/theme/blocks/${ slug }-meta/
4. Создать шаблоны:
     templates/single-${ slug }.html
     templates/archive-${ slug }.html
5. Добавить стили карточки в style.css.
6. Написать seed-скрипт для демо-данных (опц., по аналогии с seed-properties.mjs).
7. Деплой: npm run build && node scripts/deploy-stack.mjs
` );
