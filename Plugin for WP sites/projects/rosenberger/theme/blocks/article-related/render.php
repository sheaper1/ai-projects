<?php
/**
 * Article Related — «Das könnte Ihnen auch gefallen»: 3 похожие статьи
 * (та же рубрика, без текущей; добор последними при нехватке).
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

$heading = wp_kses_post( $attributes['heading'] ?? '' );
$cats    = wp_get_post_categories( $post_id );

$base_args = array(
	'post_type'           => 'post',
	'posts_per_page'      => 3,
	'post__not_in'        => array( $post_id ),
	'ignore_sticky_posts' => true,
	'no_found_rows'       => true,
);

$related = new WP_Query( $cats ? array_merge( $base_args, array( 'category__in' => $cats ) ) : $base_args );

// Добор последними записями, если по рубрике меньше трёх.
$ids = wp_list_pluck( $related->posts, 'ID' );
if ( count( $ids ) < 3 ) {
	$fill = new WP_Query(
		array_merge(
			$base_args,
			array( 'post__not_in' => array_merge( array( $post_id ), $ids ) )
		)
	);
	$ids = array_slice( array_merge( $ids, wp_list_pluck( $fill->posts, 'ID' ) ), 0, 3 );
	wp_reset_postdata();
}

if ( empty( $ids ) ) {
	return;
}
?>
<section <?php echo get_block_wrapper_attributes( array( 'class' => 'article-related' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="article-related__inner">
		<?php if ( '' !== trim( wp_strip_all_tags( $heading ) ) ) : ?>
			<h2 class="article-related__heading"><?php echo $heading; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h2>
		<?php endif; ?>
		<div class="article-related__list">
			<?php
			foreach ( $ids as $rid ) {
				echo rosenberger_blog_card_html( (int) $rid, is_sticky( $rid ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
			?>
		</div>
	</div>
</section>
