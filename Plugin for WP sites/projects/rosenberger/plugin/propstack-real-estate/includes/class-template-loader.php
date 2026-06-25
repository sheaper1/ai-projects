<?php
defined( 'ABSPATH' ) || exit;

class Propstack_RE_Template_Loader {

    /**
     * Lädt ein Template — Theme-Override hat Vorrang (WooCommerce-Muster).
     * Theme kann propstack/{template}.php in seinem Ordner ablegen.
     */
    public static function get_template( string $template_name, array $args = [] ): void {
        // phpcs:ignore WordPress.PHP.DontExtract
        if ( $args ) {
            extract( $args );
        }

        $template = self::locate_template( $template_name );

        if ( $template ) {
            include $template;
        }
    }

    public static function get_template_html( string $template_name, array $args = [] ): string {
        ob_start();
        self::get_template( $template_name, $args );
        return ob_get_clean();
    }

    public static function locate_template( string $template_name ): string {
        // 1. Aktives Theme: mytheme/propstack/{template}.php
        $theme_template = get_stylesheet_directory() . '/propstack/' . $template_name;
        if ( file_exists( $theme_template ) ) {
            return $theme_template;
        }

        // 2. Parent Theme (bei Child-Themes)
        $parent_template = get_template_directory() . '/propstack/' . $template_name;
        if ( file_exists( $parent_template ) && $parent_template !== $theme_template ) {
            return $parent_template;
        }

        // 3. Plugin-Verzeichnis
        $plugin_template = PROPSTACK_RE_PATH . 'templates/' . $template_name;
        if ( file_exists( $plugin_template ) ) {
            return $plugin_template;
        }

        return '';
    }

    public static function get_partial( string $partial_name, array $args = [] ): void {
        self::get_template( 'partials/' . $partial_name, $args );
    }

    public static function get_partial_html( string $partial_name, array $args = [] ): string {
        return self::get_template_html( 'partials/' . $partial_name, $args );
    }
}
