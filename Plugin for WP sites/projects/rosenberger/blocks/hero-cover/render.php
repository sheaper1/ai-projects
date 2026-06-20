<?php
/**
 * Серверный рендер блока Hero Cover.
 *
 * @var array    $attributes Атрибуты блока.
 * @var string   $content    (не используется — блок строится из атрибутов).
 * @var WP_Block $block      Экземпляр блока.
 *
 * @package library
 */

// Фон: выбранное изображение или дефолтный WebP из ассетов блока.
$bg = isset( $attributes['backgroundUrl'] ) ? trim( $attributes['backgroundUrl'] ) : '';
if ( '' === $bg ) {
	$asset_path = __DIR__ . '/assets/hero-bg.webp';
	if ( file_exists( $asset_path ) ) {
		$content_dir = wp_normalize_path( WP_CONTENT_DIR );
		$asset_norm  = wp_normalize_path( $asset_path );
		if ( str_starts_with( $asset_norm, $content_dir ) ) {
			$bg = content_url( substr( $asset_norm, strlen( $content_dir ) ) );
		}
	}
}

$wrapper = get_block_wrapper_attributes();

$menu_text    = esc_html( $attributes['menuText'] ?? 'Menu' );
$logo_text    = esc_html( $attributes['logoText'] ?? 'LOGO' );
$contact_text = esc_html( $attributes['contactText'] ?? 'Contact' );
$contact_url  = esc_url( $attributes['contactUrl'] ?? '#' );
$title_main   = esc_html( $attributes['titleMain'] ?? '' );
$title_accent = esc_html( $attributes['titleAccent'] ?? '' );
$subtitle     = esc_html( $attributes['subtitle'] ?? '' );
$columns      = isset( $attributes['columns'] ) && is_array( $attributes['columns'] ) ? $attributes['columns'] : array();
?>
<section <?php echo $wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php if ( $bg ) : ?>
		<img class="hero-cover__bg" src="<?php echo esc_url( $bg ); ?>" alt="" loading="eager" fetchpriority="high" decoding="async" />
	<?php endif; ?>
	<div class="hero-cover__overlay" aria-hidden="true"></div>
	<div class="hero-cover__inner">
		<header class="hero-cover__bar">
			<div class="hero-cover__menu">
				<svg width="32" height="32" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M4 6h16v2H4zM4 11h16v2H4zM4 16h10v2H4z"></path></svg>
				<span><?php echo $menu_text; ?></span>
			</div>
			<div class="hero-cover__logo">
				<span class="hero-cover__logo-box" aria-hidden="true"></span>
				<span class="hero-cover__logo-text"><?php echo $logo_text; ?></span>
			</div>
			<a class="hero-cover__contact" href="<?php echo $contact_url; ?>"><?php echo $contact_text; ?></a>
		</header>

		<div class="hero-cover__content">
			<h1 class="hero-cover__title"><?php echo $title_main; ?><?php if ( '' !== $title_accent ) : ?><em><?php echo $title_accent; ?></em><?php endif; ?></h1>
			<p class="hero-cover__subtitle"><?php echo $subtitle; ?></p>
		</div>

		<?php if ( ! empty( $columns ) ) : ?>
			<div class="hero-cover__cols">
				<?php foreach ( $columns as $col ) : ?>
					<p class="hero-cover__col"><?php echo esc_html( $col ); ?></p>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
</section>
