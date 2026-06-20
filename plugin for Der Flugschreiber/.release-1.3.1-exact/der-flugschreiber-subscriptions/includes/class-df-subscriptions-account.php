<?php

if (!defined('ABSPATH')) {
    exit;
}

class DF_Subscriptions_Account
{
    private $access;

    public function __construct(DF_Subscriptions_Access $access)
    {
        $this->access = $access;
    }

    public function hooks()
    {
        add_action('init', array($this, 'handle_profile_update'));
        add_shortcode('df_account', array($this, 'account'));
    }

    public function handle_profile_update()
    {
        if (!isset($_POST['df_account_action']) || !is_user_logged_in()) {
            return;
        }

        $return_url = isset($_POST['df_account_url']) ? wp_validate_redirect(esc_url_raw(wp_unslash($_POST['df_account_url'])), home_url('/')) : home_url('/');

        if (!isset($_POST['df_account_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['df_account_nonce'])), 'df_update_account')) {
            wp_safe_redirect(add_query_arg('df_account_message', 'security', $return_url));
            exit;
        }

        $user_id = get_current_user_id();
        $display_name = isset($_POST['df_display_name']) ? sanitize_text_field(wp_unslash($_POST['df_display_name'])) : '';

        if (!$display_name) {
            wp_safe_redirect(add_query_arg('df_account_message', 'invalid', $return_url));
            exit;
        }

        $result = wp_update_user(
            array(
                'ID' => $user_id,
                'display_name' => $display_name,
            )
        );

        wp_safe_redirect(add_query_arg('df_account_message', is_wp_error($result) ? 'invalid' : 'updated', $return_url));
        exit;
    }

    public function account()
    {
        if (!is_user_logged_in()) {
            $login_url = get_option(DF_Subscriptions::LOGIN_URL_OPTION, '');
            $login_url = $login_url ? add_query_arg('redirect_to', $this->get_current_url(), $login_url) : wp_login_url($this->get_current_url());

            return '<div class="df-subscriptions-account"><p>' . esc_html__('Please log in to view your subscription.', 'der-flugschreiber-subscriptions') . '</p><a class="button" href="' . esc_url($login_url) . '">' . esc_html__('Log in', 'der-flugschreiber-subscriptions') . '</a></div>';
        }

        $user_id = get_current_user_id();
        $user = wp_get_current_user();
        $status = $this->access->get_subscription_status($user_id);
        $expires_at = get_user_meta($user_id, DF_Subscriptions::EXPIRES_META, true);
        $payment_url = get_option(DF_Subscriptions::PAYMENT_URL_OPTION, '');
        $message = isset($_GET['df_account_message']) ? sanitize_key(wp_unslash($_GET['df_account_message'])) : '';
        $display_name_id = wp_unique_id('df_account_display_name_');

        ob_start();
        ?>
        <section class="df-subscriptions-account">
            <h2><?php esc_html_e('My subscription', 'der-flugschreiber-subscriptions'); ?></h2>

            <?php if ('updated' === $message) : ?>
                <p class="df-subscriptions-account__notice"><?php esc_html_e('Your profile was updated.', 'der-flugschreiber-subscriptions'); ?></p>
            <?php elseif ($message) : ?>
                <p class="df-subscriptions-account__error"><?php esc_html_e('The profile could not be updated.', 'der-flugschreiber-subscriptions'); ?></p>
            <?php endif; ?>

            <dl class="df-subscriptions-account__details">
                <div><dt><?php esc_html_e('Email', 'der-flugschreiber-subscriptions'); ?></dt><dd><?php echo esc_html($user->user_email); ?></dd></div>
                <div><dt><?php esc_html_e('Status', 'der-flugschreiber-subscriptions'); ?></dt><dd><?php echo esc_html(ucfirst($status)); ?></dd></div>
                <div><dt><?php esc_html_e('Expires at', 'der-flugschreiber-subscriptions'); ?></dt><dd><?php echo esc_html($expires_at ? $expires_at : __('Not set', 'der-flugschreiber-subscriptions')); ?></dd></div>
            </dl>

            <?php if ($payment_url) : ?>
                <p><a class="button df-subscriptions-payment-link" href="<?php echo esc_url($payment_url); ?>"><?php esc_html_e('Renew subscription', 'der-flugschreiber-subscriptions'); ?></a></p>
            <?php endif; ?>

            <form method="post" action="">
                <?php wp_nonce_field('df_update_account', 'df_account_nonce'); ?>
                <input type="hidden" name="df_account_action" value="1">
                <input type="hidden" name="df_account_url" value="<?php echo esc_attr($this->get_current_url()); ?>">
                <p>
                    <label for="<?php echo esc_attr($display_name_id); ?>"><?php esc_html_e('Display name', 'der-flugschreiber-subscriptions'); ?></label>
                    <input type="text" id="<?php echo esc_attr($display_name_id); ?>" name="df_display_name" value="<?php echo esc_attr($user->display_name); ?>" required>
                </p>
                <p><button type="submit" class="button"><?php esc_html_e('Save profile', 'der-flugschreiber-subscriptions'); ?></button></p>
            </form>

            <p><a href="<?php echo esc_url(wp_lostpassword_url($this->get_current_url())); ?>"><?php esc_html_e('Change password', 'der-flugschreiber-subscriptions'); ?></a></p>
        </section>
        <?php

        return ob_get_clean();
    }

    private function get_current_url()
    {
        return isset($_SERVER['REQUEST_URI']) ? home_url(esc_url_raw(wp_unslash($_SERVER['REQUEST_URI']))) : home_url('/');
    }
}
