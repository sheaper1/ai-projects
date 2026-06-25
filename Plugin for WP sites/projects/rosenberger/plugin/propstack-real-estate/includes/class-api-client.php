<?php
defined( 'ABSPATH' ) || exit;

class Propstack_RE_API_Client {

    private string $api_key;
    private string $base_url;
    private int    $timeout = 30;

    public function __construct() {
        $this->api_key  = get_option( 'propstack_re_api_key', '' );
        $this->base_url = rtrim( get_option( 'propstack_re_api_base_url', 'https://api.propstack.de/v1' ), '/' );
    }

    // -------------------------------------------------------------------------
    // Öffentliche API-Methoden
    // -------------------------------------------------------------------------

    public function test_connection(): array {
        $response = $this->get( '/units', [ 'per_page' => 1, 'page' => 1 ] );
        if ( is_wp_error( $response ) ) {
            return [ 'success' => false, 'message' => $response->get_error_message() ];
        }
        return [ 'success' => true, 'message' => __( 'Verbindung erfolgreich.', 'propstack-re' ) ];
    }

    public function get_properties( int $page = 1, int $per_page = 50, array $filters = [] ): array|WP_Error {
        $params = array_merge( [ 'page' => $page, 'per_page' => $per_page ], $filters );
        return $this->get( '/units', $params );
    }

    public function get_property( int|string $id ): array|WP_Error {
        // Propstack /units/{id} ist nicht verfügbar — alle Units laden und filtern
        $all = $this->get_all_properties();
        foreach ( $all as $unit ) {
            if ( isset( $unit['id'] ) && (string) $unit['id'] === (string) $id ) {
                return $unit;
            }
        }
        return new WP_Error( 'not_found', "Unit #{$id} nicht gefunden." );
    }

    public function get_statuses(): array|WP_Error {
        return $this->get( '/property_statuses' );
    }

    public function get_all_properties(): array {
        $all      = [];
        $page     = 1;
        $per_page = 50;

        do {
            $response = $this->get_properties( $page, $per_page );

            if ( is_wp_error( $response ) ) {
                Propstack_RE_Logger::error(
                    'Fehler beim Abrufen der Objekte (Seite ' . $page . '): ' . $response->get_error_message(),
                    'sync'
                );
                break;
            }

            $items = $this->extract_items( $response );

            if ( empty( $items ) ) {
                break;
            }

            // Nur echte Arrays (Objekte) übernehmen
            $items = array_values( array_filter( $items, 'is_array' ) );

            if ( empty( $items ) ) {
                break;
            }

            $all  = array_merge( $all, $items );
            $page++;

            // Propstack /units: kein total_pages — Ende wenn weniger als per_page geliefert
        } while ( count( $items ) === $per_page );

        return $all;
    }

    private function extract_items( array $response ): array {
        // Bekannte Wrapper-Keys probieren
        foreach ( [ 'records', 'data', 'properties', 'items', 'results', 'collection' ] as $key ) {
            if ( isset( $response[ $key ] ) && is_array( $response[ $key ] ) ) {
                return $response[ $key ];
            }
        }

        // Flat-Liste: numerisch indiziertes Array
        if ( ! empty( $response ) && array_keys( $response ) === range( 0, count( $response ) - 1 ) ) {
            return $response;
        }

        // Fallback: ersten Array-Wert nehmen dessen erstes Element selbst ein Array ist
        foreach ( $response as $key => $value ) {
            if ( is_array( $value ) && ! empty( $value ) && is_array( reset( $value ) ) ) {
                Propstack_RE_Logger::info( "API-Response: Items unter Key \"{$key}\" gefunden.", 'api' );
                return $value;
            }
        }

        Propstack_RE_Logger::warning(
            'API-Response: Unbekanntes Format. Keys: ' . implode( ', ', array_keys( $response ) ),
            'api'
        );

        return [];
    }

    public function create_contact( array $data ): array|WP_Error {
        return $this->post( '/contacts', $data );
    }

    public function update_contact( int|string $id, array $data ): array|WP_Error {
        return $this->patch( "/contacts/{$id}", $data );
    }

    public function find_contact_by_email( string $email ): array|WP_Error {
        return $this->get( '/contacts', [ 'email' => $email ] );
    }

    public function create_activity( array $data ): array|WP_Error {
        return $this->post( '/activities', $data );
    }

    public function create_note( array $data ): array|WP_Error {
        return $this->post( '/notes', $data );
    }

    public function link_contact_to_property( int|string $contact_id, int|string $property_id ): array|WP_Error {
        return $this->post( '/relationships', [
            'contact_id'  => $contact_id,
            'property_id' => $property_id,
            'kind'        => 'interested',
        ] );
    }

    public function register_webhook( string $url, array $events = [] ): array|WP_Error {
        return $this->post( '/webhooks', [
            'url'    => $url,
            'events' => $events ?: [ 'property.updated', 'property.created', 'property.deleted' ],
            'active' => true,
        ] );
    }

    public function delete_webhook( int|string $id ): array|WP_Error {
        return $this->delete( "/webhooks/{$id}" );
    }

    public function get_webhooks(): array|WP_Error {
        return $this->get( '/webhooks' );
    }

    // -------------------------------------------------------------------------
    // HTTP-Methoden
    // -------------------------------------------------------------------------

    private function get( string $endpoint, array $params = [] ): array|WP_Error {
        $url = $this->build_url( $endpoint, $params );
        $response = wp_remote_get( $url, $this->default_args() );
        return $this->parse_response( $response, $endpoint );
    }

    private function post( string $endpoint, array $body = [] ): array|WP_Error {
        $args = array_merge( $this->default_args(), [
            'method' => 'POST',
            'body'   => wp_json_encode( $body ),
        ] );
        $response = wp_remote_post( $this->build_url( $endpoint ), $args );
        return $this->parse_response( $response, $endpoint );
    }

    private function patch( string $endpoint, array $body = [] ): array|WP_Error {
        $args = array_merge( $this->default_args(), [
            'method' => 'PATCH',
            'body'   => wp_json_encode( $body ),
        ] );
        $response = wp_remote_request( $this->build_url( $endpoint ), $args );
        return $this->parse_response( $response, $endpoint );
    }

    private function delete( string $endpoint ): array|WP_Error {
        $args = array_merge( $this->default_args(), [ 'method' => 'DELETE' ] );
        $response = wp_remote_request( $this->build_url( $endpoint ), $args );
        return $this->parse_response( $response, $endpoint );
    }

    private function default_args(): array {
        return [
            'timeout' => $this->timeout,
            'headers' => [
                'X-Api-Key'    => $this->api_key,
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
            ],
        ];
    }

    private function build_url( string $endpoint, array $params = [] ): string {
        $url = $this->base_url . $endpoint;
        if ( $params ) {
            $url .= '?' . http_build_query( $params );
        }
        return $url;
    }

    private function parse_response( mixed $response, string $endpoint ): array|WP_Error {
        if ( is_wp_error( $response ) ) {
            Propstack_RE_Logger::error(
                'HTTP-Fehler bei ' . $endpoint . ': ' . $response->get_error_message(),
                'api'
            );
            set_transient( 'propstack_re_api_error', $response->get_error_message(), 3600 );
            return $response;
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if ( $code >= 400 ) {
            $message = $data['message'] ?? $data['error'] ?? "HTTP {$code}";
            Propstack_RE_Logger::error(
                "API {$code} bei {$endpoint}: {$message}",
                'api'
            );
            if ( $code === 401 || $code === 403 ) {
                set_transient( 'propstack_re_api_error', 'API-Authentifizierung fehlgeschlagen. Bitte API-Key prüfen.', 3600 );
            }
            return new WP_Error( "propstack_api_{$code}", $message );
        }

        // Erfolg: API-Fehler-Transient löschen
        delete_transient( 'propstack_re_api_error' );

        return $data ?? [];
    }
}
