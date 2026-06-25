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

require_once get_theme_file_path( 'inc/blog.php' );

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
		// Шрифты бренда — локально (DSGVO: без передачи IP в Google CDN).
		// Файлы и fonts.css генерятся scripts/selfhost-fonts.mjs.
		wp_enqueue_style(
			'rosenberger-fonts',
			get_theme_file_uri( '/assets/css/fonts.css' ),
			array(),
			(string) filemtime( get_stylesheet_directory() . '/assets/css/fonts.css' )
		);
		wp_enqueue_style(
			'rosenberger-style',
			get_stylesheet_uri(),
			array(),
			(string) filemtime( get_stylesheet_directory() . '/style.css' )
		);
		wp_enqueue_script(
			'rosenberger-header',
			get_theme_file_uri( '/assets/header.js' ),
			array(),
			(string) filemtime( get_stylesheet_directory() . '/assets/header.js' ),
			true
		);
		// Общий движок каруселей (нужен блокам property-hero / property-gallery).
		wp_enqueue_script(
			'rb-carousel',
			get_theme_file_uri( '/assets/js/rb-carousel.js' ),
			array(),
			(string) filemtime( get_stylesheet_directory() . '/assets/js/rb-carousel.js' ),
			true
		);
		// Универсальный scroll-reveal для всех блоков библиотеки.
		// В <head> (не в футере), чтобы скрытое состояние применилось до отрисовки.
		wp_enqueue_style(
			'rb-reveal',
			get_theme_file_uri( '/assets/css/reveal.css' ),
			array(),
			(string) filemtime( get_stylesheet_directory() . '/assets/css/reveal.css' )
		);
		wp_enqueue_script(
			'rb-reveal',
			get_theme_file_uri( '/assets/js/reveal.js' ),
			array(),
			(string) filemtime( get_stylesheet_directory() . '/assets/js/reveal.js' ),
			false
		);
	}
);

/**
 * Favicon: один SVG-знак бренда, цвет переключается по теме ОС
 * (тёмно-синий на светлой, белый на тёмной — через prefers-color-scheme
 * внутри самого SVG). Перебивает дефолтную иконку WP.
 */
add_action(
	'wp_head',
	function () {
		$href = get_theme_file_uri( '/assets/favicon.svg' )
			. '?v=' . filemtime( get_stylesheet_directory() . '/assets/favicon.svg' );
		printf(
			'<link rel="icon" href="%s" type="image/svg+xml" sizes="any">' . "\n",
			esc_url( $href )
		);
	},
	5
);

/**
 * Класс на body для страниц со светлым Hero (single object) — шапка с тёмным меню.
 */
add_filter(
	'body_class',
	function ( $classes ) {
		if ( is_singular( 'property' ) || is_singular( 'reference' ) || is_post_type_archive( 'reference' )
				|| is_home() || is_singular( 'post' ) || is_singular( 'region' )
				|| is_404() || is_page( 'danke' ) || is_page( 'kontakt' ) ) {
			$classes[] = 'has-light-hero';
		}
		return $classes;
	}
);
