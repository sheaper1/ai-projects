<?php
defined( 'ABSPATH' ) || exit;

$items   = is_array( $attributes['items'] ?? null ) ? $attributes['items'] : [];
$heading = wp_kses_post( $attributes['heading'] ?? 'Häufige Fragen' );
$wrapper = get_block_wrapper_attributes( [ 'class' => 'faq-section' ] );

// Иконка как в Figma (simple-line-icons:plus): тонкое кольцо + плюс с
// закруглёнными штрихами. Вертикальный штрих скрывается при раскрытии (plus→minus).
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
				<?php
				$is_open = ! empty( $item['open'] );
				$ans_id  = wp_unique_id( 'faq-answer-' );
				?>
				<div class="faq-section__item<?php echo $is_open ? ' is-open' : ''; ?>">
					<button class="faq-section__q" type="button" aria-expanded="<?php echo $is_open ? 'true' : 'false'; ?>" aria-controls="<?php echo esc_attr( $ans_id ); ?>">
						<span><?php echo wp_kses( $item['question'] ?? '', [ 'br' => [] ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
						<?php echo $icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</button>
					<div class="faq-section__answer-wrap" id="<?php echo esc_attr( $ans_id ); ?>" role="region">
						<div class="faq-section__answer">
							<div class="faq-section__answer-inner"><?php echo esc_html( $item['answer'] ?? '' ); ?></div>
						</div>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</section>
