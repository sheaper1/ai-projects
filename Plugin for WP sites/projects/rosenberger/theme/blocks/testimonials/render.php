<?php
/** Dynamic render for Testimonials (Google reviews via grw). @package library */
defined( 'ABSPATH' ) || exit;

$a = wp_parse_args( $attributes, [
	'heading'       => '',
	'headingItalic' => '',
	'limit'         => 3,
	'minRating'     => 4,
] );

$reviews = function_exists( 'rosenberger_google_reviews_positive' )
	? rosenberger_google_reviews_positive( (int) $a['minRating'] )
	: [];

if ( $a['limit'] > 0 ) {
	$reviews = array_slice( $reviews, 0, (int) $a['limit'] );
}

if ( empty( $reviews ) ) {
	return;
}

/** Ряд из 5 звёзд, заполненных по рейтингу. Золото — инлайн (бренд, не токен). */
$stars = static function ( $rating ) {
	$pct = max( 0, min( 100, ( (int) $rating / 5 ) * 100 ) );
	return '<span class="testimonials__stars" aria-label="' . esc_attr( $rating ) . ' von 5">'
		. '<span class="testimonials__stars-empty">★★★★★</span>'
		. '<span class="testimonials__stars-full" style="width:' . esc_attr( $pct ) . '%;color:#FBBC04">★★★★★</span>'
		. '</span>';
};
?>
<section <?php echo get_block_wrapper_attributes( [ 'class' => 'testimonials' ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="testimonials__inner">
		<h2 class="testimonials__heading">
			<?php echo wp_kses_post( $a['heading'] ); ?>
			<?php if ( $a['headingItalic'] ) : ?><br><em><?php echo wp_kses_post( $a['headingItalic'] ); ?></em><?php endif; ?>
		</h2>
		<div class="testimonials__carousel">
			<div class="testimonials__track">
				<?php foreach ( $reviews as $r ) : ?>
					<article class="testimonials__card">
						<div class="testimonials__head">
							<div class="testimonials__avatar">
								<?php if ( ! empty( $r['avatar'] ) ) : ?>
									<img src="<?php echo esc_url( $r['avatar'] ); ?>" alt="<?php echo esc_attr( $r['name'] ); ?>" loading="lazy" />
								<?php else : ?>
									<span class="testimonials__avatar-fallback"><?php echo esc_html( mb_substr( $r['name'], 0, 1 ) ); ?></span>
								<?php endif; ?>
							</div>
							<div class="testimonials__who">
								<span class="testimonials__name"><?php echo esc_html( $r['name'] ); ?></span>
								<?php if ( ! empty( $r['time'] ) ) : ?>
									<span class="testimonials__date"><?php echo esc_html( date_i18n( 'j. F Y', (int) $r['time'] ) ); ?></span>
								<?php endif; ?>
							</div>
							<span class="testimonials__g" aria-hidden="true"><svg viewBox="0 0 48 48" width="24" height="24" focusable="false"><path fill="#4285F4" d="M45.12 24.5c0-1.56-.14-3.06-.4-4.5H24v8.51h11.84c-.51 2.75-2.06 5.08-4.39 6.64v5.52h7.11c4.16-3.83 6.56-9.47 6.56-16.17z"/><path fill="#34A853" d="M24 46c5.94 0 10.92-1.97 14.56-5.33l-7.11-5.52c-1.97 1.32-4.49 2.1-7.45 2.1-5.73 0-10.58-3.87-12.31-9.07H4.34v5.7C7.96 41.07 15.4 46 24 46z"/><path fill="#FBBC05" d="M11.69 28.18C11.25 26.86 11 25.45 11 24s.25-2.86.69-4.18v-5.7H4.34A21.99 21.99 0 0 0 2 24c0 3.55.85 6.91 2.34 9.88l7.35-5.7z"/><path fill="#EA4335" d="M24 10.75c3.23 0 6.13 1.11 8.41 3.29l6.31-6.31C34.91 4.18 29.93 2 24 2 15.4 2 7.96 6.93 4.34 14.12l7.35 5.7c1.73-5.2 6.58-9.07 12.31-9.07z"/></svg></span>
						</div>
						<?php echo $stars( $r['rating'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<p class="testimonials__text"><?php echo wp_kses_post( $r['text'] ); ?></p>
					</article>
				<?php endforeach; ?>
			</div>
			<div class="testimonials__dots" role="tablist" aria-label="Bewertungen blättern"></div>
		</div>
	</div>
</section>
