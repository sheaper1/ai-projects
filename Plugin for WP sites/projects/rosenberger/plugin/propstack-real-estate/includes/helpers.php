<?php
defined( 'ABSPATH' ) || exit;

function propstack_format_price( float|int|null $price, string $currency = '€' ): string {
    if ( null === $price || $price <= 0 ) {
        return __( 'Auf Anfrage', 'propstack-re' );
    }
    return number_format( (float) $price, 0, ',', '.' ) . ' ' . $currency;
}

function propstack_format_area( float|int|null $area, string $unit = 'm²' ): string {
    if ( null === $area || $area <= 0 ) {
        return '';
    }
    return number_format( (float) $area, 2, ',', '.' ) . ' ' . $unit;
}

function propstack_get_status_label( string|int $status ): string {
    $labels = [
        'vermarktung' => __( 'Verfügbar',   'propstack-re' ),
        'reserviert'  => __( 'Reserviert',  'propstack-re' ),
        'akquise'     => __( 'Akquise',     'propstack-re' ),
        'vorbereitung'=> __( 'Vorbereitung','propstack-re' ),
        'abgeschlossen'=> __( 'Verkauft',   'propstack-re' ),
    ];
    $key = strtolower( (string) $status );
    return $labels[ $key ] ?? ucfirst( $key );
}

function propstack_get_marketing_type_label( string $type ): string {
    return match( strtolower( $type ) ) {
        'buy', 'kauf', 'purchase' => __( 'Kaufen', 'propstack-re' ),
        'rent', 'miete'           => __( 'Mieten', 'propstack-re' ),
        default                   => $type,
    };
}

function propstack_get_property( int $post_id ): array {
    $meta_keys = [
        '_propstack_id', '_propstack_status', '_propstack_last_sync',
        '_property_price', '_property_price_display', '_property_price_on_request',
        '_property_living_area', '_property_plot_area', '_property_rooms',
        '_property_bedrooms', '_property_bathrooms', '_property_city',
        '_property_zip', '_property_region', '_property_street',
        '_property_house_number', '_property_lat', '_property_lng',
        '_property_type', '_property_marketing_type', '_property_object_number',
        '_property_gallery', '_property_featured_image_url',
        '_property_short_description', '_property_long_description',
        '_property_location_description', '_property_equipment_description',
        '_property_other_description', '_property_available_from',
        '_property_contact_name', '_property_contact_email', '_property_contact_phone',
        '_property_usable_area', '_property_toilets', '_property_country',
        '_property_project_id', '_property_category',
        '_property_energy_hwb', '_property_energy_hwb_class',
        '_property_energy_fgee', '_property_energy_fgee_class',
        '_property_heating_type', '_property_energy_carrier',
        '_property_energy_cert_date', '_property_energy_cert_valid',
        '_property_features',
    ];

    $data = [ 'ID' => $post_id ];
    foreach ( $meta_keys as $key ) {
        $raw = get_post_meta( $post_id, $key, true );
        $clean_key = ltrim( $key, '_' );
        $data[ $clean_key ] = $raw;
    }

    // Gallerie als Array
    if ( is_string( $data['property_gallery'] ) ) {
        $data['property_gallery'] = maybe_unserialize( $data['property_gallery'] );
    }
    if ( ! is_array( $data['property_gallery'] ) ) {
        $data['property_gallery'] = [];
    }

    // Features als Array
    if ( is_string( $data['property_features'] ) ) {
        $data['property_features'] = maybe_unserialize( $data['property_features'] );
    }
    if ( ! is_array( $data['property_features'] ) ) {
        $data['property_features'] = [];
    }

    return $data;
}

function propstack_get_properties( array $args = [] ): array {
    $defaults = [
        'posts_per_page' => 12,
        'paged'          => 1,
        'post_type'      => 'propstack_property',
        'post_status'    => 'publish',
        'orderby'        => 'date',
        'order'          => 'DESC',
    ];

    $query_args = array_merge( $defaults, $args );

    // Filter-Parameter aus URL
    $tax_query = [];
    $meta_query = [ 'relation' => 'AND' ];

    $type   = sanitize_text_field( $_GET['type']   ?? '' );
    $city   = sanitize_text_field( $_GET['city']   ?? '' );
    $region = sanitize_text_field( $_GET['region'] ?? '' );
    $marketing = sanitize_text_field( $_GET['marketing'] ?? '' );
    $status    = sanitize_text_field( $_GET['status']    ?? '' );

    if ( $type ) {
        $tax_query[] = [ 'taxonomy' => 'property_type', 'field' => 'slug', 'terms' => $type ];
    }
    if ( $city ) {
        $tax_query[] = [ 'taxonomy' => 'property_city', 'field' => 'slug', 'terms' => $city ];
    }
    if ( $region ) {
        $tax_query[] = [ 'taxonomy' => 'property_region', 'field' => 'slug', 'terms' => $region ];
    }
    if ( $marketing ) {
        $tax_query[] = [ 'taxonomy' => 'property_marketing_type', 'field' => 'slug', 'terms' => $marketing ];
    }
    if ( $status ) {
        $tax_query[] = [ 'taxonomy' => 'property_status', 'field' => 'slug', 'terms' => $status ];
    }
    if ( ! empty( $tax_query ) ) {
        $query_args['tax_query'] = $tax_query;
    }

    // Preis-Filter
    $price_min = absint( $_GET['price_min'] ?? 0 );
    $price_max = absint( $_GET['price_max'] ?? 0 );
    if ( $price_min ) {
        $meta_query[] = [ 'key' => '_property_price', 'value' => $price_min, 'compare' => '>=', 'type' => 'NUMERIC' ];
    }
    if ( $price_max ) {
        $meta_query[] = [ 'key' => '_property_price', 'value' => $price_max, 'compare' => '<=', 'type' => 'NUMERIC' ];
    }

    // Fläche-Filter
    $area_min = absint( $_GET['area_min'] ?? 0 );
    if ( $area_min ) {
        $meta_query[] = [ 'key' => '_property_living_area', 'value' => $area_min, 'compare' => '>=', 'type' => 'NUMERIC' ];
    }

    // Zimmer-Filter
    $rooms_min = absint( $_GET['rooms_min'] ?? 0 );
    if ( $rooms_min ) {
        $meta_query[] = [ 'key' => '_property_rooms', 'value' => $rooms_min, 'compare' => '>=', 'type' => 'NUMERIC' ];
    }

    if ( count( $meta_query ) > 1 ) {
        $query_args['meta_query'] = $meta_query;
    }

    // Sortierung
    $sort = sanitize_text_field( $_GET['sort'] ?? '' );
    if ( $sort ) {
        match( $sort ) {
            'price_asc'  => $query_args['meta_key'] = '_property_price'
                         && $query_args['orderby'] = 'meta_value_num'
                         && $query_args['order'] = 'ASC',
            'price_desc' => $query_args['meta_key'] = '_property_price'
                         && $query_args['orderby'] = 'meta_value_num'
                         && $query_args['order'] = 'DESC',
            'area_desc'  => $query_args['meta_key'] = '_property_living_area'
                         && $query_args['orderby'] = 'meta_value_num'
                         && $query_args['order'] = 'DESC',
            default      => null,
        };
    }

    return (array) new WP_Query( $query_args );
}

function propstack_render_listing( array $args = [] ): string {
    ob_start();
    Propstack_RE_Template_Loader::get_template( 'listing.php', $args );
    return ob_get_clean();
}

function propstack_render_property_detail( int $property_id ): string {
    ob_start();
    Propstack_RE_Template_Loader::get_template( 'single-property.php', [ 'property_id' => $property_id ] );
    return ob_get_clean();
}

function propstack_render_contact_form( ?int $property_id = null ): string {
    ob_start();
    Propstack_RE_Template_Loader::get_template( 'contact-form.php', [ 'property_id' => $property_id ] );
    return ob_get_clean();
}
