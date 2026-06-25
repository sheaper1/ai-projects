<?php
defined( 'ABSPATH' ) || exit;

class Propstack_RE_Sync_Service {

    private Propstack_RE_API_Client   $api;
    private Propstack_RE_Field_Mapper $mapper;
    private Propstack_RE_Image_Service $images;

    public function __construct() {
        $this->api    = new Propstack_RE_API_Client();
        $this->mapper = new Propstack_RE_Field_Mapper();
        $this->images = new Propstack_RE_Image_Service();
    }

    // -------------------------------------------------------------------------
    // Cron
    // -------------------------------------------------------------------------

    public function schedule_cron(): void {
        if ( ! get_option( 'propstack_re_api_key', '' ) ) {
            return;
        }
        $interval = get_option( 'propstack_re_sync_interval', 'hourly' );
        if ( ! wp_next_scheduled( 'propstack_re_cron_sync' ) ) {
            wp_schedule_event( time(), $interval, 'propstack_re_cron_sync' );
        }
    }

    public function run_cron_sync(): void {
        Propstack_RE_Logger::info( 'Cron-Sync gestartet.', 'sync' );
        $this->sync_all();
    }

    // -------------------------------------------------------------------------
    // Sync-Methoden
    // -------------------------------------------------------------------------

    public function sync_all( bool $force = false ): array {
        $start   = microtime( true );
        $results = [ 'created' => 0, 'updated' => 0, 'skipped' => 0, 'deactivated' => 0, 'errors' => 0 ];

        $properties = $this->api->get_all_properties();

        if ( empty( $properties ) ) {
            Propstack_RE_Logger::warning( 'Keine Objekte von API erhalten.', 'sync' );
            return $results;
        }

        $synced_ids = [];

        foreach ( $properties as $property_data ) {
            if ( ! is_array( $property_data ) ) {
                $results['errors']++;
                Propstack_RE_Logger::warning( 'Ungültiges Objekt übersprungen (kein Array).', 'sync' );
                continue;
            }
            $result = $this->sync_property( $property_data, $force );
            $synced_ids[] = (int) ( $property_data['id'] ?? 0 );

            if ( is_wp_error( $result ) ) {
                $results['errors']++;
            } elseif ( $result === 'created' ) {
                $results['created']++;
            } elseif ( $result === 'updated' ) {
                $results['updated']++;
            } else {
                $results['skipped']++;
            }
        }

        // Objekte die nicht mehr in Propstack sind → deaktivieren
        $results['deactivated'] = $this->deactivate_removed( $synced_ids );

        $duration = round( microtime( true ) - $start, 2 );
        Propstack_RE_Logger::info(
            "Sync abgeschlossen in {$duration}s: {$results['created']} neu, {$results['updated']} aktualisiert, {$results['skipped']} übersprungen, {$results['deactivated']} deaktiviert, {$results['errors']} Fehler.",
            'sync'
        );

        update_option( 'propstack_re_last_sync', current_time( 'mysql' ) );
        update_option( 'propstack_re_last_sync_results', $results );

        return $results;
    }

    public function sync_single_by_propstack_id( int|string $propstack_id ): string|WP_Error {
        $data = $this->api->get_property( $propstack_id );
        if ( is_wp_error( $data ) ) {
            return $data;
        }
        return $this->sync_property( $data, true );
    }

    private function sync_property( array $data, bool $force = false ): string|WP_Error {
        $propstack_id = $data['id'] ?? null;
        if ( ! $propstack_id ) {
            return new WP_Error( 'missing_id', 'Propstack-ID fehlt in Objekt-Daten.' );
        }

        try {
            $mapped   = $this->mapper->map( $data );
            $new_hash = $this->mapper->compute_hash( $data );
            $post_id  = $this->find_wp_post( $propstack_id );

            // Hash-Vergleich: nur aktualisieren wenn sich etwas geändert hat
            if ( $post_id && ! $force ) {
                $old_hash = get_post_meta( $post_id, '_propstack_last_hash', true );
                if ( $old_hash === $new_hash ) {
                    return 'skipped';
                }
            }

            // Sichtbarkeit bestimmen
            $mapped['post']['post_status'] = $this->determine_post_status( $data );

            if ( $post_id ) {
                $mapped['post']['ID'] = $post_id;
                wp_update_post( $mapped['post'] );
                $action = 'updated';
            } else {
                $post_id = wp_insert_post( $mapped['post'] );
                if ( is_wp_error( $post_id ) ) {
                    return $post_id;
                }
                $action = 'created';
            }

            // Meta speichern
            foreach ( $mapped['meta'] as $key => $value ) {
                update_post_meta( $post_id, $key, $value );
            }

            // Taxonomien setzen
            foreach ( $mapped['taxonomies'] as $taxonomy => $term ) {
                if ( $term ) {
                    wp_set_object_terms( $post_id, (string) $term, $taxonomy );
                }
            }

            // Bilder importieren
            $images = maybe_unserialize( $mapped['meta']['_property_gallery'] ?? '' );
            if ( is_array( $images ) && ! empty( $images ) ) {
                $this->images->import_images( $post_id, $images );
            }

            Propstack_RE_Logger::info(
                "Objekt #{$propstack_id} — {$action} (WP-ID: {$post_id}).",
                'sync'
            );

            return $action;

        } catch ( \Throwable $e ) {
            Propstack_RE_Logger::error(
                "Fehler bei Objekt #{$propstack_id}: " . $e->getMessage(),
                'sync'
            );
            return new WP_Error( 'sync_error', $e->getMessage() );
        }
    }

    // -------------------------------------------------------------------------
    // Sichtbarkeitslogik
    // -------------------------------------------------------------------------

    private function determine_post_status( array $data ): string {
        $public_status_ids = get_option( 'propstack_re_public_statuses', [] );
        if ( ! is_array( $public_status_ids ) ) {
            $public_status_ids = [];
        }

        // Units-API: Status als Objekt {"id": ..., "name": "..."} oder direkt als ID
        $status_id = isset( $data['status']['id'] )
            ? (int) $data['status']['id']
            : (int) ( $data['rs_status_id'] ?? $data['status_id'] ?? 0 );

        // Wenn public_status_ids konfiguriert sind → prüfen
        if ( ! empty( $public_status_ids ) ) {
            if ( in_array( $status_id, array_map( 'intval', $public_status_ids ), true ) ) {
                return 'publish';
            }
        } else {
            // Keine Konfiguration → anhand Status-Name "Vermarktung" / "reserviert" publizieren
            $status_name = strtolower( $data['status']['name'] ?? $data['status_name'] ?? '' );
            if ( str_contains( $status_name, 'vermarktung' ) || str_contains( $status_name, 'reserviert' ) || str_contains( $status_name, 'aktiv' ) ) {
                return 'publish';
            }
        }

        // Gelöscht in Propstack?
        $deleted = $data['deleted'] ?? $data['archived'] ?? false;
        if ( $deleted ) {
            $inactive_action = get_option( 'propstack_re_deleted_action', 'trash' );
            return $inactive_action === 'trash' ? 'trash' : 'draft';
        }

        // Andere Status → Draft
        $inactive_action = get_option( 'propstack_re_inactive_action', 'draft' );
        return $inactive_action;
    }

    // -------------------------------------------------------------------------
    // Hilfsmethoden
    // -------------------------------------------------------------------------

    private function find_wp_post( int|string $propstack_id ): int {
        global $wpdb;
        $post_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_propstack_id' AND meta_value = %s LIMIT 1",
                (string) $propstack_id
            )
        );
        return $post_id ? (int) $post_id : 0;
    }

    private function deactivate_removed( array $synced_propstack_ids ): int {
        if ( empty( $synced_propstack_ids ) ) {
            return 0;
        }

        global $wpdb;
        $inactive_action = get_option( 'propstack_re_inactive_action', 'draft' );

        $placeholders = implode( ',', array_fill( 0, count( $synced_propstack_ids ), '%s' ) );
        $ids_string   = array_map( 'strval', $synced_propstack_ids );

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT pm.post_id, pm.meta_value FROM {$wpdb->postmeta} pm
                 INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
                 WHERE pm.meta_key = '_propstack_id'
                 AND pm.meta_value NOT IN ({$placeholders})
                 AND p.post_type = 'propstack_property'
                 AND p.post_status = 'publish'",
                ...$ids_string
            )
        );

        $count = 0;
        foreach ( $rows as $row ) {
            wp_update_post( [ 'ID' => $row->post_id, 'post_status' => $inactive_action ] );
            $count++;
        }

        return $count;
    }

    public function get_stats(): array {
        $total     = wp_count_posts( 'propstack_property' );
        $last_sync = get_option( 'propstack_re_last_sync', '' );
        $results   = get_option( 'propstack_re_last_sync_results', [] );

        return [
            'total_published' => $total->publish ?? 0,
            'total_draft'     => $total->draft ?? 0,
            'last_sync'       => $last_sync,
            'last_results'    => $results,
            'next_sync'       => wp_next_scheduled( 'propstack_re_cron_sync' ),
        ];
    }
}
