<?php
/** Dynamic render for Error 404. @package library */
defined( 'ABSPATH' ) || exit;

$a = wp_parse_args( $attributes ?? array(), array(
	'headingMain'   => '',
	'headingItalic' => '',
	'lead'          => '',
	'buttonText'    => '',
	'buttonUrl'     => '/',
	'imageId'       => 0,
	'imageUrl'      => '',
) );

// Фото из медиатеки по slug (страница-шаблон не может передать media URL в атрибутах).
$image_url = $a['imageUrl'];
if ( ! $image_url ) {
	$att = get_posts( array(
		'post_type'      => 'attachment',
		'name'           => 'rosenberger-404-building',
		'posts_per_page' => 1,
		'post_status'    => 'inherit',
	) );
	if ( ! empty( $att ) ) {
		$image_url = wp_get_attachment_url( $att[0]->ID );
	}
}
?>
<section <?php echo get_block_wrapper_attributes( array( 'class' => 'error-404' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="error-404__inner">
		<div class="error-404__text">
			<h1 class="error-404__heading"><?php echo esc_html( $a['headingMain'] ); ?><br><em><?php echo esc_html( $a['headingItalic'] ); ?></em></h1>
			<?php if ( $a['lead'] ) : ?>
				<p class="error-404__lead"><?php echo wp_kses_post( $a['lead'] ); ?></p>
			<?php endif; ?>
			<?php if ( $a['buttonText'] ) : ?>
				<a class="error-404__button" href="<?php echo esc_url( $a['buttonUrl'] ); ?>"><?php echo esc_html( $a['buttonText'] ); ?></a>
			<?php endif; ?>
		</div>
	</div>
	<div class="error-404__banner">
		<?php if ( $image_url ) : ?>
			<img class="error-404__photo" src="<?php echo esc_url( $image_url ); ?>" alt="" />
		<?php endif; ?>
		<span class="error-404__num" aria-hidden="true">404</span>
	</div>
</section>
