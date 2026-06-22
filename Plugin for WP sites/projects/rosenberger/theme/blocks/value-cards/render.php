<?php
defined( 'ABSPATH' ) || exit;

$a     = wp_parse_args( $attributes, [ 'cards' => [] ] );
$cards = is_array( $a['cards'] ) ? $a['cards'] : [];

if ( empty( $cards ) ) {
	return;
}

$wrapper = get_block_wrapper_attributes( [ 'class' => 'value-cards' ] );
?>
<section <?php echo $wrapper; ?>>
	<div class="value-cards__row">
		<?php foreach ( $cards as $card ) :
			$icon_url = ! empty( $card['iconUrl'] ) ? $card['iconUrl'] : ( ! empty( $card['iconId'] ) ? wp_get_attachment_url( (int) $card['iconId'] ) : '' );
			$title    = ! empty( $card['title'] ) ? $card['title'] : '';
			$text     = ! empty( $card['text'] ) ? $card['text'] : '';
			?>
			<article class="value-cards__card">
				<?php if ( $icon_url ) : ?>
					<div class="value-cards__icon">
						<img src="<?php echo esc_url( $icon_url ); ?>" alt="" width="64" height="64" aria-hidden="true" />
					</div>
				<?php endif; ?>
				<div class="value-cards__body">
					<?php if ( $title ) : ?>
						<h3 class="value-cards__title"><?php echo esc_html( $title ); ?></h3>
					<?php endif; ?>
					<?php if ( $text ) : ?>
						<p class="value-cards__text"><?php echo esc_html( $text ); ?></p>
					<?php endif; ?>
				</div>
			</article>
		<?php endforeach; ?>
	</div>
</section>
