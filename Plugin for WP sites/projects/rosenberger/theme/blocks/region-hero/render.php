<?php
/**
 * Region Hero — «Immobilienmakler in {Ort}» + подзаголовок + кнопка + фото.
 * Берёт текущую запись region: title = название города, подзаголовок/кнопка/note
 * из меты, фото — featured image.
 *
 * @var array    $attributes
 * @var WP_Block $block
 * @package rosenberger
 */

defined( 'ABSPATH' ) || exit;

$post_id = isset( $block->context['postId'] ) ? (int) $block->context['postId'] : get_the_ID();
if ( ! $post_id ) {
	return;
}

$prefix   = wp_kses_post( $attributes['headingPrefix'] ?? 'Immobilienmakler in' );
$city     = get_the_title( $post_id );
$subtitle = get_post_meta( $post_id, 'region_subtitle', true )
	?: sprintf( 'Ehrlich beraten in %s, ob Sie verkaufen, kaufen oder den Wert Ihrer Immobilie wissen wollen.', get_the_title( $post_id ) );
$btn_text = get_post_meta( $post_id, 'region_button_text', true ) ?: 'Kostenlos beraten lassen';
$btn_url  = get_post_meta( $post_id, 'region_button_url', true ) ?: '/kontakt/';
$note     = get_post_meta( $post_id, 'region_note', true ) ?: 'Unverbindlich und kostenlos';

$thumb_id  = get_post_thumbnail_id( $post_id );
$thumb_src = $thumb_id ? wp_get_attachment_image_url( $thumb_id, 'full' ) : '';
?>
<section <?php echo get_block_wrapper_attributes( array( 'class' => 'region-hero' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="region-hero__head">
		<h1 class="region-hero__title"><?php echo esc_html( $prefix ); ?> <em><?php echo esc_html( $city ); ?></em></h1>
		<?php if ( $subtitle ) : ?>
			<p class="region-hero__subtitle"><?php echo wp_kses_post( $subtitle ); ?></p>
		<?php endif; ?>
		<div class="region-hero__cta">
			<a class="region-hero__button" href="<?php echo esc_url( $btn_url ); ?>"><?php echo esc_html( $btn_text ); ?></a>
			<?php if ( $note ) : ?>
				<p class="region-hero__note"><?php echo esc_html( $note ); ?></p>
			<?php endif; ?>
		</div>
	</div>
	<div class="region-hero__image">
		<?php if ( $thumb_src ) : ?>
			<img src="<?php echo esc_url( $thumb_src ); ?>" alt="<?php echo esc_attr( $city ); ?>">
		<?php endif; ?>
	</div>
</section>
