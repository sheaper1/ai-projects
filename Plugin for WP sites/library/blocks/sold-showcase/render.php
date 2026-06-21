<?php
defined( 'ABSPATH' ) || exit;

$a = wp_parse_args(
	$attributes,
	[
		'heading'       => 'Erfolgreich verkauft',
		'headingItalic' => 'in Vorarlberg',
		'title'         => 'Wohnung',
		'text'          => '',
		'locationLabel' => 'Lage',
		'locationValue' => '',
		'priceLabel'    => 'Kaufpreis',
		'priceValue'    => '',
		'areaLabel'     => 'Grundstücksfläche',
		'areaValue'     => '',
		'roomsLabel'    => 'Zimmer',
		'roomsValue'    => '',
		'buttonText'    => 'Erfahren Sie mehr',
		'buttonUrl'     => '#',
		'ctaText'       => 'Alle Referenzen ansehen',
		'ctaUrl'        => '#',
		'imageUrl'      => '',
	]
);
$wrapper = get_block_wrapper_attributes( [ 'class' => 'sold-showcase' ] );
?>
<section <?php echo $wrapper; ?>>
	<div class="sold-showcase__inner">
		<header class="sold-showcase__header">
			<h2 class="sold-showcase__heading">
				<?php echo wp_kses_post( $a['heading'] ); ?>
				<em><?php echo wp_kses_post( $a['headingItalic'] ); ?></em>
			</h2>
		</header>
		<div class="sold-showcase__frame">
			<button class="sold-showcase__nav sold-showcase__nav--prev" type="button" aria-label="Vorheriges Referenzobjekt" disabled>←</button>
			<article class="sold-showcase__card">
				<div class="sold-showcase__content">
					<h3><?php echo esc_html( $a['title'] ); ?></h3>
					<p class="sold-showcase__text"><?php echo esc_html( $a['text'] ); ?></p>
					<dl class="sold-showcase__meta">
						<div><dt><?php echo esc_html( $a['locationLabel'] ); ?></dt><dd><?php echo esc_html( $a['locationValue'] ); ?></dd></div>
						<div><dt><?php echo esc_html( $a['priceLabel'] ); ?></dt><dd><?php echo esc_html( $a['priceValue'] ); ?></dd></div>
						<div><dt><?php echo esc_html( $a['areaLabel'] ); ?></dt><dd><?php echo esc_html( $a['areaValue'] ); ?></dd></div>
						<div><dt><?php echo esc_html( $a['roomsLabel'] ); ?></dt><dd><?php echo esc_html( $a['roomsValue'] ); ?></dd></div>
					</dl>
					<a class="sold-showcase__link" href="<?php echo esc_url( $a['buttonUrl'] ); ?>"><?php echo esc_html( $a['buttonText'] ); ?> →</a>
				</div>
				<div class="sold-showcase__media">
					<?php if ( $a['imageUrl'] ) : ?>
						<img src="<?php echo esc_url( $a['imageUrl'] ); ?>" alt="<?php echo esc_attr( $a['title'] ); ?>" />
					<?php endif; ?>
				</div>
			</article>
			<button class="sold-showcase__nav sold-showcase__nav--next" type="button" aria-label="Nächstes Referenzobjekt" disabled>→</button>
		</div>
		<div class="sold-showcase__footer">
			<div class="sold-showcase__dots" aria-hidden="true"><span class="is-active"></span><span></span><span></span></div>
			<a class="sold-showcase__button" href="<?php echo esc_url( $a['ctaUrl'] ); ?>"><?php echo esc_html( $a['ctaText'] ); ?></a>
		</div>
	</div>
</section>
