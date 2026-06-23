<?php
defined( 'ABSPATH' ) || exit;

$a     = wp_parse_args( $attributes, [ 'heading' => '', 'items' => [] ] );
$items = is_array( $a['items'] ) ? $a['items'] : [];

$wrapper = get_block_wrapper_attributes( [ 'class' => 'promise-list' ] );
?>
<section <?php echo $wrapper; ?>>
	<div class="promise-list__inner">

		<div class="promise-list__heading-col">
			<?php if ( $a['heading'] ) : ?>
				<h2 class="promise-list__heading"><?php echo wp_kses_post( $a['heading'] ); ?></h2>
			<?php endif; ?>
		</div>

		<div class="promise-list__items-col">
			<?php foreach ( $items as $index => $item ) :
				$number = ! empty( $item['number'] ) ? $item['number'] : str_pad( $index + 1, 2, '0', STR_PAD_LEFT );
				$title  = ! empty( $item['title'] ) ? $item['title'] : '';
				$text   = ! empty( $item['text'] ) ? $item['text'] : '';
				?>
				<?php if ( $index > 0 ) : ?>
					<hr class="promise-list__divider" aria-hidden="true" />
				<?php endif; ?>
				<div class="promise-list__item">
					<span class="promise-list__number" aria-hidden="true"><?php echo esc_html( $number ); ?></span>
					<div class="promise-list__item-body">
						<?php if ( $title ) : ?>
							<h3 class="promise-list__item-title"><?php echo wp_kses_post( $title ); ?></h3>
						<?php endif; ?>
						<?php if ( $text ) : ?>
							<p class="promise-list__item-text"><?php echo esc_html( $text ); ?></p>
						<?php endif; ?>
					</div>
				</div>
			<?php endforeach; ?>
		</div>

	</div>
</section>
