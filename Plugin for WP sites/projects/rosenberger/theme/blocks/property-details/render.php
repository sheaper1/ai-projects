<?php
/**
 * Objektdaten — список аккордеонов.
 * Первый («Eckdaten & Flächen») открыт и содержит 3-колоночную сетку структурных
 * полей (пустые показываются как «—», как в макете). Остальные — свободный текст.
 *
 * @var WP_Block $block
 */

defined( 'ABSPATH' ) || exit;

$post_id = isset( $block->context['postId'] ) ? (int) $block->context['postId'] : get_the_ID();
$get     = fn( $k ) => get_post_meta( $post_id, $k, true );

// Структурная сетка (порядок — построчно, как в макете: 3 колонки).
$grid = array(
	array( 'label' => 'Objektart',                    'key' => 'property_object_type' ),
	array( 'label' => 'Kategorie',                    'key' => 'property_category' ),
	array( 'label' => 'Wohnfläche',                   'key' => 'property_area' ),
	array( 'label' => 'Grundstücksfläche',            'key' => 'property_plot_area' ),
	array( 'label' => 'Nutzfläche (Wohnen)',          'key' => 'property_usable_area' ),
	array( 'label' => 'Zimmer',                       'key' => 'property_rooms' ),
	array( 'label' => 'Schlafzimmer',                 'key' => 'property_bedrooms' ),
	array( 'label' => 'Badezimmer',                   'key' => 'property_bathrooms' ),
	array( 'label' => 'Anzahl Toiletten',             'key' => 'property_toilets' ),
	array( 'label' => 'Balkon/Terrasse Fläche',       'key' => 'property_balcony_area' ),
	array( 'label' => 'Loggiafläche',                 'key' => 'property_loggia_area' ),
	array( 'label' => 'Anzahl Loggia',                'key' => 'property_loggia_count' ),
	array( 'label' => 'Gartenfläche',                 'key' => 'property_garden_area' ),
	array( 'label' => 'Stock',                        'key' => 'property_floor' ),
	array( 'label' => 'Geschossanzahl',               'key' => 'property_floors_total' ),
	array( 'label' => 'Ausrichtung',                  'key' => 'property_orientation' ),
	array( 'label' => 'Ausrichtung (Balkon/Terrasse)', 'key' => 'property_balcony_orientation' ),
	array( 'label' => 'Bodenbelag',                   'key' => 'property_flooring' ),
);

// Свободные аккордеоны (WYSIWYG).
$sections = array(
	'property_acc_condition' => 'Zustand & Zusatzinformationen',
	'property_acc_equipment' => 'Ausstattung',
	'property_acc_layout'    => 'Raumaufteilung',
	'property_acc_prices'    => 'Preise & laufende Kosten',
	'property_acc_energy'    => 'Energieausweis',
);

$arrow = '<span class="property-details__arrow" aria-hidden="true"></span>';
?>
<section <?php echo get_block_wrapper_attributes( array( 'class' => 'property-details' ) ); ?>>
	<div class="property-details__inner">
		<h2 class="property-details__title">Objektdaten</h2>

		<div class="property-details__accordions">

			<!-- Eckdaten & Flächen (открыт) -->
			<div class="property-details__item is-open">
				<button class="property-details__header" type="button" aria-expanded="true" data-toggle>
					<span class="property-details__label">Eckdaten &amp; Flächen</span>
					<?php echo $arrow; // phpcs:ignore ?>
				</button>
				<div class="property-details__panel">
					<div class="property-details__panel-inner">
						<div class="property-details__grid">
							<?php foreach ( $grid as $f ) :
								$val = $get( $f['key'] );
								if ( '' === $val ) {
									$val = '—';
								}
								?>
								<div class="property-details__field">
									<span class="property-details__field-label"><?php echo esc_html( $f['label'] ); ?></span>
									<span class="property-details__field-value"><?php echo esc_html( $val ); ?></span>
								</div>
							<?php endforeach; ?>
						</div>
					</div>
				</div>
			</div>

			<!-- Свободные секции -->
			<?php foreach ( $sections as $key => $heading ) :
				$html = $get( $key );
				?>
				<div class="property-details__item">
					<button class="property-details__header" type="button" aria-expanded="false" data-toggle>
						<span class="property-details__label"><?php echo esc_html( $heading ); ?></span>
						<?php echo $arrow; // phpcs:ignore ?>
					</button>
					<div class="property-details__panel">
						<div class="property-details__panel-inner">
							<div class="property-details__prose">
								<?php
								echo $html
									? wp_kses_post( wpautop( $html ) )
									: '<p class="property-details__empty">Angaben folgen.</p>';
								?>
							</div>
						</div>
					</div>
				</div>
			<?php endforeach; ?>

		</div>
	</div>
</section>
