<?php
/**
 * Reference Catalog — серверный рендер сетки референсов + REST-эндпоинт для AJAX.
 *
 * Одна функция rosenberger_rc_results_html() рендерит сетку и при первичной
 * загрузке (render.php блока), и при AJAX (REST) — разметка идентична, без дубля
 * шаблона в JS. Фильтр: Typ (tabs) + Lage (Ort) + Sortierung + пагинация.
 *
 * Числовые спутники *_num для property_price/rooms/area/plot обновляет общий хук
 * из property-catalog.php (rosenberger_pc_sync_numeric) — он реагирует на запись
 * меты любого типа записи, поэтому референсы тоже индексируются.
 *
 * @package rosenberger-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Привести «сырой» источник (GET или REST) к каноничному набору фильтров.
 */
function rosenberger_rc_params( array $src, int $per_page = 8 ): array {
	return array(
		'typ'      => sanitize_text_field( (string) ( $src['rc_typ'] ?? '' ) ),
		'ort'      => sanitize_text_field( (string) ( $src['rc_ort'] ?? '' ) ),
		'sort'     => sanitize_text_field( (string) ( $src['rc_sort'] ?? 'newest' ) ),
		'page'     => max( 1, absint( $src['rc_page'] ?? 1 ) ),
		'per_page' => max( 1, absint( $src['rc_per_page'] ?? $per_page ) ),
	);
}

/**
 * WP_Query по каноничным параметрам фильтра.
 */
function rosenberger_rc_query( array $p ): WP_Query {
	$tax_query = array();
	if ( $p['typ'] ) {
		$tax_query[] = array( 'taxonomy' => 'reference-type', 'field' => 'slug', 'terms' => $p['typ'] );
	}
	if ( $p['ort'] ) {
		$tax_query[] = array( 'taxonomy' => 'reference-city', 'field' => 'slug', 'terms' => $p['ort'] );
	}
	if ( count( $tax_query ) > 1 ) {
		$tax_query['relation'] = 'AND';
	}

	$order_map = array(
		'newest'     => array( 'orderby' => 'date', 'order' => 'DESC' ),
		'oldest'     => array( 'orderby' => 'date', 'order' => 'ASC' ),
		'price_asc'  => array( 'orderby' => 'meta_value_num', 'order' => 'ASC',  'meta_key' => 'property_price_num' ),
		'price_desc' => array( 'orderby' => 'meta_value_num', 'order' => 'DESC', 'meta_key' => 'property_price_num' ),
	);

	return new WP_Query( array_merge(
		array(
			'post_type'      => 'reference',
			'posts_per_page' => $p['per_page'],
			'paged'          => $p['page'],
			'tax_query'      => $tax_query,
		),
		$order_map[ $p['sort'] ] ?? $order_map['newest']
	) );
}

/**
 * Разметка одной карточки референса (изображение + заголовок + текст + 2×2 мета).
 */
function rosenberger_rc_card_html( int $post_id ): string {
	$price     = get_post_meta( $post_id, 'property_price', true );
	$plot      = get_post_meta( $post_id, 'property_plot_area', true );
	$rooms     = get_post_meta( $post_id, 'property_rooms', true );
	$lage_tax  = get_the_terms( $post_id, 'reference-city' );
	$lage_str  = ( $lage_tax && ! is_wp_error( $lage_tax ) )
		? implode( ', ', wp_list_pluck( $lage_tax, 'name' ) )
		: ( get_post_meta( $post_id, 'property_address', true ) ?: 'Vorarlberg | Österreich' );
	$thumb_id  = get_post_thumbnail_id( $post_id );
	$thumb_src = $thumb_id ? wp_get_attachment_image_url( $thumb_id, 'large' ) : '';
	$excerpt   = get_the_excerpt( $post_id );
	$link      = get_permalink( $post_id );

	ob_start();
	?>
	<a class="rc-card" href="<?php echo esc_url( $link ); ?>">
		<div class="rc-card__img-wrap">
			<?php if ( $thumb_src ) : ?>
				<img class="rc-card__img" src="<?php echo esc_url( $thumb_src ); ?>" alt="<?php echo esc_attr( get_the_title( $post_id ) ); ?>" loading="lazy">
			<?php else : ?>
				<div class="rc-card__img-placeholder"></div>
			<?php endif; ?>
		</div>
		<div class="rc-card__body">
			<div class="rc-card__top">
				<h3 class="rc-card__title"><?php echo esc_html( get_the_title( $post_id ) ); ?></h3>
				<?php if ( $excerpt ) : ?>
					<p class="rc-card__excerpt"><?php echo esc_html( $excerpt ); ?></p>
				<?php endif; ?>
			</div>
			<dl class="rc-card__meta">
				<div class="rc-meta-item"><dt>Lage</dt><dd><?php echo esc_html( $lage_str ); ?></dd></div>
				<div class="rc-meta-item"><dt>Kaufpreis</dt><dd><?php echo $price ? esc_html( $price ) : 'Auf Anfrage'; ?></dd></div>
				<div class="rc-meta-item"><dt>Grundstücksfläche</dt><dd><?php echo $plot ? esc_html( $plot ) : '—'; ?></dd></div>
				<div class="rc-meta-item"><dt>Zimmer</dt><dd><?php echo $rooms ? esc_html( $rooms ) : '—'; ?></dd></div>
			</dl>
		</div>
	</a>
	<?php
	return (string) ob_get_clean();
}

/**
 * Пагинация (ссылки сохраняют фильтр; JS перехватывает по data-page).
 */
function rosenberger_rc_pagination_html( WP_Query $query, array $p ): string {
	$total = (int) $query->max_num_pages;
	if ( $total <= 1 ) {
		return '';
	}
	$current = $p['page'];
	$href    = static function ( int $page ) use ( $p ) {
		$qs = array_filter( array(
			'rc_typ'  => $p['typ'],
			'rc_ort'  => $p['ort'],
			'rc_sort' => 'newest' !== $p['sort'] ? $p['sort'] : '',
			'rc_page' => $page,
		), static fn( $v ) => '' !== $v );
		return '?' . http_build_query( $qs );
	};

	ob_start();
	echo '<nav class="rc-pagination" aria-label="Seiten">';
	if ( $current > 1 ) {
		printf( '<a class="rc-page-btn rc-page-btn--arrow" href="%s" data-page="%d" aria-label="Vorherige Seite"><svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M12.5 15 7.5 10l5-5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg></a>', esc_url( $href( $current - 1 ) ), $current - 1 );
	}
	$window = 2;
	for ( $i = 1; $i <= $total; $i++ ) {
		if ( $i > 1 && $i < $total && abs( $i - $current ) > $window ) {
			if ( $i === $current - $window - 1 || $i === $current + $window + 1 ) {
				echo '<span class="rc-page-ellipsis">…</span>';
			}
			continue;
		}
		printf(
			'<a class="rc-page-btn%s" href="%s" data-page="%d"%s>%d</a>',
			$i === $current ? ' is-active' : '',
			esc_url( $href( $i ) ),
			$i,
			$i === $current ? ' aria-current="page"' : '',
			$i
		);
	}
	if ( $current < $total ) {
		printf( '<a class="rc-page-btn rc-page-btn--arrow" href="%s" data-page="%d" aria-label="Nächste Seite"><svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M7.5 5 12.5 10 7.5 15" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg></a>', esc_url( $href( $current + 1 ) ), $current + 1 );
	}
	echo '</nav>';
	return (string) ob_get_clean();
}

/**
 * Содержимое подменяемой области .rc-results: сетка карточек + пагинация.
 */
function rosenberger_rc_results_html( array $p ): string {
	$query = rosenberger_rc_query( $p );
	ob_start();
	if ( $query->have_posts() ) {
		echo '<div class="rc-grid">';
		while ( $query->have_posts() ) {
			$query->the_post();
			echo rosenberger_rc_card_html( get_the_ID() );
		}
		wp_reset_postdata();
		echo '</div>';
		echo rosenberger_rc_pagination_html( $query, $p );
	} else {
		echo '<p class="rc-empty">Keine Referenzen gefunden.</p>';
	}
	return (string) ob_get_clean();
}

/**
 * REST: GET /wp-json/rosenberger/v1/references — { html } сетки по фильтрам.
 */
add_action( 'rest_api_init', function () {
	register_rest_route( 'rosenberger/v1', '/references', array(
		'methods'             => 'GET',
		'permission_callback' => '__return_true',
		'callback'            => function ( WP_REST_Request $req ) {
			$p = rosenberger_rc_params( $req->get_params() );
			return new WP_REST_Response( array( 'html' => rosenberger_rc_results_html( $p ) ), 200 );
		},
	) );
} );
