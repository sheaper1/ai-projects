<?php
defined( 'ABSPATH' ) || exit;

$wrapper        = get_block_wrapper_attributes( [ 'class' => 'consultation-cta' ] );
$heading        = wp_kses_post( $attributes['heading'] ?? '' );
$heading_italic = wp_kses_post( $attributes['headingItalic'] ?? '' );
$text           = wp_kses_post( $attributes['text'] ?? '' );
$button_text    = wp_kses_post( $attributes['buttonText'] ?? '' );
$button_url     = esc_url( $attributes['buttonUrl'] ?? '#' );
$background_url = esc_url( $attributes['backgroundUrl'] ?? '' );
?>
<section <?php echo $wrapper; ?>>
	<?php if ( $background_url ) : ?>
		<div class="consultation-cta__bg" aria-hidden="true"><img src="<?php echo $background_url; ?>" alt="" /></div>
	<?php endif; ?>
	<div class="consultation-cta__overlay" aria-hidden="true"></div>
	<div class="consultation-cta__inner">
		<h2 class="consultation-cta__heading">
			<?php echo $heading; ?>
			<?php if ( '' !== trim( wp_strip_all_tags( $heading_italic ) ) ) : ?>
				<em><?php echo $heading_italic; ?></em>
			<?php endif; ?>
		</h2>
		<p class="consultation-cta__text"><?php echo $text; ?></p>
		<a class="consultation-cta__button" href="<?php echo $button_url; ?>"><?php echo $button_text; ?></a>
	</div>
</section>
