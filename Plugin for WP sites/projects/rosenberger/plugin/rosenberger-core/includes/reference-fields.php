<?php
/**
 * Единый источник схемы мета-полей референса (CPT reference).
 *
 * Референс — завершённая сделка (проданный объект) + отзыв клиента. Чтобы
 * переиспользовать блоки property-hero / property-stats / property-gallery
 * (они читают мету `property_*` текущей записи через context), референс хранит
 * ТЕ ЖЕ ключи `property_*` для общих полей, плюс собственные `reference_*` для
 * отзыва.
 *
 * Используется в:
 *   - reference-cpt.php       → register_post_meta (REST/Block Bindings)
 *   - reference-meta-box.php  → форма в админке
 *   - блоки property-* / reference-* → чтение через get_post_meta
 *
 * @package rosenberger-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Поля, сгруппированные по секциям админки.
 * type: text | textarea | select | gallery
 *
 * @return array<string, array<string, array>>
 */
function rosenberger_reference_field_groups(): array {
	return array(
		'Identität & Preis' => array(
			'property_object_type' => array( 'label' => 'Objektart', 'type' => 'text', 'ph' => 'z. B. Eigentumswohnung' ),
			'property_status'      => array( 'label' => 'Status', 'type' => 'select', 'options' => array( 'Verkauft', 'Vermietet', 'Verfügbar', 'Reserviert' ), 'default' => 'Verkauft' ),
			'property_address'     => array( 'label' => 'Adresse / Lage', 'type' => 'text', 'ph' => 'z. B. Höchsterstraße 24, 6850 Dornbirn' ),
			'property_price'       => array( 'label' => 'Kaufpreis', 'type' => 'text', 'ph' => 'z. B. 685.000 € oder Auf Anfrage' ),
			'property_price_sub'   => array( 'label' => 'Preis-Zusatz', 'type' => 'text', 'ph' => 'z. B. 5.805 €/m² · zzgl. Kaufnebenkosten' ),
			'property_short_desc'  => array( 'label' => 'Kurzbeschreibung (Karten-Teaser)', 'type' => 'textarea', 'ph' => 'Kurzer Text für die Übersicht / Karte.' ),
		),
		'Kennzahlen' => array(
			'property_area'      => array( 'label' => 'Wohnfläche', 'type' => 'text', 'ph' => 'z. B. 118 m²' ),
			'property_rooms'     => array( 'label' => 'Zimmer', 'type' => 'text', 'ph' => 'z. B. 4' ),
			'property_bedrooms'  => array( 'label' => 'Schlafzimmer', 'type' => 'text', 'ph' => 'z. B. 2' ),
			'property_bathrooms' => array( 'label' => 'Badezimmer', 'type' => 'text', 'ph' => 'z. B. 2' ),
			'property_floor'     => array( 'label' => 'Stock', 'type' => 'text', 'ph' => 'z. B. 2. OG' ),
			'property_year'      => array( 'label' => 'Baujahr', 'type' => 'text', 'ph' => 'z. B. 2019' ),
			'property_plot_area' => array( 'label' => 'Grundstücksfläche (für Karte)', 'type' => 'text', 'ph' => 'z. B. ca. 130 m² oder —' ),
		),
		'Kundenstimme (Testimonial)' => array(
			'reference_quote'    => array( 'label' => 'Zitat des Kunden', 'type' => 'textarea', 'ph' => 'Was der Kunde über die Zusammenarbeit sagt.' ),
			'reference_author'   => array( 'label' => 'Name', 'type' => 'text', 'ph' => 'z. B. Karsten G.' ),
			'reference_location' => array( 'label' => 'Ort / Kanton', 'type' => 'text', 'ph' => 'z. B. Dornbirn' ),
			'reference_rating'   => array( 'label' => 'Bewertung (Sterne)', 'type' => 'select', 'options' => array( '5', '4', '3', '2', '1' ), 'default' => '5' ),
		),
		'Galerie' => array(
			'property_gallery' => array( 'label' => 'Bildergalerie (Hero + Galerie)', 'type' => 'gallery' ),
		),
	);
}

/**
 * Плоский список всех полей (ключ → определение).
 *
 * @return array<string, array>
 */
function rosenberger_reference_fields(): array {
	$flat = array();
	foreach ( rosenberger_reference_field_groups() as $fields ) {
		foreach ( $fields as $key => $def ) {
			$flat[ $key ] = $def;
		}
	}
	return $flat;
}
