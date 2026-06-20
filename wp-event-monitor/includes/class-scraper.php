<?php
/**
 * WEM_Scraper
 *
 * Handles URL fetching and HTML parsing with DOMDocument
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WEM_Scraper {

	/**
	 * Scrape a URL and extract event elements
	 *
	 * @param string $url URL to scrape
	 * @param string $css_selector Optional CSS selector to target specific elements
	 *
	 * @return array Array of events, each with 'title', 'text', 'href', 'html'
	 */
	public static function scrape( $url, $css_selector = '', $parser_mode = 'auto', $field_selectors = array() ) {
		if ( preg_match( '/\.pdf(?:$|\?)/i', $url ) ) {
			return array(
				'error' => 'PDF sources are not supported yet',
				'events' => array(),
			);
		}

		// Fetch the URL
		$response = wp_remote_get( $url, array(
			'timeout' => 15,
			'user-agent' => 'Mozilla/5.0 (WordPress Event Monitor)',
		) );

		if ( is_wp_error( $response ) ) {
			return array(
				'error' => $response->get_error_message(),
				'events' => array(),
			);
		}

		$body = wp_remote_retrieve_body( $response );
		if ( empty( $body ) ) {
			return array(
				'error' => 'Empty response body',
				'events' => array(),
			);
		}

		$content_type = wp_remote_retrieve_header( $response, 'content-type' );
		if ( is_string( $content_type ) && stripos( $content_type, 'application/pdf' ) !== false ) {
			return array(
				'error' => 'PDF sources are not supported yet',
				'events' => array(),
			);
		}

		// Parse HTML
		$events = self::parse_html( $body, $css_selector, $url, $parser_mode, $field_selectors );

		return array(
			'error' => null,
			'events' => $events,
		);
	}

	/**
	 * Parse HTML and extract events
	 *
	 * @param string $html HTML content
	 * @param string $css_selector Optional CSS selector
	 *
	 * @return array Events array
	 */
	private static function parse_html( $html, $css_selector = '', $source_url = '', $parser_mode = 'auto', $field_selectors = array() ) {
		$events = array();
		$parser_mode = self::sanitize_parser_mode( $parser_mode );
		$field_selectors = self::sanitize_field_selectors( $field_selectors );

		// Suppress warnings from DOMDocument
		libxml_use_internal_errors( true );

		$dom = new DOMDocument();

		// Force UTF-8 encoding
		$html_with_charset = '<?xml encoding="UTF-8">' . $html;
		$dom->loadHTML( $html_with_charset );

		libxml_clear_errors();

		$xpath = new DOMXPath( $dom );
		$page_image_url = self::extract_meta_image_url( $xpath, $source_url );

		// If CSS selector provided, convert to XPath
		if ( 'structured' === $parser_mode ) {
			$elements = false;
		} elseif ( ! empty( $css_selector ) ) {
			$xpath_expr = self::css_to_xpath( $css_selector );
			$elements = $xpath->query( $xpath_expr );
		} else {
			// Default: only scan containers that look like event/news/calendar entries.
			$elements = $xpath->query(
				'//article |
				//li[@class or @id] |
				//a[
					@href and (
						contains(translate(concat(" ", @class, " ", @id, " ", @title, " ", @aria-label), "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz"), " detail") or
						contains(translate(concat(" ", @class, " ", @id, " ", @title, " ", @aria-label), "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz"), " event") or
						contains(translate(concat(" ", @class, " ", @id, " ", @title, " ", @aria-label), "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz"), " termin") or
						contains(translate(concat(" ", @class, " ", @id, " ", @title, " ", @aria-label), "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz"), " veranstaltung") or
						contains(translate(concat(" ", @class, " ", @id, " ", @title, " ", @aria-label), "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz"), " kurs") or
						contains(translate(concat(" ", @class, " ", @id, " ", @title, " ", @aria-label), "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz"), " seminar")
					)
				] |
				//*[self::div or self::section or self::article][
					contains(translate(concat(" ", @class, " ", @id, " ", @data-testid, " "), "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz"), " event") or
					contains(translate(concat(" ", @class, " ", @id, " ", @data-testid, " "), "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz"), " termin") or
					contains(translate(concat(" ", @class, " ", @id, " ", @data-testid, " "), "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz"), " veranstaltung") or
					contains(translate(concat(" ", @class, " ", @id, " ", @data-testid, " "), "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz"), " calendar") or
					contains(translate(concat(" ", @class, " ", @id, " ", @data-testid, " "), "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz"), " agenda") or
					contains(translate(concat(" ", @class, " ", @id, " ", @data-testid, " "), "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz"), " listing") or
					contains(translate(concat(" ", @class, " ", @id, " ", @data-testid, " "), "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz"), " panel-list") or
					contains(translate(concat(" ", @class, " ", @id, " ", @data-testid, " "), "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz"), " card") or
					contains(translate(concat(" ", @class, " ", @id, " ", @data-testid, " "), "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz"), " item") or
					.//*[contains(translate(concat(" ", @class, " ", @id, " "), "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz"), " date")] or
					.//*[contains(translate(concat(" ", @class, " ", @id, " "), "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz"), " time")]
				]'
			);
		}

		if ( $elements ) {
			foreach ( $elements as $element ) {
				$event = self::event_from_element( $dom, $element, $source_url, $field_selectors, $page_image_url );
				if ( empty( $event ) ) {
					continue;
				}

				$events[] = $event;
			}
		}

		if ( 'auto' === $parser_mode && empty( $css_selector ) ) {
			$events = array_merge( $events, self::extract_semantic_event_candidates( $dom, $source_url, $field_selectors, $page_image_url ) );
		}

		if ( 'html' !== $parser_mode ) {
			$events = array_merge( $events, self::extract_structured_events( $dom, $source_url ) );
		}

		return self::dedupe_events( $events );
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
	 * Sanitize optional field selectors.
	 *
	 * @param array $field_selectors Raw selector map
	 *
	 * @return array Safe selector map
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
	 * Build an event array from a DOM element when it passes validation.
	 *
	 * @param DOMDocument $dom DOM document
	 * @param DOMElement  $element Candidate element
	 * @param string      $source_url Source page URL
	 *
	 * @return array Event data or empty array
	 */
	private static function event_from_element( $dom, $element, $source_url, $field_selectors = array(), $page_image_url = '' ) {
		$text = self::normalize_text( $element->textContent );
		$title = self::extract_text_by_selector( $element, $field_selectors['title_selector'] ?? '' );
		if ( empty( $title ) ) {
			$title = self::extract_title( $element );
		}

		$href = self::extract_href_by_selector( $element, $field_selectors['link_selector'] ?? '', $source_url );
		if ( empty( $href ) ) {
			$href = self::extract_href( $element, $source_url );
		}
		$class = $element->getAttribute( 'class' );
		$id = $element->getAttribute( 'id' );
		$description = self::extract_text_by_selector( $element, $field_selectors['description_selector'] ?? '' );
		if ( empty( $description ) ) {
			$description = self::extract_description( $dom, $element, $title );
		}
		$date_text = self::extract_text_by_selector( $element, $field_selectors['date_selector'] ?? '' );
		$time_text = self::extract_text_by_selector( $element, $field_selectors['time_selector'] ?? '' );
		$datetime_text = self::extract_datetime_text( $element );
		$date_source = trim( $date_text . ' ' . $datetime_text . ' ' . $text );
		$time_source = trim( $time_text . ' ' . $datetime_text . ' ' . $text );
		$date = self::extract_date( $date_source );
		$time = self::extract_time( $time_source );

		if ( ! self::is_valid_event_candidate( $element, $title, trim( $text . ' ' . $datetime_text ), $href, $description ) ) {
			return array();
		}

		if ( strlen( $title ) > 180 ) {
			$title = substr( $title, 0, 180 );
		}

		return array(
			'title' => $title,
			'href' => $href,
			'class' => $class,
			'id' => $id,
			'description' => $description,
			'image_url' => self::extract_image_url( $element, $source_url, $page_image_url ),
			'text' => $text,
			'date' => $date,
			'time' => $time,
			'schedule' => self::extract_schedule( $date_source, $time ),
			'location' => self::extract_location( $text ),
			'email' => self::extract_email( $text ),
			'phone' => self::extract_phone( $text ),
			'html' => $element->ownerDocument->saveHTML( $element ),
		);
	}

	/**
	 * Find events by looking for headings/links that sit near a date.
	 *
	 * @param DOMDocument $dom DOM document
	 * @param string      $source_url Source page URL
	 *
	 * @return array Events array
	 */
	private static function extract_semantic_event_candidates( $dom, $source_url = '', $field_selectors = array(), $page_image_url = '' ) {
		$events = array();
		$xpath = new DOMXPath( $dom );
		$candidates = $xpath->query(
			'//a[@href] | //h2 | //h3 | //h4 | //h5 | //h6 |
			//*[contains(translate(concat(" ", @class, " "), "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz"), " title ")] |
			//*[contains(translate(concat(" ", @class, " "), "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz"), " headline ")]'
		);

		if ( ! $candidates ) {
			return $events;
		}

		foreach ( $candidates as $candidate ) {
			if ( ! $candidate instanceof DOMElement ) {
				continue;
			}

			$title = self::normalize_text( $candidate->textContent );
			if ( strlen( $title ) < 5 || strlen( $title ) > 180 || self::is_ignored_title( $title ) ) {
				continue;
			}

			$container = self::find_nearest_event_container( $candidate );
			if ( ! $container ) {
				continue;
			}

			$event = self::event_from_element( $dom, $container, $source_url, $field_selectors, $page_image_url );
			if ( empty( $event ) ) {
				continue;
			}

			if ( strlen( $event['title'] ) > strlen( $title ) && ! self::is_ignored_title( $title ) ) {
				$event['title'] = $title;
			}

			$events[] = $event;
		}

		return $events;
	}

	/**
	 * Find the nearest ancestor that looks like one event item.
	 *
	 * @param DOMElement $candidate Candidate heading/link
	 *
	 * @return DOMElement|null Event container
	 */
	private static function find_nearest_event_container( $candidate ) {
		$node = $candidate;
		$steps = 0;
		$best = null;

		while ( $node instanceof DOMElement && $steps < 6 ) {
			if ( self::has_ignored_ancestor( $node ) ) {
				return null;
			}

			if ( self::is_page_level_container( $node ) ) {
				break;
			}

			$text = self::normalize_text( $node->textContent );
			if ( strlen( $text ) >= 20 && strlen( $text ) <= 1200 && ( self::extract_date( $text ) || self::extract_time( $text ) ) && self::has_event_signal( $text ) ) {
				if ( ! self::looks_like_event_collection( self::normalize_text( $candidate->textContent ), $text ) ) {
					$best = $node;
				}
			}

			$node = $node->parentNode;
			$steps++;
		}

		return $best;
	}

	/**
	 * Extract text from the first descendant matching a selector.
	 *
	 * @param DOMElement $element Parent element
	 * @param string     $selector CSS-like selector
	 *
	 * @return string Text content
	 */
	private static function extract_text_by_selector( $element, $selector ) {
		$node = self::query_first_by_selector( $element, $selector );

		return $node ? self::normalize_text( $node->textContent ) : '';
	}

	/**
	 * Extract href from the first descendant matching a selector.
	 *
	 * @param DOMElement $element Parent element
	 * @param string     $selector CSS-like selector
	 * @param string     $source_url Source page URL
	 *
	 * @return string Absolute URL when possible
	 */
	private static function extract_href_by_selector( $element, $selector, $source_url ) {
		$node = self::query_first_by_selector( $element, $selector );
		if ( ! $node ) {
			return '';
		}

		if ( $node->hasAttribute( 'href' ) ) {
			return self::make_absolute_url( $node->getAttribute( 'href' ), $source_url );
		}

		$links = $node->getElementsByTagName( 'a' );
		if ( $links->length > 0 ) {
			return self::make_absolute_url( $links->item( 0 )->getAttribute( 'href' ), $source_url );
		}

		return '';
	}

	/**
	 * Query the first descendant matching a CSS-like selector.
	 *
	 * @param DOMElement $element Parent element
	 * @param string     $selector CSS-like selector
	 *
	 * @return DOMElement|null Matching element
	 */
	private static function query_first_by_selector( $element, $selector ) {
		$selector = trim( (string) $selector );
		if ( empty( $selector ) ) {
			return null;
		}

		$xpath_expr = self::css_to_xpath( $selector );
		$xpath_expr = preg_replace( '/(^|\|\s*)\/\//', '$1.//', $xpath_expr );
		$xpath = new DOMXPath( $element->ownerDocument );
		$nodes = $xpath->query( $xpath_expr, $element );

		if ( $nodes && $nodes->length > 0 && $nodes->item( 0 ) instanceof DOMElement ) {
			return $nodes->item( 0 );
		}

		return null;
	}

	/**
	 * Convert CSS selector to XPath expression
	 *
	 * Simple conversion - handles basic selectors like .class, #id, tag.class, etc.
	 *
	 * @param string $css_selector CSS selector
	 *
	 * @return string XPath expression
	 */
	private static function css_to_xpath( $css_selector ) {
		$css_selector = trim( (string) $css_selector );

		if ( strpos( $css_selector, ',' ) !== false ) {
			$selectors = array_filter( array_map( 'trim', explode( ',', $css_selector ) ) );
			$xpath_parts = array();

			foreach ( $selectors as $selector ) {
				$xpath_parts[] = self::css_to_xpath( $selector );
			}

			return implode( ' | ', $xpath_parts );
		}

		if ( strpos( $css_selector, ' ' ) !== false ) {
			$parts = preg_split( '/\s+/', $css_selector );
			$xpath_parts = array();

			foreach ( $parts as $part ) {
				$part = trim( $part );
				if ( empty( $part ) ) {
					continue;
				}

				$xpath_parts[] = ltrim( self::css_to_xpath( $part ), '/' );
			}

			if ( ! empty( $xpath_parts ) ) {
				return '//' . implode( '//', $xpath_parts );
			}
		}

		// Handle class selector (.classname)
		if ( strpos( $css_selector, '.' ) === 0 ) {
			$class = substr( $css_selector, 1 );
			return "//*[contains(concat(' ', normalize-space(@class), ' '), ' " . addslashes( $class ) . " ')]";
		}

		// Handle ID selector (#id)
		if ( strpos( $css_selector, '#' ) === 0 ) {
			$id = substr( $css_selector, 1 );
			return "//*[@id='" . addslashes( $id ) . "']";
		}

		// Handle simple attribute selectors, e.g. a[title="Details"] or [data-event].
		if ( preg_match( '/^([a-z0-9_-]*)?\[([a-z0-9_:-]+)(?:=["\']?([^"\']+)["\']?)?\]$/i', $css_selector, $matches ) ) {
			$tag = ! empty( $matches[1] ) ? $matches[1] : '*';
			$attribute = $matches[2];
			if ( isset( $matches[3] ) && $matches[3] !== '' ) {
				return "//{$tag}[@{$attribute}='" . addslashes( $matches[3] ) . "']";
			}

			return "//{$tag}[@{$attribute}]";
		}

		// Handle tag.class
		if ( strpos( $css_selector, '.' ) !== false ) {
			$parts = explode( '.', $css_selector );
			$tag = $parts[0];
			$class = $parts[1];
			if ( empty( $tag ) ) {
				$tag = '*';
			}
			return "//{$tag}[contains(concat(' ', normalize-space(@class), ' '), ' " . addslashes( $class ) . " ')]";
		}

		// Default: just the tag name
		return "//" . str_replace( ' ', '//', $css_selector );
	}

	/**
	 * Extract schema.org Event entries from JSON-LD blocks.
	 *
	 * @param DOMDocument $dom DOM document
	 * @param string      $source_url Source page URL
	 *
	 * @return array Events array
	 */
	private static function extract_structured_events( $dom, $source_url = '' ) {
		$events = array();
		$xpath = new DOMXPath( $dom );
		$scripts = $xpath->query( '//script[contains(translate(@type, "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz"), "ld+json")]' );

		if ( ! $scripts ) {
			return $events;
		}

		foreach ( $scripts as $script ) {
			$json = trim( $script->textContent );
			if ( empty( $json ) ) {
				continue;
			}

			$data = json_decode( $json, true );
			if ( null === $data ) {
				continue;
			}

			foreach ( self::find_structured_event_nodes( $data ) as $node ) {
				$title = self::normalize_text( self::array_get_first( $node, array( 'name', 'headline', 'title' ) ) );
				if ( empty( $title ) || self::is_ignored_title( $title ) ) {
					continue;
				}

				$description = self::normalize_text( self::array_get_first( $node, array( 'description', 'summary' ) ) );
				$start_date = self::array_get_first( $node, array( 'startDate', 'start_date', 'date' ) );
				$end_date = self::array_get_first( $node, array( 'endDate', 'end_date' ) );
				$url = self::array_get_first( $node, array( 'url', '@id' ) );
				$image_url = self::array_get_image_url( $node );
				$location = self::array_get_location( $node );
				$text = trim( $title . ' ' . $description . ' ' . $start_date . ' ' . $end_date );

				$events[] = array(
					'title' => $title,
					'href' => self::make_absolute_url( $url, $source_url ),
					'class' => 'json-ld',
					'id' => '',
					'description' => $description,
					'image_url' => self::make_absolute_url( $image_url, $source_url ),
					'text' => $text,
					'date' => self::extract_date( $start_date . ' ' . $text ),
					'time' => self::extract_time( $start_date . ' ' . $text ),
					'schedule' => self::extract_schedule( trim( $start_date . ' ' . $end_date . ' ' . $text ), self::extract_time( $start_date . ' ' . $text ) ),
					'location' => $location,
					'email' => self::extract_email( $text ),
					'phone' => self::extract_phone( $text ),
					'html' => '',
				);
			}
		}

		return $events;
	}

	/**
	 * Recursively find Event-like nodes in structured data.
	 *
	 * @param mixed $data Structured data node
	 *
	 * @return array Matching nodes
	 */
	private static function find_structured_event_nodes( $data ) {
		$nodes = array();

		if ( ! is_array( $data ) ) {
			return $nodes;
		}

		$type = isset( $data['@type'] ) ? $data['@type'] : '';
		$types = is_array( $type ) ? $type : array( $type );
		$is_event = false;

		foreach ( $types as $candidate_type ) {
			if ( strtolower( (string) $candidate_type ) === 'event' ) {
				$is_event = true;
				break;
			}
		}

		if ( $is_event || ( isset( $data['name'], $data['startDate'] ) && self::has_event_signal( wp_json_encode( $data ) ) ) ) {
			$nodes[] = $data;
		}

		foreach ( $data as $value ) {
			if ( is_array( $value ) ) {
				$nodes = array_merge( $nodes, self::find_structured_event_nodes( $value ) );
			}
		}

		return $nodes;
	}

	/**
	 * Get the first non-empty scalar value from an array.
	 *
	 * @param array $data Source array
	 * @param array $keys Candidate keys
	 *
	 * @return string Found value
	 */
	private static function array_get_first( $data, $keys ) {
		foreach ( $keys as $key ) {
			if ( ! isset( $data[ $key ] ) ) {
				continue;
			}

			$value = $data[ $key ];
			if ( is_array( $value ) ) {
				if ( isset( $value['@id'] ) ) {
					$value = $value['@id'];
				} elseif ( isset( $value['name'] ) ) {
					$value = $value['name'];
				} else {
					continue;
				}
			}

			$value = trim( (string) $value );
			if ( '' !== $value ) {
				return $value;
			}
		}

		return '';
	}

	/**
	 * Extract an image URL from JSON-LD image data.
	 *
	 * @param array $data Structured event data
	 *
	 * @return string Image URL
	 */
	private static function array_get_image_url( $data ) {
		if ( empty( $data['image'] ) ) {
			return '';
		}

		$image = $data['image'];

		if ( is_string( $image ) ) {
			return trim( $image );
		}

		if ( isset( $image['url'] ) && is_string( $image['url'] ) ) {
			return trim( $image['url'] );
		}

		if ( isset( $image['@id'] ) && is_string( $image['@id'] ) ) {
			return trim( $image['@id'] );
		}

		if ( is_array( $image ) ) {
			foreach ( $image as $candidate ) {
				if ( is_string( $candidate ) ) {
					return trim( $candidate );
				}

				if ( is_array( $candidate ) && ! empty( $candidate['url'] ) && is_string( $candidate['url'] ) ) {
					return trim( $candidate['url'] );
				}
			}
		}

		return '';
	}

	/**
	 * Extract a location string from structured event data.
	 *
	 * @param array $data Structured event data
	 *
	 * @return string
	 */
	private static function array_get_location( $data ) {
		if ( empty( $data['location'] ) ) {
			return '';
		}

		$location = $data['location'];

		if ( is_string( $location ) ) {
			return self::normalize_location( $location );
		}

		if ( ! is_array( $location ) ) {
			return '';
		}

		$parts = array();
		if ( ! empty( $location['name'] ) && is_string( $location['name'] ) ) {
			$parts[] = $location['name'];
		}

		$address = $location['address'] ?? '';
		if ( is_string( $address ) ) {
			$parts[] = $address;
		} elseif ( is_array( $address ) ) {
			foreach ( array( 'streetAddress', 'addressLocality', 'postalCode', 'addressRegion', 'addressCountry' ) as $key ) {
				if ( ! empty( $address[ $key ] ) && is_string( $address[ $key ] ) ) {
					$parts[] = $address[ $key ];
				}
			}
		}

		return self::normalize_location( implode( ', ', array_unique( array_filter( $parts ) ) ) );
	}

	/**
	 * Remove duplicate events from mixed HTML and structured-data sources.
	 *
	 * @param array $events Events array
	 *
	 * @return array De-duplicated events
	 */
	private static function dedupe_events( $events ) {
		$seen = array();
		$deduped = array();

		foreach ( $events as $event ) {
			$key = self::generate_hash( $event['title'] ?? '', $event['href'] ?? '', $event['date'] ?? '' );
			if ( isset( $seen[ $key ] ) ) {
				continue;
			}

			$seen[ $key ] = true;
			$deduped[] = $event;
		}

		return $deduped;
	}

	/**
	 * Extract description from element or page meta
	 *
	 * @param DOMDocument $dom DOM document
	 * @param DOMElement $element Current element
	 *
	 * @return string Description text
	 */
	private static function extract_description( $dom, $element, $title = '' ) {
		$text = self::normalize_text( $element->textContent );

		if ( ! empty( $title ) && strpos( $text, $title ) === 0 ) {
			$text = trim( substr( $text, strlen( $title ) ) );
		}

		if ( strlen( $text ) > 1200 ) {
			$text = substr( $text, 0, 1200 ) . '...';
		}

		return $text;
	}

	/**
	 * Check whether a parsed element is likely to be a real event.
	 *
	 * @param DOMElement $element Current element
	 * @param string $title Event title
	 * @param string $text Full element text
	 * @param string $href Link URL
	 * @param string $description Event description
	 *
	 * @return bool True when the candidate looks useful
	 */
	private static function is_valid_event_candidate( $element, $title, $text, $href, $description ) {
		$title = self::normalize_text( $title );
		$text = self::normalize_text( $text );
		$description = self::normalize_text( $description );

		if ( strlen( $title ) < 5 || strlen( $title ) > 180 ) {
			return false;
		}

		if ( self::has_ignored_ancestor( $element ) || self::is_ignored_title( $title ) || self::is_ignored_href( $href ) ) {
			return false;
		}

		if ( strlen( $description ) < 15 && empty( $href ) ) {
			return false;
		}

		if ( self::is_page_level_container( $element ) ) {
			return false;
		}

		if ( self::looks_like_event_collection( $title, $text ) ) {
			return false;
		}

		if ( ! self::has_event_signal( $title . ' ' . $text . ' ' . $href . ' ' . $description ) ) {
			return false;
		}

		if ( ! self::extract_date( $text ) && ! self::extract_time( $text ) ) {
			$has_contextual_title = preg_match( '/[:\-–]|fortbildung|workshop|seminar|kurs|camp|turnier|finals|laufende trainings/iu', $title );
			if ( strlen( $description ) < 80 && ! $has_contextual_title ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Check whether a candidate is a whole event list/month section instead of one event.
	 *
	 * @param string $title Candidate title
	 * @param string $text Candidate text
	 *
	 * @return bool True when this is too broad
	 */
	private static function looks_like_event_collection( $title, $text ) {
		$text = self::normalize_text( $text );
		$title = self::normalize_text( $title );

		if ( preg_match( '/zukünftige events|zukunftige events|vergangene events|past events|upcoming events|toggle navigation|breadcrumb|hauptnavigation/iu', $title . ' ' . $text ) ) {
			return true;
		}

		$date_count = preg_match_all( '/\b\d{1,2}[.\/-]\d{1,2}(?:[.\/-]\d{2,4})?\b|\b\d{1,2}\.?\s*(?:januar|februar|märz|maerz|april|mai|juni|juli|august|september|oktober|november|dezember|jan|feb|mar|apr|jun|jul|aug|sep|okt|oct|nov|dez|dec)\.?\s*\d{4}\b/iu', $text );
		$linkish_count = preg_match_all( '/ausschreibung|anzeigen|details|mehr erfahren|weiterlesen/iu', $text );

		return ( strlen( $text ) > 550 && $date_count >= 3 && $linkish_count >= 2 ) || ( strlen( $text ) > 1000 && $date_count >= 2 );
	}

	/**
	 * Check whether an element is too broad to represent a single event.
	 *
	 * @param DOMElement $element Candidate element
	 *
	 * @return bool True when the element is page-level content
	 */
	private static function is_page_level_container( $element ) {
		$tag = strtolower( $element->tagName );
		if ( in_array( $tag, array( 'html', 'body', 'main' ), true ) ) {
			return true;
		}

		$role = strtolower( $element->getAttribute( 'role' ) );
		if ( 'main' === $role ) {
			return true;
		}

		$tokens = strtolower( $element->getAttribute( 'class' ) . ' ' . $element->getAttribute( 'id' ) );
		foreach ( array( 'content', 'page', 'main', 'wrapper', 'container' ) as $token ) {
			if ( preg_match( '/(^|[\s_-])' . preg_quote( $token, '/' ) . '($|[\s_-])/', $tokens ) ) {
				$text = self::normalize_text( $element->textContent );
				return strlen( $text ) > 1000;
			}
		}

		return false;
	}

	/**
	 * Check whether an element is inside a navigation, header, footer, or sidebar.
	 *
	 * @param DOMElement $element Current element
	 *
	 * @return bool True when the ancestor should be ignored
	 */
	private static function has_ignored_ancestor( $element ) {
		$ignored_tags = array( 'header', 'footer', 'nav', 'aside' );
		$ignored_tokens = array(
			'menu',
			'nav',
			'navbar',
			'header',
			'footer',
			'sidebar',
			'breadcrumb',
			'cookie',
			'popup',
			'modal',
			'search',
			'social',
			'language',
			'logo',
		);

		$node = $element;
		while ( $node instanceof DOMElement ) {
			$tag = strtolower( $node->tagName );
			if ( in_array( $tag, $ignored_tags, true ) ) {
				return true;
			}

			$role = strtolower( $node->getAttribute( 'role' ) );
			if ( in_array( $role, array( 'navigation', 'banner', 'contentinfo', 'search', 'complementary' ), true ) ) {
				return true;
			}

			if ( in_array( $tag, array( 'html', 'body', 'main' ), true ) ) {
				$node = $node->parentNode;
				continue;
			}

			$tokens = strtolower( $node->getAttribute( 'class' ) . ' ' . $node->getAttribute( 'id' ) );
			foreach ( $ignored_tokens as $token ) {
				if ( strpos( $tokens, $token ) !== false ) {
					return true;
				}
			}

			$node = $node->parentNode;
		}

		return false;
	}

	/**
	 * Check for titles that are obviously pages, sections, or navigation labels.
	 *
	 * @param string $title Event title
	 *
	 * @return bool True when the title should be ignored
	 */
	private static function is_ignored_title( $title ) {
		$normalized = strtolower( trim( preg_replace( '/\s+/u', ' ', $title ) ) );
		$normalized = trim( $normalized, ". \t\n\r\0\x0B" );

		if ( preg_match( '/^https?:\/\//i', $normalized ) || is_email( $normalized ) ) {
			return true;
		}

		$exact = array(
			'details',
			'detail',
			'mehr',
			'mehr...',
			'mehr…',
			'mehr erfahren',
			'weiterlesen',
			'weiterlesen...',
			'weiterlesen…',
			'read more',
			'learn more',
			'kontakt',
			'impressum',
			'datenschutz',
			'privacy policy',
			'barrierefreiheit',
			'barrierefreiheitserklärung',
			'barrierefreiheitserklaerung',
			'language switcher',
			'sport',
			'sportarten',
			'behinderungsgruppen',
			'angebote nach behinderungsgruppe',
			'verbandsleben',
			'hauptinhalt',
			'aktuelles',
			'aktuell',
			'veranstaltungen',
			'termine',
			'termin',
			'ueberblick',
			'überblick',
			'programm',
			'kalender',
			'mitglieder werden',
			'newsletter',
			'facebook',
			'instagram',
			'youtube',
			'ausschreibung anzeigen',
		);

		if ( in_array( $normalized, $exact, true ) ) {
			return true;
		}

		if ( preg_match( '/^(januar|februar|märz|maerz|april|mai|juni|juli|august|september|oktober|november|dezember|january|february|march|may|june|july|october|december)\s+\d{4}$/iu', $normalized ) ) {
			return true;
		}

		$fragments = array(
			'svg-fill',
			'svg-stroke',
			'zur startseite',
			'logo',
			'suchbox',
			'öffne das untermenü',
			'öffne das untermen',
			'go to',
			'toggle navigation',
			'nichts mehr verpassen',
			'anzeigen',
		);

		foreach ( $fragments as $fragment ) {
			if ( strpos( $normalized, $fragment ) !== false ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Public wrapper for validating scraped titles before post creation.
	 *
	 * @param string $title Event title
	 *
	 * @return bool True when the title should be ignored
	 */
	public static function is_ignored_event_title( $title ) {
		return self::is_ignored_title( $title );
	}

	/**
	 * Check for links that should never become events.
	 *
	 * @param string $href Link URL
	 *
	 * @return bool True when the link should be ignored
	 */
	private static function is_ignored_href( $href ) {
		$href = strtolower( trim( (string) $href ) );
		if ( empty( $href ) ) {
			return false;
		}

		if ( strpos( $href, '#' ) === 0 || strpos( $href, 'mailto:' ) === 0 || strpos( $href, 'tel:' ) === 0 ) {
			return true;
		}

		$ignored = array(
			'/kontakt',
			'/contact',
			'/impressum',
			'/datenschutz',
			'/privacy',
			'/barrierefreiheit',
			'/accessibility',
			'/newsletter',
			'/feed',
			'/wp-content/',
			'instagram.com',
			'facebook.com',
			'youtube.com',
			'linkedin.com',
			'twitter.com',
			'x.com',
		);

		foreach ( $ignored as $fragment ) {
			if ( strpos( $href, $fragment ) !== false ) {
				return true;
			}
		}

		$path = wp_parse_url( $href, PHP_URL_PATH );
		$path = trim( (string) $path, '/' );
		if ( empty( $path ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Check whether text contains signals typical for an event.
	 *
	 * @param string $text Candidate text
	 *
	 * @return bool True when candidate contains event signals
	 */
	private static function has_event_signal( $text ) {
		if ( self::extract_date( $text ) || self::extract_time( $text ) ) {
			return true;
		}

		return (bool) preg_match( '/\b(event|events|veranstaltung|veranstaltungen|termin|termine|treffen|training|trainings|kurs|kurse|workshop|workshops|seminar|seminare|fortbildung|fortbildungen|ausschreibung|finals|turnier|turniere|camp|kalender|anmeldung|programm|tagung|webinar|infoabend|elternabend|netzwerktreffen|selbsthilfegruppe|gruppe|gruppenangebot|beratung|dialog|vortrag|impuls|pflege|betreuung|inklusion|behinderung)\b/iu', $text );
	}

	/**
	 * Enrich a thin event card by fetching its linked detail page.
	 *
	 * @param array $event Parsed event
	 *
	 * @return array Enriched event
	 */
	public static function enrich_event( $event ) {
		$needs_detail = strlen( $event['description'] ?? '' ) < 80
			|| empty( $event['image_url'] )
			|| empty( $event['location'] )
			|| empty( $event['email'] )
			|| empty( $event['phone'] );

		if ( empty( $event['href'] ) || ! preg_match( '/^https?:\/\//i', $event['href'] ) || ! $needs_detail ) {
			return $event;
		}

		$detail = self::scrape_detail_page( $event['href'], $event['title'] );
		if ( empty( $detail ) ) {
			return $event;
		}

		foreach ( array( 'description', 'date', 'time', 'email', 'phone', 'image_url', 'location', 'schedule' ) as $field ) {
			if ( empty( $event[ $field ] ) && ! empty( $detail[ $field ] ) ) {
				$event[ $field ] = $detail[ $field ];
			}
		}

		return $event;
	}

	/**
	 * Scrape a linked detail page for richer event data.
	 *
	 * @param string $url Event detail URL
	 * @param string $title Event title
	 *
	 * @return array Detail fields
	 */
	private static function scrape_detail_page( $url, $title = '' ) {
		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 10,
				'user-agent' => 'Mozilla/5.0 (WordPress Event Monitor)',
			)
		);

		if ( is_wp_error( $response ) ) {
			return array();
		}

		$body = wp_remote_retrieve_body( $response );
		if ( empty( $body ) ) {
			return array();
		}

		libxml_use_internal_errors( true );

		$dom = new DOMDocument();
		$dom->loadHTML( '<?xml encoding="UTF-8">' . $body );

		libxml_clear_errors();

		$xpath = new DOMXPath( $dom );
		$image_url = self::extract_meta_image_url( $xpath, $url );
		$text = '';
		$nodes = $xpath->query( '//article | //main | //*[@role="main"]' );

		if ( $nodes && $nodes->length > 0 ) {
			foreach ( $nodes as $node ) {
				$candidate = self::normalize_text( $node->textContent );
				if ( strlen( $candidate ) > strlen( $text ) ) {
					$text = $candidate;
				}
			}
		} else {
			$body = $xpath->query( '//body' );
			if ( $body && $body->length > 0 ) {
				$text = self::normalize_text( $body->item( 0 )->textContent );
			}
		}

		if ( empty( $image_url ) && $nodes && $nodes->length > 0 ) {
			foreach ( $nodes as $node ) {
				if ( $node instanceof DOMElement ) {
					$image_url = self::extract_image_url( $node, $url );
					if ( ! empty( $image_url ) ) {
						break;
					}
				}
			}
		}

		if ( ! empty( $title ) ) {
			$text = preg_replace( '/^' . preg_quote( $title, '/' ) . '\s*/u', '', $text );
		}

		if ( strlen( $text ) > 2000 ) {
			$text = substr( $text, 0, 2000 ) . '...';
		}

		return array(
			'description' => $text,
			'date' => self::extract_date( $text ),
			'time' => self::extract_time( $text ),
			'schedule' => self::extract_schedule( self::extract_datetime_text( $dom->documentElement ) . ' ' . $text, self::extract_time( $text ) ),
			'location' => self::extract_location( $text ),
			'email' => self::extract_email( $text ),
			'phone' => self::extract_phone( $text ),
			'image_url' => $image_url,
		);
	}

	/**
	 * Extract a useful title from headings, links, or element text.
	 *
	 * @param DOMElement $element Current element
	 *
	 * @return string Event title
	 */
	private static function extract_title( $element ) {
		$xpath = new DOMXPath( $element->ownerDocument );
		$candidates = $xpath->query(
			'.//h1 | .//h2 | .//h3 | .//h4 |
			.//*[contains(translate(concat(" ", @class, " "), "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz"), " title ")] |
			.//*[contains(translate(concat(" ", @class, " "), "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz"), " headline ")] |
			.//*[contains(translate(concat(" ", @class, " "), "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz"), " heading ")] |
			.//*[contains(translate(concat(" ", @class, " "), "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz"), " name ")] |
			.//*[contains(translate(concat(" ", @class, " "), "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz"), " h1 ")] |
			.//*[contains(translate(concat(" ", @class, " "), "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz"), " h2 ")] |
			.//*[contains(translate(concat(" ", @class, " "), "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz"), " h3 ")] |
			.//*[contains(translate(concat(" ", @class, " "), "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz"), " h4 ")] |
			.//a',
			$element
		);

		if ( $candidates ) {
			foreach ( $candidates as $candidate ) {
				$text = self::normalize_text( $candidate->textContent );
				if ( strlen( $text ) >= 5 && strlen( $text ) <= 180 && ! self::is_ignored_title( $text ) ) {
					return $text;
				}
			}
		}

		return self::normalize_text( $element->textContent );
	}

	/**
	 * Extract the event URL from the element or a child link.
	 *
	 * @param DOMElement $element Current element
	 * @param string $source_url Source page URL
	 *
	 * @return string Event URL
	 */
	private static function extract_href( $element, $source_url ) {
		$href = $element->getAttribute( 'href' );

		if ( empty( $href ) ) {
			$links = $element->getElementsByTagName( 'a' );
			if ( $links->length > 0 ) {
				$href = $links->item( 0 )->getAttribute( 'href' );
			}
		}

		return self::make_absolute_url( $href, $source_url );
	}

	/**
	 * Extract the best image URL from an event element.
	 *
	 * @param DOMElement $element Current element
	 * @param string     $source_url Source page URL
	 *
	 * @return string Image URL
	 */
	private static function extract_image_url( $element, $source_url, $fallback_url = '' ) {
		$images = $element->getElementsByTagName( 'img' );

		foreach ( $images as $image ) {
			$url = self::extract_url_from_image_element( $image );
			if ( self::is_usable_image_url( $url ) ) {
				return self::make_absolute_url( $url, $source_url );
			}
		}

		$sources = $element->getElementsByTagName( 'source' );
		foreach ( $sources as $source ) {
			$url = self::extract_url_from_source_element( $source );
			if ( self::is_usable_image_url( $url ) ) {
				return self::make_absolute_url( $url, $source_url );
			}
		}

		$url = self::extract_background_image_url( $element );
		if ( self::is_usable_image_url( $url ) ) {
			return self::make_absolute_url( $url, $source_url );
		}

		if ( self::is_usable_image_url( $fallback_url ) ) {
			return self::make_absolute_url( $fallback_url, $source_url );
		}

		return '';
	}

	/**
	 * Extract a page-level image from Open Graph or Twitter meta tags.
	 *
	 * @param DOMXPath $xpath DOM XPath
	 * @param string   $source_url Source page URL
	 *
	 * @return string Image URL
	 */
	private static function extract_meta_image_url( $xpath, $source_url ) {
		$nodes = $xpath->query(
			'//meta[
				translate(@property, "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz") = "og:image" or
				translate(@property, "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz") = "og:image:url" or
				translate(@name, "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz") = "twitter:image"
			]'
		);

		if ( ! $nodes ) {
			return '';
		}

		foreach ( $nodes as $node ) {
			if ( ! $node instanceof DOMElement ) {
				continue;
			}

			$url = trim( $node->getAttribute( 'content' ) );
			if ( self::is_usable_image_url( $url ) ) {
				return self::make_absolute_url( $url, $source_url );
			}
		}

		return '';
	}

	/**
	 * Extract URL from common image and lazy-load attributes.
	 *
	 * @param DOMElement $image Image element
	 *
	 * @return string Image URL
	 */
	private static function extract_url_from_image_element( $image ) {
		foreach ( array( 'src', 'data-src', 'data-lazy-src', 'data-original', 'data-url', 'data-bg', 'data-background', 'data-background-image' ) as $attribute ) {
			$value = trim( $image->getAttribute( $attribute ) );
			if ( self::is_usable_image_url( $value ) ) {
				return $value;
			}
		}

		return self::extract_url_from_source_element( $image );
	}

	/**
	 * Extract URL from common source/srcset attributes.
	 *
	 * @param DOMElement $source Source or image element
	 *
	 * @return string Image URL
	 */
	private static function extract_url_from_source_element( $source ) {
		foreach ( array( 'srcset', 'data-srcset' ) as $attribute ) {
			$value = trim( $source->getAttribute( $attribute ) );
			if ( empty( $value ) ) {
				continue;
			}

			$candidates = array_map( 'trim', explode( ',', $value ) );
			$candidate = end( $candidates );
			$parts = preg_split( '/\s+/', trim( $candidate ) );

			if ( ! empty( $parts[0] ) && self::is_usable_image_url( $parts[0] ) ) {
				return $parts[0];
			}
		}

		foreach ( array( 'src', 'data-src' ) as $attribute ) {
			$value = trim( $source->getAttribute( $attribute ) );
			if ( self::is_usable_image_url( $value ) ) {
				return $value;
			}
		}

		return '';
	}

	/**
	 * Extract a CSS background image URL from an element tree.
	 *
	 * @param DOMElement $element Current element
	 *
	 * @return string Image URL
	 */
	private static function extract_background_image_url( $element ) {
		$candidates = array( $element );
		foreach ( $element->getElementsByTagName( '*' ) as $child ) {
			$candidates[] = $child;
		}

		foreach ( $candidates as $candidate ) {
			if ( ! $candidate instanceof DOMElement ) {
				continue;
			}

			foreach ( array( 'style', 'data-bg', 'data-background', 'data-background-image' ) as $attribute ) {
				$value = trim( $candidate->getAttribute( $attribute ) );
				if ( empty( $value ) ) {
					continue;
				}

				if ( preg_match( '/url\((["\']?)([^"\')]+)\1\)/i', $value, $matches ) && self::is_usable_image_url( $matches[2] ) ) {
					return $matches[2];
				}

				if ( self::is_usable_image_url( $value ) ) {
					return $value;
				}
			}
		}

		return '';
	}

	/**
	 * Check if an image URL is suitable for importing as a featured image.
	 *
	 * @param string $url Image URL
	 *
	 * @return bool
	 */
	private static function is_usable_image_url( $url ) {
		$url = trim( (string) $url );

		if ( empty( $url ) || preg_match( '/^(data:|mailto:|tel:|#)/i', $url ) ) {
			return false;
		}

		if ( preg_match( '/\.(svg|ico)(?:$|\?)/i', $url ) ) {
			return false;
		}

		return ! preg_match( '/(logo|icon|sprite|placeholder|avatar|tracking|pixel)/i', $url );
	}

	/**
	 * Normalize scraped text.
	 *
	 * @param string $text Raw text
	 *
	 * @return string Normalized text
	 */
	private static function normalize_text( $text ) {
		$text = html_entity_decode( wp_strip_all_tags( (string) $text ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		$text = preg_replace( '/\s+/u', ' ', $text );

		return trim( $text );
	}

	/**
	 * Convert relative URLs to absolute URLs.
	 *
	 * @param string $href Link URL
	 * @param string $source_url Source page URL
	 *
	 * @return string Absolute URL when possible
	 */
	private static function make_absolute_url( $href, $source_url ) {
		$href = trim( (string) $href );
		if ( empty( $href ) || preg_match( '/^https?:\/\//i', $href ) ) {
			return $href;
		}

		$parts = wp_parse_url( $source_url );
		if ( empty( $parts['scheme'] ) || empty( $parts['host'] ) ) {
			return $href;
		}

		if ( strpos( $href, '//' ) === 0 ) {
			return $parts['scheme'] . ':' . $href;
		}

		$base = $parts['scheme'] . '://' . $parts['host'];
		if ( strpos( $href, '/' ) === 0 ) {
			return $base . $href;
		}

		$path = isset( $parts['path'] ) ? dirname( $parts['path'] ) : '';
		$path = $path === '\\' || $path === '/' || $path === '.' ? '' : $path;

		return $base . '/' . ltrim( $path . '/' . $href, '/' );
	}

	/**
	 * Extract a date and convert it to ACF date_picker storage format.
	 *
	 * @param string $text Event text
	 *
	 * @return string Date in Ymd format or empty string
	 */
	private static function extract_date( $text ) {
		if ( preg_match( '/\b(\d{1,2})\.(\d{1,2})\.\s*(?:-|–|—|bis)\s*\d{1,2}\.\d{1,2}\.(\d{4})\b/u', $text, $matches ) ) {
			return sprintf( '%04d%02d%02d', (int) $matches[3], (int) $matches[2], (int) $matches[1] );
		}

		$patterns = array(
			'/\b(\d{1,2})[.\/-](\d{1,2})[.\/-](\d{2,4})\b/u',
			'/\b(\d{4})-(\d{1,2})-(\d{1,2})\b/u',
		);

		foreach ( $patterns as $pattern ) {
			if ( preg_match( $pattern, $text, $matches ) ) {
				if ( $pattern === $patterns[1] ) {
					return sprintf( '%04d%02d%02d', (int) $matches[1], (int) $matches[2], (int) $matches[3] );
				}

				$year = (int) $matches[3];
				if ( $year < 100 ) {
					$year += 2000;
				}

				return sprintf( '%04d%02d%02d', $year, (int) $matches[2], (int) $matches[1] );
			}
		}

		$month_pattern = 'jan(?:uar|uary)?|feb(?:ruar|ruary)?|märz|maerz|mar(?:ch)?|apr(?:il)?|mai|may|jun(?:i|e)?|jul(?:i|y)?|aug(?:ust)?|sep(?:tember)?|okt(?:ober)?|oct(?:ober)?|nov(?:ember)?|dez(?:ember)?|dec(?:ember)?';
		if ( preg_match( '/\b(?:mo|di|mi|do|fr|sa|so|mon|tue|wed|thu|fri|sat|sun)[a-z]*,?\s+(\d{1,2})\.?\s*(' . $month_pattern . ')\.?\s*(\d{4})\b/iu', $text, $matches )
			|| preg_match( '/\b(\d{1,2})\.?\s*(' . $month_pattern . ')\.?\s*(\d{4})\b/iu', $text, $matches )
		) {
			$month = self::month_number( $matches[2] );
			if ( $month ) {
				return sprintf( '%04d%02d%02d', (int) $matches[3], $month, (int) $matches[1] );
			}
		}

		return '';
	}

	/**
	 * Convert German/English month names and abbreviations to a number.
	 *
	 * @param string $month_name Month name
	 *
	 * @return int Month number or 0
	 */
	private static function month_number( $month_name ) {
		$month_name = strtolower( trim( (string) $month_name, ". \t\n\r\0\x0B" ) );
		$month_name = str_replace( 'ä', 'ae', $month_name );

		$months = array(
			'jan' => 1,
			'januar' => 1,
			'january' => 1,
			'feb' => 2,
			'februar' => 2,
			'february' => 2,
			'maerz' => 3,
			'mar' => 3,
			'march' => 3,
			'apr' => 4,
			'april' => 4,
			'mai' => 5,
			'may' => 5,
			'jun' => 6,
			'juni' => 6,
			'june' => 6,
			'jul' => 7,
			'juli' => 7,
			'july' => 7,
			'aug' => 8,
			'august' => 8,
			'sep' => 9,
			'september' => 9,
			'okt' => 10,
			'oktober' => 10,
			'oct' => 10,
			'october' => 10,
			'nov' => 11,
			'november' => 11,
			'dez' => 12,
			'dezember' => 12,
			'dec' => 12,
			'december' => 12,
		);

		return isset( $months[ $month_name ] ) ? (int) $months[ $month_name ] : 0;
	}

	/**
	 * Extract a time or time range.
	 *
	 * @param string $text Event text
	 *
	 * @return string Time string
	 */
	private static function extract_time( $text ) {
		if ( preg_match( '/\b(\d{1,2}[:.]\d{2})(?::\d{2})?(?:\s*(?:-|–|bis|to)\s*(\d{1,2}[:.]\d{2})(?::\d{2})?)?\s*(?:Uhr|h)\b/iu', $text, $matches )
			|| preg_match( '/(?:^|[^\d.])(\d{1,2}:\d{2})(?::\d{2})?(?:\s*(?:-|–|bis|to)\s*(\d{1,2}:\d{2})(?::\d{2})?)?(?:$|[^\d.])/iu', $text, $matches )
		) {
			$start = str_replace( '.', ':', $matches[1] );
			if ( ! empty( $matches[2] ) ) {
				return $start . ' - ' . str_replace( '.', ':', $matches[2] );
			}

			return $start;
		}

		if ( preg_match( '/\b(\d{1,2})(?:\s*(?:Uhr|h))?\s*(?:-|–|bis|to)\s*(\d{1,2})(?:[:.](\d{2}))?\s*(?:Uhr|h)\b/iu', $text, $matches ) ) {
			$start = sprintf( '%02d:00', (int) $matches[1] );
			$end = sprintf( '%02d:%02d', (int) $matches[2], ! empty( $matches[3] ) ? (int) $matches[3] : 0 );

			return $start . ' - ' . $end;
		}

		return '';
	}

	/**
	 * Extract date/time hints from semantic HTML attributes.
	 *
	 * @param DOMElement $element Source element
	 *
	 * @return string
	 */
	private static function extract_datetime_text( $element ) {
		if ( ! $element instanceof DOMElement ) {
			return '';
		}

		$values = array();
		$nodes = array( $element );
		foreach ( $element->getElementsByTagName( '*' ) as $child ) {
			$nodes[] = $child;
		}

		$attributes = array( 'datetime', 'content', 'title', 'aria-label', 'data-date', 'data-start', 'data-end', 'data-begin', 'data-time', 'data-from', 'data-to' );
		foreach ( $nodes as $node ) {
			if ( ! $node instanceof DOMElement ) {
				continue;
			}

			foreach ( $attributes as $attribute ) {
				$value = trim( $node->getAttribute( $attribute ) );
				if ( empty( $value ) || ! preg_match( '/\d{1,4}[.\/:-]\d{1,2}|\d{8}|\b(?:jan|feb|mar|apr|mai|jun|jul|aug|sep|okt|oct|nov|dez|dec)/iu', $value ) ) {
					continue;
				}

				$values[] = $value;
			}
		}

		return self::normalize_text( implode( ' ', array_unique( $values ) ) );
	}

	/**
	 * Extract a sorted multi-day schedule from event text.
	 *
	 * @param string $text Event text
	 * @param string $time Fallback time
	 *
	 * @return array
	 */
	private static function extract_schedule( $text, $time = '' ) {
		$text = self::normalize_text( $text );
		$dates = array();

		if ( preg_match_all( '/\b(\d{1,2})[.\/-](\d{1,2})[.\/-](\d{2,4})\s*(?:-|bis|to)\s*(\d{1,2})[.\/-](\d{1,2})[.\/-](\d{2,4})\b/iu', $text, $ranges, PREG_SET_ORDER ) ) {
			foreach ( $ranges as $range ) {
				$start_year = (int) $range[3];
				$end_year = (int) $range[6];
				if ( $start_year < 100 ) {
					$start_year += 2000;
				}
				if ( $end_year < 100 ) {
					$end_year += 2000;
				}

				self::add_date_range( $dates, sprintf( '%04d%02d%02d', $start_year, (int) $range[2], (int) $range[1] ), sprintf( '%04d%02d%02d', $end_year, (int) $range[5], (int) $range[4] ) );
			}
		}

		if ( preg_match_all( '/\b(\d{4})-(\d{1,2})-(\d{1,2})(?:T\d{1,2}:\d{2}(?::\d{2})?)?\b/u', $text, $iso_dates, PREG_SET_ORDER ) ) {
			foreach ( $iso_dates as $match ) {
				$dates[ sprintf( '%04d%02d%02d', (int) $match[1], (int) $match[2], (int) $match[3] ) ] = true;
			}
		}

		if ( preg_match_all( '/\b(\d{1,2})[.\/](\d{1,2})[.\/](\d{2,4})\b/u', $text, $numeric_dates, PREG_SET_ORDER ) ) {
			foreach ( $numeric_dates as $match ) {
				$year = (int) $match[3];
				if ( $year < 100 ) {
					$year += 2000;
				}
				$dates[ sprintf( '%04d%02d%02d', $year, (int) $match[2], (int) $match[1] ) ] = true;
			}
		}

		$month_pattern = 'jan(?:uar|uary)?|feb(?:ruar|ruary)?|märz|maerz|mar(?:ch)?|apr(?:il)?|mai|may|jun(?:i|e)?|jul(?:i|y)?|aug(?:ust)?|sep(?:tember)?|okt(?:ober)?|oct(?:ober)?|nov(?:ember)?|dez(?:ember)?|dec(?:ember)?';
		if ( preg_match_all( '/\b(\d{1,2})\.?\s*(' . $month_pattern . ')\.?\s*(\d{4})\b/iu', $text, $named_dates, PREG_SET_ORDER ) ) {
			foreach ( $named_dates as $match ) {
				$month = self::month_number( $match[2] );
				if ( $month ) {
					$dates[ sprintf( '%04d%02d%02d', (int) $match[3], $month, (int) $match[1] ) ] = true;
				}
			}
		}

		ksort( $dates );
		$schedule = array();
		foreach ( array_keys( $dates ) as $date ) {
			if ( 8 !== strlen( $date ) || count( $schedule ) >= 40 ) {
				continue;
			}

			$schedule[] = array(
				'date' => $date,
				'time' => $time,
			);
		}

		return $schedule;
	}

	/**
	 * Add an inclusive date range to a schedule map, capped to avoid broad page sections.
	 *
	 * @param array  $dates Date map
	 * @param string $start Start date Ymd
	 * @param string $end End date Ymd
	 */
	private static function add_date_range( &$dates, $start, $end ) {
		$start_ts = strtotime( substr( $start, 0, 4 ) . '-' . substr( $start, 4, 2 ) . '-' . substr( $start, 6, 2 ) );
		$end_ts = strtotime( substr( $end, 0, 4 ) . '-' . substr( $end, 4, 2 ) . '-' . substr( $end, 6, 2 ) );

		if ( ! $start_ts || ! $end_ts || $end_ts < $start_ts || ( $end_ts - $start_ts ) > 31 * DAY_IN_SECONDS ) {
			return;
		}

		for ( $ts = $start_ts; $ts <= $end_ts; $ts += DAY_IN_SECONDS ) {
			$dates[ gmdate( 'Ymd', $ts ) ] = true;
		}
	}

	/**
	 * Extract a probable event location from text.
	 *
	 * @param string $text Source text
	 *
	 * @return string
	 */
	private static function extract_location( $text ) {
		$text = self::normalize_text( $text );
		if ( empty( $text ) ) {
			return '';
		}

		$patterns = array(
			'/(?:Veranstaltungsort|Ort|Adresse|Treffpunkt|Location)\s*[:\-]\s*([^\.|;]{3,120})/iu',
			'/(?:in|im|am)\s+([A-ZÄÖÜ][^\.|;]{3,90}(?:Saal|Haus|Zentrum|Gemeindehaus|Gemeindesaal|Bildungshaus|Schule|Pfarrheim|Rathaus|Museum|Bibliothek|Hotel|Institut|Schnepfau|Batschuns|Dornbirn|Bregenz|Feldkirch|Bludenz)[^\.|;]*)/u',
		);

		foreach ( $patterns as $pattern ) {
			if ( preg_match( $pattern, $text, $matches ) ) {
				$location = self::normalize_location( $matches[1] );
				if ( ! empty( $location ) ) {
					return $location;
				}
			}
		}

		return '';
	}

	/**
	 * Normalize and validate a location string.
	 *
	 * @param string $location Raw location
	 *
	 * @return string
	 */
	private static function normalize_location( $location ) {
		$location = self::normalize_text( $location );
		$location = preg_replace( '/\b(?:Referenten?|Veranstalter|Anmeldung|Termin speichern|Keine Anmeldung|Freier Eintritt)\b.*$/iu', '', $location );
		$location = trim( $location, " \t\n\r\0\x0B,.;:-" );

		if ( strlen( $location ) < 3 || strlen( $location ) > 160 ) {
			return '';
		}

		if ( preg_match( '/^(time|source|buy ticket|view event|termin speichern)$/iu', $location ) ) {
			return '';
		}

		return $location;
	}

	/**
	 * Extract the first email address.
	 *
	 * @param string $text Event text
	 *
	 * @return string Email address
	 */
	private static function extract_email( $text ) {
		if ( preg_match( '/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i', $text, $matches ) ) {
			return $matches[0];
		}

		return '';
	}

	/**
	 * Extract the first likely phone number.
	 *
	 * @param string $text Event text
	 *
	 * @return string Phone number
	 */
	private static function extract_phone( $text ) {
		if ( preg_match( '/(?:\+|00)\d[\d\s()\/.-]{6,}\d/', $text, $matches ) ) {
			return trim( $matches[0] );
		}

		return '';
	}

	/**
	 * Generate hash for event deduplication
	 *
	 * @param string $title Event title
	 * @param string $href Event URL
	 *
	 * @return string MD5 hash
	 */
	public static function generate_hash( $title, $href = '', $date = '', $source_id = 0 ) {
		$title = self::normalize_hash_text( $title );
		$date = preg_replace( '/\D+/', '', (string) $date );
		$href = self::normalize_hash_url( $href );

		$parts = array( $title );
		if ( $source_id ) {
			$parts[] = 'source:' . (int) $source_id;
		} elseif ( ! empty( $href ) ) {
			$parts[] = $date;
			$parts[] = $href;
		} else {
			$parts[] = $date;
		}

		return md5( implode( '|', $parts ) );
	}

	/**
	 * Generate hashes used by older plugin versions.
	 *
	 * @param string $title Event title
	 * @param string $href Event URL
	 * @param string $date Event date
	 *
	 * @return string MD5 hash
	 */
	public static function generate_legacy_hash( $title, $href = '', $date = '' ) {
		return md5( strtolower( trim( $title ) ) . strtolower( trim( $href ) ) . trim( (string) $date ) );
	}

	/**
	 * Normalize title text before hashing.
	 *
	 * @param string $text Raw title
	 *
	 * @return string Normalized title
	 */
	private static function normalize_hash_text( $text ) {
		$text = html_entity_decode( wp_strip_all_tags( (string) $text ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		$text = strtolower( trim( preg_replace( '/\s+/u', ' ', $text ) ) );

		return $text;
	}

	/**
	 * Normalize URLs before hashing, dropping volatile query arguments/fragments.
	 *
	 * @param string $href Raw URL
	 *
	 * @return string Normalized URL
	 */
	private static function normalize_hash_url( $href ) {
		$href = trim( (string) $href );
		if ( empty( $href ) ) {
			return '';
		}

		$parts = wp_parse_url( $href );
		if ( empty( $parts['host'] ) ) {
			return strtolower( rtrim( strtok( $href, '?#' ), '/' ) );
		}

		$scheme = ! empty( $parts['scheme'] ) ? strtolower( $parts['scheme'] ) : 'https';
		$host = strtolower( preg_replace( '/^www\./', '', $parts['host'] ) );
		$path = isset( $parts['path'] ) ? rtrim( preg_replace( '#/+#', '/', $parts['path'] ), '/' ) : '';
		$query = '';

		if ( ! empty( $parts['query'] ) ) {
			parse_str( $parts['query'], $query_args );
			foreach ( array_keys( $query_args ) as $key ) {
				if ( preg_match( '/^(utm_|fbclid$|gclid$|mc_|pk_)/i', $key ) ) {
					unset( $query_args[ $key ] );
				}
			}
			if ( ! empty( $query_args ) ) {
				ksort( $query_args );
				$query = '?' . http_build_query( $query_args );
			}
		}

		return $scheme . '://' . $host . $path . $query;
	}
}

