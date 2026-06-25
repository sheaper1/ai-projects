<?php
/**
 * Propstack → Rosenberger «мост данных».
 *
 * Плагин propstack-real-estate хранит данные объекта в скрытой мете `_property_*`
 * (его CPT `propstack_property`). Наши готовые блоки property-* и шаблон
 * single-propstack_property рассчитаны на наши ключи `property_*`.
 *
 * Вместо дублирования данных перехватываем чтение `property_*` у постов
 * `propstack_property` и отдаём значения из `_property_*` на лету, приводя форматы
 * к виду, который ждут блоки («85 m²», «450.000 €», статус «Verfügbar» и т.д.).
 *
 * Тело плагина при этом не трогается (вариант B). Работает сразу для существующих
 * и будущих синков, без проблем с таймингом save_post.
 *
 * @package rosenberger
 */

defined( 'ABSPATH' ) || exit;

/**
 * Скомпоновать адрес одной строкой, как в наших макетах.
 */
function rosenberger_propstack_address( int $post_id ): string {
	$raw     = fn( $k ) => trim( (string) get_post_meta( $post_id, $k, true ) );
	$street  = trim( $raw( '_property_street' ) . ' ' . $raw( '_property_house_number' ) );
	$city    = trim( $raw( '_property_zip' ) . ' ' . $raw( '_property_city' ) );
	$region  = $raw( '_property_region' );
	$country = $raw( '_property_country' );
	$country = array( 'AUT' => 'Österreich', 'AT' => 'Österreich', 'DEU' => 'Deutschland', 'DE' => 'Deutschland' )[ $country ] ?? $country;

	$line = array_filter( array( $street, $city ) );
	$line = implode( ', ', $line );

	$tail = array_filter( array( $region, $country ) );
	if ( $tail ) {
		$line .= ' · ' . implode( ', ', $tail );
	}
	return $line;
}

/**
 * Площадь с единицей. Пусто/0 → '' (блок сам покажет «—»).
 */
function rosenberger_propstack_area( int $post_id, string $key ): string {
	$v = (string) get_post_meta( $post_id, $key, true );
	$v = str_replace( ',', '.', $v );
	if ( '' === $v || (float) $v <= 0 ) {
		return '';
	}
	// Целые без дробной части — без хвоста.
	$num = ( (float) $v == (int) $v ) ? (string) (int) $v : rtrim( rtrim( number_format( (float) $v, 2, ',', '.' ), '0' ), ',' );
	return $num . ' m²';
}

/**
 * Карта `property_*` (наши ключи) → значение из `_property_*` плагина.
 * Возвращает null, если ключ не маппится (тогда WP читает мету как обычно).
 */
function rosenberger_propstack_map_value( int $post_id, string $key ) {
	$raw = fn( $k ) => get_post_meta( $post_id, $k, true );

	switch ( $key ) {
		case 'property_address':
			return rosenberger_propstack_address( $post_id );

		case 'property_status':
			$s = strtolower( (string) $raw( '_propstack_status' ) );
			if ( str_contains( $s, 'reserv' ) ) {
				return 'Reserviert';
			}
			if ( str_contains( $s, 'abgeschlossen' ) || str_contains( $s, 'verkauf' ) ) {
				return 'Verkauft';
			}
			return 'Verfügbar';

		case 'property_object_type':
			// Propstack /units не отдаёт Objektart отдельным полем — оставляем как есть.
			return (string) $raw( '_property_type' );
		case 'property_category':
			$cat = (string) $raw( '_property_category' );
			if ( '' !== $cat ) {
				return $cat;
			}
			// Из marketing_type (BUY/RENT) собираем человекочитаемую категорию.
			$mt = strtoupper( (string) $raw( '_property_marketing_type' ) );
			if ( str_contains( $mt, 'BUY' ) || str_contains( $mt, 'KAUF' ) || str_contains( $mt, 'PURCHASE' ) ) {
				return 'Kauf';
			}
			if ( str_contains( $mt, 'RENT' ) || str_contains( $mt, 'MIETE' ) ) {
				return 'Miete';
			}
			return '';
		case 'property_object_nr':
			return (string) $raw( '_property_object_number' );

		case 'property_price':
			$disp = (string) $raw( '_property_price_display' );
			if ( '' !== $disp ) {
				return $disp;
			}
			$p = (float) $raw( '_property_price' );
			return $p > 0 ? number_format( $p, 0, ',', '.' ) . ' €' : 'Auf Anfrage';
		case 'property_price_sub':
			$pps = $raw( '_property_price_per_sqm' );
			if ( '' === (string) $pps || (float) $pps <= 0 ) {
				return '';
			}
			return number_format( (float) $pps, 0, ',', '.' ) . ' €/m²';

		case 'property_area':
			return rosenberger_propstack_area( $post_id, '_property_living_area' );
		case 'property_plot_area':
			return rosenberger_propstack_area( $post_id, '_property_plot_area' );
		case 'property_usable_area':
			return rosenberger_propstack_area( $post_id, '_property_usable_area' );

		case 'property_rooms':
			$v = $raw( '_property_rooms' );
			return '' === (string) $v ? '' : (string) ( (float) $v == (int) $v ? (int) $v : $v );
		case 'property_bedrooms':
			return (string) $raw( '_property_bedrooms' );
		case 'property_bathrooms':
			return (string) $raw( '_property_bathrooms' );
		case 'property_toilets':
			return (string) $raw( '_property_toilets' );
		case 'property_floor':
			return (string) $raw( '_property_floor' );

		case 'property_lat':
			return (string) $raw( '_property_lat' );
		case 'property_lng':
			return (string) $raw( '_property_lng' );

		case 'property_short_desc':
			return (string) $raw( '_property_short_description' );

		// Аккордеоны — из текстовых полей плагина (где есть).
		case 'property_acc_condition':
			return (string) $raw( '_property_other_description' );
		case 'property_acc_equipment':
			return (string) $raw( '_property_equipment_description' );
		case 'property_acc_layout':
			return (string) $raw( '_property_long_description' );
		case 'property_acc_energy':
			return (string) $raw( '_property_energy_hwb' );

		// Галерея: ID вложений, импортированных плагином; иначе — featured image.
		case 'property_gallery':
			$g = $raw( '_property_gallery_ids' );
			if ( is_array( $g ) && $g ) {
				return implode( ',', array_map( 'absint', $g ) );
			}
			if ( is_string( $g ) && '' !== $g ) {
				return $g;
			}
			$thumb = get_post_thumbnail_id( $post_id );
			return $thumb ? (string) $thumb : '';
	}

	return null; // не маппим → обычное чтение (блок покажет «—»/пусто).
}

/**
 * Перехват чтения `property_*` для постов propstack_property.
 */
add_filter(
	'get_post_metadata',
	function ( $value, $object_id, $meta_key, $single ) {
		if ( '' === $meta_key || strncmp( $meta_key, 'property_', 9 ) !== 0 ) {
			return $value;
		}
		if ( 'propstack_property' !== get_post_type( $object_id ) ) {
			return $value;
		}
		$mapped = rosenberger_propstack_map_value( (int) $object_id, $meta_key );
		if ( null === $mapped ) {
			return $value;
		}
		// get_post_meta($id,$key,true) вернёт [0]; для $single=false — массив.
		return array( $mapped );
	},
	10,
	4
);
