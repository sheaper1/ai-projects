<?php
/**
 * Мост лидов: WPForms (форма «Kontakt») → Propstack.
 *
 * При успешной отправке контактной формы создаём в Propstack контакт + активность
 * через `Propstack_RE_Lead_Service` (он сам ищет/создаёт контакт и пишет заметку).
 * Тело плагина не трогаем — это проектный glue-код в теме.
 *
 * Привязка к конкретному объекту не делается: /kontakt/ — общая форма без property.
 * Чтобы привязывать лид к объекту, нужно прокинуть property в форму (hidden-поле).
 *
 * @package rosenberger
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'wpforms_process_complete',
	function ( $fields, $entry, $form_data ) {
		// Только форма «Kontakt» (id 180; плюс страховка по названию).
		$title = strtolower( (string) ( $form_data['settings']['form_title'] ?? '' ) );
		if ( 180 !== (int) ( $form_data['id'] ?? 0 ) && false === strpos( $title, 'kontakt' ) ) {
			return;
		}
		if ( ! class_exists( 'Propstack_RE_Lead_Service' ) ) {
			return;
		}

		$val = static function ( $id ) use ( $fields ) {
			return isset( $fields[ $id ]['value'] ) ? trim( (string) $fields[ $id ]['value'] ) : '';
		};

		$name    = $val( 1 );
		$parts   = preg_split( '/\s+/', $name, 2 );
		$subject = $val( 4 );
		$message = $val( 5 );
		if ( '' !== $subject ) {
			$message = '[' . $subject . '] ' . $message;
		}

		$data = array(
			'first_name'  => $parts[0] ?? '',
			'last_name'   => $parts[1] ?? '',
			'email'       => $val( 2 ),
			'phone'       => $val( 3 ),
			'message'     => $message,
			'_source_url' => home_url( '/kontakt/' ),
		);

		if ( '' === $data['email'] ) {
			return;
		}

		// Отправку в Propstack делаем в фоне (cron), чтобы не блокировать ответ
		// формы синхронными API-вызовами (find/create contact, activity, link).
		wp_schedule_single_event( time() + 1, 'rosenberger_propstack_send_lead', array( $data ) );
	},
	20,
	3
);

/**
 * Фоновая отправка лида в Propstack.
 */
add_action(
	'rosenberger_propstack_send_lead',
	function ( $data ) {
		if ( ! is_array( $data ) || ! class_exists( 'Propstack_RE_Lead_Service' ) ) {
			return;
		}
		try {
			$service = new Propstack_RE_Lead_Service();
			$service->send_lead( $data, null );
		} catch ( \Throwable $e ) {
			if ( class_exists( 'Propstack_RE_Logger' ) ) {
				Propstack_RE_Logger::error( 'WPForms→Propstack Lead Fehler: ' . $e->getMessage(), 'lead' );
			}
		}
	}
);
