<?php
/**
 * Property Catalog - server-side render.
 *
 * @package rosenberger-core
 */

defined( 'ABSPATH' ) || exit;

$posts_per_page = (int) ( $attributes['postsPerPage'] ?? 6 );
$archive_url    = esc_url( $attributes['archiveUrl'] ?? '/objekte/' );
$heading        = wp_kses_post( $attributes['heading'] ?? 'Aktuelle Objekte' );
$heading_italic = wp_kses_post( $attributes['headingItalic'] ?? 'in Vorarlberg' );
$subtext        = wp_kses_post( $attributes['subtext'] ?? 'Sie suchen ein Zuhause oder ein Grundstück in Vorarlberg? Hier sehen Sie, was ich gerade vermittle. Der Bestand wird laufend aktualisiert.' );

$filter_lage = sanitize_text_field( wp_unslash( $_GET['pc_lage'] ?? '' ) );
$filter_typ  = sanitize_text_field( wp_unslash( $_GET['pc_typ'] ?? '' ) );
$filter_sort = sanitize_text_field( wp_unslash( $_GET['pc_sort'] ?? 'newest' ) );

$tax_query = [];
if ( $filter_lage ) {
	$tax_query[] = [
		'taxonomy' => 'property-city',
		'field'    => 'slug',
		'terms'    => $filter_lage,
	];
}

if ( $filter_typ ) {
	$tax_query[] = [
		'taxonomy' => 'property-type',
		'field'    => 'slug',
		'terms'    => $filter_typ,
	];
}

$order = 'oldest' === $filter_sort ? 'ASC' : 'DESC';

$query = new WP_Query(
	[
		'post_type'      => 'property',
		'posts_per_page' => $posts_per_page,
		'orderby'        => 'date',
		'order'          => $order,
		'tax_query'      => $tax_query,
	]
);

$lage_terms = get_terms( [ 'taxonomy' => 'property-city', 'hide_empty' => false ] );
$typ_terms  = get_terms( [ 'taxonomy' => 'property-type', 'hide_empty' => false ] );

$wrapper_attrs = get_block_wrapper_attributes( [ 'class' => 'property-catalog' ] );
?>
<section <?php echo $wrapper_attrs; ?>>
	<div class="pc-inner">
		<header class="pc-header">
			<h2 class="pc-heading">
				<?php echo $heading; ?>
				<?php if ( '' !== trim( wp_strip_all_tags( $heading_italic ) ) ) : ?>
					<em><?php echo $heading_italic; ?></em>
				<?php endif; ?>
			</h2>
			<p class="pc-subtext"><?php echo $subtext; ?></p>
		</header>

		<form class="pc-filters" method="get" action="<?php echo esc_url( get_permalink() ); ?>">
			<div class="pc-filter">
				<select name="pc_lage" aria-label="Lage">
					<option value="">Lage</option>
					<?php foreach ( (array) $lage_terms as $term ) : ?>
						<option value="<?php echo esc_attr( $term->slug ); ?>" <?php selected( $filter_lage, $term->slug ); ?>>
							<?php echo esc_html( $term->name ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="pc-filter">
				<select name="pc_typ" aria-label="Typ">
					<option value="">Typ</option>
					<?php foreach ( (array) $typ_terms as $term ) : ?>
						<option value="<?php echo esc_attr( $term->slug ); ?>" <?php selected( $filter_typ, $term->slug ); ?>>
							<?php echo esc_html( $term->name ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="pc-filter pc-filter--sort">
				<select name="pc_sort" aria-label="Sortieren nach">
					<option value="newest" <?php selected( $filter_sort, 'newest' ); ?>>Neueste zuerst</option>
					<option value="oldest" <?php selected( $filter_sort, 'oldest' ); ?>>Älteste zuerst</option>
				</select>
			</div>
		</form>

		<?php if ( $query->have_posts() ) : ?>
			<div class="pc-grid">
				<?php while ( $query->have_posts() ) : $query->the_post(); ?>
					<?php
					$post_id   = get_the_ID();
					$price     = get_post_meta( $post_id, 'property_price', true );
					$area      = get_post_meta( $post_id, 'property_area', true );
					$rooms     = get_post_meta( $post_id, 'property_rooms', true );
					$lage_tax  = get_the_terms( $post_id, 'property-city' );
					$lage_str  = ( $lage_tax && ! is_wp_error( $lage_tax ) ) ? implode( ', ', wp_list_pluck( $lage_tax, 'name' ) ) : '';
					$thumb_id  = get_post_thumbnail_id( $post_id );
					$thumb_src = $thumb_id ? wp_get_attachment_image_url( $thumb_id, 'large' ) : '';
					?>
					<article class="pc-card">
						<div class="pc-card__img-wrap">
							<?php if ( $thumb_src ) : ?>
								<img
									class="pc-card__img"
									src="<?php echo esc_url( $thumb_src ); ?>"
									alt="<?php echo esc_attr( get_the_title() ); ?>"
									loading="lazy"
								/>
							<?php else : ?>
								<div class="pc-card__img-placeholder"></div>
							<?php endif; ?>
						</div>
						<div class="pc-card__body">
							<div class="pc-card__content">
								<div class="pc-card__title-group">
									<h3 class="pc-card__title"><?php the_title(); ?></h3>
									<?php if ( has_excerpt() || get_the_excerpt() ) : ?>
										<p class="pc-card__excerpt"><?php echo wp_kses_post( get_the_excerpt() ); ?></p>
									<?php endif; ?>
								</div>
								<dl class="pc-card__meta">
									<?php if ( $lage_str ) : ?>
										<div class="pc-meta-item">
											<dt>Lage</dt>
											<dd><?php echo esc_html( $lage_str ); ?></dd>
										</div>
									<?php endif; ?>
									<?php if ( $price ) : ?>
										<div class="pc-meta-item">
											<dt>Kaufpreis</dt>
											<dd><?php echo esc_html( $price ); ?></dd>
										</div>
									<?php endif; ?>
									<?php if ( $area ) : ?>
										<div class="pc-meta-item">
											<dt>Fläche</dt>
											<dd><?php echo esc_html( $area ); ?></dd>
										</div>
									<?php endif; ?>
									<?php if ( $rooms ) : ?>
										<div class="pc-meta-item">
											<dt>Zimmer</dt>
											<dd><?php echo esc_html( $rooms ); ?></dd>
										</div>
									<?php endif; ?>
								</dl>
							</div>
							<a class="pc-card__link" href="<?php the_permalink(); ?>">Erfahren Sie mehr →</a>
						</div>
					</article>
				<?php endwhile; ?>
				<?php wp_reset_postdata(); ?>
			</div>
		<?php else : ?>
			<p class="pc-empty">Keine Objekte gefunden.</p>
		<?php endif; ?>

		<div class="pc-cta">
			<a class="pc-cta__btn" href="<?php echo $archive_url; ?>">Alle Objekte ansehen</a>
		</div>
	</div>
</section>
