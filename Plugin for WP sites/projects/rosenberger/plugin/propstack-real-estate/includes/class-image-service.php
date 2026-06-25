<?php
defined( 'ABSPATH' ) || exit;

class Propstack_RE_Image_Service {

    private int $max_images;

    public function __construct() {
        $this->max_images = (int) get_option( 'propstack_re_max_images', 20 );
    }

    /**
     * Importiert alle Bilder eines Objekts in die WP-Mediathek.
     * Gibt Array von Attachment-IDs zurück.
     */
    public function import_images( int $post_id, array $images ): array {
        if ( empty( $images ) ) {
            return [];
        }

        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $images     = array_slice( $images, 0, $this->max_images );
        $attachment_ids = [];
        $existing   = $this->get_existing_attachments( $post_id );

        foreach ( $images as $image ) {
            $url = $image['url'] ?? '';
            if ( ! $url ) {
                continue;
            }

            // Bereits importiert? (anhand Propstack-Bild-URL)
            $existing_id = $this->find_by_source_url( $url, $existing );
            if ( $existing_id ) {
                $attachment_ids[] = $existing_id;
                continue;
            }

            $attachment_id = $this->sideload_image( $url, $post_id, $image['title'] ?? '' );
            if ( $attachment_id && ! is_wp_error( $attachment_id ) ) {
                // Propstack-URL als Meta speichern für spätere Erkennung
                update_post_meta( $attachment_id, '_propstack_source_url', esc_url_raw( $url ) );
                $attachment_ids[] = $attachment_id;
            }
        }

        // Erstes Bild als Featured Image setzen
        if ( ! empty( $attachment_ids ) ) {
            $current_thumb = get_post_thumbnail_id( $post_id );
            if ( ! $current_thumb || ! in_array( $current_thumb, $attachment_ids, true ) ) {
                set_post_thumbnail( $post_id, $attachment_ids[0] );
            }
        }

        // Galerie-Meta aktualisieren
        update_post_meta( $post_id, '_property_gallery_ids', $attachment_ids );

        return $attachment_ids;
    }

    private function sideload_image( string $url, int $post_id, string $alt = '' ): int|WP_Error {
        // Temporäre Datei herunterladen
        $tmp = download_url( $url, 60 );
        if ( is_wp_error( $tmp ) ) {
            Propstack_RE_Logger::warning(
                'Bild-Download fehlgeschlagen: ' . $url . ' — ' . $tmp->get_error_message(),
                'images'
            );
            return $tmp;
        }

        $filename  = basename( parse_url( $url, PHP_URL_PATH ) );
        $file_array = [
            'name'     => sanitize_file_name( $filename ?: 'propstack-image.jpg' ),
            'tmp_name' => $tmp,
        ];

        $attachment_id = media_handle_sideload( $file_array, $post_id, $alt );

        @unlink( $tmp );

        if ( is_wp_error( $attachment_id ) ) {
            Propstack_RE_Logger::warning(
                'Bild-Import fehlgeschlagen: ' . $url . ' — ' . $attachment_id->get_error_message(),
                'images'
            );
        } else {
            // Alt-Text setzen
            if ( $alt ) {
                update_post_meta( $attachment_id, '_wp_attachment_image_alt', sanitize_text_field( $alt ) );
            }
        }

        return $attachment_id;
    }

    private function get_existing_attachments( int $post_id ): array {
        $ids = get_post_meta( $post_id, '_property_gallery_ids', true );
        return is_array( $ids ) ? $ids : [];
    }

    private function find_by_source_url( string $url, array $attachment_ids ): int {
        foreach ( $attachment_ids as $id ) {
            $source = get_post_meta( $id, '_propstack_source_url', true );
            if ( $source === $url ) {
                return (int) $id;
            }
        }
        return 0;
    }

    /**
     * Löscht alle importierten Bilder eines Objekts.
     */
    public function delete_images( int $post_id ): void {
        $ids = get_post_meta( $post_id, '_property_gallery_ids', true );
        if ( ! is_array( $ids ) ) {
            return;
        }
        foreach ( $ids as $attachment_id ) {
            wp_delete_attachment( $attachment_id, true );
        }
        delete_post_meta( $post_id, '_property_gallery_ids' );
        delete_post_thumbnail( $post_id );
    }
}
