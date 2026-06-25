<?php
defined( 'ABSPATH' ) || exit;
/** @var array $prop */

$prices = array_filter( [
    __( 'Kaufpreis',              'propstack-re' ) => $prop['property_price_brutto']      ? propstack_format_price( (float) $prop['property_price_brutto'] ) : null,
    __( 'Kaufpreis netto',        'propstack-re' ) => $prop['property_price_netto']       ? propstack_format_price( (float) $prop['property_price_netto'] )  : null,
    __( 'Preis pro m²',           'propstack-re' ) => $prop['property_price_per_sqm']     ? propstack_format_price( (float) $prop['property_price_per_sqm'] ) : null,
    __( 'Miete',                  'propstack-re' ) => $prop['property_rent_gross']        ? propstack_format_price( (float) $prop['property_rent_gross'] )    : null,
    __( 'Betriebskosten',         'propstack-re' ) => $prop['property_operating_costs']   ? propstack_format_price( (float) $prop['property_operating_costs'] ) : null,
    __( 'Heizkosten',             'propstack-re' ) => $prop['property_heating_costs']     ? propstack_format_price( (float) $prop['property_heating_costs'] )   : null,
    __( 'Monatliche Kosten',      'propstack-re' ) => $prop['property_monthly_costs']     ? propstack_format_price( (float) $prop['property_monthly_costs'] )   : null,
    __( 'Instandhaltungsrücklage','propstack-re' ) => $prop['property_reserve_fund']      ? propstack_format_price( (float) $prop['property_reserve_fund'] )    : null,
    __( 'Ablöse',                 'propstack-re' ) => $prop['property_deposit']           ? propstack_format_price( (float) $prop['property_deposit'] )         : null,
    __( 'Provision',              'propstack-re' ) => $prop['property_commission']        ?? null,
] );

// Provisionshinweis extra
$commission_note = $prop['property_commission_note'] ?? null;

if ( empty( $prices ) && ! $commission_note ) {
    return;
}
?>
<section class="propstack-detail__section propstack-detail__prices">
    <h2><?php esc_html_e( 'Preise & Kosten', 'propstack-re' ); ?></h2>
    <?php if ( ! empty( $prices ) ) : ?>
    <dl class="propstack-facts-grid">
        <?php foreach ( $prices as $label => $value ) : ?>
        <div class="propstack-facts-grid__item">
            <dt><?php echo esc_html( $label ); ?></dt>
            <dd><?php echo esc_html( (string) $value ); ?></dd>
        </div>
        <?php endforeach; ?>
    </dl>
    <?php endif; ?>
    <?php if ( $commission_note ) : ?>
    <p class="propstack-detail__commission-note"><?php echo esc_html( $commission_note ); ?></p>
    <?php endif; ?>
</section>
