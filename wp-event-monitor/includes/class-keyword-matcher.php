<?php
/**
 * WEM_Keyword_Matcher
 *
 * Matches events against keywords and regex patterns
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WEM_Keyword_Matcher {

	/**
	 * Check if event matches any keywords
	 *
	 * @param array $event Event data with 'title', 'text', 'href'
	 * @param array $keywords Array of keyword objects with 'keyword' and 'type'
	 *
	 * @return array Array of matched keywords, empty if no match
	 */
	public static function match( $event, $keywords ) {
		if ( empty( $keywords ) ) {
			return array();
		}

		$matched = array();
		$search_text = self::prepare_search_text( $event );

		foreach ( $keywords as $kw ) {
			$keyword = $kw->keyword;
			$type = $kw->type;

			if ( $type === 'regex' ) {
				if ( self::match_regex( $search_text, $keyword ) ) {
					$matched[] = $keyword;
				}
			} else {
				// Plain text match (case-insensitive)
				if ( self::match_plain( $search_text, $keyword ) ) {
					$matched[] = $keyword;
				}
			}
		}

		return $matched;
	}

	/**
	 * Prepare text for searching (title + href + description)
	 *
	 * @param array $event Event array
	 *
	 * @return string Combined searchable text
	 */
	private static function prepare_search_text( $event ) {
		$text = '';

		if ( ! empty( $event['title'] ) ) {
			$text .= $event['title'] . ' ';
		}

		if ( ! empty( $event['href'] ) ) {
			$text .= $event['href'] . ' ';
		}

		if ( ! empty( $event['description'] ) ) {
			$text .= $event['description'] . ' ';
		}

		if ( ! empty( $event['text'] ) ) {
			$text .= $event['text'] . ' ';
		}

		// Remove HTML tags if present
		$text = wp_strip_all_tags( $text );

		return strtolower( trim( $text ) );
	}

	/**
	 * Match plain text keyword (case-insensitive)
	 *
	 * @param string $text Text to search in
	 * @param string $keyword Keyword to find
	 *
	 * @return bool True if keyword found
	 */
	private static function match_plain( $text, $keyword ) {
		return stripos( $text, strtolower( trim( $keyword ) ) ) !== false;
	}

	/**
	 * Match regex pattern
	 *
	 * @param string $text Text to search in
	 * @param string $pattern Regex pattern
	 *
	 * @return bool True if pattern matches
	 */
	private static function match_regex( $text, $pattern ) {
		// Ensure pattern is wrapped in delimiters
		if ( ! preg_match( '/^[\/#~].*[\/#~][imsxADSUXJu]*$/', $pattern ) ) {
			$pattern = '/' . $pattern . '/i';
		}

		$result = @preg_match( $pattern, $text );

		return $result === 1;
	}

	/**
	 * Validate regex pattern
	 *
	 * @param string $pattern Pattern to validate
	 *
	 * @return array Array with 'valid' bool and 'error' message if invalid
	 */
	public static function validate_regex( $pattern ) {
		// Ensure pattern is wrapped in delimiters
		if ( ! preg_match( '/^[\/#~].*[\/#~][imsxADSUXJu]*$/', $pattern ) ) {
			$pattern = '/' . $pattern . '/i';
		}

		$result = @preg_match( $pattern, '' );

		if ( $result === false ) {
			return array(
				'valid' => false,
				'error' => 'Invalid regex pattern',
			);
		}

		return array(
			'valid' => true,
			'error' => null,
		);
	}
}
