<?php
defined( 'ABSPATH' ) || exit;

$a = wp_parse_args(
	$attributes,
	[
		'heading'       => 'Erfolgreich verkauft',
		'headingItalic' => 'in Vorarlberg',
		'ctaText'       => 'Alle Referenzen ansehen',
		'ctaUrl'        => '/objekte/',
		'navNextId'     => 0,
		'navNextUrl'    => '',
		'navPrevId'     => 0,
		'navPrevUrl'    => '',
	]
);

$nav_next_url = $a['navNextUrl'] ?: wp_get_attachment_url( (int) $a['navNextId'] );
$nav_prev_url = $a['navPrevUrl'] ?: wp_get_attachment_url( (int) $a['navPrevId'] );

$posts = get_posts(
	[
		'post_type'      => 'property',
		'posts_per_page' => -1,
		'post_status'    => 'publish',
		'meta_key'       => 'property_status',
		'meta_value'     => 'Verkauft',
		'orderby'        => 'date',
		'order'          => 'DESC',
	]
);

$wrapper = get_block_wrapper_attributes( [ 'class' => 'sold-showcase' ] );

$icon_prev = $nav_prev_url
	? '<img src="' . esc_url( $nav_prev_url ) . '" alt="" width="48" height="48" aria-hidden="true" />'
	: '<svg width="48" height="48" viewBox="0 0 48 48" fill="none" aria-hidden="true"><path d="M7.68 24C7.68 14.98 14.98 7.68 24 7.68C33.02 7.68 40.32 14.98 40.32 24C40.32 33.02 33.02 40.32 24 40.32C14.98 40.32 7.68 33.02 7.68 24ZM38.4 24C38.4 16.03 31.97 9.6 24 9.6C16.03 9.6 9.6 16.03 9.6 24C9.6 31.97 16.03 38.4 24 38.4C31.97 38.4 38.4 31.97 38.4 24Z" fill="currentColor"/><path d="M15 24L24.29 14.69L25.63 16.03L17.66 24L25.63 31.97L24.29 33.31L15 24Z" fill="currentColor"/><path d="M32.64 23.04V24.96H16.32V23.04H32.64Z" fill="currentColor"/></svg>';

$icon_next = $nav_next_url
	? '<img src="' . esc_url( $nav_next_url ) . '" alt="" width="48" height="48" aria-hidden="true" />'
	: '<svg width="48" height="48" viewBox="0 0 48 48" fill="none" aria-hidden="true"><path d="M40.32 24C40.32 33.02 33.02 40.32 24 40.32C14.98 40.32 7.68 33.02 7.68 24C7.68 14.98 14.98 7.68 24 7.68C33.02 7.68 40.32 14.98 40.32 24ZM9.6 24C9.6 31.97 16.03 38.4 24 38.4C31.97 38.4 38.4 31.97 38.4 24C38.4 16.03 31.97 9.6 24 9.6C16.03 9.6 9.6 16.03 9.6 24Z" fill="currentColor"/><path d="M33 24L23.71 33.31L22.37 31.97L30.34 24L22.37 16.03L23.71 14.69L33 24Z" fill="currentColor"/><path d="M15.36 24.96V23.04H31.68V24.96H15.36Z" fill="currentColor"/></svg>';
?>
<section <?php echo $wrapper; ?>>
	<div class="sold-showcase__inner">
		<header class="sold-showcase__header">
			<h2 class="sold-showcase__heading">
				<?php echo wp_kses_post( $a['heading'] ); ?>
				<em><?php echo wp_kses_post( $a['headingItalic'] ); ?></em>
			</h2>
		</header>

		<?php if ( empty( $posts ) ) : ?>
			<p class="sold-showcase__empty">Keine verkauften Objekte vorhanden.</p>
		<?php else : ?>
			<div class="sold-showcase__carousel" data-total="<?php echo count( $posts ); ?>">
				<div class="sold-showcase__track">
					<div class="sold-showcase__strip">
						<?php foreach ( $posts as $post ) : ?>
							<?php
							$price  = get_post_meta( $post->ID, 'property_price', true );
							$area   = get_post_meta( $post->ID, 'property_area', true );
							$rooms  = get_post_meta( $post->ID, 'property_rooms', true );
							$cities = get_the_terms( $post->ID, 'property-city' );
							$city   = ( $cities && ! is_wp_error( $cities ) ) ? $cities[0]->name : '';
							$img    = get_the_post_thumbnail_url( $post->ID, 'large' );
							$url    = get_permalink( $post->ID );
							?>
							<article class="sold-showcase__slide" aria-label="<?php echo esc_attr( $post->post_title ); ?>">
								<div class="sold-showcase__content">
									<h3><?php echo esc_html( $post->post_title ); ?></h3>
									<?php if ( $post->post_excerpt ) : ?>
										<p class="sold-showcase__text"><?php echo esc_html( $post->post_excerpt ); ?></p>
									<?php endif; ?>
									<dl class="sold-showcase__meta">
										<?php if ( $city ) : ?>
											<div><dt>Lage</dt><dd><?php echo esc_html( $city ); ?></dd></div>
										<?php endif; ?>
										<?php if ( $price ) : ?>
											<div><dt>Kaufpreis</dt><dd><?php echo esc_html( $price ); ?></dd></div>
										<?php endif; ?>
										<?php if ( $area ) : ?>
											<div><dt>Wohnfläche</dt><dd><?php echo esc_html( $area ); ?></dd></div>
										<?php endif; ?>
										<?php if ( $rooms ) : ?>
											<div><dt>Zimmer</dt><dd><?php echo esc_html( $rooms ); ?></dd></div>
										<?php endif; ?>
									</dl>
									<a class="sold-showcase__link" href="<?php echo esc_url( $url ); ?>">Erfahren Sie mehr →</a>
								</div>
								<div class="sold-showcase__media">
									<?php if ( $img ) : ?>
										<img
											src="<?php echo esc_url( $img ); ?>"
											alt="<?php echo esc_attr( $post->post_title ); ?>"
											loading="lazy"
										/>
									<?php endif; ?>
								</div>
							</article>
						<?php endforeach; ?>
					</div>
				</div>

				<div class="sold-showcase__nav-row">
					<button class="sold-showcase__nav sold-showcase__nav--prev" type="button" aria-label="Vorheriges">
						<?php echo $icon_prev; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</button>
					<div class="sold-showcase__dots" aria-hidden="true">
						<?php foreach ( $posts as $index => $post ) : ?>
							<span class="sold-showcase__dot<?php echo 0 === $index ? ' is-active' : ''; ?>"></span>
						<?php endforeach; ?>
					</div>
					<button class="sold-showcase__nav sold-showcase__nav--next" type="button" aria-label="Nächstes">
						<?php echo $icon_next; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</button>
				</div>
			</div>

			<div class="sold-showcase__footer">
				<a class="sold-showcase__button" href="<?php echo esc_url( $a['ctaUrl'] ); ?>">
					<?php echo esc_html( $a['ctaText'] ); ?>
				</a>
			</div>
		<?php endif; ?>
	</div>
</section>
