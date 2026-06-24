<?php
/**
 * Region Services — услуги региона: заголовок + кнопка + 3 карточки
 * (иконка, заголовок, текст, «Erfahren Sie mehr →»). По макету 2009:6685.
 *
 * @var array $attributes
 * @package rosenberger
 */

defined( 'ABSPATH' ) || exit;

$heading     = wp_kses_post( $attributes['heading'] ?? '' );
$button_text = wp_kses_post( $attributes['buttonText'] ?? '' );
$button_url  = esc_url( $attributes['buttonUrl'] ?? '#' );
$items       = is_array( $attributes['items'] ?? null ) ? $attributes['items'] : array();
?>
<section <?php echo get_block_wrapper_attributes( array( 'class' => 'region-services' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="region-services__inner">
		<div class="region-services__head">
			<h2 class="region-services__heading"><?php echo $heading; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h2>
			<?php if ( '' !== trim( wp_strip_all_tags( $button_text ) ) ) : ?>
				<a class="region-services__button" href="<?php echo $button_url; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>"><?php echo $button_text; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
			<?php endif; ?>
		</div>

		<div class="region-services__grid">
			<?php foreach ( $items as $item ) : ?>
				<?php
				$icon  = $item['iconUrl'] ?? ( ! empty( $item['iconId'] ) ? wp_get_attachment_url( (int) $item['iconId'] ) : '' );
				$link  = $item['linkUrl'] ?? '';
				$tag   = $link ? 'a' : 'div';
				$href  = $link ? ' href="' . esc_url( $link ) . '"' : '';
				?>
				<<?php echo $tag . $href; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> class="rs-card">
					<div class="rs-card__icon"><?php if ( $icon ) : ?><img src="<?php echo esc_url( $icon ); ?>" alt=""><?php endif; ?></div>
					<div class="rs-card__bottom">
						<div class="rs-card__textgroup">
							<h3 class="rs-card__title"><?php echo wp_kses_post( $item['title'] ?? '' ); ?></h3>
							<p class="rs-card__desc"><?php echo wp_kses_post( $item['text'] ?? '' ); ?></p>
						</div>
						<?php if ( $link ) : ?><span class="rs-card__more">Erfahren Sie mehr&nbsp;&rarr;</span><?php endif; ?>
					</div>
				</<?php echo $tag; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
			<?php endforeach; ?>
		</div>
	</div>
</section>
