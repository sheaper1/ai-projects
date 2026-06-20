<?php

if (!defined('ABSPATH')) {
    exit;
}

class DF_Subscriptions_Content
{
    private $access;
    private $filtering_content = false;

    public function __construct(DF_Subscriptions_Access $access)
    {
        $this->access = $access;
    }

    public function hooks()
    {
        add_action('init', array($this, 'register_post_types'));
        add_action('add_meta_boxes', array($this, 'add_article_meta_box'));
        add_action('save_post_' . DF_Subscriptions::ARTICLE_POST_TYPE, array($this, 'save_article_meta'));
        add_filter('the_content', array($this, 'maybe_lock_content'), 20);
        add_filter('manage_' . DF_Subscriptions::ARTICLE_POST_TYPE . '_posts_columns', array($this, 'article_columns'));
        add_action('manage_' . DF_Subscriptions::ARTICLE_POST_TYPE . '_posts_custom_column', array($this, 'article_column_content'), 10, 2);
    }

    public function register_post_types()
    {
        register_post_type(
            DF_Subscriptions::MAGAZINE_POST_TYPE,
            array(
                'labels' => array(
                    'name' => __('Magazines', 'der-flugschreiber-subscriptions'),
                    'singular_name' => __('Magazine', 'der-flugschreiber-subscriptions'),
                    'add_new_item' => __('Add New Magazine', 'der-flugschreiber-subscriptions'),
                    'edit_item' => __('Edit Magazine', 'der-flugschreiber-subscriptions'),
                ),
                'public' => true,
                'has_archive' => true,
                'menu_icon' => 'dashicons-book-alt',
                'supports' => array('title', 'editor', 'excerpt', 'thumbnail', 'revisions'),
                'rewrite' => array('slug' => 'magazines'),
                'show_in_rest' => true,
            )
        );

        register_post_type(
            DF_Subscriptions::ARTICLE_POST_TYPE,
            array(
                'labels' => array(
                    'name' => __('Magazine Articles', 'der-flugschreiber-subscriptions'),
                    'singular_name' => __('Magazine Article', 'der-flugschreiber-subscriptions'),
                    'add_new_item' => __('Add New Article', 'der-flugschreiber-subscriptions'),
                    'edit_item' => __('Edit Article', 'der-flugschreiber-subscriptions'),
                ),
                'public' => true,
                'has_archive' => true,
                'menu_icon' => 'dashicons-media-document',
                'supports' => array('title', 'editor', 'excerpt', 'thumbnail', 'author', 'revisions'),
                'rewrite' => array('slug' => 'magazine-articles'),
                'show_in_rest' => true,
            )
        );
    }

    public function add_article_meta_box()
    {
        add_meta_box(
            'df_article_magazine',
            __('Magazine Issue', 'der-flugschreiber-subscriptions'),
            array($this, 'render_article_meta_box'),
            DF_Subscriptions::ARTICLE_POST_TYPE,
            'side',
            'default'
        );
    }

    public function render_article_meta_box($post)
    {
        wp_nonce_field('df_save_article_magazine', 'df_article_magazine_nonce');

        $selected_id = (int) get_post_meta($post->ID, DF_Subscriptions::ARTICLE_MAGAZINE_META, true);
        $magazines = get_posts(
            array(
                'post_type' => DF_Subscriptions::MAGAZINE_POST_TYPE,
                'numberposts' => -1,
                'orderby' => 'title',
                'order' => 'ASC',
                'post_status' => array('publish', 'draft', 'private'),
            )
        );
        ?>
        <p>
            <label for="df_magazine_id"><?php esc_html_e('Select magazine issue', 'der-flugschreiber-subscriptions'); ?></label>
        </p>
        <select name="df_magazine_id" id="df_magazine_id" class="widefat">
            <option value="0"><?php esc_html_e('No magazine selected', 'der-flugschreiber-subscriptions'); ?></option>
            <?php foreach ($magazines as $magazine) : ?>
                <option value="<?php echo esc_attr($magazine->ID); ?>" <?php selected($selected_id, $magazine->ID); ?>>
                    <?php echo esc_html(get_the_title($magazine)); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php
    }

    public function save_article_meta($post_id)
    {
        if (!isset($_POST['df_article_magazine_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['df_article_magazine_nonce'])), 'df_save_article_magazine')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        $magazine_id = isset($_POST['df_magazine_id']) ? absint($_POST['df_magazine_id']) : 0;

        if ($magazine_id > 0) {
            update_post_meta($post_id, DF_Subscriptions::ARTICLE_MAGAZINE_META, $magazine_id);
            return;
        }

        delete_post_meta($post_id, DF_Subscriptions::ARTICLE_MAGAZINE_META);
    }

    public function maybe_lock_content($content)
    {
        if ($this->filtering_content || !is_singular(array(DF_Subscriptions::MAGAZINE_POST_TYPE, DF_Subscriptions::ARTICLE_POST_TYPE)) || !in_the_loop() || !is_main_query()) {
            return $content;
        }

        if ($this->access->current_user_can_read_paid_content()) {
            return $content;
        }

        $post = get_post();

        if (!$post) {
            return $content;
        }

        $this->filtering_content = true;
        $excerpt = $this->get_public_excerpt($post, $content);
        $this->filtering_content = false;

        return $excerpt . $this->get_locked_message();
    }

    public function article_columns($columns)
    {
        $new_columns = array();

        foreach ($columns as $key => $label) {
            $new_columns[$key] = $label;

            if ('title' === $key) {
                $new_columns['df_magazine'] = __('Magazine', 'der-flugschreiber-subscriptions');
            }
        }

        return $new_columns;
    }

    public function article_column_content($column, $post_id)
    {
        if ('df_magazine' !== $column) {
            return;
        }

        $magazine_id = (int) get_post_meta($post_id, DF_Subscriptions::ARTICLE_MAGAZINE_META, true);

        if (!$magazine_id) {
            echo '&mdash;';
            return;
        }

        $edit_link = get_edit_post_link($magazine_id);
        $title = get_the_title($magazine_id);

        if ($edit_link) {
            printf('<a href="%s">%s</a>', esc_url($edit_link), esc_html($title));
            return;
        }

        echo esc_html($title);
    }

    private function get_public_excerpt(WP_Post $post, $content)
    {
        if (has_excerpt($post)) {
            return '<div class="df-subscriptions-excerpt">' . wpautop(wp_kses_post(get_the_excerpt($post))) . '</div>';
        }

        $plain_text = wp_strip_all_tags(strip_shortcodes($content));
        $trimmed = wp_trim_words($plain_text, 55, '...');

        return '<div class="df-subscriptions-excerpt">' . wpautop(esc_html($trimmed)) . '</div>';
    }

    private function get_locked_message()
    {
        $payment_url = get_option(DF_Subscriptions::PAYMENT_URL_OPTION, '');
        $login_page_url = wp_login_url(get_permalink());

        ob_start();
        ?>
        <div class="df-subscriptions-locked">
            <p><?php esc_html_e('To read the full text, please log in or purchase a subscription.', 'der-flugschreiber-subscriptions'); ?></p>
            <p>
                <?php if ($payment_url) : ?>
                    <a class="button df-subscriptions-payment-link" href="<?php echo esc_url($payment_url); ?>">
                        <?php esc_html_e('Purchase subscription', 'der-flugschreiber-subscriptions'); ?>
                    </a>
                <?php endif; ?>
                <a class="button df-subscriptions-login-link" href="<?php echo esc_url($login_page_url); ?>">
                    <?php esc_html_e('Log in', 'der-flugschreiber-subscriptions'); ?>
                </a>
            </p>
        </div>
        <?php
        return ob_get_clean();
    }
}
