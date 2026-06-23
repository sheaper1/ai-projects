<?php
/**
 * Объект-Hero: левая колонка (заголовок/адрес/бейджи/цена + CTA), правая — карусель фото.
 * Читает мета текущей записи property через context['postId'].
 *
 * @var WP_Block $block
 */

defined( 'ABSPATH' ) || exit;

$post_id = isset( $block->context['postId'] ) ? (int) $block->context['postId'] : get_the_ID();
$get     = fn( $k ) => get_post_meta( $post_id, $k, true );

$address   = $get( 'property_address' );
$status    = $get( 'property_status' ) ?: 'Verfügbar';
$type      = $get( 'property_object_type' );
$nr        = $get( 'property_object_nr' );
$price     = $get( 'property_price' );
$price_sub = $get( 'property_price_sub' );
$icons     = get_stylesheet_directory_uri() . '/assets/property/icons/';

// Галерея для карусели (fallback — featured image).
$ids = array_filter( array_map( 'absint', explode( ',', (string) $get( 'property_gallery' ) ) ) );
if ( ! $ids && get_post_thumbnail_id( $post_id ) ) {
	$ids = array( get_post_thumbnail_id( $post_id ) );
}
$images = array_values( array_filter( array_map(
	fn( $id ) => wp_get_attachment_image_url( $id, 'full' ),
	$ids
) ) );

$badges = array();
if ( $status ) {
	$badges[] = array( 'text' => $status, 'mod' => ( 'Verfügbar' === $status ? 'available' : 'muted' ) );
}
if ( $type ) {
	$badges[] = array( 'text' => $type, 'mod' => 'muted' );
}
if ( $nr ) {
	$badges[] = array( 'text' => 'Objekt-Nr. ' . $nr, 'mod' => 'muted' );
}
?>
<section <?php echo get_block_wrapper_attributes( array( 'class' => 'property-hero' ) ); ?>>

	<div class="property-hero__media">
		<?php if ( $images ) : ?>
		<div class="property-hero__carousel" data-carousel>
			<div class="property-hero__track" data-track>
				<?php foreach ( $images as $src ) : ?>
					<div class="property-hero__slide"><img src="<?php echo esc_url( $src ); ?>" alt="" loading="lazy" /></div>
				<?php endforeach; ?>
			</div>
			<?php if ( count( $images ) > 1 ) : ?>
				<button class="property-hero__nav property-hero__nav--prev" data-prev aria-label="Vorheriges Bild"></button>
				<button class="property-hero__nav property-hero__nav--next" data-next aria-label="Nächstes Bild"></button>
				<div class="property-hero__dots" data-dots></div>
			<?php endif; ?>
		</div>
		<?php endif; ?>
	</div>

	<div class="property-hero__content">
		<div class="property-hero__col">

			<div class="property-hero__head">
				<h1 class="property-hero__title"><?php echo wp_kses_post( get_the_title( $post_id ) ); ?></h1>

				<?php if ( $address ) : ?>
				<p class="property-hero__location">
					<img class="property-hero__pin" src="<?php echo esc_url( $icons . 'location.svg' ); ?>" alt="" width="24" height="24" />
					<span><?php echo esc_html( $address ); ?></span>
				</p>
				<?php endif; ?>

				<?php if ( $badges ) : ?>
				<div class="property-hero__badges">
					<?php foreach ( $badges as $b ) : ?>
						<span class="property-hero__badge property-hero__badge--<?php echo esc_attr( $b['mod'] ); ?>"><?php echo esc_html( $b['text'] ); ?></span>
					<?php endforeach; ?>
				</div>
				<?php endif; ?>
			</div>

			<div class="property-hero__price-row">
				<?php if ( $price ) : ?>
				<div class="property-hero__price">
					<span class="property-hero__price-label">Kaufpreis</span>
					<span class="property-hero__price-value"><?php echo esc_html( $price ); ?></span>
					<?php if ( $price_sub ) : ?>
						<span class="property-hero__price-sub"><?php echo esc_html( $price_sub ); ?></span>
					<?php endif; ?>
				</div>
				<?php endif; ?>
				<a class="property-hero__cta" href="/kontakt/">Besichtigung anfragen</a>
			</div>

		</div>
	</div>

</section>
