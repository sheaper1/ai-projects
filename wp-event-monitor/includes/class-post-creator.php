<?php
/**
 * WEM_Post_Creator
 *
 * Creates WordPress event drafts from scraped events
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WEM_Post_Creator {

	/**
	 * Create a draft event from scraped event data.
	 *
	 * @param array $event Event data
	 * @param array $matched_keywords Array of matched keywords
	 * @param int $source_id Source ID
	 * @param string $source_url Source URL
	 *
	 * @return int|WP_Error Post ID on success, WP_Error on failure
	 */
	public static function create_post( $event, $matched_keywords, $source_id, $source_url ) {
		$title = sanitize_text_field( $event['title'] );
		$about_event = self::prepare_about_event( $event, $source_url );
		$tags = self::prepare_tags( $matched_keywords );

		$post_id = wp_insert_post(
			array(
				'post_title' => $title,
				'post_content' => $about_event,
				'post_excerpt' => wp_trim_words( wp_strip_all_tags( $about_event ), 35, '...' ),
				'post_status' => 'draft',
				'post_type' => 'event',
			)
		);

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		if ( ! $post_id ) {
			return new WP_Error( 'event_create_failed', __( 'Failed to create event draft', 'wp-event-monitor' ) );
		}

		if ( ! empty( $tags ) ) {
			self::set_terms_by_name( $post_id, $tags, 'category-tag' );
		}

		if ( ! empty( $event['city'] ) ) {
			self::set_terms_by_name( $post_id, array( sanitize_text_field( $event['city'] ) ), 'city-name' );
		}

		self::update_acf_field( 'field_69e14e141f715', 'about_event', $about_event, $post_id );

		self::store_event_meta( $post_id, $event, $about_event, $source_id, $source_url );

		$stored_image_url = '';
		if ( ! empty( $event['image_url'] ) ) {
			$stored_image_url = self::import_featured_image( $post_id, $event, $source_url );
		}

		if ( empty( $stored_image_url ) ) {
			$stored_image_url = self::assign_random_fallback_image( $post_id );
		}

		update_post_meta( $post_id, '_em_source_url', esc_url_raw( $source_url ) );
		update_post_meta( $post_id, '_em_source_id', (int) $source_id );
		update_post_meta( $post_id, '_em_event_hash', ! empty( $event['hash'] ) ? sanitize_text_field( $event['hash'] ) : WEM_Scraper::generate_hash( $event['title'], $event['href'] ?? '', $event['date'] ?? '', $source_id ) );
		update_post_meta( $post_id, '_em_matched_keywords', $matched_keywords );
		update_post_meta( $post_id, '_em_event_url', ! empty( $event['href'] ) ? esc_url_raw( $event['href'] ) : '' );
		update_post_meta( $post_id, '_em_image_url', $stored_image_url );
		update_post_meta( $post_id, '_em_import_status', 'imported' );

		return $post_id;
	}

	/**
	 * Store plugin-native event data while keeping existing ACF fields in sync.
	 *
	 * @param int    $post_id Post ID
	 * @param array  $event Event data
	 * @param string $about_event Prepared event HTML
	 * @param int    $source_id Source ID
	 * @param string $source_url Source URL
	 */
	private static function store_event_meta( $post_id, $event, $about_event, $source_id, $source_url ) {
		$date = ! empty( $event['date'] ) ? sanitize_text_field( $event['date'] ) : '';
		$time = ! empty( $event['time'] ) ? sanitize_text_field( $event['time'] ) : '';
		$phone = ! empty( $event['phone'] ) ? sanitize_text_field( $event['phone'] ) : '';
		$email = ! empty( $event['email'] ) ? sanitize_email( $event['email'] ) : '';
		$location = ! empty( $event['location'] ) ? sanitize_text_field( $event['location'] ) : '';
		$event_url = ! empty( $event['href'] ) ? esc_url_raw( $event['href'] ) : '';
		$has_ticket = ! empty( $event['href'] ) && self::is_ticket_link( $event );

		update_post_meta( $post_id, '_wem_event_date', $date );
		update_post_meta( $post_id, '_wem_event_time', $time );
		update_post_meta( $post_id, '_wem_event_phone', $phone );
		update_post_meta( $post_id, '_wem_event_email', $email );
		update_post_meta( $post_id, '_wem_event_location', $location );
		update_post_meta( $post_id, '_wem_event_contact', trim( implode( ' | ', array_filter( array( $phone, $email ) ) ) ) );
		update_post_meta( $post_id, '_wem_event_url', $event_url );
		update_post_meta( $post_id, '_wem_ticket_url', $has_ticket ? $event_url : '' );
		update_post_meta( $post_id, '_wem_has_ticket', $has_ticket ? '1' : '0' );
		update_post_meta( $post_id, '_wem_source_id', (int) $source_id );
		update_post_meta( $post_id, '_wem_source_url', esc_url_raw( $source_url ) );
		update_post_meta( $post_id, '_wem_about_event', wp_kses_post( $about_event ) );
		update_post_meta( $post_id, '_wem_event_description', wp_trim_words( wp_strip_all_tags( $about_event ), 45, '...' ) );

		if ( ! empty( $event['schedule'] ) && is_array( $event['schedule'] ) ) {
			update_post_meta( $post_id, '_wem_event_schedule', self::sanitize_schedule( $event['schedule'], $date, $time ) );
		}

		if ( ! empty( $event['city'] ) ) {
			update_post_meta( $post_id, '_wem_event_city', sanitize_text_field( $event['city'] ) );
		}

		if ( $date ) {
			self::update_acf_field( 'field_69e143ebdc608', 'event_date', $date, $post_id );
		}

		if ( $time ) {
			self::update_acf_field( 'field_69e145c8f0af2', 'event_time', $time, $post_id );
		}

		if ( $phone ) {
			self::update_acf_field( 'field_69e1565162364', 'event_phone', $phone, $post_id );
		}

		if ( $email ) {
			self::update_acf_field( 'field_69e1557c62363', 'event_email', $email, $post_id );
		}
	}

	/**
	 * Sanitize parsed multi-day schedule data before storing it.
	 *
	 * @param array  $schedule Parsed schedule rows
	 * @param string $fallback_date Primary date
	 * @param string $fallback_time Primary time
	 *
	 * @return array
	 */
	private static function sanitize_schedule( $schedule, $fallback_date = '', $fallback_time = '' ) {
		$clean = array();

		foreach ( $schedule as $term ) {
			if ( ! is_array( $term ) || empty( $term['date'] ) ) {
				continue;
			}

			$date = preg_replace( '/\D+/', '', (string) $term['date'] );
			if ( 8 !== strlen( $date ) ) {
				continue;
			}

			$key = $date . '|' . sanitize_text_field( $term['time'] ?? $fallback_time );
			$clean[ $key ] = array(
				'date' => $date,
				'time' => sanitize_text_field( $term['time'] ?? $fallback_time ),
			);
		}

		if ( empty( $clean ) && ! empty( $fallback_date ) ) {
			$clean[ $fallback_date . '|' . $fallback_time ] = array(
				'date' => preg_replace( '/\D+/', '', (string) $fallback_date ),
				'time' => sanitize_text_field( $fallback_time ),
			);
		}

		$clean = array_values( $clean );
		usort(
			$clean,
			function ( $a, $b ) {
				return strcmp( $a['date'], $b['date'] );
			}
		);

		return $clean;
	}

	/**
	 * Prepare clean WYSIWYG content for the ACF about_event field.
	 *
	 * @param array $event Event data
	 * @param string $source_url Source URL
	 *
	 * @return string HTML content
	 */
	private static function prepare_about_event( $event, $source_url ) {
		$content = '';

		if ( ! empty( $event['description'] ) ) {
			$content .= '<p>' . wp_kses_post( $event['description'] ) . '</p>';
		} elseif ( ! empty( $event['text'] ) ) {
			$content .= '<p>' . esc_html( $event['text'] ) . '</p>';
		}

		return $content;
	}

	/**
	 * Detect whether the scraped event link looks like a ticket, booking, or registration URL.
	 *
	 * @param array $event Event data
	 *
	 * @return bool
	 */
	private static function is_ticket_link( $event ) {
		$haystack = implode(
			' ',
			array_filter(
				array(
					$event['title'] ?? '',
					$event['description'] ?? '',
					$event['text'] ?? '',
					$event['href'] ?? '',
				)
			)
		);

		if ( empty( $haystack ) ) {
			return false;
		}

		return (bool) preg_match(
			'/\b(ticket|tickets|anmeldung|anmelden|registrierung|registrieren|registration|booking|buchen|buchung|reservierung|reservieren|teilnahme|eventbrite|oeticket|shop)\b/iu',
			$haystack
		);
	}

	/**
	 * Update an ACF field when ACF is available; otherwise update post meta.
	 *
	 * @param string $field_key ACF field key
	 * @param string $field_name ACF field name
	 * @param mixed $value Field value
	 * @param int $post_id Post ID
	 */
	private static function update_acf_field( $field_key, $field_name, $value, $post_id ) {
		if ( function_exists( 'update_field' ) ) {
			update_field( $field_key, $value, $post_id );
			return;
		}

		update_post_meta( $post_id, $field_name, $value );
	}

	/**
	 * Download a scraped image and set it as the event featured image.
	 *
	 * @param int    $post_id Post ID
	 * @param array  $event Event data
	 * @param string $source_url Source URL
	 *
	 * @return string Imported image URL or an empty string when rejected
	 */
	private static function import_featured_image( $post_id, $event, $source_url ) {
		$image_url = esc_url_raw( $event['image_url'] ?? '' );
		if ( empty( $image_url ) || ! preg_match( '/^https?:\/\//i', $image_url ) ) {
			return '';
		}

		if ( ! function_exists( 'download_url' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		if ( ! function_exists( 'media_handle_sideload' ) ) {
			require_once ABSPATH . 'wp-admin/includes/media.php';
			require_once ABSPATH . 'wp-admin/includes/image.php';
		}

		$tmp = download_url( $image_url, 15 );
		if ( is_wp_error( $tmp ) ) {
			return '';
		}

		if ( ! self::is_usable_downloaded_image( $tmp ) ) {
			@unlink( $tmp );
			return '';
		}

		$file_name = self::image_file_name_from_url( $image_url, $event['title'] ?? 'event-image' );
		$file = array(
			'name' => $file_name,
			'tmp_name' => $tmp,
		);

		$attachment_id = media_handle_sideload( $file, $post_id, sanitize_text_field( $event['title'] ?? '' ) );

		if ( is_wp_error( $attachment_id ) ) {
			@unlink( $tmp );
			return '';
		}

		set_post_thumbnail( $post_id, $attachment_id );

		$credit_url = self::image_credit_url( $event, $source_url );
		update_post_meta( $post_id, '_em_image_credit_url', esc_url_raw( $credit_url ) );
		update_post_meta( $attachment_id, '_em_source_url', esc_url_raw( $credit_url ) );
		update_post_meta( $attachment_id, '_em_source_image_url', esc_url_raw( $image_url ) );

		return $image_url;
	}

	/**
	 * Assign one configured fallback image as the featured image.
	 *
	 * @param int $post_id Post ID
	 *
	 * @return string Attachment URL or an empty string
	 */
	private static function assign_random_fallback_image( $post_id ) {
		$image_ids = get_option( 'wem_fallback_image_ids', array() );
		if ( is_string( $image_ids ) ) {
			$image_ids = preg_split( '/[\s,]+/', $image_ids );
		}

		if ( ! is_array( $image_ids ) ) {
			return '';
		}

		$valid_ids = array();
		foreach ( $image_ids as $image_id ) {
			$image_id = absint( $image_id );
			if ( $image_id && wp_attachment_is_image( $image_id ) ) {
				$valid_ids[] = $image_id;
			}
		}

		if ( empty( $valid_ids ) ) {
			return '';
		}

		$attachment_id = $valid_ids[ array_rand( $valid_ids ) ];
		$image_url = wp_get_attachment_url( $attachment_id );
		if ( empty( $image_url ) ) {
			return '';
		}

		set_post_thumbnail( $post_id, $attachment_id );
		update_post_meta( $post_id, '_wem_fallback_image_id', $attachment_id );
		delete_post_meta( $post_id, '_em_image_credit_url' );

		return esc_url_raw( $image_url );
	}

	/**
	 * Reject non-images, tiny assets, and blank white screenshots before import.
	 *
	 * @param string $file_path Downloaded temp file path
	 *
	 * @return bool
	 */
	private static function is_usable_downloaded_image( $file_path ) {
		$image_size = @getimagesize( $file_path );
		if ( empty( $image_size[0] ) || empty( $image_size[1] ) ) {
			return false;
		}

		$width = (int) $image_size[0];
		$height = (int) $image_size[1];
		if ( $width < 240 || $height < 120 ) {
			return false;
		}

		if ( ! function_exists( 'imagecreatefromstring' ) || ! function_exists( 'imagecolorat' ) ) {
			return true;
		}

		$bytes = @file_get_contents( $file_path );
		if ( false === $bytes ) {
			return true;
		}

		$image = @imagecreatefromstring( $bytes );
		if ( ! $image ) {
			return true;
		}

		$sample_count = 0;
		$white_count = 0;
		$luminance_total = 0;
		$luminance_values = array();
		$steps_x = min( 8, max( 3, (int) floor( $width / 120 ) ) );
		$steps_y = min( 8, max( 3, (int) floor( $height / 90 ) ) );

		for ( $y = 0; $y < $steps_y; $y++ ) {
			for ( $x = 0; $x < $steps_x; $x++ ) {
				$pixel_x = (int) round( ( $width - 1 ) * ( $x + 0.5 ) / $steps_x );
				$pixel_y = (int) round( ( $height - 1 ) * ( $y + 0.5 ) / $steps_y );
				$color = imagecolorat( $image, $pixel_x, $pixel_y );
				$red = ( $color >> 16 ) & 0xFF;
				$green = ( $color >> 8 ) & 0xFF;
				$blue = $color & 0xFF;
				$luminance = ( 0.2126 * $red ) + ( 0.7152 * $green ) + ( 0.0722 * $blue );

				$sample_count++;
				$luminance_total += $luminance;
				$luminance_values[] = $luminance;

				if ( $red >= 245 && $green >= 245 && $blue >= 245 ) {
					$white_count++;
				}
			}
		}

		if ( function_exists( 'imagedestroy' ) ) {
			imagedestroy( $image );
		}

		if ( 0 === $sample_count ) {
			return true;
		}

		$average_luminance = $luminance_total / $sample_count;
		$variance_total = 0;
		foreach ( $luminance_values as $luminance ) {
			$variance_total += pow( $luminance - $average_luminance, 2 );
		}
		$variance = $variance_total / $sample_count;

		return ! ( $average_luminance >= 245 && $variance < 20 && ( $white_count / $sample_count ) >= 0.9 );
	}

	/**
	 * Build a safe file name for a sideloaded event image.
	 *
	 * @param string $image_url Image URL
	 * @param string $title Event title
	 *
	 * @return string File name
	 */
	private static function image_file_name_from_url( $image_url, $title ) {
		$path = wp_parse_url( $image_url, PHP_URL_PATH );
		$file_name = $path ? wp_basename( $path ) : '';

		if ( empty( $file_name ) || strpos( $file_name, '.' ) === false ) {
			$file_name = sanitize_title( $title ) . '.jpg';
		}

		return sanitize_file_name( $file_name );
	}

	/**
	 * Prefer the event detail page as the human-readable image source.
	 *
	 * @param array  $event Event data
	 * @param string $source_url Source URL
	 *
	 * @return string Credit URL
	 */
	private static function image_credit_url( $event, $source_url ) {
		if ( ! empty( $event['href'] ) ) {
			return $event['href'];
		}

		return ! empty( $source_url ) ? $source_url : ( $event['image_url'] ?? '' );
	}

	/**
	 * Create or reuse terms by name, then assign them to the event.
	 *
	 * @param int $post_id Post ID
	 * @param array $terms Term names
	 * @param string $taxonomy Taxonomy name
	 */
	private static function set_terms_by_name( $post_id, $terms, $taxonomy ) {
		$term_ids = array();

		foreach ( $terms as $term_name ) {
			$term_name = sanitize_text_field( $term_name );
			if ( empty( $term_name ) ) {
				continue;
			}

			$term = term_exists( $term_name, $taxonomy );
			if ( ! $term ) {
				$term = wp_insert_term( $term_name, $taxonomy );
			}

			if ( is_wp_error( $term ) ) {
				continue;
			}

			$term_id = is_array( $term ) ? (int) $term['term_id'] : (int) $term;
			if ( $term_id ) {
				$term_ids[] = $term_id;
			}
		}

		if ( ! empty( $term_ids ) ) {
			wp_set_post_terms( $post_id, $term_ids, $taxonomy );
		}
	}

	/**
	 * Prepare matched keywords as taxonomy terms.
	 *
	 * @param array $matched_keywords Array of matched keywords
	 *
	 * @return array Array of term names
	 */
	private static function prepare_tags( $matched_keywords ) {
		if ( empty( $matched_keywords ) ) {
			return array();
		}

		$tags = array();

		foreach ( $matched_keywords as $keyword ) {
			$tags[] = sanitize_text_field( $keyword );
		}

		return array_unique( $tags );
	}
}
