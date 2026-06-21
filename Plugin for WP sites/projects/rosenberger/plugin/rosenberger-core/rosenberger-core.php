<?php
/**
 * Plugin Name:       Rosenberger Core
 * Description:       Функционал сайта Rosenberger: настройки сайта (контакты), кастомные записи. Данные и логика — здесь, оформление — в теме.
 * Version:           0.1.0
 * Requires at least: 6.5
 * Requires PHP:      8.0
 * Author:            Web Agency
 * Text Domain:       rosenberger-core
 *
 * @package rosenberger-core
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/includes/settings.php';
require_once __DIR__ . '/includes/bindings.php';
require_once __DIR__ . '/includes/cpt.php';
require_once __DIR__ . '/includes/property-meta-box.php';
require_once __DIR__ . '/includes/svg-media.php';
