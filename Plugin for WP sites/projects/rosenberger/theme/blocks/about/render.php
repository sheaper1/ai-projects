<?php
/** Dynamic render for About. @package library */
$items = isset( $attributes['items'] ) && is_array( $attributes['items'] ) ? $attributes['items'] : array();
$bg    = $attributes['backgroundUrl'] ?? '';
?>
<section <?php echo get_block_wrapper_attributes(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="about__bg" aria-hidden="true">
		<?php if ( ! empty( $bg ) ) : ?><img src="<?php echo esc_url( $bg ); ?>" alt="" /><?php endif; ?>
	</div>
	<div class="about__inner">
		<div class="about__intro">
			<div class="about__intro-text">
				<h2 class="about__heading"><?php echo wp_kses_post( $attributes['titleMain'] ?? '' ); ?></h2>
				<p class="about__lead"><?php echo esc_html( $attributes['text'] ?? '' ); ?></p>
			</div>
			<?php if ( ! empty( $attributes['buttonText'] ) ) : ?>
				<a class="about__button" href="<?php echo esc_url( $attributes['buttonUrl'] ?? '#' ); ?>"><?php echo esc_html( $attributes['buttonText'] ); ?></a>
			<?php endif; ?>
		</div>
		<div class="about__cards" style="--bw-cols:<?php echo (int)( $attributes['columns'] ?? 4 ); ?>">
			<?php foreach ( $items as $item ) : ?>
				<article class="about__card">
					<h3><?php echo wp_kses_post( $item['title'] ?? '' ); ?></h3>
					<p><?php echo esc_html( $item['text'] ?? '' ); ?></p>
				</article>
			<?php endforeach; ?>
		</div>
	</div>
</section>
