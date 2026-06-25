<?php
defined( 'ABSPATH' ) || exit;
/** @var WP_Query $query */
/** @var array    $atts */
/** @var bool     $show_filters */
/** @var string   $layout */
/** @var int      $columns */
?>
<div class="propstack-listing propstack-listing--<?php echo esc_attr( $layout ); ?> propstack-listing--cols-<?php echo esc_attr( $columns ); ?>">

    <?php if ( $show_filters ) :
        Propstack_RE_Template_Loader::get_template( 'filters.php' );
    endif; ?>

    <?php if ( $query->have_posts() ) : ?>
        <div class="propstack-listing__grid">
            <?php while ( $query->have_posts() ) : $query->the_post(); ?>
                <?php Propstack_RE_Template_Loader::get_template( 'card.php', [ 'post_id' => get_the_ID() ] ); ?>
            <?php endwhile; wp_reset_postdata(); ?>
        </div>

        <?php if ( $query->max_num_pages > 1 ) : ?>
        <div class="propstack-listing__pagination">
            <?php
            echo paginate_links( [
                'total'   => $query->max_num_pages,
                'current' => max( 1, get_query_var( 'paged' ) ),
            ] );
            ?>
        </div>
        <?php endif; ?>

    <?php else : ?>
        <div class="propstack-listing__empty">
            <p><?php esc_html_e( 'Keine Objekte gefunden.', 'propstack-re' ); ?></p>
            <?php if ( ! empty( $_GET ) ) : ?>
            <a href="<?php echo esc_url( strtok( (string) $_SERVER['REQUEST_URI'], '?' ) ); ?>" class="propstack-btn propstack-btn--outline">
                <?php esc_html_e( 'Filter zurücksetzen', 'propstack-re' ); ?>
            </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

</div>
