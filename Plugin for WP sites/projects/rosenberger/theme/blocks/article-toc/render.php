<?php
/**
 * Article TOC — оболочка оглавления. Список наполняет view.js из заголовков
 * статьи (h2/h3) на фронте. Без заголовков блок прячется (см. view.js).
 *
 * @var array $attributes
 * @package rosenberger
 */

defined( 'ABSPATH' ) || exit;

$heading = wp_kses_post( $attributes['heading'] ?? 'Inhaltsverzeichnis' );
?>
<section <?php echo get_block_wrapper_attributes( array( 'class' => 'article-toc' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> hidden>
	<div class="article-toc__inner">
		<div class="article-toc__card">
			<button type="button" class="article-toc__toggle" aria-expanded="true">
				<span class="article-toc__title"><?php echo $heading; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
				<span class="article-toc__icon" aria-hidden="true"><?php echo rosenberger_blog_icon( 'chevron' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
			</button>
			<div class="article-toc__body">
				<div class="article-toc__body-inner">
					<hr class="article-toc__divider">
					<ol class="toc-list" data-toc-list></ol>
				</div>
			</div>
		</div>
	</div>
</section>
