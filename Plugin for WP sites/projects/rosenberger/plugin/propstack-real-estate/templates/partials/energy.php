<?php
defined( 'ABSPATH' ) || exit;
/** @var array $prop */

$energy_data = array_filter( [
    'hwb'       => $prop['property_energy_hwb']       ?? null,
    'hwb_class' => $prop['property_energy_hwb_class'] ?? null,
    'fgee'      => $prop['property_energy_fgee']      ?? null,
    'fgee_class'=> $prop['property_energy_fgee_class']?? null,
    'heating'   => $prop['property_heating_type']     ?? null,
    'carrier'   => $prop['property_energy_carrier']   ?? null,
    'cert_date' => $prop['property_energy_cert_date'] ?? null,
    'cert_valid'=> $prop['property_energy_cert_valid']?? null,
] );

if ( empty( $energy_data ) ) {
    return;
}
?>
<section class="propstack-detail__section propstack-detail__energy">
    <h2><?php esc_html_e( 'Energieausweis', 'propstack-re' ); ?></h2>
    <dl class="propstack-facts-grid">
        <?php if ( ! empty( $energy_data['hwb'] ) ) : ?>
        <div class="propstack-facts-grid__item">
            <dt><?php esc_html_e( 'HWB', 'propstack-re' ); ?></dt>
            <dd>
                <?php echo esc_html( $energy_data['hwb'] ); ?> kWh/m²a
                <?php if ( ! empty( $energy_data['hwb_class'] ) ) : ?>
                <span class="propstack-energy-class propstack-energy-class--<?php echo esc_attr( strtolower( $energy_data['hwb_class'] ) ); ?>"><?php echo esc_html( $energy_data['hwb_class'] ); ?></span>
                <?php endif; ?>
            </dd>
        </div>
        <?php endif; ?>
        <?php if ( ! empty( $energy_data['fgee'] ) ) : ?>
        <div class="propstack-facts-grid__item">
            <dt><?php esc_html_e( 'fGEE', 'propstack-re' ); ?></dt>
            <dd>
                <?php echo esc_html( $energy_data['fgee'] ); ?>
                <?php if ( ! empty( $energy_data['fgee_class'] ) ) : ?>
                <span class="propstack-energy-class propstack-energy-class--<?php echo esc_attr( strtolower( $energy_data['fgee_class'] ) ); ?>"><?php echo esc_html( $energy_data['fgee_class'] ); ?></span>
                <?php endif; ?>
            </dd>
        </div>
        <?php endif; ?>
        <?php if ( ! empty( $energy_data['heating'] ) ) : ?>
        <div class="propstack-facts-grid__item">
            <dt><?php esc_html_e( 'Heizungsart', 'propstack-re' ); ?></dt>
            <dd><?php echo esc_html( $energy_data['heating'] ); ?></dd>
        </div>
        <?php endif; ?>
        <?php if ( ! empty( $energy_data['carrier'] ) ) : ?>
        <div class="propstack-facts-grid__item">
            <dt><?php esc_html_e( 'Energieträger', 'propstack-re' ); ?></dt>
            <dd><?php echo esc_html( $energy_data['carrier'] ); ?></dd>
        </div>
        <?php endif; ?>
        <?php if ( ! empty( $energy_data['cert_date'] ) ) : ?>
        <div class="propstack-facts-grid__item">
            <dt><?php esc_html_e( 'Ausstellungsdatum', 'propstack-re' ); ?></dt>
            <dd><?php echo esc_html( $energy_data['cert_date'] ); ?></dd>
        </div>
        <?php endif; ?>
        <?php if ( ! empty( $energy_data['cert_valid'] ) ) : ?>
        <div class="propstack-facts-grid__item">
            <dt><?php esc_html_e( 'Gültig bis', 'propstack-re' ); ?></dt>
            <dd><?php echo esc_html( $energy_data['cert_valid'] ); ?></dd>
        </div>
        <?php endif; ?>
    </dl>
</section>
