<?php
/**
 * Настройки сайта (единый источник контактных данных).
 * Блоки и шаблоны берут значения через rosenberger_contact( 'phone' ) и т.п.
 *
 * @package rosenberger-core
 */

defined( 'ABSPATH' ) || exit;

const ROSENBERGER_CONTACT_FIELDS = array(
	'phone'     => 'Телефон',
	'email'     => 'Email',
	'address'   => 'Адрес',
	'hours'     => 'Часы работы',
	'facebook'  => 'Facebook',
	'instagram' => 'Instagram',
);

/**
 * Хелпер для чтения контактного значения.
 *
 * @param string $key     Ключ поля.
 * @param string $default Значение по умолчанию.
 * @return string
 */
function rosenberger_contact( $key, $default = '' ) {
	$c = get_option( 'rosenberger_contacts', array() );
	return ( isset( $c[ $key ] ) && '' !== $c[ $key ] ) ? $c[ $key ] : $default;
}

add_action(
	'admin_init',
	function () {
		register_setting(
			'rosenberger_core_group',
			'rosenberger_contacts',
			array(
				'type'              => 'array',
				'sanitize_callback' => 'rosenberger_sanitize_contacts',
				'default'           => array(),
				'show_in_rest'      => false,
			)
		);
	}
);

/**
 * Санитизация контактов.
 *
 * @param array $value Сырые значения.
 * @return array
 */
function rosenberger_sanitize_contacts( $value ) {
	$out = array();
	foreach ( array_keys( ROSENBERGER_CONTACT_FIELDS ) as $key ) {
		$raw           = isset( $value[ $key ] ) ? $value[ $key ] : '';
		$out[ $key ]   = ( 'email' === $key ) ? sanitize_email( $raw ) : sanitize_text_field( $raw );
	}
	return $out;
}

add_action(
	'admin_menu',
	function () {
		add_menu_page(
			'Настройки сайта',
			'Настройки сайта',
			'manage_options',
			'rosenberger-settings',
			'rosenberger_render_settings_page',
			'dashicons-admin-generic',
			59
		);
	}
);

/**
 * Рендер страницы настроек.
 */
function rosenberger_render_settings_page() {
	$c = get_option( 'rosenberger_contacts', array() );
	?>
	<div class="wrap">
		<h1>Настройки сайта</h1>
		<form method="post" action="options.php">
			<?php settings_fields( 'rosenberger_core_group' ); ?>
			<table class="form-table">
				<?php foreach ( ROSENBERGER_CONTACT_FIELDS as $key => $label ) : ?>
					<tr>
						<th scope="row"><label for="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label></th>
						<td>
							<input
								type="<?php echo 'email' === $key ? 'email' : 'text'; ?>"
								id="<?php echo esc_attr( $key ); ?>"
								name="rosenberger_contacts[<?php echo esc_attr( $key ); ?>]"
								value="<?php echo esc_attr( $c[ $key ] ?? '' ); ?>"
								class="regular-text"
							>
						</td>
					</tr>
				<?php endforeach; ?>
			</table>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}
