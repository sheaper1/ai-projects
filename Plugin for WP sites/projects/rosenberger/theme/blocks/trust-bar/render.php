<?php
/** Dynamic render for Trust Bar. @package library */
$rating = esc_html( $attributes['rating'] ?? '4.5' );
$items  = isset( $attributes['items'] ) && is_array( $attributes['items'] ) ? $attributes['items'] : array();
?>
<section <?php echo get_block_wrapper_attributes(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="trust-bar__rating" aria-label="Google Bewertung <?php echo esc_attr( $rating ); ?> von 5">
		<span class="trust-bar__google" aria-hidden="true">G</span>
		<span><strong>Google bewertet</strong><small><?php echo $rating; ?> <span class="trust-bar__stars" aria-hidden="true">★★★★★</span></small></span>
	</div>
	<div class="trust-bar__items">
		<?php foreach ( $items as $item ) : ?><span><?php echo esc_html( $item ); ?></span><?php endforeach; ?>
	</div>
</section>
