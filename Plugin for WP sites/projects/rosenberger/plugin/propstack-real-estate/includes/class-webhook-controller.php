<?php
defined( 'ABSPATH' ) || exit;

class Propstack_RE_Webhook_Controller {

    public function register_routes(): void {
        register_rest_route( 'propstack/v1', '/webhook', [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => [ $this, 'handle_webhook' ],
            'permission_callback' => [ $this, 'verify_request' ],
        ] );
    }

    public function verify_request( WP_REST_Request $request ): bool {
        if ( ! get_option( 'propstack_re_webhook_enabled', '1' ) ) {
            return false;
        }

        $secret = get_option( 'propstack_re_webhook_secret', '' );
        if ( ! $secret ) {
            return true; // Kein Secret konfiguriert → offen (nur für Tests)
        }

        // Signatur aus Header prüfen
        $signature = $request->get_header( 'X-Propstack-Signature' )
            ?? $request->get_header( 'X-Webhook-Signature' )
            ?? '';

        if ( ! $signature ) {
            Propstack_RE_Logger::warning( 'Webhook ohne Signatur empfangen.', 'webhook' );
            return false;
        }

        $body     = $request->get_body();
        $expected = 'sha256=' . hash_hmac( 'sha256', $body, $secret );

        return hash_equals( $expected, $signature );
    }

    public function handle_webhook( WP_REST_Request $request ): WP_REST_Response {
        $payload = $request->get_json_params();

        if ( empty( $payload ) ) {
            return new WP_REST_Response( [ 'error' => 'Empty payload' ], 400 );
        }

        $event       = sanitize_text_field( $payload['event'] ?? $payload['type'] ?? '' );
        $property_id = $payload['data']['id'] ?? $payload['property_id'] ?? null;

        Propstack_RE_Logger::info(
            "Webhook empfangen: {$event}" . ( $property_id ? " (ID: {$property_id})" : '' ),
            'webhook'
        );

        update_option( 'propstack_re_last_webhook', current_time( 'mysql' ) );

        if ( $property_id ) {
            // Asynchron via WP Cron ausführen damit der Webhook sofort antwortet
            wp_schedule_single_event( time(), 'propstack_re_webhook_sync', [ (int) $property_id, $event ] );
            add_action( 'propstack_re_webhook_sync', [ $this, 'process_webhook_sync' ], 10, 2 );
        }

        return new WP_REST_Response( [ 'success' => true ], 200 );
    }

    public function process_webhook_sync( int $propstack_id, string $event ): void {
        if ( str_contains( $event, 'deleted' ) || str_contains( $event, 'archived' ) ) {
            $this->handle_delete( $propstack_id );
            return;
        }

        $sync = new Propstack_RE_Sync_Service();
        $result = $sync->sync_single_by_propstack_id( $propstack_id );

        if ( is_wp_error( $result ) ) {
            Propstack_RE_Logger::error(
                "Webhook-Sync für #{$propstack_id} fehlgeschlagen: " . $result->get_error_message(),
                'webhook'
            );
        }
    }

    private function handle_delete( int $propstack_id ): void {
        global $wpdb;
        $post_id = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key='_propstack_id' AND meta_value=%s LIMIT 1",
                (string) $propstack_id
            )
        );
        if ( $post_id ) {
            $action = get_option( 'propstack_re_deleted_action', 'trash' );
            wp_update_post( [ 'ID' => $post_id, 'post_status' => $action ] );
            Propstack_RE_Logger::info( "Objekt #{$propstack_id} (WP #{$post_id}) als {$action} markiert.", 'webhook' );
        }
    }
}
