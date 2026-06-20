<?php

if (!defined('ABSPATH')) {
    exit;
}

class DF_Subscriptions_Shortcodes
{
    private $access;

    public function __construct(DF_Subscriptions_Access $access)
    {
        $this->access = $access;
    }

    public function hooks()
    {
        add_action('init', array($this, 'handle_login_submission'));
        add_action('init', array($this, 'handle_lostpassword_submission'));
        add_action('init', array($this, 'handle_resetpass_submission'));
        add_action('wp_ajax_df_filter_issues', array($this, 'ajax_filter_issues'));
        add_action('wp_ajax_nopriv_df_filter_issues', array($this, 'ajax_filter_issues'));
        add_action('wp_ajax_df_filter_articles', array($this, 'ajax_filter_articles'));
        add_action('wp_ajax_nopriv_df_filter_articles', array($this, 'ajax_filter_articles'));
        add_shortcode('df_logout_link', array($this, 'logout_link'));
        add_shortcode('df_all_issues', array($this, 'all_issues'));
        add_shortcode('df_all_articles', array($this, 'all_articles'));
        add_shortcode('df_article_page', array($this, 'article_page'));
        add_shortcode('df_homepage', array($this, 'homepage'));
        add_shortcode('df_magazine_archive', array($this, 'magazine_archive'));
        add_shortcode('df_article_archive', array($this, 'article_archive'));
        add_shortcode('df_magazine_page', array($this, 'magazine_page'));
        add_action('wp_ajax_df_filter_posts', array($this, 'ajax_filter_posts'));
        add_action('wp_ajax_nopriv_df_filter_posts', array($this, 'ajax_filter_posts'));
        add_shortcode('df_all_posts', array($this, 'all_posts'));
        add_shortcode('df_post_page', array($this, 'post_page'));
        add_shortcode('df_blog_archive', array($this, 'blog_archive'));
    }

    public function handle_login_submission()
    {
        if (!isset($_POST['df_login_form_action'])) {
            return;
        }

        $form_url = isset($_POST['df_login_form_url']) ? esc_url_raw(wp_unslash($_POST['df_login_form_url'])) : home_url('/');
        $redirect_to = isset($_POST['df_redirect_to']) ? esc_url_raw(wp_unslash($_POST['df_redirect_to'])) : $form_url;

        if (!isset($_POST['df_login_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['df_login_nonce'])), 'df_login_form')) {
            $this->redirect_to_login_form($form_url, 'security');
        }

        $username = isset($_POST['df_username']) ? sanitize_user(wp_unslash($_POST['df_username'])) : '';
        $password = isset($_POST['df_password']) ? (string) wp_unslash($_POST['df_password']) : '';
        $remember = !empty($_POST['df_remember']);

        if (!$username || !$password) {
            $this->redirect_to_login_form($form_url, 'empty');
        }

        $user = wp_signon(
            array(
                'user_login' => $username,
                'user_password' => $password,
                'remember' => $remember,
            ),
            is_ssl()
        );

        if (is_wp_error($user)) {
            $this->redirect_to_login_form($form_url, 'invalid');
        }

        wp_safe_redirect(wp_validate_redirect($redirect_to, home_url('/')));
        exit;
    }

    public function handle_lostpassword_submission()
    {
        if (!isset($_POST['df_lostpassword_action'])) {
            return;
        }

        $form_url = isset($_POST['df_form_url']) ? esc_url_raw(wp_unslash($_POST['df_form_url'])) : home_url('/');

        if (!isset($_POST['df_lostpassword_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['df_lostpassword_nonce'])), 'df_lostpassword')) {
            wp_safe_redirect(add_query_arg(array('df_action' => 'lostpassword', 'df_msg' => 'empty'), $form_url));
            exit;
        }

        $login = isset($_POST['df_user_login']) ? trim(sanitize_text_field(wp_unslash($_POST['df_user_login']))) : '';

        if ('' === $login) {
            wp_safe_redirect(add_query_arg(array('df_action' => 'lostpassword', 'df_msg' => 'empty'), $form_url));
            exit;
        }

        $user = is_email($login) ? get_user_by('email', $login) : get_user_by('login', $login);

        if (!$user) {
            $user = is_email($login) ? get_user_by('login', $login) : get_user_by('email', $login);
        }

        if ($user) {
            $key = get_password_reset_key($user);

            if (!is_wp_error($key)) {
                $reset_url = add_query_arg(
                    array(
                        'df_action' => 'resetpass',
                        'key'       => $key,
                        'login'     => $user->user_login,
                    ),
                    $form_url
                );
                wp_mail(
                    $user->user_email,
                    $this->t('reset_email_subject'),
                    sprintf($this->t('reset_email_body'), $reset_url)
                );
            }
        }

        // Always show the same confirmation so the form does not reveal whether an account exists.
        wp_safe_redirect(add_query_arg(array('df_action' => 'lostpassword', 'df_msg' => 'sent'), $form_url));
        exit;
    }

    public function handle_resetpass_submission()
    {
        if (!isset($_POST['df_resetpass_action'])) {
            return;
        }

        $form_url = isset($_POST['df_form_url']) ? esc_url_raw(wp_unslash($_POST['df_form_url'])) : home_url('/');
        $key = isset($_POST['df_rp_key']) ? sanitize_text_field(wp_unslash($_POST['df_rp_key'])) : '';
        $login = isset($_POST['df_rp_login']) ? sanitize_text_field(wp_unslash($_POST['df_rp_login'])) : '';
        $reset_url = add_query_arg(array('df_action' => 'resetpass', 'key' => $key, 'login' => $login), $form_url);

        if (!isset($_POST['df_resetpass_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['df_resetpass_nonce'])), 'df_resetpass')) {
            wp_safe_redirect(add_query_arg('df_action', 'lostpassword', $form_url));
            exit;
        }

        $user = ($key && $login) ? check_password_reset_key($key, $login) : new WP_Error('missing_key');

        if (is_wp_error($user)) {
            wp_safe_redirect(add_query_arg('df_action', 'lostpassword', $form_url));
            exit;
        }

        $pass1 = isset($_POST['df_pass1']) ? (string) wp_unslash($_POST['df_pass1']) : '';
        $pass2 = isset($_POST['df_pass2']) ? (string) wp_unslash($_POST['df_pass2']) : '';

        if (strlen($pass1) < 8) {
            wp_safe_redirect(add_query_arg('df_msg', 'short', $reset_url));
            exit;
        }

        if ($pass1 !== $pass2) {
            wp_safe_redirect(add_query_arg('df_msg', 'mismatch', $reset_url));
            exit;
        }

        reset_password($user, $pass1);

        wp_safe_redirect(add_query_arg('df_msg', 'resetdone', $form_url));
        exit;
    }

    public function login_form($atts)
    {
        $atts = shortcode_atts(
            array(
                'redirect' => '',
            ),
            $atts,
            'df_login_form'
        );

        $action = isset($_GET['df_action']) ? sanitize_key(wp_unslash($_GET['df_action'])) : '';

        if ('lostpassword' === $action) {
            return '<div class="df-subscriptions-login-form">' . $this->render_lostpassword_form() . '</div>';
        }

        if ('resetpass' === $action) {
            return '<div class="df-subscriptions-login-form">' . $this->render_resetpass_form() . '</div>';
        }

        if (is_user_logged_in()) {
            $message = $this->access->current_user_can_read_paid_content()
                ? $this->t('login_active')
                : $this->t('login_inactive');

            return '<div class="df-subscriptions-login-status"><p>' . esc_html($message) . '</p>' . $this->logout_link(array()) . '</div>';
        }

        $redirect = $atts['redirect'] ? esc_url_raw($atts['redirect']) : $this->get_redirect_url();
        $form_url = $this->get_current_url();
        $error = $this->get_login_error_message();
        $notice = (isset($_GET['df_msg']) && 'resetdone' === sanitize_key(wp_unslash($_GET['df_msg']))) ? $this->t('reset_done') : '';
        $form_id = wp_unique_id('df-subscriptions-login-form-');
        $username_id = $form_id . '-username';
        $password_id = $form_id . '-password';

        ob_start();
        ?>
        <?php if ($error) : ?>
            <div class="df-subscriptions-login-error" role="alert"><?php echo esc_html($error); ?></div>
        <?php endif; ?>
        <?php if ($notice) : ?>
            <div class="df-subscriptions-login-notice" role="status"><?php echo esc_html($notice); ?></div>
        <?php endif; ?>
        <form id="<?php echo esc_attr($form_id); ?>" class="df-subscriptions-login" method="post" action="<?php echo esc_url($form_url); ?>">
            <?php wp_nonce_field('df_login_form', 'df_login_nonce'); ?>
            <input type="hidden" name="df_login_form_action" value="1">
            <input type="hidden" name="df_redirect_to" value="<?php echo esc_attr($redirect); ?>">
            <input type="hidden" name="df_login_form_url" value="<?php echo esc_attr($form_url); ?>">

            <p class="login-username">
                <label for="<?php echo esc_attr($username_id); ?>"><?php echo esc_html($this->t('login_username')); ?></label>
                <input type="text" name="df_username" id="<?php echo esc_attr($username_id); ?>" autocomplete="username" required>
            </p>
            <p class="login-password">
                <label for="<?php echo esc_attr($password_id); ?>"><?php echo esc_html($this->t('login_password')); ?></label>
                <input type="password" name="df_password" id="<?php echo esc_attr($password_id); ?>" autocomplete="current-password" required>
            </p>
            <p class="login-remember">
                <label><input name="df_remember" type="checkbox" value="1"> <?php echo esc_html($this->t('login_remember')); ?></label>
            </p>
            <p class="login-submit">
                <button type="submit" class="button df-subscriptions-login-button"><?php echo esc_html($this->t('login_submit')); ?></button>
            </p>
            <p class="df-subscriptions-lost-password">
                <a href="<?php echo esc_url(add_query_arg('df_action', 'lostpassword', $form_url)); ?>"><?php echo esc_html($this->t('login_forgot')); ?></a>
            </p>
        </form>
        <?php

        return '<div class="df-subscriptions-login-form">' . ob_get_clean() . '</div>';
    }

    private function render_lostpassword_form()
    {
        $form_url = remove_query_arg(array('df_action', 'df_msg', 'key', 'login'), $this->get_current_url());
        $msg = isset($_GET['df_msg']) ? sanitize_key(wp_unslash($_GET['df_msg'])) : '';
        $field_id = wp_unique_id('df-subscriptions-lostpassword-');

        ob_start();

        if ('sent' === $msg) {
            ?>
            <div class="df-subscriptions-login-notice" role="status"><?php echo esc_html($this->t('lost_sent')); ?></div>
            <p class="df-subscriptions-lost-password">
                <a href="<?php echo esc_url($form_url); ?>"><?php echo esc_html($this->t('lost_back')); ?></a>
            </p>
            <?php
            return ob_get_clean();
        }
        ?>
        <?php if ('empty' === $msg) : ?>
            <div class="df-subscriptions-login-error" role="alert"><?php echo esc_html($this->t('lost_empty')); ?></div>
        <?php endif; ?>
        <p class="df-subscriptions-lost-password"><?php echo esc_html($this->t('lost_intro')); ?></p>
        <form class="df-subscriptions-login" method="post" action="<?php echo esc_url(add_query_arg('df_action', 'lostpassword', $form_url)); ?>">
            <?php wp_nonce_field('df_lostpassword', 'df_lostpassword_nonce'); ?>
            <input type="hidden" name="df_lostpassword_action" value="1">
            <input type="hidden" name="df_form_url" value="<?php echo esc_attr($form_url); ?>">
            <p class="login-username">
                <label for="<?php echo esc_attr($field_id); ?>"><?php echo esc_html($this->t('login_username')); ?></label>
                <input type="text" name="df_user_login" id="<?php echo esc_attr($field_id); ?>" autocomplete="username" required>
            </p>
            <p class="login-submit">
                <button type="submit" class="button df-subscriptions-login-button"><?php echo esc_html($this->t('lost_submit')); ?></button>
            </p>
            <p class="df-subscriptions-lost-password">
                <a href="<?php echo esc_url($form_url); ?>"><?php echo esc_html($this->t('lost_back')); ?></a>
            </p>
        </form>
        <?php

        return ob_get_clean();
    }

    private function render_resetpass_form()
    {
        $form_url = remove_query_arg(array('df_action', 'df_msg', 'key', 'login'), $this->get_current_url());
        $key = isset($_GET['key']) ? sanitize_text_field(wp_unslash($_GET['key'])) : '';
        $login = isset($_GET['login']) ? sanitize_text_field(wp_unslash($_GET['login'])) : '';
        $msg = isset($_GET['df_msg']) ? sanitize_key(wp_unslash($_GET['df_msg'])) : '';
        $user = ($key && $login) ? check_password_reset_key($key, $login) : new WP_Error('missing_key');

        ob_start();

        if (is_wp_error($user)) {
            ?>
            <div class="df-subscriptions-login-error" role="alert"><?php echo esc_html($this->t('reset_invalid_key')); ?></div>
            <p class="df-subscriptions-lost-password">
                <a href="<?php echo esc_url(add_query_arg('df_action', 'lostpassword', $form_url)); ?>"><?php echo esc_html($this->t('login_forgot')); ?></a>
            </p>
            <?php
            return ob_get_clean();
        }

        $pass1_id = wp_unique_id('df-subscriptions-pass1-');
        $pass2_id = wp_unique_id('df-subscriptions-pass2-');
        ?>
        <?php if ('mismatch' === $msg) : ?>
            <div class="df-subscriptions-login-error" role="alert"><?php echo esc_html($this->t('reset_mismatch')); ?></div>
        <?php elseif ('short' === $msg) : ?>
            <div class="df-subscriptions-login-error" role="alert"><?php echo esc_html($this->t('reset_too_short')); ?></div>
        <?php endif; ?>
        <p class="df-subscriptions-lost-password"><?php echo esc_html($this->t('reset_intro')); ?></p>
        <form class="df-subscriptions-login" method="post" action="<?php echo esc_url($form_url); ?>">
            <?php wp_nonce_field('df_resetpass', 'df_resetpass_nonce'); ?>
            <input type="hidden" name="df_resetpass_action" value="1">
            <input type="hidden" name="df_form_url" value="<?php echo esc_attr($form_url); ?>">
            <input type="hidden" name="df_rp_key" value="<?php echo esc_attr($key); ?>">
            <input type="hidden" name="df_rp_login" value="<?php echo esc_attr($login); ?>">
            <p class="login-password">
                <label for="<?php echo esc_attr($pass1_id); ?>"><?php echo esc_html($this->t('reset_password_new')); ?></label>
                <input type="password" name="df_pass1" id="<?php echo esc_attr($pass1_id); ?>" autocomplete="new-password" required>
            </p>
            <p class="login-password">
                <label for="<?php echo esc_attr($pass2_id); ?>"><?php echo esc_html($this->t('reset_password_confirm')); ?></label>
                <input type="password" name="df_pass2" id="<?php echo esc_attr($pass2_id); ?>" autocomplete="new-password" required>
            </p>
            <p class="login-submit">
                <button type="submit" class="button df-subscriptions-login-button"><?php echo esc_html($this->t('reset_submit')); ?></button>
            </p>
        </form>
        <?php

        return ob_get_clean();
    }

    public function logout_link($atts)
    {
        $atts = shortcode_atts(
            array(
                'text' => __('Log out', 'der-flugschreiber-subscriptions'),
                'redirect' => home_url('/'),
            ),
            $atts,
            'df_logout_link'
        );

        if (!is_user_logged_in()) {
            return '';
        }

        return sprintf(
            '<a class="df-subscriptions-logout-link" href="%s">%s</a>',
            esc_url(wp_logout_url(esc_url_raw($atts['redirect']))),
            esc_html($atts['text'])
        );
    }

    public function all_issues($atts)
    {
        $atts = shortcode_atts(
            array(
                'initial' => 12,
                'step' => 4,
                'title' => __('All Issues', 'der-flugschreiber-subscriptions'),
            ),
            $atts,
            'df_all_issues'
        );

        $years = get_terms(
            array(
                'taxonomy' => DF_Subscriptions::ISSUE_YEAR_TAXONOMY,
                'hide_empty' => true,
                'orderby' => 'name',
                'order' => 'DESC',
            )
        );
        $topics = get_terms(
            array(
                'taxonomy' => DF_Subscriptions::TOPIC_TAXONOMY,
                'hide_empty' => true,
                'orderby' => 'count',
                'order' => 'DESC',
            )
        );
        $years = is_wp_error($years) ? array() : $years;
        $topics = is_wp_error($topics) ? array() : $topics;

        ob_start();
        ?>
        <section class="df-issues" data-initial="<?php echo esc_attr(absint($atts['initial'])); ?>" data-step="<?php echo esc_attr(absint($atts['step'])); ?>" data-page="1">
            <h2 class="df-issues__title"><?php echo esc_html($atts['title']); ?></h2>

            <div class="df-issues__controls" aria-label="<?php esc_attr_e('Issue filters', 'der-flugschreiber-subscriptions'); ?>">
                <div class="df-issues__type-toggle" role="group" aria-label="<?php esc_attr_e('Issue type', 'der-flugschreiber-subscriptions'); ?>">
                    <button type="button" class="df-issues__type-button is-active" data-filter-type="paid"><?php echo esc_html($this->t('issues_tab_new')); ?></button>
                    <button type="button" class="df-issues__type-button" data-filter-type="free_pdf"><?php echo esc_html($this->t('issues_tab_old')); ?></button>
                </div>

                <?php
                echo $this->render_filter_dropdown(
                    'year',
                    $this->t('filter_year'),
                    $this->t('filter_year_all'),
                    wp_list_pluck($years, 'name', 'slug')
                ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                echo $this->render_filter_dropdown(
                    'topic',
                    $this->t('filter_topic'),
                    $this->t('filter_topic_all'),
                    wp_list_pluck($topics, 'name', 'slug')
                ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                echo $this->render_filter_dropdown(
                    'sort',
                    $this->t('filter_sort'),
                    $this->t('sort_newest'),
                    array(
                        'newest' => $this->t('sort_newest'),
                        'oldest' => $this->t('sort_oldest'),
                        'title'  => $this->t('sort_title'),
                    ),
                    'newest',
                    true
                ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                ?>
            </div>

            <?php if (!empty($topics) && !is_wp_error($topics)) : ?>
                <div class="df-issues__popular" aria-label="<?php echo esc_attr($this->t('issues_popular_label')); ?>">
                    <span class="df-issues__popular-label"><?php echo esc_html($this->t('issues_popular_label')); ?></span>
                    <div class="df-issues__popular-list">
                        <?php foreach (array_slice($topics, 0, 8) as $topic) : ?>
                            <button type="button" class="df-issues__chip" data-topic-chip="<?php echo esc_attr($topic->slug); ?>"><?php echo esc_html($topic->name); ?></button>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php $initial_result = $this->get_issues_result(array('type' => 'paid', 'limit' => absint($atts['initial']), 'page' => 1)); ?>

            <div class="df-issues__grid" aria-live="polite">
                <?php echo $initial_result['html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            </div>

            <p class="df-issues__empty" <?php echo $initial_result['found'] ? 'hidden' : ''; ?>><?php echo esc_html($this->t('issues_empty')); ?></p>

            <div class="df-issues__more-wrap">
                <button type="button" class="df-issues__more" <?php echo $initial_result['has_more'] ? '' : 'hidden'; ?>><?php echo esc_html($this->t('list_show_more')); ?></button>
            </div>
        </section>
        <?php

        return ob_get_clean();
    }

    public function homepage($atts)
    {
        $atts = shortcode_atts(
            array(
                'price' => '38,25 €',
                'regular_price' => '51,00 €',
                'issues_per_year' => '4',
                'discount' => '-25%',
                'subscription_url' => '',
                'issues_url' => '',
                'articles_url' => '',
            ),
            $atts,
            'df_homepage'
        );

        $subscription_url = $atts['subscription_url']
            ? esc_url_raw($atts['subscription_url'])
            : get_option(DF_Subscriptions::PAYMENT_URL_OPTION, '');
        $subscription_url = $subscription_url ? $subscription_url : home_url('/');
        $issues_url = $atts['issues_url']
            ? esc_url_raw($atts['issues_url'])
            : get_post_type_archive_link(DF_Subscriptions::MAGAZINE_POST_TYPE);
        $articles_url = $atts['articles_url']
            ? esc_url_raw($atts['articles_url'])
            : get_post_type_archive_link(DF_Subscriptions::ARTICLE_POST_TYPE);
        $issues_url = $issues_url ? $issues_url : home_url('/');
        $articles_url = $articles_url ? $articles_url : home_url('/');

        $issues = get_posts(
            array(
                'post_type' => DF_Subscriptions::MAGAZINE_POST_TYPE,
                'numberposts' => 4,
                'post_status' => 'publish',
                'meta_key' => DF_Subscriptions::MAGAZINE_ISSUE_DATE_META,
                'orderby' => 'meta_value',
                'order' => 'DESC',
            )
        );
        $articles = get_posts(
            array(
                'post_type' => DF_Subscriptions::ARTICLE_POST_TYPE,
                'numberposts' => 3,
                'post_status' => 'publish',
                'orderby' => 'date',
                'order' => 'DESC',
            )
        );
        $latest_issue = !empty($issues) ? $issues[0] : null;
        $latest_cover = $latest_issue ? $this->get_issue_cover_url($latest_issue) : '';
        $latest_number = $latest_issue
            ? get_post_meta($latest_issue->ID, DF_Subscriptions::MAGAZINE_ISSUE_NUMBER_META, true)
            : '';
        $latest_date = $latest_issue
            ? get_post_meta($latest_issue->ID, DF_Subscriptions::MAGAZINE_ISSUE_DATE_META, true)
            : '';
        $latest_date_label = $latest_date ? date_i18n('F Y', strtotime($latest_date)) : '';

        ob_start();
        ?>
        <div class="df-home">
            <section class="df-home__hero">
                <div class="df-home__hero-copy">
                    <p class="df-home__eyebrow"><?php echo esc_html($this->t('home_eyebrow')); ?></p>
                    <h1><?php echo esc_html($this->t('home_headline')); ?></h1>
                    <p class="df-home__lead"><?php echo esc_html($this->t('home_lead')); ?></p>

                    <div class="df-home__offer">
                        <div>
                            <span class="df-home__offer-label"><?php echo esc_html($this->t('home_offer_label')); ?></span>
                            <span class="df-home__price"><?php echo esc_html($atts['price']); ?></span>
                        </div>
                        <div class="df-home__offer-detail">
                            <span><?php echo esc_html(sprintf($this->t('home_offer_detail'), $atts['issues_per_year'])); ?></span>
                            <del><?php echo esc_html($atts['regular_price']); ?></del>
                        </div>
                    </div>

                    <div class="df-home__actions">
                        <a class="df-home__button df-home__button--primary" href="<?php echo esc_url($subscription_url); ?>"><?php echo esc_html($this->t('home_subscribe_btn')); ?></a>
                        <a class="df-home__text-link" href="#df-home-current-issue"><?php echo esc_html($this->t('home_hero_link')); ?></a>
                    </div>
                </div>

                <div class="df-home__hero-visual">
                    <span class="df-home__discount"><?php echo esc_html($atts['discount']); ?></span>
                    <?php if ($latest_cover) : ?>
                        <img src="<?php echo esc_url($latest_cover); ?>" alt="<?php echo esc_attr($latest_issue ? get_the_title($latest_issue) : ''); ?>">
                    <?php else : ?>
                        <div class="df-home__cover-placeholder">
                            <img src="<?php echo esc_url(DF_SUBSCRIPTIONS_PLANE_URL); ?>" alt="" aria-hidden="true">
                            <span>DER<br>FLUGSCHREIBER</span>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

            <section class="df-home__promise" aria-label="<?php esc_attr_e('Ihre Vorteile', 'der-flugschreiber-subscriptions'); ?>">
                <div><strong><?php echo esc_html($atts['issues_per_year']); ?></strong><span><?php echo esc_html($this->t('home_promise_1_label')); ?></span></div>
                <div><strong><?php echo esc_html($this->t('home_promise_2_value')); ?></strong><span><?php echo esc_html($this->t('home_promise_2_label')); ?></span></div>
                <div><strong><?php echo esc_html($this->t('home_promise_3_value')); ?></strong><span><?php echo esc_html($this->t('home_promise_3_label')); ?></span></div>
            </section>

            <section class="df-home__current" id="df-home-current-issue">
                <div class="df-home__section-heading">
                    <p class="df-home__eyebrow"><?php echo esc_html($this->t('home_current_eyebrow')); ?></p>
                    <h2><?php echo esc_html($latest_issue ? get_the_title($latest_issue) : __('Der Flugschreiber', 'der-flugschreiber-subscriptions')); ?></h2>
                </div>

                <div class="df-home__current-grid">
                    <div class="df-home__current-cover">
                        <?php if ($latest_cover) : ?>
                            <img src="<?php echo esc_url($latest_cover); ?>" alt="<?php echo esc_attr($latest_issue ? get_the_title($latest_issue) : ''); ?>" loading="lazy">
                        <?php else : ?>
                            <div class="df-home__cover-placeholder">
                                <img src="<?php echo esc_url(DF_SUBSCRIPTIONS_PLANE_ALT_URL); ?>" alt="" aria-hidden="true">
                                <span>DER<br>FLUGSCHREIBER</span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="df-home__current-copy">
                        <?php if ($latest_date_label) : ?>
                            <p class="df-home__issue-meta"><?php echo esc_html(sprintf(__('Erschienen im %s', 'der-flugschreiber-subscriptions'), $latest_date_label)); ?></p>
                        <?php endif; ?>
                        <h3><?php echo esc_html($this->t('home_current_intro')); ?></h3>
                        <p><?php echo esc_html($this->get_issue_summary($latest_issue)); ?></p>
                        <div class="df-home__actions">
                            <a class="df-home__button df-home__button--primary" href="<?php echo esc_url($subscription_url); ?>"><?php echo esc_html($this->t('home_current_btn')); ?></a>
                            <?php if ($latest_issue) : ?>
                                <a class="df-home__text-link" href="<?php echo esc_url(get_permalink($latest_issue)); ?>"><?php echo esc_html($this->t('home_current_more')); ?></a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </section>

            <section class="df-home__topics">
                <div class="df-home__section-heading">
                    <p class="df-home__eyebrow"><?php echo esc_html($this->t('home_topics_eyebrow')); ?></p>
                    <h2><?php echo esc_html($this->t('home_topics_headline')); ?></h2>
                </div>
                <div class="df-home__topic-grid">
                    <article><span>01</span><h3><?php echo esc_html($this->t('home_topic_1_title')); ?></h3><p><?php echo esc_html($this->t('home_topic_1_body')); ?></p></article>
                    <article><span>02</span><h3><?php echo esc_html($this->t('home_topic_2_title')); ?></h3><p><?php echo esc_html($this->t('home_topic_2_body')); ?></p></article>
                    <article><span>03</span><h3><?php echo esc_html($this->t('home_topic_3_title')); ?></h3><p><?php echo esc_html($this->t('home_topic_3_body')); ?></p></article>
                    <article><span>04</span><h3><?php echo esc_html($this->t('home_topic_4_title')); ?></h3><p><?php echo esc_html($this->t('home_topic_4_body')); ?></p></article>
                </div>
            </section>

            <?php if (!empty($articles)) : ?>
                <section class="df-home__editorial">
                    <div class="df-home__section-heading df-home__section-heading--row">
                        <div>
                            <p class="df-home__eyebrow"><?php echo esc_html($this->t('home_articles_eyebrow')); ?></p>
                            <h2><?php echo esc_html($this->t('home_articles_headline')); ?></h2>
                        </div>
                        <a class="df-home__text-link" href="<?php echo esc_url($articles_url); ?>"><?php echo esc_html($this->t('home_articles_all')); ?></a>
                    </div>
                    <div class="df-home__article-grid">
                        <?php foreach ($articles as $article) : ?>
                            <?php $article_image = $this->get_article_image_url($article, (int) get_post_meta($article->ID, DF_Subscriptions::ARTICLE_MAGAZINE_META, true)); ?>
                            <article class="df-home__article-card">
                                <a class="df-home__article-image" href="<?php echo esc_url(get_permalink($article)); ?>">
                                    <?php if ($article_image) : ?>
                                        <img src="<?php echo esc_url($article_image); ?>" alt="<?php echo esc_attr(get_the_title($article)); ?>" loading="lazy">
                                    <?php else : ?>
                                        <span><img src="<?php echo esc_url(DF_SUBSCRIPTIONS_PLANE_ALT_URL); ?>" alt="" aria-hidden="true"></span>
                                    <?php endif; ?>
                                </a>
                                <p><?php echo esc_html(get_the_date('j. F Y', $article)); ?></p>
                                <h3><a href="<?php echo esc_url(get_permalink($article)); ?>"><?php echo esc_html(get_the_title($article)); ?></a></h3>
                                <a class="df-home__text-link" href="<?php echo esc_url(get_permalink($article)); ?>"><?php echo esc_html($this->t('home_article_read')); ?></a>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

            <?php if (count($issues) > 1) : ?>
                <section class="df-home__archive">
                    <div class="df-home__section-heading df-home__section-heading--row">
                        <div>
                            <p class="df-home__eyebrow"><?php echo esc_html($this->t('home_archive_eyebrow')); ?></p>
                            <h2><?php echo esc_html($this->t('home_archive_headline')); ?></h2>
                        </div>
                        <a class="df-home__text-link" href="<?php echo esc_url($issues_url); ?>"><?php echo esc_html($this->t('home_archive_all')); ?></a>
                    </div>
                    <div class="df-home__issue-grid">
                        <?php foreach (array_slice($issues, 1, 3) as $issue) : ?>
                            <?php
                            $cover = $this->get_issue_cover_url($issue);
                            $number = get_post_meta($issue->ID, DF_Subscriptions::MAGAZINE_ISSUE_NUMBER_META, true);
                            ?>
                            <article>
                                <a href="<?php echo esc_url(get_permalink($issue)); ?>">
                                    <?php if ($cover) : ?>
                                        <img src="<?php echo esc_url($cover); ?>" alt="<?php echo esc_attr(get_the_title($issue)); ?>" loading="lazy">
                                    <?php else : ?>
                                        <span class="df-home__issue-placeholder"><img src="<?php echo esc_url(DF_SUBSCRIPTIONS_PLANE_URL); ?>" alt="" aria-hidden="true"></span>
                                    <?php endif; ?>
                                </a>
                                <p><?php echo esc_html($number); ?></p>
                                <h3><a href="<?php echo esc_url(get_permalink($issue)); ?>"><?php echo esc_html(get_the_title($issue)); ?></a></h3>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

            <section class="df-home__about">
                <div>
                    <p class="df-home__eyebrow"><?php echo esc_html($this->t('home_about_eyebrow')); ?></p>
                    <h2><?php echo esc_html($this->t('home_about_headline')); ?></h2>
                </div>
                <div>
                    <p><?php echo esc_html($this->t('home_about_text_1')); ?></p>
                    <p><?php echo esc_html($this->t('home_about_text_2')); ?></p>
                </div>
            </section>

            <section class="df-home__final-cta">
                <img src="<?php echo esc_url(DF_SUBSCRIPTIONS_PLANE_URL); ?>" alt="" aria-hidden="true">
                <p class="df-home__eyebrow"><?php echo esc_html($this->t('home_cta_eyebrow')); ?></p>
                <h2><?php echo esc_html($this->t('home_cta_headline')); ?></h2>
                <p><?php echo esc_html(sprintf($this->t('home_cta_text'), $atts['price'])); ?></p>
                <a class="df-home__button df-home__button--light" href="<?php echo esc_url($subscription_url); ?>"><?php echo esc_html($this->t('home_subscribe_btn')); ?></a>
            </section>
        </div>
        <?php

        return ob_get_clean();
    }

    public function magazine_archive($atts)
    {
        $atts = shortcode_atts(
            array(
                'title' => __('Alle Ausgaben', 'der-flugschreiber-subscriptions'),
                'intro' => __('Entdecken Sie aktuelle und vergangene Ausgaben des Flugschreibers. Neue Ausgaben sind Teil des Abonnements, ältere Ausgaben stehen teilweise als freie PDF-Dateien bereit.', 'der-flugschreiber-subscriptions'),
                'initial' => 12,
                'step' => 4,
            ),
            $atts,
            'df_magazine_archive'
        );

        ob_start();
        ?>
        <div class="df-collection-page df-collection-page--issues">
            <header class="df-collection-page__hero">
                <p class="df-collection-page__eyebrow"><?php echo esc_html($this->t('blog_eyebrow')); ?></p>
                <h1><?php echo esc_html($atts['title']); ?></h1>
                <p><?php echo esc_html($atts['intro']); ?></p>
            </header>
            <?php
            echo $this->all_issues(
                array(
                    'initial' => absint($atts['initial']),
                    'step' => absint($atts['step']),
                    'title' => $atts['title'],
                )
            ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            ?>
        </div>
        <?php

        return ob_get_clean();
    }

    public function article_archive($atts)
    {
        $atts = shortcode_atts(
            array(
                'title' => __('Alle Artikel', 'der-flugschreiber-subscriptions'),
                'intro' => __('Reportagen, Fachwissen und persönliche Geschichten aus der Welt der Luftfahrt - geschrieben von Piloten und Flugbegeisterten.', 'der-flugschreiber-subscriptions'),
                'initial' => 6,
                'step' => 6,
                'magazine' => 0,
            ),
            $atts,
            'df_article_archive'
        );

        ob_start();
        ?>
        <div class="df-collection-page df-collection-page--articles">
            <header class="df-collection-page__hero">
                <p class="df-collection-page__eyebrow"><?php echo esc_html($this->t('articles_eyebrow')); ?></p>
                <h1><?php echo esc_html($atts['title']); ?></h1>
                <p><?php echo esc_html($atts['intro']); ?></p>
            </header>
            <?php
            echo $this->all_articles(
                array(
                    'initial' => absint($atts['initial']),
                    'step' => absint($atts['step']),
                    'magazine' => absint($atts['magazine']),
                    'title' => $atts['title'],
                )
            ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            ?>
        </div>
        <?php

        return ob_get_clean();
    }

    public function magazine_page($atts)
    {
        $atts = shortcode_atts(
            array(
                'magazine' => 0,
                'show_back' => 'yes',
                'back_url' => '',
                'back_text' => __('Alle Ausgaben', 'der-flugschreiber-subscriptions'),
                'articles_title' => __('Artikel dieser Ausgabe', 'der-flugschreiber-subscriptions'),
                'articles_initial' => 5,
                'articles_step' => 5,
            ),
            $atts,
            'df_magazine_page'
        );
        $magazine_id = absint($atts['magazine']);

        if (!$magazine_id && is_singular(DF_Subscriptions::MAGAZINE_POST_TYPE)) {
            $magazine_id = get_queried_object_id();
        }

        $magazine = $magazine_id ? get_post($magazine_id) : null;

        if (
            !$magazine
            || DF_Subscriptions::MAGAZINE_POST_TYPE !== $magazine->post_type
            || ('publish' !== $magazine->post_status && !current_user_can('read_post', $magazine->ID))
        ) {
            return '';
        }

        $cover = $this->get_issue_cover_url($magazine);
        $number = get_post_meta($magazine->ID, DF_Subscriptions::MAGAZINE_ISSUE_NUMBER_META, true);
        $issue_date = get_post_meta($magazine->ID, DF_Subscriptions::MAGAZINE_ISSUE_DATE_META, true);
        $date_label = $issue_date ? date_i18n('j. F Y', strtotime($issue_date)) : '';
        $access_type = get_post_meta($magazine->ID, DF_Subscriptions::MAGAZINE_ACCESS_META, true);
        $is_free = 'free_pdf' === $access_type;
        $can_read = $is_free
            || $this->access->current_user_can_read_paid_content()
            || current_user_can('edit_post', $magazine->ID);
        $pdf_url = $is_free ? get_post_meta($magazine->ID, DF_Subscriptions::MAGAZINE_PDF_META, true) : '';

        if ($can_read && !$is_free) {
            $pdf_url = DF_Subscriptions::instance()->content->get_protected_pdf_url($magazine->ID);
        }

        $payment_url = get_option(DF_Subscriptions::PAYMENT_URL_OPTION, '');
        $login_url = get_option(DF_Subscriptions::LOGIN_URL_OPTION, '');
        $login_url = $login_url
            ? add_query_arg('redirect_to', get_permalink($magazine), $login_url)
            : wp_login_url(get_permalink($magazine));
        $back_url = $atts['back_url'] ? esc_url_raw($atts['back_url']) : get_post_type_archive_link(DF_Subscriptions::MAGAZINE_POST_TYPE);
        $back_url = $back_url ? $back_url : home_url('/');
        $raw_content = preg_replace('/\[df_magazine_page[^\]]*\]/', '', $magazine->post_content);
        $summary = $this->get_issue_summary($magazine);
        $content = $can_read
            ? wpautop(do_shortcode($raw_content))
            : wpautop(esc_html($summary));
        $article_count = (int) count(
            get_posts(
                array(
                    'post_type' => DF_Subscriptions::ARTICLE_POST_TYPE,
                    'numberposts' => -1,
                    'post_status' => 'publish',
                    'fields' => 'ids',
                    'meta_key' => DF_Subscriptions::ARTICLE_MAGAZINE_META,
                    'meta_value' => $magazine->ID,
                )
            )
        );

        ob_start();
        ?>
        <article class="df-magazine-page">
            <header class="df-magazine-page__hero">
                <?php if ('yes' === $atts['show_back']) : ?>
                    <a class="df-magazine-page__back" href="<?php echo esc_url($back_url); ?>"><span aria-hidden="true">&larr;</span> <?php echo esc_html($atts['back_text']); ?></a>
                <?php endif; ?>
                <div class="df-magazine-page__hero-grid">
                    <div class="df-magazine-page__cover-wrap">
                        <?php if ($cover) : ?>
                            <img class="df-magazine-page__cover" src="<?php echo esc_url($cover); ?>" alt="<?php echo esc_attr(get_the_title($magazine)); ?>">
                        <?php else : ?>
                            <span class="df-magazine-page__cover-placeholder"><img src="<?php echo esc_url(DF_SUBSCRIPTIONS_PLANE_URL); ?>" alt="" aria-hidden="true"></span>
                        <?php endif; ?>
                    </div>
                    <div class="df-magazine-page__heading">
                        <p class="df-magazine-page__eyebrow"><?php echo esc_html(trim($number . ($number && $date_label ? ' | ' : '') . $date_label)); ?></p>
                        <h1><?php echo esc_html(get_the_title($magazine)); ?></h1>
                        <p class="df-magazine-page__summary"><?php echo esc_html($summary); ?></p>
                        <div class="df-magazine-page__actions">
                            <?php if ($pdf_url) : ?>
                                <a class="df-magazine-page__button df-magazine-page__button--primary" href="<?php echo esc_url($pdf_url); ?>" target="_blank" rel="noopener"><?php echo esc_html($this->t('magazine_pdf_btn')); ?></a>
                            <?php elseif (!$can_read && $payment_url) : ?>
                                <a class="df-magazine-page__button df-magazine-page__button--primary" href="<?php echo esc_url($payment_url); ?>"><?php echo esc_html($this->t('magazine_subscribe_btn')); ?></a>
                            <?php endif; ?>
                            <?php if (!$can_read) : ?>
                                <a class="df-magazine-page__button" href="<?php echo esc_url($login_url); ?>"><?php echo esc_html($this->t('magazine_login_btn')); ?></a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </header>

            <?php if ($content) : ?>
                <section class="df-magazine-page__content <?php echo $can_read ? '' : 'is-preview'; ?>">
                    <?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                </section>
            <?php endif; ?>

            <?php if ($article_count > 0) : ?>
                <section class="df-magazine-page__articles-list">
                    <?php
                    echo $this->all_articles(
                        array(
                            'initial' => absint($atts['articles_initial']),
                            'step' => absint($atts['articles_step']),
                            'magazine' => $magazine->ID,
                            'title' => $atts['articles_title'],
                            'layout' => 'grid',
                        )
                    ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    ?>
                </section>
            <?php endif; ?>
        </article>
        <?php

        return ob_get_clean();
    }

    public function ajax_filter_issues()
    {
        check_ajax_referer('df_issues_filter', 'nonce');

        $limit = isset($_POST['limit']) ? min(50, max(1, absint($_POST['limit']))) : 12;
        $page = isset($_POST['page']) ? min(100, max(1, absint($_POST['page']))) : 1;
        $offset = isset($_POST['offset']) ? min(5000, absint($_POST['offset'])) : 0;
        $append = !empty($_POST['append']);

        $result = $this->get_issues_result(
            array(
                'type' => isset($_POST['type']) ? sanitize_key(wp_unslash($_POST['type'])) : 'paid',
                'year' => isset($_POST['year']) ? sanitize_title(wp_unslash($_POST['year'])) : '',
                'topic' => isset($_POST['topic']) ? sanitize_title(wp_unslash($_POST['topic'])) : '',
                'sort' => isset($_POST['sort']) ? sanitize_key(wp_unslash($_POST['sort'])) : 'newest',
                'limit' => $limit,
                'page' => $page,
                'offset' => $offset,
            )
        );

        wp_send_json_success(
            array(
                'html' => $result['html'],
                'found' => $result['found'],
                'hasMore' => $result['has_more'],
                'page' => $page,
                'append' => $append,
            )
        );
    }

    public function all_articles($atts)
    {
        $atts = shortcode_atts(
            array(
                'initial' => 5,
                'step' => 5,
                'title' => __('All Articles', 'der-flugschreiber-subscriptions'),
                'magazine' => 0,
                'layout' => 'list',
            ),
            $atts,
            'df_all_articles'
        );
        $layout = 'grid' === $atts['layout'] ? 'grid' : 'list';

        $magazines = get_posts(
            array(
                'post_type' => DF_Subscriptions::MAGAZINE_POST_TYPE,
                'numberposts' => -1,
                'orderby' => 'meta_value',
                'meta_key' => DF_Subscriptions::MAGAZINE_ISSUE_DATE_META,
                'order' => 'DESC',
                'post_status' => 'publish',
            )
        );
        $topics = get_terms(
            array(
                'taxonomy' => DF_Subscriptions::TOPIC_TAXONOMY,
                'hide_empty' => true,
                'orderby' => 'count',
                'order' => 'DESC',
            )
        );
        $topics = is_wp_error($topics) ? array() : $topics;
        $magazine_options = array();

        foreach ($magazines as $magazine) {
            $magazine_options[$magazine->ID] = get_the_title($magazine);
        }

        $selected_magazine_id = absint($atts['magazine']);

        if (!$selected_magazine_id && is_singular(DF_Subscriptions::MAGAZINE_POST_TYPE)) {
            $selected_magazine_id = get_queried_object_id();
        }

        if (!$selected_magazine_id && isset($_GET['df_magazine'])) {
            $selected_magazine_id = absint($_GET['df_magazine']);
        }

        if ($selected_magazine_id && !isset($magazine_options[$selected_magazine_id])) {
            $selected_magazine_id = 0;
        }

        $selected_magazine_label = $selected_magazine_id
            ? $magazine_options[$selected_magazine_id]
            : __('Magazine issue', 'der-flugschreiber-subscriptions');

        $initial_result = $this->get_articles_result(
            array(
                'magazine' => $selected_magazine_id,
                'limit' => absint($atts['initial']),
                'page' => 1,
                'layout' => $layout,
            )
        );

        ob_start();
        ?>
        <section class="df-articles<?php echo 'grid' === $layout ? ' df-articles--grid' : ''; ?>" data-initial="<?php echo esc_attr(absint($atts['initial'])); ?>" data-step="<?php echo esc_attr(absint($atts['step'])); ?>" data-page="1" data-layout="<?php echo esc_attr($layout); ?>">
            <h2 class="df-articles__title"><?php echo esc_html($atts['title']); ?></h2>

            <div class="df-articles__filters" aria-label="<?php esc_attr_e('Article filters', 'der-flugschreiber-subscriptions'); ?>">
                <?php
                echo $this->render_filter_dropdown(
                    'magazine',
                    $this->t('filter_magazine'),
                    $selected_magazine_label,
                    $magazine_options,
                    $selected_magazine_id
                ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                echo $this->render_filter_dropdown(
                    'topic',
                    $this->t('filter_topic'),
                    $this->t('filter_topic'),
                    wp_list_pluck($topics, 'name', 'slug')
                ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                echo $this->render_filter_dropdown(
                    'sort',
                    $this->t('filter_sort'),
                    $this->t('filter_sort'),
                    array(
                        'newest' => $this->t('sort_newest'),
                        'oldest' => $this->t('sort_oldest'),
                        'title'  => $this->t('sort_title'),
                    ),
                    'newest',
                    true
                ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                ?>
            </div>

            <?php if (!empty($topics)) : ?>
                <div class="df-articles__popular" aria-label="<?php echo esc_attr($this->t('issues_popular_label')); ?>">
                    <span class="df-articles__popular-label"><?php echo esc_html($this->t('issues_popular_label')); ?></span>
                    <div class="df-articles__popular-list">
                        <?php foreach (array_slice($topics, 0, 8) as $topic) : ?>
                            <button type="button" class="df-articles__chip" data-topic-chip="<?php echo esc_attr($topic->slug); ?>"><?php echo esc_html($topic->name); ?></button>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="df-articles__list" aria-live="polite">
                <?php echo $initial_result['html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            </div>

            <p class="df-articles__empty" <?php echo $initial_result['found'] ? 'hidden' : ''; ?>><?php echo esc_html($this->t('articles_empty')); ?></p>

            <div class="df-articles__more-wrap">
                <button type="button" class="df-articles__more" <?php echo $initial_result['has_more'] ? '' : 'hidden'; ?>><?php echo esc_html($this->t('list_show_more')); ?></button>
            </div>
        </section>
        <?php

        return ob_get_clean();
    }

    public function ajax_filter_articles()
    {
        check_ajax_referer('df_issues_filter', 'nonce');

        $limit = isset($_POST['limit']) ? min(50, max(1, absint($_POST['limit']))) : 5;
        $page = isset($_POST['page']) ? min(100, max(1, absint($_POST['page']))) : 1;
        $offset = isset($_POST['offset']) ? min(5000, absint($_POST['offset'])) : 0;
        $append = !empty($_POST['append']);

        $result = $this->get_articles_result(
            array(
                'magazine' => isset($_POST['magazine']) ? absint($_POST['magazine']) : 0,
                'topic' => isset($_POST['topic']) ? sanitize_title(wp_unslash($_POST['topic'])) : '',
                'sort' => isset($_POST['sort']) ? sanitize_key(wp_unslash($_POST['sort'])) : 'newest',
                'layout' => isset($_POST['layout']) && 'grid' === $_POST['layout'] ? 'grid' : 'list',
                'limit' => $limit,
                'page' => $page,
                'offset' => $offset,
            )
        );

        wp_send_json_success(
            array(
                'html' => $result['html'],
                'found' => $result['found'],
                'hasMore' => $result['has_more'],
                'page' => $page,
                'append' => $append,
            )
        );
    }

    public function article_page($atts)
    {
        $atts = shortcode_atts(
            array(
                'article' => 0,
                'show_back' => 'yes',
                'back_url' => '',
                'back_text' => __('Go Back', 'der-flugschreiber-subscriptions'),
                'button_text' => __('Read the full article', 'der-flugschreiber-subscriptions'),
            ),
            $atts,
            'df_article_page'
        );

        $article_id = absint($atts['article']);

        if (!$article_id && is_singular(DF_Subscriptions::ARTICLE_POST_TYPE)) {
            $article_id = get_queried_object_id();
        }

        if (!$article_id) {
            return '';
        }

        $article = get_post($article_id);

        if (
            !$article
            || DF_Subscriptions::ARTICLE_POST_TYPE !== $article->post_type
            || ('publish' !== $article->post_status && !current_user_can('read_post', $article->ID))
        ) {
            return '';
        }

        $magazine_id = (int) get_post_meta($article->ID, DF_Subscriptions::ARTICLE_MAGAZINE_META, true);
        $magazine = $magazine_id ? get_post($magazine_id) : null;
        $topics = wp_get_post_terms($article->ID, DF_Subscriptions::TOPIC_TAXONOMY);
        $topics = is_wp_error($topics) ? array() : $topics;
        $author = get_the_author_meta('display_name', (int) $article->post_author);
        $date = get_the_date('Y-m-d', $article);
        $date_label = get_the_date('j. F Y', $article);
        $word_count = str_word_count(wp_strip_all_tags($article->post_content));
        $duration = max(1, (int) ceil($word_count / 200));
        $hero_image = $this->get_article_image_url($article, $magazine_id);
        $magazine_cover = $magazine_id ? get_the_post_thumbnail_url($magazine_id, 'large') : '';
        $magazine_cover = $magazine_cover ? $magazine_cover : ($magazine_id ? get_post_meta($magazine_id, DF_Subscriptions::MAGAZINE_COVER_URL_META, true) : '');
        $magazine_date = $magazine_id ? get_post_meta($magazine_id, DF_Subscriptions::MAGAZINE_ISSUE_DATE_META, true) : '';
        $magazine_date_label = $magazine_date ? date_i18n('j. F Y', strtotime($magazine_date)) : $date_label;
        $magazine_number = $magazine_id ? get_post_meta($magazine_id, DF_Subscriptions::MAGAZINE_ISSUE_NUMBER_META, true) : '';
        $magazine_title = $magazine ? get_the_title($magazine) : '';
        $magazine_label = trim($magazine_number . ($magazine_number && $magazine_title ? ' | ' : '') . $magazine_title);
        $pdf_url = $magazine_id ? get_post_meta($magazine_id, DF_Subscriptions::MAGAZINE_PDF_META, true) : '';
        $can_read = $this->access->current_user_can_read_paid_content()
            || current_user_can('edit_post', $article->ID)
            || !$this->article_requires_subscription($article);

        if ($magazine_id && $can_read) {
            $protected_pdf_url = DF_Subscriptions::instance()->content->get_protected_pdf_url($magazine_id);

            if ($protected_pdf_url) {
                $pdf_url = $protected_pdf_url;
            }
        }

        if ($pdf_url && !$can_read) {
            $pdf_url = '';
        }

        $cta_url = $this->get_article_cta_url($article);
        $back_url = $atts['back_url'] ? esc_url_raw($atts['back_url']) : wp_get_referer();
        $back_url = $back_url ? $back_url : home_url('/');
        $raw_content = preg_replace('/\[df_article_page[^\]]*\]/', '', $article->post_content);

        if ($can_read) {
            $content = wpautop(do_shortcode($raw_content));
        } else {
            $preview_words = absint(get_post_meta($article->ID, DF_Subscriptions::ARTICLE_PREVIEW_WORDS_META, true));
            $preview_words = $preview_words ? min(500, max(10, $preview_words)) : 120;
            $preview = has_excerpt($article) ? get_the_excerpt($article) : wp_trim_words(wp_strip_all_tags(strip_shortcodes($raw_content)), $preview_words, '...');
            $content = wpautop(esc_html($preview));
        }

        ob_start();
        ?>
        <article class="df-article-page">
            <header class="df-article-page__hero">
                <?php if ('yes' === $atts['show_back']) : ?>
                    <a class="df-article-page__back" href="<?php echo esc_url($back_url); ?>"><span aria-hidden="true">&larr;</span> <?php echo esc_html($atts['back_text']); ?></a>
                <?php endif; ?>

                <h1 class="df-article-page__title"><?php echo esc_html(get_the_title($article)); ?></h1>

                <div class="df-article-page__meta-row">
                    <div class="df-article-page__meta">
                        <span><strong><?php echo esc_html($this->t('article_meta_author')); ?></strong> <?php echo esc_html($author); ?></span>
                        <span><strong><?php echo esc_html($this->t('article_meta_date')); ?></strong> <time datetime="<?php echo esc_attr($date); ?>"><?php echo esc_html($date_label); ?></time></span>
                        <span><strong><?php echo esc_html($this->t('article_meta_duration')); ?></strong> <?php echo esc_html(sprintf($this->t('article_meta_min'), $duration)); ?></span>
                    </div>

                    <?php if (!empty($topics)) : ?>
                        <div class="df-article-page__tags">
                            <?php foreach (array_slice($topics, 0, 2) as $topic) : ?>
                                <span><?php echo esc_html($topic->name); ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if ($hero_image) : ?>
                    <img class="df-article-page__hero-image" src="<?php echo esc_url($hero_image); ?>" alt="<?php echo esc_attr(get_the_title($article)); ?>">
                <?php endif; ?>
            </header>

            <section class="df-article-page__main">
                <aside class="df-article-page__sidebar">
                    <?php if ($magazine_cover) : ?>
                        <img class="df-article-page__cover" src="<?php echo esc_url($magazine_cover); ?>" alt="<?php echo esc_attr($magazine_label ? $magazine_label : $magazine_title); ?>">
                    <?php endif; ?>

                    <?php if ($magazine_label) : ?>
                        <h2 class="df-article-page__issue-title"><?php echo esc_html($magazine_label); ?></h2>
                    <?php endif; ?>

                    <div class="df-article-page__issue-meta">
                        <div><strong><?php echo esc_html($this->t('issue_date_label')); ?></strong><span><?php echo esc_html($magazine_date_label); ?></span></div>
                        <div>
                            <strong><?php echo esc_html($this->t('article_share')); ?></strong>
                            <span class="df-article-page__share">
                                <a href="<?php echo esc_url('https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode(get_permalink($article))); ?>" target="_blank" rel="noopener">f</a>
                                <a href="<?php echo esc_url('https://twitter.com/intent/tweet?url=' . rawurlencode(get_permalink($article)) . '&text=' . rawurlencode(get_the_title($article))); ?>" target="_blank" rel="noopener">x</a>
                                <a href="<?php echo esc_url('mailto:?subject=' . rawurlencode(get_the_title($article)) . '&body=' . rawurlencode(get_permalink($article))); ?>">@</a>
                            </span>
                        </div>
                    </div>

                    <?php if ($pdf_url) : ?>
                        <a class="df-article-page__pdf" href="<?php echo esc_url($pdf_url); ?>" target="_blank" rel="noopener"><?php echo esc_html($this->t('article_pdf_btn')); ?></a>
                    <?php else : ?>
                        <span class="df-article-page__pdf is-disabled"><?php echo esc_html($this->t('article_pdf_btn')); ?></span>
                    <?php endif; ?>
                </aside>

                <div class="df-article-page__article">
                    <h2 class="df-article-page__intro"><?php echo esc_html(has_excerpt($article) ? get_the_excerpt($article) : get_the_title($article)); ?></h2>
                    <div class="df-article-page__content <?php echo $can_read ? '' : 'is-preview'; ?>">
                        <?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    </div>

                    <?php if (!$can_read) : ?>
                        <a class="df-article-page__cta" href="<?php echo esc_url($cta_url); ?>"><?php echo esc_html($atts['button_text']); ?></a>
                    <?php endif; ?>
                </div>
            </section>
        </article>
        <?php

        return ob_get_clean();
    }

    private function get_current_url()
    {
        if (isset($_SERVER['REQUEST_URI'])) {
            return home_url(esc_url_raw(wp_unslash($_SERVER['REQUEST_URI'])));
        }

        return home_url('/');
    }

    private function render_issue_card(WP_Post $issue)
    {
        $access = get_post_meta($issue->ID, DF_Subscriptions::MAGAZINE_ACCESS_META, true);
        $access = $access ? $access : 'paid';
        $issue_date = get_post_meta($issue->ID, DF_Subscriptions::MAGAZINE_ISSUE_DATE_META, true);
        $issue_timestamp = $issue_date ? strtotime($issue_date) : get_post_time('U', true, $issue);
        $year_terms = wp_get_post_terms($issue->ID, DF_Subscriptions::ISSUE_YEAR_TAXONOMY, array('fields' => 'slugs'));
        $topic_terms = wp_get_post_terms($issue->ID, DF_Subscriptions::TOPIC_TAXONOMY, array('fields' => 'slugs'));
        $image_url = get_the_post_thumbnail_url($issue, 'large');

        if (!$image_url) {
            $image_url = get_post_meta($issue->ID, DF_Subscriptions::MAGAZINE_COVER_URL_META, true);
        }

        $pdf_url = get_post_meta($issue->ID, DF_Subscriptions::MAGAZINE_PDF_META, true);
        $link = ('free_pdf' === $access && $pdf_url) ? $pdf_url : get_permalink($issue);
        $date_label = $issue_timestamp ? date_i18n('j. F Y', $issue_timestamp) : '';

        ob_start();
        ?>
        <article
            class="df-issues__card"
            data-issue-card
            data-type="<?php echo esc_attr($access); ?>"
            data-year="<?php echo esc_attr(implode(' ', is_array($year_terms) ? $year_terms : array())); ?>"
            data-topic="<?php echo esc_attr(implode(' ', is_array($topic_terms) ? $topic_terms : array())); ?>"
            data-date="<?php echo esc_attr($issue_timestamp ? $issue_timestamp : 0); ?>"
            data-title="<?php echo esc_attr(get_the_title($issue)); ?>"
        >
            <a class="df-issues__card-link" href="<?php echo esc_url($link); ?>" <?php echo ('free_pdf' === $access && $pdf_url) ? 'target="_blank" rel="noopener"' : ''; ?>>
                <span class="df-issues__image-wrap">
                    <?php if ($image_url) : ?>
                        <img class="df-issues__image" src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr(get_the_title($issue)); ?>" loading="lazy">
                    <?php else : ?>
                        <span class="df-issues__image-placeholder">
                            <img src="<?php echo esc_url(DF_SUBSCRIPTIONS_PLANE_URL); ?>" alt="" aria-hidden="true">
                        </span>
                    <?php endif; ?>
                </span>
                <span class="df-issues__card-title"><?php echo esc_html(get_the_title($issue)); ?></span>
            </a>
            <span class="df-issues__meta">
                <span><?php echo esc_html($this->t('issue_date_label')); ?></span>
                <time datetime="<?php echo esc_attr($issue_date); ?>"><?php echo esc_html($date_label); ?></time>
            </span>
        </article>
        <?php

        return ob_get_clean();
    }

    private function render_article_card(WP_Post $article)
    {
        $magazine_id = (int) get_post_meta($article->ID, DF_Subscriptions::ARTICLE_MAGAZINE_META, true);
        $magazine_title = $magazine_id ? get_the_title($magazine_id) : '';
        $topics = wp_get_post_terms($article->ID, DF_Subscriptions::TOPIC_TAXONOMY);
        $label = $magazine_title;

        if (!$label && !is_wp_error($topics) && !empty($topics)) {
            $label = $topics[0]->name;
        }

        if (!$label) {
            $label = __('Magazine article', 'der-flugschreiber-subscriptions');
        }

        $image_url = get_the_post_thumbnail_url($article, 'large');

        if (!$image_url) {
            $image_url = get_post_meta($article->ID, DF_Subscriptions::ARTICLE_IMAGE_URL_META, true);
        }

        if (!$image_url && $magazine_id) {
            $image_url = get_the_post_thumbnail_url($magazine_id, 'large');
        }

        if (!$image_url && $magazine_id) {
            $image_url = get_post_meta($magazine_id, DF_Subscriptions::MAGAZINE_COVER_URL_META, true);
        }

        $date = get_the_date('Y-m-d', $article);
        $date_label = get_the_date('j. F Y', $article);
        $author = get_the_author_meta('display_name', (int) $article->post_author);
        $word_count = str_word_count(wp_strip_all_tags($article->post_content));
        $duration = max(1, (int) ceil($word_count / 200));
        $preview_words = absint(get_post_meta($article->ID, DF_Subscriptions::ARTICLE_PREVIEW_WORDS_META, true));
        $preview_words = $preview_words ? min(120, max(10, $preview_words)) : 62;
        $excerpt = has_excerpt($article) ? get_the_excerpt($article) : wp_trim_words(wp_strip_all_tags(strip_shortcodes($article->post_content)), $preview_words, '...');

        ob_start();
        ?>
        <article class="df-articles__item">
            <div class="df-articles__content">
                <div class="df-articles__top-meta">
                    <time datetime="<?php echo esc_attr($date); ?>"><?php echo esc_html($date_label); ?></time>
                    <span class="df-articles__pill"><?php echo esc_html($label); ?></span>
                </div>

                <div class="df-articles__body">
                    <div class="df-articles__inline-meta">
                        <span><strong><?php echo esc_html($this->t('article_meta_author')); ?></strong> <?php echo esc_html($author); ?></span>
                        <span><strong><?php echo esc_html($this->t('article_meta_duration')); ?></strong> <?php echo esc_html(sprintf($this->t('article_meta_min'), $duration)); ?></span>
                    </div>

                    <div class="df-articles__text">
                        <h3 class="df-articles__item-title"><?php echo esc_html(get_the_title($article)); ?></h3>
                        <p class="df-articles__excerpt"><?php echo esc_html($excerpt); ?></p>
                    </div>

                    <a class="df-articles__button" href="<?php echo esc_url(get_permalink($article)); ?>"><?php echo esc_html($this->t('article_read_btn')); ?></a>
                </div>
            </div>

            <a class="df-articles__image-link" href="<?php echo esc_url(get_permalink($article)); ?>" aria-label="<?php echo esc_attr(get_the_title($article)); ?>">
                <?php if ($image_url) : ?>
                    <img class="df-articles__image" src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr(get_the_title($article)); ?>" loading="lazy">
                <?php else : ?>
                    <span class="df-articles__image-placeholder">
                        <img src="<?php echo esc_url(DF_SUBSCRIPTIONS_PLANE_ALT_URL); ?>" alt="" aria-hidden="true">
                    </span>
                <?php endif; ?>
            </a>
        </article>
        <?php

        return ob_get_clean();
    }

    private function get_article_image_url(WP_Post $article, $magazine_id = 0)
    {
        $image_url = get_the_post_thumbnail_url($article, 'large');

        if (!$image_url) {
            $image_url = get_post_meta($article->ID, DF_Subscriptions::ARTICLE_IMAGE_URL_META, true);
        }

        if (!$image_url && $magazine_id) {
            $image_url = get_the_post_thumbnail_url($magazine_id, 'large');
        }

        if (!$image_url && $magazine_id) {
            $image_url = get_post_meta($magazine_id, DF_Subscriptions::MAGAZINE_COVER_URL_META, true);
        }

        return $image_url;
    }

    private function get_issue_cover_url(WP_Post $issue)
    {
        $cover_url = get_the_post_thumbnail_url($issue, 'large');

        if (!$cover_url) {
            $cover_url = get_post_meta($issue->ID, DF_Subscriptions::MAGAZINE_COVER_URL_META, true);
        }

        return $cover_url;
    }

    private function get_issue_summary($issue)
    {
        $fallback = $this->t('issue_summary_fallback');

        if (!$issue instanceof WP_Post) {
            return $fallback;
        }

        $summary = has_excerpt($issue)
            ? get_the_excerpt($issue)
            : wp_trim_words(wp_strip_all_tags(strip_shortcodes($issue->post_content)), 42, '...');

        if (!$summary || preg_match('/\b(demo|testing|test content|lorem ipsum)\b/i', $summary)) {
            return $fallback;
        }

        return $summary;
    }

    private function article_requires_subscription(WP_Post $article)
    {
        $article_access = get_post_meta($article->ID, DF_Subscriptions::ARTICLE_ACCESS_META, true);

        if ('free' === $article_access) {
            return false;
        }

        if ('paid' === $article_access) {
            return true;
        }

        $magazine_id = (int) get_post_meta($article->ID, DF_Subscriptions::ARTICLE_MAGAZINE_META, true);

        if ($magazine_id && 'free_pdf' === get_post_meta($magazine_id, DF_Subscriptions::MAGAZINE_ACCESS_META, true)) {
            return false;
        }

        return true;
    }

    private function get_article_cta_url(WP_Post $article)
    {
        $payment_url = get_option(DF_Subscriptions::PAYMENT_URL_OPTION, '');

        if ($payment_url) {
            return $payment_url;
        }

        $custom_login_url = get_option(DF_Subscriptions::LOGIN_URL_OPTION, '');

        if ($custom_login_url) {
            return add_query_arg('redirect_to', get_permalink($article), $custom_login_url);
        }

        return wp_login_url(get_permalink($article));
    }

    private function render_filter_dropdown($key, $label, $default_label, $options, $default_value = '', $is_sort = false)
    {
        $dropdown_class = $is_sort ? 'df-issues__dropdown df-issues__dropdown--sort' : 'df-issues__dropdown';
        $button_id = 'df-issues-' . sanitize_key($key) . '-' . wp_rand(1000, 9999);

        ob_start();
        ?>
        <div class="<?php echo esc_attr($dropdown_class); ?>" data-dropdown="<?php echo esc_attr($key); ?>" data-value="<?php echo esc_attr($default_value); ?>">
            <button id="<?php echo esc_attr($button_id); ?>" class="df-issues__dropdown-button" type="button" aria-haspopup="listbox" aria-expanded="false">
                <?php if ($is_sort) : ?>
                    <span class="df-issues__sort-icon" aria-hidden="true"></span>
                <?php endif; ?>
                <span class="df-issues__dropdown-label"><?php echo esc_html($label); ?></span>
                <span class="df-issues__dropdown-value" data-dropdown-value-label><?php echo esc_html($default_label); ?></span>
                <span class="df-issues__dropdown-arrow" aria-hidden="true"></span>
            </button>
            <div class="df-issues__dropdown-menu" role="listbox" aria-labelledby="<?php echo esc_attr($button_id); ?>" hidden>
                <button class="df-issues__dropdown-option is-active" type="button" role="option" data-value="<?php echo esc_attr($default_value); ?>" data-label="<?php echo esc_attr($default_label); ?>" aria-selected="true">
                    <?php echo esc_html($default_label); ?>
                </button>
                <?php foreach ($options as $value => $option_label) : ?>
                    <?php if ((string) $value === (string) $default_value) : ?>
                        <?php continue; ?>
                    <?php endif; ?>
                    <button class="df-issues__dropdown-option" type="button" role="option" data-value="<?php echo esc_attr($value); ?>" data-label="<?php echo esc_attr($option_label); ?>" aria-selected="false">
                        <?php echo esc_html($option_label); ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>
        <?php

        return ob_get_clean();
    }

    private function get_issues_result($args)
    {
        $defaults = array(
            'type' => 'paid',
            'year' => '',
            'topic' => '',
            'sort' => 'newest',
            'limit' => 12,
            'page' => 1,
            'offset' => 0,
        );
        $args = wp_parse_args($args, $defaults);

        $limit = min(50, max(1, absint($args['limit'])));
        $page = min(100, max(1, absint($args['page'])));
        $offset = min(5000, absint($args['offset']));
        $access_type = in_array($args['type'], array('paid', 'free_pdf'), true) ? $args['type'] : 'paid';
        $query_args = array(
            'post_type' => DF_Subscriptions::MAGAZINE_POST_TYPE,
            'post_status' => 'publish',
            'posts_per_page' => $limit,
            'offset' => $offset,
        );

        if ('paid' === $access_type) {
            $query_args['meta_query'] = array(
                'relation' => 'OR',
                array(
                    'key' => DF_Subscriptions::MAGAZINE_ACCESS_META,
                    'value' => 'paid',
                    'compare' => '=',
                ),
                array(
                    'key' => DF_Subscriptions::MAGAZINE_ACCESS_META,
                    'compare' => 'NOT EXISTS',
                ),
            );
        } else {
            $query_args['meta_query'] = array(
                array(
                    'key' => DF_Subscriptions::MAGAZINE_ACCESS_META,
                    'value' => 'free_pdf',
                    'compare' => '=',
                ),
            );
        }

        if ('title' === $args['sort']) {
            $query_args['orderby'] = 'title';
            $query_args['order'] = 'ASC';
        } else {
            $query_args['meta_key'] = DF_Subscriptions::MAGAZINE_ISSUE_DATE_META;
            $query_args['orderby'] = 'meta_value';
            $query_args['order'] = 'oldest' === $args['sort'] ? 'ASC' : 'DESC';
        }

        $tax_query = array();

        if ($args['year']) {
            $tax_query[] = array(
                'taxonomy' => DF_Subscriptions::ISSUE_YEAR_TAXONOMY,
                'field' => 'slug',
                'terms' => $args['year'],
            );
        }

        if ($args['topic']) {
            $tax_query[] = array(
                'taxonomy' => DF_Subscriptions::TOPIC_TAXONOMY,
                'field' => 'slug',
                'terms' => $args['topic'],
            );
        }

        if ($tax_query) {
            $query_args['tax_query'] = $tax_query;
        }

        $query = new WP_Query($query_args);
        $html = '';

        foreach ($query->posts as $issue) {
            $html .= $this->render_issue_card($issue);
        }

        return array(
            'html' => $html,
            'found' => $query->found_posts > 0,
            'has_more' => ($offset + $limit) < (int) $query->found_posts,
        );
    }

    private function render_article_card_grid(WP_Post $article)
    {
        $magazine_id = (int) get_post_meta($article->ID, DF_Subscriptions::ARTICLE_MAGAZINE_META, true);
        $image_url = $this->get_article_image_url($article, $magazine_id);

        ob_start();
        ?>
        <article>
            <a class="df-magazine-page__article-image" href="<?php echo esc_url(get_permalink($article)); ?>">
                <?php if ($image_url) : ?>
                    <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr(get_the_title($article)); ?>" loading="lazy">
                <?php else : ?>
                    <span><img src="<?php echo esc_url(DF_SUBSCRIPTIONS_PLANE_ALT_URL); ?>" alt="" aria-hidden="true"></span>
                <?php endif; ?>
            </a>
            <h3><a href="<?php echo esc_url(get_permalink($article)); ?>"><?php echo esc_html(get_the_title($article)); ?></a></h3>
            <a class="df-magazine-page__article-link" href="<?php echo esc_url(get_permalink($article)); ?>"><?php echo esc_html($this->t('article_read_link')); ?></a>
        </article>
        <?php
        return ob_get_clean();
    }

    private function get_articles_result($args)
    {
        $defaults = array(
            'magazine' => 0,
            'topic' => '',
            'sort' => 'newest',
            'layout' => 'list',
            'limit' => 5,
            'page' => 1,
            'offset' => 0,
        );
        $args = wp_parse_args($args, $defaults);

        $limit = min(50, max(1, absint($args['limit'])));
        $page = min(100, max(1, absint($args['page'])));
        $offset = min(5000, absint($args['offset']));
        $query_args = array(
            'post_type' => DF_Subscriptions::ARTICLE_POST_TYPE,
            'post_status' => 'publish',
            'posts_per_page' => $limit,
            'offset' => $offset,
        );

        if ('title' === $args['sort']) {
            $query_args['orderby'] = 'title';
            $query_args['order'] = 'ASC';
        } else {
            $query_args['orderby'] = 'date';
            $query_args['order'] = 'oldest' === $args['sort'] ? 'ASC' : 'DESC';
        }

        if ($args['magazine']) {
            $query_args['meta_query'] = array(
                array(
                    'key' => DF_Subscriptions::ARTICLE_MAGAZINE_META,
                    'value' => absint($args['magazine']),
                    'compare' => '=',
                ),
            );
        }

        if ($args['topic']) {
            $query_args['tax_query'] = array(
                array(
                    'taxonomy' => DF_Subscriptions::TOPIC_TAXONOMY,
                    'field' => 'slug',
                    'terms' => $args['topic'],
                ),
            );
        }

        $query = new WP_Query($query_args);
        $html = '';
        $use_grid = 'grid' === $args['layout'];

        foreach ($query->posts as $article) {
            $html .= $use_grid ? $this->render_article_card_grid($article) : $this->render_article_card($article);
        }

        return array(
            'html' => $html,
            'found' => $query->found_posts > 0,
            'has_more' => ($offset + $limit) < (int) $query->found_posts,
        );
    }

    private function t($key)
    {
        $defaults = self::text_defaults();
        $default = isset($defaults[$key]) ? $defaults[$key] : '';
        $saved = get_option('df_text_' . $key, '');
        return $saved !== '' ? $saved : $default;
    }

    public static function text_defaults()
    {
        return array(
            'home_eyebrow'           => 'Das Magazin von Piloten für Piloten',
            'home_headline'          => 'Die Faszination des Fliegens. Viermal im Jahr.',
            'home_lead'              => 'Reportagen, Technik, Sicherheit und Geschichten aus der Praxis - geschrieben von Menschen, die Luftfahrt leben.',
            'home_offer_label'       => 'Print-Abonnement im ersten Jahr',
            'home_offer_detail'      => '%s Ausgaben inklusive Versand und MwSt.',
            'home_subscribe_btn'     => 'Jetzt abonnieren',
            'home_hero_link'         => 'Aktuelle Ausgabe ansehen',
            'home_promise_1_label'   => 'hochwertige Ausgaben pro Jahr',
            'home_promise_2_value'   => 'Praxis',
            'home_promise_2_label'   => 'Erfahrungen direkt aus dem Cockpit',
            'home_promise_3_value'   => 'Community',
            'home_promise_3_label'   => 'Von und für Flugbegeisterte',
            'home_current_eyebrow'   => 'Aktuelle Ausgabe',
            'home_current_intro'     => 'Geschichten, Wissen und neue Perspektiven aus der Luftfahrt.',
            'home_current_btn'       => 'Zum Abonnement',
            'home_current_more'      => 'Mehr zur Ausgabe',
            'home_topics_eyebrow'    => 'Was uns bewegt',
            'home_topics_headline'   => 'Luftfahrt in ihrer ganzen Vielfalt',
            'home_topic_1_title'     => 'Flugsicherheit',
            'home_topic_1_body'      => 'Praxisnahes Wissen für sichere Entscheidungen am Boden und in der Luft.',
            'home_topic_2_title'     => 'Flugzeuge & Technik',
            'home_topic_2_body'      => 'Klassiker, Innovationen und Technik verständlich erklärt.',
            'home_topic_3_title'     => 'Menschen & Reisen',
            'home_topic_3_body'      => 'Persönliche Geschichten, Reiseberichte und besondere Flugerlebnisse.',
            'home_topic_4_title'     => 'Ausbildung & Praxis',
            'home_topic_4_body'      => 'Erfahrungen und Impulse für Piloten in jeder Phase ihrer Laufbahn.',
            'home_articles_eyebrow'  => 'Aus dem Magazin',
            'home_articles_headline' => 'Aktuelle Geschichten',
            'home_articles_all'      => 'Alle Artikel',
            'home_article_read'      => 'Artikel lesen',
            'home_archive_eyebrow'   => 'Archiv',
            'home_archive_headline'  => 'Weitere Ausgaben',
            'home_archive_all'       => 'Alle Ausgaben',
            'home_about_eyebrow'     => 'Über uns',
            'home_about_headline'    => 'Ein Magazin, getragen von der fliegerischen Community.',
            'home_about_text_1'      => 'Unser Team besteht aus erfahrenen Piloten und Flugbegeisterten. Wir teilen Wissen, Erfahrungen und die Begeisterung für Motorflug, Segelflug, Fallschirmsport, Paragleiten und Modellflug.',
            'home_about_text_2'      => 'Der Flugschreiber ist eine Plattform zum Lernen, Austauschen und gemeinsamen Erleben der Luftfahrt.',
            'home_cta_eyebrow'       => 'Bereit zum Abheben?',
            'home_cta_headline'      => 'Vier Ausgaben. Ein Jahr voller Luftfahrt.',
            'home_cta_text'          => 'Jetzt für %s im ersten Jahr abonnieren.',
            'issue_summary_fallback' => 'Die Ausgabe verbindet fundierte Fachthemen mit persönlichen Reportagen, starken Bildern und Erfahrungen aus der fliegerischen Praxis.',
            'article_read_btn'       => 'Artikel lesen',
            'article_read_link'      => 'Artikel lesen',
            // Filters (shared across issues / articles / posts)
            'filter_year'            => 'Erscheinungsjahr',
            'filter_year_all'        => 'Alle Jahre',
            'filter_topic'           => 'Themenbereich',
            'filter_topic_all'       => 'Alle Themen',
            'filter_sort'            => 'Sortieren nach',
            'filter_magazine'        => 'Ausgabe',
            'filter_category'        => 'Kategorie',
            'filter_category_all'    => 'Alle Kategorien',
            'sort_newest'            => 'Neueste',
            'sort_oldest'            => 'Älteste',
            'sort_title'             => 'Titel',
            'sort_title_az'          => 'Titel A–Z',
            // Issues list
            'issues_tab_new'         => 'Neue Ausgaben',
            'issues_tab_old'         => 'Ältere Ausgaben',
            'issues_popular_label'   => 'Beliebte Themen:',
            'issues_empty'           => 'Keine Ausgaben gefunden.',
            'list_show_more'         => 'Mehr laden',
            // Articles list
            'articles_empty'         => 'Keine Artikel gefunden.',
            // Magazine page
            'magazine_pdf_btn'       => 'PDF öffnen',
            'magazine_subscribe_btn' => 'Jetzt abonnieren',
            'magazine_login_btn'     => 'Einloggen',
            // Article page
            'article_meta_author'    => 'Text',
            'article_meta_date'      => 'Datum',
            'article_meta_duration'  => 'Lesezeit',
            'article_meta_min'       => '%d Min',
            'article_share'          => 'Teilen',
            'article_pdf_btn'        => 'PDF herunterladen',
            'issue_date_label'       => 'Datum',
            // Login form
            'login_active'           => 'Sie sind angemeldet und Ihr Abonnement ist aktiv.',
            'login_inactive'         => 'Sie sind angemeldet, aber Ihr Abonnement ist nicht aktiv.',
            'login_username'         => 'E-Mail oder Benutzername',
            'login_password'         => 'Passwort',
            'login_remember'         => 'Angemeldet bleiben',
            'login_submit'           => 'Anmelden',
            'login_forgot'           => 'Passwort vergessen?',
            // Password reset (styled, stays on the login page)
            'lost_intro'             => 'Geben Sie Ihre E-Mail-Adresse oder Ihren Benutzernamen ein. Wir senden Ihnen einen Link zum Zurücksetzen des Passworts.',
            'lost_submit'            => 'Link zum Zurücksetzen senden',
            'lost_back'              => 'Zurück zur Anmeldung',
            'lost_sent'              => 'Falls ein Konto zu diesen Angaben existiert, haben wir eine E-Mail mit einem Link zum Zurücksetzen des Passworts gesendet.',
            'lost_empty'             => 'Bitte geben Sie Ihre E-Mail-Adresse oder Ihren Benutzernamen ein.',
            'reset_intro'            => 'Wählen Sie ein neues Passwort für Ihr Konto.',
            'reset_password_new'     => 'Neues Passwort',
            'reset_password_confirm' => 'Neues Passwort bestätigen',
            'reset_submit'           => 'Passwort speichern',
            'reset_done'             => 'Ihr Passwort wurde geändert. Sie können sich jetzt anmelden.',
            'reset_mismatch'         => 'Die beiden Passwörter stimmen nicht überein.',
            'reset_too_short'        => 'Das Passwort muss mindestens 8 Zeichen lang sein.',
            'reset_invalid_key'      => 'Dieser Link ist ungültig oder abgelaufen. Bitte fordern Sie einen neuen Link an.',
            'reset_email_subject'    => 'Passwort zurücksetzen – Der Flugschreiber',
            'reset_email_body'       => "Hallo,\n\nSie haben angefordert, Ihr Passwort zurückzusetzen. Öffnen Sie den folgenden Link, um ein neues Passwort zu wählen:\n\n%s\n\nWenn Sie das nicht angefordert haben, können Sie diese E-Mail ignorieren.",
            // Posts / Blog
            'posts_empty'            => 'Keine Beiträge gefunden.',
            'blog_eyebrow'           => 'Der Flugschreiber',
            'articles_eyebrow'       => 'Aus dem Magazin',
        );
    }

    private function get_redirect_url()
    {
        if (isset($_GET['redirect_to'])) {
            return esc_url_raw(wp_unslash($_GET['redirect_to']));
        }

        return $this->get_current_url();
    }

    private function get_login_error_message()
    {
        if (!isset($_GET['df_login_error'])) {
            return '';
        }

        $error = sanitize_key(wp_unslash($_GET['df_login_error']));

        if ('empty' === $error) {
            return __('Please enter your email or username and password.', 'der-flugschreiber-subscriptions');
        }

        if ('security' === $error) {
            return __('The login form expired. Please try again.', 'der-flugschreiber-subscriptions');
        }

        return __('Incorrect email, username, or password. Please try again.', 'der-flugschreiber-subscriptions');
    }

    private function redirect_to_login_form($form_url, $error)
    {
        wp_safe_redirect(add_query_arg('df_login_error', $error, remove_query_arg('df_login_error', $form_url)));
        exit;
    }

    // -------------------------------------------------------------------------
    // Regular WordPress posts shortcodes
    // -------------------------------------------------------------------------

    public function all_posts($atts)
    {
        $atts = shortcode_atts(
            array(
                'initial' => 5,
                'step'    => 5,
                'title'   => __('All Posts', 'der-flugschreiber-subscriptions'),
                'layout'  => 'list',
            ),
            $atts,
            'df_all_posts'
        );
        $layout = 'grid' === $atts['layout'] ? 'grid' : 'list';

        $categories        = get_categories(array('hide_empty' => true, 'orderby' => 'count', 'order' => 'DESC'));
        $category_options  = array();
        foreach ($categories as $cat) {
            $category_options[$cat->term_id] = $cat->name;
        }

        $initial_result = $this->get_posts_result(array(
            'category' => 0,
            'sort'     => 'newest',
            'layout'   => $layout,
            'limit'    => absint($atts['initial']),
            'page'     => 1,
            'offset'   => 0,
        ));

        ob_start();
        ?>
        <section class="df-articles<?php echo 'grid' === $layout ? ' df-articles--grid' : ''; ?>"
                 data-initial="<?php echo esc_attr(absint($atts['initial'])); ?>"
                 data-step="<?php echo esc_attr(absint($atts['step'])); ?>"
                 data-page="1"
                 data-layout="<?php echo esc_attr($layout); ?>"
                 data-action="df_filter_posts">

            <h2 class="df-articles__title"><?php echo esc_html($atts['title']); ?></h2>

            <div class="df-articles__filters" aria-label="<?php esc_attr_e('Post filters', 'der-flugschreiber-subscriptions'); ?>">
                <?php
                // Key "magazine" reuses the existing JS getDropdownValue('magazine') serialization
                echo $this->render_filter_dropdown( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    'magazine',
                    $this->t('filter_category'),
                    $this->t('filter_category_all'),
                    $category_options,
                    0
                );
                echo $this->render_filter_dropdown( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    'sort',
                    $this->t('filter_sort'),
                    $this->t('sort_newest'),
                    array(
                        'newest' => $this->t('sort_newest'),
                        'oldest' => $this->t('sort_oldest'),
                        'title'  => $this->t('sort_title_az'),
                    ),
                    'newest',
                    true
                );
                ?>
            </div>

            <?php if (!empty($categories)) : ?>
                <div class="df-articles__popular" aria-label="<?php echo esc_attr($this->t('filter_category')); ?>">
                    <span class="df-articles__popular-label"><?php echo esc_html($this->t('filter_category')); ?>:</span>
                    <div class="df-articles__popular-list">
                        <?php foreach (array_slice($categories, 0, 8) as $cat) : ?>
                            <button type="button" class="df-articles__chip" data-topic-chip="<?php echo esc_attr($cat->term_id); ?>"><?php echo esc_html($cat->name); ?></button>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="df-articles__list" aria-live="polite">
                <?php echo $initial_result['html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            </div>

            <p class="df-articles__empty" <?php echo $initial_result['found'] ? 'hidden' : ''; ?>><?php echo esc_html($this->t('posts_empty')); ?></p>

            <div class="df-articles__more-wrap">
                <button type="button" class="df-articles__more" <?php echo $initial_result['has_more'] ? '' : 'hidden'; ?>><?php echo esc_html($this->t('list_show_more')); ?></button>
            </div>
        </section>
        <?php

        return ob_get_clean();
    }

    public function ajax_filter_posts()
    {
        check_ajax_referer('df_issues_filter', 'nonce');

        $limit  = isset($_POST['limit']) ? min(50, max(1, absint($_POST['limit']))) : 5;
        $page   = isset($_POST['page']) ? min(100, max(1, absint($_POST['page']))) : 1;
        $offset = isset($_POST['offset']) ? min(5000, absint($_POST['offset'])) : 0;
        $append = !empty($_POST['append']);

        $result = $this->get_posts_result(array(
            'category' => isset($_POST['magazine']) ? absint($_POST['magazine']) : 0,
            'sort'     => isset($_POST['sort']) ? sanitize_key(wp_unslash($_POST['sort'])) : 'newest',
            'layout'   => isset($_POST['layout']) && 'grid' === $_POST['layout'] ? 'grid' : 'list',
            'limit'    => $limit,
            'page'     => $page,
            'offset'   => $offset,
        ));

        wp_send_json_success(array(
            'html'    => $result['html'],
            'found'   => $result['found'],
            'hasMore' => $result['has_more'],
            'page'    => $page,
            'append'  => $append,
        ));
    }

    private function get_posts_result($args)
    {
        $args  = wp_parse_args($args, array(
            'category' => 0,
            'sort'     => 'newest',
            'layout'   => 'list',
            'limit'    => 5,
            'page'     => 1,
            'offset'   => 0,
        ));
        $limit  = min(50, max(1, absint($args['limit'])));
        $offset = min(5000, absint($args['offset']));

        $query_args = array(
            'post_type'      => 'post',
            'post_status'    => 'publish',
            'posts_per_page' => $limit,
            'offset'         => $offset,
        );

        if ('title' === $args['sort']) {
            $query_args['orderby'] = 'title';
            $query_args['order']   = 'ASC';
        } else {
            $query_args['orderby'] = 'date';
            $query_args['order']   = 'oldest' === $args['sort'] ? 'ASC' : 'DESC';
        }

        if (!empty($args['category'])) {
            $query_args['cat'] = absint($args['category']);
        }

        $query    = new WP_Query($query_args);
        $html     = '';
        $use_grid = 'grid' === $args['layout'];

        foreach ($query->posts as $post) {
            $html .= $use_grid ? $this->render_post_card_grid($post) : $this->render_post_card($post);
        }

        return array(
            'html'     => $html,
            'found'    => $query->found_posts > 0,
            'has_more' => ($offset + $limit) < (int) $query->found_posts,
        );
    }

    private function render_post_card(WP_Post $post)
    {
        $categories  = get_the_category($post->ID);
        $label       = !empty($categories) ? $categories[0]->name : __('Blog', 'der-flugschreiber-subscriptions');
        $image_url   = get_the_post_thumbnail_url($post, 'large');
        $date        = get_the_date('Y-m-d', $post);
        $date_label  = get_the_date('j. F Y', $post);
        $author      = get_the_author_meta('display_name', (int) $post->post_author);
        $word_count  = str_word_count(wp_strip_all_tags($post->post_content));
        $duration    = max(1, (int) ceil($word_count / 200));
        $excerpt     = has_excerpt($post)
            ? get_the_excerpt($post)
            : wp_trim_words(wp_strip_all_tags(strip_shortcodes($post->post_content)), 62, '...');

        ob_start();
        ?>
        <article class="df-articles__item">
            <div class="df-articles__content">
                <div class="df-articles__top-meta">
                    <time datetime="<?php echo esc_attr($date); ?>"><?php echo esc_html($date_label); ?></time>
                    <span class="df-articles__pill"><?php echo esc_html($label); ?></span>
                </div>
                <div class="df-articles__body">
                    <div class="df-articles__inline-meta">
                        <span><strong><?php echo esc_html($this->t('article_meta_author')); ?></strong> <?php echo esc_html($author); ?></span>
                        <span><strong><?php echo esc_html($this->t('article_meta_duration')); ?></strong> <?php echo esc_html(sprintf($this->t('article_meta_min'), $duration)); ?></span>
                    </div>
                    <div class="df-articles__text">
                        <h3 class="df-articles__item-title"><?php echo esc_html(get_the_title($post)); ?></h3>
                        <p class="df-articles__excerpt"><?php echo esc_html($excerpt); ?></p>
                    </div>
                    <a class="df-articles__button" href="<?php echo esc_url(get_permalink($post)); ?>"><?php echo esc_html($this->t('article_read_btn')); ?></a>
                </div>
            </div>
            <a class="df-articles__image-link" href="<?php echo esc_url(get_permalink($post)); ?>" aria-label="<?php echo esc_attr(get_the_title($post)); ?>">
                <?php if ($image_url) : ?>
                    <img class="df-articles__image" src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr(get_the_title($post)); ?>" loading="lazy">
                <?php else : ?>
                    <span class="df-articles__image-placeholder">
                        <img src="<?php echo esc_url(DF_SUBSCRIPTIONS_PLANE_ALT_URL); ?>" alt="" aria-hidden="true">
                    </span>
                <?php endif; ?>
            </a>
        </article>
        <?php

        return ob_get_clean();
    }

    private function render_post_card_grid(WP_Post $post)
    {
        $image_url = get_the_post_thumbnail_url($post, 'large');

        ob_start();
        ?>
        <article>
            <a class="df-magazine-page__article-image" href="<?php echo esc_url(get_permalink($post)); ?>">
                <?php if ($image_url) : ?>
                    <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr(get_the_title($post)); ?>" loading="lazy">
                <?php else : ?>
                    <span><img src="<?php echo esc_url(DF_SUBSCRIPTIONS_PLANE_ALT_URL); ?>" alt="" aria-hidden="true"></span>
                <?php endif; ?>
            </a>
            <h3><a href="<?php echo esc_url(get_permalink($post)); ?>"><?php echo esc_html(get_the_title($post)); ?></a></h3>
            <a class="df-magazine-page__article-link" href="<?php echo esc_url(get_permalink($post)); ?>"><?php echo esc_html($this->t('article_read_link')); ?></a>
        </article>
        <?php

        return ob_get_clean();
    }

    public function post_page($atts)
    {
        $atts = shortcode_atts(
            array(
                'post'      => 0,
                'show_back' => 'yes',
                'back_url'  => '',
                'back_text' => __('Go Back', 'der-flugschreiber-subscriptions'),
            ),
            $atts,
            'df_post_page'
        );

        $post_id = absint($atts['post']);

        if (!$post_id && is_singular('post')) {
            $post_id = get_queried_object_id();
        }

        if (!$post_id) {
            return '';
        }

        $post = get_post($post_id);

        if (!$post || 'post' !== $post->post_type || ('publish' !== $post->post_status && !current_user_can('read_post', $post->ID))) {
            return '';
        }

        $categories  = get_the_category($post->ID);
        $author      = get_the_author_meta('display_name', (int) $post->post_author);
        $date        = get_the_date('Y-m-d', $post);
        $date_label  = get_the_date('j. F Y', $post);
        $word_count  = str_word_count(wp_strip_all_tags($post->post_content));
        $duration    = max(1, (int) ceil($word_count / 200));
        $hero_image  = get_the_post_thumbnail_url($post, 'full');
        $raw_content = preg_replace('/\[df_post_page[^\]]*\]/', '', $post->post_content);
        $content     = wpautop(do_shortcode($raw_content));
        $back_url    = $atts['back_url'] ? esc_url_raw($atts['back_url']) : (wp_get_referer() ?: home_url('/'));

        ob_start();
        ?>
        <article class="df-article-page">
            <header class="df-article-page__hero">
                <?php if ('yes' === $atts['show_back']) : ?>
                    <a class="df-article-page__back" href="<?php echo esc_url($back_url); ?>"><span aria-hidden="true">&larr;</span> <?php echo esc_html($atts['back_text']); ?></a>
                <?php endif; ?>

                <h1 class="df-article-page__title"><?php echo esc_html(get_the_title($post)); ?></h1>

                <div class="df-article-page__meta-row">
                    <div class="df-article-page__meta">
                        <span><strong><?php echo esc_html($this->t('article_meta_author')); ?></strong> <?php echo esc_html($author); ?></span>
                        <span><strong><?php echo esc_html($this->t('article_meta_date')); ?></strong> <time datetime="<?php echo esc_attr($date); ?>"><?php echo esc_html($date_label); ?></time></span>
                        <span><strong><?php echo esc_html($this->t('article_meta_duration')); ?></strong> <?php echo esc_html(sprintf($this->t('article_meta_min'), $duration)); ?></span>
                    </div>
                    <?php if (!empty($categories)) : ?>
                        <div class="df-article-page__tags">
                            <?php foreach (array_slice($categories, 0, 3) as $cat) : ?>
                                <span><?php echo esc_html($cat->name); ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if ($hero_image) : ?>
                    <img class="df-article-page__hero-image" src="<?php echo esc_url($hero_image); ?>" alt="<?php echo esc_attr(get_the_title($post)); ?>">
                <?php endif; ?>
            </header>

            <div class="df-post-page__content">
                <?php if (has_excerpt($post)) : ?>
                    <p class="df-article-page__intro"><?php echo esc_html(get_the_excerpt($post)); ?></p>
                <?php endif; ?>
                <div class="df-article-page__content">
                    <?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                </div>
            </div>
        </article>
        <?php

        return ob_get_clean();
    }

    public function blog_archive($atts)
    {
        $atts = shortcode_atts(
            array(
                'title'   => __('Blog', 'der-flugschreiber-subscriptions'),
                'intro'   => '',
                'initial' => 6,
                'step'    => 6,
                'layout'  => 'list',
            ),
            $atts,
            'df_blog_archive'
        );

        ob_start();
        ?>
        <div class="df-collection-page df-collection-page--posts">
            <header class="df-collection-page__hero">
                <p class="df-collection-page__eyebrow"><?php echo esc_html($this->t('blog_eyebrow')); ?></p>
                <h1><?php echo esc_html($atts['title']); ?></h1>
                <?php if ($atts['intro']) : ?>
                    <p><?php echo esc_html($atts['intro']); ?></p>
                <?php endif; ?>
            </header>
            <?php
            echo $this->all_posts(array( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                'initial' => absint($atts['initial']),
                'step'    => absint($atts['step']),
                'title'   => '',
                'layout'  => $atts['layout'],
            ));
            ?>
        </div>
        <?php

        return ob_get_clean();
    }
}
