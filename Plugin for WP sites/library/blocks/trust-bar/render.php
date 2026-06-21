<?php
/** Dynamic render for Trust Bar. @package library */
$rating = esc_html( $attributes['rating'] ?? '4.5' );
$items  = isset( $attributes['items'] ) && is_array( $attributes['items'] ) ? $attributes['items'] : array();
?>
<section <?php echo get_block_wrapper_attributes(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="trust-bar__rating"><?php if ( ! empty( $attributes['badgeUrl'] ) ) : ?><img src="<?php echo esc_url( $attributes['badgeUrl'] ); ?>" alt="Google Bewertung <?php echo esc_attr( $rating ); ?> von 5" /><?php endif; ?></div>
	<div class="trust-bar__items">
		<?php foreach ( $items as $item ) : ?><span><?php echo esc_html( $item ); ?></span><?php endforeach; ?>
	</div>
</section>
