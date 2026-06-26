<?php
defined( 'ABSPATH' ) || exit;

$a = wp_parse_args(
	$attributes,
	[
		'label'      => 'Über mich',
		'name'       => '',
		'jobTitle'   => '',
		'bio'        => '',
		'imageId'    => 0,
		'imageUrl'   => '',
		'nameCredit' => '',
	]
);

$img_url = $a['imageUrl'] ?: wp_get_attachment_image_url( (int) $a['imageId'], 'full' );
$wrapper = get_block_wrapper_attributes( [ 'class' => 'bio-hero' ] );
?>
<section <?php echo $wrapper; ?>>

	<div class="bio-hero__content">
		<?php if ( $a['label'] ) : ?>
			<p class="bio-hero__label"><?php echo esc_html( $a['label'] ); ?></p>
		<?php endif; ?>
		<?php if ( $a['name'] ) : ?>
			<h1 class="bio-hero__name"><?php echo esc_html( $a['name'] ); ?></h1>
		<?php endif; ?>
		<?php if ( $a['jobTitle'] ) : ?>
			<p class="bio-hero__job"><?php echo esc_html( $a['jobTitle'] ); ?></p>
		<?php endif; ?>
		<?php if ( $a['bio'] ) : ?>
			<p class="bio-hero__bio"><?php echo esc_html( $a['bio'] ); ?></p>
		<?php endif; ?>
	</div>

	<div class="bio-hero__media">
		<?php if ( $img_url ) : ?>
			<img
				src="<?php echo esc_url( $img_url ); ?>"
				alt="<?php echo esc_attr( $a['name'] ); ?>"
				loading="eager"
			/>
		<?php endif; ?>
		<?php if ( $a['nameCredit'] ) : ?>
			<p class="bio-hero__credit" aria-hidden="true"><?php echo wp_kses( $a['nameCredit'], [ 'br' => [] ] ); ?></p>
		<?php endif; ?>
	</div>

</section>
