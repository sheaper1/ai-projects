<?php
/**
 * WEM_Content_Types
 *
 * Registers the event post type and taxonomies used by the standalone plugin.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WEM_Content_Types {

	/**
	 * Register hooks.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register' ), 5 );
	}

	/**
	 * Register event content types.
	 */
	public static function register() {
		if ( ! post_type_exists( 'event' ) ) {
			register_post_type(
				'event',
				array(
					'labels' => array(
						'name' => __( 'Events', 'wp-event-monitor' ),
						'singular_name' => __( 'Event', 'wp-event-monitor' ),
						'add_new_item' => __( 'Add New Event', 'wp-event-monitor' ),
						'edit_item' => __( 'Edit Event', 'wp-event-monitor' ),
						'new_item' => __( 'New Event', 'wp-event-monitor' ),
						'view_item' => __( 'View Event', 'wp-event-monitor' ),
						'search_items' => __( 'Search Events', 'wp-event-monitor' ),
						'not_found' => __( 'No events found', 'wp-event-monitor' ),
					),
					'public' => true,
					'has_archive' => true,
					'menu_icon' => 'dashicons-calendar-alt',
					'rewrite' => array( 'slug' => 'event' ),
					'show_in_rest' => true,
					'supports' => array( 'title', 'editor', 'excerpt', 'thumbnail', 'custom-fields' ),
				)
			);
		}

		if ( ! taxonomy_exists( 'category-tag' ) ) {
			register_taxonomy(
				'category-tag',
				'event',
				array(
					'labels' => array(
						'name' => __( 'Event Categories', 'wp-event-monitor' ),
						'singular_name' => __( 'Event Category', 'wp-event-monitor' ),
					),
					'hierarchical' => false,
					'public' => true,
					'rewrite' => array( 'slug' => 'event-category' ),
					'show_admin_column' => true,
					'show_in_rest' => true,
				)
			);
		}

		if ( ! taxonomy_exists( 'city-name' ) ) {
			register_taxonomy(
				'city-name',
				'event',
				array(
					'labels' => array(
						'name' => __( 'Cities', 'wp-event-monitor' ),
						'singular_name' => __( 'City', 'wp-event-monitor' ),
					),
					'hierarchical' => false,
					'public' => true,
					'rewrite' => array( 'slug' => 'event-city' ),
					'show_admin_column' => true,
					'show_in_rest' => true,
				)
			);
		}

		if ( get_option( 'wem_flush_rewrite_rules' ) ) {
			flush_rewrite_rules();
			delete_option( 'wem_flush_rewrite_rules' );
		}
	}
}
