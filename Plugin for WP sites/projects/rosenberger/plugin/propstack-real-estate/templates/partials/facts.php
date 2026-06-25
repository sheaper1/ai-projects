<?php
defined( 'ABSPATH' ) || exit;
/** @var array $prop */

$facts = array_filter( [
    __( 'Objektart',         'propstack-re' ) => $prop['property_type']       ?? null,
    __( 'Vermarktungsart',   'propstack-re' ) => $prop['property_marketing_type'] ? propstack_get_marketing_type_label( $prop['property_marketing_type'] ) : null,
    __( 'Wohnfläche',        'propstack-re' ) => $prop['property_living_area']  ? propstack_format_area( (float) $prop['property_living_area'] )  : null,
    __( 'Grundstücksfläche', 'propstack-re' ) => $prop['property_plot_area']    ? propstack_format_area( (float) $prop['property_plot_area'] )    : null,
    __( 'Nutzfläche',        'propstack-re' ) => $prop['property_usable_area']  ? propstack_format_area( (float) $prop['property_usable_area'] )  : null,
    __( 'Zimmer',            'propstack-re' ) => $prop['property_rooms']       ?? null,
    __( 'Schlafzimmer',      'propstack-re' ) => $prop['property_bedrooms']    ?? null,
    __( 'Badezimmer',        'propstack-re' ) => $prop['property_bathrooms']   ?? null,
    __( 'WC',                'propstack-re' ) => $prop['property_toilets']     ?? null,
    __( 'Stockwerk',         'propstack-re' ) => $prop['property_floor']       ?? null,
    __( 'Verfügbar ab',      'propstack-re' ) => $prop['property_available_from'] ?? null,
    __( 'Objektnummer',      'propstack-re' ) => $prop['property_object_number'] ?? null,
] );

if ( empty( $facts ) ) {
    return;
}
?>
<section class="propstack-detail__section propstack-detail__facts">
    <h2><?php esc_html_e( 'Eckdaten', 'propstack-re' ); ?></h2>
    <dl class="propstack-facts-grid">
        <?php foreach ( $facts as $label => $value ) : ?>
        <div class="propstack-facts-grid__item">
            <dt><?php echo esc_html( $label ); ?></dt>
            <dd><?php echo esc_html( (string) $value ); ?></dd>
        </div>
        <?php endforeach; ?>
    </dl>
</section>
