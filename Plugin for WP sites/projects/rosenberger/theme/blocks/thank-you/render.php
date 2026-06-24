<?php
/** Dynamic render for Thank You page. @package library */
defined( 'ABSPATH' ) || exit;

$a = wp_parse_args( $attributes ?? array(), array(
	'headingItalic' => '',
	'headingRest'   => '',
	'lead'          => '',
	'buttonText'    => '',
	'buttonUrl'     => '/',
	'cards'         => array(),
) );
$cards = is_array( $a['cards'] ) ? $a['cards'] : array();
?>
<section <?php echo get_block_wrapper_attributes( array( 'class' => 'thank-you' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="thank-you__hero">
		<div class="thank-you__head">
			<h1 class="thank-you__heading"><em><?php echo esc_html( $a['headingItalic'] ); ?></em><br><?php echo esc_html( $a['headingRest'] ); ?></h1>
			<?php if ( $a['lead'] ) : ?>
				<p class="thank-you__lead"><?php echo wp_kses_post( $a['lead'] ); ?></p>
			<?php endif; ?>
			<?php if ( $a['buttonText'] ) : ?>
				<a class="thank-you__button" href="<?php echo esc_url( $a['buttonUrl'] ); ?>"><?php echo esc_html( $a['buttonText'] ); ?></a>
			<?php endif; ?>
		</div>
	</div>

	<?php if ( ! empty( $cards ) ) : ?>
		<div class="thank-you__cards-band">
			<div class="thank-you__cards">
				<?php foreach ( $cards as $card ) :
					$icon_url = ! empty( $card['iconUrl'] ) ? $card['iconUrl'] : ( ! empty( $card['iconId'] ) ? wp_get_attachment_url( (int) $card['iconId'] ) : '' );
					?>
					<article class="ty-card">
						<?php if ( $icon_url ) : ?>
							<div class="ty-card__icon"><img src="<?php echo esc_url( $icon_url ); ?>" alt="" width="64" height="64" aria-hidden="true" /></div>
						<?php endif; ?>
						<div class="ty-card__body">
							<?php if ( ! empty( $card['title'] ) ) : ?>
								<h3 class="ty-card__title"><?php echo esc_html( $card['title'] ); ?></h3>
							<?php endif; ?>
							<?php if ( ! empty( $card['text'] ) ) : ?>
								<p class="ty-card__text"><?php echo esc_html( $card['text'] ); ?></p>
							<?php endif; ?>
						</div>
					</article>
				<?php endforeach; ?>
			</div>
		</div>
	<?php endif; ?>
</section>
