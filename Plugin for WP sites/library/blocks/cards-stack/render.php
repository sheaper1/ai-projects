<?php
/** Dynamic render for Cards Stack. @package library */
$cards = isset( $attributes['cards'] ) && is_array( $attributes['cards'] ) ? $attributes['cards'] : array();
$total = count( $cards );
?>
<section <?php echo get_block_wrapper_attributes(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<header class="cards-stack__head">
		<h2 class="cards-stack__heading"><?php echo esc_html( $attributes['titleMain'] ?? '' ); ?> <em><?php echo esc_html( $attributes['titleAccent'] ?? '' ); ?></em></h2>
		<p class="cards-stack__subtitle"><?php echo esc_html( $attributes['subtitle'] ?? '' ); ?></p>
	</header>
	<div class="cards-stack__layout">
		<div class="cards-stack__cards">
			<?php foreach ( $cards as $i => $card ) : ?>
				<article class="cards-stack__card" style="--i: <?php echo (int) $i; ?>;" data-index="<?php echo (int) $i + 1; ?>">
					<div class="cards-stack__text">
						<h3><?php echo esc_html( $card['title'] ?? '' ); ?></h3>
						<p><?php echo esc_html( $card['text'] ?? '' ); ?></p>
						<?php if ( ! empty( $card['buttonText'] ) ) : ?>
							<a class="cards-stack__button" href="<?php echo esc_url( $card['buttonUrl'] ?? '#' ); ?>"><?php echo esc_html( $card['buttonText'] ); ?> <span aria-hidden="true">&rarr;</span></a>
						<?php endif; ?>
					</div>
					<div class="cards-stack__media">
						<?php if ( ! empty( $card['imageUrl'] ) ) : ?><img src="<?php echo esc_url( $card['imageUrl'] ); ?>" alt="" loading="lazy" /><?php endif; ?>
					</div>
				</article>
			<?php endforeach; ?>
		</div>
		<?php if ( $total ) : ?>
			<aside class="cards-stack__counter" aria-hidden="true">
				<span class="cards-stack__current">01</span>
				<span class="cards-stack__total"><?php echo esc_html( sprintf( '%02d', $total ) ); ?></span>
			</aside>
		<?php endif; ?>
	</div>
	<?php if ( ! empty( $attributes['ctaText'] ) ) : ?>
		<div class="cards-stack__cta">
			<a class="cards-stack__cta-button" href="<?php echo esc_url( $attributes['ctaUrl'] ?? '#' ); ?>"><?php echo esc_html( $attributes['ctaText'] ); ?></a>
		</div>
	<?php endif; ?>
</section>
