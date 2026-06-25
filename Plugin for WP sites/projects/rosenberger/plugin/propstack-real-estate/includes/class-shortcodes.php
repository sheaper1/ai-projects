<?php
defined( 'ABSPATH' ) || exit;

class Propstack_RE_Shortcodes {

    public function register(): void {
        add_shortcode( 'propstack_listing',      [ $this, 'listing'      ] );
        add_shortcode( 'propstack_filters',      [ $this, 'filters'      ] );
        add_shortcode( 'propstack_property',     [ $this, 'property'     ] );
        add_shortcode( 'propstack_contact_form', [ $this, 'contact_form' ] );
    }

    public function listing( array $atts ): string {
        $atts = shortcode_atts( [
            'limit'        => get_option( 'propstack_re_default_limit', 12 ),
            'type'         => '',
            'city'         => '',
            'region'       => '',
            'marketing'    => '',
            'status'       => '',
            'project'      => '',
            'show_filters' => 'true',
            'layout'       => get_option( 'propstack_re_listing_layout', 'grid' ),
            'columns'      => get_option( 'propstack_re_cards_per_row', '3' ),
            'orderby'      => 'date',
            'order'        => 'DESC',
        ], $atts, 'propstack_listing' );

        // Shortcode-Attribute als Basis für Query, URL-Parameter überschreiben
        $query_args = [
            'posts_per_page' => (int) $atts['limit'],
            'paged'          => max( 1, get_query_var( 'paged', 1 ) ),
            'orderby'        => sanitize_key( $atts['orderby'] ),
            'order'          => strtoupper( $atts['order'] ) === 'ASC' ? 'ASC' : 'DESC',
        ];

        // Shortcode-Attribute als Basis-Filter (URL-Parameter haben Vorrang)
        $tax_query = [];
        foreach ( [
            'type'      => 'property_type',
            'city'      => 'property_city',
            'region'    => 'property_region',
            'marketing' => 'property_marketing_type',
            'status'    => 'property_status',
            'project'   => 'property_project',
        ] as $attr => $taxonomy ) {
            $value = sanitize_text_field( $_GET[ $attr ] ?? $atts[ $attr ] ?? '' );
            if ( $value ) {
                $tax_query[] = [ 'taxonomy' => $taxonomy, 'field' => 'slug', 'terms' => $value ];
            }
        }
        if ( ! empty( $tax_query ) ) {
            $query_args['tax_query'] = $tax_query;
        }

        // Preis + Fläche + Zimmer aus URL
        $meta_query = [ 'relation' => 'AND' ];
        $filters_map = [
            'price_min' => [ '_property_price',       '>=' ],
            'price_max' => [ '_property_price',       '<=' ],
            'area_min'  => [ '_property_living_area', '>=' ],
            'rooms_min' => [ '_property_rooms',       '>=' ],
        ];
        foreach ( $filters_map as $param => [ $meta_key, $compare ] ) {
            $val = absint( $_GET[ $param ] ?? 0 );
            if ( $val ) {
                $meta_query[] = [ 'key' => $meta_key, 'value' => $val, 'compare' => $compare, 'type' => 'NUMERIC' ];
            }
        }
        if ( count( $meta_query ) > 1 ) {
            $query_args['meta_query'] = $meta_query;
        }

        // Sortierung aus URL
        $sort = sanitize_key( $_GET['sort'] ?? '' );
        if ( $sort === 'price_asc' ) {
            $query_args['meta_key'] = '_property_price';
            $query_args['orderby']  = 'meta_value_num';
            $query_args['order']    = 'ASC';
        } elseif ( $sort === 'price_desc' ) {
            $query_args['meta_key'] = '_property_price';
            $query_args['orderby']  = 'meta_value_num';
            $query_args['order']    = 'DESC';
        } elseif ( $sort === 'area_desc' ) {
            $query_args['meta_key'] = '_property_living_area';
            $query_args['orderby']  = 'meta_value_num';
            $query_args['order']    = 'DESC';
        }

        $query_args['post_type']   = 'propstack_property';
        $query_args['post_status'] = 'publish';
        $query                     = new WP_Query( $query_args );

        return Propstack_RE_Template_Loader::get_template_html( 'listing.php', [
            'query'        => $query,
            'atts'         => $atts,
            'show_filters' => $atts['show_filters'] === 'true',
            'layout'       => sanitize_key( $atts['layout'] ),
            'columns'      => (int) $atts['columns'],
        ] );
    }

    public function filters( array $atts ): string {
        $atts = shortcode_atts( [], $atts, 'propstack_filters' );
        return Propstack_RE_Template_Loader::get_template_html( 'filters.php', [ 'atts' => $atts ] );
    }

    public function property( array $atts ): string {
        $atts = shortcode_atts( [ 'id' => '' ], $atts, 'propstack_property' );

        if ( ! $atts['id'] ) {
            return '';
        }

        // Nach Propstack-ID oder WP-Post-ID suchen
        $post_id = (int) $atts['id'];
        if ( get_post_type( $post_id ) !== 'propstack_property' ) {
            global $wpdb;
            $post_id = (int) $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key='_propstack_id' AND meta_value=%s LIMIT 1",
                    (string) $atts['id']
                )
            );
        }

        if ( ! $post_id ) {
            return '';
        }

        return Propstack_RE_Template_Loader::get_template_html( 'single-property.php', [
            'property_id' => $post_id,
        ] );
    }

    public function contact_form( array $atts ): string {
        $atts = shortcode_atts( [ 'property_id' => '' ], $atts, 'propstack_contact_form' );

        if ( ! get_option( 'propstack_re_form_enabled', '1' ) ) {
            return '';
        }

        $property_id = $atts['property_id'] ? (int) $atts['property_id'] : null;

        // Wenn kein property_id übergeben, aktuellen Post verwenden
        if ( ! $property_id ) {
            global $post;
            if ( $post && $post->post_type === 'propstack_property' ) {
                $property_id = $post->ID;
            }
        }

        return Propstack_RE_Template_Loader::get_template_html( 'contact-form.php', [
            'property_id' => $property_id,
        ] );
    }
}
