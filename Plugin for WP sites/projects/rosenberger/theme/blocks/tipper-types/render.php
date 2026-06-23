<?php
defined( 'ABSPATH' ) || exit;

$a     = wp_parse_args( $attributes, [
	'headingStart'  => '',
	'headingItalic' => '',
	'headingLine2'  => '',
	'lead'          => '',
	'items'         => [],
] );
$items   = is_array( $a['items'] ) ? $a['items'] : [];
$wrapper = get_block_wrapper_attributes( [ 'class' => 'tipper-types' ] );
?>
<section <?php echo $wrapper; ?>>
	<div class="tipper-types__inner">
		<div class="tipper-types__header">
			<h2 class="tipper-types__heading">
				<?php echo wp_kses_post( $a['headingStart'] ); ?>
				<?php if ( $a['headingItalic'] ) : ?>
					<em><?php echo wp_kses_post( $a['headingItalic'] ); ?></em>
				<?php endif; ?>
				<?php if ( $a['headingLine2'] ) : ?>
					<br /><?php echo wp_kses_post( $a['headingLine2'] ); ?>
				<?php endif; ?>
			</h2>
			<?php if ( $a['lead'] ) : ?>
				<p class="tipper-types__lead"><?php echo esc_html( $a['lead'] ); ?></p>
			<?php endif; ?>
		</div>
		<div class="tipper-types__cards">
			<?php foreach ( $items as $item ) :
				$img_url = ! empty( $item['imageUrl'] ) ? esc_url( $item['imageUrl'] ) : '';
				$title   = ! empty( $item['title'] ) ? $item['title'] : '';
				$text    = ! empty( $item['text'] ) ? $item['text'] : '';
				?>
				<div class="tipper-types__card">
					<?php if ( $img_url ) : ?>
						<div class="tipper-types__card-image">
							<img src="<?php echo $img_url; ?>" alt="<?php echo esc_attr( $title ); ?>" />
						</div>
					<?php endif; ?>
					<div class="tipper-types__card-body">
						<?php if ( $title ) : ?>
							<h3 class="tipper-types__card-title"><?php echo esc_html( $title ); ?></h3>
						<?php endif; ?>
						<?php if ( $text ) : ?>
							<p class="tipper-types__card-text"><?php echo esc_html( $text ); ?></p>
						<?php endif; ?>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</section>
