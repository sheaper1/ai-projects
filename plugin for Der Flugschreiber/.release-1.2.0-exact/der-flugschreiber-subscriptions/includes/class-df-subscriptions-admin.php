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

        if (isset($_POST['df_bulk_subscribers'])) {
            $this->handle_bulk_subscribers();
        }

        if (isset($_POST['df_import_subscribers'])) {
            $this->handle_import_subscribers();
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
                        <td><input name="df_subscriber_password" id="df_subscriber_password" type="password" class="regular-text" autocomplete="new-password" required></td>
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
                            <input name="df_subscription_login_url" id="df_subscription_login_url" type="url" class="regular-text" value="<?php echo esc_attr(get_option(DF_Subscriptions::LOGIN_URL_OPTION, '')); ?>">
                            <p class="description"><?php esc_html_e('Create a page with the [df_login_form] shortcode and paste its URL here.', 'der-flugschreiber-subscriptions'); ?></p>
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
        $login_url = isset($_POST['df_subscription_login_url']) ? esc_url_raw(wp_unslash($_POST['df_subscription_login_url'])) : '';
        update_option(DF_Subscriptions::PAYMENT_URL_OPTION, $payment_url);
        update_option(DF_Subscriptions::LOGIN_URL_OPTION, $login_url);
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

        $topics = array('Pilot Training', 'Aviation Safety', 'Aircraft Technology', 'Travel Reports');
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
                'topics' => array('Pilot Training', 'Aircraft Technology'),
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
                'topics' => array('Aviation Safety', 'Travel Reports'),
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
                'topics' => array('Aviation Safety', 'Aircraft Technology'),
                'articles' => array(),
            ),
            array(
                'title' => '# 01 | Juni 2025',
                'date' => '2025-06-10',
                'number' => '# 01',
                'access' => 'free_pdf',
                'pdf' => 'https://example.com/der-flugschreiber-juni-2025.pdf',
                'cover' => 'https://derflugschreiber.digirelation.dev/wp-content/uploads/2026/04/oliver-johnson-MxFM97qeS3I-unsplash-1.webp',
                'topics' => array('Travel Reports'),
                'articles' => array(),
            ),
        );

        $created_magazines = 0;
        $created_articles = 0;

        foreach ($demo_magazines as $magazine_data) {
            $existing = get_page_by_title($magazine_data['title'], OBJECT, DF_Subscriptions::MAGAZINE_POST_TYPE);

            if ($existing) {
                $magazine_id = $existing->ID;
            } else {
                $magazine_id = wp_insert_post(
                    array(
                        'post_type' => DF_Subscriptions::MAGAZINE_POST_TYPE,
                        'post_status' => 'publish',
                        'post_title' => $magazine_data['title'],
                        'post_excerpt' => __('Demo issue preview for testing filters, subscriptions, and issue cards.', 'der-flugschreiber-subscriptions'),
                        'post_content' => __('This is demo issue content. New paid issues are protected by the subscription gate; old PDF issues can redirect directly to the PDF URL.', 'der-flugschreiber-subscriptions'),
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
                $existing_article = get_page_by_title($article_title, OBJECT, DF_Subscriptions::ARTICLE_POST_TYPE);

                if ($existing_article) {
                    continue;
                }

                $article_id = wp_insert_post(
                    array(
                        'post_type' => DF_Subscriptions::ARTICLE_POST_TYPE,
                        'post_status' => 'publish',
                        'post_title' => $article_title,
                        'post_excerpt' => __('This public excerpt is visible to visitors without an active subscription.', 'der-flugschreiber-subscriptions'),
                        'post_content' => __('This is the full demo article text. It should only be visible to administrators and subscribers with an active subscription date.', 'der-flugschreiber-subscriptions'),
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
}
