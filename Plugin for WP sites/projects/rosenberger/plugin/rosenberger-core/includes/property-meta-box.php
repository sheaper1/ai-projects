<?php
/**
 * Meta box «Objekt-Details» для CPT property.
 * Поля: Kaufpreis, Wohnfläche, Zimmer, Status.
 *
 * @package rosenberger-core
 */

defined( 'ABSPATH' ) || exit;

add_action( 'add_meta_boxes', function () {
	add_meta_box(
		'property-details',
		'Objekt-Details',
		'rosenberger_property_meta_box_html',
		'property',
		'normal',
		'high'
	);
} );

function rosenberger_property_meta_box_html( WP_Post $post ): void {
	wp_nonce_field( 'save_property_meta', 'property_meta_nonce' );

	$price  = get_post_meta( $post->ID, 'property_price', true );
	$area   = get_post_meta( $post->ID, 'property_area', true );
	$plot   = get_post_meta( $post->ID, 'property_plot_area', true );
	$rooms  = get_post_meta( $post->ID, 'property_rooms', true );
	$status = get_post_meta( $post->ID, 'property_status', true ) ?: 'Verfügbar';
	?>
	<style>
	.pmb-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; padding: 8px 0 4px; }
	.pmb-grid label { display: block; font-weight: 600; font-size: 12px; text-transform: uppercase;
	                   letter-spacing: .05em; color: #747c86; margin-bottom: 4px; }
	.pmb-grid input, .pmb-grid select { width: 100%; box-sizing: border-box; }
	</style>
	<div class="pmb-grid">
		<div>
			<label for="property_price">Kaufpreis</label>
			<input type="text" id="property_price" name="property_price"
			       value="<?php echo esc_attr( $price ); ?>"
			       placeholder="z. B. Auf Anfrage oder € 450.000" />
		</div>
		<div>
			<label for="property_rooms">Zimmer</label>
			<input type="text" id="property_rooms" name="property_rooms"
			       value="<?php echo esc_attr( $rooms ); ?>"
			       placeholder="z. B. 4 oder 4,5" />
		</div>
		<div>
			<label for="property_area">Wohnfläche</label>
			<input type="text" id="property_area" name="property_area"
			       value="<?php echo esc_attr( $area ); ?>"
			       placeholder="z. B. ca. 130 m²" />
		</div>
		<div>
			<label for="property_plot_area">Grundstücksfläche</label>
			<input type="text" id="property_plot_area" name="property_plot_area"
			       value="<?php echo esc_attr( $plot ); ?>"
			       placeholder="z. B. ca. 500 m²" />
		</div>
		<div>
			<label for="property_status">Status</label>
			<select id="property_status" name="property_status">
				<?php foreach ( array( 'Verfügbar', 'Reserviert', 'Verkauft' ) as $opt ) : ?>
					<option value="<?php echo esc_attr( $opt ); ?>"
					        <?php selected( $status, $opt ); ?>>
						<?php echo esc_html( $opt ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</div>
	</div>
	<?php
}

add_action( 'save_post_property', function ( int $post_id ): void {
	if (
		! isset( $_POST['property_meta_nonce'] ) ||
		! wp_verify_nonce( sanitize_key( $_POST['property_meta_nonce'] ), 'save_property_meta' ) ||
		( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ||
		! current_user_can( 'edit_post', $post_id )
	) {
		return;
	}

	$map = array(
		'property_price'     => 'property_price',
		'property_area'      => 'property_area',
		'property_plot_area' => 'property_plot_area',
		'property_rooms'     => 'property_rooms',
		'property_status'    => 'property_status',
	);

	foreach ( $map as $meta_key => $field ) {
		if ( isset( $_POST[ $field ] ) ) {
			update_post_meta(
				$post_id,
				$meta_key,
				sanitize_text_field( wp_unslash( $_POST[ $field ] ) )
			);
		}
	}
} );
