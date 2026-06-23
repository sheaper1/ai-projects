<?php
defined( 'ABSPATH' ) || exit;

$a          = wp_parse_args( $attributes, [ 'heading' => '', 'lead' => '', 'wpformsId' => 0 ] );
$wpforms_id = (int) $a['wpformsId'];
$funnel_file = __DIR__ . '/assets/funnel.html';
$wrapper    = get_block_wrapper_attributes( [ 'class' => 'tipper-form' ] );
?>
<section <?php echo $wrapper; ?>>
	<div class="tipper-form__header">
		<?php if ( $a['heading'] ) : ?>
			<h2 class="tipper-form__heading"><?php echo esc_html( $a['heading'] ); ?></h2>
		<?php endif; ?>
		<?php if ( $a['lead'] ) : ?>
			<p class="tipper-form__lead"><?php echo esc_html( $a['lead'] ); ?></p>
		<?php endif; ?>
	</div>

	<div class="tipper-form__funnel">
		<?php if ( file_exists( $funnel_file ) ) : ?>
			<?php echo file_get_contents( $funnel_file ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<?php endif; ?>
	</div>

	<?php if ( $wpforms_id ) : ?>
		<div aria-hidden="true" style="position:absolute;left:-99999px;top:0;width:1px;height:1px;overflow:hidden">
			<?php echo do_shortcode( '[wpforms id="' . $wpforms_id . '"]' ); ?>
		</div>
	<?php endif; ?>
</section>
