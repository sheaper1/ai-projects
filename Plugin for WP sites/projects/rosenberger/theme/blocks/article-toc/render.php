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
				<span class="article-toc__icon" aria-hidden="true"><svg viewBox="0 0 24 24" width="24" height="24" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M11.2954 7.84315L5.63845 13.5001L7.05245 14.9141L12.0024 9.96411L16.9524 14.9141L18.3664 13.5001L12.7094 7.84315C12.5219 7.65565 12.2676 7.55029 12.0024 7.55029C11.7373 7.55029 11.483 7.65565 11.2954 7.84315Z" fill="currentColor"/></svg></span>
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
