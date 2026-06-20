<?php

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

if ('1' !== get_option('df_subscription_delete_data', '0')) {
    return;
}

delete_option('df_subscription_payment_url');
delete_option('df_subscription_login_url');
delete_option('df_subscription_require_magazine');
delete_option('df_subscription_delete_data');
delete_option('df_subscription_email_notices');
delete_option('df_subscription_author_name');

// Editable website texts and prices are stored as df_text_* options.
global $wpdb;
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'df_text\_%'");

$users = get_users(array('role' => 'df_subscriber', 'fields' => 'ID'));

foreach ($users as $user_id) {
    delete_user_meta($user_id, 'df_subscription_expires_at');
    delete_user_meta($user_id, 'df_subscription_status');
    delete_user_meta($user_id, 'df_subscription_history');
    delete_user_meta($user_id, 'df_subscription_reminder_sent');
    delete_user_meta($user_id, 'df_subscription_expired_notice_sent');
}

$posts = get_posts(
    array(
        'post_type' => array('df_magazine', 'df_article'),
        'post_status' => 'any',
        'numberposts' => -1,
        'fields' => 'ids',
    )
);

foreach ($posts as $post_id) {
    wp_delete_post($post_id, true);
}

$taxonomies = array('df_topic_category', 'df_issue_year');

foreach ($taxonomies as $taxonomy) {
    $terms = get_terms(array('taxonomy' => $taxonomy, 'hide_empty' => false, 'fields' => 'ids'));

    if (!is_wp_error($terms)) {
        foreach ($terms as $term_id) {
            wp_delete_term($term_id, $taxonomy);
        }
    }
}

remove_role('df_subscriber');

$protected_directories = array(
    trailingslashit(dirname(untrailingslashit(ABSPATH))) . 'df-subscriptions-protected',
    trailingslashit(WP_CONTENT_DIR) . 'df-subscriptions-protected',
);

foreach (array_unique($protected_directories) as $protected_directory) {
    if (is_dir($protected_directory)) {
        $files = glob(trailingslashit($protected_directory) . '*');

        if (is_array($files)) {
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }

        foreach (array('.htaccess', 'web.config') as $hidden_file) {
            $path = trailingslashit($protected_directory) . $hidden_file;

            if (is_file($path)) {
                unlink($path);
            }
        }

        rmdir($protected_directory);
    }
}
