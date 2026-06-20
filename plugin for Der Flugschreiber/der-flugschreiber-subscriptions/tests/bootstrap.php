<?php

define('ABSPATH', dirname(__DIR__) . '/');

class DF_Subscriptions
{
    const ROLE = 'df_subscriber';
    const EXPIRES_META = 'df_subscription_expires_at';
    const STATUS_META = 'df_subscription_status';
}

$GLOBALS['df_test_users'] = array();
$GLOBALS['df_test_meta'] = array();
$GLOBALS['df_test_now'] = new DateTimeImmutable('2026-06-11 12:00:00', new DateTimeZone('Europe/Kyiv'));

function current_user_can()
{
    return false;
}

function is_user_logged_in()
{
    return false;
}

function get_current_user_id()
{
    return 0;
}

function get_userdata($user_id)
{
    return isset($GLOBALS['df_test_users'][$user_id]) ? $GLOBALS['df_test_users'][$user_id] : false;
}

function get_user_meta($user_id, $key)
{
    return isset($GLOBALS['df_test_meta'][$user_id][$key]) ? $GLOBALS['df_test_meta'][$user_id][$key] : '';
}

function wp_timezone()
{
    return new DateTimeZone('Europe/Kyiv');
}

function current_datetime()
{
    return $GLOBALS['df_test_now'];
}

function sanitize_key($value)
{
    return preg_replace('/[^a-z0-9_\-]/', '', strtolower((string) $value));
}

function __($text)
{
    return $text;
}

require_once dirname(__DIR__) . '/includes/class-df-subscriptions-access.php';
