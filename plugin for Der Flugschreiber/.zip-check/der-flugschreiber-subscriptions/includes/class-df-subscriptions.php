<?php

if (!defined('ABSPATH')) {
    exit;
}

require_once DF_SUBSCRIPTIONS_PATH . 'includes/class-df-subscriptions-access.php';
require_once DF_SUBSCRIPTIONS_PATH . 'includes/class-df-subscriptions-content.php';
require_once DF_SUBSCRIPTIONS_PATH . 'includes/class-df-subscriptions-shortcodes.php';
require_once DF_SUBSCRIPTIONS_PATH . 'includes/class-df-subscriptions-admin.php';

final class DF_Subscriptions
{
    const ROLE = 'df_subscriber';
    const MAGAZINE_POST_TYPE = 'df_magazine';
    const ARTICLE_POST_TYPE = 'df_article';
    const ARTICLE_MAGAZINE_META = '_df_magazine_id';
    const EXPIRES_META = 'df_subscription_expires_at';
    const PAYMENT_URL_OPTION = 'df_subscription_payment_url';

    private static $instance = null;

    public static function instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public static function activate()
    {
        self::add_role();
        self::instance()->content->register_post_types();
        flush_rewrite_rules();
    }

    public static function deactivate()
    {
        flush_rewrite_rules();
    }

    public static function add_role()
    {
        add_role(
            self::ROLE,
            __('DF Subscriber', 'der-flugschreiber-subscriptions'),
            array(
                'read' => true,
            )
        );
    }

    public $access;
    public $content;
    public $shortcodes;
    public $admin;

    private function __construct()
    {
        self::add_role();

        $this->access = new DF_Subscriptions_Access();
        $this->content = new DF_Subscriptions_Content($this->access);
        $this->shortcodes = new DF_Subscriptions_Shortcodes($this->access);
        $this->admin = new DF_Subscriptions_Admin($this->access);

        $this->content->hooks();
        $this->shortcodes->hooks();
        $this->admin->hooks();

        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
    }

    public function enqueue_assets()
    {
        wp_enqueue_style(
            'df-subscriptions',
            DF_SUBSCRIPTIONS_URL . 'assets/css/df-subscriptions.css',
            array(),
            DF_SUBSCRIPTIONS_VERSION
        );
    }
}
