<?php
/**
 * Block Bindings: блоки шапки/подвала тянут значения из «Настроек сайта».
 * В разметке: metadata.bindings.<attr> = { source: 'rosenberger/setting', args: { key } }.
 * Если значение пустое — возвращаем null, и блок показывает свой плейсхолдер.
 *
 * @package rosenberger-core
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'init',
	function () {
		if ( ! function_exists( 'register_block_bindings_source' ) ) {
			return;
		}
		register_block_bindings_source(
			'rosenberger/setting',
			array(
				'label'              => 'Настройки сайта',
				'get_value_callback' => function ( $source_args ) {
					if ( empty( $source_args['key'] ) ) {
						return null;
					}
					$value = rosenberger_contact( $source_args['key'] );
					return '' !== $value ? $value : null;
				},
			)
		);
	}
);
