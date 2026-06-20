<?php
/**
 * WEM_Database
 *
 * Handles custom database table creation and management
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WEM_Database {

	const DEFAULT_CONFIGURATION_VERSION = '2026-06-03-inclusion-sources';

	/**
	 * Create custom database tables
	 */
	public static function create_tables() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();

		// Sources table
		$sources_table = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}em_sources (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			url VARCHAR(2048) NOT NULL,
			label VARCHAR(255),
			parser_mode VARCHAR(30) DEFAULT 'auto',
			css_selector VARCHAR(500),
			title_selector VARCHAR(500),
			date_selector VARCHAR(500),
			time_selector VARCHAR(500),
			description_selector VARCHAR(500),
			link_selector VARCHAR(500),
			enabled TINYINT(1) DEFAULT 1,
			last_scraped DATETIME,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY url (url(255))
		) {$charset_collate};";

		// Keywords table
		$keywords_table = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}em_keywords (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			keyword VARCHAR(255) NOT NULL,
			type ENUM('plain', 'regex') DEFAULT 'plain',
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY keyword (keyword)
		) {$charset_collate};";

		// Seen events table (for deduplication)
		$seen_table = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}em_seen (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			event_hash VARCHAR(32) NOT NULL,
			source_id BIGINT(20) UNSIGNED,
			source_url VARCHAR(2048),
			post_id BIGINT(20) UNSIGNED,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY event_hash (event_hash),
			KEY source_id (source_id),
			KEY post_id (post_id)
		) {$charset_collate};";

		// Log table
		$log_table = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}em_log (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			source_id BIGINT(20) UNSIGNED,
			source_url VARCHAR(2048),
			status ENUM('success', 'error', 'no_match') DEFAULT 'success',
			items_found INT DEFAULT 0,
			items_matched INT DEFAULT 0,
			posts_created INT DEFAULT 0,
			error_message TEXT,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY source_id (source_id),
			KEY created_at (created_at)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		dbDelta( $sources_table );
		dbDelta( $keywords_table );
		dbDelta( $seen_table );
		dbDelta( $log_table );

		// Set version
		update_option( 'wem_db_version', WEM_VERSION );
	}

	/**
	 * Seed useful event sources and inclusion-related keywords once.
	 */
	public static function seed_default_configuration() {
		if ( get_option( 'wem_default_configuration_version' ) === self::DEFAULT_CONFIGURATION_VERSION ) {
			return;
		}

		foreach ( self::default_sources() as $source ) {
			if ( WEM_Source_Manager::source_exists( $source['url'] ) ) {
				continue;
			}

			WEM_Source_Manager::add_source(
				$source['url'],
				$source['label'],
				'',
				'auto',
				array()
			);
		}

		foreach ( self::default_keywords() as $keyword ) {
			WEM_Source_Manager::add_keyword( $keyword, 'plain' );
		}

		update_option( 'wem_default_configuration_version', self::DEFAULT_CONFIGURATION_VERSION );
	}

	/**
	 * Default public sources for Vorarlberg inclusion/family events.
	 *
	 * @return array
	 */
	private static function default_sources() {
		return array(
			array(
				'url' => 'https://www.bsv.or.at/community/event-kalender/',
				'label' => 'BSV Event Kalender',
			),
			array(
				'url' => 'https://www.bildungshaus-batschuns.at/?inhalt=Veranstaltungen&id=5-1-0',
				'label' => 'Bildungshaus Batschuns Veranstaltungen',
			),
			array(
				'url' => 'https://www.connexia.at/termine/',
				'label' => 'connexia Termine',
			),
			array(
				'url' => 'https://www.netzwerk-familie.at/veranstaltungen',
				'label' => 'Netzwerk Familie Veranstaltungen',
			),
			array(
				'url' => 'https://www.lzh.at/aktuell/termine/ueberblick/',
				'label' => 'LZH Termine',
			),
			array(
				'url' => 'https://www.vorarlberg.travel/aktivitaet/veranstaltungen-kinder-familien/',
				'label' => 'Vorarlberg Kinder & Familien Veranstaltungen',
			),
		);
	}

	/**
	 * Default inclusion-related keywords.
	 *
	 * @return array
	 */
	private static function default_keywords() {
		return array(
			'Inklusion',
			'inklusiv',
			'inklusive',
			'Barrierefreiheit',
			'barrierefrei',
			'Behinderung',
			'beeinträchtigt',
			'Beeinträchtigung',
			'Menschen mit Behinderung',
			'Kinder mit Behinderung',
			'Jugendliche mit Behinderung',
			'Teilhabe',
			'Integration',
			'Diversität',
			'Vielfalt',
			'Chancengleichheit',
			'Selbstbestimmung',
			'Assistenz',
			'Unterstützung',
			'Pflege',
			'Entlastung',
			'Angehörige',
			'Eltern',
			'Familie',
			'Frühförderung',
			'Sonderpädagogik',
			'Autismus',
			'Down-Syndrom',
			'Selbsthilfe',
			'Beratung',
		);
	}

	/**
	 * Apply lightweight schema upgrades for already activated installs.
	 */
	public static function maybe_upgrade() {
		$installed_version = get_option( 'wem_db_version', '' );

		if ( $installed_version === WEM_VERSION ) {
			return;
		}

		self::create_tables();
		self::ensure_source_columns();
		update_option( 'wem_flush_rewrite_rules', '1' );
		update_option( 'wem_db_version', WEM_VERSION );
	}

	/**
	 * Ensure all sources have a parser mode, defaulting to auto.
	 */
	private static function ensure_source_columns() {
		global $wpdb;
		$table = $wpdb->prefix . 'em_sources';

		$columns = array(
			'parser_mode' => "ALTER TABLE {$table} ADD parser_mode VARCHAR(30) DEFAULT 'auto' AFTER label",
			'title_selector' => "ALTER TABLE {$table} ADD title_selector VARCHAR(500) AFTER css_selector",
			'date_selector' => "ALTER TABLE {$table} ADD date_selector VARCHAR(500) AFTER title_selector",
			'time_selector' => "ALTER TABLE {$table} ADD time_selector VARCHAR(500) AFTER date_selector",
			'description_selector' => "ALTER TABLE {$table} ADD description_selector VARCHAR(500) AFTER time_selector",
			'link_selector' => "ALTER TABLE {$table} ADD link_selector VARCHAR(500) AFTER description_selector",
		);

		foreach ( $columns as $column_name => $sql ) {
			$column = $wpdb->get_var( $wpdb->prepare( 'SHOW COLUMNS FROM ' . $table . ' LIKE %s', $column_name ) );
			if ( empty( $column ) ) {
				$wpdb->query( $sql );
			}
		}

		$wpdb->query( "UPDATE {$table} SET parser_mode = 'auto' WHERE parser_mode IS NULL OR parser_mode = ''" );
	}

	/**
	 * Get all sources
	 */
	public static function get_sources( $enabled_only = false ) {
		global $wpdb;
		$table = $wpdb->prefix . 'em_sources';

		$query = "SELECT * FROM {$table}";
		if ( $enabled_only ) {
			$query .= " WHERE enabled = 1";
		}
		$query .= " ORDER BY label ASC";

		return $wpdb->get_results( $query );
	}

	/**
	 * Get single source
	 */
	public static function get_source( $source_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'em_sources';

		return $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $source_id )
		);
	}

	/**
	 * Get all keywords
	 */
	public static function get_keywords() {
		global $wpdb;
		$table = $wpdb->prefix . 'em_keywords';

		return $wpdb->get_results( "SELECT * FROM {$table} ORDER BY keyword ASC" );
	}

	/**
	 * Log a scrape event
	 */
	public static function add_log( $source_id, $source_url, $status, $items_found = 0, $items_matched = 0, $posts_created = 0, $error_message = '' ) {
		global $wpdb;
		$table = $wpdb->prefix . 'em_log';

		return $wpdb->insert(
			$table,
			array(
				'source_id' => $source_id,
				'source_url' => $source_url,
				'status' => $status,
				'items_found' => $items_found,
				'items_matched' => $items_matched,
				'posts_created' => $posts_created,
				'error_message' => $error_message,
			),
			array( '%d', '%s', '%s', '%d', '%d', '%d', '%s' )
		);
	}

	/**
	 * Get recent logs
	 */
	public static function get_logs( $limit = 100 ) {
		global $wpdb;
		$table = $wpdb->prefix . 'em_log';

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} ORDER BY created_at DESC LIMIT %d",
				$limit
			)
		);
	}

	/**
	 * Check if event hash exists
	 */
	public static function hash_exists( $hash ) {
		global $wpdb;
		$table = $wpdb->prefix . 'em_seen';

		$seen = $wpdb->get_row(
			$wpdb->prepare( "SELECT id, post_id FROM {$table} WHERE event_hash = %s", $hash )
		);

		if ( $seen ) {
			if ( ! empty( $seen->post_id ) && self::post_exists( (int) $seen->post_id ) ) {
				return $seen->id;
			}

			$wpdb->delete(
				$table,
				array( 'id' => (int) $seen->id ),
				array( '%d' )
			);
		}

		$post_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value = %s LIMIT 1",
				'_em_event_hash',
				$hash
			)
		);

		return $post_id && self::post_exists( (int) $post_id ) ? $post_id : false;
	}

	/**
	 * Check whether a dedupe target post still exists.
	 *
	 * @param int $post_id Post ID
	 *
	 * @return bool True when the post exists and is not trashed
	 */
	private static function post_exists( $post_id ) {
		$status = get_post_status( $post_id );

		return ! empty( $status ) && 'trash' !== $status;
	}

	/**
	 * Check if an event post already exists for a source/title/date combination.
	 *
	 * @param int    $source_id Source ID
	 * @param string $title Event title
	 * @param string $date Event date in Ymd format
	 *
	 * @return int|false Existing post ID or false
	 */
	public static function event_post_exists( $source_id, $title, $date = '' ) {
		global $wpdb;

		$title = sanitize_text_field( $title );
		if ( empty( $source_id ) || empty( $title ) ) {
			return false;
		}

		$sql = "
			SELECT p.ID
			FROM {$wpdb->posts} p
			INNER JOIN {$wpdb->postmeta} source_meta
				ON source_meta.post_id = p.ID
				AND source_meta.meta_key = '_em_source_id'
				AND source_meta.meta_value = %d
			WHERE p.post_type = 'event'
				AND p.post_status != 'trash'
				AND p.post_title = %s
		";
		$args = array( (int) $source_id, $title );

		$sql .= ' LIMIT 1';

		$post_id = $wpdb->get_var( $wpdb->prepare( $sql, $args ) );

		return $post_id ? (int) $post_id : false;
	}

	/**
	 * Add event hash
	 */
	public static function add_hash( $hash, $source_id, $source_url, $post_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'em_seen';

		return $wpdb->insert(
			$table,
			array(
				'event_hash' => $hash,
				'source_id' => $source_id,
				'source_url' => $source_url,
				'post_id' => $post_id,
			),
			array( '%s', '%d', '%s', '%d' )
		);
	}

	/**
	 * Update source's last_scraped timestamp
	 */
	public static function update_source_scraped( $source_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'em_sources';

		return $wpdb->update(
			$table,
			array( 'last_scraped' => current_time( 'mysql' ) ),
			array( 'id' => $source_id ),
			array( '%s' ),
			array( '%d' )
		);
	}
}
