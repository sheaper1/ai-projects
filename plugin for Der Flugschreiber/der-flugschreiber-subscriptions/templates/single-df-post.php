<?php
/**
 * Frontend template for single standard blog posts.
 *
 * Renders the post inside the Der Flugschreiber article design while keeping the
 * active theme's header and footer (navigation, branding, footer widgets).
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();
?>
<main id="df-post-main" class="df-post-main">
    <?php
    while (have_posts()) :
        the_post();
        echo DF_Subscriptions::instance()->shortcodes->post_page(array('show_back' => 'no')); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    endwhile;
    ?>
</main>
<?php
get_footer();
