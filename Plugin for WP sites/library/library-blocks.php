<?php
/**
 * Plugin Name:       Library Blocks
 * Description:       Эталонная библиотека динамических блоков Gutenberg.
 * Version:           0.1.0
 * Requires at least: 6.5
 * Requires PHP:      8.0
 * Author:            Web Agency
 * Text Domain:       library
 *
 * @package library
 */

defined( 'ABSPATH' ) || exit;

/**
 * Регистрируем все блоки из папки /blocks/<slug>/ (каждый со своим block.json).
 * editorScript/style резолвятся штатно через plugins_url — без ручных хаков.
 */
add_action(
	'init',
	function () {
		foreach ( glob( __DIR__ . '/blocks/*', GLOB_ONLYDIR ) as $dir ) {
			if ( file_exists( $dir . '/block.json' ) ) {
				register_block_type( $dir );
			}
		}
	}
);

/**
 * Дефолтный фон Hero Cover для редактора (чтобы блок не выглядел пустым).
 */
add_action(
	'enqueue_block_editor_assets',
	function () {
		$handle = 'library-hero-cover-editor-script';
		if ( wp_script_is( $handle, 'registered' ) ) {
			$bg = plugins_url( 'blocks/hero-cover/assets/hero-bg.webp', __FILE__ );
			wp_add_inline_script(
				$handle,
				"window.libraryBlockDefaults=window.libraryBlockDefaults||{};window.libraryBlockDefaults['hero-cover']={bg:" . wp_json_encode( $bg ) . '};',
				'after'
			);
		}
	}
);
