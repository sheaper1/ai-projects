<?php
/**
 * WEM_Cron
 *
 * Handles scheduled scraping via WordPress Cron
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WEM_Cron {

	const CRON_HOOK = 'wem_scrape_all_sources';
	const CRON_INTERVAL_OPTION = 'wem_cron_interval';

	/**
	 * Initialize cron
	 */
	public static function init() {
		add_filter( 'cron_schedules', array( __CLASS__, 'add_cron_intervals' ) );
		add_action( self::CRON_HOOK, array( __CLASS__, 'run_scrape' ) );
		self::schedule_cron();
	}

	/**
	 * Schedule the cron job
	 */
	public static function schedule_cron() {
		// Only schedule if not already scheduled
		if ( wp_next_scheduled( self::CRON_HOOK ) ) {
			return;
		}

		$schedule_type = get_option( 'wem_schedule_type', '' );
		if ( in_array( $schedule_type, array( 'weekly', 'biweekly', 'monthly' ), true ) ) {
			$schedule_day = (int) get_option( 'wem_schedule_day', 1 );
			$schedule_time = get_option( 'wem_schedule_time', '08:00' );

			wp_schedule_event(
				self::calculate_next_run( $schedule_type, $schedule_day, $schedule_time ),
				$schedule_type,
				self::CRON_HOOK
			);
			return;
		}

		$interval = get_option( self::CRON_INTERVAL_OPTION, 'hourly' );
		wp_schedule_event( time(), $interval, self::CRON_HOOK );
	}

	/**
	 * Unschedule the cron job (called on deactivation)
	 */
	public static function unschedule() {
		wp_clear_scheduled_hook( self::CRON_HOOK );
	}

	/**
	 * Reschedule cron with new interval (legacy)
	 *
	 * @param string $interval Cron interval
	 */
	public static function reschedule( $interval = 'hourly' ) {
		self::unschedule();
		update_option( self::CRON_INTERVAL_OPTION, $interval );

		self::schedule_cron();
	}

	/**
	 * Reschedule cron with new schedule settings
	 *
	 * @param string $type 'weekly', 'biweekly', 'monthly'
	 * @param int $day Day of week (0-6) or day of month (1-28)
	 * @param string $time Time in HH:MM format (e.g., '08:00')
	 */
	public static function reschedule_by_schedule( $type, $day, $time ) {
		self::unschedule();

		// Save settings
		update_option( 'wem_schedule_type', sanitize_text_field( $type ) );
		update_option( 'wem_schedule_day', (int) $day );
		update_option( 'wem_schedule_time', sanitize_text_field( $time ) );

		// Calculate next run timestamp
		$next_timestamp = self::calculate_next_run( $type, $day, $time );

		// Schedule the event
		wp_schedule_event( $next_timestamp, $type, self::CRON_HOOK );
	}

	/**
	 * Calculate next run timestamp based on schedule settings
	 *
	 * @param string $type 'weekly', 'biweekly', 'monthly'
	 * @param int $day Day of week (0-6) or day of month (1-28)
	 * @param string $time Time in HH:MM format
	 *
	 * @return int Next run timestamp
	 */
	private static function calculate_next_run( $type, $day, $time ) {
		$time_parts = explode( ':', $time );
		$hour = isset( $time_parts[0] ) ? (int) $time_parts[0] : 0;
		$minute = isset( $time_parts[1] ) ? (int) $time_parts[1] : 0;

		$current_time = current_time( 'timestamp' );
		$current_day = (int) wp_date( 'w', null ); // 0 (Sunday) through 6 (Saturday)

		if ( $type === 'weekly' ) {
			// Find next occurrence of the specified day
			$days_ahead = $day - $current_day;
			if ( $days_ahead < 0 ) {
				$days_ahead += 7;
			}
			$next_date = strtotime( "+{$days_ahead} days", strtotime( 'today' ) );
		} elseif ( $type === 'biweekly' ) {
			// Every 2 weeks on the same day
			$days_ahead = $day - $current_day;
			if ( $days_ahead < 0 ) {
				$days_ahead += 14;
			}
			$next_date = strtotime( "+{$days_ahead} days", strtotime( 'today' ) );
		} else { // monthly
			// Find next occurrence of the specified day of month
			$today = (int) wp_date( 'd', null );
			if ( $day >= $today ) {
				$next_date = strtotime( "first day of this month +" . ( $day - 1 ) . " days" );
			} else {
				$next_date = strtotime( "first day of next month +" . ( $day - 1 ) . " days" );
			}
		}

		// Set the time
		$next_timestamp = strtotime( "{$hour}:{$minute}:00", $next_date );

		// If the selected time has already passed, move to the next interval.
		if ( $next_timestamp <= $current_time ) {
			if ( $type === 'weekly' ) {
				$next_timestamp = strtotime( "+7 days", $next_timestamp );
			} elseif ( $type === 'biweekly' ) {
				$next_timestamp = strtotime( "+14 days", $next_timestamp );
			} else {
				$next_timestamp = strtotime( "+1 month", $next_timestamp );
			}
		}

		return $next_timestamp;
	}

	/**
	 * Get the next scheduled run time
	 *
	 * @return string Formatted date/time or empty if not scheduled
	 */
	public static function get_next_run() {
		$timestamp = wp_next_scheduled( self::CRON_HOOK );
		if ( ! $timestamp ) {
			return '';
		}
		return wp_date( 'l, j. M Y H:i', $timestamp );
	}

	/**
	 * Add custom cron intervals
	 *
	 * @param array $schedules Existing schedules
	 *
	 * @return array Updated schedules
	 */
	public static function add_cron_intervals( $schedules ) {
		$schedules['every_3_hours'] = array(
			'interval' => 3 * HOUR_IN_SECONDS,
			'display' => __( 'Every 3 hours', 'wp-event-monitor' ),
		);

		$schedules['every_6_hours'] = array(
			'interval' => 6 * HOUR_IN_SECONDS,
			'display' => __( 'Every 6 hours', 'wp-event-monitor' ),
		);

		$schedules['weekly'] = array(
			'interval' => 7 * DAY_IN_SECONDS,
			'display' => __( 'Weekly', 'wp-event-monitor' ),
		);

		$schedules['biweekly'] = array(
			'interval' => 14 * DAY_IN_SECONDS,
			'display' => __( 'Bi-weekly', 'wp-event-monitor' ),
		);

		$schedules['monthly'] = array(
			'interval' => 30 * DAY_IN_SECONDS,
			'display' => __( 'Monthly', 'wp-event-monitor' ),
		);

		return $schedules;
	}

	/**
	 * Run the scraping job
	 */
	public static function run_scrape() {
		// Get all enabled sources
		$sources = WEM_Database::get_sources( true );
		$keywords = WEM_Database::get_keywords();

		if ( empty( $sources ) || empty( $keywords ) ) {
			return;
		}

		foreach ( $sources as $source ) {
			self::scrape_source( $source, $keywords );
		}
	}

	/**
	 * Scrape a single source
	 *
	 * @param object $source Source object
	 * @param array $keywords Keywords array
	 */
	private static function scrape_source( $source, $keywords ) {
		// Scrape the URL
		$parser_mode = ! empty( $source->parser_mode ) ? $source->parser_mode : 'auto';
		$field_selectors = self::get_source_field_selectors( $source );
		$result = WEM_Scraper::scrape( $source->url, $source->css_selector, $parser_mode, $field_selectors );

		if ( ! empty( $result['error'] ) ) {
			WEM_Database::add_log(
				$source->id,
				$source->url,
				'error',
				0,
				0,
				0,
				$result['error']
			);
			return;
		}

		$events = $result['events'];
		$items_found = count( $events );
		$items_matched = 0;
		$posts_created = 0;
		$duplicates_skipped = 0;
		$errors = array();
		$seen_in_run = array();

		foreach ( $events as $event ) {
			// Match keywords
			$matched = WEM_Keyword_Matcher::match( $event, $keywords );
			if ( empty( $matched ) ) {
				continue;
			}

			$items_matched++;
			$event = WEM_Scraper::enrich_event( $event );
			if ( WEM_Scraper::is_ignored_event_title( $event['title'] ?? '' ) ) {
				continue;
			}

			$hash = WEM_Scraper::generate_hash( $event['title'], $event['href'] ?? '', $event['date'] ?? '', $source->id );
			$legacy_hash = WEM_Scraper::generate_legacy_hash( $event['title'], $event['href'] ?? '', $event['date'] ?? '' );
			$event['hash'] = $hash;

			if ( isset( $seen_in_run[ $hash ] ) || WEM_Database::hash_exists( $hash ) || WEM_Database::hash_exists( $legacy_hash ) || WEM_Database::event_post_exists( $source->id, $event['title'], $event['date'] ?? '' ) ) {
				$duplicates_skipped++;
				continue;
			}

			$seen_in_run[ $hash ] = true;

			// Create draft post
			$post_id = WEM_Post_Creator::create_post( $event, $matched, $source->id, $source->url );
			if ( ! is_wp_error( $post_id ) ) {
				WEM_Database::add_hash( $hash, $source->id, $source->url, $post_id );
				$posts_created++;
			} else {
				$errors[] = $event['title'] . ': ' . $post_id->get_error_message();
			}
		}

		// Update source's last_scraped timestamp
		WEM_Database::update_source_scraped( $source->id );

		// Log the event
		WEM_Database::add_log(
			$source->id,
			$source->url,
			empty( $errors ) ? 'success' : 'error',
			$items_found,
			$items_matched,
			$posts_created,
			self::format_log_message( $errors, $duplicates_skipped )
		);
	}

	/**
	 * Format optional scrape details for the activity log.
	 *
	 * @param array $errors Creation errors
	 * @param int   $duplicates_skipped Duplicate events skipped
	 *
	 * @return string Log message
	 */
	private static function format_log_message( $errors, $duplicates_skipped ) {
		$messages = array();

		if ( $duplicates_skipped > 0 ) {
			$messages[] = sprintf(
				/* translators: %d: number of skipped duplicate events */
				__( 'Skipped duplicates: %d', 'wp-event-monitor' ),
				(int) $duplicates_skipped
			);
		}

		if ( ! empty( $errors ) ) {
			$messages = array_merge( $messages, array_slice( $errors, 0, 5 ) );
		}

		return implode( '; ', $messages );
	}

	/**
	 * Manually trigger a scrape for a specific source
	 *
	 * @param int $source_id Source ID
	 *
	 * @return array Result status
	 */
	public static function manual_scrape( $source_id ) {
		$source = WEM_Database::get_source( $source_id );
		if ( ! $source ) {
			return array(
				'success' => false,
				'message' => __( 'Source not found', 'wp-event-monitor' ),
			);
		}

		$keywords = WEM_Database::get_keywords();
		if ( empty( $keywords ) ) {
			return array(
				'success' => false,
				'message' => __( 'No keywords defined', 'wp-event-monitor' ),
			);
		}

		self::scrape_source( $source, $keywords );

		return array(
			'success' => true,
			'message' => __( 'Scrape completed', 'wp-event-monitor' ),
		);
	}

	/**
	 * Preview a source scrape without creating drafts.
	 *
	 * @param int $source_id Source ID
	 *
	 * @return array Preview result
	 */
	public static function preview_source( $source_id ) {
		$source = WEM_Database::get_source( $source_id );
		if ( ! $source ) {
			return array(
				'success' => false,
				'message' => __( 'Source not found', 'wp-event-monitor' ),
				'events' => array(),
			);
		}

		$keywords = WEM_Database::get_keywords();
		if ( empty( $keywords ) ) {
			return array(
				'success' => false,
				'message' => __( 'No keywords defined', 'wp-event-monitor' ),
				'events' => array(),
			);
		}

		$result = WEM_Scraper::scrape(
			$source->url,
			$source->css_selector,
			! empty( $source->parser_mode ) ? $source->parser_mode : 'auto',
			self::get_source_field_selectors( $source )
		);

		if ( ! empty( $result['error'] ) ) {
			return array(
				'success' => false,
				'message' => $result['error'],
				'events' => array(),
			);
		}

		$events = array();
		foreach ( $result['events'] as $event ) {
			$matched = WEM_Keyword_Matcher::match( $event, $keywords );
			$hash = WEM_Scraper::generate_hash( $event['title'], $event['href'] ?? '', $event['date'] ?? '', $source->id );
			$legacy_hash = WEM_Scraper::generate_legacy_hash( $event['title'], $event['href'] ?? '', $event['date'] ?? '' );
			$events[] = array(
				'title' => $event['title'],
				'date' => $event['date'] ?? '',
				'time' => $event['time'] ?? '',
				'href' => $event['href'] ?? '',
				'description' => wp_trim_words( wp_strip_all_tags( $event['description'] ?? '' ), 24, '...' ),
				'matched' => $matched,
				'duplicate' => (bool) ( WEM_Database::hash_exists( $hash ) || WEM_Database::hash_exists( $legacy_hash ) ),
			);
		}

		return array(
			'success' => true,
			'message' => __( 'Preview completed', 'wp-event-monitor' ),
			'found' => count( $result['events'] ),
			'matched' => count( array_filter( $events, static function( $event ) {
				return ! empty( $event['matched'] );
			} ) ),
			'events' => $events,
		);
	}

	/**
	 * Get optional field selectors from a source row.
	 *
	 * @param object $source Source row
	 *
	 * @return array Field selectors
	 */
	private static function get_source_field_selectors( $source ) {
		return array(
			'title_selector' => $source->title_selector ?? '',
			'date_selector' => $source->date_selector ?? '',
			'time_selector' => $source->time_selector ?? '',
			'description_selector' => $source->description_selector ?? '',
			'link_selector' => $source->link_selector ?? '',
		);
	}
}
