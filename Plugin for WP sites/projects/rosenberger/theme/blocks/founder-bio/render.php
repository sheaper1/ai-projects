<?php
defined( 'ABSPATH' ) || exit;

$a = wp_parse_args(
	$attributes,
	[
		'heading'    => '',
		'paragraphs' => [],
		'imageId'    => 0,
		'imageUrl'   => '',
	]
);

$paragraphs = is_array( $a['paragraphs'] ) ? array_filter( $a['paragraphs'] ) : [];
$img_url    = $a['imageUrl'] ?: wp_get_attachment_image_url( (int) $a['imageId'], 'large' );
$wrapper    = get_block_wrapper_attributes( [ 'class' => 'founder-bio' ] );
?>
<section <?php echo $wrapper; ?>>

	<div class="founder-bio__content">
		<?php if ( $a['heading'] ) : ?>
			<h2 class="founder-bio__heading"><?php echo esc_html( $a['heading'] ); ?></h2>
		<?php endif; ?>
		<?php foreach ( $paragraphs as $para ) : ?>
			<p class="founder-bio__para"><?php echo esc_html( $para ); ?></p>
		<?php endforeach; ?>
	</div>

	<div class="founder-bio__media">
		<?php if ( $img_url ) : ?>
			<img
				src="<?php echo esc_url( $img_url ); ?>"
				alt="<?php echo esc_attr( $a['heading'] ); ?>"
				loading="lazy"
			/>
		<?php endif; ?>
	</div>

</section>
