<?php
defined( 'ABSPATH' ) || exit;

// Aktuelle Filterparameter
$current = [
    'marketing' => sanitize_text_field( $_GET['marketing'] ?? '' ),
    'type'      => sanitize_text_field( $_GET['type']      ?? '' ),
    'city'      => sanitize_text_field( $_GET['city']      ?? '' ),
    'region'    => sanitize_text_field( $_GET['region']    ?? '' ),
    'price_min' => absint( $_GET['price_min']              ?? 0 ),
    'price_max' => absint( $_GET['price_max']              ?? 0 ),
    'area_min'  => absint( $_GET['area_min']               ?? 0 ),
    'rooms_min' => absint( $_GET['rooms_min']              ?? 0 ),
    'sort'      => sanitize_key( $_GET['sort']             ?? '' ),
];

// Taxonomie-Optionen
$types      = get_terms( [ 'taxonomy' => 'property_type',          'hide_empty' => true ] );
$cities     = get_terms( [ 'taxonomy' => 'property_city',          'hide_empty' => true ] );
$regions    = get_terms( [ 'taxonomy' => 'property_region',        'hide_empty' => true ] );
$marketings = get_terms( [ 'taxonomy' => 'property_marketing_type','hide_empty' => true ] );

$base_url = get_post_type_archive_link( 'propstack_property' ) ?: strtok( (string) $_SERVER['REQUEST_URI'], '?' );
?>
<div class="propstack-filters" data-base-url="<?php echo esc_url( $base_url ); ?>">
    <form class="propstack-filters__form" method="get" action="<?php echo esc_url( $base_url ); ?>">

        <div class="propstack-filters__row">
            <?php if ( ! is_wp_error( $marketings ) && ! empty( $marketings ) ) : ?>
            <div class="propstack-filters__group">
                <label><?php esc_html_e( 'Kaufen / Mieten', 'propstack-re' ); ?></label>
                <select name="marketing">
                    <option value=""><?php esc_html_e( 'Alle', 'propstack-re' ); ?></option>
                    <?php foreach ( $marketings as $term ) : ?>
                    <option value="<?php echo esc_attr( $term->slug ); ?>" <?php selected( $current['marketing'], $term->slug ); ?>>
                        <?php echo esc_html( $term->name ); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <?php if ( ! is_wp_error( $types ) && ! empty( $types ) ) : ?>
            <div class="propstack-filters__group">
                <label><?php esc_html_e( 'Objektart', 'propstack-re' ); ?></label>
                <select name="type">
                    <option value=""><?php esc_html_e( 'Alle', 'propstack-re' ); ?></option>
                    <?php foreach ( $types as $term ) : ?>
                    <option value="<?php echo esc_attr( $term->slug ); ?>" <?php selected( $current['type'], $term->slug ); ?>>
                        <?php echo esc_html( $term->name ); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <?php if ( ! is_wp_error( $cities ) && ! empty( $cities ) ) : ?>
            <div class="propstack-filters__group">
                <label><?php esc_html_e( 'Ort', 'propstack-re' ); ?></label>
                <select name="city">
                    <option value=""><?php esc_html_e( 'Alle', 'propstack-re' ); ?></option>
                    <?php foreach ( $cities as $term ) : ?>
                    <option value="<?php echo esc_attr( $term->slug ); ?>" <?php selected( $current['city'], $term->slug ); ?>>
                        <?php echo esc_html( $term->name ); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <?php if ( ! is_wp_error( $regions ) && ! empty( $regions ) ) : ?>
            <div class="propstack-filters__group">
                <label><?php esc_html_e( 'Region', 'propstack-re' ); ?></label>
                <select name="region">
                    <option value=""><?php esc_html_e( 'Alle', 'propstack-re' ); ?></option>
                    <?php foreach ( $regions as $term ) : ?>
                    <option value="<?php echo esc_attr( $term->slug ); ?>" <?php selected( $current['region'], $term->slug ); ?>>
                        <?php echo esc_html( $term->name ); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
        </div>

        <div class="propstack-filters__row propstack-filters__row--secondary">
            <div class="propstack-filters__group propstack-filters__group--inline">
                <label><?php esc_html_e( 'Preis bis', 'propstack-re' ); ?></label>
                <input type="number" name="price_max" value="<?php echo esc_attr( $current['price_max'] ?: '' ); ?>" placeholder="z.B. 500000" min="0" step="10000">
            </div>
            <div class="propstack-filters__group propstack-filters__group--inline">
                <label><?php esc_html_e( 'Fläche ab m²', 'propstack-re' ); ?></label>
                <input type="number" name="area_min" value="<?php echo esc_attr( $current['area_min'] ?: '' ); ?>" placeholder="z.B. 60" min="0">
            </div>
            <div class="propstack-filters__group propstack-filters__group--inline">
                <label><?php esc_html_e( 'Zimmer ab', 'propstack-re' ); ?></label>
                <input type="number" name="rooms_min" value="<?php echo esc_attr( $current['rooms_min'] ?: '' ); ?>" placeholder="z.B. 2" min="0" step="0.5">
            </div>
            <div class="propstack-filters__group propstack-filters__group--inline">
                <label><?php esc_html_e( 'Sortierung', 'propstack-re' ); ?></label>
                <select name="sort">
                    <option value=""><?php esc_html_e( 'Neueste zuerst', 'propstack-re' ); ?></option>
                    <option value="price_asc"  <?php selected( $current['sort'], 'price_asc'  ); ?>><?php esc_html_e( 'Preis ↑', 'propstack-re' ); ?></option>
                    <option value="price_desc" <?php selected( $current['sort'], 'price_desc' ); ?>><?php esc_html_e( 'Preis ↓', 'propstack-re' ); ?></option>
                    <option value="area_desc"  <?php selected( $current['sort'], 'area_desc'  ); ?>><?php esc_html_e( 'Fläche ↓', 'propstack-re' ); ?></option>
                </select>
            </div>
        </div>

        <div class="propstack-filters__actions">
            <button type="submit" class="propstack-btn propstack-btn--primary"><?php esc_html_e( 'Filtern', 'propstack-re' ); ?></button>
            <?php if ( array_filter( $current ) ) : ?>
            <a href="<?php echo esc_url( $base_url ); ?>" class="propstack-btn propstack-btn--outline"><?php esc_html_e( 'Filter zurücksetzen', 'propstack-re' ); ?></a>
            <?php endif; ?>
        </div>
    </form>
</div>
