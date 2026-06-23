<?php
defined( 'ABSPATH' ) || exit;

$a     = wp_parse_args( $attributes, [ 'heading' => '', 'lead' => '', 'items' => [] ] );
$items = is_array( $a['items'] ) ? $a['items'] : [];

$wrapper = get_block_wrapper_attributes( [ 'class' => 'how-it-works' ] );
?>
<section <?php echo $wrapper; ?>>
	<div class="how-it-works__inner">
		<div class="how-it-works__header">
			<?php if ( $a['heading'] ) : ?>
				<h2 class="how-it-works__heading"><?php echo wp_kses_post( $a['heading'] ); ?></h2>
			<?php endif; ?>
			<?php if ( $a['lead'] ) : ?>
				<p class="how-it-works__lead"><?php echo esc_html( $a['lead'] ); ?></p>
			<?php endif; ?>
		</div>
		<div class="how-it-works__cards">
			<?php foreach ( $items as $item ) :
				$icon_url = ! empty( $item['iconUrl'] ) ? esc_url( $item['iconUrl'] ) : '';
				$title    = ! empty( $item['title'] ) ? $item['title'] : '';
				$text     = ! empty( $item['text'] ) ? $item['text'] : '';
				?>
				<div class="how-it-works__card">
					<?php if ( $icon_url ) : ?>
						<div class="how-it-works__icon" aria-hidden="true">
							<img src="<?php echo $icon_url; ?>" alt="" />
						</div>
					<?php endif; ?>
					<div class="how-it-works__card-body">
						<?php if ( $title ) : ?>
							<h3 class="how-it-works__card-title"><?php echo wp_kses_post( $title ); ?></h3>
						<?php endif; ?>
						<?php if ( $text ) : ?>
							<p class="how-it-works__card-text"><?php echo esc_html( $text ); ?></p>
						<?php endif; ?>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</section>
