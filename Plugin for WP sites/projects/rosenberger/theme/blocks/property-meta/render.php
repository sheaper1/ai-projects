<?php
/**
 * Рендер блока rosenberger/property-meta.
 * Показывает 2×2 мета-сетку карточки объекта: Lage, Kaufpreis, Wohnfläche, Zimmer.
 * Работает и в архиве (wp:query loop), и на странице сингла.
 *
 * @var array    $attributes Атрибуты блока.
 * @var string   $content    Контент InnerBlocks (не используется).
 * @var WP_Block $block      Объект блока (содержит context с postId).
 */

$post_id = isset( $block->context['postId'] ) ? (int) $block->context['postId'] : get_the_ID();

$price  = get_post_meta( $post_id, 'property_price', true );
$area   = get_post_meta( $post_id, 'property_area', true );
$rooms  = get_post_meta( $post_id, 'property_rooms', true );
$status = get_post_meta( $post_id, 'property_status', true );

$city_terms = get_the_terms( $post_id, 'property-city' );
$location   = '';
if ( $city_terms && ! is_wp_error( $city_terms ) ) {
	$location = implode( ' | ', wp_list_pluck( $city_terms, 'name' ) );
}

// Поля в нужном порядке (как в Figma: Lage | Kaufpreis / Wohnfläche | Zimmer)
$fields = array(
	array( 'label' => 'Lage',       'value' => $location ),
	array( 'label' => 'Kaufpreis',  'value' => $price ),
	array( 'label' => 'Wohnfläche', 'value' => $area ),
	array( 'label' => 'Zimmer',     'value' => $rooms ),
);

// Выводим только если есть хотя бы одно заполненное поле
if ( ! array_filter( array_column( $fields, 'value' ) ) ) {
	return;
}
?>
<div <?php echo get_block_wrapper_attributes( array( 'class' => 'property-meta' ) ); ?>>

	<div class="property-meta__grid">
		<?php foreach ( $fields as $field ) :
			if ( empty( $field['value'] ) ) continue;
		?>
		<div class="property-meta__item">
			<span class="property-meta__label"><?php echo esc_html( $field['label'] ); ?></span>
			<span class="property-meta__value"><?php echo esc_html( $field['value'] ); ?></span>
		</div>
		<?php endforeach; ?>
	</div>

	<?php if ( $status && 'Verfügbar' !== $status ) : ?>
	<span class="property-meta__status property-meta__status--<?php echo esc_attr( strtolower( $status ) ); ?>">
		<?php echo esc_html( $status ); ?>
	</span>
	<?php endif; ?>

</div>
