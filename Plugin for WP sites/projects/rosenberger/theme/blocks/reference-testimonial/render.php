<?php
/**
 * Referenz-Kundenstimme: «Zufriedene Kundenstimmen» + Zitat + Name | Sterne.
 * Liest reference_quote / reference_author / reference_location / reference_rating.
 * Gold der Sterne (#fbbc05) ist Markenfarbe → inline (kein Token), wie Logo.
 *
 * @var WP_Block $block
 *
 * @package rosenberger
 */

defined( 'ABSPATH' ) || exit;

$post_id  = isset( $block->context['postId'] ) ? (int) $block->context['postId'] : get_the_ID();
$quote    = get_post_meta( $post_id, 'reference_quote', true );
$author   = get_post_meta( $post_id, 'reference_author', true );
$location = get_post_meta( $post_id, 'reference_location', true );
$rating   = (int) get_post_meta( $post_id, 'reference_rating', true );

if ( ! $quote ) {
	return;
}

$rating  = max( 1, min( 5, $rating ?: 5 ) );
$name    = trim( $author . ( $location ? ' · ' . $location : '' ) );
$stars   = str_repeat( '★', $rating ) . str_repeat( '☆', 5 - $rating );
?>
<section <?php echo get_block_wrapper_attributes( array( 'class' => 'reference-testimonial' ) ); ?>>
	<div class="reference-testimonial__inner">
		<h2 class="reference-testimonial__heading"><em>Zufriedene</em> Kundenstimmen</h2>

		<figure class="reference-testimonial__card">
			<span class="reference-testimonial__mark" aria-hidden="true">
				<svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
					<path d="M13.3 6.4C8.2 8.5 5 13 5 18.2c0 4.4 2.7 7.4 6.4 7.4 3.1 0 5.5-2.3 5.5-5.4 0-3-2.1-5.2-4.9-5.2-.5 0-1.2.1-1.4.2.6-2.6 3-5.3 6-6.6l-3.3-2.2Zm14.4 0C22.6 8.5 19.4 13 19.4 18.2c0 4.4 2.7 7.4 6.4 7.4 3.1 0 5.5-2.3 5.5-5.4 0-3-2.1-5.2-4.9-5.2-.5 0-1.2.1-1.4.2.6-2.6 3-5.3 6-6.6l-3.3-2.2Z" fill="currentColor"/>
				</svg>
			</span>

			<blockquote class="reference-testimonial__quote"><?php echo esc_html( $quote ); ?></blockquote>

			<figcaption class="reference-testimonial__author">
				<?php if ( $name ) : ?>
					<span class="reference-testimonial__name"><?php echo esc_html( $name ); ?></span>
					<span class="reference-testimonial__divider" aria-hidden="true"></span>
				<?php endif; ?>
				<span class="reference-testimonial__stars" style="color:#fbbc05" aria-label="<?php echo esc_attr( $rating . ' von 5 Sternen' ); ?>"><?php echo esc_html( $stars ); ?></span>
			</figcaption>
		</figure>
	</div>
</section>
