<?php

if (!defined('ABSPATH')) {
    exit;
}

class DF_Subscriptions_Access
{
    public function current_user_can_read_paid_content()
    {
        if (current_user_can('manage_options')) {
            return true;
        }

        if (!is_user_logged_in()) {
            return false;
        }

        return $this->user_can_read_paid_content(get_current_user_id());
    }

    public function user_can_read_paid_content($user_id)
    {
        $user = get_userdata($user_id);

        if (!$user || !in_array(DF_Subscriptions::ROLE, (array) $user->roles, true)) {
            return false;
        }

        $expires_at = get_user_meta($user_id, DF_Subscriptions::EXPIRES_META, true);

        if (!$expires_at) {
            return false;
        }

        $expires_timestamp = strtotime($expires_at . ' 23:59:59');

        return $expires_timestamp && $expires_timestamp >= current_time('timestamp');
    }

    public function get_subscription_status_label($user_id)
    {
        $expires_at = get_user_meta($user_id, DF_Subscriptions::EXPIRES_META, true);

        if (!$expires_at) {
            return __('No expiration date', 'der-flugschreiber-subscriptions');
        }

        if ($this->user_can_read_paid_content($user_id)) {
            return sprintf(
                /* translators: %s: subscription expiration date. */
                __('Active until %s', 'der-flugschreiber-subscriptions'),
                esc_html($expires_at)
            );
        }

        return sprintf(
            /* translators: %s: subscription expiration date. */
            __('Expired on %s', 'der-flugschreiber-subscriptions'),
            esc_html($expires_at)
        );
    }
}
