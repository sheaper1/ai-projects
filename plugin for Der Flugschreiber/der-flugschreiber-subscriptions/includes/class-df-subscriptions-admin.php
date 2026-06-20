<?php

if (!defined('ABSPATH')) {
    exit;
}

class DF_Subscriptions_Admin
{
    private $access;
    private $messages = array();

    public function __construct(DF_Subscriptions_Access $access)
    {
        $this->access = $access;
    }

    public function hooks()
    {
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'handle_actions'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_filter('manage_users_columns', array($this, 'user_columns'));
        add_filter('manage_users_custom_column', array($this, 'user_column_content'), 10, 3);
    }

    public function admin_menu()
    {
        add_menu_page(
            __('DF Subscriptions', 'der-flugschreiber-subscriptions'),
            __('DF Subscriptions', 'der-flugschreiber-subscriptions'),
            'manage_options',
            'df-subscriptions',
            array($this, 'render_page'),
            'dashicons-groups',
            56
        );
        add_submenu_page(
            'df-subscriptions',
            __('Anleitung', 'der-flugschreiber-subscriptions'),
            __('Anleitung', 'der-flugschreiber-subscriptions'),
            'manage_options',
            'df-subscriptions-guide',
            array($this, 'render_guide_page')
        );
        add_submenu_page(
            'df-subscriptions',
            __('Website-Texte', 'der-flugschreiber-subscriptions'),
            __('Inhalte', 'der-flugschreiber-subscriptions'),
            'manage_options',
            'df-subscriptions-texts',
            array($this, 'render_texts_page')
        );
    }

    public function handle_actions()
    {
        if (!is_admin() || !current_user_can('manage_options')) {
            return;
        }

        if (isset($_POST['df_create_subscriber'])) {
            $this->handle_create_subscriber();
        }

        if (isset($_POST['df_update_subscriber'])) {
            $this->handle_update_subscriber();
        }

        if (isset($_POST['df_save_settings'])) {
            $this->handle_save_settings();
        }

        if (isset($_POST['df_create_demo_content'])) {
            $this->handle_create_demo_content();
        }

        if (isset($_POST['df_remove_demo_content'])) {
            $this->handle_remove_demo_content();
        }

        if (isset($_POST['df_bulk_subscribers'])) {
            $this->handle_bulk_subscribers();
        }

        if (isset($_POST['df_import_subscribers'])) {
            $this->handle_import_subscribers();
        }

        if (isset($_POST['df_save_texts'])) {
            $this->handle_save_texts();
        }

        if (isset($_GET['df_export_subscribers'])) {
            $this->handle_export_subscribers();
        }
    }

    public function enqueue_admin_assets($hook_suffix)
    {
        if ('toplevel_page_df-subscriptions' === $hook_suffix) {
            wp_enqueue_script(
                'df-subscriptions-admin',
                DF_SUBSCRIPTIONS_URL . 'assets/js/df-admin.js',
                array(),
                DF_SUBSCRIPTIONS_VERSION,
                true
            );
            $this->localize_admin_script();
            return;
        }

        if (!in_array($hook_suffix, array('post.php', 'post-new.php'), true)) {
            return;
        }

        $screen = get_current_screen();

        if (!$screen || !in_array($screen->post_type, array(DF_Subscriptions::MAGAZINE_POST_TYPE, DF_Subscriptions::ARTICLE_POST_TYPE), true)) {
            return;
        }

        wp_enqueue_media();
        wp_enqueue_script(
            'df-subscriptions-admin',
            DF_SUBSCRIPTIONS_URL . 'assets/js/df-admin.js',
            array(),
            DF_SUBSCRIPTIONS_VERSION,
            true
        );
        $this->localize_admin_script();
    }

    private function localize_admin_script()
    {
        wp_localize_script(
            'df-subscriptions-admin',
            'DFSubscriptionsAdmin',
            array(
                'confirmDelete' => __('Delete the selected subscriber accounts permanently?', 'der-flugschreiber-subscriptions'),
            )
        );
    }

    public function render_page()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $this->load_messages();
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Der Flugschreiber Subscriptions', 'der-flugschreiber-subscriptions'); ?></h1>
            <?php $this->render_messages(); ?>

            <h2><?php esc_html_e('Create subscriber', 'der-flugschreiber-subscriptions'); ?></h2>
            <?php $this->render_create_form(); ?>

            <hr>

            <h2><?php esc_html_e('Subscribers', 'der-flugschreiber-subscriptions'); ?></h2>
            <?php $this->render_subscribers_table(); ?>

            <hr>

            <h2><?php esc_html_e('Settings', 'der-flugschreiber-subscriptions'); ?></h2>
            <?php $this->render_settings_form(); ?>

            <hr>

            <h2><?php esc_html_e('Import and export', 'der-flugschreiber-subscriptions'); ?></h2>
            <?php $this->render_import_export(); ?>

            <hr>

            <h2><?php esc_html_e('Demo content', 'der-flugschreiber-subscriptions'); ?></h2>
            <?php $this->render_demo_content_form(); ?>
        </div>
        <?php
    }

    public function render_guide_page()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $settings_url = admin_url('admin.php?page=df-subscriptions');
        $texts_url    = admin_url('admin.php?page=df-subscriptions-texts');
        $new_page     = admin_url('post-new.php?post_type=page');
        $new_magazine = admin_url('post-new.php?post_type=' . DF_Subscriptions::MAGAZINE_POST_TYPE);
        $new_article  = admin_url('post-new.php?post_type=' . DF_Subscriptions::ARTICLE_POST_TYPE);
        ?>
        <style>
        .df-guide { max-width: 960px; }
        .df-guide__intro { color: #50575e; font-size: 14px; margin: 6px 0 4px; }
        .df-guide__step {
            background: #fff;
            border: 1px solid #dcdcde;
            border-radius: 6px;
            box-shadow: 0 1px 2px rgba(0,0,0,.04);
            margin: 14px 0;
            padding: 18px 22px 18px 64px;
            position: relative;
        }
        .df-guide__num {
            align-items: center;
            background: #2271b1;
            border-radius: 50%;
            color: #fff;
            display: flex;
            font-size: 15px;
            font-weight: 700;
            height: 30px;
            justify-content: center;
            left: 18px;
            position: absolute;
            top: 18px;
            width: 30px;
        }
        .df-guide__step h2 { color: #1d2327; font-size: 16px; margin: 0 0 6px; }
        .df-guide__step p { color: #3c434a; font-size: 13.5px; line-height: 1.6; margin: 6px 0; }
        .df-guide__step ul { margin: 6px 0 6px 4px; }
        .df-guide__step li { color: #3c434a; font-size: 13.5px; line-height: 1.7; list-style: disc; margin-left: 18px; }
        .df-guide__step a { font-weight: 600; }
        .df-guide__note {
            background: #fcf9e8;
            border-left: 4px solid #dba617;
            border-radius: 0 4px 4px 0;
            color: #3c434a;
            font-size: 13.5px;
            margin: 18px 0;
            padding: 12px 16px;
        }
        </style>

        <div class="wrap df-guide">
            <h1><?php esc_html_e('Anleitung – in 5 Schritten startklar', 'der-flugschreiber-subscriptions'); ?></h1>
            <p class="df-guide__intro"><?php esc_html_e('Arbeite diese Schritte einmal von oben nach unten ab. Du brauchst keinen Code – nur die WordPress-Oberfläche.', 'der-flugschreiber-subscriptions'); ?></p>

            <div class="df-guide__step">
                <span class="df-guide__num">1</span>
                <h2><?php esc_html_e('Grundeinstellungen eintragen', 'der-flugschreiber-subscriptions'); ?></h2>
                <p>
                    <?php
                    printf(
                        /* translators: %s: link to the settings screen. */
                        esc_html__('Öffne %s und trage zwei Adressen ein: die externe Bezahlseite (dorthin werden Interessenten zum Kaufen geschickt) und die Adresse deiner Konto-Seite aus Schritt 2.', 'der-flugschreiber-subscriptions'),
                        '<a href="' . esc_url($settings_url) . '">' . esc_html__('DF Subscriptions → Einstellungen', 'der-flugschreiber-subscriptions') . '</a>'
                    );
                    ?>
                </p>
            </div>

            <div class="df-guide__step">
                <span class="df-guide__num">2</span>
                <h2><?php esc_html_e('Konto-Seite anlegen', 'der-flugschreiber-subscriptions'); ?></h2>
                <p>
                    <?php
                    printf(
                        /* translators: %s: link to create a new WordPress page. */
                        esc_html__('Erstelle eine %s und füge mit dem Block-Plus den Block „DF Subscriber Account“ ein. Veröffentlichen – fertig. Auf dieser Seite melden sich Besucher an; angemeldete Abonnenten sehen dort ihr Konto.', 'der-flugschreiber-subscriptions'),
                        '<a href="' . esc_url($new_page) . '">' . esc_html__('neue Seite', 'der-flugschreiber-subscriptions') . '</a>'
                    );
                    ?>
                </p>
                <p><?php esc_html_e('Kopiere die Adresse dieser Seite und trage sie in Schritt 1 als Konto-/Login-Seite ein.', 'der-flugschreiber-subscriptions'); ?></p>
            </div>

            <div class="df-guide__step">
                <span class="df-guide__num">3</span>
                <h2><?php esc_html_e('Ausgaben, Artikel und Blog füllen', 'der-flugschreiber-subscriptions'); ?></h2>
                <ul>
                    <li><?php
                        printf(
                            /* translators: %s: link to create a new magazine issue. */
                            esc_html__('%s: Wähle den Typ (neue bezahlte Ausgabe oder altes Gratis-PDF), setze Cover, Ausgabennummer und Datum. Bei bezahlten Ausgaben lädst du das geschützte PDF hoch, bei Gratis-Ausgaben gibst du die PDF-Adresse an.', 'der-flugschreiber-subscriptions'),
                            '<a href="' . esc_url($new_magazine) . '"><strong>' . esc_html__('Ausgaben → Neu', 'der-flugschreiber-subscriptions') . '</strong></a>'
                        );
                    ?></li>
                    <li><?php
                        printf(
                            /* translators: %s: link to create a new article. */
                            esc_html__('%s: Schreibe den Artikel und wähle rechts die zugehörige Ausgabe. Optional: eigenes Bild und Länge der kostenlosen Vorschau.', 'der-flugschreiber-subscriptions'),
                            '<a href="' . esc_url($new_article) . '"><strong>' . esc_html__('Artikel → Neu', 'der-flugschreiber-subscriptions') . '</strong></a>'
                        );
                    ?></li>
                    <li><?php esc_html_e('Normale Blog-Beiträge schreibst du wie gewohnt unter „Beiträge“.', 'der-flugschreiber-subscriptions'); ?></li>
                </ul>
                <p><?php esc_html_e('Die Einzelseiten von Ausgaben, Artikeln und Blog-Beiträgen erscheinen automatisch im Magazin-Design – hier ist nichts einzurichten.', 'der-flugschreiber-subscriptions'); ?></p>
            </div>

            <div class="df-guide__step">
                <span class="df-guide__num">4</span>
                <h2><?php esc_html_e('Übersichts-Seiten erstellen', 'der-flugschreiber-subscriptions'); ?></h2>
                <p><?php esc_html_e('Lege je eine Seite an und füge den passenden Block ein – ganz ohne Code:', 'der-flugschreiber-subscriptions'); ?></p>
                <ul>
                    <li><?php esc_html_e('Block „DF All Issues“ → die Seite mit allen Ausgaben (Filter und Tabs sind enthalten).', 'der-flugschreiber-subscriptions'); ?></li>
                    <li><?php esc_html_e('Block „DF All Articles“ → die Seite mit allen Artikeln.', 'der-flugschreiber-subscriptions'); ?></li>
                </ul>
            </div>

            <div class="df-guide__step">
                <span class="df-guide__num">5</span>
                <h2><?php esc_html_e('Texte, Preise und Abonnenten', 'der-flugschreiber-subscriptions'); ?></h2>
                <ul>
                    <li><?php
                        printf(
                            /* translators: %s: link to the website texts screen. */
                            esc_html__('Alle sichtbaren Texte und den Abo-Preis änderst du unter %s – kein Code nötig.', 'der-flugschreiber-subscriptions'),
                            '<a href="' . esc_url($texts_url) . '"><strong>' . esc_html__('Inhalte', 'der-flugschreiber-subscriptions') . '</strong></a>'
                        );
                    ?></li>
                    <li><?php
                        printf(
                            /* translators: %s: link to the main plugin screen. */
                            esc_html__('Abonnenten legst du unter %s einzeln an (E-Mail, Passwort, Ablaufdatum) oder importierst sie als CSV.', 'der-flugschreiber-subscriptions'),
                            '<a href="' . esc_url($settings_url) . '"><strong>' . esc_html__('DF Subscriptions', 'der-flugschreiber-subscriptions') . '</strong></a>'
                        );
                    ?></li>
                    <li><?php esc_html_e('Tipp: Teste die Konto-Seite einmal abgemeldet und einmal mit einem Abonnenten-Konto.', 'der-flugschreiber-subscriptions'); ?></li>
                </ul>
            </div>

            <div class="df-guide__note">
                <strong><?php esc_html_e('Gut zu wissen', 'der-flugschreiber-subscriptions'); ?></strong>
                <ul style="margin:6px 0 0;">
                    <li style="list-style:disc;margin-left:18px;"><?php esc_html_e('Zahlungen laufen extern: Das Plugin verarbeitet kein Geld, es leitet nur zur Bezahlseite weiter.', 'der-flugschreiber-subscriptions'); ?></li>
                    <li style="list-style:disc;margin-left:18px;"><?php esc_html_e('Preise und alle Texte sind unter „Inhalte“ änderbar – ohne Code.', 'der-flugschreiber-subscriptions'); ?></li>
                    <li style="list-style:disc;margin-left:18px;"><?php esc_html_e('Bezahlte Ausgaben sind durch den geschützten PDF-Upload abgesichert; Gratis-Ausgaben öffnen direkt die PDF-Adresse.', 'der-flugschreiber-subscriptions'); ?></li>
                    <li style="list-style:disc;margin-left:18px;"><?php esc_html_e('Für automatische E-Mails (Willkommen, Ablauf-Erinnerung) muss WordPress-Cron laufen.', 'der-flugschreiber-subscriptions'); ?></li>
                </ul>
            </div>
        </div>
        <?php
    }

    public function user_columns($columns)
    {
        $columns['df_subscription'] = __('DF Subscription', 'der-flugschreiber-subscriptions');
        return $columns;
    }

    public function user_column_content($output, $column_name, $user_id)
    {
        if ('df_subscription' !== $column_name) {
            return $output;
        }

        $user = get_userdata($user_id);

        if (!$user || !in_array(DF_Subscriptions::ROLE, (array) $user->roles, true)) {
            return '&mdash;';
        }

        return esc_html($this->access->get_subscription_status_label($user_id));
    }

    private function render_create_form()
    {
        ?>
        <form method="post" action="">
            <?php wp_nonce_field('df_create_subscriber', 'df_create_subscriber_nonce'); ?>
            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row"><label for="df_subscriber_email"><?php esc_html_e('Email', 'der-flugschreiber-subscriptions'); ?></label></th>
                        <td><input name="df_subscriber_email" id="df_subscriber_email" type="email" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="df_subscriber_name"><?php esc_html_e('Name', 'der-flugschreiber-subscriptions'); ?></label></th>
                        <td><input name="df_subscriber_name" id="df_subscriber_name" type="text" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="df_subscriber_password"><?php esc_html_e('Password', 'der-flugschreiber-subscriptions'); ?></label></th>
                        <td>
                            <input name="df_subscriber_password" id="df_subscriber_password" type="text" class="regular-text" autocomplete="new-password" required>
                            <button type="button" class="button df-generate-password" data-target="#df_subscriber_password"><?php esc_html_e('Generate password', 'der-flugschreiber-subscriptions'); ?></button>
                            <p class="description"><?php esc_html_e('Click “Generate password” for a strong random password, then copy it and send it to the subscriber.', 'der-flugschreiber-subscriptions'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="df_subscription_expires_at"><?php esc_html_e('Expires at', 'der-flugschreiber-subscriptions'); ?></label></th>
                        <td><input name="df_subscription_expires_at" id="df_subscription_expires_at" type="date" required></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="df_subscription_status"><?php esc_html_e('Status', 'der-flugschreiber-subscriptions'); ?></label></th>
                        <td><?php $this->render_status_select('df_subscription_status', 'active', 'df_subscription_status'); ?></td>
                    </tr>
                </tbody>
            </table>
            <?php submit_button(__('Create subscriber', 'der-flugschreiber-subscriptions'), 'primary', 'df_create_subscriber'); ?>
        </form>
        <?php
    }

    private function render_subscribers_table()
    {
        $per_page = 50;
        $current_page = isset($_GET['df_subscribers_page']) ? max(1, absint($_GET['df_subscribers_page'])) : 1;
        $search = isset($_GET['df_subscriber_search']) ? sanitize_text_field(wp_unslash($_GET['df_subscriber_search'])) : '';
        $query_args = array(
            'role' => DF_Subscriptions::ROLE,
            'orderby' => 'registered',
            'order' => 'DESC',
            'number' => $per_page,
            'offset' => ($current_page - 1) * $per_page,
            'count_total' => true,
        );

        if ($search) {
            $query_args['search'] = '*' . $search . '*';
            $query_args['search_columns'] = array('user_login', 'user_email', 'display_name');
        }

        $query = new WP_User_Query($query_args);
        $subscribers = $query->get_results();
        $total_subscribers = (int) $query->get_total();
        $total_pages = max(1, (int) ceil($total_subscribers / $per_page));

        if ($current_page > $total_pages) {
            $current_page = $total_pages;
            $query_args['offset'] = ($current_page - 1) * $per_page;
            $query = new WP_User_Query($query_args);
            $subscribers = $query->get_results();
        }

        ?>
        <form method="get" action="" style="margin:12px 0;">
            <input type="hidden" name="page" value="df-subscriptions">
            <label class="screen-reader-text" for="df_subscriber_search"><?php esc_html_e('Search subscribers', 'der-flugschreiber-subscriptions'); ?></label>
            <input type="search" name="df_subscriber_search" id="df_subscriber_search" value="<?php echo esc_attr($search); ?>" placeholder="<?php esc_attr_e('Email, name, or username', 'der-flugschreiber-subscriptions'); ?>">
            <?php submit_button(__('Search subscribers', 'der-flugschreiber-subscriptions'), 'secondary', '', false); ?>
            <?php if ($search) : ?>
                <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=df-subscriptions')); ?>"><?php esc_html_e('Clear', 'der-flugschreiber-subscriptions'); ?></a>
            <?php endif; ?>
        </form>
        <?php

        if (!$subscribers) {
            echo '<p>' . esc_html($search ? __('No matching subscribers found.', 'der-flugschreiber-subscriptions') : __('No subscribers yet.', 'der-flugschreiber-subscriptions')) . '</p>';
            return;
        }
        ?>
        <form id="df-bulk-subscribers" method="post" action="">
            <?php wp_nonce_field('df_bulk_subscribers', 'df_bulk_subscribers_nonce'); ?>
            <select name="df_bulk_action" required>
                <option value=""><?php esc_html_e('Bulk actions', 'der-flugschreiber-subscriptions'); ?></option>
                <option value="active"><?php esc_html_e('Set active', 'der-flugschreiber-subscriptions'); ?></option>
                <option value="paused"><?php esc_html_e('Pause', 'der-flugschreiber-subscriptions'); ?></option>
                <option value="cancelled"><?php esc_html_e('Cancel', 'der-flugschreiber-subscriptions'); ?></option>
                <option value="extend_30"><?php esc_html_e('Extend by 30 days', 'der-flugschreiber-subscriptions'); ?></option>
                <option value="delete"><?php esc_html_e('Delete users', 'der-flugschreiber-subscriptions'); ?></option>
            </select>
            <?php submit_button(__('Apply', 'der-flugschreiber-subscriptions'), 'secondary', 'df_bulk_subscribers', false); ?>
        </form>
        <table class="widefat striped">
            <thead>
                <tr>
                    <td class="check-column"><input type="checkbox" data-df-select-all aria-label="<?php esc_attr_e('Select all subscribers', 'der-flugschreiber-subscriptions'); ?>"></td>
                    <th><?php esc_html_e('User', 'der-flugschreiber-subscriptions'); ?></th>
                    <th><?php esc_html_e('Email', 'der-flugschreiber-subscriptions'); ?></th>
                    <th><?php esc_html_e('Status', 'der-flugschreiber-subscriptions'); ?></th>
                    <th><?php esc_html_e('Change expiration date', 'der-flugschreiber-subscriptions'); ?></th>
                    <th><?php esc_html_e('Last change', 'der-flugschreiber-subscriptions'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($subscribers as $subscriber) : ?>
                    <tr>
                        <th scope="row" class="check-column">
                            <input form="df-bulk-subscribers" type="checkbox" name="df_user_ids[]" value="<?php echo esc_attr($subscriber->ID); ?>" aria-label="<?php echo esc_attr(sprintf(__('Select %s', 'der-flugschreiber-subscriptions'), $subscriber->display_name)); ?>">
                        </th>
                        <td>
                            <a href="<?php echo esc_url(get_edit_user_link($subscriber->ID)); ?>">
                                <?php echo esc_html($subscriber->display_name); ?>
                            </a>
                        </td>
                        <td><?php echo esc_html($subscriber->user_email); ?></td>
                        <td><?php echo esc_html($this->access->get_subscription_status_label($subscriber->ID)); ?></td>
                        <td>
                            <form method="post" action="" style="display:flex;gap:8px;align-items:center;">
                                <?php wp_nonce_field('df_update_subscriber_' . $subscriber->ID, 'df_update_subscriber_nonce'); ?>
                                <input type="hidden" name="df_user_id" value="<?php echo esc_attr($subscriber->ID); ?>">
                                <input type="date" name="df_subscription_expires_at" value="<?php echo esc_attr(get_user_meta($subscriber->ID, DF_Subscriptions::EXPIRES_META, true)); ?>" required>
                                <?php $this->render_status_select('df_subscription_status', $this->access->get_subscription_status($subscriber->ID)); ?>
                                <?php submit_button(__('Save', 'der-flugschreiber-subscriptions'), 'secondary small', 'df_update_subscriber', false); ?>
                            </form>
                        </td>
                        <td><?php $this->render_history($subscriber->ID); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php

        if ($total_pages > 1) {
            $pagination_placeholder = 999999999;
            $base_url = add_query_arg(
                array_filter(
                    array(
                        'page' => 'df-subscriptions',
                        'df_subscriber_search' => $search,
                        'df_subscribers_page' => $pagination_placeholder,
                    )
                ),
                admin_url('admin.php')
            );
            $base_url = str_replace((string) $pagination_placeholder, '%#%', $base_url);

            echo '<div class="tablenav"><div class="tablenav-pages">';
            echo wp_kses_post(
                paginate_links(
                    array(
                        'base' => $base_url,
                        'format' => '',
                        'current' => $current_page,
                        'total' => $total_pages,
                        'prev_text' => __('Previous', 'der-flugschreiber-subscriptions'),
                        'next_text' => __('Next', 'der-flugschreiber-subscriptions'),
                    )
                )
            );
            echo '</div></div>';
        }
    }

    private function render_settings_form()
    {
        $payment_url = get_option(DF_Subscriptions::PAYMENT_URL_OPTION, '');
        ?>
        <form method="post" action="">
            <?php wp_nonce_field('df_save_settings', 'df_save_settings_nonce'); ?>
            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row"><label for="df_subscription_payment_url"><?php esc_html_e('Payment page URL', 'der-flugschreiber-subscriptions'); ?></label></th>
                        <td>
                            <input name="df_subscription_payment_url" id="df_subscription_payment_url" type="url" class="regular-text" value="<?php echo esc_attr($payment_url); ?>">
                            <p class="description"><?php esc_html_e('Visitors without active subscription will see this link below the article preview.', 'der-flugschreiber-subscriptions'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="df_subscription_login_url"><?php esc_html_e('Subscriber login page URL', 'der-flugschreiber-subscriptions'); ?></label></th>
                        <td>
                            <input name="df_subscription_login_url" id="df_subscription_login_url" type="text" class="regular-text" value="<?php echo esc_attr(get_option(DF_Subscriptions::LOGIN_URL_OPTION, '')); ?>" placeholder="/mein-konto/">
                            <p class="description"><?php esc_html_e('Account page on this site. You can enter just a path like /mein-konto/ or the full URL. Guests use the same page to log in.', 'der-flugschreiber-subscriptions'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="df_subscription_author_name"><?php esc_html_e('Article author name', 'der-flugschreiber-subscriptions'); ?></label></th>
                        <td>
                            <input name="df_subscription_author_name" id="df_subscription_author_name" type="text" class="regular-text" value="<?php echo esc_attr(get_option(DF_Subscriptions::AUTHOR_NAME_OPTION, '')); ?>" placeholder="Raphael Rothmund">
                            <p class="description"><?php esc_html_e('Shown as the author ("Text") on every article and blog post. Leave empty to use the WordPress display name of each post\'s author.', 'der-flugschreiber-subscriptions'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Subscription emails', 'der-flugschreiber-subscriptions'); ?></th>
                        <td><label><input type="checkbox" name="df_subscription_email_notices" value="1" <?php checked(get_option(DF_Subscriptions::EMAIL_NOTICES_OPTION, '1'), '1'); ?>> <?php esc_html_e('Send welcome, expiration reminder, and expired subscription emails.', 'der-flugschreiber-subscriptions'); ?></label></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Article magazine requirement', 'der-flugschreiber-subscriptions'); ?></th>
                        <td><label><input type="checkbox" name="df_subscription_require_magazine" value="1" <?php checked(get_option(DF_Subscriptions::REQUIRE_MAGAZINE_OPTION, '0'), '1'); ?>> <?php esc_html_e('Require every magazine article to be linked to an issue.', 'der-flugschreiber-subscriptions'); ?></label></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Delete data on uninstall', 'der-flugschreiber-subscriptions'); ?></th>
                        <td><label><input type="checkbox" name="df_subscription_delete_data" value="1" <?php checked(get_option(DF_Subscriptions::DELETE_DATA_OPTION, '0'), '1'); ?>> <?php esc_html_e('Permanently delete plugin content, settings, and subscriber metadata when the plugin is deleted.', 'der-flugschreiber-subscriptions'); ?></label></td>
                    </tr>
                </tbody>
            </table>
            <?php submit_button(__('Save settings', 'der-flugschreiber-subscriptions'), 'primary', 'df_save_settings'); ?>
        </form>
        <?php
    }

    private function render_import_export()
    {
        $export_url = wp_nonce_url(
            add_query_arg(
                array(
                    'page' => 'df-subscriptions',
                    'df_export_subscribers' => '1',
                ),
                admin_url('admin.php')
            ),
            'df_export_subscribers',
            'df_export_nonce'
        );
        ?>
        <p><a class="button" href="<?php echo esc_url($export_url); ?>"><?php esc_html_e('Export subscribers as CSV', 'der-flugschreiber-subscriptions'); ?></a></p>
        <form method="post" action="" enctype="multipart/form-data">
            <?php wp_nonce_field('df_import_subscribers', 'df_import_subscribers_nonce'); ?>
            <input type="file" name="df_subscribers_csv" accept=".csv,text/csv" required>
            <?php submit_button(__('Import subscribers from CSV', 'der-flugschreiber-subscriptions'), 'secondary', 'df_import_subscribers', false); ?>
            <p class="description"><?php esc_html_e('Columns: email, name, expires_at, status. Existing subscribers are updated by email.', 'der-flugschreiber-subscriptions'); ?></p>
        </form>
        <?php
    }

    private function render_demo_content_form()
    {
        ?>
        <p><?php esc_html_e('Create sample magazines, topic categories, issue years, and linked articles for testing the subscription architecture.', 'der-flugschreiber-subscriptions'); ?></p>
        <form method="post" action="">
            <?php wp_nonce_field('df_create_demo_content', 'df_create_demo_content_nonce'); ?>
            <?php submit_button(__('Create demo content', 'der-flugschreiber-subscriptions'), 'secondary', 'df_create_demo_content'); ?>
        </form>
        <p><?php esc_html_e('Remove the demo magazines and articles created above. Only items created by this button are affected (real imported content is left untouched), and they are moved to the Trash so they can be restored.', 'der-flugschreiber-subscriptions'); ?></p>
        <form method="post" action="" onsubmit="return confirm('<?php echo esc_js(__('Move all demo content to the Trash?', 'der-flugschreiber-subscriptions')); ?>');">
            <?php wp_nonce_field('df_remove_demo_content', 'df_remove_demo_content_nonce'); ?>
            <?php submit_button(__('Remove demo content', 'der-flugschreiber-subscriptions'), 'delete', 'df_remove_demo_content'); ?>
        </form>
        <?php
    }

    private function handle_create_subscriber()
    {
        if (!isset($_POST['df_create_subscriber_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['df_create_subscriber_nonce'])), 'df_create_subscriber')) {
            $this->redirect_with_message('error', __('Security check failed.', 'der-flugschreiber-subscriptions'));
        }

        $email = isset($_POST['df_subscriber_email']) ? sanitize_email(wp_unslash($_POST['df_subscriber_email'])) : '';
        $name = isset($_POST['df_subscriber_name']) ? sanitize_text_field(wp_unslash($_POST['df_subscriber_name'])) : '';
        $password = isset($_POST['df_subscriber_password']) ? (string) wp_unslash($_POST['df_subscriber_password']) : '';
        $expires_at = isset($_POST['df_subscription_expires_at']) ? sanitize_text_field(wp_unslash($_POST['df_subscription_expires_at'])) : '';
        $status = $this->sanitize_status(isset($_POST['df_subscription_status']) ? wp_unslash($_POST['df_subscription_status']) : 'active');

        if (!$email || !is_email($email)) {
            $this->redirect_with_message('error', __('Please enter a valid email address.', 'der-flugschreiber-subscriptions'));
        }

        if (email_exists($email)) {
            $this->redirect_with_message('error', __('A user with this email already exists.', 'der-flugschreiber-subscriptions'));
        }

        if (!$password || !$this->is_valid_date($expires_at)) {
            $this->redirect_with_message('error', __('Please enter a password and a valid expiration date.', 'der-flugschreiber-subscriptions'));
        }

        $username = $this->generate_username_from_email($email);
        $user_id = wp_create_user($username, $password, $email);

        if (is_wp_error($user_id)) {
            $this->redirect_with_message('error', $user_id->get_error_message());
        }

        wp_update_user(
            array(
                'ID' => $user_id,
                'display_name' => $name ? $name : $email,
                'first_name' => $name,
            )
        );

        $user = new WP_User($user_id);
        $user->set_role(DF_Subscriptions::ROLE);
        update_user_meta($user_id, DF_Subscriptions::EXPIRES_META, $expires_at);
        update_user_meta($user_id, DF_Subscriptions::STATUS_META, $status);
        $this->add_history($user_id, 'created', $expires_at, $status);
        DF_Subscriptions::instance()->lifecycle->send_welcome_email($user_id);
        wp_new_user_notification($user_id, null, 'user');

        $this->redirect_with_message('success', __('Subscriber created.', 'der-flugschreiber-subscriptions'));
    }

    private function handle_update_subscriber()
    {
        $user_id = isset($_POST['df_user_id']) ? absint($_POST['df_user_id']) : 0;

        if (!$user_id || !isset($_POST['df_update_subscriber_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['df_update_subscriber_nonce'])), 'df_update_subscriber_' . $user_id)) {
            $this->redirect_with_message('error', __('Security check failed.', 'der-flugschreiber-subscriptions'));
        }

        $expires_at = isset($_POST['df_subscription_expires_at']) ? sanitize_text_field(wp_unslash($_POST['df_subscription_expires_at'])) : '';
        $status = $this->sanitize_status(isset($_POST['df_subscription_status']) ? wp_unslash($_POST['df_subscription_status']) : 'active');

        if (!$this->is_valid_date($expires_at)) {
            $this->redirect_with_message('error', __('Please enter a valid expiration date.', 'der-flugschreiber-subscriptions'));
        }

        $user = get_userdata($user_id);

        if (!$user || !in_array(DF_Subscriptions::ROLE, (array) $user->roles, true)) {
            $this->redirect_with_message('error', __('The selected user is not a subscriber.', 'der-flugschreiber-subscriptions'));
        }

        update_user_meta($user_id, DF_Subscriptions::EXPIRES_META, $expires_at);
        update_user_meta($user_id, DF_Subscriptions::STATUS_META, $status);
        delete_user_meta($user_id, DF_Subscriptions::REMINDER_META);
        delete_user_meta($user_id, DF_Subscriptions::EXPIRED_NOTICE_META);
        $this->add_history($user_id, 'updated', $expires_at, $status);
        $this->redirect_with_message('success', __('Subscription updated.', 'der-flugschreiber-subscriptions'));
    }

    private function handle_save_settings()
    {
        if (!isset($_POST['df_save_settings_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['df_save_settings_nonce'])), 'df_save_settings')) {
            $this->redirect_with_message('error', __('Security check failed.', 'der-flugschreiber-subscriptions'));
        }

        $payment_url = isset($_POST['df_subscription_payment_url']) ? esc_url_raw(wp_unslash($_POST['df_subscription_payment_url'])) : '';
        $login_url = $this->sanitize_internal_url(isset($_POST['df_subscription_login_url']) ? wp_unslash($_POST['df_subscription_login_url']) : '');
        $author_name = isset($_POST['df_subscription_author_name']) ? sanitize_text_field(wp_unslash($_POST['df_subscription_author_name'])) : '';
        update_option(DF_Subscriptions::PAYMENT_URL_OPTION, $payment_url);
        update_option(DF_Subscriptions::LOGIN_URL_OPTION, $login_url);
        update_option(DF_Subscriptions::AUTHOR_NAME_OPTION, $author_name);
        update_option(DF_Subscriptions::EMAIL_NOTICES_OPTION, !empty($_POST['df_subscription_email_notices']) ? '1' : '0');
        update_option(DF_Subscriptions::REQUIRE_MAGAZINE_OPTION, !empty($_POST['df_subscription_require_magazine']) ? '1' : '0');
        update_option(DF_Subscriptions::DELETE_DATA_OPTION, !empty($_POST['df_subscription_delete_data']) ? '1' : '0');

        $this->redirect_with_message('success', __('Settings saved.', 'der-flugschreiber-subscriptions'));
    }

    private function handle_create_demo_content()
    {
        if (!isset($_POST['df_create_demo_content_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['df_create_demo_content_nonce'])), 'df_create_demo_content')) {
            $this->redirect_with_message('error', __('Security check failed.', 'der-flugschreiber-subscriptions'));
        }

        $topics = array('Flugsicherheit', 'Flugzeuge & Technik', 'Menschen & Reisen', 'Ausbildung & Praxis');
        $topic_term_ids = array();

        foreach ($topics as $topic) {
            $term = term_exists($topic, DF_Subscriptions::TOPIC_TAXONOMY);

            if (!$term) {
                $term = wp_insert_term($topic, DF_Subscriptions::TOPIC_TAXONOMY);
            }

            if (!is_wp_error($term)) {
                $topic_term_ids[] = (int) (is_array($term) ? $term['term_id'] : $term);
            }
        }

        $demo_magazines = array(
            array(
                'title' => '# 10 | Juni 2026',
                'date' => '2026-06-16',
                'number' => '# 10',
                'access' => 'paid',
                'cover' => 'https://derflugschreiber.digirelation.dev/wp-content/uploads/2026/04/oliver-johnson-MxFM97qeS3I-unsplash-1.webp',
                'article_image' => 'https://derflugschreiber.digirelation.dev/wp-content/uploads/2026/04/3Rectangle-3-scaled.webp',
                'topics' => array('Ausbildung & Praxis', 'Flugzeuge & Technik'),
                'articles' => array(
                    'Bionic: Die Natur als Vorbild',
                    'Hohenemser Piloten',
                    'Skyguide: 24/7 Sicherheit über den Wolken',
                ),
            ),
            array(
                'title' => '# 09 | März 2026',
                'date' => '2026-03-16',
                'number' => '# 09',
                'access' => 'paid',
                'cover' => 'https://derflugschreiber.digirelation.dev/wp-content/uploads/2026/04/oliver-johnson-MxFM97qeS3I-unsplash-1-1.webp',
                'article_image' => 'https://derflugschreiber.digirelation.dev/wp-content/uploads/2026/03/for-example-post-scaled.webp',
                'topics' => array('Flugsicherheit', 'Menschen & Reisen'),
                'articles' => array(
                    'Polarlichter über Österreich',
                    '17 Jahre und Pilot in Hohenems',
                    '7 Blickwinkel aus dem Cockpit',
                ),
            ),
            array(
                'title' => '# 05 | September 2025',
                'date' => '2025-09-12',
                'number' => '# 05',
                'access' => 'free_pdf',
                'pdf' => 'https://example.com/der-flugschreiber-september-2025.pdf',
                'cover' => 'https://derflugschreiber.digirelation.dev/wp-content/uploads/2026/04/oliver-johnson-MxFM97qeS3I-unsplash-1-2.webp',
                'topics' => array('Flugsicherheit', 'Flugzeuge & Technik'),
                'articles' => array(),
            ),
            array(
                'title' => '# 01 | Juni 2025',
                'date' => '2025-06-10',
                'number' => '# 01',
                'access' => 'free_pdf',
                'pdf' => 'https://example.com/der-flugschreiber-juni-2025.pdf',
                'cover' => 'https://derflugschreiber.digirelation.dev/wp-content/uploads/2026/04/oliver-johnson-MxFM97qeS3I-unsplash-1.webp',
                'topics' => array('Menschen & Reisen'),
                'articles' => array(),
            ),
        );

        $created_magazines = 0;
        $created_articles = 0;

        foreach ($demo_magazines as $magazine_data) {
            $existing = $this->find_post_by_title($magazine_data['title'], DF_Subscriptions::MAGAZINE_POST_TYPE);

            if ($existing) {
                $magazine_id = $existing->ID;
            } else {
                $magazine_id = wp_insert_post(
                    array(
                        'post_type' => DF_Subscriptions::MAGAZINE_POST_TYPE,
                        'post_status' => 'publish',
                        'post_title' => $magazine_data['title'],
                        'post_excerpt' => __('Demo-Vorschau einer Ausgabe zum Testen von Filtern, Abonnements und Ausgaben-Karten.', 'der-flugschreiber-subscriptions'),
                        'post_content' => __('Dies ist ein Demo-Inhalt einer Ausgabe. Neue kostenpflichtige Ausgaben sind durch die Abo-Schranke geschützt; ältere PDF-Ausgaben können direkt zur PDF-Datei weiterleiten.', 'der-flugschreiber-subscriptions'),
                        'meta_input' => array(
                            '_df_demo_content' => '1',
                        ),
                    )
                );

                if (!is_wp_error($magazine_id)) {
                    $created_magazines++;
                }
            }

            if (is_wp_error($magazine_id) || !$magazine_id) {
                continue;
            }

            update_post_meta($magazine_id, DF_Subscriptions::MAGAZINE_ACCESS_META, $magazine_data['access']);
            update_post_meta($magazine_id, DF_Subscriptions::MAGAZINE_ISSUE_NUMBER_META, $magazine_data['number']);
            update_post_meta($magazine_id, DF_Subscriptions::MAGAZINE_ISSUE_DATE_META, $magazine_data['date']);
            update_post_meta($magazine_id, DF_Subscriptions::MAGAZINE_PDF_META, isset($magazine_data['pdf']) ? $magazine_data['pdf'] : '');
            update_post_meta($magazine_id, DF_Subscriptions::MAGAZINE_COVER_URL_META, $magazine_data['cover']);
            $this->maybe_set_featured_image_from_url($magazine_id, $magazine_data['cover']);
            wp_set_object_terms($magazine_id, substr($magazine_data['date'], 0, 4), DF_Subscriptions::ISSUE_YEAR_TAXONOMY, false);
            wp_set_object_terms($magazine_id, $magazine_data['topics'], DF_Subscriptions::TOPIC_TAXONOMY, false);

            foreach ($magazine_data['articles'] as $article_title) {
                $existing_article = $this->find_post_by_title($article_title, DF_Subscriptions::ARTICLE_POST_TYPE);

                if ($existing_article) {
                    continue;
                }

                $article_id = wp_insert_post(
                    array(
                        'post_type' => DF_Subscriptions::ARTICLE_POST_TYPE,
                        'post_status' => 'publish',
                        'post_title' => $article_title,
                        'post_excerpt' => __('Dieser öffentliche Auszug ist für Besucher ohne aktives Abonnement sichtbar.', 'der-flugschreiber-subscriptions'),
                        'post_content' => __('Dies ist der vollständige Demo-Artikeltext. Er sollte nur für Administratoren und Abonnenten mit aktivem Abonnement sichtbar sein.', 'der-flugschreiber-subscriptions'),
                        'meta_input' => array(
                            DF_Subscriptions::ARTICLE_MAGAZINE_META => $magazine_id,
                            '_df_demo_content' => '1',
                        ),
                    )
                );

                if (!is_wp_error($article_id)) {
                    if (!empty($magazine_data['article_image'])) {
                        update_post_meta($article_id, DF_Subscriptions::ARTICLE_IMAGE_URL_META, $magazine_data['article_image']);
                        $this->maybe_set_featured_image_from_url($article_id, $magazine_data['article_image']);
                    }

                    wp_set_object_terms($article_id, $magazine_data['topics'], DF_Subscriptions::TOPIC_TAXONOMY, false);
                    $created_articles++;
                }
            }
        }

        $message = sprintf(
            /* translators: 1: created magazine count, 2: created article count. */
            __('Demo content ready. Created %1$d magazines and %2$d articles. Existing matching demo titles were skipped.', 'der-flugschreiber-subscriptions'),
            $created_magazines,
            $created_articles
        );

        $this->redirect_with_message('success', $message);
    }

    private function handle_remove_demo_content()
    {
        if (!isset($_POST['df_remove_demo_content_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['df_remove_demo_content_nonce'])), 'df_remove_demo_content')) {
            $this->redirect_with_message('error', __('Security check failed.', 'der-flugschreiber-subscriptions'));
        }

        $demo_posts = get_posts(
            array(
                'post_type'        => array(DF_Subscriptions::MAGAZINE_POST_TYPE, DF_Subscriptions::ARTICLE_POST_TYPE),
                'post_status'      => 'any',
                'posts_per_page'   => -1,
                'fields'           => 'ids',
                'meta_key'         => '_df_demo_content',
                'meta_value'       => '1',
                'suppress_filters' => true,
                'no_found_rows'    => true,
            )
        );

        $trashed = 0;

        foreach ($demo_posts as $post_id) {
            // Move to Trash (reversible) rather than deleting permanently.
            if (wp_trash_post((int) $post_id)) {
                $trashed++;
            }
        }

        $message = sprintf(
            /* translators: %d: number of demo items moved to the Trash. */
            _n('Moved %d demo item to the Trash.', 'Moved %d demo items to the Trash.', $trashed, 'der-flugschreiber-subscriptions'),
            $trashed
        );

        $this->redirect_with_message('success', $message);
    }

    private function find_post_by_title($title, $post_type)
    {
        // Replacement for the deprecated get_page_by_title() (deprecated since WP 6.2).
        $matches = get_posts(
            array(
                'post_type'        => $post_type,
                'post_status'      => array('publish', 'draft', 'pending', 'future', 'private'),
                'title'            => $title,
                'posts_per_page'   => 1,
                'orderby'          => 'ID',
                'order'            => 'ASC',
                'suppress_filters' => true,
                'no_found_rows'    => true,
            )
        );

        return $matches ? $matches[0] : null;
    }

    private function maybe_set_featured_image_from_url($post_id, $image_url)
    {
        if (has_post_thumbnail($post_id) || !$image_url) {
            return;
        }

        $attachment_id = attachment_url_to_postid($image_url);

        if ($attachment_id) {
            set_post_thumbnail($post_id, $attachment_id);
        }
    }

    private function handle_bulk_subscribers()
    {
        if (!isset($_POST['df_bulk_subscribers_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['df_bulk_subscribers_nonce'])), 'df_bulk_subscribers')) {
            $this->redirect_with_message('error', __('Security check failed.', 'der-flugschreiber-subscriptions'));
        }

        $user_ids = isset($_POST['df_user_ids']) ? array_filter(array_map('absint', (array) wp_unslash($_POST['df_user_ids']))) : array();
        $action = isset($_POST['df_bulk_action']) ? sanitize_key(wp_unslash($_POST['df_bulk_action'])) : '';

        if (!$user_ids || !in_array($action, array('active', 'paused', 'cancelled', 'extend_30', 'delete'), true)) {
            $this->redirect_with_message('error', __('Select subscribers and a valid bulk action.', 'der-flugschreiber-subscriptions'));
        }

        foreach ($user_ids as $user_id) {
            $user = get_userdata($user_id);

            if (!$user || !in_array(DF_Subscriptions::ROLE, (array) $user->roles, true)) {
                continue;
            }

            if ('delete' === $action) {
                if (user_can($user, 'manage_options')) {
                    continue;
                }

                require_once ABSPATH . 'wp-admin/includes/user.php';
                wp_delete_user($user_id);
                continue;
            }

            if ('extend_30' === $action) {
                $current = get_user_meta($user_id, DF_Subscriptions::EXPIRES_META, true);
                $base = DateTimeImmutable::createFromFormat('!Y-m-d', $current, wp_timezone());

                if (!$base || $base < current_datetime()) {
                    $base = current_datetime()->setTime(0, 0);
                }

                $expires_at = $base->modify('+30 days')->format('Y-m-d');
                update_user_meta($user_id, DF_Subscriptions::EXPIRES_META, $expires_at);
                update_user_meta($user_id, DF_Subscriptions::STATUS_META, 'active');
                $this->add_history($user_id, 'extended_30', $expires_at, 'active');
                continue;
            }

            update_user_meta($user_id, DF_Subscriptions::STATUS_META, $action);
            $this->add_history($user_id, 'status_changed', get_user_meta($user_id, DF_Subscriptions::EXPIRES_META, true), $action);
        }

        $this->redirect_with_message('success', __('Bulk action completed.', 'der-flugschreiber-subscriptions'));
    }

    private function handle_export_subscribers()
    {
        if (!isset($_GET['df_export_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['df_export_nonce'])), 'df_export_subscribers')) {
            wp_die(esc_html__('Security check failed.', 'der-flugschreiber-subscriptions'));
        }

        $users = get_users(array('role' => DF_Subscriptions::ROLE, 'orderby' => 'ID'));
        nocache_headers();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=df-subscribers-' . gmdate('Y-m-d') . '.csv');
        $output = fopen('php://output', 'w');
        fputcsv($output, array('email', 'name', 'expires_at', 'status'));

        foreach ($users as $user) {
            fputcsv(
                $output,
                array(
                    $user->user_email,
                    $this->sanitize_csv_cell($user->display_name),
                    get_user_meta($user->ID, DF_Subscriptions::EXPIRES_META, true),
                    $this->access->get_subscription_status($user->ID),
                )
            );
        }

        fclose($output);
        exit;
    }

    private function handle_import_subscribers()
    {
        if (!isset($_POST['df_import_subscribers_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['df_import_subscribers_nonce'])), 'df_import_subscribers')) {
            $this->redirect_with_message('error', __('Security check failed.', 'der-flugschreiber-subscriptions'));
        }

        if (empty($_FILES['df_subscribers_csv']['tmp_name']) || !is_uploaded_file($_FILES['df_subscribers_csv']['tmp_name'])) {
            $this->redirect_with_message('error', __('Choose a valid CSV file.', 'der-flugschreiber-subscriptions'));
        }

        $handle = fopen($_FILES['df_subscribers_csv']['tmp_name'], 'r');
        $headers = $handle ? fgetcsv($handle) : false;
        $headers = is_array($headers) ? array_map('sanitize_key', $headers) : array();
        $required = array('email', 'name', 'expires_at', 'status');

        if (!$handle || array_diff($required, $headers)) {
            if ($handle) {
                fclose($handle);
            }
            $this->redirect_with_message('error', __('The CSV columns are invalid.', 'der-flugschreiber-subscriptions'));
        }

        $indexes = array_flip($headers);
        $processed = 0;

        while (($row = fgetcsv($handle)) !== false) {
            $email = sanitize_email(isset($row[$indexes['email']]) ? $row[$indexes['email']] : '');
            $name = sanitize_text_field(isset($row[$indexes['name']]) ? $row[$indexes['name']] : '');
            $expires_at = sanitize_text_field(isset($row[$indexes['expires_at']]) ? $row[$indexes['expires_at']] : '');
            $status = $this->sanitize_status(isset($row[$indexes['status']]) ? $row[$indexes['status']] : 'active');

            if (!is_email($email) || !$this->is_valid_date($expires_at)) {
                continue;
            }

            $user_id = email_exists($email);

            if (!$user_id) {
                $user_id = wp_create_user($this->generate_username_from_email($email), wp_generate_password(20, true), $email);

                if (is_wp_error($user_id)) {
                    continue;
                }

                (new WP_User($user_id))->set_role(DF_Subscriptions::ROLE);
                wp_new_user_notification($user_id, null, 'user');
            } else {
                $existing_user = new WP_User($user_id);

                if (!in_array(DF_Subscriptions::ROLE, (array) $existing_user->roles, true)) {
                    $existing_user->add_role(DF_Subscriptions::ROLE);
                }
            }

            wp_update_user(array('ID' => $user_id, 'display_name' => $name ? $name : $email));
            update_user_meta($user_id, DF_Subscriptions::EXPIRES_META, $expires_at);
            update_user_meta($user_id, DF_Subscriptions::STATUS_META, $status);
            $this->add_history($user_id, 'imported', $expires_at, $status);
            $processed++;
        }

        fclose($handle);
        $this->redirect_with_message('success', sprintf(__('Imported or updated %d subscribers.', 'der-flugschreiber-subscriptions'), $processed));
    }

    private function render_status_select($name, $selected, $id = '')
    {
        ?>
        <select name="<?php echo esc_attr($name); ?>" <?php echo $id ? 'id="' . esc_attr($id) . '"' : ''; ?>>
            <option value="active" <?php selected($selected, 'active'); ?>><?php esc_html_e('Active', 'der-flugschreiber-subscriptions'); ?></option>
            <option value="trial" <?php selected($selected, 'trial'); ?>><?php esc_html_e('Trial', 'der-flugschreiber-subscriptions'); ?></option>
            <option value="paused" <?php selected($selected, 'paused'); ?>><?php esc_html_e('Paused', 'der-flugschreiber-subscriptions'); ?></option>
            <option value="cancelled" <?php selected($selected, 'cancelled'); ?>><?php esc_html_e('Cancelled', 'der-flugschreiber-subscriptions'); ?></option>
        </select>
        <?php
    }

    private function sanitize_status($status)
    {
        $status = sanitize_key($status);

        return in_array($status, array('active', 'trial', 'paused', 'cancelled'), true) ? $status : 'active';
    }

    private function add_history($user_id, $action, $expires_at, $status)
    {
        $history = get_user_meta($user_id, DF_Subscriptions::HISTORY_META, true);
        $history = is_array($history) ? $history : array();
        array_unshift(
            $history,
            array(
                'time' => current_time('mysql'),
                'action' => sanitize_key($action),
                'expires_at' => sanitize_text_field($expires_at),
                'status' => $this->sanitize_status($status),
                'actor' => get_current_user_id(),
            )
        );
        update_user_meta($user_id, DF_Subscriptions::HISTORY_META, array_slice($history, 0, 50));
    }

    private function get_last_history_label($user_id)
    {
        $history = get_user_meta($user_id, DF_Subscriptions::HISTORY_META, true);

        if (!is_array($history) || empty($history[0])) {
            return __('No history', 'der-flugschreiber-subscriptions');
        }

        $entry = $history[0];

        return sprintf('%s: %s', isset($entry['time']) ? $entry['time'] : '', isset($entry['action']) ? $entry['action'] : '');
    }

    private function sanitize_csv_cell($value)
    {
        $value = (string) $value;

        return preg_match('/^[=\-+@]/', $value) ? "'" . $value : $value;
    }

    private function render_history($user_id)
    {
        $history = get_user_meta($user_id, DF_Subscriptions::HISTORY_META, true);

        if (!is_array($history) || !$history) {
            echo esc_html__('No history', 'der-flugschreiber-subscriptions');
            return;
        }

        echo '<details><summary>' . esc_html($this->get_last_history_label($user_id)) . '</summary><ul>';

        foreach (array_slice($history, 0, 10) as $entry) {
            $actor = !empty($entry['actor']) ? get_userdata(absint($entry['actor'])) : null;
            $actor_name = $actor ? $actor->display_name : __('System', 'der-flugschreiber-subscriptions');
            printf(
                '<li>%s</li>',
                esc_html(
                    sprintf(
                        '%s | %s | %s | %s | %s',
                        isset($entry['time']) ? $entry['time'] : '',
                        isset($entry['action']) ? $entry['action'] : '',
                        isset($entry['status']) ? $entry['status'] : '',
                        isset($entry['expires_at']) ? $entry['expires_at'] : '',
                        $actor_name
                    )
                )
            );
        }

        echo '</ul></details>';
    }

    private function load_messages()
    {
        if (!isset($_GET['df_message_type'], $_GET['df_message'])) {
            return;
        }

        $this->messages[] = array(
            'type' => sanitize_key(wp_unslash($_GET['df_message_type'])),
            'message' => sanitize_text_field(wp_unslash($_GET['df_message'])),
        );
    }

    private function render_messages()
    {
        foreach ($this->messages as $message) {
            $class = 'success' === $message['type'] ? 'notice notice-success' : 'notice notice-error';
            printf('<div class="%s"><p>%s</p></div>', esc_attr($class), esc_html($message['message']));
        }
    }

    private function redirect_with_message($type, $message)
    {
        wp_safe_redirect(
            add_query_arg(
                array(
                    'page' => 'df-subscriptions',
                    'df_message_type' => $type,
                    'df_message' => $message,
                ),
                admin_url('admin.php')
            )
        );
        exit;
    }

    private function generate_username_from_email($email)
    {
        $email_parts = explode('@', $email);
        $base = sanitize_user($email_parts[0], true);

        if (!$base) {
            $base = 'subscriber';
        }

        $username = $base;
        $index = 1;

        while (username_exists($username)) {
            $username = $base . $index;
            $index++;
        }

        return $username;
    }

    private function is_valid_date($date)
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return false;
        }

        $parts = explode('-', $date);

        return checkdate((int) $parts[1], (int) $parts[2], (int) $parts[0]);
    }

    /**
     * Accept either a full URL or a site-relative path/slug (e.g. "/mein-konto/")
     * and always store a full, valid URL on this site.
     */
    private function sanitize_internal_url($value)
    {
        $value = trim((string) $value);

        if ('' === $value) {
            return '';
        }

        if (preg_match('#^https?://#i', $value)) {
            return esc_url_raw($value);
        }

        return esc_url_raw(home_url('/' . ltrim($value, '/')));
    }

    public function render_texts_page()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $this->load_messages();
        $defaults = DF_Subscriptions_Shortcodes::text_defaults();

        $tabs = array(
            'startseite' => array(
                'label' => 'Startseite',
                'icon'  => 'dashicons-admin-home',
                'sections' => array(
                    array(
                        'title' => 'Preis & Angebot',
                        'icon'  => 'dashicons-tag',
                        'color' => '#16a34a',
                        'desc'  => 'Der Abo-Preis und das Angebot. Diese Werte erscheinen im Hero, in der Versprechen-Leiste und im Abschluss-Aufruf.',
                        'fields' => array(
                            'home_price'           => array('label' => 'Abo-Preis',           'hint' => 'z.B. "38,25 €" — der große Preis im Angebot'),
                            'home_regular_price'   => array('label' => 'Regulärer Preis',     'hint' => 'Durchgestrichener Vergleichspreis, z.B. "51,00 €"'),
                            'home_discount'        => array('label' => 'Rabatt-Badge',        'hint' => 'Kleines Abzeichen neben dem Preis, z.B. "-25%". Leer lassen zum Ausblenden'),
                            'home_issues_per_year' => array('label' => 'Ausgaben pro Jahr',   'hint' => 'Zahl, z.B. "4" — erscheint im Angebot und in der Versprechen-Leiste'),
                        ),
                    ),
                    array(
                        'title' => 'Hero-Bereich',
                        'icon'  => 'dashicons-admin-home',
                        'color' => '#2271b1',
                        'desc'  => 'Der große Einstiegsbereich oben auf der Startseite.',
                        'fields' => array(
                            'home_eyebrow'       => array('label' => 'Subzeile (klein über der Überschrift)', 'hint' => 'Kurze Zeile in Kleinbuchstaben über dem Titel'),
                            'home_headline'      => array('label' => 'Haupt-Überschrift (H1)',                'hint' => 'Großer Titel im Hero'),
                            'home_lead'          => array('label' => 'Lead-Text',                             'hint' => 'Beschreibungstext unter der Überschrift'),
                            'home_offer_label'   => array('label' => 'Angebot-Bezeichnung',                   'hint' => 'z.B. "Print-Abonnement im ersten Jahr"'),
                            'home_offer_detail'  => array('label' => 'Angebot-Detail',                        'hint' => '%s wird durch die Ausgaben-Anzahl ersetzt'),
                            'home_subscribe_btn' => array('label' => 'Button "Abonnieren"',                   'hint' => 'Hauptbutton im Hero und im Abschluss-CTA'),
                            'home_hero_link'     => array('label' => 'Link "Aktuelle Ausgabe ansehen"',       'hint' => 'Textlink unter dem Button'),
                        ),
                    ),
                    array(
                        'title' => 'Versprechen-Leiste',
                        'icon'  => 'dashicons-awards',
                        'color' => '#8b5cf6',
                        'desc'  => 'Die drei Punkte direkt unter dem Hero.',
                        'fields' => array(
                            'home_promise_1_label'  => array('label' => 'Punkt 1 — Bezeichnung',  'hint' => 'Wird hinter der Ausgaben-Anzahl angezeigt'),
                            'home_promise_2_value'  => array('label' => 'Punkt 2 — Fetter Text',  'hint' => 'z.B. "Praxis"'),
                            'home_promise_2_label'  => array('label' => 'Punkt 2 — Beschreibung', 'hint' => ''),
                            'home_promise_3_value'  => array('label' => 'Punkt 3 — Fetter Text',  'hint' => 'z.B. "Community"'),
                            'home_promise_3_label'  => array('label' => 'Punkt 3 — Beschreibung', 'hint' => ''),
                        ),
                    ),
                    array(
                        'title' => 'Aktuelle Ausgabe',
                        'icon'  => 'dashicons-book',
                        'color' => '#059669',
                        'desc'  => 'Abschnitt mit Cover und Beschreibung der neuesten Ausgabe.',
                        'fields' => array(
                            'home_current_eyebrow' => array('label' => 'Subzeile',                'hint' => 'Klein über dem Titel'),
                            'home_current_intro'   => array('label' => 'Einleitungssatz (H3)',    'hint' => 'Satz unter dem Cover der Ausgabe'),
                            'home_current_btn'     => array('label' => 'Button-Text',             'hint' => 'Zum Abo-Formular'),
                            'home_current_more'    => array('label' => 'Link "Mehr zur Ausgabe"', 'hint' => 'Textlink zur Ausgaben-Seite'),
                            'home_current_free'    => array('label' => 'Link "Gratis-Ausgaben"',  'hint' => 'Verweist auf das Archiv mit freien älteren PDF-Ausgaben'),
                        ),
                    ),
                    array(
                        'title' => 'Themen-Abschnitt',
                        'icon'  => 'dashicons-category',
                        'color' => '#d97706',
                        'desc'  => 'Die vier Themen-Kacheln (Flugsicherheit, Technik, …).',
                        'fields' => array(
                            'home_topics_eyebrow'  => array('label' => 'Subzeile',          'hint' => ''),
                            'home_topics_headline' => array('label' => 'Überschrift',       'hint' => ''),
                            'home_topic_1_title'   => array('label' => 'Thema 1 — Titel',  'hint' => ''),
                            'home_topic_1_body'    => array('label' => 'Thema 1 — Text',   'hint' => ''),
                            'home_topic_2_title'   => array('label' => 'Thema 2 — Titel',  'hint' => ''),
                            'home_topic_2_body'    => array('label' => 'Thema 2 — Text',   'hint' => ''),
                            'home_topic_3_title'   => array('label' => 'Thema 3 — Titel',  'hint' => ''),
                            'home_topic_3_body'    => array('label' => 'Thema 3 — Text',   'hint' => ''),
                            'home_topic_4_title'   => array('label' => 'Thema 4 — Titel',  'hint' => ''),
                            'home_topic_4_body'    => array('label' => 'Thema 4 — Text',   'hint' => ''),
                        ),
                    ),
                    array(
                        'title' => 'Artikel-Abschnitt',
                        'icon'  => 'dashicons-media-text',
                        'color' => '#0891b2',
                        'desc'  => 'Die neuesten Artikel auf der Startseite.',
                        'fields' => array(
                            'home_articles_eyebrow'  => array('label' => 'Subzeile',              'hint' => ''),
                            'home_articles_headline' => array('label' => 'Überschrift',           'hint' => ''),
                            'home_articles_all'      => array('label' => 'Link "Alle Artikel"',   'hint' => ''),
                            'home_article_read'      => array('label' => 'Link "Artikel lesen"',  'hint' => 'Auf jeder Artikel-Karte'),
                        ),
                    ),
                    array(
                        'title' => 'Archiv-Abschnitt',
                        'icon'  => 'dashicons-archive',
                        'color' => '#64748b',
                        'desc'  => 'Ältere Ausgaben auf der Startseite.',
                        'fields' => array(
                            'home_archive_eyebrow'  => array('label' => 'Subzeile',             'hint' => ''),
                            'home_archive_headline' => array('label' => 'Überschrift',          'hint' => ''),
                            'home_archive_all'      => array('label' => 'Link "Alle Ausgaben"', 'hint' => ''),
                        ),
                    ),
                    array(
                        'title' => 'Über uns',
                        'icon'  => 'dashicons-groups',
                        'color' => '#be185d',
                        'desc'  => 'Kurzvorstellung des Magazins.',
                        'fields' => array(
                            'home_about_eyebrow'  => array('label' => 'Subzeile',    'hint' => ''),
                            'home_about_headline' => array('label' => 'Überschrift', 'hint' => ''),
                            'home_about_text_1'   => array('label' => 'Absatz 1',   'hint' => ''),
                            'home_about_text_2'   => array('label' => 'Absatz 2',   'hint' => ''),
                        ),
                    ),
                    array(
                        'title' => 'Abschluss-CTA',
                        'icon'  => 'dashicons-megaphone',
                        'color' => '#b45309',
                        'desc'  => 'Der letzte Abschnitt mit dem finalen Abo-Aufruf.',
                        'fields' => array(
                            'home_cta_eyebrow'  => array('label' => 'Subzeile',    'hint' => ''),
                            'home_cta_headline' => array('label' => 'Überschrift', 'hint' => ''),
                            'home_cta_text'     => array('label' => 'Text',        'hint' => '%s wird durch den Preis ersetzt'),
                        ),
                    ),
                ),
            ),
            'ausgaben' => array(
                'label' => 'Ausgaben',
                'icon'  => 'dashicons-pressthis',
                'sections' => array(
                    array(
                        'title' => 'Ausgaben-Liste',
                        'icon'  => 'dashicons-pressthis',
                        'color' => '#7c3aed',
                        'desc'  => 'Texte auf der Seite mit allen Ausgaben.',
                        'fields' => array(
                            'issues_tab_new'       => array('label' => 'Tab: Neue Ausgaben',       'hint' => 'Erster Tab-Button'),
                            'issues_tab_old'       => array('label' => 'Tab: Alte Ausgaben',       'hint' => 'Zweiter Tab-Button'),
                            'issues_popular_label' => array('label' => 'Beliebte Kategorien',      'hint' => 'Beschriftung über den Kategorie-Chips'),
                            'issues_empty'         => array('label' => 'Hinweis: Keine Ausgaben',  'hint' => 'Wird angezeigt wenn keine Ausgaben gefunden wurden'),
                            'issue_date_label'     => array('label' => 'Label "Datum"',            'hint' => 'Auf Ausgaben-Karten'),
                            'list_show_more'       => array('label' => 'Button "Mehr laden"',      'hint' => 'Gilt für alle Listen (Ausgaben, Artikel, Blog)'),
                        ),
                    ),
                    array(
                        'title' => 'Ausgaben-Seite',
                        'icon'  => 'dashicons-book-alt',
                        'color' => '#059669',
                        'desc'  => 'Buttons auf der Einzelseite einer Ausgabe.',
                        'fields' => array(
                            'magazine_pdf_btn'       => array('label' => 'Button "PDF öffnen"',       'hint' => ''),
                            'magazine_subscribe_btn' => array('label' => 'Button "Jetzt abonnieren"', 'hint' => 'Erscheint wenn kein aktives Abo'),
                            'magazine_login_btn'     => array('label' => 'Button "Einloggen"',        'hint' => 'Erscheint wenn nicht angemeldet'),
                        ),
                    ),
                ),
            ),
            'artikel' => array(
                'label' => 'Artikel',
                'icon'  => 'dashicons-text-page',
                'sections' => array(
                    array(
                        'title' => 'Artikel-Liste',
                        'icon'  => 'dashicons-text-page',
                        'color' => '#d97706',
                        'desc'  => 'Texte auf der Seite mit allen Artikeln.',
                        'fields' => array(
                            'articles_eyebrow' => array('label' => 'Eyebrow-Text',           'hint' => 'Kleiner Text über dem Archiv-Titel'),
                            'articles_empty'   => array('label' => 'Hinweis: Keine Artikel', 'hint' => 'Wird angezeigt wenn keine Artikel gefunden wurden'),
                        ),
                    ),
                    array(
                        'title' => 'Artikel-Seite',
                        'icon'  => 'dashicons-admin-page',
                        'color' => '#dc2626',
                        'desc'  => 'Meta-Labels und Aktionen auf der Einzelseite eines Artikels.',
                        'fields' => array(
                            'article_meta_author'   => array('label' => 'Meta: Autor-Label',         'hint' => 'z.B. "Text" oder "Autor"'),
                            'article_meta_date'     => array('label' => 'Meta: Datum-Label',         'hint' => ''),
                            'article_meta_duration' => array('label' => 'Meta: Lesezeit-Label',      'hint' => ''),
                            'article_meta_min'      => array('label' => 'Meta: Minuten-Format',      'hint' => '%d wird durch die Zahl ersetzt, z.B. "%d Min"'),
                            'article_share'         => array('label' => 'Label "Teilen"',            'hint' => 'Über den Share-Links'),
                            'article_pdf_btn'       => array('label' => 'Button "PDF herunterladen"','hint' => ''),
                        ),
                    ),
                    array(
                        'title' => 'Allgemein',
                        'icon'  => 'dashicons-editor-textcolor',
                        'color' => '#374151',
                        'desc'  => 'Texte die auf mehreren Artikel-Seiten vorkommen.',
                        'fields' => array(
                            'article_read_btn'       => array('label' => 'Button "Artikel lesen"',                'hint' => 'In Artikellisten'),
                            'article_read_link'      => array('label' => 'Link "Artikel lesen"',                  'hint' => 'Auf Ausgaben-Seiten'),
                            'issue_summary_fallback' => array('label' => 'Ausgaben-Kurzbeschreibung (Fallback)',   'hint' => 'Wird angezeigt wenn eine Ausgabe keine eigene Beschreibung hat'),
                        ),
                    ),
                ),
            ),
            'blog' => array(
                'label' => 'Blog',
                'icon'  => 'dashicons-rss',
                'sections' => array(
                    array(
                        'title' => 'Blog / Posts',
                        'icon'  => 'dashicons-rss',
                        'color' => '#ea580c',
                        'desc'  => 'Texte im Blog-Bereich.',
                        'fields' => array(
                            'blog_eyebrow' => array('label' => 'Eyebrow: Blog',           'hint' => 'Kleiner Text über dem Blog-Titel'),
                            'posts_empty'  => array('label' => 'Hinweis: Keine Beiträge', 'hint' => 'Wird angezeigt wenn keine Posts gefunden wurden'),
                        ),
                    ),
                ),
            ),
            'filter' => array(
                'label' => 'Filter',
                'icon'  => 'dashicons-filter',
                'sections' => array(
                    array(
                        'title' => 'Filter & Sortierung',
                        'icon'  => 'dashicons-filter',
                        'color' => '#0284c7',
                        'desc'  => 'Beschriftungen für alle Filter-Dropdowns und Sortieroptionen.',
                        'fields' => array(
                            'filter_year'         => array('label' => 'Filter: Erscheinungsjahr',  'hint' => 'Überschrift des Jahres-Dropdowns'),
                            'filter_year_all'     => array('label' => 'Filter: Alle Jahre',        'hint' => 'Standardoption'),
                            'filter_topic'        => array('label' => 'Filter: Themenbereich',     'hint' => 'Überschrift des Themen-Dropdowns'),
                            'filter_topic_all'    => array('label' => 'Filter: Alle Themen',       'hint' => 'Standardoption'),
                            'filter_magazine'     => array('label' => 'Filter: Ausgabennummer',    'hint' => 'Überschrift im Artikel-Filter'),
                            'filter_category'     => array('label' => 'Filter: Kategorie',         'hint' => 'Überschrift des Kategorie-Dropdowns (Blog)'),
                            'filter_category_all' => array('label' => 'Filter: Alle Kategorien',   'hint' => 'Standardoption'),
                            'filter_sort'         => array('label' => 'Filter: Sortierung',        'hint' => 'Überschrift des Sortier-Dropdowns'),
                            'sort_newest'         => array('label' => 'Sortierung: Neueste',       'hint' => ''),
                            'sort_oldest'         => array('label' => 'Sortierung: Älteste',       'hint' => ''),
                            'sort_title'          => array('label' => 'Sortierung: Titel',         'hint' => ''),
                            'sort_title_az'       => array('label' => 'Sortierung: Titel A–Z',     'hint' => 'Nur im Blog-Bereich'),
                        ),
                    ),
                ),
            ),
            'anmeldung' => array(
                'label' => 'Anmeldung',
                'icon'  => 'dashicons-lock',
                'sections' => array(
                    array(
                        'title' => 'Anmelde-Formular',
                        'icon'  => 'dashicons-lock',
                        'color' => '#0f766e',
                        'desc'  => 'Felder und Meldungen im Anmeldebereich der Konto-Seite.',
                        'fields' => array(
                            'login_active'   => array('label' => 'Meldung: Abo aktiv',       'hint' => 'Wird angezeigt wenn der Nutzer eingeloggt ist und ein aktives Abo hat'),
                            'login_inactive' => array('label' => 'Meldung: Abo inaktiv',     'hint' => 'Wird angezeigt wenn eingeloggt aber kein aktives Abo'),
                            'login_username' => array('label' => 'Feld: E-Mail / Benutzername', 'hint' => ''),
                            'login_password' => array('label' => 'Feld: Passwort',           'hint' => ''),
                            'login_remember' => array('label' => 'Checkbox: Angemeldet bleiben', 'hint' => ''),
                            'login_submit'   => array('label' => 'Button: Anmelden',         'hint' => ''),
                            'login_forgot'   => array('label' => 'Link: Passwort vergessen', 'hint' => ''),
                        ),
                    ),
                    array(
                        'title' => 'Passwort zurücksetzen',
                        'icon'  => 'dashicons-update',
                        'color' => '#b45309',
                        'desc'  => 'Eigener, gestalteter Ablauf zum Zurücksetzen des Passworts — ohne den Standard-Bildschirm von WordPress.',
                        'fields' => array(
                            'lost_intro'             => array('label' => 'Schritt 1 — Einleitung',        'hint' => 'Text über dem Feld zur Eingabe der E-Mail'),
                            'lost_submit'            => array('label' => 'Schritt 1 — Button',            'hint' => 'Sendet den Zurücksetzen-Link'),
                            'lost_sent'              => array('label' => 'Schritt 1 — Bestätigung',       'hint' => 'Wird nach dem Absenden angezeigt (verrät nicht, ob das Konto existiert)'),
                            'lost_back'              => array('label' => 'Link „Zurück zur Anmeldung“',  'hint' => ''),
                            'reset_intro'            => array('label' => 'Schritt 2 — Einleitung',        'hint' => 'Text über den Feldern für das neue Passwort'),
                            'reset_password_new'     => array('label' => 'Schritt 2 — Feld „Neues Passwort“',     'hint' => ''),
                            'reset_password_confirm' => array('label' => 'Schritt 2 — Feld „Passwort bestätigen“','hint' => ''),
                            'reset_submit'           => array('label' => 'Schritt 2 — Button',            'hint' => 'Speichert das neue Passwort'),
                            'reset_done'             => array('label' => 'Erfolg-Meldung',                'hint' => 'Wird auf der Anmeldeseite nach erfolgreichem Zurücksetzen angezeigt'),
                            'reset_email_subject'    => array('label' => 'E-Mail — Betreff',             'hint' => ''),
                            'reset_email_body'       => array('label' => 'E-Mail — Text',                'hint' => '%s wird durch den Zurücksetzen-Link ersetzt'),
                        ),
                    ),
                ),
            ),
        );

        $long_keys = array(
            'home_lead', 'home_offer_detail', 'home_current_intro',
            'home_topic_1_body', 'home_topic_2_body', 'home_topic_3_body', 'home_topic_4_body',
            'home_about_text_1', 'home_about_text_2', 'home_cta_text',
            'issue_summary_fallback', 'login_active', 'login_inactive',
            'lost_intro', 'lost_sent', 'reset_intro', 'reset_email_body',
        );

        $active_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'startseite';
        if (!isset($tabs[$active_tab])) {
            $active_tab = 'startseite';
        }
        ?>
        <style>
        .df-texts-card {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin: 16px 0;
            box-shadow: 0 1px 3px rgba(0,0,0,.06);
            overflow: hidden;
        }
        .df-texts-card__header {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 20px;
            border-left: 4px solid var(--df-color);
            background: #fafafa;
            border-bottom: 1px solid #eee;
        }
        .df-texts-card__icon {
            font-size: 22px;
            color: var(--df-color);
            width: 28px;
            text-align: center;
        }
        .df-texts-card__header h2 { margin: 0; font-size: 15px; font-weight: 600; color: #1d2327; }
        .df-texts-card__header p  { margin: 2px 0 0; font-size: 12px; color: #646970; }
        .df-texts-card__body { padding: 8px 20px 16px; }
        .df-texts-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0 32px;
        }
        .df-texts-field { padding: 10px 0; border-bottom: 1px solid #f0f0f0; }
        .df-texts-field:last-child,
        .df-texts-field.df-texts-field--full:last-child { border-bottom: none; }
        .df-texts-field--full { grid-column: 1 / -1; }
        .df-texts-field label { display: block; font-weight: 600; font-size: 13px; color: #1d2327; margin-bottom: 5px; }
        .df-texts-field input[type="text"],
        .df-texts-field textarea { width: 100%; box-sizing: border-box; }
        .df-texts-field textarea { resize: vertical; }
        .df-texts-field__hint    { font-size: 11px; color: #8c8f94; margin: 3px 0 0; font-style: italic; }
        .df-texts-field__default { font-size: 11px; color: #a0a5aa; margin: 4px 0 0; }
        .df-texts-field__default strong { color: #646970; font-weight: 600; }
        .df-texts-save-bar {
            position: sticky;
            bottom: 0;
            background: #fff;
            border-top: 1px solid #ddd;
            padding: 12px 0;
            margin-top: 8px;
            z-index: 10;
        }
        .df-tab-nav { margin: 16px 0 0; border-bottom: 1px solid #c3c4c7; display: flex; flex-wrap: wrap; gap: 0; }
        .df-tab-nav a {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            font-size: 14px;
            font-weight: 500;
            color: #1d2327;
            text-decoration: none;
            border: 1px solid transparent;
            border-bottom: none;
            border-radius: 3px 3px 0 0;
            margin-bottom: -1px;
            background: transparent;
        }
        .df-tab-nav a:hover { background: #f0f0f1; color: #1d2327; }
        .df-tab-nav a.is-active {
            background: #fff;
            border-color: #c3c4c7;
            color: #1d2327;
            font-weight: 600;
        }
        .df-tab-nav .dashicons { font-size: 16px; width: 16px; height: 16px; }
        </style>

        <div class="wrap">
            <h1>Website-Inhalte</h1>
            <p style="color:#646970;margin-bottom:0;"><?php esc_html_e('Alle Felder sind optional — leere Felder verwenden den Standardtext (grau angezeigt).', 'der-flugschreiber-subscriptions'); ?></p>
            <?php $this->render_messages(); ?>

            <nav class="df-tab-nav">
                <?php foreach ($tabs as $slug => $tab) : ?>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=df-subscriptions-texts&tab=' . $slug)); ?>"
                       class="<?php echo $active_tab === $slug ? 'is-active' : ''; ?>">
                        <span class="dashicons <?php echo esc_attr($tab['icon']); ?>"></span>
                        <?php echo esc_html($tab['label']); ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <form method="post" action="<?php echo esc_url(admin_url('admin.php?page=df-subscriptions-texts&tab=' . esc_attr($active_tab))); ?>">
                <?php wp_nonce_field('df_save_texts', 'df_save_texts_nonce'); ?>

                <?php foreach ($tabs[$active_tab]['sections'] as $section) : ?>
                    <div class="df-texts-card" style="--df-color:<?php echo esc_attr($section['color']); ?>">
                        <div class="df-texts-card__header">
                            <span class="df-texts-card__icon dashicons <?php echo esc_attr($section['icon']); ?>"></span>
                            <div>
                                <h2><?php echo esc_html($section['title']); ?></h2>
                                <p><?php echo esc_html($section['desc']); ?></p>
                            </div>
                        </div>
                        <div class="df-texts-card__body">
                            <div class="df-texts-grid">
                                <?php foreach ($section['fields'] as $key => $field) : ?>
                                    <?php
                                    $option_name = 'df_text_' . $key;
                                    $saved       = get_option($option_name, '');
                                    $default     = isset($defaults[$key]) ? $defaults[$key] : '';
                                    $field_id    = 'df_text_' . sanitize_key($key);
                                    $is_long     = in_array($key, $long_keys, true);
                                    ?>
                                    <div class="df-texts-field<?php echo $is_long ? ' df-texts-field--full' : ''; ?>">
                                        <label for="<?php echo esc_attr($field_id); ?>"><?php echo esc_html($field['label']); ?></label>
                                        <?php if ($is_long) : ?>
                                            <textarea name="<?php echo esc_attr($option_name); ?>" id="<?php echo esc_attr($field_id); ?>" rows="2" placeholder="<?php echo esc_attr($default); ?>"><?php echo esc_textarea($saved); ?></textarea>
                                        <?php else : ?>
                                            <input type="text" name="<?php echo esc_attr($option_name); ?>" id="<?php echo esc_attr($field_id); ?>" value="<?php echo esc_attr($saved); ?>" placeholder="<?php echo esc_attr($default); ?>">
                                        <?php endif; ?>
                                        <?php if ($field['hint']) : ?>
                                            <p class="df-texts-field__hint"><?php echo esc_html($field['hint']); ?></p>
                                        <?php endif; ?>
                                        <p class="df-texts-field__default"><strong>Standard:</strong> <?php echo esc_html($default); ?></p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

                <div class="df-texts-save-bar">
                    <?php submit_button('Inhalte speichern', 'primary', 'df_save_texts', false); ?>
                </div>
            </form>
        </div>
        <?php
    }

    private function handle_save_texts()
    {
        if (!isset($_POST['df_save_texts_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['df_save_texts_nonce'])), 'df_save_texts')) {
            $this->redirect_with_message_texts('error', __('Security check failed.', 'der-flugschreiber-subscriptions'));
        }

        $defaults = DF_Subscriptions_Shortcodes::text_defaults();

        foreach (array_keys($defaults) as $key) {
            $option_name = 'df_text_' . $key;
            $value = isset($_POST[$option_name]) ? sanitize_textarea_field(wp_unslash($_POST[$option_name])) : '';
            update_option($option_name, $value);
        }

        $this->redirect_with_message_texts('success', __('Texte gespeichert.', 'der-flugschreiber-subscriptions'));
    }

    private function redirect_with_message_texts($type, $message)
    {
        wp_safe_redirect(
            add_query_arg(
                array(
                    'page'            => 'df-subscriptions-texts',
                    'df_message_type' => $type,
                    'df_message'      => $message,
                ),
                admin_url('admin.php')
            )
        );
        exit;
    }
}
