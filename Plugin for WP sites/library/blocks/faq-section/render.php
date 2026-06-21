<?php
defined( 'ABSPATH' ) || exit;

$items   = is_array( $attributes['items'] ?? null ) ? $attributes['items'] : [];
$heading = wp_kses_post( $attributes['heading'] ?? 'Häufige Fragen' );
$wrapper = get_block_wrapper_attributes( [ 'class' => 'faq-section' ] );
?>
<section <?php echo $wrapper; ?>>
	<div class="faq-section__inner">
		<h2 class="faq-section__heading"><?php echo $heading; ?></h2>
		<div class="faq-section__items">
			<?php foreach ( $items as $item ) : ?>
				<details class="faq-section__item" <?php echo ! empty( $item['open'] ) ? 'open' : ''; ?>>
					<summary>
						<span><?php echo esc_html( $item['question'] ?? '' ); ?></span>
						<i aria-hidden="true"></i>
					</summary>
					<div class="faq-section__answer"><?php echo esc_html( $item['answer'] ?? '' ); ?></div>
				</details>
			<?php endforeach; ?>
		</div>
	</div>
</section>
