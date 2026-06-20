<?php
/**
 * Тема Rosenberger — регистрация блоков проекта, шрифты.
 *
 * Модель «копия в проект»: блоки лежат внутри темы (blocks/), правятся здесь,
 * эталон в /library не трогаем.
 *
 * @package rosenberger
 */

defined( 'ABSPATH' ) || exit;

/**
 * Регистрируем все блоки темы из /blocks/<slug>/ (каждый со своим block.json).
 */
add_action(
	'init',
	function () {
		foreach ( glob( get_stylesheet_directory() . '/blocks/*', GLOB_ONLYDIR ) as $dir ) {
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
			$bg = get_stylesheet_directory_uri() . '/blocks/hero-cover/assets/hero-bg.webp';
			wp_add_inline_script(
				$handle,
				"window.libraryBlockDefaults=window.libraryBlockDefaults||{};window.libraryBlockDefaults['hero-cover']={bg:" . wp_json_encode( $bg ) . '};',
				'after'
			);
		}
	}
);

/**
 * Шрифты бренда + style.css темы (стили шапки/подвала).
 */
add_action(
	'wp_enqueue_scripts',
	function () {
		wp_enqueue_style(
			'rosenberger-fonts',
			'https://fonts.googleapis.com/css2?family=Nunito+Sans:ital,wght@0,400;1,300&family=Roboto+Flex:wght@400;500;600;800&display=swap',
			array(),
			null
		);
		wp_enqueue_style(
			'rosenberger-style',
			get_stylesheet_uri(),
			array(),
			wp_get_theme()->get( 'Version' )
		);
	}
);
