<?php
defined( 'ABSPATH' ) || exit;

class Propstack_RE_CPT {

    public function register(): void {
        $this->register_post_type();
        $this->register_taxonomies();
    }

    private function register_post_type(): void {
        $slug = get_option( 'propstack_re_cpt_slug', 'immobilien' );

        $labels = [
            'name'               => __( 'Immobilien',         'propstack-re' ),
            'singular_name'      => __( 'Immobilie',          'propstack-re' ),
            'add_new'            => __( 'Neu',                 'propstack-re' ),
            'add_new_item'       => __( 'Neue Immobilie',      'propstack-re' ),
            'edit_item'          => __( 'Immobilie bearbeiten','propstack-re' ),
            'new_item'           => __( 'Neue Immobilie',      'propstack-re' ),
            'view_item'          => __( 'Immobilie ansehen',   'propstack-re' ),
            'search_items'       => __( 'Immobilien suchen',   'propstack-re' ),
            'not_found'          => __( 'Keine Immobilien',    'propstack-re' ),
            'not_found_in_trash' => __( 'Kein Papierkorb',    'propstack-re' ),
            'menu_name'          => __( 'Immobilien',          'propstack-re' ),
            'all_items'          => __( 'Alle Immobilien',     'propstack-re' ),
        ];

        $args = [
            'labels'              => $labels,
            'public'              => true,
            'publicly_queryable'  => true,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'query_var'           => true,
            'rewrite'             => [ 'slug' => $slug, 'with_front' => false ],
            'capability_type'     => 'post',
            'has_archive'         => $slug,
            'hierarchical'        => false,
            'menu_position'       => 20,
            'menu_icon'           => 'dashicons-admin-home',
            'supports'            => [ 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields' ],
            'show_in_rest'        => true,
        ];

        register_post_type( 'propstack_property', $args );
    }

    private function register_taxonomies(): void {
        $taxonomies = [
            'property_type' => [
                'label'   => __( 'Objektart',          'propstack-re' ),
                'slug'    => 'objektart',
            ],
            'property_city' => [
                'label'   => __( 'Stadt / Ort',        'propstack-re' ),
                'slug'    => 'ort',
            ],
            'property_region' => [
                'label'   => __( 'Region',             'propstack-re' ),
                'slug'    => 'region',
            ],
            'property_status' => [
                'label'   => __( 'Status',             'propstack-re' ),
                'slug'    => 'objekt-status',
            ],
            'property_marketing_type' => [
                'label'   => __( 'Vermarktungsart',    'propstack-re' ),
                'slug'    => 'vermarktung',
            ],
            'property_project' => [
                'label'   => __( 'Projekt',            'propstack-re' ),
                'slug'    => 'projekt',
            ],
        ];

        foreach ( $taxonomies as $taxonomy => $config ) {
            register_taxonomy(
                $taxonomy,
                'propstack_property',
                [
                    'hierarchical'      => true,
                    'labels'            => [
                        'name'          => $config['label'],
                        'singular_name' => $config['label'],
                    ],
                    'show_ui'           => true,
                    'show_admin_column' => true,
                    'query_var'         => true,
                    'rewrite'           => [ 'slug' => $config['slug'] ],
                    'show_in_rest'      => true,
                ]
            );
        }
    }
}
