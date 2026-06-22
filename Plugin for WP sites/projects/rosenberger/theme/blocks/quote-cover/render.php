<?php
defined( 'ABSPATH' ) || exit;

$a = wp_parse_args(
	$attributes,
	[
		'text'     => '',
		'imageId'  => 0,
		'imageUrl' => '',
	]
);

$img_url = $a['imageUrl'] ?: wp_get_attachment_image_url( (int) $a['imageId'], 'full' );
$wrapper = get_block_wrapper_attributes( [ 'class' => 'quote-cover' ] );
?>
<section <?php echo $wrapper; ?>>

	<?php if ( $img_url ) : ?>
		<img
			class="quote-cover__bg"
			src="<?php echo esc_url( $img_url ); ?>"
			alt=""
			aria-hidden="true"
			loading="lazy"
		/>
	<?php endif; ?>
	<div class="quote-cover__overlay" aria-hidden="true"></div>

	<?php if ( $a['text'] ) : ?>
		<p class="quote-cover__text"><?php echo wp_kses_post( nl2br( esc_html( $a['text'] ) ) ); ?></p>
	<?php endif; ?>

</section>
