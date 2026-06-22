<?php
/**
 * Кастомные записи проекта.
 * CPT `property` держим в плагине — данные переживают смену темы.
 *
 * @package rosenberger-core
 */

defined( 'ABSPATH' ) || exit;

add_action( 'init', function () {

	// ── CPT: Objekte (property) ──────────────────────────────────────────────
	register_post_type(
		'property',
		array(
			'labels'            => array(
				'name'               => 'Objekte',
				'singular_name'      => 'Objekt',
				'add_new_item'       => 'Objekt hinzufügen',
				'edit_item'          => 'Objekt bearbeiten',
				'new_item'           => 'Neues Objekt',
				'view_item'          => 'Objekt ansehen',
				'search_items'       => 'Objekte suchen',
				'not_found'          => 'Keine Objekte gefunden',
				'not_found_in_trash' => 'Keine Objekte im Papierkorb',
				'all_items'          => 'Alle Objekte',
			),
			'public'            => true,
			'has_archive'       => true,
			'menu_icon'         => 'dashicons-building',
			'menu_position'     => 5,
			'supports'          => array( 'title', 'editor', 'thumbnail', 'excerpt', 'revisions' ),
			'show_in_rest'      => true,
			'rewrite'           => array( 'slug' => 'objekte' ),
			'taxonomies'        => array( 'property-type', 'property-city' ),
		)
	);

	// ── Taxonomy: Typ (Wohnung, Haus, Grundstück…) ──────────────────────────
	register_taxonomy(
		'property-type',
		'property',
		array(
			'labels'            => array(
				'name'          => 'Typen',
				'singular_name' => 'Typ',
				'add_new_item'  => 'Typ hinzufügen',
				'edit_item'     => 'Typ bearbeiten',
				'search_items'  => 'Typen suchen',
				'all_items'     => 'Alle Typen',
				'menu_name'     => 'Typen',
			),
			'hierarchical'      => true,
			'public'            => true,
			'show_in_rest'      => true,
			'show_admin_column' => true,
			'rewrite'           => array( 'slug' => 'objekte-typ' ),
		)
	);

	// ── Taxonomy: Lage / Ort (Feldkirch, Dornbirn…) ─────────────────────────
	register_taxonomy(
		'property-city',
		'property',
		array(
			'labels'            => array(
				'name'          => 'Orte',
				'singular_name' => 'Ort',
				'add_new_item'  => 'Ort hinzufügen',
				'edit_item'     => 'Ort bearbeiten',
				'search_items'  => 'Orte suchen',
				'all_items'     => 'Alle Orte',
				'menu_name'     => 'Orte',
			),
			'hierarchical'      => false,
			'public'            => true,
			'show_in_rest'      => true,
			'show_admin_column' => true,
			'rewrite'           => array( 'slug' => 'objekte-ort' ),
		)
	);

	// ── Meta-поля (show_in_rest для Block Bindings и REST-фильтрации) ────────
	$meta_fields = array(
		'property_price'  => 'Kaufpreis (z. B. «Auf Anfrage» oder «€ 450.000»)',
		'property_area'   => 'Wohnfläche (z. B. «ca. 130 m²»)',
		'property_rooms'  => 'Zimmer (z. B. «4» oder «4,5»)',
		'property_status' => 'Status: Verfügbar | Reserviert | Verkauft',
	);

	foreach ( $meta_fields as $key => $description ) {
		register_post_meta(
			'property',
			$key,
			array(
				'type'              => 'string',
				'description'       => $description,
				'default'           => '',
				'single'            => true,
				'sanitize_callback' => 'sanitize_text_field',
				'auth_callback'     => fn() => current_user_can( 'edit_posts' ),
				'show_in_rest'      => true,
			)
		);
	}
} );

// ── Custom REST endpoint: PATCH /rosenberger/v1/property/<id>/meta ──────────
add_action( 'rest_api_init', function () {
	register_rest_route(
		'rosenberger/v1',
		'/property/(?P<id>\d+)/meta',
		array(
			'methods'             => 'POST',
			'permission_callback' => fn() => current_user_can( 'edit_posts' ),
			'args'                => array(
				'id' => array( 'validate_callback' => fn( $v ) => is_numeric( $v ) && get_post( $v ) ),
			),
			'callback'            => function ( WP_REST_Request $req ) {
				$id      = (int) $req['id'];
				$allowed = array( 'property_price', 'property_area', 'property_rooms', 'property_status' );
				$updated = array();
				foreach ( $allowed as $key ) {
					if ( $req->has_param( $key ) ) {
						$val = sanitize_text_field( $req->get_param( $key ) );
						update_post_meta( $id, $key, $val );
						$updated[ $key ] = get_post_meta( $id, $key, true );
					}
				}
				return rest_ensure_response( array( 'id' => $id, 'meta' => $updated ) );
			},
		)
	);
} );
