<?php
/**
 * Тема Rosenberger — подключение шрифтов и поддержка тем.
 *
 * @package rosenberger
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'wp_enqueue_scripts',
	function () {
		wp_enqueue_style(
			'rosenberger-fonts',
			'https://fonts.googleapis.com/css2?family=Nunito+Sans:ital,wght@0,400;1,300&family=Roboto+Flex:wght@400;500;600;800&display=swap',
			array(),
			null
		);
	}
);
