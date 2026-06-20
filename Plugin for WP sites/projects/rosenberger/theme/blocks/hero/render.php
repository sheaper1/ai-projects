<?php
/**
 * Серверный рендер блока Hero.
 *
 * @var array    $attributes Атрибуты блока.
 * @var string   $content    HTML вложенных блоков (InnerBlocks).
 * @var WP_Block $block      Экземпляр блока.
 *
 * @package library
 */

if ( '' === trim( (string) $content ) ) {
	return;
}

$wrapper_attributes = get_block_wrapper_attributes();
?>
<section <?php echo wp_kses_data( $wrapper_attributes ); ?>>
	<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — разметка вложенных блоков уже санитизирована ядром. ?>
</section>
