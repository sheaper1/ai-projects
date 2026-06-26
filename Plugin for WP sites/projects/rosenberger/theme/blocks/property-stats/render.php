<?php
/**
 * Полоса ключевых показателей объекта: 6 карточек (иконка + лейбл + значение).
 * Пустые поля пропускаются; сетка достраивается сама.
 *
 * @var WP_Block $block
 */

defined( 'ABSPATH' ) || exit;

$post_id = isset( $block->context['postId'] ) ? (int) $block->context['postId'] : get_the_ID();
$icons   = get_stylesheet_directory_uri() . '/assets/property/icons/';

$stats = array(
	array( 'icon' => 'area.svg',      'label' => 'Wohnfläche',   'key' => 'property_area' ),
	array( 'icon' => 'rooms.svg',     'label' => 'Zimmer',       'key' => 'property_rooms' ),
	array( 'icon' => 'bedrooms.svg',  'label' => 'Schlafzimmer', 'key' => 'property_bedrooms' ),
	array( 'icon' => 'bathrooms.svg', 'label' => 'Badezimmer',   'key' => 'property_bathrooms' ),
	array( 'icon' => 'floor.svg',     'label' => 'Stock',        'key' => 'property_floor' ),
	array( 'icon' => 'year.svg',      'label' => 'Baujahr',      'key' => 'property_year' ),
);

$is_ref = 'reference' === get_post_type( $post_id );

// Для референсов (часто Grundstück без жилых показателей) — добавляем Grundstücksfläche.
if ( $is_ref ) {
	$stats[] = array( 'icon' => 'area.svg', 'label' => 'Grundstücksfläche', 'key' => 'property_plot_area' );
}

$cards = array();
foreach ( $stats as $s ) {
	$value = get_post_meta( $post_id, $s['key'], true );
	// Этаж из Propstack приходит словом («2. Obergeschoss») — сокращаем как в макете
	// (Figma «2. OG»): Obergeschoss→OG, Untergeschoss→UG, Erdgeschoss→EG, Dachgeschoss→DG.
	if ( 'property_floor' === $s['key'] && '' !== $value ) {
		$value = str_replace(
			array( 'Obergeschoss', 'Untergeschoss', 'Erdgeschoss', 'Dachgeschoss', 'Obergeschoß', 'Untergeschoß' ),
			array( 'OG', 'UG', 'EG', 'DG', 'OG', 'UG' ),
			$value
		);
	}
	if ( $is_ref ) {
		// Референс: только заполненные показатели.
		if ( '' === $value ) {
			continue;
		}
		$s['value'] = $value;
	} else {
		// Объекты: ровная сетка 3×2 — всегда 6 ячеек, пустые как «—».
		$s['value'] = ( '' !== $value ) ? $value : '—';
	}
	$cards[] = $s;
}
if ( ! $is_ref ) {
	$cards = array_slice( $cards, 0, 6 );
}
if ( ! $cards ) {
	return;
}
?>
<section <?php echo get_block_wrapper_attributes( array( 'class' => 'property-stats' ) ); ?>>
	<div class="property-stats__inner">
		<?php foreach ( $cards as $c ) : ?>
		<div class="property-stats__card">
			<img class="property-stats__icon" src="<?php echo esc_url( $icons . $c['icon'] ); ?>" alt="" width="40" height="40" />
			<div class="property-stats__text">
				<span class="property-stats__label"><?php echo esc_html( $c['label'] ); ?></span>
				<span class="property-stats__value"><?php echo esc_html( $c['value'] ); ?></span>
			</div>
		</div>
		<?php endforeach; ?>
	</div>
</section>
