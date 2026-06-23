<?php
/**
 * Property Catalog - server-side render.
 *
 * Layouts (attribute 'layout'):
 *   'compact'  – header + inline filters + grid + CTA (homepage)
 *   'catalog'  – sidebar filters + grid + pagination (all-immobilien), AJAX-фильтрация
 *
 * Сетка/карточки/пагинация рендерятся общими функциями rosenberger_pc_* из плагина
 * (includes/property-catalog.php), те же зовёт REST-эндпоинт при AJAX. Фильтр катало-
 * га применяется без перезагрузки (view.js → REST → подмена .pc-results).
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

// Защита: если плагин проекта не активен — общих рендер-функций нет.
if ( ! function_exists( 'rosenberger_pc_results_html' ) ) {
	return;
}

$params      = rosenberger_pc_params( $_GET, $posts_per_page );
$results     = rosenberger_pc_results_html( $params );
$endpoint    = esc_url( rest_url( 'rosenberger/v1/properties' ) );
$typ_terms   = get_terms( [ 'taxonomy' => 'property-type', 'hide_empty' => false ] );
$lage_terms  = get_terms( [ 'taxonomy' => 'property-city', 'hide_empty' => false ] );
$is_mieten   = 'mieten' === $params['art'];

$wrapper_attrs = get_block_wrapper_attributes( [
	'class' => 'property-catalog property-catalog--' . esc_attr( $layout ),
] );

$rng = static function ( $v ) {
	return $v ? (string) $v : '';
};
?>

<section <?php echo $wrapper_attrs; ?>>
<?php if ( 'catalog' === $layout ) : ?>
	<!-- ── CATALOG LAYOUT (sidebar + grid, AJAX) ── -->
	<div class="pc-wrap pc-catalog">

		<div class="pc-top">
			<h2 class="pc-heading">
				<?php echo $heading; ?>
				<?php if ( $heading_italic ) : ?>
					<br aria-hidden="true"><em><?php echo $heading_italic; ?></em>
				<?php endif; ?>
			</h2>
		</div>

		<div class="pc-body">

			<!-- Sidebar / Filter -->
			<aside class="pc-sidebar" aria-label="Filter">
				<form class="pc-filter-form" method="get" action="<?php echo esc_url( get_permalink() ); ?>">
					<input type="hidden" name="pc_per_page" value="<?php echo esc_attr( $posts_per_page ); ?>">

					<!-- Kaufen / Mieten -->
					<div class="pc-toggle" role="group" aria-label="Angebotsart">
						<label class="pc-toggle__option<?php echo $is_mieten ? '' : ' is-active'; ?>">
							<input type="radio" name="pc_art" value="" <?php checked( ! $is_mieten ); ?>> Kaufen
						</label>
						<label class="pc-toggle__option<?php echo $is_mieten ? ' is-active' : ''; ?>">
							<input type="radio" name="pc_art" value="mieten" <?php checked( $is_mieten ); ?>> Mieten
						</label>
					</div>

					<!-- Мобильный триггер аккордеона фильтра -->
					<button type="button" class="pc-filter-toggle" aria-expanded="false">
						<span>FILTER</span>
						<svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="m5 7.5 5 5 5-5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
					</button>

					<div class="pc-filter-collapse">
						<p class="pc-sidebar__label">Filter</p>

						<!-- Objektart -->
						<fieldset class="pc-filter-group">
							<legend class="pc-filter-group__title">Objektart</legend>
							<div class="pc-checkboxes">
								<?php foreach ( (array) $typ_terms as $term ) : ?>
									<label class="pc-checkbox">
										<span class="pc-checkbox__box">
											<input type="checkbox" name="pc_typ[]" value="<?php echo esc_attr( $term->slug ); ?>" <?php checked( in_array( $term->slug, $params['typ'], true ) ); ?>>
											<span class="pc-checkbox__check" aria-hidden="true"></span>
										</span>
										<span class="pc-checkbox__text"><?php echo esc_html( $term->name ); ?>
											<span class="pc-checkbox__count">( <?php echo (int) $term->count; ?> )</span>
										</span>
									</label>
								<?php endforeach; ?>
							</div>
						</fieldset>

						<?php
						$range_fields = [
							'zimmer'  => [ 'Zimmer', 'pc_zimmer_min', 'pc_zimmer_max' ],
							'flaeche' => [ 'Wohnfläche m²', 'pc_flaeche_min', 'pc_flaeche_max' ],
							'grund'   => [ 'Grundstücksfläche m²', 'pc_grund_min', 'pc_grund_max' ],
							'preis'   => [ 'Preis', 'pc_preis_min', 'pc_preis_max' ],
						];
						foreach ( $range_fields as $key => $f ) :
							list( $title, $name_min, $name_max ) = $f;
						?>
							<fieldset class="pc-filter-group">
								<legend class="pc-filter-group__title"><?php echo esc_html( $title ); ?></legend>
								<div class="pc-range">
									<div class="pc-range__field">
										<label class="pc-range__label" for="<?php echo esc_attr( $name_min ); ?>">von</label>
										<input class="pc-range__input" type="number" inputmode="numeric" id="<?php echo esc_attr( $name_min ); ?>" name="<?php echo esc_attr( $name_min ); ?>" min="0" placeholder="0" value="<?php echo esc_attr( $rng( $params[ $key ][0] ) ); ?>">
									</div>
									<span class="pc-range__sep" aria-hidden="true">—</span>
									<div class="pc-range__field">
										<label class="pc-range__label" for="<?php echo esc_attr( $name_max ); ?>">bis</label>
										<input class="pc-range__input" type="number" inputmode="numeric" id="<?php echo esc_attr( $name_max ); ?>" name="<?php echo esc_attr( $name_max ); ?>" min="0" placeholder="∞" value="<?php echo esc_attr( $rng( $params[ $key ][1] ) ); ?>">
									</div>
								</div>
							</fieldset>
						<?php endforeach; ?>

						<a class="pc-consult-cta" href="/kontakt/">Kostenlos beraten lassen</a>

						<a class="pc-filter-reset" href="<?php echo esc_url( get_permalink() ); ?>">
							<svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M2 8a6 6 0 1 0 6-6 6 6 0 0 0-4.243 1.757" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/><path d="M2 3.5V8h4.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
							Filter zurücksetzen
						</a>
					</div>
				</form>
			</aside>

			<!-- Main -->
			<div class="pc-main">
				<div class="pc-sort-bar">
					<div class="pc-sort-control">
						<label class="pc-sort-label" for="pc_sort_select">
							<svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M4 6h12M6 10h8M8 14h4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
							Sortieren nach
						</label>
						<select class="pc-sort-select" id="pc_sort_select" name="pc_sort" aria-label="Sortieren nach">
							<option value="newest" <?php selected( $params['sort'], 'newest' ); ?>>Neueste zuerst</option>
							<option value="oldest" <?php selected( $params['sort'], 'oldest' ); ?>>Älteste zuerst</option>
							<option value="price_asc" <?php selected( $params['sort'], 'price_asc' ); ?>>Preis aufsteigend</option>
							<option value="price_desc" <?php selected( $params['sort'], 'price_desc' ); ?>>Preis absteigend</option>
						</select>
					</div>
				</div>

				<div class="pc-results" data-endpoint="<?php echo $endpoint; ?>" aria-live="polite">
					<?php echo $results; ?>
				</div>
			</div>

		</div><!-- .pc-body -->
	</div><!-- .pc-wrap -->

<?php else : ?>
	<!-- ── COMPACT LAYOUT (homepage) ── -->
	<div class="pc-inner">
		<header class="pc-header">
			<h2 class="pc-heading">
				<?php echo $heading; ?>
				<?php if ( $heading_italic ) : ?><em><?php echo $heading_italic; ?></em><?php endif; ?>
			</h2>
			<?php if ( $subtext ) : ?><p class="pc-subtext"><?php echo $subtext; ?></p><?php endif; ?>
		</header>

		<?php
		$compact = rosenberger_pc_query( array_merge( $params, [ 'page' => 1 ] ) );
		if ( $compact->have_posts() ) : ?>
			<div class="pc-grid">
				<?php while ( $compact->have_posts() ) : $compact->the_post(); echo rosenberger_pc_card_html( get_the_ID() ); endwhile; wp_reset_postdata(); ?>
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
