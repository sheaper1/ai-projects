<?php
/**
 * Единый источник схемы мета-полей объекта (CPT property).
 * Используется в трёх местах:
 *   - cpt.php            → register_post_meta (REST/Block Bindings)
 *   - property-meta-box.php → форма в админке
 *   - блоки property-*   → чтение через get_post_meta
 *
 * Группы нужны только для раскладки meta box; ключи полей плоские.
 *
 * @package rosenberger-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Поля, сгруппированные по секциям админки.
 * type: text | textarea | wysiwyg | select | gallery
 *
 * @return array<string, array<string, array>>
 */
function rosenberger_property_field_groups(): array {
	return array(
		'Identität & Preis' => array(
			'property_object_nr' => array( 'label' => 'Objekt-Nr.', 'type' => 'text', 'ph' => 'z. B. RB-2048' ),
			'property_address'   => array( 'label' => 'Adresse', 'type' => 'text', 'ph' => 'z. B. Höchsterstraße 24, 6850 Dornbirn · Vorarlberg, Österreich' ),
			'property_object_type' => array( 'label' => 'Objektart', 'type' => 'text', 'ph' => 'z. B. Eigentumswohnung' ),
			'property_category'  => array( 'label' => 'Kategorie', 'type' => 'text', 'ph' => 'z. B. Wohnen · Kauf' ),
			'property_status'    => array( 'label' => 'Status', 'type' => 'select', 'options' => array( 'Verfügbar', 'Reserviert', 'Verkauft' ), 'default' => 'Verfügbar' ),
			'property_price'     => array( 'label' => 'Kaufpreis', 'type' => 'text', 'ph' => 'z. B. 685.000 € oder Auf Anfrage' ),
			'property_price_sub' => array( 'label' => 'Preis-Zusatz', 'type' => 'text', 'ph' => 'z. B. 5.805 €/m² · zzgl. Kaufnebenkosten' ),
			'property_short_desc' => array( 'label' => 'Kurzbeschreibung', 'type' => 'textarea', 'ph' => 'Kurzer Teaser-Text für die Übersicht.' ),
		),
		'Flächen & Räume' => array(
			'property_area'        => array( 'label' => 'Wohnfläche', 'type' => 'text', 'ph' => 'z. B. 118 m²' ),
			'property_usable_area' => array( 'label' => 'Nutzfläche (Wohnen)', 'type' => 'text', 'ph' => 'z. B. 132 m²' ),
			'property_plot_area'   => array( 'label' => 'Grundstücksfläche', 'type' => 'text', 'ph' => 'z. B. 500 m² oder — (Wohnung)' ),
			'property_rooms'       => array( 'label' => 'Zimmer', 'type' => 'text', 'ph' => 'z. B. 4' ),
			'property_bedrooms'    => array( 'label' => 'Schlafzimmer', 'type' => 'text', 'ph' => 'z. B. 2' ),
			'property_bathrooms'   => array( 'label' => 'Badezimmer', 'type' => 'text', 'ph' => 'z. B. 2' ),
			'property_toilets'     => array( 'label' => 'Anzahl Toiletten', 'type' => 'text', 'ph' => 'z. B. 2' ),
			'property_floor'       => array( 'label' => 'Stock', 'type' => 'text', 'ph' => 'z. B. 2. Obergeschoss' ),
			'property_floors_total' => array( 'label' => 'Geschossanzahl', 'type' => 'text', 'ph' => 'z. B. 4' ),
			'property_year'        => array( 'label' => 'Baujahr', 'type' => 'text', 'ph' => 'z. B. 2019' ),
			'property_balcony_area' => array( 'label' => 'Balkon/Terrasse Fläche', 'type' => 'text', 'ph' => 'z. B. 14 m²' ),
			'property_balcony_orientation' => array( 'label' => 'Ausrichtung (Balkon/Terrasse)', 'type' => 'text', 'ph' => 'z. B. Süd' ),
			'property_loggia_area' => array( 'label' => 'Loggiafläche', 'type' => 'text', 'ph' => 'z. B. 8 m² oder ---' ),
			'property_loggia_count' => array( 'label' => 'Anzahl Loggia', 'type' => 'text', 'ph' => 'z. B. 0' ),
			'property_garden_area' => array( 'label' => 'Gartenfläche', 'type' => 'text', 'ph' => 'z. B. 200 m² oder ---' ),
			'property_orientation' => array( 'label' => 'Ausrichtung', 'type' => 'text', 'ph' => 'z. B. Süd-West' ),
			'property_flooring'    => array( 'label' => 'Bodenbelag', 'type' => 'text', 'ph' => 'z. B. Parkett, Fliesen' ),
		),
		'Akkordeons (frei editierbar)' => array(
			'property_acc_condition' => array( 'label' => 'Zustand & Zusatzinformationen', 'type' => 'wysiwyg' ),
			'property_acc_equipment' => array( 'label' => 'Ausstattung', 'type' => 'wysiwyg' ),
			'property_acc_layout'    => array( 'label' => 'Raumaufteilung', 'type' => 'wysiwyg' ),
			'property_acc_prices'    => array( 'label' => 'Preise & laufende Kosten', 'type' => 'wysiwyg' ),
			'property_acc_energy'    => array( 'label' => 'Energieausweis', 'type' => 'wysiwyg' ),
		),
		'Lage & Karte' => array(
			'property_lat' => array( 'label' => 'Breitengrad (Lat)', 'type' => 'text', 'ph' => 'z. B. 47.4125 (optional — sonst Geocoding per Adresse)' ),
			'property_lng' => array( 'label' => 'Längengrad (Lng)', 'type' => 'text', 'ph' => 'z. B. 9.7417 (optional)' ),
			'property_poi' => array( 'label' => 'In der Nähe (eine Zeile = «Name | Entfernung»)', 'type' => 'textarea', 'ph' => "Bahnhof Dornbirn | 8 min\nVolksschule | 5 min\nNahversorger | 3 min\nAutobahn A14 | 6 min" ),
		),
		'Galerie' => array(
			'property_gallery' => array( 'label' => 'Bildergalerie', 'type' => 'gallery' ),
		),
	);
}

/**
 * Плоский список всех полей (ключ → определение).
 *
 * @return array<string, array>
 */
function rosenberger_property_fields(): array {
	$flat = array();
	foreach ( rosenberger_property_field_groups() as $fields ) {
		foreach ( $fields as $key => $def ) {
			$flat[ $key ] = $def;
		}
	}
	return $flat;
}

/**
 * Санитайзер значения по типу поля.
 *
 * @param string $type Тип поля.
 * @param mixed  $value Сырое значение.
 * @return string
 */
function rosenberger_property_sanitize( string $type, $value ): string {
	switch ( $type ) {
		case 'wysiwyg':
			return wp_kses_post( $value );
		case 'textarea':
			return sanitize_textarea_field( $value );
		case 'gallery':
			// CSV из ID вложений.
			$ids = array_filter( array_map( 'absint', explode( ',', (string) $value ) ) );
			return implode( ',', $ids );
		default:
			return sanitize_text_field( $value );
	}
}
