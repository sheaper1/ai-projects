<?php
/** Dynamic render for Contact Section. @package library */
defined( 'ABSPATH' ) || exit;

$a = wp_parse_args( $attributes ?? array(), array(
	'headingItalic' => '',
	'headingRest'   => '',
	'lead'          => '',
	'cardTitle'     => 'Contact Information',
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

// Контактные данные из «Настроек сайта» (плагин), c фолбэком на дефолт из подвала.
$contact = function_exists( 'rosenberger_contact' ) ? 'rosenberger_contact' : null;
$phone   = $contact ? rosenberger_contact( 'phone' ) : '';
$email   = $contact ? rosenberger_contact( 'email' ) : '';
$address = $contact ? rosenberger_contact( 'address' ) : '';
$phone   = $phone ? $phone : '+43 699 11 777 505';
$email   = $email ? $email : 'office@rosenberger.immo';
$address = $address ? $address : 'ROSENBERGER Immobilien GmbH, Drevesstraße 2/1, 6800 Feldkirch';
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
			<div class="contact-section__form">
				<?php
				if ( $form_id && function_exists( 'wpforms' ) ) {
					echo do_shortcode( '[wpforms id="' . $form_id . '" title="false" description="false"]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				} else {
					echo '<p class="contact-section__form-missing">Das Kontaktformular ist noch nicht eingerichtet.</p>';
				}
				?>
			</div>
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
