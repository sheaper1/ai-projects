<?php
defined( 'ABSPATH' ) || exit;

class Propstack_RE_Admin_Settings {

    private array $pages = [
        'propstack-re'          => 'Dashboard',
        'propstack-re-api'      => 'API Verbindung',
        'propstack-re-sync'     => 'Sync',
        'propstack-re-display'  => 'Darstellung',
        'propstack-re-form'     => 'Kontaktformular',
        'propstack-re-seo'      => 'SEO',
        'propstack-re-logs'     => 'Logs',
    ];

    public function init(): void {
        add_action( 'admin_menu',    [ $this, 'register_menu'    ] );
        add_action( 'admin_init',    [ $this, 'register_settings'] );
        add_action( 'wp_ajax_propstack_re_sync_now',   [ $this, 'ajax_sync_now'       ] );
        add_action( 'wp_ajax_propstack_re_test_api',   [ $this, 'ajax_test_api'       ] );
        add_action( 'wp_ajax_propstack_re_clear_logs', [ $this, 'ajax_clear_logs'     ] );
    }

    public function register_menu(): void {
        add_menu_page(
            __( 'Propstack', 'propstack-re' ),
            __( 'Propstack', 'propstack-re' ),
            'manage_options',
            'propstack-re',
            [ $this, 'page_dashboard' ],
            'dashicons-admin-home',
            25
        );

        $subpages = [
            'propstack-re-api'      => __( 'API Verbindung', 'propstack-re' ),
            'propstack-re-sync'     => __( 'Sync',           'propstack-re' ),
            'propstack-re-display'  => __( 'Darstellung',    'propstack-re' ),
            'propstack-re-form'     => __( 'Kontaktformular','propstack-re' ),
            'propstack-re-seo'      => __( 'SEO',            'propstack-re' ),
            'propstack-re-logs'     => __( 'Logs',           'propstack-re' ),
        ];

        foreach ( $subpages as $slug => $label ) {
            add_submenu_page(
                'propstack-re',
                $label,
                $label,
                'manage_options',
                $slug,
                [ $this, 'page_' . str_replace( '-', '_', str_replace( 'propstack-re-', '', $slug ) ) ]
            );
        }
    }

    public function register_settings(): void {
        // API
        register_setting( 'propstack_re_api', 'propstack_re_api_key',      [ 'sanitize_callback' => 'sanitize_text_field' ] );
        register_setting( 'propstack_re_api', 'propstack_re_api_base_url', [ 'sanitize_callback' => 'esc_url_raw'         ] );
        register_setting( 'propstack_re_api', 'propstack_re_debug_mode',   [ 'sanitize_callback' => 'absint'              ] );

        // Sync
        register_setting( 'propstack_re_sync', 'propstack_re_sync_interval',   [ 'sanitize_callback' => 'sanitize_key'     ] );
        register_setting( 'propstack_re_sync', 'propstack_re_public_statuses', [ 'sanitize_callback' => [ $this, 'sanitize_int_array' ] ] );
        register_setting( 'propstack_re_sync', 'propstack_re_inactive_action', [ 'sanitize_callback' => 'sanitize_key'     ] );
        register_setting( 'propstack_re_sync', 'propstack_re_deleted_action',  [ 'sanitize_callback' => 'sanitize_key'     ] );
        register_setting( 'propstack_re_sync', 'propstack_re_webhook_enabled', [ 'sanitize_callback' => 'absint'           ] );
        register_setting( 'propstack_re_sync', 'propstack_re_webhook_secret',  [ 'sanitize_callback' => 'sanitize_text_field' ] );
        register_setting( 'propstack_re_sync', 'propstack_re_image_mode',      [ 'sanitize_callback' => 'sanitize_key'     ] );
        register_setting( 'propstack_re_sync', 'propstack_re_max_images',      [ 'sanitize_callback' => 'absint'           ] );

        // Darstellung
        register_setting( 'propstack_re_display', 'propstack_re_cpt_slug',       [ 'sanitize_callback' => 'sanitize_title' ] );
        register_setting( 'propstack_re_display', 'propstack_re_listing_layout', [ 'sanitize_callback' => 'sanitize_key'  ] );
        register_setting( 'propstack_re_display', 'propstack_re_cards_per_row',  [ 'sanitize_callback' => 'absint'        ] );
        register_setting( 'propstack_re_display', 'propstack_re_custom_css',     [ 'sanitize_callback' => 'wp_strip_all_tags' ] );

        // Formular
        register_setting( 'propstack_re_form', 'propstack_re_form_enabled',         [ 'sanitize_callback' => 'absint'           ] );
        register_setting( 'propstack_re_form', 'propstack_re_thankyou_page',        [ 'sanitize_callback' => 'absint'           ] );
        register_setting( 'propstack_re_form', 'propstack_re_admin_notification',   [ 'sanitize_callback' => 'absint'           ] );
        register_setting( 'propstack_re_form', 'propstack_re_notification_email',   [ 'sanitize_callback' => 'sanitize_email'   ] );
        register_setting( 'propstack_re_form', 'propstack_re_privacy_text',         [ 'sanitize_callback' => 'wp_kses_post'     ] );
        register_setting( 'propstack_re_form', 'propstack_re_privacy_page',         [ 'sanitize_callback' => 'absint'           ] );
        register_setting( 'propstack_re_form', 'propstack_re_create_contact',       [ 'sanitize_callback' => 'absint'           ] );
        register_setting( 'propstack_re_form', 'propstack_re_contact_dedup',        [ 'sanitize_callback' => 'absint'           ] );

        // SEO
        register_setting( 'propstack_re_seo', 'propstack_re_seo_enabled',          [ 'sanitize_callback' => 'absint'           ] );
        register_setting( 'propstack_re_seo', 'propstack_re_meta_title_template',  [ 'sanitize_callback' => 'sanitize_text_field' ] );
        register_setting( 'propstack_re_seo', 'propstack_re_meta_desc_template',   [ 'sanitize_callback' => 'sanitize_text_field' ] );
        register_setting( 'propstack_re_seo', 'propstack_re_jsonld_enabled',       [ 'sanitize_callback' => 'absint'           ] );
    }

    // -------------------------------------------------------------------------
    // Seiten
    // -------------------------------------------------------------------------

    public function page_dashboard(): void {
        if ( ! current_user_can( 'manage_options' ) ) { return; }
        $sync    = new Propstack_RE_Sync_Service();
        $stats   = $sync->get_stats();
        $api_key = get_option( 'propstack_re_api_key', '' );
        ?>
        <div class="wrap propstack-re-admin">
            <h1><?php esc_html_e( 'Propstack Real Estate Sync', 'propstack-re' ); ?></h1>
            <div class="propstack-re-dashboard">
                <div class="propstack-re-card">
                    <h2><?php esc_html_e( 'Status', 'propstack-re' ); ?></h2>
                    <table class="propstack-re-stats">
                        <tr><th><?php esc_html_e( 'Aktive Objekte',   'propstack-re' ); ?></th><td><?php echo (int) $stats['total_published']; ?></td></tr>
                        <tr><th><?php esc_html_e( 'Entwürfe',         'propstack-re' ); ?></th><td><?php echo (int) $stats['total_draft']; ?></td></tr>
                        <tr><th><?php esc_html_e( 'Letzter Sync',     'propstack-re' ); ?></th><td><?php echo $stats['last_sync'] ? esc_html( $stats['last_sync'] ) : '—'; ?></td></tr>
                        <tr><th><?php esc_html_e( 'Nächster Sync',    'propstack-re' ); ?></th><td><?php echo $stats['next_sync'] ? esc_html( date_i18n( 'd.m.Y H:i', $stats['next_sync'] ) ) : '—'; ?></td></tr>
                        <tr><th><?php esc_html_e( 'API Key',          'propstack-re' ); ?></th><td><?php echo $api_key ? '<span class="propstack-re-ok">✓ Konfiguriert</span>' : '<span class="propstack-re-warn">✗ Nicht konfiguriert</span>'; ?></td></tr>
                    </table>
                </div>
                <?php if ( ! empty( $stats['last_results'] ) ) : ?>
                <div class="propstack-re-card">
                    <h2><?php esc_html_e( 'Letzter Sync', 'propstack-re' ); ?></h2>
                    <table class="propstack-re-stats">
                        <?php foreach ( $stats['last_results'] as $key => $value ) : ?>
                        <tr><th><?php echo esc_html( $key ); ?></th><td><?php echo (int) $value; ?></td></tr>
                        <?php endforeach; ?>
                    </table>
                </div>
                <?php endif; ?>
                <?php if ( $api_key ) : ?>
                <div class="propstack-re-card">
                    <h2><?php esc_html_e( 'Aktionen', 'propstack-re' ); ?></h2>
                    <p>
                        <button class="button button-primary" id="propstack-sync-now"><?php esc_html_e( 'Jetzt synchronisieren', 'propstack-re' ); ?></button>
                        <button class="button" id="propstack-sync-full"><?php esc_html_e( 'Kompletter Re-Sync', 'propstack-re' ); ?></button>
                    </p>
                    <div id="propstack-sync-result" style="margin-top:10px;"></div>
                </div>
                <?php else : ?>
                <div class="propstack-re-card notice notice-warning"><p><?php printf( __( '<strong>API-Key fehlt.</strong> Bitte unter <a href="%s">API Verbindung</a> konfigurieren.', 'propstack-re' ), esc_url( admin_url( 'admin.php?page=propstack-re-api' ) ) ); ?></p></div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    public function page_api(): void {
        if ( ! current_user_can( 'manage_options' ) ) { return; }
        ?>
        <div class="wrap propstack-re-admin">
            <h1><?php esc_html_e( 'API Verbindung', 'propstack-re' ); ?></h1>
            <form method="post" action="options.php">
                <?php settings_fields( 'propstack_re_api' ); ?>
                <table class="form-table">
                    <tr>
                        <th><?php esc_html_e( 'API Key', 'propstack-re' ); ?></th>
                        <td>
                            <input type="password" name="propstack_re_api_key" value="<?php echo esc_attr( get_option( 'propstack_re_api_key', '' ) ); ?>" class="regular-text" autocomplete="off">
                            <button type="button" class="button" id="propstack-test-api"><?php esc_html_e( 'Verbindung testen', 'propstack-re' ); ?></button>
                            <span id="propstack-api-test-result" style="margin-left:10px;"></span>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'API Base URL', 'propstack-re' ); ?></th>
                        <td>
                            <input type="url" name="propstack_re_api_base_url" value="<?php echo esc_attr( get_option( 'propstack_re_api_base_url', 'https://api.propstack.de/v1' ) ); ?>" class="regular-text">
                            <p class="description"><?php esc_html_e( 'Standard: https://api.propstack.de/v1', 'propstack-re' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Debug-Modus', 'propstack-re' ); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="propstack_re_debug_mode" value="1" <?php checked( get_option( 'propstack_re_debug_mode' ), 1 ); ?>>
                                <?php esc_html_e( 'Erweiterte Logs aktivieren', 'propstack-re' ); ?>
                            </label>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function page_sync(): void {
        if ( ! current_user_can( 'manage_options' ) ) { return; }
        $webhook_url = rest_url( 'propstack/v1/webhook' );
        ?>
        <div class="wrap propstack-re-admin">
            <h1><?php esc_html_e( 'Sync-Einstellungen', 'propstack-re' ); ?></h1>
            <form method="post" action="options.php">
                <?php settings_fields( 'propstack_re_sync' ); ?>
                <h2><?php esc_html_e( 'Automatischer Sync', 'propstack-re' ); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><?php esc_html_e( 'Sync-Intervall', 'propstack-re' ); ?></th>
                        <td>
                            <select name="propstack_re_sync_interval">
                                <?php foreach ( [ 'fifteen_min' => 'Alle 15 Min.', 'hourly' => 'Stündlich', 'twicedaily' => 'Zweimal täglich', 'daily' => 'Täglich' ] as $value => $label ) : ?>
                                <option value="<?php echo esc_attr( $value ); ?>" <?php selected( get_option( 'propstack_re_sync_interval', 'hourly' ), $value ); ?>><?php echo esc_html( $label ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Öffentliche Status-IDs', 'propstack-re' ); ?></th>
                        <td>
                            <input type="text" name="propstack_re_public_statuses" value="<?php echo esc_attr( implode( ',', (array) get_option( 'propstack_re_public_statuses', [ 1, 2 ] ) ) ); ?>" class="regular-text">
                            <p class="description"><?php esc_html_e( 'Komma-getrennte Status-IDs die veröffentlicht werden (z.B. 1,2)', 'propstack-re' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Inaktive Objekte', 'propstack-re' ); ?></th>
                        <td>
                            <select name="propstack_re_inactive_action">
                                <option value="draft"   <?php selected( get_option( 'propstack_re_inactive_action' ), 'draft'   ); ?>><?php esc_html_e( 'Entwurf setzen',  'propstack-re' ); ?></option>
                                <option value="trash"   <?php selected( get_option( 'propstack_re_inactive_action' ), 'trash'   ); ?>><?php esc_html_e( 'In Papierkorb',  'propstack-re' ); ?></option>
                                <option value="private" <?php selected( get_option( 'propstack_re_inactive_action' ), 'private' ); ?>><?php esc_html_e( 'Privat setzen',  'propstack-re' ); ?></option>
                            </select>
                        </td>
                    </tr>
                </table>
                <h2><?php esc_html_e( 'Bilder', 'propstack-re' ); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><?php esc_html_e( 'Bilder-Modus', 'propstack-re' ); ?></th>
                        <td>
                            <select name="propstack_re_image_mode">
                                <option value="import" <?php selected( get_option( 'propstack_re_image_mode', 'import' ), 'import' ); ?>><?php esc_html_e( 'In WP-Mediathek importieren', 'propstack-re' ); ?></option>
                                <option value="remote" <?php selected( get_option( 'propstack_re_image_mode' ), 'remote' ); ?>><?php esc_html_e( 'Remote (Propstack-URL)', 'propstack-re' ); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Max. Bilder pro Objekt', 'propstack-re' ); ?></th>
                        <td><input type="number" name="propstack_re_max_images" value="<?php echo esc_attr( get_option( 'propstack_re_max_images', '20' ) ); ?>" min="1" max="100" class="small-text"></td>
                    </tr>
                </table>
                <h2><?php esc_html_e( 'Webhook', 'propstack-re' ); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><?php esc_html_e( 'Webhook URL', 'propstack-re' ); ?></th>
                        <td>
                            <code><?php echo esc_html( $webhook_url ); ?></code>
                            <p class="description"><?php esc_html_e( 'Diese URL in Propstack als Webhook eintragen.', 'propstack-re' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Webhook Secret', 'propstack-re' ); ?></th>
                        <td><input type="text" name="propstack_re_webhook_secret" value="<?php echo esc_attr( get_option( 'propstack_re_webhook_secret', '' ) ); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Webhook aktiv', 'propstack-re' ); ?></th>
                        <td><label><input type="checkbox" name="propstack_re_webhook_enabled" value="1" <?php checked( get_option( 'propstack_re_webhook_enabled', '1' ), 1 ); ?>></label></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function page_display(): void {
        if ( ! current_user_can( 'manage_options' ) ) { return; }
        ?>
        <div class="wrap propstack-re-admin">
            <h1><?php esc_html_e( 'Darstellung', 'propstack-re' ); ?></h1>
            <form method="post" action="options.php">
                <?php settings_fields( 'propstack_re_display' ); ?>
                <table class="form-table">
                    <tr>
                        <th><?php esc_html_e( 'URL-Slug (Basis)', 'propstack-re' ); ?></th>
                        <td>
                            <input type="text" name="propstack_re_cpt_slug" value="<?php echo esc_attr( get_option( 'propstack_re_cpt_slug', 'immobilien' ) ); ?>" class="regular-text">
                            <p class="description"><?php esc_html_e( 'Nach Änderung: Einstellungen > Permalinks speichern!', 'propstack-re' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Listing Layout', 'propstack-re' ); ?></th>
                        <td>
                            <select name="propstack_re_listing_layout">
                                <option value="grid" <?php selected( get_option( 'propstack_re_listing_layout', 'grid' ), 'grid' ); ?>><?php esc_html_e( 'Grid', 'propstack-re' ); ?></option>
                                <option value="list" <?php selected( get_option( 'propstack_re_listing_layout' ), 'list' ); ?>><?php esc_html_e( 'Liste', 'propstack-re' ); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Karten pro Zeile (Desktop)', 'propstack-re' ); ?></th>
                        <td>
                            <select name="propstack_re_cards_per_row">
                                <?php foreach ( [ 1, 2, 3, 4 ] as $n ) : ?>
                                <option value="<?php echo $n; ?>" <?php selected( (int) get_option( 'propstack_re_cards_per_row', 3 ), $n ); ?>><?php echo $n; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Custom CSS', 'propstack-re' ); ?></th>
                        <td>
                            <textarea name="propstack_re_custom_css" rows="10" class="large-text code"><?php echo esc_textarea( get_option( 'propstack_re_custom_css', '' ) ); ?></textarea>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function page_form(): void {
        if ( ! current_user_can( 'manage_options' ) ) { return; }
        $pages = get_pages();
        ?>
        <div class="wrap propstack-re-admin">
            <h1><?php esc_html_e( 'Kontaktformular', 'propstack-re' ); ?></h1>
            <form method="post" action="options.php">
                <?php settings_fields( 'propstack_re_form' ); ?>
                <table class="form-table">
                    <tr>
                        <th><?php esc_html_e( 'Formular aktiv', 'propstack-re' ); ?></th>
                        <td><label><input type="checkbox" name="propstack_re_form_enabled" value="1" <?php checked( get_option( 'propstack_re_form_enabled', '1' ), 1 ); ?>></label></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Danke-Seite', 'propstack-re' ); ?></th>
                        <td>
                            <select name="propstack_re_thankyou_page">
                                <option value=""><?php esc_html_e( 'Standard (/danke/)', 'propstack-re' ); ?></option>
                                <?php foreach ( $pages as $page ) : ?>
                                <option value="<?php echo (int) $page->ID; ?>" <?php selected( (int) get_option( 'propstack_re_thankyou_page' ), $page->ID ); ?>><?php echo esc_html( $page->post_title ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Admin-Benachrichtigung', 'propstack-re' ); ?></th>
                        <td><label><input type="checkbox" name="propstack_re_admin_notification" value="1" <?php checked( get_option( 'propstack_re_admin_notification', '1' ), 1 ); ?>></label></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Empfänger E-Mail', 'propstack-re' ); ?></th>
                        <td><input type="email" name="propstack_re_notification_email" value="<?php echo esc_attr( get_option( 'propstack_re_notification_email', get_option( 'admin_email' ) ) ); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Kontakt in Propstack erstellen', 'propstack-re' ); ?></th>
                        <td><label><input type="checkbox" name="propstack_re_create_contact" value="1" <?php checked( get_option( 'propstack_re_create_contact', '1' ), 1 ); ?>></label></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Kontakt-Deduplizierung via E-Mail', 'propstack-re' ); ?></th>
                        <td><label><input type="checkbox" name="propstack_re_contact_dedup" value="1" <?php checked( get_option( 'propstack_re_contact_dedup', '1' ), 1 ); ?>></label></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Datenschutztext', 'propstack-re' ); ?></th>
                        <td>
                            <?php wp_editor( get_option( 'propstack_re_privacy_text', 'Ich stimme der Verarbeitung meiner Daten gemäß der Datenschutzerklärung zu.' ), 'propstack_re_privacy_text', [ 'textarea_rows' => 3, 'teeny' => true ] ); ?>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Datenschutzseite', 'propstack-re' ); ?></th>
                        <td>
                            <select name="propstack_re_privacy_page">
                                <option value=""><?php esc_html_e( '— keine —', 'propstack-re' ); ?></option>
                                <?php foreach ( $pages as $page ) : ?>
                                <option value="<?php echo (int) $page->ID; ?>" <?php selected( (int) get_option( 'propstack_re_privacy_page' ), $page->ID ); ?>><?php echo esc_html( $page->post_title ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function page_seo(): void {
        if ( ! current_user_can( 'manage_options' ) ) { return; }
        ?>
        <div class="wrap propstack-re-admin">
            <h1><?php esc_html_e( 'SEO', 'propstack-re' ); ?></h1>
            <?php if ( defined( 'WPSEO_VERSION' ) ) : ?>
            <div class="notice notice-info"><p><?php esc_html_e( 'Yoast SEO erkannt — Plugin befüllt Yoast-Felder automatisch.', 'propstack-re' ); ?></p></div>
            <?php elseif ( defined( 'RANK_MATH_VERSION' ) ) : ?>
            <div class="notice notice-info"><p><?php esc_html_e( 'RankMath erkannt — Plugin befüllt RankMath-Felder automatisch.', 'propstack-re' ); ?></p></div>
            <?php endif; ?>
            <form method="post" action="options.php">
                <?php settings_fields( 'propstack_re_seo' ); ?>
                <table class="form-table">
                    <tr>
                        <th><?php esc_html_e( 'SEO aktiv', 'propstack-re' ); ?></th>
                        <td><label><input type="checkbox" name="propstack_re_seo_enabled" value="1" <?php checked( get_option( 'propstack_re_seo_enabled', '1' ), 1 ); ?>></label></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Meta Title Template', 'propstack-re' ); ?></th>
                        <td>
                            <input type="text" name="propstack_re_meta_title_template" value="<?php echo esc_attr( get_option( 'propstack_re_meta_title_template', '{title} in {city} | {site_name}' ) ); ?>" class="large-text">
                            <p class="description"><?php esc_html_e( 'Variablen: {title} {city} {region} {type} {price} {rooms} {area} {site_name}', 'propstack-re' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Meta Description Template', 'propstack-re' ); ?></th>
                        <td>
                            <input type="text" name="propstack_re_meta_desc_template" value="<?php echo esc_attr( get_option( 'propstack_re_meta_desc_template', '{short_description}' ) ); ?>" class="large-text">
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'JSON-LD (Schema.org)', 'propstack-re' ); ?></th>
                        <td><label><input type="checkbox" name="propstack_re_jsonld_enabled" value="1" <?php checked( get_option( 'propstack_re_jsonld_enabled', '1' ), 1 ); ?>></label></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function page_logs(): void {
        if ( ! current_user_can( 'manage_options' ) ) { return; }
        $level   = sanitize_key( $_GET['level'] ?? '' );
        $context = sanitize_key( $_GET['context'] ?? '' );
        $paged   = max( 1, absint( $_GET['paged'] ?? 1 ) );
        $limit   = 50;
        $offset  = ( $paged - 1 ) * $limit;

        $logs    = Propstack_RE_Logger::get_logs( compact( 'level', 'context', 'limit', 'offset' ) );
        $total   = Propstack_RE_Logger::count_logs( $level, $context );
        $pages   = ceil( $total / $limit );
        ?>
        <div class="wrap propstack-re-admin">
            <h1><?php esc_html_e( 'Logs', 'propstack-re' ); ?></h1>
            <p>
                <button class="button" id="propstack-clear-logs"><?php esc_html_e( 'Alle Logs löschen', 'propstack-re' ); ?></button>
                <a href="<?php echo esc_url( add_query_arg( [ 'level' => 'error', 'paged' => 1 ] ) ); ?>" class="button"><?php esc_html_e( 'Nur Fehler', 'propstack-re' ); ?></a>
                <?php if ( $level || $context ) : ?>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=propstack-re-logs' ) ); ?>" class="button"><?php esc_html_e( 'Filter zurücksetzen', 'propstack-re' ); ?></a>
                <?php endif; ?>
            </p>
            <table class="widefat striped">
                <thead><tr><th><?php esc_html_e( 'Zeit', 'propstack-re' ); ?></th><th><?php esc_html_e( 'Level', 'propstack-re' ); ?></th><th><?php esc_html_e( 'Kontext', 'propstack-re' ); ?></th><th><?php esc_html_e( 'Nachricht', 'propstack-re' ); ?></th></tr></thead>
                <tbody>
                <?php if ( empty( $logs ) ) : ?>
                    <tr><td colspan="4"><?php esc_html_e( 'Keine Einträge.', 'propstack-re' ); ?></td></tr>
                <?php else : ?>
                    <?php foreach ( $logs as $log ) : ?>
                    <tr class="propstack-re-log-<?php echo esc_attr( $log->level ); ?>">
                        <td><?php echo esc_html( $log->created_at ); ?></td>
                        <td><code><?php echo esc_html( $log->level ); ?></code></td>
                        <td><?php echo esc_html( $log->context ); ?></td>
                        <td><?php echo esc_html( $log->message ); ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
            <?php if ( $pages > 1 ) : ?>
            <div class="tablenav"><div class="tablenav-pages">
                <?php echo paginate_links( [ 'base' => add_query_arg( 'paged', '%#%' ), 'format' => '', 'current' => $paged, 'total' => $pages ] ); ?>
            </div></div>
            <?php endif; ?>
        </div>
        <?php
    }

    // -------------------------------------------------------------------------
    // AJAX
    // -------------------------------------------------------------------------

    public function ajax_sync_now(): void {
        check_ajax_referer( 'propstack_re_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Keine Berechtigung.' );
        }
        $force  = ! empty( $_POST['force'] );
        $sync   = new Propstack_RE_Sync_Service();
        $result = $sync->sync_all( $force );
        wp_send_json_success( $result );
    }

    public function ajax_test_api(): void {
        check_ajax_referer( 'propstack_re_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Keine Berechtigung.' );
        }
        $api    = new Propstack_RE_API_Client();
        $result = $api->test_connection();
        if ( $result['success'] ) {
            wp_send_json_success( $result );
        } else {
            wp_send_json_error( $result );
        }
    }

    public function ajax_clear_logs(): void {
        check_ajax_referer( 'propstack_re_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Keine Berechtigung.' );
        }
        Propstack_RE_Logger::clear_logs();
        wp_send_json_success( [ 'message' => __( 'Logs gelöscht.', 'propstack-re' ) ] );
    }

    public function sanitize_int_array( mixed $value ): array {
        if ( is_string( $value ) ) {
            $value = explode( ',', $value );
        }
        if ( ! is_array( $value ) ) {
            return [ 1, 2 ];
        }
        return array_map( 'intval', array_filter( $value ) );
    }
}
