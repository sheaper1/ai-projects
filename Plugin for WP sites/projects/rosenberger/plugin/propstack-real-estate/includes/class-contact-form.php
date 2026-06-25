<?php
defined( 'ABSPATH' ) || exit;

class Propstack_RE_Contact_Form {

    public function handle_submission(): void {
        check_ajax_referer( 'propstack_re_nonce', 'nonce' );

        // Honeypot prüfen
        if ( ! empty( $_POST['_gotcha'] ) ) {
            wp_send_json_error( [ 'message' => __( 'Ungültige Anfrage.', 'propstack-re' ) ] );
        }

        // Rate Limit: max 3 Anfragen / IP / Stunde
        $ip       = sanitize_text_field( $_SERVER['REMOTE_ADDR'] ?? '' );
        $rate_key = 'propstack_re_rl_' . md5( $ip );
        $count    = (int) get_transient( $rate_key );
        if ( $count >= 3 ) {
            wp_send_json_error( [ 'message' => __( 'Zu viele Anfragen. Bitte warte einen Moment.', 'propstack-re' ) ] );
        }
        set_transient( $rate_key, $count + 1, HOUR_IN_SECONDS );

        // Mindestzeit: 3 Sekunden zwischen Seitenaufruf und Absenden
        $submit_time = absint( $_POST['_time'] ?? 0 );
        if ( $submit_time && ( time() - $submit_time ) < 3 ) {
            wp_send_json_error( [ 'message' => __( 'Ungültige Anfrage.', 'propstack-re' ) ] );
        }

        // Felder validieren
        $errors   = $this->validate( $_POST );
        if ( ! empty( $errors ) ) {
            wp_send_json_error( [ 'message' => implode( ' ', $errors ), 'errors' => $errors ] );
        }

        // Daten zusammenstellen
        $form_data = $this->collect_form_data( $_POST );
        $post_id   = absint( $_POST['property_id'] ?? 0 );

        // Lead an Propstack senden
        if ( get_option( 'propstack_re_create_contact', '1' ) ) {
            $lead_service = new Propstack_RE_Lead_Service();
            $result       = $lead_service->send_lead( $form_data, $post_id ?: null );

            if ( ! $result['success'] ) {
                // Lead-Fehler ins Log, aber trotzdem Danke-Seite zeigen (kein Fehler für Benutzer)
                Propstack_RE_Logger::error( 'Lead-Übertragung fehlgeschlagen.', 'lead' );
            }
        }

        // Admin-E-Mail
        if ( get_option( 'propstack_re_admin_notification', '1' ) ) {
            $this->send_admin_email( $form_data, $post_id );
        }

        // Danke-Seite URL bestimmen
        $thankyou_page_id = get_option( 'propstack_re_thankyou_page', '' );
        $redirect_url     = $thankyou_page_id
            ? get_permalink( (int) $thankyou_page_id )
            : home_url( '/danke/' );

        if ( $post_id ) {
            $redirect_url = add_query_arg( 'property_id', $post_id, $redirect_url );
        }

        wp_send_json_success( [
            'message'  => __( 'Vielen Dank! Wir melden uns bald.', 'propstack-re' ),
            'redirect' => esc_url( $redirect_url ),
        ] );
    }

    private function validate( array $post ): array {
        $errors = [];

        if ( empty( trim( $post['first_name'] ?? '' ) ) ) {
            $errors['first_name'] = __( 'Vorname ist erforderlich.', 'propstack-re' );
        }
        if ( empty( trim( $post['last_name'] ?? '' ) ) ) {
            $errors['last_name'] = __( 'Nachname ist erforderlich.', 'propstack-re' );
        }
        if ( empty( $post['email'] ) || ! is_email( $post['email'] ) ) {
            $errors['email'] = __( 'Gültige E-Mail-Adresse erforderlich.', 'propstack-re' );
        }
        if ( empty( trim( $post['phone'] ?? '' ) ) ) {
            $errors['phone'] = __( 'Telefonnummer ist erforderlich.', 'propstack-re' );
        }
        if ( empty( $post['privacy'] ) ) {
            $errors['privacy'] = __( 'Datenschutzerklärung muss akzeptiert werden.', 'propstack-re' );
        }

        return $errors;
    }

    private function collect_form_data( array $post ): array {
        return [
            'salutation'     => sanitize_text_field( $post['salutation']   ?? '' ),
            'first_name'     => sanitize_text_field( $post['first_name']   ?? '' ),
            'last_name'      => sanitize_text_field( $post['last_name']    ?? '' ),
            'email'          => sanitize_email( $post['email']             ?? '' ),
            'phone'          => sanitize_text_field( $post['phone']        ?? '' ),
            'message'        => sanitize_textarea_field( $post['message']  ?? '' ),
            'street'         => sanitize_text_field( $post['street']       ?? '' ),
            'zip'            => sanitize_text_field( $post['zip']          ?? '' ),
            'city_contact'   => sanitize_text_field( $post['city_contact'] ?? '' ),
            'financing'      => ! empty( $post['financing'] ) ? '1' : '0',
            'viewing'        => ! empty( $post['viewing'] ) ? '1' : '0',
            'viewing_date'   => sanitize_text_field( $post['viewing_date'] ?? '' ),
            'newsletter'     => ! empty( $post['newsletter'] ) ? '1' : '0',
            // Hidden Fields
            '_property_title'=> sanitize_text_field( $post['property_title'] ?? '' ),
            '_source_url'    => esc_url_raw( $post['source_url']  ?? wp_get_referer() ),
            '_utm_source'    => sanitize_text_field( $post['utm_source']  ?? '' ),
            '_utm_medium'    => sanitize_text_field( $post['utm_medium']  ?? '' ),
            '_utm_campaign'  => sanitize_text_field( $post['utm_campaign']?? '' ),
            '_submitted_at'  => current_time( 'mysql' ),
        ];
    }

    private function send_admin_email( array $data, int $post_id ): void {
        $to      = get_option( 'propstack_re_notification_email', get_option( 'admin_email' ) );
        $subject = sprintf(
            __( 'Neue Immobilien-Anfrage: %s', 'propstack-re' ),
            $data['_property_title'] ?: __( 'Allgemeine Anfrage', 'propstack-re' )
        );

        $body = sprintf(
            "Neue Anfrage über die Website\n\n" .
            "Name: %s %s\n" .
            "E-Mail: %s\n" .
            "Telefon: %s\n" .
            "Nachricht: %s\n\n" .
            "Objekt: %s\n" .
            "Quelle: %s\n" .
            "Datum: %s",
            $data['first_name'],
            $data['last_name'],
            $data['email'],
            $data['phone'],
            $data['message'],
            $post_id ? get_the_title( $post_id ) . ' (' . get_permalink( $post_id ) . ')' : '—',
            $data['_source_url'],
            $data['_submitted_at']
        );

        wp_mail( $to, $subject, $body );
    }
}
