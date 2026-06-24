<?php
/** Dynamic render for Problem Cards. @package library */
defined( 'ABSPATH' ) || exit;

$a     = wp_parse_args( $attributes, [
	'heading'       => '',
	'headingItalic' => '',
	'intro'         => '',
	'items'         => [],
] );
$items = is_array( $a['items'] ) ? $a['items'] : [];
?>
<section <?php echo get_block_wrapper_attributes( [ 'class' => 'problem-cards' ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="problem-cards__inner">
		<div class="problem-cards__head">
			<h2 class="problem-cards__heading">
				<?php echo wp_kses_post( $a['heading'] ); ?>
				<?php if ( $a['headingItalic'] ) : ?><em><?php echo wp_kses_post( $a['headingItalic'] ); ?></em><?php endif; ?>
			</h2>
			<?php if ( $a['intro'] ) : ?>
				<p class="problem-cards__intro"><?php echo wp_kses_post( $a['intro'] ); ?></p>
			<?php endif; ?>
		</div>
		<div class="problem-cards__row" style="--pc-cols:<?php echo (int) max( 1, count( $items ) ); ?>">
			<?php foreach ( $items as $item ) : ?>
				<article class="problem-cards__card">
					<div class="problem-cards__icon">
						<?php if ( ! empty( $item['iconUrl'] ) ) : ?><img src="<?php echo esc_url( $item['iconUrl'] ); ?>" alt="" /><?php endif; ?>
					</div>
					<div class="problem-cards__text">
						<h3 class="problem-cards__title"><?php echo wp_kses_post( $item['title'] ?? '' ); ?></h3>
						<p class="problem-cards__desc"><?php echo wp_kses_post( $item['text'] ?? '' ); ?></p>
					</div>
				</article>
			<?php endforeach; ?>
		</div>
	</div>
</section>
