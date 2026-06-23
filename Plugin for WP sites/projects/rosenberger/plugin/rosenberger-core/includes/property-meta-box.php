<?php
/**
 * Meta box «Objekt-Details» для CPT property.
 * Схема полей — в property-fields.php (один источник). Здесь только рендер формы
 * и сохранение. Поддерживает text/textarea/wysiwyg/select/gallery.
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

// Медиа-скрипты для галереи в редакторе объекта.
add_action( 'admin_enqueue_scripts', function ( $hook ) {
	$screen = get_current_screen();
	if ( $screen && 'property' === $screen->post_type && in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
		wp_enqueue_media();
	}
} );

function rosenberger_property_meta_box_html( WP_Post $post ): void {
	wp_nonce_field( 'save_property_meta', 'property_meta_nonce' );
	$groups = rosenberger_property_field_groups();
	?>
	<style>
	.pmb-section { margin: 18px 0 4px; font-size: 13px; text-transform: uppercase; letter-spacing: .05em;
	               color: #142335; border-bottom: 1px solid #e0e0e0; padding-bottom: 6px; }
	.pmb-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px 20px; padding: 8px 0 4px; }
	.pmb-field--full { grid-column: 1 / -1; }
	.pmb-field label { display: block; font-weight: 600; font-size: 12px; color: #747c86; margin-bottom: 4px; }
	.pmb-field input[type=text], .pmb-field select, .pmb-field textarea { width: 100%; box-sizing: border-box; }
	.pmb-field textarea { min-height: 70px; }
	.pmb-gallery__preview { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 8px; }
	.pmb-gallery__preview img { width: 84px; height: 64px; object-fit: cover; border-radius: 4px; border: 1px solid #ddd; }
	</style>

	<?php foreach ( $groups as $section => $fields ) : ?>
		<p class="pmb-section"><?php echo esc_html( $section ); ?></p>
		<div class="pmb-grid">
			<?php foreach ( $fields as $key => $def ) :
				$type  = $def['type'] ?? 'text';
				$value = get_post_meta( $post->ID, $key, true );
				$full  = in_array( $type, array( 'textarea', 'wysiwyg', 'gallery' ), true );
			?>
			<div class="pmb-field <?php echo $full ? 'pmb-field--full' : ''; ?>">
				<label for="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $def['label'] ); ?></label>
				<?php if ( 'select' === $type ) : ?>
					<select id="<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( $key ); ?>">
						<?php
						$selected = '' !== $value ? $value : ( $def['default'] ?? '' );
						foreach ( $def['options'] as $opt ) :
							?>
							<option value="<?php echo esc_attr( $opt ); ?>" <?php selected( $selected, $opt ); ?>><?php echo esc_html( $opt ); ?></option>
						<?php endforeach; ?>
					</select>
				<?php elseif ( 'textarea' === $type ) : ?>
					<textarea id="<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( $key ); ?>"
					          placeholder="<?php echo esc_attr( $def['ph'] ?? '' ); ?>"><?php echo esc_textarea( $value ); ?></textarea>
				<?php elseif ( 'wysiwyg' === $type ) : ?>
					<?php
					wp_editor(
						$value,
						$key,
						array(
							'textarea_name' => $key,
							'textarea_rows' => 5,
							'media_buttons' => false,
							'teeny'         => true,
						)
					);
					?>
				<?php elseif ( 'gallery' === $type ) : ?>
					<div class="pmb-gallery" data-target="<?php echo esc_attr( $key ); ?>">
						<div class="pmb-gallery__preview"></div>
						<input type="hidden" id="<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $value ); ?>" />
						<button type="button" class="button pmb-gallery__choose">Bilder auswählen</button>
						<button type="button" class="button-link pmb-gallery__clear">Leeren</button>
					</div>
				<?php else : ?>
					<input type="text" id="<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( $key ); ?>"
					       value="<?php echo esc_attr( $value ); ?>" placeholder="<?php echo esc_attr( $def['ph'] ?? '' ); ?>" />
				<?php endif; ?>
			</div>
			<?php endforeach; ?>
		</div>
	<?php endforeach; ?>

	<script>
	( function () {
		document.querySelectorAll( '.pmb-gallery' ).forEach( function ( box ) {
			var input   = box.querySelector( 'input[type=hidden]' );
			var preview = box.querySelector( '.pmb-gallery__preview' );
			function render() {
				preview.innerHTML = '';
				( input.value ? input.value.split( ',' ) : [] ).forEach( function ( id ) {
					var att = wp.media.attachment( id );
					att.fetch().then( function () {
						var img = document.createElement( 'img' );
						var s = att.get( 'sizes' );
						img.src = ( s && s.thumbnail ? s.thumbnail.url : att.get( 'url' ) );
						preview.appendChild( img );
					} );
				} );
			}
			box.querySelector( '.pmb-gallery__choose' ).addEventListener( 'click', function ( e ) {
				e.preventDefault();
				var ids = input.value ? input.value.split( ',' ) : [];
				var frame = wp.media( { title: 'Galerie', multiple: true, library: { type: 'image' },
					button: { text: 'Übernehmen' } } );
				frame.on( 'open', function () {
					var sel = frame.state().get( 'selection' );
					ids.forEach( function ( id ) {
						var a = wp.media.attachment( id ); a.fetch(); sel.add( a );
					} );
				} );
				frame.on( 'select', function () {
					input.value = frame.state().get( 'selection' ).map( function ( a ) { return a.id; } ).join( ',' );
					render();
				} );
				frame.open();
			} );
			box.querySelector( '.pmb-gallery__clear' ).addEventListener( 'click', function ( e ) {
				e.preventDefault(); input.value = ''; render();
			} );
			render();
		} );
	}() );
	</script>
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

	foreach ( rosenberger_property_fields() as $key => $def ) {
		if ( isset( $_POST[ $key ] ) ) {
			$raw = wp_unslash( $_POST[ $key ] );
			update_post_meta( $post_id, $key, rosenberger_property_sanitize( $def['type'] ?? 'text', $raw ) );
		}
	}
} );
