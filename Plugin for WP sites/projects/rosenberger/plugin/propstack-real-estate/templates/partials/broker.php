<?php
defined( 'ABSPATH' ) || exit;
/** @var array $prop */

if ( ! $prop['property_contact_name'] && ! $prop['property_contact_email'] ) {
    return;
}
?>
<div class="propstack-broker">
    <?php if ( $prop['property_contact_avatar'] ) : ?>
    <img class="propstack-broker__avatar" src="<?php echo esc_url( $prop['property_contact_avatar'] ); ?>" alt="<?php echo esc_attr( $prop['property_contact_name'] ); ?>" loading="lazy">
    <?php endif; ?>
    <div class="propstack-broker__info">
        <?php if ( $prop['property_contact_name'] ) : ?>
        <strong class="propstack-broker__name"><?php echo esc_html( $prop['property_contact_name'] ); ?></strong>
        <?php endif; ?>
        <?php if ( $prop['property_contact_phone'] ) : ?>
        <a class="propstack-broker__phone" href="tel:<?php echo esc_attr( preg_replace( '/[^+\d]/', '', $prop['property_contact_phone'] ) ); ?>">
            <?php echo esc_html( $prop['property_contact_phone'] ); ?>
        </a>
        <?php endif; ?>
        <?php if ( $prop['property_contact_email'] ) : ?>
        <a class="propstack-broker__email" href="mailto:<?php echo esc_attr( $prop['property_contact_email'] ); ?>">
            <?php echo esc_html( $prop['property_contact_email'] ); ?>
        </a>
        <?php endif; ?>
    </div>
</div>
