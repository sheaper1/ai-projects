<?php
/**
 * Blog Grid — сетка статей (3 кол.) с пагинацией. Последняя статья показана
 * в blog-hero (featured), поэтому здесь она исключается.
 *
 * @var array $attributes
 * @package rosenberger
 */

defined( 'ABSPATH' ) || exit;

$heading  = wp_kses_post( $attributes['heading'] ?? '' );
$per_page = max( 1, (int) ( $attributes['postsPerPage'] ?? 9 ) );
$paged    = max( 1, (int) get_query_var( 'paged' ), (int) get_query_var( 'page' ) );

// Последняя статья (она же featured в blog-hero) — исключаем из сетки.
$newest  = new WP_Query(
	array(
		'post_type'      => 'post',
		'posts_per_page' => 1,
		'fields'         => 'ids',
		'no_found_rows'  => true,
	)
);
$exclude = ! empty( $newest->posts ) ? array( (int) $newest->posts[0] ) : array();
wp_reset_postdata();

$query = new WP_Query(
	array(
		'post_type'      => 'post',
		'posts_per_page' => $per_page,
		'paged'          => $paged,
		'post__not_in'   => $exclude,
	)
);

if ( ! $query->have_posts() ) {
	return;
}
?>
<section <?php echo get_block_wrapper_attributes( array( 'class' => 'blog-grid' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="blog-grid__inner">
		<?php if ( '' !== trim( wp_strip_all_tags( $heading ) ) ) : ?>
			<h2 class="blog-grid__heading"><?php echo $heading; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h2>
		<?php endif; ?>

		<div class="blog-grid__list">
			<?php
			foreach ( $query->posts as $post_obj ) {
				echo rosenberger_blog_card_html( (int) $post_obj->ID, is_sticky( $post_obj->ID ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
			?>
		</div>

		<?php
		if ( $query->max_num_pages > 1 ) {
			$page_for_posts = (int) get_option( 'page_for_posts' );
			$base           = $page_for_posts ? trailingslashit( get_permalink( $page_for_posts ) ) : trailingslashit( home_url( '/' ) );
			$links          = paginate_links(
				array(
					'base'      => $base . '%_%',
					'format'    => 'page/%#%/',
					'current'   => $paged,
					'total'     => $query->max_num_pages,
					'prev_text' => '&larr;',
					'next_text' => '&rarr;',
					'mid_size'  => 1,
				)
			);
			if ( $links ) {
				echo '<nav class="blog-pagination" aria-label="Seiten">' . $links . '</nav>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
		}
		wp_reset_postdata();
		?>
	</div>
</section>
