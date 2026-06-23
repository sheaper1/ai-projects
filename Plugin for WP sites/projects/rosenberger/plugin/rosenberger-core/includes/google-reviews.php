<?php
/**
 * Чтение Google-отзывов из плагина «Widget for Google Reviews» (grw).
 *
 * Источник — transient `grw_feed_<version>_<feedId>_reviews`, который grw
 * наполняет из Google (по cron / при рендере виджета). Ключ собираем из опций
 * `grw_version` и `grw_feed_ids`, чтобы не хардкодить версию.
 *
 * Возвращает агрегат (rating/count/name) и массив отзывов. Последний удачный
 * результат кэшируем в опцию — если transient протух, отдаём last-known-good,
 * чтобы trust-bar и отзывы не «пропадали» между обновлениями кэша grw.
 *
 * @package rosenberger-core
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'rosenberger_google_reviews' ) ) {
	/**
	 * @return array{rating:string,count:string,name:string,url:string,reviews:array<int,array>}
	 */
	function rosenberger_google_reviews() {
		static $cache = null;
		if ( null !== $cache ) {
			return $cache;
		}

		$out = [ 'rating' => '', 'count' => '', 'name' => '', 'url' => '', 'reviews' => [] ];

		$ver = get_option( 'grw_version' );
		$ids = get_option( 'grw_feed_ids' );
		if ( $ver && $ids ) {
			$id   = trim( explode( ',', (string) $ids )[0] );
			$data = get_transient( "grw_feed_{$ver}_{$id}_reviews" );
			if ( is_array( $data ) ) {
				if ( ! empty( $data['businesses'][0] ) ) {
					$b           = $data['businesses'][0];
					$out['rating'] = (string) ( $b->rating ?? '' );
					$out['count']  = (string) ( $b->review_count ?? '' );
					$out['name']   = (string) ( $b->name ?? '' );
					$out['url']    = (string) ( $b->url ?? '' );
				}
				if ( ! empty( $data['reviews'] ) && is_array( $data['reviews'] ) ) {
					foreach ( $data['reviews'] as $r ) {
						if ( ! empty( $r->hide ) ) {
							continue;
						}
						$out['reviews'][] = [
							'name'   => (string) ( $r->author_name ?? '' ),
							'avatar' => (string) ( $r->author_avatar ?? '' ),
							'rating' => (int) ( $r->rating ?? 0 ),
							'text'   => (string) ( $r->text ?? '' ),
							'time'   => (int) ( $r->time ?? 0 ),
							'url'    => (string) ( $r->author_url ?? '' ),
						];
					}
				}
			}
		}

		// Last-known-good: пишем при свежих данных, читаем при пустом transient.
		if ( '' !== $out['rating'] || ! empty( $out['reviews'] ) ) {
			update_option( 'rosenberger_grw_cache', $out, false );
		} else {
			$cached = get_option( 'rosenberger_grw_cache' );
			if ( is_array( $cached ) ) {
				$out = $cached;
			}
		}

		$cache = $out;
		return $out;
	}
}

if ( ! function_exists( 'rosenberger_google_reviews_positive' ) ) {
	/**
	 * Отзывы для секции-витрины: только с текстом и рейтингом >= порога,
	 * отсортированы по дате (свежие первыми).
	 *
	 * @param int $min_rating Минимальный рейтинг (по умолчанию 4).
	 * @return array<int,array>
	 */
	function rosenberger_google_reviews_positive( $min_rating = 4 ) {
		$data    = rosenberger_google_reviews();
		$reviews = array_filter(
			$data['reviews'],
			static function ( $r ) use ( $min_rating ) {
				return $r['rating'] >= $min_rating && '' !== trim( wp_strip_all_tags( $r['text'] ) );
			}
		);
		usort( $reviews, static fn( $a, $b ) => $b['time'] <=> $a['time'] );
		return array_values( $reviews );
	}
}
