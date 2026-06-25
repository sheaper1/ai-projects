<?php
defined( 'ABSPATH' ) || exit;
/** @var array $prop */
/** @var int   $property_id */

// Importierte WP-Bilder bevorzugen
$gallery_ids = get_post_meta( $property_id, '_property_gallery_ids', true );
$images      = [];

if ( is_array( $gallery_ids ) && ! empty( $gallery_ids ) ) {
    foreach ( $gallery_ids as $id ) {
        $src = wp_get_attachment_image_src( $id, 'large' );
        if ( $src ) {
            $images[] = [
                'url'   => $src[0],
                'thumb' => wp_get_attachment_image_src( $id, 'medium' )[0] ?? $src[0],
                'alt'   => get_post_meta( $id, '_wp_attachment_image_alt', true ) ?: get_the_title( $property_id ),
            ];
        }
    }
} elseif ( ! empty( $prop['property_gallery'] ) ) {
    foreach ( $prop['property_gallery'] as $img ) {
        $images[] = [
            'url'   => $img['url'] ?? '',
            'thumb' => $img['thumb'] ?? $img['url'] ?? '',
            'alt'   => $img['title'] ?? get_the_title( $property_id ),
        ];
    }
}

if ( empty( $images ) ) {
    return;
}
?>
<div class="propstack-detail__gallery propstack-gallery" data-count="<?php echo count( $images ); ?>">
    <div class="propstack-gallery__main">
        <img src="<?php echo esc_url( $images[0]['url'] ); ?>" alt="<?php echo esc_attr( $images[0]['alt'] ); ?>" loading="eager" class="propstack-gallery__main-img" id="propstack-gallery-main-<?php echo esc_attr( $property_id ); ?>">
        <?php if ( count( $images ) > 1 ) : ?>
        <button class="propstack-gallery__nav propstack-gallery__nav--prev" aria-label="<?php esc_attr_e( 'Vorheriges Bild', 'propstack-re' ); ?>">&#8249;</button>
        <button class="propstack-gallery__nav propstack-gallery__nav--next" aria-label="<?php esc_attr_e( 'Nächstes Bild', 'propstack-re' ); ?>">&#8250;</button>
        <span class="propstack-gallery__counter"><span class="propstack-gallery__current">1</span> / <?php echo count( $images ); ?></span>
        <?php endif; ?>
    </div>

    <?php if ( count( $images ) > 1 ) : ?>
    <div class="propstack-gallery__thumbs">
        <?php foreach ( $images as $i => $img ) : ?>
        <button class="propstack-gallery__thumb <?php echo $i === 0 ? 'propstack-gallery__thumb--active' : ''; ?>" data-index="<?php echo $i; ?>">
            <img src="<?php echo esc_url( $img['thumb'] ); ?>" alt="" loading="lazy">
        </button>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- JSON für JS -->
    <script type="application/json" id="propstack-gallery-data-<?php echo esc_attr( $property_id ); ?>">
    <?php echo wp_json_encode( array_map( fn( $img ) => [ 'url' => $img['url'], 'alt' => $img['alt'] ], $images ) ); ?>
    </script>
</div>
