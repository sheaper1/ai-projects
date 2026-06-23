<?php
/**
 * Единая страница «Настройки сайта» — то, что редактирует клиент.
 * Один источник данных; шапка/подвал/блоки тянут значения через Block Bindings
 * (см. bindings.php) и хелпер rosenberger_contact().
 *
 * @package rosenberger-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Поля, сгруппированные по секциям. Всё хранится в одной опции rosenberger_contacts.
 */
function rosenberger_setting_groups() {
	return array(
		'Contacts' => array(
			'phone'   => 'Phone',
			'email'   => 'Email',
			'address' => 'Address',
			'hours'   => 'Opening hours',
		),
		'Makler / Ansprechpartner' => array(
			'agent_name'     => 'Name',
			'agent_role'     => 'Funktion (z. B. Ihr Ansprechpartner)',
			'agent_phone'    => 'Telefon',
			'agent_email'    => 'Email',
			'agent_portrait' => 'Portrait (Bild-URL)',
		),
		'Social media' => array(
			'facebook'  => 'Facebook (URL)',
			'instagram' => 'Instagram (URL)',
		),
		'Header'   => array(
			'cta_text' => 'Header button — text',
			'cta_url'  => 'Header button — URL',
		),
		'Forms'    => array(
			'form_email' => 'Email for form submissions',
		),
	);
}

/**
 * Хелпер для чтения значения.
 *
 * @param string $key     Ключ.
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
 * Санитизация всех полей.
 *
 * @param array $value Сырые значения.
 * @return array
 */
function rosenberger_sanitize_contacts( $value ) {
	$out = array();
	foreach ( rosenberger_setting_groups() as $fields ) {
		foreach ( array_keys( $fields ) as $key ) {
			$raw = isset( $value[ $key ] ) ? $value[ $key ] : '';
			if ( 'email' === $key || 'form_email' === $key || 'agent_email' === $key ) {
				$out[ $key ] = sanitize_email( $raw );
			} elseif ( in_array( $key, array( 'cta_url', 'facebook', 'instagram', 'agent_portrait' ), true ) ) {
				$out[ $key ] = esc_url_raw( $raw );
			} else {
				$out[ $key ] = sanitize_text_field( $raw );
			}
		}
	}
	return $out;
}

add_action(
	'admin_menu',
	function () {
		add_menu_page(
			'Site Settings',
			'Site Settings',
			'manage_options',
			'rosenberger-settings',
			'rosenberger_render_settings_page',
			'dashicons-admin-settings',
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
		<h1>Site Settings</h1>
		<p>This data is used in the header, footer, and site blocks.
			Menus are edited separately:
			<a href="<?php echo esc_url( admin_url( 'nav-menus.php' ) ); ?>">Appearance → Menus</a>.</p>
		<form method="post" action="options.php">
			<?php settings_fields( 'rosenberger_core_group' ); ?>
			<?php foreach ( rosenberger_setting_groups() as $section => $fields ) : ?>
				<h2><?php echo esc_html( $section ); ?></h2>
				<table class="form-table">
					<?php foreach ( $fields as $key => $label ) : ?>
						<tr>
							<th scope="row"><label for="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label></th>
							<td>
								<input
									type="<?php echo ( false !== strpos( $key, 'email' ) ) ? 'email' : 'text'; ?>"
									id="<?php echo esc_attr( $key ); ?>"
									name="rosenberger_contacts[<?php echo esc_attr( $key ); ?>]"
									value="<?php echo esc_attr( $c[ $key ] ?? '' ); ?>"
									class="regular-text"
								>
							</td>
						</tr>
					<?php endforeach; ?>
				</table>
			<?php endforeach; ?>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}
