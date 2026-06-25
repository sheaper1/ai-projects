<?php
defined( 'ABSPATH' ) || exit;
/** @var array $prop */

if ( ! $prop['property_city'] && ! $prop['property_street'] ) {
    return;
}

$has_coords = $prop['property_lat'] && $prop['property_lng'];
?>
<section class="propstack-detail__section propstack-detail__address">
    <h2><?php esc_html_e( 'Lage', 'propstack-re' ); ?></h2>
    <address class="propstack-address">
        <?php if ( $prop['property_street'] ) : ?>
        <p><?php echo esc_html( trim( $prop['property_street'] . ' ' . ( $prop['property_house_number'] ?? '' ) ) ); ?></p>
        <?php endif; ?>
        <p>
            <?php if ( $prop['property_zip'] ) echo esc_html( $prop['property_zip'] ) . ' '; ?>
            <?php if ( $prop['property_city'] ) echo esc_html( $prop['property_city'] ); ?>
        </p>
        <?php if ( $prop['property_region'] ) : ?>
        <p><?php echo esc_html( $prop['property_region'] ); ?></p>
        <?php endif; ?>
        <?php if ( $prop['property_country'] ) : ?>
        <p><?php echo esc_html( $prop['property_country'] ); ?></p>
        <?php endif; ?>
    </address>

    <?php if ( $has_coords ) : ?>
    <div class="propstack-map"
         data-lat="<?php echo esc_attr( $prop['property_lat'] ); ?>"
         data-lng="<?php echo esc_attr( $prop['property_lng'] ); ?>"
         data-title="<?php echo esc_attr( $prop['property_city'] ?? '' ); ?>">
        <div class="propstack-map__placeholder">
            <p><?php esc_html_e( 'Karte wird geladen…', 'propstack-re' ); ?></p>
        </div>
    </div>
    <?php endif; ?>
</section>
