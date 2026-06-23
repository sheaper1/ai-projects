<?php
/**
 * Property Catalog - server-side render.
 *
 * Supports two layouts via the 'layout' attribute:
 *   'compact'  – header + inline filters + grid + CTA (homepage usage)
 *   'catalog'  – sidebar filters + full grid + pagination (all-immobilien page)
 *
 * @package rosenberger-core
 */

defined( 'ABSPATH' ) || exit;

$posts_per_page = (int) ( $attributes['postsPerPage'] ?? 9 );
$archive_url    = esc_url( $attributes['archiveUrl'] ?? '/alle-immobilien/' );
$heading        = wp_kses_post( $attributes['heading'] ?? 'Aktuelle Objekte' );
$heading_italic = wp_kses_post( $attributes['headingItalic'] ?? 'in Vorarlberg' );
$subtext        = wp_kses_post( $attributes['subtext'] ?? '' );
$layout         = sanitize_key( $attributes['layout'] ?? 'compact' );

// ── Filter params ─────────────────────────────────────────────────────────────
$filter_lage  = sanitize_text_field( wp_unslash( $_GET['pc_lage'] ?? '' ) );
$filter_sort  = sanitize_text_field( wp_unslash( $_GET['pc_sort'] ?? 'newest' ) );

// Checkbox multi-select for Objektart (property-type taxonomy)
$filter_typen = isset( $_GET['pc_typ'] ) ? array_map( 'sanitize_text_field', (array) wp_unslash( $_GET['pc_typ'] ) ) : [];

// Kaufen/Mieten toggle
$filter_art   = sanitize_text_field( wp_unslash( $_GET['pc_art'] ?? '' ) );

// Range filters (numeric)
$min_zimmer  = isset( $_GET['pc_zimmer_min'] ) ? absint( $_GET['pc_zimmer_min'] ) : 0;
$max_zimmer  = isset( $_GET['pc_zimmer_max'] ) ? absint( $_GET['pc_zimmer_max'] ) : 0;
$min_flaeche = isset( $_GET['pc_flaeche_min'] ) ? absint( $_GET['pc_flaeche_min'] ) : 0;
$max_flaeche = isset( $_GET['pc_flaeche_max'] ) ? absint( $_GET['pc_flaeche_max'] ) : 0;
$min_grund   = isset( $_GET['pc_grund_min'] ) ? absint( $_GET['pc_grund_min'] ) : 0;
$max_grund   = isset( $_GET['pc_grund_max'] ) ? absint( $_GET['pc_grund_max'] ) : 0;
$min_preis   = isset( $_GET['pc_preis_min'] ) ? absint( $_GET['pc_preis_min'] ) : 0;
$max_preis   = isset( $_GET['pc_preis_max'] ) ? absint( $_GET['pc_preis_max'] ) : 0;

// Current page for pagination
$paged = max( 1, absint( $_GET['pc_page'] ?? 1 ) );

// ── Build queries ─────────────────────────────────────────────────────────────
$tax_query = [];

if ( $filter_lage ) {
	$tax_query[] = [
		'taxonomy' => 'property-city',
		'field'    => 'slug',
		'terms'    => $filter_lage,
	];
}

if ( ! empty( $filter_typen ) ) {
	$tax_query[] = [
		'taxonomy' => 'property-type',
		'field'    => 'slug',
		'terms'    => $filter_typen,
		'operator' => 'IN',
	];
}

$meta_query = [ 'relation' => 'AND' ];

if ( $filter_art ) {
	$meta_query[] = [
		'key'     => 'property_listing_type',
		'value'   => $filter_art,
		'compare' => '=',
	];
}

if ( $min_zimmer || $max_zimmer ) {
	$meta_query[] = [
		'key'     => 'property_rooms',
		'value'   => [ $min_zimmer ?: 0, $max_zimmer ?: 9999 ],
		'type'    => 'NUMERIC',
		'compare' => 'BETWEEN',
	];
}

if ( $min_flaeche || $max_flaeche ) {
	$meta_query[] = [
		'key'     => 'property_area',
		'value'   => [ $min_flaeche ?: 0, $max_flaeche ?: 9999999 ],
		'type'    => 'NUMERIC',
		'compare' => 'BETWEEN',
	];
}

if ( $min_grund || $max_grund ) {
	$meta_query[] = [
		'key'     => 'property_plot_area',
		'value'   => [ $min_grund ?: 0, $max_grund ?: 9999999 ],
		'type'    => 'NUMERIC',
		'compare' => 'BETWEEN',
	];
}

if ( $min_preis || $max_preis ) {
	$meta_query[] = [
		'key'     => 'property_price',
		'value'   => [ $min_preis ?: 0, $max_preis ?: 99999999 ],
		'type'    => 'NUMERIC',
		'compare' => 'BETWEEN',
	];
}

$order_map = [
	'newest'      => [ 'orderby' => 'date',          'order' => 'DESC' ],
	'oldest'      => [ 'orderby' => 'date',          'order' => 'ASC' ],
	'price_asc'   => [ 'orderby' => 'meta_value_num', 'order' => 'ASC',  'meta_key' => 'property_price' ],
	'price_desc'  => [ 'orderby' => 'meta_value_num', 'order' => 'DESC', 'meta_key' => 'property_price' ],
];
$order_args = $order_map[ $filter_sort ] ?? $order_map['newest'];

$query_args = array_merge(
	[
		'post_type'      => 'property',
		'posts_per_page' => $posts_per_page,
		'paged'          => 'catalog' === $layout ? $paged : 1,
		'tax_query'      => $tax_query,
		'meta_query'     => count( $meta_query ) > 1 ? $meta_query : [],
	],
	$order_args
);

$query = new WP_Query( $query_args );

// Term lists for filters
$typ_terms  = get_terms( [ 'taxonomy' => 'property-type', 'hide_empty' => false ] );
$lage_terms = get_terms( [ 'taxonomy' => 'property-city', 'hide_empty' => false ] );

$wrapper_attrs = get_block_wrapper_attributes( [
	'class' => 'property-catalog property-catalog--' . esc_attr( $layout ),
] );

// Helper: build a URL preserving current GET params but replacing/adding given ones
if ( ! function_exists( 'pc_url' ) ) {
	function pc_url( array $overrides ): string {
		$params = array_merge( $_GET, $overrides );
		$params = array_filter( $params, fn( $v ) => '' !== $v && [] !== $v && 0 !== $v );
		return esc_url( add_query_arg( $params, get_permalink() ) );
	}
}
?>

<section <?php echo $wrapper_attrs; ?>>
<?php if ( 'catalog' === $layout ) : ?>
	<!-- ── CATALOG LAYOUT (sidebar + grid) ── -->
	<div class="pc-wrap">

		<div class="pc-top">
			<h2 class="pc-heading">
				<?php echo $heading; ?>
				<?php if ( $heading_italic ) : ?>
					<br aria-hidden="true">
					<em><?php echo $heading_italic; ?></em>
				<?php endif; ?>
			</h2>
		</div>

		<div class="pc-body">

			<!-- Sidebar -->
			<aside class="pc-sidebar" aria-label="Filter">
				<form class="pc-filter-form" method="get" action="<?php echo esc_url( get_permalink() ); ?>">
					<input type="hidden" name="pc_sort" value="<?php echo esc_attr( $filter_sort ); ?>">

					<!-- Kaufen / Mieten toggle -->
					<div class="pc-toggle" role="group" aria-label="Angebotsart">
						<label class="pc-toggle__option<?php echo ( 'mieten' !== $filter_art ) ? ' is-active' : ''; ?>">
							<input type="radio" name="pc_art" value=""
								<?php checked( 'mieten' !== $filter_art ); ?>
								onchange="this.form.submit()">
							Kaufen
						</label>
						<label class="pc-toggle__option<?php echo ( 'mieten' === $filter_art ) ? ' is-active' : ''; ?>">
							<input type="radio" name="pc_art" value="mieten"
								<?php checked( 'mieten', $filter_art ); ?>
								onchange="this.form.submit()">
							Mieten
						</label>
					</div>

					<p class="pc-sidebar__label">Filter</p>

					<!-- Objektart checkboxes -->
					<fieldset class="pc-filter-group">
						<legend class="pc-filter-group__title">Objektart</legend>
						<div class="pc-checkboxes">
							<?php foreach ( (array) $typ_terms as $term ) : ?>
								<label class="pc-checkbox">
									<span class="pc-checkbox__box">
										<input type="checkbox" name="pc_typ[]"
											value="<?php echo esc_attr( $term->slug ); ?>"
											<?php checked( in_array( $term->slug, $filter_typen, true ) ); ?>>
										<span class="pc-checkbox__check" aria-hidden="true"></span>
									</span>
									<span class="pc-checkbox__text">
										<?php echo esc_html( $term->name ); ?>
										<span class="pc-checkbox__count">( <?php echo (int) $term->count; ?> )</span>
									</span>
								</label>
							<?php endforeach; ?>
						</div>
					</fieldset>

					<!-- Zimmer range -->
					<fieldset class="pc-filter-group">
						<legend class="pc-filter-group__title">Zimmer</legend>
						<div class="pc-range">
							<div class="pc-range__field">
								<label class="pc-range__label" for="pc_zimmer_min">von</label>
								<input class="pc-range__input" type="number" id="pc_zimmer_min" name="pc_zimmer_min"
									min="0" placeholder="0"
									value="<?php echo $min_zimmer ?: ''; ?>">
							</div>
							<span class="pc-range__sep" aria-hidden="true">—</span>
							<div class="pc-range__field">
								<label class="pc-range__label" for="pc_zimmer_max">bis</label>
								<input class="pc-range__input" type="number" id="pc_zimmer_max" name="pc_zimmer_max"
									min="0" placeholder="∞"
									value="<?php echo $max_zimmer ?: ''; ?>">
							</div>
						</div>
					</fieldset>

					<!-- Wohnfläche range -->
					<fieldset class="pc-filter-group">
						<legend class="pc-filter-group__title">Wohnfläche m²</legend>
						<div class="pc-range">
							<div class="pc-range__field">
								<label class="pc-range__label" for="pc_flaeche_min">von</label>
								<input class="pc-range__input" type="number" id="pc_flaeche_min" name="pc_flaeche_min"
									min="0" placeholder="0"
									value="<?php echo $min_flaeche ?: ''; ?>">
							</div>
							<span class="pc-range__sep" aria-hidden="true">—</span>
							<div class="pc-range__field">
								<label class="pc-range__label" for="pc_flaeche_max">bis</label>
								<input class="pc-range__input" type="number" id="pc_flaeche_max" name="pc_flaeche_max"
									min="0" placeholder="∞"
									value="<?php echo $max_flaeche ?: ''; ?>">
							</div>
						</div>
					</fieldset>

					<!-- Grundstücksfläche range -->
					<fieldset class="pc-filter-group">
						<legend class="pc-filter-group__title">Grundstücksfläche m²</legend>
						<div class="pc-range">
							<div class="pc-range__field">
								<label class="pc-range__label" for="pc_grund_min">von</label>
								<input class="pc-range__input" type="number" id="pc_grund_min" name="pc_grund_min"
									min="0" placeholder="0"
									value="<?php echo $min_grund ?: ''; ?>">
							</div>
							<span class="pc-range__sep" aria-hidden="true">—</span>
							<div class="pc-range__field">
								<label class="pc-range__label" for="pc_grund_max">bis</label>
								<input class="pc-range__input" type="number" id="pc_grund_max" name="pc_grund_max"
									min="0" placeholder="∞"
									value="<?php echo $max_grund ?: ''; ?>">
							</div>
						</div>
					</fieldset>

					<!-- Preis range -->
					<fieldset class="pc-filter-group">
						<legend class="pc-filter-group__title">Preis</legend>
						<div class="pc-range">
							<div class="pc-range__field">
								<label class="pc-range__label" for="pc_preis_min">von</label>
								<input class="pc-range__input" type="number" id="pc_preis_min" name="pc_preis_min"
									min="0" placeholder="0"
									value="<?php echo $min_preis ?: ''; ?>">
							</div>
							<span class="pc-range__sep" aria-hidden="true">—</span>
							<div class="pc-range__field">
								<label class="pc-range__label" for="pc_preis_max">bis</label>
								<input class="pc-range__input" type="number" id="pc_preis_max" name="pc_preis_max"
									min="0" placeholder="∞"
									value="<?php echo $max_preis ?: ''; ?>">
							</div>
						</div>
					</fieldset>

					<button type="submit" class="pc-filter-btn">Filtern</button>

					<?php
					$has_filters = ! empty( $filter_typen ) || $filter_art || $min_zimmer || $max_zimmer ||
						$min_flaeche || $max_flaeche || $min_grund || $max_grund || $min_preis || $max_preis;
					if ( $has_filters ) :
					?>
						<a class="pc-filter-reset" href="<?php echo esc_url( get_permalink() ); ?>">
							<svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true">
								<path d="M2 8a6 6 0 1 0 6-6 6 6 0 0 0-4.243 1.757" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
								<path d="M2 3.5V8h4.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
							</svg>
							Filter zurücksetzen
						</a>
					<?php endif; ?>
				</form>
			</aside>

			<!-- Main grid area -->
			<div class="pc-main">

				<!-- Sort bar -->
				<div class="pc-sort-bar">
					<form class="pc-sort-form" method="get" action="<?php echo esc_url( get_permalink() ); ?>">
						<?php foreach ( $_GET as $k => $v ) : ?>
							<?php if ( 'pc_sort' !== $k && 'pc_page' !== $k ) : ?>
								<?php if ( is_array( $v ) ) : ?>
									<?php foreach ( $v as $vi ) : ?>
										<input type="hidden" name="<?php echo esc_attr( $k ); ?>[]" value="<?php echo esc_attr( $vi ); ?>">
									<?php endforeach; ?>
								<?php else : ?>
									<input type="hidden" name="<?php echo esc_attr( $k ); ?>" value="<?php echo esc_attr( $v ); ?>">
								<?php endif; ?>
							<?php endif; ?>
						<?php endforeach; ?>
						<label class="pc-sort-label" for="pc_sort_select">
							<svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true">
								<path d="M4 6h12M6 10h8M8 14h4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
							</svg>
							Sortieren nach
						</label>
						<select class="pc-sort-select" id="pc_sort_select" name="pc_sort"
							aria-label="Sortieren nach" onchange="this.form.submit()">
							<option value="newest" <?php selected( $filter_sort, 'newest' ); ?>>Neueste zuerst</option>
							<option value="oldest" <?php selected( $filter_sort, 'oldest' ); ?>>Älteste zuerst</option>
							<option value="price_asc" <?php selected( $filter_sort, 'price_asc' ); ?>>Preis aufsteigend</option>
							<option value="price_desc" <?php selected( $filter_sort, 'price_desc' ); ?>>Preis absteigend</option>
						</select>
					</form>
				</div>

				<!-- Property grid -->
				<?php if ( $query->have_posts() ) : ?>
					<div class="pc-grid">
						<?php while ( $query->have_posts() ) : $query->the_post(); ?>
							<?php
							$post_id   = get_the_ID();
							$price     = get_post_meta( $post_id, 'property_price', true );
							$area      = get_post_meta( $post_id, 'property_area', true );
							$rooms     = get_post_meta( $post_id, 'property_rooms', true );
							$lage_tax  = get_the_terms( $post_id, 'property-city' );
							$lage_str  = ( $lage_tax && ! is_wp_error( $lage_tax ) )
								? implode( ', ', wp_list_pluck( $lage_tax, 'name' ) )
								: 'Vorarlberg | Österreich';
							$thumb_id  = get_post_thumbnail_id( $post_id );
							$thumb_src = $thumb_id ? wp_get_attachment_image_url( $thumb_id, 'large' ) : '';
							?>
							<article class="pc-card">
								<div class="pc-card__img-wrap">
									<?php if ( $thumb_src ) : ?>
										<img class="pc-card__img"
											src="<?php echo esc_url( $thumb_src ); ?>"
											alt="<?php echo esc_attr( get_the_title() ); ?>"
											loading="lazy">
									<?php else : ?>
										<div class="pc-card__img-placeholder"></div>
									<?php endif; ?>
								</div>
								<div class="pc-card__body">
									<div class="pc-card__top">
										<h3 class="pc-card__title"><?php the_title(); ?></h3>
										<?php $excerpt = get_the_excerpt(); if ( $excerpt ) : ?>
											<p class="pc-card__excerpt"><?php echo esc_html( $excerpt ); ?></p>
										<?php endif; ?>
									</div>
									<dl class="pc-card__meta">
										<div class="pc-meta-item">
											<dt>Lage</dt>
											<dd><?php echo esc_html( $lage_str ); ?></dd>
										</div>
										<div class="pc-meta-item">
											<dt>Kaufpreis</dt>
											<dd><?php echo $price ? esc_html( $price ) : 'Auf Anfrage'; ?></dd>
										</div>
										<div class="pc-meta-item">
											<dt>Grundstücksfläche</dt>
											<dd><?php echo $area ? esc_html( $area ) : '—'; ?></dd>
										</div>
										<div class="pc-meta-item">
											<dt>Zimmer</dt>
											<dd><?php echo $rooms ? esc_html( $rooms ) : '—'; ?></dd>
										</div>
									</dl>
									<a class="pc-card__link" href="<?php the_permalink(); ?>">Erfahren Sie mehr →</a>
								</div>
							</article>
						<?php endwhile; ?>
						<?php wp_reset_postdata(); ?>
					</div>

					<!-- Pagination -->
					<?php
					$total_pages = $query->max_num_pages;
					if ( $total_pages > 1 ) :
						$current = $paged;
						$base_url = get_permalink();
						// Preserve all GET params except pc_page
						$base_params = $_GET;
						unset( $base_params['pc_page'] );

						if ( ! function_exists( 'pc_page_url' ) ) {
							function pc_page_url( int $page, array $params, string $base ): string {
								$p = array_merge( $params, [ 'pc_page' => $page ] );
								return esc_url( add_query_arg( $p, $base ) );
							}
						}
					?>
					<nav class="pc-pagination" aria-label="Seiten">
						<?php if ( $current > 1 ) : ?>
							<a class="pc-page-btn pc-page-btn--arrow" href="<?php echo pc_page_url( $current - 1, $base_params, $base_url ); ?>" aria-label="Vorherige Seite">
								<svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M12.5 15 7.5 10l5-5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
							</a>
						<?php endif; ?>

						<?php
						$window = 2;
						for ( $p = 1; $p <= $total_pages; $p++ ) :
							if ( $p > 1 && $p < $total_pages && abs( $p - $current ) > $window ) :
								if ( $p === $current - $window - 1 || $p === $current + $window + 1 ) : ?>
									<span class="pc-page-ellipsis">…</span>
								<?php endif; ?>
								<?php continue; ?>
							<?php endif; ?>
							<a class="pc-page-btn<?php echo $p === $current ? ' is-active' : ''; ?>"
								href="<?php echo pc_page_url( $p, $base_params, $base_url ); ?>"
								<?php echo $p === $current ? 'aria-current="page"' : ''; ?>>
								<?php echo $p; ?>
							</a>
						<?php endfor; ?>

						<?php if ( $current < $total_pages ) : ?>
							<a class="pc-page-btn pc-page-btn--arrow" href="<?php echo pc_page_url( $current + 1, $base_params, $base_url ); ?>" aria-label="Nächste Seite">
								<svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M7.5 5 12.5 10 7.5 15" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
							</a>
						<?php endif; ?>
					</nav>
					<?php endif; ?>

				<?php else : ?>
					<p class="pc-empty">Keine Objekte gefunden.</p>
				<?php endif; ?>

			</div><!-- .pc-main -->
		</div><!-- .pc-body -->
	</div><!-- .pc-wrap -->

<?php else : ?>
	<!-- ── COMPACT LAYOUT (homepage section) ── -->
	<div class="pc-inner">
		<header class="pc-header">
			<h2 class="pc-heading">
				<?php echo $heading; ?>
				<?php if ( $heading_italic ) : ?>
					<em><?php echo $heading_italic; ?></em>
				<?php endif; ?>
			</h2>
			<?php if ( $subtext ) : ?>
				<p class="pc-subtext"><?php echo $subtext; ?></p>
			<?php endif; ?>
		</header>

		<form class="pc-filters" method="get" action="<?php echo esc_url( get_permalink() ); ?>">
			<div class="pc-filter">
				<select name="pc_lage" aria-label="Lage">
					<option value="">Lage</option>
					<?php foreach ( (array) $lage_terms as $term ) : ?>
						<option value="<?php echo esc_attr( $term->slug ); ?>" <?php selected( $filter_lage, $term->slug ); ?>>
							<?php echo esc_html( $term->name ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="pc-filter pc-filter--sort">
				<select name="pc_sort" aria-label="Sortieren nach">
					<option value="newest" <?php selected( $filter_sort, 'newest' ); ?>>Neueste zuerst</option>
					<option value="oldest" <?php selected( $filter_sort, 'oldest' ); ?>>Älteste zuerst</option>
				</select>
			</div>
		</form>

		<?php if ( $query->have_posts() ) : ?>
			<div class="pc-grid">
				<?php while ( $query->have_posts() ) : $query->the_post(); ?>
					<?php
					$post_id   = get_the_ID();
					$price     = get_post_meta( $post_id, 'property_price', true );
					$area      = get_post_meta( $post_id, 'property_area', true );
					$rooms     = get_post_meta( $post_id, 'property_rooms', true );
					$lage_tax  = get_the_terms( $post_id, 'property-city' );
					$lage_str  = ( $lage_tax && ! is_wp_error( $lage_tax ) ) ? implode( ', ', wp_list_pluck( $lage_tax, 'name' ) ) : '';
					$thumb_id  = get_post_thumbnail_id( $post_id );
					$thumb_src = $thumb_id ? wp_get_attachment_image_url( $thumb_id, 'large' ) : '';
					?>
					<article class="pc-card">
						<div class="pc-card__img-wrap">
							<?php if ( $thumb_src ) : ?>
								<img class="pc-card__img"
									src="<?php echo esc_url( $thumb_src ); ?>"
									alt="<?php echo esc_attr( get_the_title() ); ?>"
									loading="lazy">
							<?php else : ?>
								<div class="pc-card__img-placeholder"></div>
							<?php endif; ?>
						</div>
						<div class="pc-card__body">
							<div class="pc-card__top">
								<h3 class="pc-card__title"><?php the_title(); ?></h3>
								<?php $excerpt = get_the_excerpt(); if ( $excerpt ) : ?>
									<p class="pc-card__excerpt"><?php echo esc_html( $excerpt ); ?></p>
								<?php endif; ?>
							</div>
							<dl class="pc-card__meta">
								<?php if ( $lage_str ) : ?>
									<div class="pc-meta-item">
										<dt>Lage</dt>
										<dd><?php echo esc_html( $lage_str ); ?></dd>
									</div>
								<?php endif; ?>
								<?php if ( $price ) : ?>
									<div class="pc-meta-item">
										<dt>Kaufpreis</dt>
										<dd><?php echo esc_html( $price ); ?></dd>
									</div>
								<?php endif; ?>
								<?php if ( $area ) : ?>
									<div class="pc-meta-item">
										<dt>Grundstücksfläche</dt>
										<dd><?php echo esc_html( $area ); ?></dd>
									</div>
								<?php endif; ?>
								<?php if ( $rooms ) : ?>
									<div class="pc-meta-item">
										<dt>Zimmer</dt>
										<dd><?php echo esc_html( $rooms ); ?></dd>
									</div>
								<?php endif; ?>
							</dl>
							<a class="pc-card__link" href="<?php the_permalink(); ?>">Erfahren Sie mehr →</a>
						</div>
					</article>
				<?php endwhile; ?>
				<?php wp_reset_postdata(); ?>
			</div>
		<?php else : ?>
			<p class="pc-empty">Keine Objekte gefunden.</p>
		<?php endif; ?>

		<div class="pc-cta">
			<a class="pc-cta__btn" href="<?php echo $archive_url; ?>">Alle Objekte ansehen</a>
		</div>
	</div>
<?php endif; ?>
</section>
