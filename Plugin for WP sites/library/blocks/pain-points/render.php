<?php
/** Dynamic render for Pain Points. @package library */
$items = isset( $attributes['items'] ) && is_array( $attributes['items'] ) ? $attributes['items'] : array();
?>
<section <?php echo get_block_wrapper_attributes(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<h2 class="pain-points__heading"><?php echo esc_html( $attributes['titleMain'] ?? '' ); ?> <em><?php echo esc_html( $attributes['titleAccent'] ?? '' ); ?></em></h2>
	<div class="pain-points__list">
		<?php foreach ( $items as $item ) : ?>
			<article class="pain-points__item">
				<div class="pain-points__icon"><?php if ( ! empty( $item['iconUrl'] ) ) : ?><img src="<?php echo esc_url( $item['iconUrl'] ); ?>" alt="" /><?php endif; ?></div>
				<div><h3><?php echo esc_html( $item['title'] ?? '' ); ?></h3><p><?php echo esc_html( $item['text'] ?? '' ); ?></p></div>
			</article>
		<?php endforeach; ?>
	</div>
</section>
