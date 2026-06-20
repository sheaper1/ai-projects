<?php
/**
 * WEM_Source_Manager
 *
 * Manages source URLs (CRUD operations)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WEM_Source_Manager {

	/**
	 * Add a new source URL
	 *
	 * @param string $url URL to scrape
	 * @param string $label Human-readable label
	 * @param string $css_selector Optional CSS selector
	 * @param string $parser_mode Parser mode: auto, html, or structured
	 * @param array $field_selectors Optional field selectors
	 *
	 * @return int|WP_Error Source ID on success, WP_Error on failure
	 */
	public static function add_source( $url, $label = '', $css_selector = '', $parser_mode = 'auto', $field_selectors = array() ) {
		global $wpdb;

		// Validate URL
		if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
			return new WP_Error( 'invalid_url', __( 'Invalid URL', 'wp-event-monitor' ) );
		}

		$url = esc_url_raw( $url );
		$label = sanitize_text_field( $label );
		$css_selector = sanitize_text_field( $css_selector );
		$parser_mode = self::sanitize_parser_mode( $parser_mode );
		$field_selectors = self::sanitize_field_selectors( $field_selectors );

		if ( empty( $label ) ) {
			$label = parse_url( $url, PHP_URL_HOST );
		}

		$table = $wpdb->prefix . 'em_sources';

		$result = $wpdb->insert(
			$table,
			array(
				'url' => $url,
				'label' => $label,
				'parser_mode' => $parser_mode,
				'css_selector' => $css_selector,
				'title_selector' => $field_selectors['title_selector'],
				'date_selector' => $field_selectors['date_selector'],
				'time_selector' => $field_selectors['time_selector'],
				'description_selector' => $field_selectors['description_selector'],
				'link_selector' => $field_selectors['link_selector'],
				'enabled' => 1,
			),
			array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d' )
		);

		if ( ! $result ) {
			return new WP_Error( 'db_error', __( 'Failed to add source', 'wp-event-monitor' ) );
		}

		return $wpdb->insert_id;
	}

	/**
	 * Check if a source URL already exists.
	 *
	 * @param string $url Source URL
	 *
	 * @return int Existing source ID or 0
	 */
	public static function source_exists( $url ) {
		global $wpdb;
		$table = $wpdb->prefix . 'em_sources';

		return (int) $wpdb->get_var(
			$wpdb->prepare( "SELECT id FROM {$table} WHERE url = %s", esc_url_raw( $url ) )
		);
	}

	/**
	 * Update a source
	 *
	 * @param int $source_id Source ID
	 * @param array $data Data to update (url, label, parser_mode, css_selector, enabled)
	 *
	 * @return bool True on success
	 */
	public static function update_source( $source_id, $data ) {
		global $wpdb;

		$updates = array();
		$formats = array();

		if ( isset( $data['url'] ) ) {
			if ( ! filter_var( $data['url'], FILTER_VALIDATE_URL ) ) {
				return false;
			}
			$updates['url'] = esc_url_raw( $data['url'] );
			$formats[] = '%s';
		}

		if ( isset( $data['label'] ) ) {
			$updates['label'] = sanitize_text_field( $data['label'] );
			$formats[] = '%s';
		}

		if ( isset( $data['parser_mode'] ) ) {
			$updates['parser_mode'] = self::sanitize_parser_mode( $data['parser_mode'] );
			$formats[] = '%s';
		}

		if ( isset( $data['css_selector'] ) ) {
			$updates['css_selector'] = sanitize_text_field( $data['css_selector'] );
			$formats[] = '%s';
		}

		foreach ( array( 'title_selector', 'date_selector', 'time_selector', 'description_selector', 'link_selector' ) as $selector_key ) {
			if ( isset( $data[ $selector_key ] ) ) {
				$updates[ $selector_key ] = sanitize_text_field( $data[ $selector_key ] );
				$formats[] = '%s';
			}
		}

		if ( isset( $data['enabled'] ) ) {
			$updates['enabled'] = (int) $data['enabled'];
			$formats[] = '%d';
		}

		if ( empty( $updates ) ) {
			return false;
		}

		$table = $wpdb->prefix . 'em_sources';

		return $wpdb->update(
			$table,
			$updates,
			array( 'id' => (int) $source_id ),
			$formats,
			array( '%d' )
		);
	}

	/**
	 * Delete a source
	 *
	 * @param int $source_id Source ID
	 *
	 * @return bool True on success
	 */
	public static function delete_source( $source_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'em_sources';

		return $wpdb->delete(
			$table,
			array( 'id' => (int) $source_id ),
			array( '%d' )
		);
	}

	/**
	 * Toggle source enabled/disabled status
	 *
	 * @param int $source_id Source ID
	 * @param bool $enabled Status
	 *
	 * @return bool True on success
	 */
	public static function toggle_source( $source_id, $enabled ) {
		return self::update_source( $source_id, array( 'enabled' => $enabled ? 1 : 0 ) );
	}

	/**
	 * Sanitize parser mode.
	 *
	 * @param string $parser_mode Raw parser mode
	 *
	 * @return string Safe parser mode
	 */
	private static function sanitize_parser_mode( $parser_mode ) {
		$parser_mode = sanitize_key( $parser_mode );

		return in_array( $parser_mode, array( 'auto', 'html', 'structured' ), true ) ? $parser_mode : 'auto';
	}

	/**
	 * Sanitize field selector map.
	 *
	 * @param array $field_selectors Raw selectors
	 *
	 * @return array Safe selectors
	 */
	private static function sanitize_field_selectors( $field_selectors ) {
		$keys = array( 'title_selector', 'date_selector', 'time_selector', 'description_selector', 'link_selector' );
		$sanitized = array();

		foreach ( $keys as $key ) {
			$sanitized[ $key ] = isset( $field_selectors[ $key ] ) ? sanitize_text_field( $field_selectors[ $key ] ) : '';
		}

		return $sanitized;
	}

	/**
	 * Add a keyword
	 *
	 * @param string $keyword Keyword text
	 * @param string $type 'plain' or 'regex'
	 *
	 * @return int|WP_Error Keyword ID on success
	 */
	public static function add_keyword( $keyword, $type = 'plain' ) {
		global $wpdb;

		$keyword = sanitize_text_field( $keyword );
		$type = in_array( $type, array( 'plain', 'regex' ), true ) ? $type : 'plain';

		if ( empty( $keyword ) ) {
			return new WP_Error( 'empty_keyword', __( 'Keyword cannot be empty', 'wp-event-monitor' ) );
		}

		// Validate regex if needed
		if ( $type === 'regex' ) {
			$validation = WEM_Keyword_Matcher::validate_regex( $keyword );
			if ( ! $validation['valid'] ) {
				return new WP_Error( 'invalid_regex', $validation['error'] );
			}
		}

		$table = $wpdb->prefix . 'em_keywords';

		$existing_id = self::keyword_exists( $keyword );

		if ( $existing_id ) {
			return (int) $existing_id;
		}

		$result = $wpdb->insert(
			$table,
			array(
				'keyword' => $keyword,
				'type' => $type,
			),
			array( '%s', '%s' )
		);

		if ( ! $result ) {
			return new WP_Error( 'db_error', __( 'Failed to add keyword', 'wp-event-monitor' ) );
		}

		return $wpdb->insert_id;
	}

	/**
	 * Add multiple keywords from textarea input.
	 *
	 * @param string $keywords_text Keywords separated by new lines, commas, or semicolons
	 * @param string $type 'plain' or 'regex'
	 *
	 * @return array Import summary
	 */
	public static function add_keywords_bulk( $keywords_text, $type = 'plain' ) {
		$type = in_array( $type, array( 'plain', 'regex' ), true ) ? $type : 'plain';
		$split_pattern = $type === 'regex' ? '/[\r\n]+/' : '/[\r\n,;]+/';
		$items = preg_split( $split_pattern, (string) $keywords_text );
		$summary = array(
			'added' => 0,
			'skipped' => 0,
			'failed' => 0,
			'errors' => array(),
		);
		$seen = array();

		foreach ( $items as $item ) {
			$keyword = sanitize_text_field( trim( $item ) );
			if ( empty( $keyword ) ) {
				continue;
			}

			$key = strtolower( $keyword );
			if ( isset( $seen[ $key ] ) ) {
				$summary['skipped']++;
				continue;
			}
			$seen[ $key ] = true;

			if ( self::keyword_exists( $keyword ) ) {
				$summary['skipped']++;
				continue;
			}

			$result = self::add_keyword( $keyword, $type );
			if ( is_wp_error( $result ) ) {
				$summary['failed']++;
				$summary['errors'][] = $keyword . ': ' . $result->get_error_message();
				continue;
			}

			$summary['added']++;
		}

		return $summary;
	}

	/**
	 * Check if a keyword already exists.
	 *
	 * @param string $keyword Keyword text
	 *
	 * @return int Existing keyword ID or 0
	 */
	public static function keyword_exists( $keyword ) {
		global $wpdb;
		$table = $wpdb->prefix . 'em_keywords';

		return (int) $wpdb->get_var(
			$wpdb->prepare( "SELECT id FROM {$table} WHERE keyword = %s", sanitize_text_field( $keyword ) )
		);
	}

	/**
	 * Delete a keyword
	 *
	 * @param int $keyword_id Keyword ID
	 *
	 * @return bool True on success
	 */
	public static function delete_keyword( $keyword_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'em_keywords';

		return $wpdb->delete(
			$table,
			array( 'id' => (int) $keyword_id ),
			array( '%d' )
		);
	}
}
