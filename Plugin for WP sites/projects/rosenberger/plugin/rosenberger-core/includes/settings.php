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
		'Контакты' => array(
			'phone'   => 'Телефон',
			'email'   => 'Email',
			'address' => 'Адрес',
			'hours'   => 'Часы работы',
		),
		'Соцсети'  => array(
			'facebook'  => 'Facebook (ссылка)',
			'instagram' => 'Instagram (ссылка)',
		),
		'Шапка'    => array(
			'cta_text' => 'Кнопка в шапке — текст',
			'cta_url'  => 'Кнопка в шапке — ссылка',
		),
		'Формы'    => array(
			'form_email' => 'Email для заявок с форм',
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
			if ( 'email' === $key || 'form_email' === $key ) {
				$out[ $key ] = sanitize_email( $raw );
			} elseif ( in_array( $key, array( 'cta_url', 'facebook', 'instagram' ), true ) ) {
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
			'Настройки сайта',
			'Настройки сайта',
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
		<h1>Настройки сайта</h1>
		<p>Эти данные используются в шапке, подвале и блоках сайта.
			Меню редактируется отдельно:
			<a href="<?php echo esc_url( admin_url( 'nav-menus.php' ) ); ?>">Внешний вид → Меню</a>.</p>
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
