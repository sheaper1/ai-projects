<?php
defined( 'ABSPATH' ) || exit;

$heading        = wp_kses_post( $attributes['heading'] ?? '' );
$heading_italic = wp_kses_post( $attributes['headingItalic'] ?? '' );
$text           = wp_kses_post( $attributes['text'] ?? '' );
$button_text    = wp_kses_post( $attributes['buttonText'] ?? '' );
$button_url     = esc_url( $attributes['buttonUrl'] ?? '#' );
$image_url      = esc_url( $attributes['imageUrl'] ?? '' );
$wrapper        = get_block_wrapper_attributes( [ 'class' => 'referral-cta' ] );
?>
<section <?php echo $wrapper; ?>>
	<div class="referral-cta__inner">
		<div class="referral-cta__content">
			<h2 class="referral-cta__heading">
				<?php echo $heading; ?>
				<?php if ( '' !== trim( wp_strip_all_tags( $heading_italic ) ) ) : ?>
					<em><?php echo $heading_italic; ?></em>
				<?php endif; ?>
			</h2>
			<p class="referral-cta__text"><?php echo $text; ?></p>
			<a class="referral-cta__button" href="<?php echo $button_url; ?>"><?php echo $button_text; ?></a>
		</div>
		<div class="referral-cta__media">
			<?php if ( $image_url ) : ?>
				<img src="<?php echo $image_url; ?>" alt="" />
			<?php endif; ?>
		</div>
	</div>
</section>
