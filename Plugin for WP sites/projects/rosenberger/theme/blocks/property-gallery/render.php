<?php
/**
 * Объект-Галерея: карусель-coverflow (центральное крупное фото, соседи приглушены),
 * точки-пагинация и тёмный CTA. Использует те же изображения, что и Hero.
 *
 * @var WP_Block $block
 */

defined( 'ABSPATH' ) || exit;

$post_id = isset( $block->context['postId'] ) ? (int) $block->context['postId'] : get_the_ID();

$ids = array_filter( array_map( 'absint', explode( ',', (string) get_post_meta( $post_id, 'property_gallery', true ) ) ) );
if ( ! $ids && get_post_thumbnail_id( $post_id ) ) {
	$ids = array( get_post_thumbnail_id( $post_id ) );
}
$images = array_values( array_filter( array_map(
	fn( $id ) => wp_get_attachment_image_url( $id, 'large' ),
	$ids
) ) );
if ( ! $images ) {
	return;
}
?>
<section <?php echo get_block_wrapper_attributes( array( 'class' => 'property-gallery' ) ); ?>>
	<div class="property-gallery__inner">
		<h2 class="property-gallery__heading">Galerie</h2>

		<div class="property-gallery__carousel" data-carousel>
			<div class="property-gallery__viewport">
				<div class="property-gallery__track" data-track>
					<?php foreach ( $images as $i => $src ) : ?>
						<figure class="property-gallery__slide" data-index="<?php echo (int) $i; ?>">
							<img src="<?php echo esc_url( $src ); ?>" alt="" loading="lazy" draggable="false" oncontextmenu="return false" />
						</figure>
					<?php endforeach; ?>
				</div>
			</div>
			<?php if ( count( $images ) > 1 ) : ?>
				<div class="property-gallery__dots" data-dots></div>
			<?php endif; ?>
		</div>

		<div class="property-gallery__cta">
			<a class="property-gallery__btn" href="/kontakt/">Besichtigung anfragen</a>
			<span class="property-gallery__note">Unverbindlich und kostenlos</span>
		</div>
	</div>
</section>
