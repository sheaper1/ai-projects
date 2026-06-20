<?php
/**
 * Plugin Name: Der Flugschreiber Subscriptions
 * Description: Paid magazine and article access for Der Flugschreiber subscribers.
 * Version: 1.8.9
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: Der Flugschreiber
 * Text Domain: der-flugschreiber-subscriptions
 */

if (!defined('ABSPATH')) {
    exit;
}

define('DF_SUBSCRIPTIONS_VERSION', '1.8.9');
define('DF_SUBSCRIPTIONS_FILE', __FILE__);
define('DF_SUBSCRIPTIONS_PATH', plugin_dir_path(__FILE__));
define('DF_SUBSCRIPTIONS_URL', plugin_dir_url(__FILE__));
define('DF_SUBSCRIPTIONS_PLANE_URL', DF_SUBSCRIPTIONS_URL . 'assets/images/df-plane.svg');
define('DF_SUBSCRIPTIONS_PLANE_ALT_URL', DF_SUBSCRIPTIONS_URL . 'assets/images/df-plane-alt.svg');

require_once DF_SUBSCRIPTIONS_PATH . 'includes/class-df-subscriptions.php';

register_activation_hook(__FILE__, array('DF_Subscriptions', 'activate'));
register_deactivation_hook(__FILE__, array('DF_Subscriptions', 'deactivate'));

add_action('plugins_loaded', array('DF_Subscriptions', 'instance'));
