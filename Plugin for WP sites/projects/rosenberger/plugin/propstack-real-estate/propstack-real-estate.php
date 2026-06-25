<?php
/**
 * Plugin Name: Propstack Real Estate Sync
 * Plugin URI:  https://digirelation.com
 * Description: Synchronisiert Immobilien aus Propstack nach WordPress. Listing, Detailseiten, Kontaktformular und Lead-Rückführung.
 * Version:     1.0.0
 * Author:      digirelation
 * Author URI:  https://digirelation.com
 * License:     GPL-2.0-or-later
 * Text Domain: propstack-re
 * Domain Path: /languages
 * Requires PHP: 8.1
 * Requires at least: 6.0
 */

defined( 'ABSPATH' ) || exit;

define( 'PROPSTACK_RE_VERSION',   '1.0.0' );
define( 'PROPSTACK_RE_FILE',      __FILE__ );
define( 'PROPSTACK_RE_PATH',      plugin_dir_path( __FILE__ ) );
define( 'PROPSTACK_RE_URL',       plugin_dir_url( __FILE__ ) );
define( 'PROPSTACK_RE_SLUG',      'propstack-re' );

require_once PROPSTACK_RE_PATH . 'includes/class-plugin.php';

register_activation_hook(   __FILE__, [ 'Propstack_RE_Plugin', 'activate'   ] );
register_deactivation_hook( __FILE__, [ 'Propstack_RE_Plugin', 'deactivate' ] );

add_action( 'plugins_loaded', [ 'Propstack_RE_Plugin', 'get_instance' ] );
