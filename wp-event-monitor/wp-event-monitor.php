<?php
/**
 * Plugin Name: WP Event Monitor
 * Plugin URI: https://example.com/wp-event-monitor
 * Description: Automatisch Events von definierten Websites scrapen und als WordPress Drafts speichern
 * Version: 1.1.13
 * Update URI: https://neli.digirelation.dev/wp-event-monitor/
 * Author: Digirelation
 * Author URI: https://example.com
 * Text Domain: wp-event-monitor
 * Domain Path: /languages
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires: 5.0
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants
define( 'WEM_PLUGIN_FILE', __FILE__ );
define( 'WEM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WEM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WEM_VERSION', '1.1.13' );

// Load all classes
require_once WEM_PLUGIN_DIR . 'includes/class-database.php';
require_once WEM_PLUGIN_DIR . 'includes/class-scraper.php';
require_once WEM_PLUGIN_DIR . 'includes/class-keyword-matcher.php';
require_once WEM_PLUGIN_DIR . 'includes/class-post-creator.php';
require_once WEM_PLUGIN_DIR . 'includes/class-content-types.php';
require_once WEM_PLUGIN_DIR . 'includes/class-events-shortcode.php';
require_once WEM_PLUGIN_DIR . 'includes/class-source-manager.php';
require_once WEM_PLUGIN_DIR . 'includes/class-cron.php';
require_once WEM_PLUGIN_DIR . 'admin/class-admin.php';

// Plugin Activation Hook
register_activation_hook( WEM_PLUGIN_FILE, 'wem_activate_plugin' );

/**
 * Activate plugin.
 */
function wem_activate_plugin() {
	WEM_Content_Types::register();
	WEM_Database::create_tables();
	WEM_Database::seed_default_configuration();
	update_option( 'wem_create_sample_events', '1' );
	flush_rewrite_rules();
}

// Initialize plugin
add_action( 'plugins_loaded', function() {
	load_plugin_textdomain( 'wp-event-monitor', false, dirname( plugin_basename( WEM_PLUGIN_FILE ) ) . '/languages' );

	WEM_Content_Types::init();
	WEM_Database::maybe_upgrade();
	WEM_Database::seed_default_configuration();

	// Initialize admin interface
	if ( is_admin() ) {
		WEM_Admin::init();
	}

	// Initialize cron
	WEM_Cron::init();

	// Initialize frontend shortcodes
	WEM_Events_Shortcode::init();
} );

add_filter( 'post_thumbnail_html', 'wem_featured_image_credit_html', 10, 5 );
add_action( 'wp_head', 'wem_featured_image_credit_styles' );
add_shortcode( 'wem_image_credit', 'wem_image_credit_shortcode' );
add_shortcode( 'wem_featured_image_with_credit', 'wem_featured_image_with_credit_shortcode' );

/**
 * Add a small expandable image source badge to imported event featured images.
 *
 * @param string $html Featured image HTML
 * @param int    $post_id Post ID
 * @param int    $post_thumbnail_id Attachment ID
 * @param string $size Image size
 * @param array  $attr Image attributes
 *
 * @return string Filtered HTML
 */
function wem_featured_image_credit_html( $html, $post_id, $post_thumbnail_id, $size, $attr ) {
	if ( empty( $html ) || get_post_type( $post_id ) !== 'event' ) {
		return $html;
	}

	$credit = wem_get_image_credit_badge( $post_id );
	if ( empty( $credit ) ) {
		return $html;
	}

	return '<span class="wem-featured-image-credit-wrap">' . $html . $credit . '</span>';
}

/**
 * Output only the image credit badge for Elementor templates.
 *
 * @param array $atts Shortcode attributes
 *
 * @return string HTML
 */
function wem_image_credit_shortcode( $atts ) {
	$atts = shortcode_atts(
		array(
			'post_id' => get_the_ID(),
		),
		$atts,
		'wem_image_credit'
	);

	return wem_get_image_credit_badge( (int) $atts['post_id'] );
}

/**
 * Output a featured image wrapped with the image credit badge.
 *
 * @param array $atts Shortcode attributes
 *
 * @return string HTML
 */
function wem_featured_image_with_credit_shortcode( $atts ) {
	$atts = shortcode_atts(
		array(
			'post_id' => get_the_ID(),
			'size' => 'large',
		),
		$atts,
		'wem_featured_image_with_credit'
	);

	$post_id = (int) $atts['post_id'];
	if ( ! $post_id || get_post_type( $post_id ) !== 'event' ) {
		return '';
	}

	$image = get_the_post_thumbnail( $post_id, sanitize_key( $atts['size'] ) );
	if ( empty( $image ) ) {
		return '';
	}

	return '<span class="wem-featured-image-credit-wrap">' . $image . wem_get_image_credit_badge( $post_id ) . '</span>';
}

/**
 * Build the expandable image source badge.
 *
 * @param int $post_id Post ID
 *
 * @return string HTML
 */
function wem_get_image_credit_badge( $post_id ) {
	$credit_url = get_post_meta( $post_id, '_em_image_credit_url', true );
	if ( empty( $credit_url ) ) {
		$credit_url = '#';
	}

	$host = wp_parse_url( $credit_url, PHP_URL_HOST );
	$label = $host ? $host : __( 'Test photo source', 'wp-event-monitor' );

	return sprintf(
		'<a class="wem-featured-image-credit" href="%1$s" target="_blank" rel="noopener noreferrer"><span class="wem-featured-image-credit-symbol">&copy;</span><span class="wem-featured-image-credit-text">%2$s %3$s</span></a>',
		esc_url( $credit_url ),
		esc_html__( 'Image source:', 'wp-event-monitor' ),
		esc_html( $label )
	);
}

/**
 * Print minimal styles for the expandable image source badge.
 */
function wem_featured_image_credit_styles() {
	?>
	<style>
		.wem-featured-image-credit-wrap {
			display: inline-block;
			position: relative;
			max-width: 100%;
		}

		.wem-featured-image-credit-wrap img {
			display: block;
		}

		.wem-featured-image-credit {
			align-items: center;
			background: rgba(0, 0, 0, 0.72);
			border-radius: 999px;
			bottom: 8px;
			color: #fff;
			display: inline-flex;
			font-size: 12px;
			gap: 6px;
			left: 8px;
			line-height: 1;
			max-width: calc(100% - 16px);
			min-height: 24px;
			padding: 0 8px;
			position: absolute;
			text-decoration: none;
			z-index: 3;
		}

		.wem-featured-image-credit:focus,
		.wem-featured-image-credit:hover {
			color: #fff;
			text-decoration: none;
		}

		.wem-featured-image-credit-symbol {
			font-weight: 700;
		}

		.wem-featured-image-credit-text {
			display: inline-block;
			max-width: 0;
			opacity: 0;
			overflow: hidden;
			transition: max-width 180ms ease, opacity 180ms ease;
			white-space: nowrap;
		}

		.wem-featured-image-credit:focus .wem-featured-image-credit-text,
		.wem-featured-image-credit:hover .wem-featured-image-credit-text {
			max-width: 260px;
			opacity: 1;
		}
	</style>
	<?php
}

// Plugin Deactivation Hook
register_deactivation_hook( WEM_PLUGIN_FILE, 'wem_deactivate_plugin' );

/**
 * Deactivate plugin.
 */
function wem_deactivate_plugin() {
	WEM_Cron::unschedule();
	flush_rewrite_rules();
}








