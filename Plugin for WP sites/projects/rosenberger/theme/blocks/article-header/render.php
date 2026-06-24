<?php
/**
 * Article Header — шапка статьи: заголовок + мета + обложка (featured image).
 * Берёт текущую запись (single.html) или postId из контекста query-loop.
 *
 * @var array    $attributes
 * @var WP_Block $block
 * @package rosenberger
 */

defined( 'ABSPATH' ) || exit;

$post_id = isset( $block->context['postId'] ) ? (int) $block->context['postId'] : get_the_ID();
if ( ! $post_id ) {
	return;
}

$thumb_id  = get_post_thumbnail_id( $post_id );
$thumb_src = $thumb_id ? wp_get_attachment_image_url( $thumb_id, 'full' ) : '';
?>
<section <?php echo get_block_wrapper_attributes( array( 'class' => 'article-header' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="article-header__inner">
		<div class="article-header__head">
			<h1 class="article-header__title"><?php echo esc_html( get_the_title( $post_id ) ); ?></h1>
			<?php echo rosenberger_blog_meta_html( $post_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>

		<?php if ( $thumb_src ) : ?>
			<div class="article-header__image">
				<img src="<?php echo esc_url( $thumb_src ); ?>" alt="<?php echo esc_attr( get_the_title( $post_id ) ); ?>">
				<?php if ( is_sticky( $post_id ) ) : ?>
					<span class="blog-badge">Empfohlener Beitrag</span>
				<?php endif; ?>
			</div>
		<?php endif; ?>
	</div>
</section>
