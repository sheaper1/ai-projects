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
        add_action('init', array($this, 'register_taxonomies'));
        add_action('add_meta_boxes', array($this, 'add_article_meta_box'));
        add_action('add_meta_boxes', array($this, 'add_magazine_meta_box'));
        add_action('admin_notices', array($this, 'render_admin_notices'));
        add_action('template_redirect', array($this, 'disable_cache_for_authorized_paid_content'), 1);
        add_action('template_redirect', array($this, 'redirect_free_pdf_magazine'));
        add_filter('template_include', array($this, 'single_post_template'), 20);
        add_action('save_post_' . DF_Subscriptions::MAGAZINE_POST_TYPE, array($this, 'save_magazine_meta'));
        add_action('save_post_' . DF_Subscriptions::ARTICLE_POST_TYPE, array($this, 'save_article_meta'));
        add_action('admin_post_df_download_pdf', array($this, 'download_protected_pdf'));
        add_action('admin_post_nopriv_df_download_pdf', array($this, 'download_protected_pdf'));
        add_filter('the_content', array($this, 'maybe_lock_content'), 20);
        add_filter('the_content_feed', array($this, 'maybe_lock_feed_content'), 20);
        add_filter('rest_prepare_' . DF_Subscriptions::MAGAZINE_POST_TYPE, array($this, 'maybe_lock_rest_content'), 20, 3);
        add_filter('rest_prepare_' . DF_Subscriptions::ARTICLE_POST_TYPE, array($this, 'maybe_lock_rest_content'), 20, 3);
        add_filter('wp_insert_post_data', array($this, 'enforce_article_magazine'), 20, 2);
        add_filter('manage_' . DF_Subscriptions::MAGAZINE_POST_TYPE . '_posts_columns', array($this, 'magazine_columns'));
        add_action('manage_' . DF_Subscriptions::MAGAZINE_POST_TYPE . '_posts_custom_column', array($this, 'magazine_column_content'), 10, 2);
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
                'taxonomies' => array(DF_Subscriptions::TOPIC_TAXONOMY, DF_Subscriptions::ISSUE_YEAR_TAXONOMY),
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
                'taxonomies' => array(DF_Subscriptions::TOPIC_TAXONOMY),
                'rewrite' => array('slug' => 'magazine-articles'),
                'show_in_rest' => true,
            )
        );

        $auth_callback = static function () {
            return current_user_can('edit_posts');
        };

        register_post_meta(
            DF_Subscriptions::ARTICLE_POST_TYPE,
            DF_Subscriptions::ARTICLE_MAGAZINE_META,
            array('type' => 'integer', 'single' => true, 'show_in_rest' => true, 'sanitize_callback' => 'absint', 'auth_callback' => $auth_callback)
        );
        register_post_meta(
            DF_Subscriptions::ARTICLE_POST_TYPE,
            DF_Subscriptions::ARTICLE_ACCESS_META,
            array('type' => 'string', 'single' => true, 'show_in_rest' => true, 'sanitize_callback' => 'sanitize_key', 'auth_callback' => $auth_callback)
        );
        register_post_meta(
            DF_Subscriptions::ARTICLE_POST_TYPE,
            DF_Subscriptions::ARTICLE_PREVIEW_WORDS_META,
            array('type' => 'integer', 'single' => true, 'show_in_rest' => true, 'sanitize_callback' => 'absint', 'auth_callback' => $auth_callback)
        );
        register_post_meta(
            DF_Subscriptions::ARTICLE_POST_TYPE,
            DF_Subscriptions::ARTICLE_IMAGE_URL_META,
            array('type' => 'string', 'single' => true, 'show_in_rest' => true, 'sanitize_callback' => 'esc_url_raw', 'auth_callback' => $auth_callback)
        );
    }

    public function register_taxonomies()
    {
        register_taxonomy(
            DF_Subscriptions::TOPIC_TAXONOMY,
            array(DF_Subscriptions::MAGAZINE_POST_TYPE, DF_Subscriptions::ARTICLE_POST_TYPE),
            array(
                'labels' => array(
                    'name' => __('Topic Categories', 'der-flugschreiber-subscriptions'),
                    'singular_name' => __('Topic Category', 'der-flugschreiber-subscriptions'),
                ),
                'hierarchical' => true,
                'public' => true,
                'show_admin_column' => true,
                'show_in_rest' => true,
                'rewrite' => array('slug' => 'topic-category'),
            )
        );

        register_taxonomy(
            DF_Subscriptions::ISSUE_YEAR_TAXONOMY,
            array(DF_Subscriptions::MAGAZINE_POST_TYPE),
            array(
                'labels' => array(
                    'name' => __('Issue Years', 'der-flugschreiber-subscriptions'),
                    'singular_name' => __('Issue Year', 'der-flugschreiber-subscriptions'),
                ),
                'hierarchical' => false,
                'public' => true,
                'show_admin_column' => true,
                'show_in_rest' => true,
                'rewrite' => array('slug' => 'issue-year'),
            )
        );
    }

    public function add_magazine_meta_box()
    {
        add_meta_box(
            'df_magazine_details',
            __('Issue Details', 'der-flugschreiber-subscriptions'),
            array($this, 'render_magazine_meta_box'),
            DF_Subscriptions::MAGAZINE_POST_TYPE,
            'side',
            'high'
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

    public function render_magazine_meta_box($post)
    {
        wp_nonce_field('df_save_magazine_details', 'df_magazine_details_nonce');

        $access = get_post_meta($post->ID, DF_Subscriptions::MAGAZINE_ACCESS_META, true);
        $access = $access ? $access : 'paid';
        $pdf_url = get_post_meta($post->ID, DF_Subscriptions::MAGAZINE_PDF_META, true);
        $protected_pdf = get_post_meta($post->ID, DF_Subscriptions::MAGAZINE_PROTECTED_PDF_META, true);
        $cover_url = get_post_meta($post->ID, DF_Subscriptions::MAGAZINE_COVER_URL_META, true);
        $issue_number = get_post_meta($post->ID, DF_Subscriptions::MAGAZINE_ISSUE_NUMBER_META, true);
        $issue_date = get_post_meta($post->ID, DF_Subscriptions::MAGAZINE_ISSUE_DATE_META, true);
        ?>
        <p>
            <label for="df_magazine_access"><strong><?php esc_html_e('Issue type', 'der-flugschreiber-subscriptions'); ?></strong></label>
            <select name="df_magazine_access" id="df_magazine_access" class="widefat">
                <option value="paid" <?php selected($access, 'paid'); ?>><?php esc_html_e('New paid issue', 'der-flugschreiber-subscriptions'); ?></option>
                <option value="free_pdf" <?php selected($access, 'free_pdf'); ?>><?php esc_html_e('Old free PDF issue', 'der-flugschreiber-subscriptions'); ?></option>
            </select>
        </p>
        <p>
            <label for="df_magazine_issue_number"><strong><?php esc_html_e('Issue number', 'der-flugschreiber-subscriptions'); ?></strong></label>
            <input type="text" name="df_magazine_issue_number" id="df_magazine_issue_number" class="widefat" value="<?php echo esc_attr($issue_number); ?>" placeholder="# 10">
        </p>
        <p>
            <label for="df_magazine_issue_date"><strong><?php esc_html_e('Issue date', 'der-flugschreiber-subscriptions'); ?></strong></label>
            <input type="date" name="df_magazine_issue_date" id="df_magazine_issue_date" class="widefat" value="<?php echo esc_attr($issue_date); ?>">
        </p>
        <p>
            <label for="df_magazine_pdf_url"><strong><?php esc_html_e('PDF URL for old issue', 'der-flugschreiber-subscriptions'); ?></strong></label>
            <input type="url" name="df_magazine_pdf_url" id="df_magazine_pdf_url" class="widefat" value="<?php echo esc_attr($pdf_url); ?>" placeholder="https://.../issue.pdf">
            <button type="button" class="button df-select-media" data-target="#df_magazine_pdf_url" data-library="application/pdf"><?php esc_html_e('Choose PDF', 'der-flugschreiber-subscriptions'); ?></button>
        </p>
        <p>
            <label for="df_magazine_protected_pdf"><strong><?php esc_html_e('Protected PDF for paid issue', 'der-flugschreiber-subscriptions'); ?></strong></label>
            <input type="file" name="df_magazine_protected_pdf" id="df_magazine_protected_pdf" accept="application/pdf,.pdf">
            <?php if ($protected_pdf) : ?>
                <span class="description"><?php echo esc_html(basename($protected_pdf)); ?></span>
                <label><input type="checkbox" name="df_remove_protected_pdf" value="1"> <?php esc_html_e('Remove protected PDF', 'der-flugschreiber-subscriptions'); ?></label>
            <?php endif; ?>
        </p>
        <p>
            <label for="df_magazine_cover_url"><strong><?php esc_html_e('Cover image URL', 'der-flugschreiber-subscriptions'); ?></strong></label>
            <input type="url" name="df_magazine_cover_url" id="df_magazine_cover_url" class="widefat" value="<?php echo esc_attr($cover_url); ?>" placeholder="https://.../cover.webp">
            <button type="button" class="button df-select-media" data-target="#df_magazine_cover_url" data-library="image"><?php esc_html_e('Choose image', 'der-flugschreiber-subscriptions'); ?></button>
        </p>
        <p class="description"><?php esc_html_e('Old free PDF issues can redirect directly to this PDF when opened.', 'der-flugschreiber-subscriptions'); ?></p>
        <?php
    }

    public function render_article_meta_box($post)
    {
        wp_nonce_field('df_save_article_magazine', 'df_article_magazine_nonce');

        $selected_id = (int) get_post_meta($post->ID, DF_Subscriptions::ARTICLE_MAGAZINE_META, true);
        $article_access = get_post_meta($post->ID, DF_Subscriptions::ARTICLE_ACCESS_META, true);
        $article_access = in_array($article_access, array('inherit', 'free', 'paid'), true) ? $article_access : 'inherit';
        $preview_words = absint(get_post_meta($post->ID, DF_Subscriptions::ARTICLE_PREVIEW_WORDS_META, true));
        $preview_words = $preview_words ? $preview_words : 120;
        $image_url = get_post_meta($post->ID, DF_Subscriptions::ARTICLE_IMAGE_URL_META, true);
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
        <p>
            <label for="df_article_access"><?php esc_html_e('Article access', 'der-flugschreiber-subscriptions'); ?></label>
            <select name="df_article_access" id="df_article_access" class="widefat">
                <option value="inherit" <?php selected($article_access, 'inherit'); ?>><?php esc_html_e('Inherit from issue', 'der-flugschreiber-subscriptions'); ?></option>
                <option value="free" <?php selected($article_access, 'free'); ?>><?php esc_html_e('Free article', 'der-flugschreiber-subscriptions'); ?></option>
                <option value="paid" <?php selected($article_access, 'paid'); ?>><?php esc_html_e('Paid article', 'der-flugschreiber-subscriptions'); ?></option>
            </select>
        </p>
        <p>
            <label for="df_article_preview_words"><?php esc_html_e('Preview length in words', 'der-flugschreiber-subscriptions'); ?></label>
            <input type="number" min="10" max="500" name="df_article_preview_words" id="df_article_preview_words" class="small-text" value="<?php echo esc_attr($preview_words); ?>">
        </p>
        <p>
            <label for="df_article_image_url"><?php esc_html_e('Article image URL', 'der-flugschreiber-subscriptions'); ?></label>
            <input type="url" name="df_article_image_url" id="df_article_image_url" class="widefat" value="<?php echo esc_attr($image_url); ?>">
            <button type="button" class="button df-select-media" data-target="#df_article_image_url" data-library="image"><?php esc_html_e('Choose image', 'der-flugschreiber-subscriptions'); ?></button>
        </p>
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
        $article_access = isset($_POST['df_article_access']) ? sanitize_key(wp_unslash($_POST['df_article_access'])) : 'inherit';
        $article_access = in_array($article_access, array('inherit', 'free', 'paid'), true) ? $article_access : 'inherit';
        $preview_words = isset($_POST['df_article_preview_words']) ? min(500, max(10, absint($_POST['df_article_preview_words']))) : 120;
        $image_url = isset($_POST['df_article_image_url']) ? esc_url_raw(wp_unslash($_POST['df_article_image_url'])) : '';

        update_post_meta($post_id, DF_Subscriptions::ARTICLE_ACCESS_META, $article_access);
        update_post_meta($post_id, DF_Subscriptions::ARTICLE_PREVIEW_WORDS_META, $preview_words);
        update_post_meta($post_id, DF_Subscriptions::ARTICLE_IMAGE_URL_META, $image_url);

        if ($magazine_id > 0) {
            $magazine = get_post($magazine_id);

            if (!$magazine || DF_Subscriptions::MAGAZINE_POST_TYPE !== $magazine->post_type) {
                delete_post_meta($post_id, DF_Subscriptions::ARTICLE_MAGAZINE_META);
                return;
            }

            update_post_meta($post_id, DF_Subscriptions::ARTICLE_MAGAZINE_META, $magazine_id);
            return;
        }

        delete_post_meta($post_id, DF_Subscriptions::ARTICLE_MAGAZINE_META);
    }

    public function save_magazine_meta($post_id)
    {
        if (!isset($_POST['df_magazine_details_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['df_magazine_details_nonce'])), 'df_save_magazine_details')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        $access = isset($_POST['df_magazine_access']) ? sanitize_key(wp_unslash($_POST['df_magazine_access'])) : 'paid';
        $access = in_array($access, array('paid', 'free_pdf'), true) ? $access : 'paid';
        $pdf_url = isset($_POST['df_magazine_pdf_url']) ? esc_url_raw(wp_unslash($_POST['df_magazine_pdf_url'])) : '';
        $cover_url = isset($_POST['df_magazine_cover_url']) ? esc_url_raw(wp_unslash($_POST['df_magazine_cover_url'])) : '';
        $issue_number = isset($_POST['df_magazine_issue_number']) ? sanitize_text_field(wp_unslash($_POST['df_magazine_issue_number'])) : '';
        $issue_date = isset($_POST['df_magazine_issue_date']) ? sanitize_text_field(wp_unslash($_POST['df_magazine_issue_date'])) : '';

        if ('free_pdf' === $access && !$pdf_url) {
            $access = 'paid';
            set_transient('df_subscriptions_notice_' . get_current_user_id(), __('A free PDF issue requires a PDF URL. The issue was kept as paid.', 'der-flugschreiber-subscriptions'), MINUTE_IN_SECONDS);
        }

        update_post_meta($post_id, DF_Subscriptions::MAGAZINE_ACCESS_META, $access);
        update_post_meta($post_id, DF_Subscriptions::MAGAZINE_PDF_META, $pdf_url);
        update_post_meta($post_id, DF_Subscriptions::MAGAZINE_COVER_URL_META, $cover_url);
        update_post_meta($post_id, DF_Subscriptions::MAGAZINE_ISSUE_NUMBER_META, $issue_number);
        $this->save_protected_pdf($post_id);

        if ($this->is_valid_date($issue_date)) {
            update_post_meta($post_id, DF_Subscriptions::MAGAZINE_ISSUE_DATE_META, $issue_date);
            wp_set_object_terms($post_id, substr($issue_date, 0, 4), DF_Subscriptions::ISSUE_YEAR_TAXONOMY, false);
            return;
        }

        delete_post_meta($post_id, DF_Subscriptions::MAGAZINE_ISSUE_DATE_META);
    }

    public function single_post_template($template)
    {
        if (is_admin() || !is_singular('post') || !is_main_query() || is_embed()) {
            return $template;
        }

        // Allow a theme/child theme to keep its own single-post template by
        // returning early through this filter if needed.
        if (!apply_filters('df_subscriptions_style_single_posts', true, get_queried_object_id())) {
            return $template;
        }

        $plugin_template = DF_SUBSCRIPTIONS_PATH . 'templates/single-df-post.php';

        return file_exists($plugin_template) ? $plugin_template : $template;
    }

    public function redirect_free_pdf_magazine()
    {
        if (!is_singular(DF_Subscriptions::MAGAZINE_POST_TYPE) || is_admin()) {
            return;
        }

        $post_id = get_queried_object_id();

        if (!$post_id || !$this->is_free_pdf_magazine($post_id)) {
            return;
        }

        $pdf_url = get_post_meta($post_id, DF_Subscriptions::MAGAZINE_PDF_META, true);

        if (!$pdf_url) {
            return;
        }

        wp_redirect(esc_url_raw($pdf_url), 302);
        exit;
    }

    public function disable_cache_for_authorized_paid_content()
    {
        if (!is_user_logged_in() || !is_singular(array(DF_Subscriptions::MAGAZINE_POST_TYPE, DF_Subscriptions::ARTICLE_POST_TYPE))) {
            return;
        }

        $post = get_post();

        if (!$post || !$this->is_paid_content($post)) {
            return;
        }

        if (!defined('DONOTCACHEPAGE')) {
            define('DONOTCACHEPAGE', true);
        }

        nocache_headers();
    }

    public function maybe_lock_content($content)
    {
        if (false !== strpos($content, 'df-article-page')) {
            return $content;
        }

        if ($this->filtering_content || !is_singular(array(DF_Subscriptions::MAGAZINE_POST_TYPE, DF_Subscriptions::ARTICLE_POST_TYPE)) || !in_the_loop() || !is_main_query()) {
            return $content;
        }

        $post = get_post();

        if (!$post) {
            return $content;
        }

        if (!$this->is_paid_content($post) || $this->current_user_can_read_post($post)) {
            return $content;
        }

        $this->filtering_content = true;
        $excerpt = $this->get_public_excerpt($post, $content);
        $this->filtering_content = false;

        return $excerpt . $this->get_locked_message($post);
    }

    public function get_protected_pdf_url($post_id)
    {
        $filename = get_post_meta($post_id, DF_Subscriptions::MAGAZINE_PROTECTED_PDF_META, true);

        if (!$filename) {
            return '';
        }

        return wp_nonce_url(
            add_query_arg(
                array(
                    'action' => 'df_download_pdf',
                    'post_id' => absint($post_id),
                ),
                admin_url('admin-post.php')
            ),
            'df_download_pdf_' . absint($post_id),
            'df_pdf_nonce'
        );
    }

    public function download_protected_pdf()
    {
        $post_id = isset($_GET['post_id']) ? absint($_GET['post_id']) : 0;
        $nonce = isset($_GET['df_pdf_nonce']) ? sanitize_text_field(wp_unslash($_GET['df_pdf_nonce'])) : '';

        if (!$post_id || !wp_verify_nonce($nonce, 'df_download_pdf_' . $post_id)) {
            wp_die(esc_html__('Der Download-Link ist ungültig oder abgelaufen.', 'der-flugschreiber-subscriptions'), '', array('response' => 403));
        }

        $post = get_post($post_id);

        if (!$post || DF_Subscriptions::MAGAZINE_POST_TYPE !== $post->post_type || !$this->current_user_can_read_post($post)) {
            wp_die(esc_html__('Sie haben keinen Zugriff auf diese PDF-Datei.', 'der-flugschreiber-subscriptions'), '', array('response' => 403));
        }

        $filename = basename(get_post_meta($post_id, DF_Subscriptions::MAGAZINE_PROTECTED_PDF_META, true));
        $path = trailingslashit($this->get_protected_directory()) . $filename;

        if (!$filename || !is_readable($path)) {
            wp_die(esc_html__('Die PDF-Datei wurde nicht gefunden.', 'der-flugschreiber-subscriptions'), '', array('response' => 404));
        }

        nocache_headers();
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . sanitize_file_name(get_the_title($post_id)) . '.pdf"');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;
    }

    public function enforce_article_magazine($data, $postarr)
    {
        if (
            DF_Subscriptions::ARTICLE_POST_TYPE !== $data['post_type']
            || 'publish' !== $data['post_status']
            || '1' !== get_option(DF_Subscriptions::REQUIRE_MAGAZINE_OPTION, '0')
        ) {
            return $data;
        }

        if (isset($_POST['df_magazine_id'])) {
            $magazine_id = absint($_POST['df_magazine_id']);
        } elseif (!empty($postarr['meta_input'][DF_Subscriptions::ARTICLE_MAGAZINE_META])) {
            $magazine_id = absint($postarr['meta_input'][DF_Subscriptions::ARTICLE_MAGAZINE_META]);
        } elseif (!empty($postarr['ID'])) {
            $magazine_id = (int) get_post_meta($postarr['ID'], DF_Subscriptions::ARTICLE_MAGAZINE_META, true);
        } else {
            $magazine_id = 0;
        }

        if (!$magazine_id) {
            $data['post_status'] = 'draft';
            set_transient('df_subscriptions_notice_' . get_current_user_id(), __('The article was saved as a draft because no magazine issue was selected.', 'der-flugschreiber-subscriptions'), MINUTE_IN_SECONDS);
        }

        return $data;
    }

    public function render_admin_notices()
    {
        $key = 'df_subscriptions_notice_' . get_current_user_id();
        $message = get_transient($key);

        if (!$message) {
            return;
        }

        delete_transient($key);
        echo '<div class="notice notice-warning is-dismissible"><p>' . esc_html($message) . '</p></div>';
    }

    public function maybe_lock_feed_content($content)
    {
        $post = get_post();

        if (!$post || !$this->is_paid_content($post) || $this->current_user_can_read_post($post)) {
            return $content;
        }

        return $this->get_public_excerpt($post, $content) . $this->get_locked_message($post);
    }

    public function maybe_lock_rest_content($response, $post, $request)
    {
        if (
            !$response instanceof WP_REST_Response
            || !$post instanceof WP_Post
            || !$this->is_paid_content($post)
            || $this->current_user_can_read_post($post)
        ) {
            return $response;
        }

        $data = $response->get_data();

        if (!isset($data['content']) || !is_array($data['content'])) {
            return $response;
        }

        $data['content']['rendered'] = $this->get_public_excerpt($post, $post->post_content) . $this->get_locked_message($post);
        $data['content']['protected'] = true;
        unset($data['content']['raw']);
        $response->set_data($data);

        return $response;
    }

    public function magazine_columns($columns)
    {
        $new_columns = array();

        foreach ($columns as $key => $label) {
            $new_columns[$key] = $label;

            if ('title' === $key) {
                $new_columns['df_issue_type'] = __('Issue Type', 'der-flugschreiber-subscriptions');
                $new_columns['df_issue_date'] = __('Issue Date', 'der-flugschreiber-subscriptions');
                $new_columns['df_pdf'] = __('PDF', 'der-flugschreiber-subscriptions');
            }
        }

        return $new_columns;
    }

    public function magazine_column_content($column, $post_id)
    {
        if ('df_issue_type' === $column) {
            $access = get_post_meta($post_id, DF_Subscriptions::MAGAZINE_ACCESS_META, true);
            echo esc_html('free_pdf' === $access ? __('Old free PDF', 'der-flugschreiber-subscriptions') : __('New paid', 'der-flugschreiber-subscriptions'));
            return;
        }

        if ('df_issue_date' === $column) {
            $issue_date = get_post_meta($post_id, DF_Subscriptions::MAGAZINE_ISSUE_DATE_META, true);
            echo $issue_date ? esc_html($issue_date) : '&mdash;';
            return;
        }

        if ('df_pdf' === $column) {
            $pdf_url = get_post_meta($post_id, DF_Subscriptions::MAGAZINE_PDF_META, true);
            echo $pdf_url ? '<a href="' . esc_url($pdf_url) . '" target="_blank" rel="noopener">' . esc_html__('Open PDF', 'der-flugschreiber-subscriptions') . '</a>' : '&mdash;';
        }
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

    private function get_locked_message(WP_Post $post)
    {
        $payment_url = get_option(DF_Subscriptions::PAYMENT_URL_OPTION, '');
        $custom_login_url = get_option(DF_Subscriptions::LOGIN_URL_OPTION, '');
        $permalink = get_permalink($post);
        $login_page_url = $custom_login_url ? add_query_arg('redirect_to', $permalink, $custom_login_url) : wp_login_url($permalink);

        ob_start();
        ?>
        <div class="df-subscriptions-locked">
            <img class="df-subscriptions-locked__plane" src="<?php echo esc_url(DF_SUBSCRIPTIONS_PLANE_URL); ?>" alt="" aria-hidden="true">
            <p><?php esc_html_e('Um den vollständigen Text zu lesen, melden Sie sich bitte an oder schließen Sie ein Abonnement ab.', 'der-flugschreiber-subscriptions'); ?></p>
            <p>
                <?php if ($payment_url) : ?>
                    <a class="button df-subscriptions-payment-link" href="<?php echo esc_url($payment_url); ?>">
                        <?php esc_html_e('Abonnement abschließen', 'der-flugschreiber-subscriptions'); ?>
                    </a>
                <?php endif; ?>
                <a class="button df-subscriptions-login-link" href="<?php echo esc_url($login_page_url); ?>">
                    <?php esc_html_e('Anmelden', 'der-flugschreiber-subscriptions'); ?>
                </a>
            </p>
        </div>
        <?php
        return ob_get_clean();
    }

    private function is_paid_content(WP_Post $post)
    {
        if (DF_Subscriptions::MAGAZINE_POST_TYPE === $post->post_type) {
            return !$this->is_free_pdf_magazine($post->ID);
        }

        if (DF_Subscriptions::ARTICLE_POST_TYPE === $post->post_type) {
            $article_access = get_post_meta($post->ID, DF_Subscriptions::ARTICLE_ACCESS_META, true);

            if ('free' === $article_access) {
                return false;
            }

            if ('paid' === $article_access) {
                return true;
            }

            $magazine_id = (int) get_post_meta($post->ID, DF_Subscriptions::ARTICLE_MAGAZINE_META, true);

            if ($magazine_id && $this->is_free_pdf_magazine($magazine_id)) {
                return false;
            }
        }

        return true;
    }

    private function is_free_pdf_magazine($post_id)
    {
        return 'free_pdf' === get_post_meta($post_id, DF_Subscriptions::MAGAZINE_ACCESS_META, true);
    }

    private function current_user_can_read_post(WP_Post $post)
    {
        return $this->access->current_user_can_read_paid_content() || current_user_can('edit_post', $post->ID);
    }

    private function save_protected_pdf($post_id)
    {
        $current = basename(get_post_meta($post_id, DF_Subscriptions::MAGAZINE_PROTECTED_PDF_META, true));
        $directory = $this->get_protected_directory();

        if (!empty($_POST['df_remove_protected_pdf'])) {
            if ($current && is_file(trailingslashit($directory) . $current)) {
                unlink(trailingslashit($directory) . $current);
            }

            delete_post_meta($post_id, DF_Subscriptions::MAGAZINE_PROTECTED_PDF_META);
            $current = '';
        }

        if (empty($_FILES['df_magazine_protected_pdf']['tmp_name']) || UPLOAD_ERR_OK !== (int) $_FILES['df_magazine_protected_pdf']['error']) {
            return;
        }

        $checked = wp_check_filetype_and_ext(
            $_FILES['df_magazine_protected_pdf']['tmp_name'],
            $_FILES['df_magazine_protected_pdf']['name'],
            array('pdf' => 'application/pdf')
        );

        if ('pdf' !== $checked['ext']) {
            set_transient('df_subscriptions_notice_' . get_current_user_id(), __('Only valid PDF files can be uploaded as protected issues.', 'der-flugschreiber-subscriptions'), MINUTE_IN_SECONDS);
            return;
        }

        if (!wp_mkdir_p($directory)) {
            set_transient('df_subscriptions_notice_' . get_current_user_id(), __('The protected PDF directory could not be created.', 'der-flugschreiber-subscriptions'), MINUTE_IN_SECONDS);
            return;
        }

        $this->protect_directory($directory);
        $filename = wp_unique_filename($directory, sanitize_file_name($_FILES['df_magazine_protected_pdf']['name']));
        $destination = trailingslashit($directory) . $filename;

        if (!move_uploaded_file($_FILES['df_magazine_protected_pdf']['tmp_name'], $destination)) {
            set_transient('df_subscriptions_notice_' . get_current_user_id(), __('The protected PDF could not be saved.', 'der-flugschreiber-subscriptions'), MINUTE_IN_SECONDS);
            return;
        }

        if ($current && is_file(trailingslashit($directory) . $current)) {
            unlink(trailingslashit($directory) . $current);
        }

        update_post_meta($post_id, DF_Subscriptions::MAGAZINE_PROTECTED_PDF_META, $filename);
    }

    private function get_protected_directory()
    {
        $preferred = trailingslashit(dirname(untrailingslashit(ABSPATH))) . DF_Subscriptions::PROTECTED_DIR;

        if (is_dir($preferred) || is_writable(dirname($preferred))) {
            return $preferred;
        }

        return trailingslashit(WP_CONTENT_DIR) . DF_Subscriptions::PROTECTED_DIR;
    }

    private function protect_directory($directory)
    {
        $htaccess = trailingslashit($directory) . '.htaccess';
        $web_config = trailingslashit($directory) . 'web.config';
        $index = trailingslashit($directory) . 'index.php';

        if (!file_exists($htaccess)) {
            file_put_contents($htaccess, "Require all denied\nDeny from all\n");
        }

        if (!file_exists($web_config)) {
            file_put_contents($web_config, '<?xml version="1.0" encoding="UTF-8"?><configuration><system.webServer><security><authorization><remove users="*" roles="" verbs="" /><add accessType="Deny" users="*" /></authorization></security></system.webServer></configuration>');
        }

        if (!file_exists($index)) {
            file_put_contents($index, "<?php\nhttp_response_code(403);\nexit;\n");
        }
    }

    private function is_valid_date($date)
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return false;
        }

        $parts = explode('-', $date);

        return checkdate((int) $parts[1], (int) $parts[2], (int) $parts[0]);
    }
}
