<?php
defined( 'ABSPATH' ) || exit;

class Propstack_RE_Plugin {

    private static ?self $instance = null;

    public static function get_instance(): self {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->load_dependencies();
        $this->define_hooks();
    }

    private function load_dependencies(): void {
        require_once PROPSTACK_RE_PATH . 'includes/helpers.php';
        require_once PROPSTACK_RE_PATH . 'includes/class-logger.php';
        require_once PROPSTACK_RE_PATH . 'includes/class-cpt.php';
        require_once PROPSTACK_RE_PATH . 'includes/class-api-client.php';
        require_once PROPSTACK_RE_PATH . 'includes/class-field-mapper.php';
        require_once PROPSTACK_RE_PATH . 'includes/class-image-service.php';
        require_once PROPSTACK_RE_PATH . 'includes/class-sync-service.php';
        require_once PROPSTACK_RE_PATH . 'includes/class-lead-service.php';
        require_once PROPSTACK_RE_PATH . 'includes/class-contact-form.php';
        require_once PROPSTACK_RE_PATH . 'includes/class-template-loader.php';
        require_once PROPSTACK_RE_PATH . 'includes/class-shortcodes.php';
        require_once PROPSTACK_RE_PATH . 'includes/class-seo.php';
        require_once PROPSTACK_RE_PATH . 'includes/class-webhook-controller.php';
        require_once PROPSTACK_RE_PATH . 'includes/class-admin-settings.php';
        require_once PROPSTACK_RE_PATH . 'includes/class-admin-docs.php';
    }

    private function define_hooks(): void {
        // Custom Cron-Intervall registrieren
        add_filter( 'cron_schedules', [ $this, 'add_cron_schedules' ] );

        // CPT + Taxonomien
        $cpt = new Propstack_RE_CPT();
        add_action( 'init', [ $cpt, 'register' ] );

        // Admin
        if ( is_admin() ) {
            $settings = new Propstack_RE_Admin_Settings();
            $settings->init();

            $docs = new Propstack_RE_Admin_Docs();
            $docs->init();
        }

        // Sync Cron
        $sync = new Propstack_RE_Sync_Service();
        add_action( 'propstack_re_cron_sync', [ $sync, 'run_cron_sync' ] );
        add_action( 'init', [ $sync, 'schedule_cron' ] );

        // Shortcodes
        $shortcodes = new Propstack_RE_Shortcodes();
        $shortcodes->register();

        // SEO
        $seo = new Propstack_RE_SEO();
        $seo->init();

        // Webhook REST
        $webhook = new Propstack_RE_Webhook_Controller();
        add_action( 'rest_api_init', [ $webhook, 'register_routes' ] );

        // Contact Form AJAX
        $form = new Propstack_RE_Contact_Form();
        add_action( 'wp_ajax_propstack_re_submit',        [ $form, 'handle_submission' ] );
        add_action( 'wp_ajax_nopriv_propstack_re_submit', [ $form, 'handle_submission' ] );

        // Assets
        add_action( 'wp_enqueue_scripts',    [ $this, 'enqueue_frontend_assets' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );

        // Admin-Notices bei API-Fehler
        add_action( 'admin_notices', [ $this, 'show_api_notice' ] );
    }

    public function enqueue_frontend_assets(): void {
        wp_enqueue_style(
            'propstack-re-frontend',
            PROPSTACK_RE_URL . 'assets/css/frontend.css',
            [],
            PROPSTACK_RE_VERSION
        );
        wp_enqueue_script(
            'propstack-re-frontend',
            PROPSTACK_RE_URL . 'assets/js/frontend.js',
            [ 'jquery' ],
            PROPSTACK_RE_VERSION,
            true
        );
        wp_localize_script( 'propstack-re-frontend', 'propstackRE', [
            'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
            'nonce'     => wp_create_nonce( 'propstack_re_nonce' ),
            'listingUrl'=> get_post_type_archive_link( 'propstack_property' ),
        ] );

        // Custom CSS aus Settings
        $custom_css = get_option( 'propstack_re_custom_css', '' );
        if ( $custom_css ) {
            wp_add_inline_style( 'propstack-re-frontend', wp_strip_all_tags( $custom_css ) );
        }
    }

    public function enqueue_admin_assets( string $hook ): void {
        if ( ! str_contains( $hook, 'propstack' ) ) {
            return;
        }
        wp_enqueue_style(
            'propstack-re-admin',
            PROPSTACK_RE_URL . 'assets/css/admin.css',
            [],
            PROPSTACK_RE_VERSION
        );
        wp_enqueue_script(
            'propstack-re-admin',
            PROPSTACK_RE_URL . 'assets/js/admin.js',
            [ 'jquery' ],
            PROPSTACK_RE_VERSION,
            true
        );
        wp_localize_script( 'propstack-re-admin', 'propstackREAdmin', [
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'propstack_re_admin_nonce' ),
            'i18n'    => [
                'syncing'  => __( 'Synchronisierung läuft…', 'propstack-re' ),
                'syncDone' => __( 'Synchronisierung abgeschlossen.', 'propstack-re' ),
                'syncFail' => __( 'Fehler bei der Synchronisierung.', 'propstack-re' ),
            ],
        ] );
    }

    public function show_api_notice(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        $error = get_transient( 'propstack_re_api_error' );
        if ( $error ) {
            printf(
                '<div class="notice notice-error"><p><strong>Propstack:</strong> %s</p></div>',
                esc_html( $error )
            );
        }
    }

    public function add_cron_schedules( array $schedules ): array {
        $schedules['fifteen_min'] = [
            'interval' => 900,
            'display'  => __( 'Alle 15 Minuten', 'propstack-re' ),
        ];
        return $schedules;
    }

    public static function activate(): void {
        // Activation-Hook läuft vor plugins_loaded — Abhängigkeiten manuell laden
        require_once PROPSTACK_RE_PATH . 'includes/helpers.php';
        require_once PROPSTACK_RE_PATH . 'includes/class-logger.php';
        require_once PROPSTACK_RE_PATH . 'includes/class-cpt.php';

        Propstack_RE_Logger::create_table();

        // Flush rewrite rules für CPT
        $cpt = new Propstack_RE_CPT();
        $cpt->register();
        flush_rewrite_rules();

        // Standard-Optionen setzen
        add_option( 'propstack_re_api_base_url',   'https://api.propstack.de/v1' );
        add_option( 'propstack_re_sync_interval',  'hourly' );
        add_option( 'propstack_re_cpt_slug',       'immobilien' );
        add_option( 'propstack_re_image_mode',     'import' );
        add_option( 'propstack_re_public_statuses', [ 1, 2 ] ); // Vermarktung + Reserviert
        add_option( 'propstack_re_inactive_action', 'draft' );
        add_option( 'propstack_re_seo_enabled',    '1' );
        add_option( 'propstack_re_form_enabled',   '1' );
        add_option( 'propstack_re_privacy_text',   'Ich stimme der Verarbeitung meiner Daten gemäß der Datenschutzerklärung zu.' );
        add_option( 'propstack_re_thankyou_page',  '' );
        add_option( 'propstack_re_max_images',     '20' );
        add_option( 'propstack_re_listing_layout', 'grid' );
        add_option( 'propstack_re_cards_per_row',  '3' );
    }

    public static function deactivate(): void {
        wp_clear_scheduled_hook( 'propstack_re_cron_sync' );
    }
}
