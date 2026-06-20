<?php

if (!defined('ABSPATH')) {
    exit;
}

require_once DF_SUBSCRIPTIONS_PATH . 'includes/class-df-subscriptions-access.php';
require_once DF_SUBSCRIPTIONS_PATH . 'includes/class-df-subscriptions-content.php';
require_once DF_SUBSCRIPTIONS_PATH . 'includes/class-df-subscriptions-shortcodes.php';
require_once DF_SUBSCRIPTIONS_PATH . 'includes/class-df-subscriptions-admin.php';
require_once DF_SUBSCRIPTIONS_PATH . 'includes/class-df-subscriptions-account.php';
require_once DF_SUBSCRIPTIONS_PATH . 'includes/class-df-subscriptions-lifecycle.php';

final class DF_Subscriptions
{
    const ROLE = 'df_subscriber';
    const MAGAZINE_POST_TYPE = 'df_magazine';
    const ARTICLE_POST_TYPE = 'df_article';
    const TOPIC_TAXONOMY = 'df_topic_category';
    const ISSUE_YEAR_TAXONOMY = 'df_issue_year';
    const ARTICLE_MAGAZINE_META = '_df_magazine_id';
    const MAGAZINE_ACCESS_META = '_df_magazine_access';
    const MAGAZINE_PDF_META = '_df_magazine_pdf_url';
    const MAGAZINE_PROTECTED_PDF_META = '_df_magazine_protected_pdf';
    const MAGAZINE_COVER_URL_META = '_df_magazine_cover_url';
    const ARTICLE_IMAGE_URL_META = '_df_article_image_url';
    const MAGAZINE_ISSUE_NUMBER_META = '_df_magazine_issue_number';
    const MAGAZINE_ISSUE_DATE_META = '_df_magazine_issue_date';
    const EXPIRES_META = 'df_subscription_expires_at';
    const STATUS_META = 'df_subscription_status';
    const HISTORY_META = 'df_subscription_history';
    const REMINDER_META = 'df_subscription_reminder_sent';
    const EXPIRED_NOTICE_META = 'df_subscription_expired_notice_sent';
    const ARTICLE_ACCESS_META = '_df_article_access';
    const ARTICLE_PREVIEW_WORDS_META = '_df_article_preview_words';
    const PAYMENT_URL_OPTION = 'df_subscription_payment_url';
    const LOGIN_URL_OPTION = 'df_subscription_login_url';
    const REQUIRE_MAGAZINE_OPTION = 'df_subscription_require_magazine';
    const DELETE_DATA_OPTION = 'df_subscription_delete_data';
    const EMAIL_NOTICES_OPTION = 'df_subscription_email_notices';
    const AUTHOR_NAME_OPTION = 'df_subscription_author_name';
    const PROTECTED_DIR = 'df-subscriptions-protected';

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
        self::instance()->content->register_taxonomies();
        self::instance()->lifecycle->schedule_events();
        flush_rewrite_rules();
    }

    public static function deactivate()
    {
        if (null !== self::$instance) {
            self::$instance->lifecycle->unschedule_events();
        } else {
            wp_clear_scheduled_hook(DF_Subscriptions_Lifecycle::CRON_HOOK);
        }

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
    public $account;
    public $lifecycle;

    private function __construct()
    {
        $this->access = new DF_Subscriptions_Access();
        $this->content = new DF_Subscriptions_Content($this->access);
        $this->shortcodes = new DF_Subscriptions_Shortcodes($this->access);
        $this->admin = new DF_Subscriptions_Admin($this->access);
        $this->account = new DF_Subscriptions_Account($this->access, $this->shortcodes);
        $this->lifecycle = new DF_Subscriptions_Lifecycle($this->access);

        $this->content->hooks();
        $this->shortcodes->hooks();
        $this->admin->hooks();
        $this->account->hooks();
        $this->lifecycle->hooks();

        add_action('init', array($this, 'load_textdomain'), 0);
        add_action('init', array(__CLASS__, 'add_role'), 1);
        add_action('init', array($this, 'register_blocks'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'), 99);

        // Subscribers manage everything from the front-end account page: hide the
        // WordPress toolbar for them and keep them out of the wp-admin dashboard.
        add_filter('show_admin_bar', array($this, 'restrict_admin_bar'));
        add_action('admin_init', array($this, 'block_admin_dashboard'));
    }

    /**
     * Hide the WordPress admin toolbar for users who cannot edit content
     * (subscribers and other read-only roles). Editors and admins keep it.
     */
    public function restrict_admin_bar($show)
    {
        if (is_user_logged_in() && !current_user_can('edit_posts')) {
            return false;
        }

        return $show;
    }

    /**
     * Redirect read-only users (subscribers) away from wp-admin. They have a
     * dedicated front-end account page, so there is no reason for them to reach
     * the dashboard. Front-end AJAX (admin-ajax.php) is left untouched.
     */
    public function block_admin_dashboard()
    {
        if (wp_doing_ajax()) {
            return;
        }

        if (!is_user_logged_in() || current_user_can('edit_posts')) {
            return;
        }

        $login = get_option(self::LOGIN_URL_OPTION, '');

        if ($login) {
            $target = preg_match('#^https?://#i', $login) ? $login : home_url('/' . ltrim($login, '/'));
        } else {
            $target = home_url('/');
        }

        wp_safe_redirect($target);
        exit;
    }

    public function load_textdomain()
    {
        load_plugin_textdomain(
            'der-flugschreiber-subscriptions',
            false,
            dirname(plugin_basename(DF_SUBSCRIPTIONS_FILE)) . '/languages'
        );
    }

    public function register_blocks()
    {
        wp_register_script(
            'df-subscriptions-blocks',
            DF_SUBSCRIPTIONS_URL . 'assets/js/df-blocks.js',
            array('wp-blocks', 'wp-element', 'wp-components', 'wp-block-editor', 'wp-i18n'),
            DF_SUBSCRIPTIONS_VERSION,
            true
        );

        register_block_type(
            'df-subscriptions/all-issues',
            array(
                'editor_script' => 'df-subscriptions-blocks',
                'attributes' => array(
                    'initial' => array('type' => 'number', 'default' => 12),
                    'step' => array('type' => 'number', 'default' => 4),
                    'title' => array('type' => 'string', 'default' => __('Alle Ausgaben', 'der-flugschreiber-subscriptions')),
                ),
                'render_callback' => array($this, 'render_issues_block'),
            )
        );
        register_block_type(
            'df-subscriptions/all-articles',
            array(
                'editor_script' => 'df-subscriptions-blocks',
                'attributes' => array(
                    'initial' => array('type' => 'number', 'default' => 5),
                    'step' => array('type' => 'number', 'default' => 5),
                    'magazine' => array('type' => 'number', 'default' => 0),
                    'title' => array('type' => 'string', 'default' => __('Alle Artikel', 'der-flugschreiber-subscriptions')),
                ),
                'render_callback' => array($this, 'render_articles_block'),
            )
        );
        register_block_type(
            'df-subscriptions/account',
            array(
                'editor_script' => 'df-subscriptions-blocks',
                'render_callback' => array($this, 'render_account_block'),
            )
        );
        register_block_type(
            'df-subscriptions/article',
            array(
                'editor_script' => 'df-subscriptions-blocks',
                'attributes' => array(
                    'article' => array('type' => 'number', 'default' => 0),
                    'showBack' => array('type' => 'boolean', 'default' => true),
                ),
                'render_callback' => array($this, 'render_article_block'),
            )
        );
    }

    public function render_issues_block($attributes)
    {
        return $this->shortcodes->all_issues($attributes);
    }

    public function render_articles_block($attributes)
    {
        return $this->shortcodes->all_articles($attributes);
    }

    public function render_account_block()
    {
        return $this->account->account();
    }

    public function render_article_block($attributes)
    {
        return $this->shortcodes->article_page(
            array(
                'article' => isset($attributes['article']) ? absint($attributes['article']) : 0,
                'show_back' => !isset($attributes['showBack']) || $attributes['showBack'] ? 'yes' : 'no',
            )
        );
    }

    public function enqueue_assets()
    {
        if (!$this->should_enqueue_assets()) {
            return;
        }

        wp_enqueue_style(
            'df-subscriptions',
            DF_SUBSCRIPTIONS_URL . 'assets/css/df-subscriptions.css',
            array(),
            DF_SUBSCRIPTIONS_VERSION
        );

        wp_enqueue_script(
            'df-subscriptions-issues',
            DF_SUBSCRIPTIONS_URL . 'assets/js/df-issues.js',
            array(),
            DF_SUBSCRIPTIONS_VERSION,
            true
        );

        wp_localize_script(
            'df-subscriptions-issues',
            'DFIssues',
            array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('df_issues_filter'),
                'errorMessage' => __('Die Inhalte konnten nicht geladen werden. Bitte versuchen Sie es erneut.', 'der-flugschreiber-subscriptions'),
            )
        );
    }

    private function should_enqueue_assets()
    {
        if (is_singular(array(self::MAGAZINE_POST_TYPE, self::ARTICLE_POST_TYPE))) {
            return true;
        }

        // Single standard blog posts are rendered through the plugin's article
        // design (see DF_Subscriptions_Content::single_post_template).
        if (is_singular('post')) {
            return true;
        }

        // The magazine/article post-type archives and the plugin taxonomies are
        // rendered through Elementor Theme Builder templates that output our
        // markup but never run has_shortcode() on a singular post – enqueue here
        // so those listing pages are not left unstyled.
        if (is_post_type_archive(array(self::MAGAZINE_POST_TYPE, self::ARTICLE_POST_TYPE))) {
            return true;
        }

        if (is_tax(array(self::TOPIC_TAXONOMY, self::ISSUE_YEAR_TAXONOMY))) {
            return true;
        }

        // The 404 page is not a singular post, so the shortcode scan below would
        // miss it. Style it whenever a 404 is served (e.g. an Elementor 404
        // template or theme 404.php containing the [df_404] shortcode).
        if (is_404()) {
            return true;
        }

        if (!is_singular()) {
            return false;
        }

        $post = get_post();

        if (!$post) {
            return false;
        }

        foreach (array('df_logout_link', 'df_account', 'df_all_issues', 'df_all_articles', 'df_article_page', 'df_homepage', 'df_magazine_archive', 'df_article_archive', 'df_magazine_page', 'df_all_posts', 'df_post_page', 'df_blog_archive', 'df_404') as $shortcode) {
            if (has_shortcode($post->post_content, $shortcode)) {
                return true;
            }
        }

        foreach (array('df-subscriptions/all-issues', 'df-subscriptions/all-articles', 'df-subscriptions/account', 'df-subscriptions/article') as $block_name) {
            if (has_block($block_name, $post)) {
                return true;
            }
        }

        return false;
    }
}
