<?php
/**
 * Reference Catalog — server render (страница references).
 * Заголовок + панель (Typ-Tabs + Lage + Sortierung) + сетка (2 кол.) + пагинация.
 * Сетка/карточки/пагинация — общие функции rosenberger_rc_* (плагин), те же
 * зовёт REST при AJAX. Фильтр без перезагрузки (view.js → REST → подмена .rc-results).
 *
 * @var array $attributes
 *
 * @package rosenberger
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'rosenberger_rc_results_html' ) ) {
	return;
}

$per_page       = (int) ( $attributes['postsPerPage'] ?? 8 );
$heading        = wp_kses_post( $attributes['heading'] ?? 'Objekte' );
$heading_italic = wp_kses_post( $attributes['headingItalic'] ?? 'in Vorarlberg' );

$params     = rosenberger_rc_params( $_GET, $per_page );
$results    = rosenberger_rc_results_html( $params );
$endpoint   = esc_url( rest_url( 'rosenberger/v1/references' ) );
$typ_terms  = get_terms( array( 'taxonomy' => 'reference-type', 'hide_empty' => true ) );
$ort_terms  = get_terms( array( 'taxonomy' => 'reference-city', 'hide_empty' => true ) );
?>
<section <?php echo get_block_wrapper_attributes( array( 'class' => 'reference-catalog' ) ); ?>>
	<div class="rc-inner">

		<h2 class="rc-heading">
			<?php echo $heading; ?>
			<?php if ( $heading_italic ) : ?><em><?php echo $heading_italic; ?></em><?php endif; ?>
		</h2>

		<form class="rc-bar" method="get" action="<?php echo esc_url( get_permalink() ); ?>">
			<input type="hidden" name="rc_per_page" value="<?php echo esc_attr( $per_page ); ?>">

			<div class="rc-tabs" role="group" aria-label="Objektart">
				<?php if ( $typ_terms && ! is_wp_error( $typ_terms ) ) : ?>
					<?php foreach ( $typ_terms as $term ) : ?>
						<label class="rc-tab<?php echo $params['typ'] === $term->slug ? ' is-active' : ''; ?>">
							<input type="radio" name="rc_typ" value="<?php echo esc_attr( $term->slug ); ?>" <?php checked( $params['typ'], $term->slug ); ?>>
							<span><?php echo esc_html( $term->name ); ?></span>
						</label>
					<?php endforeach; ?>
					<label class="rc-tab rc-tab--all<?php echo '' === $params['typ'] ? ' is-active' : ''; ?>">
						<input type="radio" name="rc_typ" value="" <?php checked( $params['typ'], '' ); ?>>
						<span>Alle</span>
					</label>
				<?php endif; ?>
			</div>

			<div class="rc-selects">
				<?php if ( $ort_terms && ! is_wp_error( $ort_terms ) ) : ?>
				<select class="rc-ort-select" name="rc_ort" aria-label="Lage">
					<option value="">Lage</option>
					<?php foreach ( $ort_terms as $term ) : ?>
						<option value="<?php echo esc_attr( $term->slug ); ?>" <?php selected( $params['ort'], $term->slug ); ?>><?php echo esc_html( $term->name ); ?></option>
					<?php endforeach; ?>
				</select>
				<?php endif; ?>

				<select class="rc-sort-select" name="rc_sort" aria-label="Sortieren nach">
					<option value="newest" <?php selected( $params['sort'], 'newest' ); ?>>Sortieren nach</option>
					<option value="oldest" <?php selected( $params['sort'], 'oldest' ); ?>>Älteste zuerst</option>
					<option value="price_asc" <?php selected( $params['sort'], 'price_asc' ); ?>>Preis aufsteigend</option>
					<option value="price_desc" <?php selected( $params['sort'], 'price_desc' ); ?>>Preis absteigend</option>
				</select>
			</div>
		</form>

		<div class="rc-results" data-endpoint="<?php echo $endpoint; ?>" aria-live="polite">
			<?php echo $results; ?>
		</div>

	</div>
</section>
