<?php
defined( 'ABSPATH' ) || exit;

$a       = wp_parse_args( $attributes, [ 'heading' => '', 'lead' => '', 'wpformsId' => 0 ] );
$funnel_file = __DIR__ . '/assets/funnel.html';
$wrapper = get_block_wrapper_attributes( [ 'class' => 'tipper-form' ] );

// WPForms-форма: явный wpformsId → форма по slug «tippgeber» → первая существующая.
$form_id = (int) $a['wpformsId'];
if ( ! $form_id ) {
	$f = get_posts( [ 'post_type' => 'wpforms', 'name' => 'tippgeber', 'posts_per_page' => 1, 'post_status' => 'publish' ] );
	if ( empty( $f ) ) {
		$f = get_posts( [ 'post_type' => 'wpforms', 'posts_per_page' => 1, 'post_status' => 'publish', 'orderby' => 'date', 'order' => 'ASC' ] );
	}
	if ( ! empty( $f ) ) {
		$form_id = $f[0]->ID;
	}
}

// Маппинг funnel-slug → WPForms field-ID (по label поля) — для JS-моста к скрытой форме.
$field_map = [];
if ( $form_id ) {
	$fp = get_post( $form_id );
	$fd = $fp ? ( function_exists( 'wpforms_decode' ) ? wpforms_decode( $fp->post_content ) : json_decode( $fp->post_content, true ) ) : null;
	$by_label = [
		'anrede'   => 'Anrede',
		'vorname'  => 'Vorname',
		'nachname' => 'Nachname',
		'email'    => 'Email',
		'telefon'  => 'Telefon',
		'plz'      => 'PLZ',
		'summary'  => 'Objektangaben',
	];
	if ( ! empty( $fd['fields'] ) ) {
		foreach ( $fd['fields'] as $fid => $field ) {
			$label = isset( $field['label'] ) ? $field['label'] : '';
			foreach ( $by_label as $slug => $want ) {
				if ( strcasecmp( $label, $want ) === 0 ) {
					$field_map[ $slug ] = (int) $fid;
				}
			}
		}
	}
}

$has_form = $form_id && function_exists( 'wpforms' ) && ! empty( $field_map );
?>
<section <?php echo $wrapper; ?>>
	<div class="tipper-form__header">
		<?php if ( $a['heading'] ) : ?>
			<h2 class="tipper-form__heading"><?php echo esc_html( $a['heading'] ); ?></h2>
		<?php endif; ?>
		<?php if ( $a['lead'] ) : ?>
			<p class="tipper-form__lead"><?php echo esc_html( $a['lead'] ); ?></p>
		<?php endif; ?>
	</div>

	<?php if ( $has_form ) : ?>
		<script>
			window.RB_TIPPER = {
				formId: <?php echo (int) $form_id; ?>,
				field: <?php echo wp_json_encode( $field_map ); ?>
			};
		</script>
	<?php endif; ?>

	<div class="tipper-form__funnel">
		<?php if ( file_exists( $funnel_file ) ) : ?>
			<?php echo file_get_contents( $funnel_file ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<?php endif; ?>
	</div>

	<?php if ( $has_form ) : ?>
		<div aria-hidden="true" style="position:absolute;left:-99999px;top:0;width:1px;height:1px;overflow:hidden">
			<?php echo do_shortcode( '[wpforms id="' . $form_id . '" title="false" description="false"]' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</div>
	<?php endif; ?>
</section>
