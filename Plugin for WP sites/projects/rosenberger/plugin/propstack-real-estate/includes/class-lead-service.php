<?php
defined( 'ABSPATH' ) || exit;

class Propstack_RE_Lead_Service {

    private Propstack_RE_API_Client $api;

    public function __construct() {
        $this->api = new Propstack_RE_API_Client();
    }

    /**
     * Formular-Daten als Lead nach Propstack senden.
     * Gibt [ 'success' => bool, 'contact_id' => int|null ] zurück.
     */
    public function send_lead( array $form_data, ?int $post_id = null ): array {
        $propstack_property_id = null;
        if ( $post_id ) {
            $propstack_property_id = get_post_meta( $post_id, '_propstack_id', true );
        }

        // 1. Kontakt suchen oder erstellen
        $contact_id = $this->find_or_create_contact( $form_data );
        if ( ! $contact_id ) {
            Propstack_RE_Logger::error( 'Kontakt konnte nicht erstellt werden: ' . ( $form_data['email'] ?? 'unbekannt' ), 'lead' );
            return [ 'success' => false, 'contact_id' => null ];
        }

        // 2. Kontakt mit Objekt verknüpfen
        if ( $propstack_property_id ) {
            $this->link_to_property( $contact_id, $propstack_property_id );
        }

        // 3. Aktivität/Notiz erstellen
        $this->create_activity( $contact_id, $propstack_property_id, $form_data );

        Propstack_RE_Logger::info(
            "Lead erstellt: Kontakt #{$contact_id}" . ( $propstack_property_id ? ", Objekt #{$propstack_property_id}" : '' ),
            'lead'
        );

        return [ 'success' => true, 'contact_id' => $contact_id ];
    }

    private function find_or_create_contact( array $data ): int {
        $email = sanitize_email( $data['email'] ?? '' );
        if ( ! $email ) {
            return 0;
        }

        $deduplicate = get_option( 'propstack_re_contact_dedup', '1' );

        // Bestehenden Kontakt suchen
        if ( $deduplicate ) {
            $existing = $this->api->find_contact_by_email( $email );
            if ( ! is_wp_error( $existing ) ) {
                $contacts = $existing['records'] ?? $existing['data'] ?? ( is_array( $existing ) ? $existing : [] );
                if ( ! empty( $contacts ) ) {
                    $contact_id = $contacts[0]['id'] ?? 0;
                    if ( $contact_id ) {
                        return (int) $contact_id;
                    }
                }
            }
        }

        // Neuen Kontakt erstellen
        $contact_data = [
            'first_name' => sanitize_text_field( $data['first_name'] ?? '' ),
            'last_name'  => sanitize_text_field( $data['last_name']  ?? '' ),
            'email'      => $email,
            'phone'      => sanitize_text_field( $data['phone'] ?? '' ),
            'source'     => 'Website',
            'tags'       => [ 'website-anfrage' ],
        ];

        if ( ! empty( $data['salutation'] ) ) {
            $contact_data['salutation'] = sanitize_text_field( $data['salutation'] );
        }
        if ( ! empty( $data['street'] ) ) {
            $contact_data['address'] = [
                'street'  => sanitize_text_field( $data['street'] ),
                'zip'     => sanitize_text_field( $data['zip'] ?? '' ),
                'city'    => sanitize_text_field( $data['city_contact'] ?? '' ),
            ];
        }

        $result = $this->api->create_contact( $contact_data );
        if ( is_wp_error( $result ) ) {
            Propstack_RE_Logger::error( 'create_contact Fehler: ' . $result->get_error_message(), 'lead' );
            return 0;
        }

        return (int) ( $result['id'] ?? $result['contact']['id'] ?? 0 );
    }

    private function link_to_property( int $contact_id, int|string $propstack_property_id ): void {
        $result = $this->api->link_contact_to_property( $contact_id, $propstack_property_id );
        if ( is_wp_error( $result ) ) {
            Propstack_RE_Logger::warning(
                "Verknüpfung fehlgeschlagen: Kontakt #{$contact_id} ↔ Objekt #{$propstack_property_id}: " . $result->get_error_message(),
                'lead'
            );
        }
    }

    private function create_activity( int $contact_id, mixed $property_id, array $data ): void {
        $property_title = sanitize_text_field( $data['_property_title'] ?? '' );
        $message        = sanitize_textarea_field( $data['message'] ?? '' );
        $source_url     = esc_url_raw( $data['_source_url'] ?? '' );
        $utm_source     = sanitize_text_field( $data['_utm_source'] ?? '' );

        $note_text = sprintf(
            "Website-Anfrage%s\n\nNachricht: %s\n\nQuelle: %s%s",
            $property_title ? " zu: {$property_title}" : '',
            $message,
            $source_url,
            $utm_source ? "\nUTM Source: {$utm_source}" : ''
        );

        $activity_data = [
            'contact_id' => $contact_id,
            'kind'       => 'note',
            'body'       => $note_text,
            'date'       => current_time( 'c' ),
        ];

        if ( $property_id ) {
            $activity_data['property_id'] = $property_id;
        }

        $result = $this->api->create_activity( $activity_data );
        if ( is_wp_error( $result ) ) {
            Propstack_RE_Logger::warning(
                'Aktivität konnte nicht erstellt werden: ' . $result->get_error_message(),
                'lead'
            );
        }
    }
}
