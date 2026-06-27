<?php
/** Dynamic render for Contact Section. @package library */
defined( 'ABSPATH' ) || exit;

$a = wp_parse_args( $attributes ?? array(), array(
	'headingItalic' => '',
	'headingRest'   => '',
	'lead'          => '',
	'cardTitle'     => 'Kontaktinformationen',
	'formId'        => 0,
	'lat'           => 47.2466,
	'lng'           => 9.5851,
) );

// WPForms-форма: явный formId → форма по slug «kontakt» → первая существующая форма.
$form_id = (int) $a['formId'];
if ( ! $form_id ) {
	$f = get_posts( array( 'post_type' => 'wpforms', 'name' => 'kontakt', 'posts_per_page' => 1, 'post_status' => 'publish' ) );
	if ( empty( $f ) ) {
		$f = get_posts( array( 'post_type' => 'wpforms', 'posts_per_page' => 1, 'post_status' => 'publish', 'orderby' => 'date', 'order' => 'ASC' ) );
	}
	if ( ! empty( $f ) ) {
		$form_id = $f[0]->ID;
	}
}

// Маппинг slug → WPForms field-ID (по типам поля) — для JS-моста к скрытой форме.
$field_map = array();
$choices   = array();
if ( $form_id ) {
	$fp = get_post( $form_id );
	$fd = $fp ? ( function_exists( 'wpforms_decode' ) ? wpforms_decode( $fp->post_content ) : json_decode( $fp->post_content, true ) ) : null;
	if ( ! empty( $fd['fields'] ) ) {
		foreach ( $fd['fields'] as $fid => $field ) {
			$type = isset( $field['type'] ) ? $field['type'] : '';
			$slug = '';
			if ( 'name' === $type ) { $slug = 'name'; }
			elseif ( 'email' === $type ) { $slug = 'email'; }
			elseif ( 'phone' === $type ) { $slug = 'phone'; }
			elseif ( 'select' === $type ) { $slug = 'subject'; }
			elseif ( 'textarea' === $type ) { $slug = 'message'; }
			if ( $slug ) {
				$field_map[ $slug ] = (int) $fid;
				if ( 'subject' === $slug && ! empty( $field['choices'] ) ) {
					foreach ( $field['choices'] as $c ) {
						if ( ! empty( $c['label'] ) ) {
							$choices[] = $c['label'];
						}
					}
				}
			}
		}
	}
}
if ( empty( $choices ) ) {
	$choices = array( 'Real estate sales', 'Immobilienbewertung', 'Vermietung', 'Sonstiges' );
}

// Контактные данные из «Настроек сайта» (плагин), c фолбэком на дефолт из подвала.
$phone   = function_exists( 'rosenberger_contact' ) ? rosenberger_contact( 'phone' ) : '';
$email   = function_exists( 'rosenberger_contact' ) ? rosenberger_contact( 'email' ) : '';
$address = function_exists( 'rosenberger_contact' ) ? rosenberger_contact( 'address' ) : '';
$phone   = $phone ? $phone : '+43 699 11 777 505';
$email   = $email ? $email : 'office@rosenberger.immo';
$address = $address ? $address : 'ROSENBERGER Immobilien GmbH, Drevesstraße 2/1, 6800 Feldkirch';

$has_form = $form_id && function_exists( 'wpforms' ) && ! empty( $field_map );
?>
<section <?php echo get_block_wrapper_attributes( array( 'class' => 'contact-section' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="contact-section__inner">

		<div class="contact-section__form-col">
			<div class="contact-section__head">
				<h1 class="contact-section__heading"><em><?php echo esc_html( $a['headingItalic'] ); ?></em><?php echo esc_html( $a['headingRest'] ); ?></h1>
				<?php if ( $a['lead'] ) : ?>
					<p class="contact-section__lead"><?php echo wp_kses_post( $a['lead'] ); ?></p>
				<?php endif; ?>
			</div>

			<form class="contact-section__form"
				novalidate
				data-contact-form
				data-wpforms-id="<?php echo esc_attr( $form_id ); ?>"
				data-wpforms-fields="<?php echo esc_attr( wp_json_encode( $field_map ) ); ?>"
			>
				<div class="cs-field">
					<label class="cs-field__label" for="cs-name">Name</label>
					<input class="cs-field__input" id="cs-name" type="text" data-cs-field="name" placeholder="Ihr Name" required />
				</div>
				<div class="cs-field">
					<label class="cs-field__label" for="cs-email">E-Mail</label>
					<input class="cs-field__input" id="cs-email" type="email" data-cs-field="email" placeholder="Ihre E-Mail-Adresse" required />
				</div>
				<div class="cs-field">
					<label class="cs-field__label" for="cs-phone">Telefon</label>
					<div class="cs-field__phone">
						<span class="cs-field__flag" aria-hidden="true">🇦🇹<svg width="8" height="5" viewBox="0 0 8 5" fill="none"><path d="M1 1l3 3 3-3" stroke="#142335" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
						<input class="cs-field__input cs-field__input--phone" id="cs-phone" type="tel" data-cs-field="phone" placeholder="+43 660 1234567" />
					</div>
				</div>
				<div class="cs-field">
					<label class="cs-field__label" for="cs-subject">Betreff der Anfrage</label>
					<div class="cs-field__select">
						<select class="cs-field__input" id="cs-subject" data-cs-field="subject">
							<?php foreach ( $choices as $opt ) : ?>
								<option value="<?php echo esc_attr( $opt ); ?>"><?php echo esc_html( $opt ); ?></option>
							<?php endforeach; ?>
						</select>
						<svg class="cs-field__caret" width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M6 8l4 4 4-4" stroke="#142335" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
					</div>
				</div>
				<div class="cs-field">
					<label class="cs-field__label" for="cs-message">Nachricht</label>
					<textarea class="cs-field__input cs-field__textarea" id="cs-message" data-cs-field="message" placeholder="Worum geht es bei Ihrem Projekt?"></textarea>
				</div>

				<button class="cs-field__submit" type="submit"<?php echo $has_form ? '' : ' disabled'; ?>>JETZT ANFRAGEN</button>
				<p class="cs-field__error" data-cs-error hidden>Übermittlung fehlgeschlagen – bitte erneut versuchen.</p>
				<?php if ( ! $has_form ) : ?>
					<p class="cs-field__error" data-cs-static>Das Kontaktformular ist noch nicht eingerichtet.</p>
				<?php endif; ?>
			</form>

			<?php if ( $has_form ) : ?>
				<div class="contact-section__wpf-hidden" aria-hidden="true">
					<?php echo do_shortcode( '[wpforms id="' . $form_id . '" title="false" description="false"]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
			<?php endif; ?>
		</div>

		<div class="contact-section__map-col">
			<div
				class="contact-section__map"
				data-contact-map
				data-lat="<?php echo esc_attr( $a['lat'] ); ?>"
				data-lng="<?php echo esc_attr( $a['lng'] ); ?>"
				data-address="<?php echo esc_attr( $address ); ?>"
				role="img"
				aria-label="Karte: <?php echo esc_attr( $address ); ?>"
			></div>
			<div class="contact-section__card">
				<p class="contact-section__card-title"><?php echo esc_html( $a['cardTitle'] ); ?></p>
				<div class="contact-section__card-body">
					<p>Telefon: <?php echo esc_html( $phone ); ?></p>
					<p>Email: <?php echo esc_html( $email ); ?></p>
					<p><?php echo esc_html( $address ); ?></p>
				</div>
			</div>
		</div>

	</div>
</section>
