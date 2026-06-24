<?php
/**
 * Blog Hero — заголовок блога + крупная карточка последней (featured) статьи.
 *
 * @var array $attributes
 * @package rosenberger
 */

defined( 'ABSPATH' ) || exit;

$eyebrow        = wp_kses_post( $attributes['eyebrow'] ?? '' );
$heading_italic = wp_kses_post( $attributes['headingItalic'] ?? '' );
$heading        = wp_kses_post( $attributes['heading'] ?? '' );

$featured = new WP_Query(
	array(
		'post_type'      => 'post',
		'posts_per_page' => 1,
		'no_found_rows'  => true,
	)
);
$featured_id = $featured->have_posts() ? (int) $featured->posts[0]->ID : 0;
wp_reset_postdata();

$thumb_id  = $featured_id ? get_post_thumbnail_id( $featured_id ) : 0;
$thumb_src = $thumb_id ? wp_get_attachment_image_url( $thumb_id, 'full' ) : '';
?>
<section <?php echo get_block_wrapper_attributes( array( 'class' => 'blog-hero' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="blog-hero__inner">
		<div class="blog-hero__head">
			<?php if ( '' !== trim( wp_strip_all_tags( $eyebrow ) ) ) : ?>
				<p class="blog-hero__eyebrow"><?php echo $eyebrow; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
			<?php endif; ?>
			<h1 class="blog-hero__title"><?php
				if ( '' !== trim( wp_strip_all_tags( $heading_italic ) ) ) {
					echo '<em>' . $heading_italic . '</em><br>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				}
				echo '<span>' . $heading . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			?></h1>
		</div>

		<?php if ( $featured_id ) : ?>
			<a class="blog-featured" href="<?php echo esc_url( get_permalink( $featured_id ) ); ?>">
				<div class="blog-featured__image">
					<?php if ( $thumb_src ) : ?>
						<img src="<?php echo esc_url( $thumb_src ); ?>" alt="<?php echo esc_attr( get_the_title( $featured_id ) ); ?>">
					<?php endif; ?>
					<?php if ( is_sticky( $featured_id ) ) : ?>
						<span class="blog-badge">Empfohlener Beitrag</span>
					<?php endif; ?>
				</div>
				<div class="blog-featured__body">
					<?php echo rosenberger_blog_meta_html( $featured_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<h2 class="blog-featured__title"><?php echo esc_html( get_the_title( $featured_id ) ); ?></h2>
					<span class="blog-featured__more">Weiterlesen&nbsp;&rarr;</span>
				</div>
			</a>
		<?php endif; ?>
	</div>
</section>
