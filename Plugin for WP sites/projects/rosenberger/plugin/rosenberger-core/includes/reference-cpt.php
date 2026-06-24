<?php
/**
 * CPT `reference` — завершённые сделки / референсы (проданный объект + отзыв).
 * Отдельный тип записи рядом с `property`: активный каталог и референсы не
 * смешиваются. Мета-схема — в reference-fields.php (ключи `property_*`
 * переиспользуются, чтобы блоки property-hero/stats/gallery работали как есть).
 *
 * @package rosenberger-core
 */

defined( 'ABSPATH' ) || exit;

add_action( 'init', function () {

	// ── CPT: Referenzen (reference) ─────────────────────────────────────────
	register_post_type(
		'reference',
		array(
			'labels'        => array(
				'name'               => 'Referenzen',
				'singular_name'      => 'Referenz',
				'add_new_item'       => 'Referenz hinzufügen',
				'edit_item'          => 'Referenz bearbeiten',
				'new_item'           => 'Neue Referenz',
				'view_item'          => 'Referenz ansehen',
				'search_items'       => 'Referenzen suchen',
				'not_found'          => 'Keine Referenzen gefunden',
				'not_found_in_trash' => 'Keine Referenzen im Papierkorb',
				'all_items'          => 'Alle Referenzen',
			),
			'public'        => true,
			'has_archive'   => true,
			'menu_icon'     => 'dashicons-awards',
			'menu_position' => 6,
			'supports'      => array( 'title', 'editor', 'thumbnail', 'excerpt', 'revisions' ),
			'show_in_rest'  => true,
			'rewrite'       => array( 'slug' => 'references' ),
			'taxonomies'    => array( 'reference-type', 'reference-city' ),
		)
	);

	// ── Taxonomy: Typ (Haus, Wohnung, Grundstück, Gewerbe) ──────────────────
	register_taxonomy(
		'reference-type',
		'reference',
		array(
			'labels'            => array(
				'name'          => 'Referenz-Typen',
				'singular_name' => 'Typ',
				'menu_name'     => 'Typen',
			),
			'hierarchical'      => true,
			'public'            => true,
			'show_in_rest'      => true,
			'show_admin_column' => true,
			'rewrite'           => array( 'slug' => 'references-typ' ),
		)
	);

	// ── Taxonomy: Lage / Ort ────────────────────────────────────────────────
	register_taxonomy(
		'reference-city',
		'reference',
		array(
			'labels'            => array(
				'name'          => 'Referenz-Orte',
				'singular_name' => 'Ort',
				'menu_name'     => 'Orte',
			),
			'hierarchical'      => false,
			'public'            => true,
			'show_in_rest'      => true,
			'show_admin_column' => true,
			'rewrite'           => array( 'slug' => 'references-ort' ),
		)
	);

	// ── Meta-поля (схема — в reference-fields.php) ───────────────────────────
	foreach ( rosenberger_reference_fields() as $key => $def ) {
		$type = $def['type'] ?? 'text';
		register_post_meta(
			'reference',
			$key,
			array(
				'type'              => 'string',
				'description'       => $def['label'] ?? $key,
				'default'           => $def['default'] ?? '',
				'single'            => true,
				'sanitize_callback' => fn( $v ) => rosenberger_property_sanitize( $type, $v ),
				'auth_callback'     => fn() => current_user_can( 'edit_posts' ),
				'show_in_rest'      => true,
			)
		);
	}
} );
