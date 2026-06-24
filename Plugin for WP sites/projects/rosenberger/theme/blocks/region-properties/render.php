<?php
/**
 * Region Properties — карусель объектов региона. Фильтр по городу: термин
 * таксономии (`property-city` / `reference-city`) со slug = slug записи region.
 * Карточка повторяет макет региона (детали слева, фото справа).
 *
 * @var array    $attributes
 * @var WP_Block $block
 * @package rosenberger
 */

defined( 'ABSPATH' ) || exit;

$post_id = isset( $block->context['postId'] ) ? (int) $block->context['postId'] : get_the_ID();
if ( ! $post_id ) {
	return;
}

$source     = ( 'reference' === ( $attributes['source'] ?? 'property' ) ) ? 'reference' : 'property';
$taxonomy   = 'reference' === $source ? 'reference-city' : 'property-city';
$city_slug  = get_post_field( 'post_name', $post_id );
$city_name  = get_the_title( $post_id );
$limit      = max( 1, (int) ( $attributes['limit'] ?? 6 ) );

$heading_italic = wp_kses_post( $attributes['headingItalic'] ?? '' );
$heading        = str_replace( '{city}', $city_name, wp_kses_post( $attributes['heading'] ?? '' ) );
$subtitle       = str_replace( '{city}', $city_name, wp_kses_post( $attributes['subtitle'] ?? '' ) );
$btn_text   = wp_kses_post( $attributes['buttonText'] ?? '' );
$btn_url    = esc_url( $attributes['buttonUrl'] ?? '#' );

$args = array(
	'post_type'      => $source,
	'posts_per_page' => $limit,
	'no_found_rows'  => true,
);
if ( term_exists( $city_slug, $taxonomy ) ) {
	$args['tax_query'] = array(
		array( 'taxonomy' => $taxonomy, 'field' => 'slug', 'terms' => $city_slug ),
	);
}

$query = new WP_Query( $args );
if ( ! $query->have_posts() ) {
	return;
}

$cards = array();
foreach ( $query->posts as $p ) {
	$pid       = (int) $p->ID;
	$title     = get_post_meta( $pid, 'property_object_type', true ) ?: get_the_title( $pid );
	$desc      = get_post_meta( $pid, 'property_short_desc', true ) ?: get_the_excerpt( $pid );
	$lage      = get_post_meta( $pid, 'property_address', true );
	if ( ! $lage ) {
		$terms = get_the_terms( $pid, $taxonomy );
		$lage  = ( $terms && ! is_wp_error( $terms ) ) ? implode( ', ', wp_list_pluck( $terms, 'name' ) ) : 'Vorarlberg | Österreich';
	}
	$price     = get_post_meta( $pid, 'property_price', true ) ?: 'Auf Anfrage';
	$plot      = get_post_meta( $pid, 'property_plot_area', true ) ?: '—';
	$rooms     = get_post_meta( $pid, 'property_rooms', true ) ?: '—';
	$thumb_id  = get_post_thumbnail_id( $pid );
	$thumb_src = $thumb_id ? wp_get_attachment_image_url( $thumb_id, 'large' ) : '';

	ob_start();
	?>
	<a class="rp-card" href="<?php echo esc_url( get_permalink( $pid ) ); ?>">
		<div class="rp-card__body">
			<h3 class="rp-card__title"><?php echo esc_html( $title ); ?></h3>
			<?php if ( $desc ) : ?><p class="rp-card__desc"><?php echo esc_html( wp_trim_words( $desc, 32 ) ); ?></p><?php endif; ?>
			<dl class="rp-card__meta">
				<div><dt>Lage</dt><dd><?php echo esc_html( $lage ); ?></dd></div>
				<div><dt>Kaufpreis</dt><dd><?php echo esc_html( $price ); ?></dd></div>
				<div><dt>Grundstücksfläche</dt><dd><?php echo esc_html( $plot ); ?></dd></div>
				<div><dt>Zimmer</dt><dd><?php echo esc_html( $rooms ); ?></dd></div>
			</dl>
			<span class="rp-card__more">Erfahren Sie mehr&nbsp;&rarr;</span>
		</div>
		<div class="rp-card__image">
			<?php if ( $thumb_src ) : ?><img src="<?php echo esc_url( $thumb_src ); ?>" alt="<?php echo esc_attr( $title ); ?>"><?php endif; ?>
		</div>
	</a>
	<?php
	$cards[] = (string) ob_get_clean();
}
wp_reset_postdata();

$has_heading = '' !== trim( wp_strip_all_tags( $heading_italic . $heading ) );
$multi       = count( $cards ) > 1;
?>
<section <?php echo get_block_wrapper_attributes( array( 'class' => 'region-properties' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="region-properties__inner">
		<?php if ( $has_heading ) : ?>
			<div class="region-properties__head">
				<h2 class="region-properties__heading"><?php
					if ( '' !== trim( wp_strip_all_tags( $heading_italic ) ) ) {
						echo '<em>' . $heading_italic . '</em>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					}
					echo esc_html( $heading );
				?></h2>
				<?php if ( $subtitle ) : ?><p class="region-properties__subtitle"><?php echo $subtitle; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p><?php endif; ?>
			</div>
		<?php endif; ?>

		<div class="region-properties__carousel"<?php echo $multi ? ' data-rp-carousel' : ''; ?>>
			<?php if ( $multi ) : ?>
				<button class="rp-arrow rp-arrow--prev" type="button" data-prev aria-label="Zurück">&larr;</button>
			<?php endif; ?>
			<div class="rp-track"<?php echo $multi ? ' data-track' : ''; ?>>
				<?php echo implode( "\n", $cards ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
			<?php if ( $multi ) : ?>
				<button class="rp-arrow rp-arrow--next" type="button" data-next aria-label="Weiter">&rarr;</button>
				<div class="rp-dots" data-dots aria-hidden="true"></div>
			<?php endif; ?>
		</div>

		<?php if ( '' !== trim( wp_strip_all_tags( $btn_text ) ) ) : ?>
			<a class="region-properties__button" href="<?php echo $btn_url; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>"><?php echo $btn_text; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
		<?php endif; ?>
	</div>
</section>
