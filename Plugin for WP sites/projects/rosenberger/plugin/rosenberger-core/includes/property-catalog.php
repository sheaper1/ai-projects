<?php
/**
 * Property Catalog — общий серверный рендер сетки + REST-эндпоинт для AJAX.
 *
 * Одна и та же функция rosenberger_pc_results_html() рендерит сетку и при
 * первичной загрузке (render.php блока), и при AJAX-фильтрации (REST). Разметка
 * гарантированно идентична — нет дубля шаблона в JS.
 *
 * @package rosenberger-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Поддержка числовых спутников <key>_num для фильтруемых полей: исходная мета —
 * человекочитаемая строка («ca. 130 m²», «€ 680.000»), а для range-фильтра нужно
 * чистое число. Хук обновляет _num при ЛЮБОЙ записи меты (мета-бокс, сид, REST).
 */
function rosenberger_pc_sync_numeric( $meta_id, $post_id, $key, $value ): void {
	static $fields = [ 'property_price' => 1, 'property_area' => 1, 'property_plot_area' => 1, 'property_rooms' => 1 ];
	if ( ! isset( $fields[ $key ] ) ) {
		return; // _num-ключи сюда не попадают — рекурсии нет.
	}
	if ( 'property_rooms' === $key ) {
		$num = (float) str_replace( ',', '.', preg_replace( '/[^0-9,.]/', '', (string) $value ) );
	} else {
		$num = (int) preg_replace( '/[^0-9]/', '', (string) $value );
	}
	update_post_meta( $post_id, $key . '_num', $num );
}
add_action( 'added_post_meta', 'rosenberger_pc_sync_numeric', 10, 4 );
add_action( 'updated_post_meta', 'rosenberger_pc_sync_numeric', 10, 4 );

/**
 * Числовые спутники для объектов из Propstack (CPT propstack_property): плагин
 * хранит чистые числа в `_property_*`, а каталог фильтрует по `property_*_num`.
 */
function rosenberger_pc_propstack_numeric( $meta_id, $post_id, $key, $value ): void {
	static $map = [
		'_property_price'        => 'property_price_num',
		'_property_living_area'  => 'property_area_num',
		'_property_plot_area'    => 'property_plot_area_num',
		'_property_rooms'        => 'property_rooms_num',
	];
	if ( ! isset( $map[ $key ] ) ) {
		return;
	}
	$raw = str_replace( ',', '.', (string) $value );
	$num = (float) preg_replace( '/[^0-9.]/', '', $raw );
	$num = ( '_property_rooms' === $key ) ? $num : (int) $num;
	update_post_meta( $post_id, $map[ $key ], $num );
}
add_action( 'added_post_meta', 'rosenberger_pc_propstack_numeric', 10, 4 );
add_action( 'updated_post_meta', 'rosenberger_pc_propstack_numeric', 10, 4 );

/**
 * Привести «сырой» источник (GET или REST-параметры) к каноничному набору фильтров.
 */
function rosenberger_pc_params( array $src, int $per_page = 9 ): array {
	$range = static function ( $min, $max ) {
		return [ absint( $min ?? 0 ), absint( $max ?? 0 ) ];
	};
	return [
		'art'      => sanitize_text_field( (string) ( $src['pc_art'] ?? '' ) ),
		'typ'      => isset( $src['pc_typ'] ) ? array_map( 'sanitize_text_field', (array) $src['pc_typ'] ) : [],
		'sort'     => sanitize_text_field( (string) ( $src['pc_sort'] ?? 'newest' ) ),
		'zimmer'   => $range( $src['pc_zimmer_min'] ?? 0,  $src['pc_zimmer_max'] ?? 0 ),
		'flaeche'  => $range( $src['pc_flaeche_min'] ?? 0, $src['pc_flaeche_max'] ?? 0 ),
		'grund'    => $range( $src['pc_grund_min'] ?? 0,   $src['pc_grund_max'] ?? 0 ),
		'preis'    => $range( $src['pc_preis_min'] ?? 0,   $src['pc_preis_max'] ?? 0 ),
		'page'     => max( 1, absint( $src['pc_page'] ?? 1 ) ),
		'per_page' => max( 1, absint( $src['pc_per_page'] ?? $per_page ) ),
	];
}

/**
 * WP_Query по каноничным параметрам фильтра.
 */
function rosenberger_pc_query( array $p ): WP_Query {
	$tax_query = [];
	if ( ! empty( $p['typ'] ) ) {
		$tax_query[] = [
			'taxonomy' => 'property-type',
			'field'    => 'slug',
			'terms'    => $p['typ'],
			'operator' => 'IN',
		];
	}

	$meta_query = [ 'relation' => 'AND' ];
	if ( $p['art'] ) {
		$meta_query[] = [ 'key' => 'property_listing_type', 'value' => $p['art'], 'compare' => '=' ];
	}
	// Фильтруем по числовым спутникам *_num (исходная мета — строки «ca. 130 m²»,
	// «€ 680.000», их NUMERIC-сравнение даёт 0). См. rosenberger_pc_sync_numeric().
	$ranges = [
		'property_rooms_num'     => $p['zimmer'],
		'property_area_num'      => $p['flaeche'],
		'property_plot_area_num' => $p['grund'],
		'property_price_num'     => $p['preis'],
	];
	foreach ( $ranges as $key => $r ) {
		if ( $r[0] || $r[1] ) {
			$meta_query[] = [
				'key'     => $key,
				'value'   => [ $r[0] ?: 0, $r[1] ?: PHP_INT_MAX ],
				'type'    => 'NUMERIC',
				'compare' => 'BETWEEN',
			];
		}
	}

	$order_map = [
		'newest'     => [ 'orderby' => 'date',           'order' => 'DESC' ],
		'oldest'     => [ 'orderby' => 'date',           'order' => 'ASC' ],
		'price_asc'  => [ 'orderby' => 'meta_value_num', 'order' => 'ASC',  'meta_key' => 'property_price_num' ],
		'price_desc' => [ 'orderby' => 'meta_value_num', 'order' => 'DESC', 'meta_key' => 'property_price_num' ],
	];

	return new WP_Query( array_merge(
		[
			// property — старый CPT, propstack_property — объекты из Propstack-синка.
			'post_type'      => [ 'property', 'propstack_property' ],
			'posts_per_page' => $p['per_page'],
			'paged'          => $p['page'],
			'tax_query'      => $tax_query,
			'meta_query'     => count( $meta_query ) > 1 ? $meta_query : [],
		],
		$order_map[ $p['sort'] ] ?? $order_map['newest']
	) );
}

/**
 * Разметка одной карточки объекта (общая для compact и catalog).
 */
function rosenberger_pc_card_html( int $post_id ): string {
	$price      = get_post_meta( $post_id, 'property_price', true );
	$plot       = get_post_meta( $post_id, 'property_plot_area', true );
	$rooms      = get_post_meta( $post_id, 'property_rooms', true );
	$lage_tax   = get_the_terms( $post_id, 'property-city' );
	$lage_str   = ( $lage_tax && ! is_wp_error( $lage_tax ) )
		? implode( ', ', wp_list_pluck( $lage_tax, 'name' ) )
		: 'Vorarlberg | Österreich';
	$thumb_id   = get_post_thumbnail_id( $post_id );
	$thumb_src  = $thumb_id ? wp_get_attachment_image_url( $thumb_id, 'large' ) : '';
	$excerpt    = get_the_excerpt( $post_id );

	ob_start();
	?>
	<article class="pc-card">
		<div class="pc-card__img-wrap">
			<?php if ( $thumb_src ) : ?>
				<img class="pc-card__img" src="<?php echo esc_url( $thumb_src ); ?>" alt="<?php echo esc_attr( get_the_title( $post_id ) ); ?>" loading="lazy">
			<?php else : ?>
				<div class="pc-card__img-placeholder"></div>
			<?php endif; ?>
		</div>
		<div class="pc-card__body">
			<div class="pc-card__top">
				<h3 class="pc-card__title"><?php echo esc_html( get_the_title( $post_id ) ); ?></h3>
				<?php if ( $excerpt ) : ?>
					<p class="pc-card__excerpt"><?php echo esc_html( $excerpt ); ?></p>
				<?php endif; ?>
			</div>
			<dl class="pc-card__meta">
				<div class="pc-meta-item"><dt>Lage</dt><dd><?php echo esc_html( $lage_str ); ?></dd></div>
				<div class="pc-meta-item"><dt>Kaufpreis</dt><dd><?php echo $price ? esc_html( $price ) : 'Auf Anfrage'; ?></dd></div>
				<div class="pc-meta-item"><dt>Grundstücksfläche</dt><dd><?php echo $plot ? esc_html( $plot ) : '—'; ?></dd></div>
				<div class="pc-meta-item"><dt>Zimmer</dt><dd><?php echo $rooms ? esc_html( $rooms ) : '—'; ?></dd></div>
			</dl>
			<a class="pc-card__link" href="<?php echo esc_url( get_permalink( $post_id ) ); ?>">Erfahren Sie mehr →</a>
		</div>
	</article>
	<?php
	return (string) ob_get_clean();
}

/**
 * Разметка пагинации (ссылки сохраняют фильтр через query string; JS перехватывает по data-page).
 */
function rosenberger_pc_pagination_html( WP_Query $query, array $p ): string {
	$total = (int) $query->max_num_pages;
	if ( $total <= 1 ) {
		return '';
	}
	$current = $p['page'];
	$href    = static function ( int $page ) use ( $p ) {
		$qs = array_filter( [
			'pc_art'        => $p['art'],
			'pc_typ'        => $p['typ'],
			'pc_sort'       => 'newest' !== $p['sort'] ? $p['sort'] : '',
			'pc_zimmer_min' => $p['zimmer'][0] ?: '', 'pc_zimmer_max' => $p['zimmer'][1] ?: '',
			'pc_flaeche_min'=> $p['flaeche'][0] ?: '', 'pc_flaeche_max'=> $p['flaeche'][1] ?: '',
			'pc_grund_min'  => $p['grund'][0] ?: '',  'pc_grund_max'  => $p['grund'][1] ?: '',
			'pc_preis_min'  => $p['preis'][0] ?: '',  'pc_preis_max'  => $p['preis'][1] ?: '',
			'pc_page'       => $page,
		], static fn( $v ) => '' !== $v && [] !== $v );
		return '?' . http_build_query( $qs );
	};

	ob_start();
	echo '<nav class="pc-pagination" aria-label="Seiten">';
	if ( $current > 1 ) {
		printf( '<a class="pc-page-btn pc-page-btn--arrow" href="%s" data-page="%d" aria-label="Vorherige Seite"><svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M12.5 15 7.5 10l5-5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg></a>', esc_url( $href( $current - 1 ) ), $current - 1 );
	}
	$window = 2;
	for ( $i = 1; $i <= $total; $i++ ) {
		if ( $i > 1 && $i < $total && abs( $i - $current ) > $window ) {
			if ( $i === $current - $window - 1 || $i === $current + $window + 1 ) {
				echo '<span class="pc-page-ellipsis">…</span>';
			}
			continue;
		}
		printf(
			'<a class="pc-page-btn%s" href="%s" data-page="%d"%s>%d</a>',
			$i === $current ? ' is-active' : '',
			esc_url( $href( $i ) ),
			$i,
			$i === $current ? ' aria-current="page"' : '',
			$i
		);
	}
	if ( $current < $total ) {
		printf( '<a class="pc-page-btn pc-page-btn--arrow" href="%s" data-page="%d" aria-label="Nächste Seite"><svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M7.5 5 12.5 10 7.5 15" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg></a>', esc_url( $href( $current + 1 ) ), $current + 1 );
	}
	echo '</nav>';
	return (string) ob_get_clean();
}

/**
 * Содержимое подменяемой области .pc-results: сетка карточек + пагинация.
 * Это то, что отдаёт и первичный рендер, и AJAX.
 */
function rosenberger_pc_results_html( array $p ): string {
	$query = rosenberger_pc_query( $p );
	ob_start();
	if ( $query->have_posts() ) {
		echo '<div class="pc-grid">';
		while ( $query->have_posts() ) {
			$query->the_post();
			echo rosenberger_pc_card_html( get_the_ID() );
		}
		wp_reset_postdata();
		echo '</div>';
		echo rosenberger_pc_pagination_html( $query, $p );
	} else {
		echo '<p class="pc-empty">Keine Objekte gefunden.</p>';
	}
	return (string) ob_get_clean();
}

/**
 * REST: GET /wp-json/rosenberger/v1/properties — отдаёт { html } сетки по фильтрам.
 */
add_action( 'rest_api_init', function () {
	register_rest_route( 'rosenberger/v1', '/properties', [
		'methods'             => 'GET',
		'permission_callback' => '__return_true',
		'callback'            => function ( WP_REST_Request $req ) {
			$p = rosenberger_pc_params( $req->get_params() );
			return new WP_REST_Response( [ 'html' => rosenberger_pc_results_html( $p ) ], 200 );
		},
	] );
} );
