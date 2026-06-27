<?php
/**
 * CPT `region` — лендинги регионов (Bludenz, Bregenz, Dornbirn, Feldkirch).
 * БЕЗ архива: каждая запись — отдельная страница (single-region.html).
 * Структура одинаковая, меняется название города + контент в редакторе.
 * Объекты на странице фильтруются по таксономии `property-city` = slug записи.
 *
 * Мета hero (подзаголовок/кнопка/note) — здесь же. Остальной контент региона —
 * в редакторе (post_content) блоками, предзаполняется паттерном при seed.
 *
 * @package rosenberger-core
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'init',
	function () {
		register_post_type(
			'region',
			array(
				'labels'        => array(
					'name'               => 'Regionen',
					'singular_name'      => 'Region',
					'add_new_item'       => 'Region hinzufügen',
					'edit_item'          => 'Region bearbeiten',
					'new_item'           => 'Neue Region',
					'view_item'          => 'Region ansehen',
					'search_items'       => 'Regionen suchen',
					'not_found'          => 'Keine Regionen gefunden',
					'not_found_in_trash' => 'Keine Regionen im Papierkorb',
					'all_items'          => 'Alle Regionen',
				),
				'public'        => true,
				'has_archive'   => false,
				'menu_icon'     => 'dashicons-location-alt',
				'menu_position' => 7,
				'supports'      => array( 'title', 'editor', 'thumbnail', 'excerpt', 'revisions', 'custom-fields' ),
				'show_in_rest'  => true,
				'rewrite'       => array( 'slug' => 'region', 'with_front' => false ),
			)
		);

		// Одноразовый сброс правил перезаписи после деплоя (CPT без архива, slug /region/).
		if ( '2' !== get_option( 'rosenberger_region_flushed' ) ) {
			flush_rewrite_rules( false );
			update_option( 'rosenberger_region_flushed', '2' );
		}

		foreach ( array( 'region_subtitle', 'region_button_text', 'region_button_url', 'region_note' ) as $key ) {
			register_post_meta(
				'region',
				$key,
				array(
					'type'         => 'string',
					'single'       => true,
					'default'      => '',
					'show_in_rest' => true,
					'auth_callback' => function () {
						return current_user_can( 'edit_posts' );
					},
				)
			);
		}
	}
);

/**
 * Мета-бокс «Region Hero».
 */
add_action(
	'add_meta_boxes',
	function () {
		add_meta_box(
			'region-hero',
			'Region Hero',
			'rosenberger_region_hero_box',
			'region',
			'normal',
			'high'
		);
	}
);

function rosenberger_region_hero_box( WP_Post $post ): void {
	wp_nonce_field( 'region_hero_save', 'region_hero_nonce' );
	$fields = array(
		'region_subtitle'    => array( 'label' => 'Untertitel (Hero)', 'type' => 'textarea', 'ph' => 'Ehrlich beraten in …, ob Sie verkaufen, kaufen oder den Wert Ihrer Immobilie wissen wollen.' ),
		'region_button_text' => array( 'label' => 'Button-Text', 'type' => 'text', 'ph' => 'Kostenlos beraten lassen' ),
		'region_button_url'  => array( 'label' => 'Button-Link', 'type' => 'text', 'ph' => '/kontakt/' ),
		'region_note'        => array( 'label' => 'Hinweis unter Button', 'type' => 'text', 'ph' => 'Unverbindlich und kostenlos' ),
	);
	echo '<div style="display:flex;flex-direction:column;gap:12px;padding-top:8px">';
	foreach ( $fields as $key => $def ) {
		$val = get_post_meta( $post->ID, $key, true );
		printf( '<label style="font-weight:600">%s</label>', esc_html( $def['label'] ) );
		if ( 'textarea' === $def['type'] ) {
			printf( '<textarea name="%s" rows="2" placeholder="%s" style="width:100%%">%s</textarea>', esc_attr( $key ), esc_attr( $def['ph'] ), esc_textarea( $val ) );
		} else {
			printf( '<input type="text" name="%s" value="%s" placeholder="%s" style="width:100%%">', esc_attr( $key ), esc_attr( $val ), esc_attr( $def['ph'] ) );
		}
	}
	echo '</div>';
}

add_action(
	'save_post_region',
	function ( $post_id ) {
		if ( ! isset( $_POST['region_hero_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['region_hero_nonce'] ) ), 'region_hero_save' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		foreach ( array( 'region_subtitle', 'region_button_text', 'region_button_url', 'region_note' ) as $key ) {
			if ( isset( $_POST[ $key ] ) ) {
				update_post_meta( $post_id, $key, sanitize_textarea_field( wp_unslash( $_POST[ $key ] ) ) );
			}
		}
	}
);
