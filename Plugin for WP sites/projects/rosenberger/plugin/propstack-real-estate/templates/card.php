<?php
defined( 'ABSPATH' ) || exit;
/** @var int $post_id */
$prop = propstack_get_property( $post_id );
$permalink = get_permalink( $post_id );
$title     = get_the_title( $post_id );

// Bild
$thumbnail_id  = get_post_thumbnail_id( $post_id );
$thumbnail_src = $thumbnail_id
    ? wp_get_attachment_image_url( $thumbnail_id, 'large' )
    : ( $prop['property_featured_image_url'] ?: '' );

// Preis
$price_display = $prop['property_price_display'] ?: __( 'Auf Anfrage', 'propstack-re' );

// Status-Badge
$status_label = $prop['propstack_status'] ? propstack_get_status_label( $prop['propstack_status'] ) : '';
$status_slug  = sanitize_key( $prop['propstack_status'] ?? '' );
?>
<article class="propstack-card" data-post-id="<?php echo esc_attr( $post_id ); ?>">
    <a href="<?php echo esc_url( $permalink ); ?>" class="propstack-card__image-link" tabindex="-1" aria-hidden="true">
        <div class="propstack-card__image">
            <?php if ( $thumbnail_src ) : ?>
                <img src="<?php echo esc_url( $thumbnail_src ); ?>" alt="<?php echo esc_attr( $title ); ?>" loading="lazy">
            <?php else : ?>
                <div class="propstack-card__image-placeholder">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                </div>
            <?php endif; ?>

            <?php if ( $status_label ) : ?>
            <span class="propstack-card__status propstack-card__status--<?php echo esc_attr( $status_slug ); ?>">
                <?php echo esc_html( $status_label ); ?>
            </span>
            <?php endif; ?>
        </div>
    </a>

    <div class="propstack-card__content">
        <h3 class="propstack-card__title">
            <a href="<?php echo esc_url( $permalink ); ?>"><?php echo esc_html( $title ); ?></a>
        </h3>

        <?php if ( $prop['property_short_description'] ) : ?>
        <p class="propstack-card__desc"><?php echo esc_html( wp_trim_words( $prop['property_short_description'], 15 ) ); ?></p>
        <?php endif; ?>

        <div class="propstack-card__meta">
            <?php if ( $prop['property_city'] ) : ?>
            <span class="propstack-card__meta-item propstack-card__meta-city">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>
                <?php echo esc_html( $prop['property_city'] ); ?>
            </span>
            <?php endif; ?>

            <?php if ( $prop['property_living_area'] ) : ?>
            <span class="propstack-card__meta-item">
                <?php echo esc_html( propstack_format_area( (float) $prop['property_living_area'] ) ); ?>
            </span>
            <?php endif; ?>

            <?php if ( $prop['property_rooms'] ) : ?>
            <span class="propstack-card__meta-item">
                <?php printf( _n( '%s Zimmer', '%s Zimmer', (float) $prop['property_rooms'], 'propstack-re' ), esc_html( $prop['property_rooms'] ) ); ?>
            </span>
            <?php endif; ?>
        </div>

        <div class="propstack-card__price"><?php echo esc_html( $price_display ); ?></div>

        <a href="<?php echo esc_url( $permalink ); ?>" class="propstack-card__link propstack-btn propstack-btn--primary">
            <?php esc_html_e( 'Details ansehen', 'propstack-re' ); ?>
        </a>
    </div>
</article>
