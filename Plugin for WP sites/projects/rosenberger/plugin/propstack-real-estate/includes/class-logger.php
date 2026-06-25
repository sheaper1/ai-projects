<?php
defined( 'ABSPATH' ) || exit;

class Propstack_RE_Logger {

    const TABLE_NAME = 'propstack_logs';

    private static function table(): string {
        global $wpdb;
        return $wpdb->prefix . self::TABLE_NAME;
    }

    public static function create_table(): void {
        global $wpdb;
        $table      = self::table();
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$table} (
            id          bigint(20)   NOT NULL AUTO_INCREMENT,
            level       varchar(20)  NOT NULL DEFAULT 'info',
            context     varchar(50)  NOT NULL DEFAULT 'general',
            message     text         NOT NULL,
            created_at  datetime     NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY level (level),
            KEY context (context),
            KEY created_at (created_at)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    public static function log( string $level, string $message, string $context = 'general' ): void {
        global $wpdb;

        // API Key und sensible Daten aus Message entfernen
        $api_key = get_option( 'propstack_re_api_key', '' );
        if ( $api_key ) {
            $message = str_replace( $api_key, '[API_KEY]', $message );
        }

        $wpdb->insert(
            self::table(),
            [
                'level'      => sanitize_key( $level ),
                'context'    => sanitize_key( $context ),
                'message'    => $message,
                'created_at' => current_time( 'mysql' ),
            ],
            [ '%s', '%s', '%s', '%s' ]
        );
    }

    public static function info( string $message, string $context = 'general' ): void {
        self::log( 'info', $message, $context );
    }

    public static function error( string $message, string $context = 'general' ): void {
        self::log( 'error', $message, $context );
    }

    public static function warning( string $message, string $context = 'general' ): void {
        self::log( 'warning', $message, $context );
    }

    public static function get_logs( array $args = [] ): array {
        global $wpdb;

        $defaults = [
            'limit'   => 100,
            'offset'  => 0,
            'level'   => '',
            'context' => '',
        ];
        $args = array_merge( $defaults, $args );

        $where  = '1=1';
        $params = [];

        if ( $args['level'] ) {
            $where   .= ' AND level = %s';
            $params[] = $args['level'];
        }
        if ( $args['context'] ) {
            $where   .= ' AND context = %s';
            $params[] = $args['context'];
        }

        $params[] = (int) $args['limit'];
        $params[] = (int) $args['offset'];

        $sql = "SELECT * FROM " . self::table() . " WHERE {$where} ORDER BY created_at DESC LIMIT %d OFFSET %d";

        if ( $params ) {
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            return $wpdb->get_results( $wpdb->prepare( $sql, ...$params ) );
        }

        return $wpdb->get_results( $sql ); // phpcs:ignore
    }

    public static function count_logs( string $level = '', string $context = '' ): int {
        global $wpdb;
        $where  = '1=1';
        $params = [];

        if ( $level ) {
            $where   .= ' AND level = %s';
            $params[] = $level;
        }
        if ( $context ) {
            $where   .= ' AND context = %s';
            $params[] = $context;
        }

        $sql = "SELECT COUNT(*) FROM " . self::table() . " WHERE {$where}";
        if ( $params ) {
            return (int) $wpdb->get_var( $wpdb->prepare( $sql, ...$params ) ); // phpcs:ignore
        }
        return (int) $wpdb->get_var( $sql ); // phpcs:ignore
    }

    public static function clear_logs( int $days = 0 ): int {
        global $wpdb;
        if ( $days > 0 ) {
            $date = date( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );
            return (int) $wpdb->query(
                $wpdb->prepare( "DELETE FROM " . self::table() . " WHERE created_at < %s", $date ) // phpcs:ignore
            );
        }
        return (int) $wpdb->query( "TRUNCATE TABLE " . self::table() ); // phpcs:ignore
    }
}
