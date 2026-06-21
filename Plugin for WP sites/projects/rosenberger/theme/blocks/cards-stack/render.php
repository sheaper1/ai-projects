<?php
/** Dynamic render for Cards Stack. @package library */
$cards = isset( $attributes['cards'] ) && is_array( $attributes['cards'] ) ? $attributes['cards'] : array();
$total = count( $cards );
?>
<section <?php echo get_block_wrapper_attributes(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<header class="cards-stack__head">
		<h2 class="cards-stack__heading"><span class="cards-stack__lead"><?php echo esc_html( $attributes['titleMain'] ?? '' ); ?></span> <em><?php echo esc_html( $attributes['titleAccent'] ?? '' ); ?></em></h2>
		<p class="cards-stack__subtitle"><?php echo esc_html( $attributes['subtitle'] ?? '' ); ?></p>
	</header>
	<div class="cards-stack__stage">
		<div class="cards-stack__cards">
			<?php foreach ( $cards as $i => $card ) : ?>
				<article class="cards-stack__card" style="--card-index: <?php echo (int) $i + 1; ?>;" data-index="<?php echo (int) $i; ?>">
					<div class="cards-stack__body">
						<div class="cards-stack__text">
							<h3><?php echo esc_html( $card['title'] ?? '' ); ?></h3>
							<p><?php echo esc_html( $card['text'] ?? '' ); ?></p>
						</div>
						<?php if ( ! empty( $card['buttonText'] ) ) : ?>
							<a class="cards-stack__button" href="<?php echo esc_url( $card['buttonUrl'] ?? '#' ); ?>">
								<?php echo esc_html( $card['buttonText'] ); ?>
								<svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M3 8H13M13 8L9 4M13 8L9 12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
							</a>
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
				<div class="cards-stack__window">
					<div class="cards-stack__track">
						<?php for ( $n = 1; $n <= $total; $n++ ) : ?><span><?php echo esc_html( sprintf( '%02d', $n ) ); ?></span><?php endfor; ?>
					</div>
				</div>
				<div class="cards-stack__line"><span></span></div>
				<div class="cards-stack__total"><?php echo esc_html( sprintf( '%02d', $total ) ); ?></div>
			</aside>
		<?php endif; ?>
	</div>
	<?php if ( ! empty( $attributes['ctaText'] ) ) : ?>
		<div class="cards-stack__cta">
			<a class="cards-stack__cta-button" href="<?php echo esc_url( $attributes['ctaUrl'] ?? '#' ); ?>"><?php echo esc_html( $attributes['ctaText'] ); ?></a>
		</div>
	<?php endif; ?>
</section>
