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
                        <td><input name="df_subscriber_password" id="df_subscriber_password" type="text" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="df_subscription_expires_at"><?php esc_html_e('Expires at', 'der-flugschreiber-subscriptions'); ?></label></th>
                        <td><input name="df_subscription_expires_at" id="df_subscription_expires_at" type="date" required></td>
                    </tr>
                </tbody>
            </table>
            <?php submit_button(__('Create subscriber', 'der-flugschreiber-subscriptions'), 'primary', 'df_create_subscriber'); ?>
        </form>
        <?php
    }

    private function render_subscribers_table()
    {
        $subscribers = get_users(
            array(
                'role' => DF_Subscriptions::ROLE,
                'orderby' => 'registered',
                'order' => 'DESC',
            )
        );

        if (!$subscribers) {
            echo '<p>' . esc_html__('No subscribers yet.', 'der-flugschreiber-subscriptions') . '</p>';
            return;
        }
        ?>
        <table class="widefat striped">
            <thead>
                <tr>
                    <th><?php esc_html_e('User', 'der-flugschreiber-subscriptions'); ?></th>
                    <th><?php esc_html_e('Email', 'der-flugschreiber-subscriptions'); ?></th>
                    <th><?php esc_html_e('Status', 'der-flugschreiber-subscriptions'); ?></th>
                    <th><?php esc_html_e('Change expiration date', 'der-flugschreiber-subscriptions'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($subscribers as $subscriber) : ?>
                    <tr>
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
                                <?php submit_button(__('Save', 'der-flugschreiber-subscriptions'), 'secondary small', 'df_update_subscriber', false); ?>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
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
                </tbody>
            </table>
            <?php submit_button(__('Save settings', 'der-flugschreiber-subscriptions'), 'primary', 'df_save_settings'); ?>
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

        $this->redirect_with_message('success', __('Subscriber created.', 'der-flugschreiber-subscriptions'));
    }

    private function handle_update_subscriber()
    {
        $user_id = isset($_POST['df_user_id']) ? absint($_POST['df_user_id']) : 0;

        if (!$user_id || !isset($_POST['df_update_subscriber_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['df_update_subscriber_nonce'])), 'df_update_subscriber_' . $user_id)) {
            $this->redirect_with_message('error', __('Security check failed.', 'der-flugschreiber-subscriptions'));
        }

        $expires_at = isset($_POST['df_subscription_expires_at']) ? sanitize_text_field(wp_unslash($_POST['df_subscription_expires_at'])) : '';

        if (!$this->is_valid_date($expires_at)) {
            $this->redirect_with_message('error', __('Please enter a valid expiration date.', 'der-flugschreiber-subscriptions'));
        }

        update_user_meta($user_id, DF_Subscriptions::EXPIRES_META, $expires_at);
        $this->redirect_with_message('success', __('Subscription updated.', 'der-flugschreiber-subscriptions'));
    }

    private function handle_save_settings()
    {
        if (!isset($_POST['df_save_settings_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['df_save_settings_nonce'])), 'df_save_settings')) {
            $this->redirect_with_message('error', __('Security check failed.', 'der-flugschreiber-subscriptions'));
        }

        $payment_url = isset($_POST['df_subscription_payment_url']) ? esc_url_raw(wp_unslash($_POST['df_subscription_payment_url'])) : '';
        update_option(DF_Subscriptions::PAYMENT_URL_OPTION, $payment_url);

        $this->redirect_with_message('success', __('Settings saved.', 'der-flugschreiber-subscriptions'));
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
