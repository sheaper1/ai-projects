<?php
defined( 'ABSPATH' ) || exit;

$a        = wp_parse_args( $attributes, [
	'headingStart'  => '',
	'headingItalic' => '',
	'headingEnd'    => '',
	'subtitle'      => '',
	'buttonText'    => '',
	'buttonUrl'     => '#',
	'disclaimer'    => '',
	'imageUrl'      => '',
] );
$wrapper = get_block_wrapper_attributes( [ 'class' => 'page-hero' ] );
?>
<section <?php echo $wrapper; ?>>
	<div class="page-hero__content">
		<div class="page-hero__inner">
			<h1 class="page-hero__heading">
				<?php echo wp_kses_post( $a['headingStart'] ); ?>
				<?php if ( $a['headingItalic'] ) : ?>
					<em><?php echo wp_kses_post( $a['headingItalic'] ); ?></em>
				<?php endif; ?>
				<?php echo wp_kses_post( $a['headingEnd'] ); ?>
			</h1>
			<?php if ( $a['subtitle'] ) : ?>
				<p class="page-hero__subtitle"><?php echo wp_kses_post( $a['subtitle'] ); ?></p>
			<?php endif; ?>
			<?php if ( $a['buttonText'] ) : ?>
				<div class="page-hero__cta">
					<a class="page-hero__button" href="<?php echo esc_url( $a['buttonUrl'] ); ?>"><?php echo esc_html( $a['buttonText'] ); ?></a>
					<?php if ( $a['disclaimer'] ) : ?>
						<p class="page-hero__disclaimer"><?php echo esc_html( $a['disclaimer'] ); ?></p>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>
	</div>
	<?php if ( $a['imageUrl'] ) : ?>
		<div class="page-hero__image" aria-hidden="true">
			<img src="<?php echo esc_url( $a['imageUrl'] ); ?>" alt="" />
		</div>
	<?php endif; ?>
</section>
