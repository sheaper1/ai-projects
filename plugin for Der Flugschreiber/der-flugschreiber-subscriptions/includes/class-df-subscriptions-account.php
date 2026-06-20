<?php

if (!defined('ABSPATH')) {
    exit;
}

class DF_Subscriptions_Account
{
    private $access;
    private $shortcodes;

    public function __construct(DF_Subscriptions_Access $access, DF_Subscriptions_Shortcodes $shortcodes)
    {
        $this->access = $access;
        $this->shortcodes = $shortcodes;
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
        $action = isset($_GET['df_action']) ? sanitize_key(wp_unslash($_GET['df_action'])) : '';

        if (!is_user_logged_in() || in_array($action, array('lostpassword', 'resetpass'), true)) {
            return $this->render_guest_account();
        }

        $user_id = get_current_user_id();
        $user = wp_get_current_user();
        $status_label = $this->access->get_subscription_status_label($user_id);
        $expires_at = get_user_meta($user_id, DF_Subscriptions::EXPIRES_META, true);
        $payment_url = get_option(DF_Subscriptions::PAYMENT_URL_OPTION, '');
        $message = isset($_GET['df_account_message']) ? sanitize_key(wp_unslash($_GET['df_account_message'])) : '';
        $display_name_id = wp_unique_id('df_account_display_name_');

        ob_start();
        ?>
        <section class="df-subscriptions-account">
            <header class="df-subscriptions-account__header">
                <p class="df-subscriptions-account__eyebrow"><?php esc_html_e('Abonnentenbereich', 'der-flugschreiber-subscriptions'); ?></p>
                <h1><?php esc_html_e('Mein Konto', 'der-flugschreiber-subscriptions'); ?></h1>
                <p class="df-subscriptions-account__intro"><?php esc_html_e('Verwalten Sie Ihr Abonnement und Ihre persönlichen Kontodaten.', 'der-flugschreiber-subscriptions'); ?></p>
            </header>

            <?php if ('updated' === $message) : ?>
                <p class="df-subscriptions-account__notice"><?php esc_html_e('Ihr Profil wurde aktualisiert.', 'der-flugschreiber-subscriptions'); ?></p>
            <?php elseif ($message) : ?>
                <p class="df-subscriptions-account__error"><?php esc_html_e('Das Profil konnte nicht aktualisiert werden.', 'der-flugschreiber-subscriptions'); ?></p>
            <?php endif; ?>

            <div class="df-subscriptions-account__grid">
                <article class="df-subscriptions-account__panel df-subscriptions-account__panel--subscription">
                    <h2><?php esc_html_e('Mein Abonnement', 'der-flugschreiber-subscriptions'); ?></h2>
                    <dl class="df-subscriptions-account__details">
                        <div>
                            <dt><?php esc_html_e('Status', 'der-flugschreiber-subscriptions'); ?></dt>
                            <dd><?php echo esc_html($status_label); ?></dd>
                        </div>
                        <div>
                            <dt><?php esc_html_e('Läuft ab am', 'der-flugschreiber-subscriptions'); ?></dt>
                            <dd><?php echo esc_html($expires_at ? $expires_at : __('Nicht festgelegt', 'der-flugschreiber-subscriptions')); ?></dd>
                        </div>
                        <div>
                            <dt><?php esc_html_e('E-Mail', 'der-flugschreiber-subscriptions'); ?></dt>
                            <dd><?php echo esc_html($user->user_email); ?></dd>
                        </div>
                    </dl>

                    <?php if ($payment_url) : ?>
                        <a class="df-subscriptions-account__button df-subscriptions-account__button--primary" href="<?php echo esc_url($payment_url); ?>"><?php esc_html_e('Abonnement verlängern', 'der-flugschreiber-subscriptions'); ?></a>
                    <?php endif; ?>
                </article>

                <article class="df-subscriptions-account__panel">
                    <h2><?php esc_html_e('Profil', 'der-flugschreiber-subscriptions'); ?></h2>
                    <form class="df-subscriptions-account__form" method="post" action="">
                        <?php wp_nonce_field('df_update_account', 'df_account_nonce'); ?>
                        <input type="hidden" name="df_account_action" value="1">
                        <input type="hidden" name="df_account_url" value="<?php echo esc_attr($this->get_current_url()); ?>">
                        <p>
                            <label for="<?php echo esc_attr($display_name_id); ?>"><?php esc_html_e('Anzeigename', 'der-flugschreiber-subscriptions'); ?></label>
                            <input type="text" id="<?php echo esc_attr($display_name_id); ?>" name="df_display_name" value="<?php echo esc_attr($user->display_name); ?>" required>
                        </p>
                        <button type="submit" class="df-subscriptions-account__button df-subscriptions-account__button--primary"><?php esc_html_e('Profil speichern', 'der-flugschreiber-subscriptions'); ?></button>
                    </form>
                </article>
            </div>

            <footer class="df-subscriptions-account__footer">
                <a class="df-subscriptions-account__text-link" href="<?php echo esc_url(add_query_arg('df_action', 'lostpassword', $this->get_current_url())); ?>"><?php esc_html_e('Passwort ändern', 'der-flugschreiber-subscriptions'); ?></a>
                <?php echo $this->shortcodes->logout_link(array('redirect' => $this->get_current_url())); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            </footer>
        </section>
        <?php

        return ob_get_clean();
    }

    private function render_guest_account()
    {
        $output = $this->shortcodes->login_form(array());
        $payment_url = get_option(DF_Subscriptions::PAYMENT_URL_OPTION, '');

        if (!$payment_url) {
            return $output;
        }

        ob_start();
        ?>
        <section class="df-subscriptions-account-signup">
            <h2><?php esc_html_e('Noch kein Konto?', 'der-flugschreiber-subscriptions'); ?></h2>
            <p><?php esc_html_e('Mit einem Abonnement erhalten Sie Zugang zu den geschützten Ausgaben und Artikeln. Nach dem Kauf wird Ihr persönliches Konto eingerichtet.', 'der-flugschreiber-subscriptions'); ?></p>
            <a class="df-subscriptions-account-signup__button" href="<?php echo esc_url($payment_url); ?>"><?php esc_html_e('Abonnement kaufen', 'der-flugschreiber-subscriptions'); ?></a>
        </section>
        <?php

        return $output . ob_get_clean();
    }

    private function get_current_url()
    {
        return isset($_SERVER['REQUEST_URI']) ? home_url(esc_url_raw(wp_unslash($_SERVER['REQUEST_URI']))) : home_url('/');
    }
}
