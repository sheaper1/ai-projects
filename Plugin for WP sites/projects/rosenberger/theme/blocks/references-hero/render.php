<?php
/**
 * Referenzen-Hero: zentrierter Titel + breites Banner-Bild (Archiv-Kopf).
 * Banner liegt als Theme-Asset (assets/reference/hero-banner.webp).
 *
 * @var array $attributes
 *
 * @package rosenberger
 */

defined( 'ABSPATH' ) || exit;

$italic = wp_kses_post( $attributes['titleItalic'] ?? 'Erfolgreiche' );
$title  = wp_kses_post( $attributes['title'] ?? 'Immobilientransaktionen' );
$banner = get_stylesheet_directory_uri() . '/assets/reference/hero-banner.webp';
?>
<section <?php echo get_block_wrapper_attributes( array( 'class' => 'references-hero' ) ); ?>>
	<div class="references-hero__head">
		<h1 class="references-hero__title">
			<em><?php echo $italic; ?></em><br aria-hidden="true"><?php echo $title; ?>
		</h1>
	</div>
	<div class="references-hero__banner">
		<img src="<?php echo esc_url( $banner ); ?>" alt="" />
	</div>
</section>
