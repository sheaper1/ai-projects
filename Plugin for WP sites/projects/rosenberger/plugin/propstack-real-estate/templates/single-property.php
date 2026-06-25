<?php
defined( 'ABSPATH' ) || exit;
/** @var int $property_id */
$prop      = propstack_get_property( $property_id );
$post      = get_post( $property_id );
$title     = get_the_title( $property_id );
$permalink = get_permalink( $property_id );
$status_label = $prop['propstack_status'] ? propstack_get_status_label( $prop['propstack_status'] ) : '';
$marketing_label = $prop['property_marketing_type'] ? propstack_get_marketing_type_label( $prop['property_marketing_type'] ) : '';
$price_display   = $prop['property_price_display'] ?: __( 'Auf Anfrage', 'propstack-re' );
?>
<div class="propstack-detail" id="propstack-detail-<?php echo esc_attr( $property_id ); ?>">

    <!-- Hero -->
    <div class="propstack-detail__hero">
        <?php Propstack_RE_Template_Loader::get_partial( 'gallery.php', [ 'prop' => $prop, 'property_id' => $property_id ] ); ?>

        <div class="propstack-detail__hero-content">
            <?php if ( $status_label ) : ?>
            <span class="propstack-card__status propstack-card__status--<?php echo esc_attr( sanitize_key( $prop['propstack_status'] ) ); ?>">
                <?php echo esc_html( $status_label ); ?>
            </span>
            <?php endif; ?>
            <h1 class="propstack-detail__title"><?php echo esc_html( $title ); ?></h1>
            <?php if ( $prop['property_city'] ) : ?>
            <p class="propstack-detail__location">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>
                <?php
                $location_parts = array_filter( [
                    $prop['property_street'] ? trim( $prop['property_street'] . ' ' . ( $prop['property_house_number'] ?? '' ) ) : '',
                    $prop['property_city'] ?? '',
                    $prop['property_region'] ?? '',
                ] );
                echo esc_html( implode(', ', $location_parts) );
                ?>
            </p>
            <?php endif; ?>
            <div class="propstack-detail__hero-price"><?php echo esc_html( $price_display ); ?></div>

            <!-- Schnell-Kennzahlen -->
            <div class="propstack-detail__hero-facts">
                <?php if ( $prop['property_living_area'] ) : ?>
                <div class="propstack-detail__hero-fact">
                    <span class="propstack-detail__hero-fact-value"><?php echo esc_html( propstack_format_area( (float) $prop['property_living_area'] ) ); ?></span>
                    <span class="propstack-detail__hero-fact-label"><?php esc_html_e( 'Wohnfläche', 'propstack-re' ); ?></span>
                </div>
                <?php endif; ?>
                <?php if ( $prop['property_rooms'] ) : ?>
                <div class="propstack-detail__hero-fact">
                    <span class="propstack-detail__hero-fact-value"><?php echo esc_html( $prop['property_rooms'] ); ?></span>
                    <span class="propstack-detail__hero-fact-label"><?php esc_html_e( 'Zimmer', 'propstack-re' ); ?></span>
                </div>
                <?php endif; ?>
                <?php if ( $prop['property_plot_area'] ) : ?>
                <div class="propstack-detail__hero-fact">
                    <span class="propstack-detail__hero-fact-value"><?php echo esc_html( propstack_format_area( (float) $prop['property_plot_area'] ) ); ?></span>
                    <span class="propstack-detail__hero-fact-label"><?php esc_html_e( 'Grundstück', 'propstack-re' ); ?></span>
                </div>
                <?php endif; ?>
                <?php if ( $marketing_label ) : ?>
                <div class="propstack-detail__hero-fact">
                    <span class="propstack-detail__hero-fact-value"><?php echo esc_html( $marketing_label ); ?></span>
                    <span class="propstack-detail__hero-fact-label"><?php esc_html_e( 'Vermarktung', 'propstack-re' ); ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Inhalt -->
    <div class="propstack-detail__body">
        <div class="propstack-detail__main">

            <!-- Beschreibungen -->
            <?php Propstack_RE_Template_Loader::get_partial( 'description.php', [ 'prop' => $prop ] ); ?>

            <!-- Eckdaten -->
            <?php Propstack_RE_Template_Loader::get_partial( 'facts.php', [ 'prop' => $prop ] ); ?>

            <!-- Preise -->
            <?php Propstack_RE_Template_Loader::get_partial( 'price.php', [ 'prop' => $prop ] ); ?>

            <!-- Ausstattung -->
            <?php if ( ! empty( $prop['property_features'] ) ) : ?>
            <section class="propstack-detail__section propstack-detail__features">
                <h2><?php esc_html_e( 'Ausstattung', 'propstack-re' ); ?></h2>
                <ul class="propstack-features-list">
                    <?php foreach ( $prop['property_features'] as $key => $label ) : ?>
                    <li class="propstack-features-list__item">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="16" height="16"><polyline points="20 6 9 17 4 12"/></svg>
                        <?php echo esc_html( $label ); ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </section>
            <?php endif; ?>

            <!-- Energieausweis -->
            <?php Propstack_RE_Template_Loader::get_partial( 'energy.php', [ 'prop' => $prop ] ); ?>

            <!-- Adresse -->
            <?php Propstack_RE_Template_Loader::get_partial( 'address.php', [ 'prop' => $prop ] ); ?>

        </div>

        <aside class="propstack-detail__sidebar">
            <!-- Ansprechpartner -->
            <?php Propstack_RE_Template_Loader::get_partial( 'broker.php', [ 'prop' => $prop ] ); ?>

            <!-- Kontaktformular -->
            <?php if ( get_option( 'propstack_re_form_enabled', '1' ) ) :
                Propstack_RE_Template_Loader::get_template( 'contact-form.php', [ 'property_id' => $property_id ] );
            endif; ?>
        </aside>
    </div>

</div>
