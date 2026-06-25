<?php
/** Dynamic render for Split CTA. @package library */
defined( 'ABSPATH' ) || exit;

$a = wp_parse_args( $attributes, [
	'heading'       => '',
	'headingItalic' => '',
	'text'          => '',
	'buttonText'    => '',
	'buttonUrl'     => '#',
	'imageUrl'      => '',
	'imageLeft'     => false,
] );
$classes = 'split-cta' . ( ! empty( $a['imageLeft'] ) ? ' split-cta--image-left' : '' );
?>
<section <?php echo get_block_wrapper_attributes( [ 'class' => $classes ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="split-cta__inner">
		<div class="split-cta__text">
			<div class="split-cta__copy">
				<h2 class="split-cta__heading">
					<?php echo wp_kses_post( $a['heading'] ); ?>
					<?php if ( $a['headingItalic'] ) : ?><em><?php echo wp_kses_post( $a['headingItalic'] ); ?></em><?php endif; ?>
				</h2>
				<?php if ( $a['text'] ) : ?>
					<div class="split-cta__desc"><?php echo wp_kses_post( wpautop( $a['text'] ) ); ?></div>
				<?php endif; ?>
			</div>
			<?php if ( $a['buttonText'] ) : ?>
				<a class="split-cta__button" href="<?php echo esc_url( $a['buttonUrl'] ); ?>"><?php echo esc_html( $a['buttonText'] ); ?></a>
			<?php endif; ?>
		</div>
		<?php if ( $a['imageUrl'] ) : ?>
			<div class="split-cta__media">
				<img src="<?php echo esc_url( $a['imageUrl'] ); ?>" alt="" />
			</div>
		<?php endif; ?>
	</div>
</section>
