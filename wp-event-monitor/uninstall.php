<?php
/**
 * Uninstall script for WP Event Monitor
 *
 * Removes all plugin data when uninstalled
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// Remove custom tables
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}em_sources" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}em_keywords" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}em_seen" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}em_log" );

// Remove options
delete_option( 'wem_db_version' );
delete_option( 'wem_cron_interval' );
delete_option( 'wem_schedule_type' );
delete_option( 'wem_schedule_day' );
delete_option( 'wem_schedule_time' );
delete_option( 'wem_fallback_image_ids' );

// Remove cron job
wp_clear_scheduled_hook( 'wem_scrape_all_sources' );

// Remove all post meta
$wpdb->query(
	"DELETE FROM {$wpdb->postmeta}
	 WHERE meta_key LIKE '_em_%'"
);
