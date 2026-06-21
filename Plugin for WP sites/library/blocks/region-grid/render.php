<?php
/**
 * Серверный рендер блока Region Grid.
 *
 * @var array    $attributes Атрибуты блока.
 * @var WP_Block $block      Экземпляр блока.
 *
 * @package library
 */

$heading        = esc_html( $attributes['heading'] ?? 'Vor Ort in ganz' );
$heading_italic = esc_html( $attributes['headingItalic'] ?? '' );
$subtext        = isset( $attributes['subtext'] ) ? wp_kses( nl2br( $attributes['subtext'] ), [ 'br' => [] ] ) : '';
$regions        = isset( $attributes['regions'] ) && is_array( $attributes['regions'] ) ? $attributes['regions'] : [];

$wrapper = get_block_wrapper_attributes( [ 'class' => 'region-grid' ] );
?>
<section <?php echo $wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>

	<div class="region-grid__header">
		<h2 class="region-grid__title">
			<?php echo $heading; ?>
			<?php if ( $heading_italic ) : ?>
				<em><?php echo $heading_italic; ?></em>
			<?php endif; ?>
		</h2>
		<?php if ( $subtext ) : ?>
			<p class="region-grid__subtext"><?php echo $subtext; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
		<?php endif; ?>
	</div>

	<?php if ( ! empty( $regions ) ) : ?>
		<div class="region-grid__grid">
			<?php foreach ( $regions as $region ) :
				$img_url = esc_url( $region['mediaUrl'] ?? '' );
				$label   = esc_html( $region['label'] ?? '' );
				$url     = esc_url( $region['url'] ?? '' );
			?>
			<div class="region-grid__card">
				<?php if ( $img_url ) : ?>
					<img
						class="region-grid__img"
						src="<?php echo $img_url; ?>"
						alt="<?php echo $label; ?>"
						loading="lazy"
						decoding="async"
					/>
				<?php else : ?>
					<div class="region-grid__placeholder" aria-hidden="true"></div>
				<?php endif; ?>
				<div class="region-grid__overlay" aria-hidden="true"></div>
				<?php if ( $label ) : ?>
					<?php if ( $url ) : ?>
						<a class="region-grid__pill" href="<?php echo $url; ?>"><?php echo $label; ?> →</a>
					<?php else : ?>
						<span class="region-grid__pill"><?php echo $label; ?> →</span>
					<?php endif; ?>
				<?php endif; ?>
			</div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

</section>
