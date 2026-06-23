<?php
/** Dynamic render for Trust Bar. @package library */

// Динамический рейтинг из плагина Google Reviews (grw); fallback — атрибут/изображение.
$gr      = function_exists( 'rosenberger_google_reviews' ) ? rosenberger_google_reviews() : array();
$rating  = ! empty( $gr['rating'] ) ? $gr['rating'] : ( $attributes['rating'] ?? '' );
$count   = $gr['count'] ?? '';
$biz_url = $gr['url'] ?? '';
$items   = isset( $attributes['items'] ) && is_array( $attributes['items'] ) ? $attributes['items'] : array();

$google_g = '<svg viewBox="0 0 48 48" width="22" height="22" aria-hidden="true" focusable="false"><path fill="#4285F4" d="M45.12 24.5c0-1.56-.14-3.06-.4-4.5H24v8.51h11.84c-.51 2.75-2.06 5.08-4.39 6.64v5.52h7.11c4.16-3.83 6.56-9.47 6.56-16.17z"/><path fill="#34A853" d="M24 46c5.94 0 10.92-1.97 14.56-5.33l-7.11-5.52c-1.97 1.32-4.49 2.1-7.45 2.1-5.73 0-10.58-3.87-12.31-9.07H4.34v5.7C7.96 41.07 15.4 46 24 46z"/><path fill="#FBBC05" d="M11.69 28.18C11.25 26.86 11 25.45 11 24s.25-2.86.69-4.18v-5.7H4.34A21.99 21.99 0 0 0 2 24c0 3.55.85 6.91 2.34 9.88l7.35-5.7z"/><path fill="#EA4335" d="M24 10.75c3.23 0 6.13 1.11 8.41 3.29l6.31-6.31C34.91 4.18 29.93 2 24 2 15.4 2 7.96 6.93 4.34 14.12l7.35 5.7c1.73-5.2 6.58-9.07 12.31-9.07z"/></svg>';

$pct = '' !== (string) $rating ? max( 0, min( 100, ( floatval( str_replace( ',', '.', (string) $rating ) ) / 5 ) * 100 ) ) : 0;
?>
<section <?php echo get_block_wrapper_attributes(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="trust-bar__inner">
		<div class="trust-bar__rating">
			<?php if ( '' !== (string) $rating ) : ?>
				<a class="trust-bar__badge"<?php echo $biz_url ? ' href="' . esc_url( $biz_url ) . '" target="_blank" rel="noopener noreferrer"' : ''; ?>>
					<span class="trust-bar__g"><?php echo $google_g; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					<span class="trust-bar__score"><?php echo esc_html( $rating ); ?></span>
					<span class="trust-bar__stars" aria-hidden="true">
						<span class="trust-bar__stars-empty">★★★★★</span>
						<span class="trust-bar__stars-full" style="width:<?php echo esc_attr( $pct ); ?>%;color:#FBBC04">★★★★★</span>
					</span>
					<?php if ( '' !== (string) $count ) : ?>
						<span class="trust-bar__count"><?php echo esc_html( $count ); ?> Bewertungen</span>
					<?php endif; ?>
				</a>
			<?php elseif ( ! empty( $attributes['badgeUrl'] ) ) : ?>
				<img src="<?php echo esc_url( $attributes['badgeUrl'] ); ?>" alt="Google Bewertung" />
			<?php endif; ?>
		</div>
		<div class="trust-bar__items">
			<?php foreach ( $items as $item ) : ?><span><?php echo esc_html( $item ); ?></span><?php endforeach; ?>
		</div>
	</div>
</section>
