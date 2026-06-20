<?php
/**
 * Кастомные записи проекта. Пример: «Объекты» (недвижимость).
 * CPT держим в плагине, чтобы данные пережили смену/обновление темы.
 *
 * @package rosenberger-core
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'init',
	function () {
		register_post_type(
			'property',
			array(
				'labels'       => array(
					'name'          => 'Объекты',
					'singular_name' => 'Объект',
					'add_new_item'  => 'Добавить объект',
					'edit_item'     => 'Редактировать объект',
					'search_items'  => 'Искать объекты',
				),
				'public'       => true,
				'has_archive'  => true,
				'menu_icon'    => 'dashicons-building',
				'supports'     => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
				'show_in_rest' => true,
				'rewrite'      => array( 'slug' => 'objekte' ),
			)
		);
	}
);
