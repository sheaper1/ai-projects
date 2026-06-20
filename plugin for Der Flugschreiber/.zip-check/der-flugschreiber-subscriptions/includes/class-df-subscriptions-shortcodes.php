<?php

if (!defined('ABSPATH')) {
    exit;
}

class DF_Subscriptions_Shortcodes
{
    private $access;

    public function __construct(DF_Subscriptions_Access $access)
    {
        $this->access = $access;
    }

    public function hooks()
    {
        add_shortcode('df_login_form', array($this, 'login_form'));
        add_shortcode('df_logout_link', array($this, 'logout_link'));
    }

    public function login_form($atts)
    {
        $atts = shortcode_atts(
            array(
                'redirect' => '',
            ),
            $atts,
            'df_login_form'
        );

        if (is_user_logged_in()) {
            $message = $this->access->current_user_can_read_paid_content()
                ? __('You are logged in and your subscription is active.', 'der-flugschreiber-subscriptions')
                : __('You are logged in, but your subscription is not active.', 'der-flugschreiber-subscriptions');

            return '<div class="df-subscriptions-login-status"><p>' . esc_html($message) . '</p>' . $this->logout_link(array()) . '</div>';
        }

        $redirect = $atts['redirect'] ? esc_url_raw($atts['redirect']) : $this->get_current_url();

        ob_start();
        wp_login_form(
            array(
                'echo' => true,
                'redirect' => $redirect,
                'form_id' => 'df-subscriptions-login-form',
                'label_username' => __('Email or username', 'der-flugschreiber-subscriptions'),
                'label_password' => __('Password', 'der-flugschreiber-subscriptions'),
                'label_remember' => __('Remember me', 'der-flugschreiber-subscriptions'),
                'label_log_in' => __('Log in', 'der-flugschreiber-subscriptions'),
            )
        );

        return '<div class="df-subscriptions-login-form">' . ob_get_clean() . '</div>';
    }

    public function logout_link($atts)
    {
        $atts = shortcode_atts(
            array(
                'text' => __('Log out', 'der-flugschreiber-subscriptions'),
                'redirect' => home_url('/'),
            ),
            $atts,
            'df_logout_link'
        );

        if (!is_user_logged_in()) {
            return '';
        }

        return sprintf(
            '<a class="df-subscriptions-logout-link" href="%s">%s</a>',
            esc_url(wp_logout_url(esc_url_raw($atts['redirect']))),
            esc_html($atts['text'])
        );
    }

    private function get_current_url()
    {
        global $wp;

        if (isset($wp->request)) {
            return home_url(add_query_arg(array(), $wp->request));
        }

        return home_url('/');
    }
}
