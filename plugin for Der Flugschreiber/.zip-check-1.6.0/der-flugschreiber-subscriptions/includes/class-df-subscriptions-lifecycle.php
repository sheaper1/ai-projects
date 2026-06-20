<?php

if (!defined('ABSPATH')) {
    exit;
}

class DF_Subscriptions_Lifecycle
{
    const CRON_HOOK = 'df_subscriptions_daily_event';

    private $access;

    public function __construct(DF_Subscriptions_Access $access)
    {
        $this->access = $access;
    }

    public function hooks()
    {
        add_action('init', array($this, 'schedule_events'));
        add_action(self::CRON_HOOK, array($this, 'process_subscription_notices'));
    }

    public function schedule_events()
    {
        if (!wp_next_scheduled(self::CRON_HOOK)) {
            wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily', self::CRON_HOOK);
        }
    }

    public function unschedule_events()
    {
        wp_clear_scheduled_hook(self::CRON_HOOK);
    }

    public function process_subscription_notices()
    {
        if ('1' !== get_option(DF_Subscriptions::EMAIL_NOTICES_OPTION, '1')) {
            return;
        }

        $users = get_users(
            array(
                'role' => DF_Subscriptions::ROLE,
                'fields' => array('ID', 'user_email', 'display_name'),
            )
        );
        $today = current_datetime()->setTime(0, 0);

        foreach ($users as $user) {
            $expires_at = get_user_meta($user->ID, DF_Subscriptions::EXPIRES_META, true);

            if (!$expires_at || 'active' !== $this->access->get_subscription_status($user->ID)) {
                continue;
            }

            $expires = DateTimeImmutable::createFromFormat('!Y-m-d', $expires_at, wp_timezone());

            if (!$expires) {
                continue;
            }

            $days = (int) $today->diff($expires)->format('%r%a');

            if ($days >= 0 && $days <= 7 && get_user_meta($user->ID, DF_Subscriptions::REMINDER_META, true) !== $expires_at) {
                $this->send_notice(
                    $user,
                    __('Your subscription expires soon', 'der-flugschreiber-subscriptions'),
                    sprintf(
                        /* translators: %s: expiration date. */
                        __('Your Der Flugschreiber subscription is active until %s. Use the subscription page to renew it.', 'der-flugschreiber-subscriptions'),
                        $expires_at
                    )
                );
                update_user_meta($user->ID, DF_Subscriptions::REMINDER_META, $expires_at);
            }

            if ($days < 0 && get_user_meta($user->ID, DF_Subscriptions::EXPIRED_NOTICE_META, true) !== $expires_at) {
                $this->send_notice(
                    $user,
                    __('Your subscription has expired', 'der-flugschreiber-subscriptions'),
                    sprintf(
                        /* translators: %s: expiration date. */
                        __('Your Der Flugschreiber subscription expired on %s. Use the subscription page to renew it.', 'der-flugschreiber-subscriptions'),
                        $expires_at
                    )
                );
                update_user_meta($user->ID, DF_Subscriptions::EXPIRED_NOTICE_META, $expires_at);
            }
        }
    }

    public function send_welcome_email($user_id)
    {
        if ('1' !== get_option(DF_Subscriptions::EMAIL_NOTICES_OPTION, '1')) {
            return;
        }

        $user = get_userdata($user_id);

        if (!$user) {
            return;
        }

        $this->send_notice(
            $user,
            __('Your Der Flugschreiber subscription account', 'der-flugschreiber-subscriptions'),
            sprintf(
                /* translators: %s: login URL. */
                __('Your subscriber account is ready. You can log in here: %s', 'der-flugschreiber-subscriptions'),
                get_option(DF_Subscriptions::LOGIN_URL_OPTION, wp_login_url())
            )
        );
    }

    private function send_notice($user, $subject, $message)
    {
        wp_mail($user->user_email, $subject, $message);
    }
}
