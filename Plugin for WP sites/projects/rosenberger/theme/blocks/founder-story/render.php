<?php
defined( 'ABSPATH' ) || exit;

$a = wp_parse_args(
	$attributes,
	[
		'heading' => '',
		'lead'    => '',
		'body'    => '',
		'quote'   => '',
	]
);

$wrapper = get_block_wrapper_attributes( [ 'class' => 'founder-story' ] );
?>
<section <?php echo $wrapper; ?>>
	<div class="founder-story__inner">

		<div class="founder-story__heading-col">
			<?php if ( $a['heading'] ) : ?>
				<h2 class="founder-story__heading"><?php echo wp_kses_post( $a['heading'] ); ?></h2>
			<?php endif; ?>
		</div>

		<div class="founder-story__body-col">
			<?php if ( $a['lead'] ) : ?>
				<p class="founder-story__lead"><?php echo wp_kses_post( nl2br( $a['lead'] ) ); ?></p>
			<?php endif; ?>
			<?php if ( $a['body'] ) : ?>
				<p class="founder-story__body"><?php echo wp_kses_post( nl2br( $a['body'] ) ); ?></p>
			<?php endif; ?>
			<?php if ( $a['quote'] ) : ?>
				<blockquote class="founder-story__quote">
					<p><?php echo wp_kses_post( nl2br( $a['quote'] ) ); ?></p>
				</blockquote>
			<?php endif; ?>
		</div>

	</div>
</section>
