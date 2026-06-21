<?php
/** Safe SVG uploads for trusted site administrators. @package rosenberger-core */
defined( 'ABSPATH' ) || exit;

add_filter(
	'upload_mimes',
	function ( $mimes ) {
		if ( current_user_can( 'manage_options' ) ) {
			$mimes['svg'] = 'image/svg+xml';
		}
		return $mimes;
	}
);

add_filter(
	'wp_check_filetype_and_ext',
	function ( $data, $file, $filename ) {
		if ( current_user_can( 'manage_options' ) && 'svg' === strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) ) ) {
			$data['ext']  = 'svg';
			$data['type'] = 'image/svg+xml';
		}
		return $data;
	},
	10,
	3
);

add_filter(
	'wp_handle_upload_prefilter',
	function ( $file ) {
		if ( 'image/svg+xml' !== ( $file['type'] ?? '' ) ) {
			return $file;
		}
		$svg = file_get_contents( $file['tmp_name'] );
		if ( false === $svg || preg_match( '/<(script|foreignObject)|\son\w+\s*=|javascript:/i', $svg ) ) {
			$file['error'] = 'SVG содержит запрещённые активные элементы.';
		}
		return $file;
	}
);
