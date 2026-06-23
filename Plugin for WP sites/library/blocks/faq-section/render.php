<?php
defined( 'ABSPATH' ) || exit;

$items   = is_array( $attributes['items'] ?? null ) ? $attributes['items'] : [];
$heading = wp_kses_post( $attributes['heading'] ?? 'Häufige Fragen' );
$wrapper = get_block_wrapper_attributes( [ 'class' => 'faq-section' ] );

// Иконка как в Figma (simple-line-icons:plus): тонкое кольцо + плюс с
// закруглёнными штрихами. Вертикальный штрих анимируется при раскрытии.
$icon = '<span class="faq-section__icon" aria-hidden="true">'
	. '<svg viewBox="0 0 24 24" width="24" height="24" fill="none">'
	. '<circle cx="12" cy="12" r="11.25" stroke="currentColor" stroke-width="1.5"/>'
	. '<path class="faq-section__icon-h" d="M6.75 12h10.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>'
	. '<path class="faq-section__icon-v" d="M12 6.75v10.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>'
	. '</svg></span>';
?>
<section <?php echo $wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="faq-section__inner">
		<h2 class="faq-section__heading"><?php echo $heading; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h2>
		<div class="faq-section__items">
			<?php foreach ( $items as $item ) : ?>
				<details class="faq-section__item<?php echo ! empty( $item['open'] ) ? ' is-open' : ''; ?>" <?php echo ! empty( $item['open'] ) ? 'open' : ''; ?>>
					<summary>
						<span><?php echo esc_html( $item['question'] ?? '' ); ?></span>
						<?php echo $icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</summary>
					<div class="faq-section__answer"><?php echo esc_html( $item['answer'] ?? '' ); ?></div>
				</details>
			<?php endforeach; ?>
		</div>
	</div>
</section>
