<?php
/**
 * Ähnliche Referenzen — до N других референсов, похожих на текущий.
 *
 * Подбор (решение по спеке — «matched by which taxonomy»): записи, делящие с
 * текущей ту же Objektart (reference-type) ИЛИ ту же Lage (reference-city),
 * исключая саму запись, новые сверху. Если совпадений меньше N — добиваем
 * самыми свежими прочими референсами, чтобы блок всегда что-то показывал.
 *
 * Карточки — общая функция rosenberger_rc_card_html() (как в каталоге), чтобы
 * разметка/дизайн совпадали.
 *
 * @var WP_Block $block
 *
 * @package rosenberger
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'rosenberger_rc_card_html' ) ) {
	return;
}

$post_id        = isset( $block->context['postId'] ) ? (int) $block->context['postId'] : get_the_ID();
$count          = max( 1, (int) ( $attributes['count'] ?? 3 ) );
$heading        = wp_kses_post( $attributes['heading'] ?? 'Ähnliche' );
$heading_italic = wp_kses_post( $attributes['headingItalic'] ?? 'Referenzen' );

$type_terms = wp_get_post_terms( $post_id, 'reference-type', array( 'fields' => 'ids' ) );
$city_terms = wp_get_post_terms( $post_id, 'reference-city', array( 'fields' => 'ids' ) );

$base_args = array(
	'post_type'           => 'reference',
	'post_status'         => 'publish',
	'posts_per_page'      => $count,
	'post__not_in'        => array( $post_id ),
	'orderby'             => 'date',
	'order'               => 'DESC',
	'ignore_sticky_posts' => true,
	'no_found_rows'       => true,
);

// 1) Похожие по taxonomy (Objektart ИЛИ Lage).
$ids     = array();
$tax_query = array( 'relation' => 'OR' );
if ( ! is_wp_error( $type_terms ) && $type_terms ) {
	$tax_query[] = array( 'taxonomy' => 'reference-type', 'field' => 'term_id', 'terms' => $type_terms );
}
if ( ! is_wp_error( $city_terms ) && $city_terms ) {
	$tax_query[] = array( 'taxonomy' => 'reference-city', 'field' => 'term_id', 'terms' => $city_terms );
}
if ( count( $tax_query ) > 1 ) {
	$similar = get_posts( array_merge( $base_args, array( 'fields' => 'ids', 'tax_query' => $tax_query ) ) );
	$ids     = array_map( 'intval', (array) $similar );
}

// 2) Добор свежими прочими референсами, если совпадений мало.
if ( count( $ids ) < $count ) {
	$fill = get_posts( array_merge( $base_args, array(
		'fields'         => 'ids',
		'posts_per_page' => $count - count( $ids ),
		'post__not_in'   => array_merge( array( $post_id ), $ids ),
	) ) );
	$ids  = array_merge( $ids, array_map( 'intval', (array) $fill ) );
}

if ( ! $ids ) {
	return;
}
?>
<section <?php echo get_block_wrapper_attributes( array( 'class' => 'reference-related' ) ); ?>>
	<div class="reference-related__inner">
		<h2 class="reference-related__heading">
			<?php echo $heading; ?>
			<?php if ( $heading_italic ) : ?><em><?php echo $heading_italic; ?></em><?php endif; ?>
		</h2>
		<div class="reference-related__grid">
			<?php foreach ( $ids as $rid ) { echo rosenberger_rc_card_html( $rid ); } ?>
		</div>
	</div>
</section>
