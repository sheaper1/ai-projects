<?php
defined( 'ABSPATH' ) || exit;

$a       = wp_parse_args( $attributes, [
	'label'     => '',
	'value'     => '',
	'subtitle'  => '',
	'finePrint' => '',
	'imageUrl'  => '',
] );
$wrapper = get_block_wrapper_attributes( [ 'class' => 'provision-callout' ] );
?>
<section <?php echo $wrapper; ?>>
	<?php if ( $a['imageUrl'] ) : ?>
		<div class="provision-callout__bg" aria-hidden="true">
			<img src="<?php echo esc_url( $a['imageUrl'] ); ?>" alt="" />
		</div>
	<?php endif; ?>
	<div class="provision-callout__overlay" aria-hidden="true"></div>
	<div class="provision-callout__inner">
		<?php if ( $a['label'] ) : ?>
			<p class="provision-callout__label"><?php echo esc_html( $a['label'] ); ?></p>
		<?php endif; ?>
		<?php if ( $a['value'] ) : ?>
			<p class="provision-callout__value"><?php echo esc_html( $a['value'] ); ?></p>
		<?php endif; ?>
		<div class="provision-callout__body">
			<?php if ( $a['subtitle'] ) : ?>
				<p class="provision-callout__subtitle"><?php echo esc_html( $a['subtitle'] ); ?></p>
			<?php endif; ?>
			<?php if ( $a['finePrint'] ) : ?>
				<p class="provision-callout__fine"><?php echo esc_html( $a['finePrint'] ); ?></p>
			<?php endif; ?>
		</div>
	</div>
</section>
