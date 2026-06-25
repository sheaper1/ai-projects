<?php
defined( 'ABSPATH' ) || exit;

class Propstack_RE_SEO {

    private bool $active     = false;
    private bool $has_yoast  = false;
    private bool $has_rank   = false;

    public function init(): void {
        if ( ! get_option( 'propstack_re_seo_enabled', '1' ) ) {
            return;
        }

        $this->has_yoast = defined( 'WPSEO_VERSION' );
        $this->has_rank  = defined( 'RANK_MATH_VERSION' );

        // Eigene Tags nur wenn kein SEO-Plugin aktiv
        $this->active = ! $this->has_yoast && ! $this->has_rank;

        if ( $this->active ) {
            add_action( 'wp_head', [ $this, 'output_meta_tags' ], 1 );
        }

        // JSON-LD immer ausgeben (auch parallel zu Yoast, ohne Konflikt)
        if ( get_option( 'propstack_re_jsonld_enabled', '1' ) ) {
            add_action( 'wp_head', [ $this, 'output_json_ld' ], 99 );
        }

        // Yoast: Felder vorbelegen
        if ( $this->has_yoast ) {
            add_filter( 'wpseo_title',            [ $this, 'yoast_title'       ] );
            add_filter( 'wpseo_metadesc',         [ $this, 'yoast_description' ] );
            add_filter( 'wpseo_opengraph_image',  [ $this, 'yoast_og_image'    ] );
        }

        // RankMath: Felder vorbelegen
        if ( $this->has_rank ) {
            add_filter( 'rank_math/title',       [ $this, 'rank_title'       ] );
            add_filter( 'rank_math/description', [ $this, 'rank_description' ] );
        }

        // Featured Image für OG
        add_filter( 'wpseo_opengraph_image', [ $this, 'yoast_og_image' ] );
    }

    public function output_meta_tags(): void {
        if ( ! is_singular( 'propstack_property' ) ) {
            return;
        }

        global $post;
        $title       = $this->get_meta_title( $post->ID );
        $description = $this->get_meta_description( $post->ID );
        $canonical   = get_permalink( $post->ID );
        $og_image    = $this->get_og_image( $post->ID );

        if ( $title ) {
            echo '<title>' . esc_html( $title ) . "</title>\n";
            echo '<meta property="og:title" content="' . esc_attr( $title ) . '">' . "\n";
        }
        if ( $description ) {
            echo '<meta name="description" content="' . esc_attr( $description ) . '">' . "\n";
            echo '<meta property="og:description" content="' . esc_attr( $description ) . '">' . "\n";
        }
        if ( $canonical ) {
            echo '<link rel="canonical" href="' . esc_url( $canonical ) . '">' . "\n";
            echo '<meta property="og:url" content="' . esc_url( $canonical ) . '">' . "\n";
        }
        if ( $og_image ) {
            echo '<meta property="og:image" content="' . esc_url( $og_image ) . '">' . "\n";
        }

        echo '<meta property="og:type" content="website">' . "\n";
        echo '<meta property="og:site_name" content="' . esc_attr( get_bloginfo( 'name' ) ) . '">' . "\n";
    }

    public function output_json_ld(): void {
        if ( ! is_singular( 'propstack_property' ) ) {
            return;
        }

        global $post;
        $prop = propstack_get_property( $post->ID );

        $schema = [
            '@context'    => 'https://schema.org',
            '@type'       => 'RealEstateListing',
            'name'        => get_the_title( $post->ID ),
            'description' => $prop['property_short_description'] ?? get_the_excerpt( $post->ID ),
            'url'         => get_permalink( $post->ID ),
        ];

        // Bild
        $image = $this->get_og_image( $post->ID );
        if ( $image ) {
            $schema['image'] = $image;
        }

        // Adresse
        if ( $prop['property_city'] ) {
            $schema['address'] = array_filter( [
                '@type'           => 'PostalAddress',
                'streetAddress'   => trim( ( $prop['property_street'] ?? '' ) . ' ' . ( $prop['property_house_number'] ?? '' ) ),
                'addressLocality' => $prop['property_city'] ?? '',
                'postalCode'      => $prop['property_zip'] ?? '',
                'addressRegion'   => $prop['property_region'] ?? '',
                'addressCountry'  => $prop['property_country'] ?? 'AT',
            ] );
        }

        // Preis
        if ( ! empty( $prop['property_price'] ) && ! ( $prop['property_price_on_request'] ?? false ) ) {
            $schema['offers'] = [
                '@type'         => 'Offer',
                'price'         => (float) $prop['property_price'],
                'priceCurrency' => 'EUR',
                'availability'  => 'https://schema.org/InStock',
            ];
        }

        // Fläche
        if ( ! empty( $prop['property_living_area'] ) ) {
            $schema['floorSize'] = [
                '@type'    => 'QuantitativeValue',
                'value'    => (float) $prop['property_living_area'],
                'unitCode' => 'MTK',
            ];
        }

        if ( ! empty( $prop['property_rooms'] ) ) {
            $schema['numberOfRooms'] = (float) $prop['property_rooms'];
        }

        // Anbieter
        $schema['offeredBy'] = [
            '@type' => 'Organization',
            'name'  => get_bloginfo( 'name' ),
            'url'   => home_url(),
        ];

        echo '<script type="application/ld+json">' . "\n";
        echo wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT );
        echo "\n</script>\n";
    }

    public function get_meta_title( int $post_id ): string {
        $template = get_option( 'propstack_re_meta_title_template', '{title} in {city} | {site_name}' );
        return $this->render_template( $template, $post_id );
    }

    public function get_meta_description( int $post_id ): string {
        $template = get_option( 'propstack_re_meta_desc_template', '{short_description}' );
        $desc     = $this->render_template( $template, $post_id );
        // Auf 160 Zeichen kürzen
        return mb_substr( $desc, 0, 160 );
    }

    private function get_og_image( int $post_id ): string {
        // WP Featured Image
        if ( has_post_thumbnail( $post_id ) ) {
            $src = wp_get_attachment_image_src( get_post_thumbnail_id( $post_id ), 'large' );
            if ( $src ) {
                return $src[0];
            }
        }
        // Propstack Remote-URL als Fallback
        return get_post_meta( $post_id, '_property_featured_image_url', true ) ?? '';
    }

    private function render_template( string $template, int $post_id ): string {
        $prop  = propstack_get_property( $post_id );
        $pairs = [
            '{title}'             => get_the_title( $post_id ),
            '{city}'              => $prop['property_city'] ?? '',
            '{region}'            => $prop['property_region'] ?? '',
            '{type}'              => $prop['property_type'] ?? '',
            '{price}'             => $prop['property_price_display'] ?? '',
            '{rooms}'             => $prop['property_rooms'] ?? '',
            '{area}'              => $prop['property_living_area'] ? propstack_format_area( (float) $prop['property_living_area'] ) : '',
            '{short_description}' => wp_strip_all_tags( $prop['property_short_description'] ?? get_the_excerpt( $post_id ) ),
            '{site_name}'         => get_bloginfo( 'name' ),
            '{object_number}'     => $prop['property_object_number'] ?? '',
        ];
        return str_replace( array_keys( $pairs ), array_values( $pairs ), $template );
    }

    // Yoast-Filter
    public function yoast_title( string $title ): string {
        if ( is_singular( 'propstack_property' ) ) {
            global $post;
            return $this->get_meta_title( $post->ID );
        }
        return $title;
    }

    public function yoast_description( string $desc ): string {
        if ( is_singular( 'propstack_property' ) ) {
            global $post;
            return $this->get_meta_description( $post->ID );
        }
        return $desc;
    }

    public function yoast_og_image( string $image ): string {
        if ( is_singular( 'propstack_property' ) ) {
            global $post;
            $img = $this->get_og_image( $post->ID );
            return $img ?: $image;
        }
        return $image;
    }

    // RankMath-Filter
    public function rank_title( string $title ): string {
        return $this->yoast_title( $title );
    }

    public function rank_description( string $desc ): string {
        return $this->yoast_description( $desc );
    }
}
