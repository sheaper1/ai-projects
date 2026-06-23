<?php
/**
 * Lage & Erreichbarkeit: карта (Leaflet/OSM) по адресу или координатам объекта,
 * поверх — карточки достижимости (черновой список из property_poi).
 *
 * @var WP_Block $block
 */

defined( 'ABSPATH' ) || exit;

$post_id = isset( $block->context['postId'] ) ? (int) $block->context['postId'] : get_the_ID();
$get     = fn( $k ) => get_post_meta( $post_id, $k, true );

$address = $get( 'property_address' );
$lat     = $get( 'property_lat' );
$lng     = $get( 'property_lng' );

if ( ! $address && ! ( $lat && $lng ) ) {
	return;
}

// POI: одна строка = «Name | Zeit».
$pois = array();
foreach ( preg_split( '/\r\n|\r|\n/', (string) $get( 'property_poi' ) ) as $line ) {
	$line = trim( $line );
	if ( '' === $line ) {
		continue;
	}
	$parts = array_map( 'trim', explode( '|', $line, 2 ) );
	$pois[] = array( 'label' => $parts[0], 'time' => $parts[1] ?? '' );
}
?>
<section <?php echo get_block_wrapper_attributes( array( 'class' => 'property-location' ) ); ?>>
	<div class="property-location__inner">
		<h2 class="property-location__title">Lage &amp; Erreichbarkeit</h2>

		<div class="property-location__map-wrap">
			<div
				class="property-location__map"
				data-map
				data-lat="<?php echo esc_attr( $lat ); ?>"
				data-lng="<?php echo esc_attr( $lng ); ?>"
				data-address="<?php echo esc_attr( $address ); ?>"
				role="img"
				aria-label="Karte: <?php echo esc_attr( $address ); ?>"
			></div>

			<?php if ( $pois ) : ?>
			<div class="property-location__pois">
				<?php foreach ( $pois as $p ) : ?>
				<div class="property-location__poi">
					<?php if ( $p['label'] ) : ?><span class="property-location__poi-label"><?php echo esc_html( $p['label'] ); ?></span><?php endif; ?>
					<?php if ( $p['time'] ) : ?><span class="property-location__poi-time"><?php echo esc_html( $p['time'] ); ?></span><?php endif; ?>
				</div>
				<?php endforeach; ?>
			</div>
			<?php endif; ?>
		</div>
	</div>
</section>
