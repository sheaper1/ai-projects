<?php
defined( 'ABSPATH' ) || exit;
/** @var array $prop */

$sections = array_filter( [
    __( 'Objektbeschreibung', 'propstack-re' ) => $prop['property_long_description']      ?? null,
    __( 'Lagebeschreibung',   'propstack-re' ) => $prop['property_location_description']   ?? null,
    __( 'Ausstattung',        'propstack-re' ) => $prop['property_equipment_description']  ?? null,
    __( 'Sonstiges',          'propstack-re' ) => $prop['property_other_description']      ?? null,
] );

if ( empty( $sections ) ) {
    return;
}
?>
<?php foreach ( $sections as $heading => $text ) : ?>
<section class="propstack-detail__section propstack-detail__description">
    <h2><?php echo esc_html( $heading ); ?></h2>
    <div class="propstack-detail__text"><?php echo wp_kses_post( wpautop( $text ) ); ?></div>
</section>
<?php endforeach; ?>
