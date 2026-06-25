<?php
defined( 'ABSPATH' ) || exit;

class Propstack_RE_Field_Mapper {

    /**
     * Propstack-API-Response auf WP-Post + Meta mappen.
     * Gibt [ 'post' => [...], 'meta' => [...], 'taxonomies' => [...] ] zurück.
     */
    public function map( array $data ): array {
        $post = [
            'post_type'    => 'propstack_property',
            'post_title'   => $this->get_title( $data ),
            'post_content' => $this->safe( $data, 'description' ) ?? $this->safe( $data, 'long_description' ) ?? '',
            'post_excerpt' => $this->safe( $data, 'short_description' ) ?? '',
            'post_status'  => 'publish',
        ];

        $meta = [
            '_propstack_id'                  => $this->safe( $data, 'id' ),
            '_propstack_status'              => $this->get_status_name( $data ),
            '_propstack_status_id'           => $this->get_status_id( $data ),
            '_propstack_last_sync'           => current_time( 'mysql' ),
            '_propstack_source_updated_at'   => $this->safe( $data, 'updated_at' ),

            // Texte
            '_property_title'                => $this->get_title( $data ),
            '_property_short_description'    => $this->safe( $data, 'short_description' ),
            '_property_long_description'     => $this->safe( $data, 'description' ) ?? $this->safe( $data, 'long_description' ),
            '_property_location_description' => $this->safe( $data, 'location_description' ),
            '_property_equipment_description'=> $this->safe( $data, 'equipment_description' ) ?? $this->safe( $data, 'furnishing_description' ),
            '_property_other_description'    => $this->safe( $data, 'other_description' ),

            // Preise (Units-API: price=Kaufpreis, base_rent=Miete)
            '_property_price'                => $this->get_price( $data ),
            '_property_price_display'        => $this->get_price_display( $data ),
            '_property_price_on_request'     => $this->safe( $data, 'price_on_request' ) ? '1' : '0',
            '_property_price_netto'          => $this->safe( $data, 'purchase_price_net' ),
            '_property_price_brutto'         => $this->safe( $data, 'price' ) ?? $this->safe( $data, 'purchase_price' ),
            '_property_price_per_sqm'        => $this->safe( $data, 'price_per_sqm' ) ?? $this->safe( $data, 'property_rent_per_sqm_from_value' ),
            '_property_rent_net'             => $this->safe( $data, 'rent_net' ),
            '_property_rent_gross'           => $this->safe( $data, 'base_rent' ) ?? $this->safe( $data, 'rent_gross' ),
            '_property_operating_costs'      => $this->safe( $data, 'property_additional_costs_per_sqm_from_value' ) ?? $this->safe( $data, 'operating_costs' ),
            '_property_heating_costs'        => $this->safe( $data, 'heating_costs' ),
            '_property_monthly_costs'        => $this->safe( $data, 'monthly_costs' ),
            '_property_reserve_fund'         => $this->safe( $data, 'reserve_fund' ),
            '_property_deposit'              => $this->safe( $data, 'deposit' ),
            '_property_commission'           => $this->safe( $data, 'commission' ),
            '_property_commission_note'      => $this->safe( $data, 'commission_note' ),
            '_property_vat'                  => $this->safe( $data, 'vat' ),
            '_property_currency'             => $this->safe( $data, 'currency' ),

            // Flächen & Kennzahlen (Units-API-Felder)
            '_property_living_area'          => $this->safe( $data, 'living_space' ) ?? $this->safe( $data, 'living_area' ),
            '_property_plot_area'            => $this->safe( $data, 'property_space_value' ) ?? $this->safe( $data, 'plot_area' ),
            '_property_usable_area'          => $this->safe( $data, 'usable_area' ),
            '_property_rooms'                => $this->safe( $data, 'number_of_rooms' ) ?? $this->safe( $data, 'rooms' ),
            '_property_bedrooms'             => $this->safe( $data, 'number_of_bed_rooms' ) ?? $this->safe( $data, 'number_of_bedrooms' ) ?? $this->safe( $data, 'bedrooms' ),
            '_property_bathrooms'            => $this->safe( $data, 'number_of_bath_rooms' ) ?? $this->safe( $data, 'number_of_bathrooms' ) ?? $this->safe( $data, 'bathrooms' ),
            '_property_toilets'              => $this->safe( $data, 'number_of_toilets' ) ?? $this->safe( $data, 'toilets' ),
            '_property_floor'                => $this->safe( $data, 'floor' ),
            '_property_available_from'       => $this->safe( $data, 'free_from' ) ?? $this->safe( $data, 'available_from' ),
            '_property_object_number'        => $this->safe( $data, 'unit_id' ) ?? $this->safe( $data, 'rs_id' ) ?? $this->safe( $data, 'id' ),

            // Adresse (Units-API: Felder direkt auf Root-Ebene)
            '_property_street'               => $this->safe( $data, 'street' ),
            '_property_house_number'         => $this->safe( $data, 'house_number' ),
            '_property_city'                 => $this->safe( $data, 'city' ),
            '_property_zip'                  => $this->safe( $data, 'zip_code' ) ?? $this->safe( $data, 'zip' ),
            '_property_region'               => $this->safe( $data, 'region' ) ?? $this->safe( $data, 'district' ),
            '_property_country'              => $this->safe( $data, 'country' ),
            '_property_lat'                  => $this->safe( $data, 'lat' ),
            '_property_lng'                  => $this->safe( $data, 'lng' ),

            // Klassifikation
            '_property_type'                 => $this->safe( $data, 'property_type' ) ?? $this->safe( $data, 'type' ),
            '_property_category'             => $this->safe( $data, 'category' ),
            '_property_marketing_type'       => $this->get_marketing_type( $data ),
            '_property_project_id'           => $this->safe( $data, 'project_id' ),
            '_property_rented'               => ( $this->safe( $data, 'rented' ) ) ? '1' : '0',

            // Bilder
            '_property_gallery'              => serialize( $this->get_images( $data ) ),
            '_property_featured_image_url'   => $this->get_featured_image( $data ),

            // Kontakt / Makler
            '_property_contact_name'         => $this->get_contact_name( $data ),
            '_property_contact_email'        => $this->get_contact_email( $data ),
            '_property_contact_phone'        => $this->get_contact_phone( $data ),
            '_property_contact_avatar'       => $this->get_contact_avatar( $data ),

            // Energie
            '_property_energy_hwb'           => $this->safe( $data['energy_certificate'] ?? [], 'hwb_value' ) ?? $this->safe( $data, 'hwb' ),
            '_property_energy_hwb_class'     => $this->safe( $data['energy_certificate'] ?? [], 'hwb_class' ),
            '_property_energy_fgee'          => $this->safe( $data['energy_certificate'] ?? [], 'fgee_value' ) ?? $this->safe( $data, 'fgee' ),
            '_property_energy_fgee_class'    => $this->safe( $data['energy_certificate'] ?? [], 'fgee_class' ),
            '_property_heating_type'         => $this->safe( $data, 'heating_type' ),
            '_property_energy_carrier'       => $this->safe( $data, 'energy_carrier' ),
            '_property_energy_cert_date'     => $this->safe( $data['energy_certificate'] ?? [], 'issue_date' ),
            '_property_energy_cert_valid'    => $this->safe( $data['energy_certificate'] ?? [], 'valid_until' ),

            // Ausstattung (Boolean-Features)
            '_property_features'             => serialize( $this->get_features( $data ) ),
        ];

        // Taxonomie-Daten (Units-API: Adresse auf Root-Ebene)
        $taxonomies = [
            'property_type'          => $this->safe( $data, 'property_type' ) ?? $this->safe( $data, 'type' ),
            'property_city'          => $this->safe( $data, 'city' ),
            'property_region'        => $this->safe( $data, 'region' ) ?? $this->safe( $data, 'district' ),
            'property_status'        => $this->get_status_name( $data ),
            'property_marketing_type'=> $this->get_marketing_type( $data ),
            'property_project'       => $this->safe( $data, 'project_name' ),
        ];

        // Hash für Änderungsvergleich (nur öffentliche Felder)
        $meta['_propstack_last_hash'] = $this->compute_hash( $data );

        return compact( 'post', 'meta', 'taxonomies' );
    }

    public function compute_hash( array $data ): string {
        $relevant = [
            $data['id'] ?? '',
            $data['updated_at'] ?? '',
            $data['price'] ?? $data['purchase_price'] ?? '',
            $data['base_rent'] ?? $data['rent_gross'] ?? '',
            $data['status']['id'] ?? $data['rs_status_id'] ?? $data['status_id'] ?? '',
            $data['living_space'] ?? $data['living_area'] ?? '',
            count( $data['images'] ?? [] ),
        ];
        return md5( implode( '|', $relevant ) );
    }

    // -------------------------------------------------------------------------
    // Felder die NICHT öffentlich sein dürfen
    // -------------------------------------------------------------------------
    public function get_public_data( array $data ): array {
        $blacklist = [
            'owner', 'owner_id', 'buyer', 'buyer_id',
            'contacts', 'linked_contacts',
            'internal_notes', 'notes', 'tasks', 'appointments',
            'target_group', 'portals', 'mandate_type',
            'contract_duration', 'rating', 'brokerage_date',
            'realized_price', 'internal_commission', 'external_commission',
            'total_commission', 'land_register', 'cadastral_municipality',
            'district_court', 'co_ownership_share', 'land_register_number',
            'documents', 'activities', 'marketing_flags',
            'landing_page_settings', 'expose_auto_send',
            'api_data', 'contact_history',
            'pipeline', 'search_profiles', 'teams',
            'email_logs', 'internal_commission_note',
        ];

        return array_diff_key( $data, array_flip( $blacklist ) );
    }

    // -------------------------------------------------------------------------
    // Private Hilfsmethoden
    // -------------------------------------------------------------------------

    private function safe( array $data, string $key ): mixed {
        return $data[ $key ] ?? null;
    }

    private function get_title( array $data ): string {
        $title = $this->safe( $data, 'title' ) ?? $this->safe( $data, 'name' );
        if ( $title ) {
            return sanitize_text_field( $title );
        }

        // Fallback: aus Typ + Ort zusammensetzen
        $type = $this->safe( $data, 'property_type' ) ?? $this->safe( $data, 'type' ) ?? 'Immobilie';
        $city = $this->safe( $data['address'] ?? [], 'city' ) ?? $this->safe( $data, 'city' ) ?? '';
        $id   = $this->safe( $data, 'rs_id' ) ?? $this->safe( $data, 'id' ) ?? '';

        return trim( "{$type}" . ( $city ? " in {$city}" : '' ) . ( $id ? " #{$id}" : '' ) );
    }

    private function get_status_name( array $data ): string {
        // Units-API: status ist ein Objekt {"id": ..., "name": "...", "color": "..."}
        if ( isset( $data['status']['name'] ) ) {
            return (string) $data['status']['name'];
        }
        return $this->safe( $data, 'status_name' )
            ?? (string) ( $this->safe( $data, 'rs_status_id' ) ?? '' );
    }

    private function get_status_id( array $data ): ?int {
        // Units-API: status ist ein Objekt
        if ( isset( $data['status']['id'] ) ) {
            return (int) $data['status']['id'];
        }
        $id = $this->safe( $data, 'rs_status_id' ) ?? $this->safe( $data, 'status_id' );
        return $id !== null ? (int) $id : null;
    }

    private function get_price( array $data ): ?float {
        // Units-API: price=Kaufpreis, base_rent=Miete
        $price = $this->safe( $data, 'price' )
            ?? $this->safe( $data, 'purchase_price' )
            ?? $this->safe( $data, 'base_rent' )
            ?? $this->safe( $data, 'rent_gross' );
        return $price ? (float) $price : null;
    }

    private function get_price_display( array $data ): string {
        if ( $this->safe( $data, 'price_on_request' ) ) {
            return __( 'Auf Anfrage', 'propstack-re' );
        }
        $price = $this->get_price( $data );
        return $price ? propstack_format_price( $price ) : __( 'Auf Anfrage', 'propstack-re' );
    }

    private function get_marketing_type( array $data ): string {
        $type = $this->safe( $data, 'marketing_type' ) ?? $this->safe( $data, 'rent_or_buy' ) ?? '';
        if ( ! $type ) {
            // Ableiten aus Preisfeldern
            $type = isset( $data['rent_gross'] ) ? 'rent' : 'buy';
        }
        return strtolower( $type );
    }

    private function get_images( array $data ): array {
        $images = $this->safe( $data, 'images' ) ?? $this->safe( $data, 'pictures' ) ?? [];
        if ( ! is_array( $images ) ) {
            return [];
        }
        return array_map( fn( $img ) => [
            'id'    => $img['id'] ?? 0,
            'url'   => $img['url'] ?? $img['original_url'] ?? '',
            'thumb' => $img['thumb_url'] ?? $img['url'] ?? '',
            'title' => $img['title'] ?? '',
            'sort'  => $img['position'] ?? $img['sort'] ?? 0,
        ], $images );
    }

    private function get_featured_image( array $data ): string {
        $images = $this->safe( $data, 'images' ) ?? $this->safe( $data, 'pictures' ) ?? [];
        if ( ! is_array( $images ) || empty( $images ) ) {
            return '';
        }
        usort( $images, fn( $a, $b ) => ( $a['position'] ?? 0 ) <=> ( $b['position'] ?? 0 ) );
        return $images[0]['url'] ?? $images[0]['original_url'] ?? '';
    }

    private function get_contact_name( array $data ): string {
        $broker = $this->safe( $data, 'broker' ) ?? $this->safe( $data, 'user' ) ?? $this->safe( $data, 'contact_person' ) ?? [];
        if ( is_array( $broker ) ) {
            $first = $broker['first_name'] ?? '';
            $last  = $broker['last_name']  ?? '';
            return trim( "{$first} {$last}" );
        }
        return '';
    }

    private function get_contact_email( array $data ): string {
        $broker = $this->safe( $data, 'broker' ) ?? $this->safe( $data, 'user' ) ?? [];
        return is_array( $broker ) ? ( $broker['email'] ?? '' ) : '';
    }

    private function get_contact_phone( array $data ): string {
        $broker = $this->safe( $data, 'broker' ) ?? $this->safe( $data, 'user' ) ?? [];
        return is_array( $broker ) ? ( $broker['phone'] ?? $broker['mobile'] ?? '' ) : '';
    }

    private function get_contact_avatar( array $data ): string {
        $broker = $this->safe( $data, 'broker' ) ?? $this->safe( $data, 'user' ) ?? [];
        return is_array( $broker ) ? ( $broker['avatar_url'] ?? $broker['picture_url'] ?? '' ) : '';
    }

    private function get_features( array $data ): array {
        $feature_keys = [
            'balcony'         => __( 'Balkon',           'propstack-re' ),
            'terrace'         => __( 'Terrasse',         'propstack-re' ),
            'garden'          => __( 'Garten',           'propstack-re' ),
            'basement'        => __( 'Keller',           'propstack-re' ),
            'elevator'        => __( 'Aufzug',           'propstack-re' ),
            'barrier_free'    => __( 'Barrierefrei',     'propstack-re' ),
            'fitted_kitchen'  => __( 'Einbauküche',      'propstack-re' ),
            'guest_toilet'    => __( 'Gäste-WC',         'propstack-re' ),
            'fireplace'       => __( 'Kamin',            'propstack-re' ),
            'pool'            => __( 'Pool',             'propstack-re' ),
            'sauna'           => __( 'Sauna',            'propstack-re' ),
            'air_conditioning'=> __( 'Klimaanlage',      'propstack-re' ),
            'smart_home'      => __( 'Smart Home',       'propstack-re' ),
            'alarm_system'    => __( 'Alarmanlage',      'propstack-re' ),
            'winter_garden'   => __( 'Wintergarten',     'propstack-re' ),
            'bicycle_room'    => __( 'Fahrradraum',      'propstack-re' ),
            'laundry_room'    => __( 'Wasch-/Trockenraum','propstack-re' ),
        ];

        $features = [];
        $attrs    = $this->safe( $data, 'attributes' ) ?? $this->safe( $data, 'features' ) ?? [];

        foreach ( $feature_keys as $key => $label ) {
            $value = $this->safe( $data, $key ) ?? $this->safe( $attrs, $key );
            if ( $value ) {
                $features[ $key ] = $label;
            }
        }

        return $features;
    }
}
