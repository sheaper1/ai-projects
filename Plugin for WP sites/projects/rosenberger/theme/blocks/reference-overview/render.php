<?php
/**
 * Referenz-Beschreibung: слева изображение, справа «Objektbeschreibung» + текст
 * записи (post_content). Изображение — featured (fallback: первое из галереи).
 *
 * @var WP_Block $block
 *
 * @package rosenberger
 */

defined( 'ABSPATH' ) || exit;

$post_id   = isset( $block->context['postId'] ) ? (int) $block->context['postId'] : get_the_ID();
$desc_html = apply_filters( 'the_content', get_post_field( 'post_content', $post_id ) );

$img_id = get_post_thumbnail_id( $post_id );
if ( ! $img_id ) {
	$ids    = array_filter( array_map( 'absint', explode( ',', (string) get_post_meta( $post_id, 'property_gallery', true ) ) ) );
	$img_id = $ids ? (int) reset( $ids ) : 0;
}
$img_src = $img_id ? wp_get_attachment_image_url( $img_id, 'large' ) : '';

if ( ! $desc_html && ! $img_src ) {
	return;
}
?>
<section <?php echo get_block_wrapper_attributes( array( 'class' => 'reference-overview' ) ); ?>>
	<div class="reference-overview__inner">
		<div class="reference-overview__media">
			<?php if ( $img_src ) : ?>
				<img src="<?php echo esc_url( $img_src ); ?>" alt="<?php echo esc_attr( get_the_title( $post_id ) ); ?>" loading="lazy" />
			<?php endif; ?>
		</div>
		<div class="reference-overview__body">
			<h2 class="reference-overview__heading">Objektbeschreibung</h2>
			<div class="reference-overview__desc"><?php echo wp_kses_post( $desc_html ); ?></div>
		</div>
	</div>
</section>
