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
        add_action('wp_ajax_df_filter_issues', array($this, 'ajax_filter_issues'));
        add_action('wp_ajax_nopriv_df_filter_issues', array($this, 'ajax_filter_issues'));
        add_action('wp_ajax_df_filter_articles', array($this, 'ajax_filter_articles'));
        add_action('wp_ajax_nopriv_df_filter_articles', array($this, 'ajax_filter_articles'));
        add_shortcode('df_login_form', array($this, 'login_form'));
        add_shortcode('df_logout_link', array($this, 'logout_link'));
        add_shortcode('df_all_issues', array($this, 'all_issues'));
        add_shortcode('df_all_articles', array($this, 'all_articles'));
        add_shortcode('df_article_page', array($this, 'article_page'));
        add_shortcode('df_homepage', array($this, 'homepage'));
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

    public function login_form($atts)
    {
        $atts = shortcode_atts(
            array(
                'redirect' => '',
            ),
            $atts,
            'df_login_form'
        );

        if (is_user_logged_in()) {
            $message = $this->access->current_user_can_read_paid_content()
                ? __('You are logged in and your subscription is active.', 'der-flugschreiber-subscriptions')
                : __('You are logged in, but your subscription is not active.', 'der-flugschreiber-subscriptions');

            return '<div class="df-subscriptions-login-status"><p>' . esc_html($message) . '</p>' . $this->logout_link(array()) . '</div>';
        }

        $redirect = $atts['redirect'] ? esc_url_raw($atts['redirect']) : $this->get_redirect_url();
        $form_url = $this->get_current_url();
        $error = $this->get_login_error_message();
        $form_id = wp_unique_id('df-subscriptions-login-form-');
        $username_id = $form_id . '-username';
        $password_id = $form_id . '-password';

        ob_start();
        ?>
        <?php if ($error) : ?>
            <div class="df-subscriptions-login-error" role="alert"><?php echo esc_html($error); ?></div>
        <?php endif; ?>
        <form id="<?php echo esc_attr($form_id); ?>" class="df-subscriptions-login" method="post" action="<?php echo esc_url($form_url); ?>">
            <?php wp_nonce_field('df_login_form', 'df_login_nonce'); ?>
            <input type="hidden" name="df_login_form_action" value="1">
            <input type="hidden" name="df_redirect_to" value="<?php echo esc_attr($redirect); ?>">
            <input type="hidden" name="df_login_form_url" value="<?php echo esc_attr($form_url); ?>">

            <p class="login-username">
                <label for="<?php echo esc_attr($username_id); ?>"><?php esc_html_e('Email or username', 'der-flugschreiber-subscriptions'); ?></label>
                <input type="text" name="df_username" id="<?php echo esc_attr($username_id); ?>" autocomplete="username" required>
            </p>
            <p class="login-password">
                <label for="<?php echo esc_attr($password_id); ?>"><?php esc_html_e('Password', 'der-flugschreiber-subscriptions'); ?></label>
                <input type="password" name="df_password" id="<?php echo esc_attr($password_id); ?>" autocomplete="current-password" required>
            </p>
            <p class="login-remember">
                <label><input name="df_remember" type="checkbox" value="1"> <?php esc_html_e('Remember me', 'der-flugschreiber-subscriptions'); ?></label>
            </p>
            <p class="login-submit">
                <button type="submit" class="button df-subscriptions-login-button"><?php esc_html_e('Log in', 'der-flugschreiber-subscriptions'); ?></button>
            </p>
            <p class="df-subscriptions-lost-password">
                <a href="<?php echo esc_url(wp_lostpassword_url($form_url)); ?>"><?php esc_html_e('Forgot password?', 'der-flugschreiber-subscriptions'); ?></a>
            </p>
        </form>
        <?php

        return '<div class="df-subscriptions-login-form">' . ob_get_clean() . '</div>';
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
                    <button type="button" class="df-issues__type-button is-active" data-filter-type="paid"><?php esc_html_e('New issues', 'der-flugschreiber-subscriptions'); ?></button>
                    <button type="button" class="df-issues__type-button" data-filter-type="free_pdf"><?php esc_html_e('Old issues', 'der-flugschreiber-subscriptions'); ?></button>
                </div>

                <?php
                echo $this->render_filter_dropdown(
                    'year',
                    __('Year of issue', 'der-flugschreiber-subscriptions'),
                    __('All years', 'der-flugschreiber-subscriptions'),
                    wp_list_pluck($years, 'name', 'slug')
                ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                echo $this->render_filter_dropdown(
                    'topic',
                    __('Topic category', 'der-flugschreiber-subscriptions'),
                    __('All topics', 'der-flugschreiber-subscriptions'),
                    wp_list_pluck($topics, 'name', 'slug')
                ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                echo $this->render_filter_dropdown(
                    'sort',
                    __('Sort by', 'der-flugschreiber-subscriptions'),
                    __('Newest', 'der-flugschreiber-subscriptions'),
                    array(
                        'newest' => __('Newest', 'der-flugschreiber-subscriptions'),
                        'oldest' => __('Oldest', 'der-flugschreiber-subscriptions'),
                        'title' => __('Title', 'der-flugschreiber-subscriptions'),
                    ),
                    'newest',
                    true
                ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                ?>
            </div>

            <?php if (!empty($topics) && !is_wp_error($topics)) : ?>
                <div class="df-issues__popular" aria-label="<?php esc_attr_e('Popular categories', 'der-flugschreiber-subscriptions'); ?>">
                    <span class="df-issues__popular-label"><?php esc_html_e('Popular categories:', 'der-flugschreiber-subscriptions'); ?></span>
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

            <p class="df-issues__empty" <?php echo $initial_result['found'] ? 'hidden' : ''; ?>><?php esc_html_e('No issues found.', 'der-flugschreiber-subscriptions'); ?></p>

            <div class="df-issues__more-wrap">
                <button type="button" class="df-issues__more" <?php echo $initial_result['has_more'] ? '' : 'hidden'; ?>><?php esc_html_e('Show more', 'der-flugschreiber-subscriptions'); ?></button>
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
        <main class="df-home">
            <section class="df-home__hero">
                <div class="df-home__hero-copy">
                    <p class="df-home__eyebrow"><?php esc_html_e('Das Magazin von Piloten für Piloten', 'der-flugschreiber-subscriptions'); ?></p>
                    <h1><?php esc_html_e('Die Faszination des Fliegens. Viermal im Jahr.', 'der-flugschreiber-subscriptions'); ?></h1>
                    <p class="df-home__lead"><?php esc_html_e('Reportagen, Technik, Sicherheit und Geschichten aus der Praxis - geschrieben von Menschen, die Luftfahrt leben.', 'der-flugschreiber-subscriptions'); ?></p>

                    <div class="df-home__offer">
                        <div>
                            <span class="df-home__offer-label"><?php esc_html_e('Print-Abonnement im ersten Jahr', 'der-flugschreiber-subscriptions'); ?></span>
                            <span class="df-home__price"><?php echo esc_html($atts['price']); ?></span>
                        </div>
                        <div class="df-home__offer-detail">
                            <span><?php echo esc_html(sprintf(__('%s Ausgaben inklusive Versand und MwSt.', 'der-flugschreiber-subscriptions'), $atts['issues_per_year'])); ?></span>
                            <del><?php echo esc_html($atts['regular_price']); ?></del>
                        </div>
                    </div>

                    <div class="df-home__actions">
                        <a class="df-home__button df-home__button--primary" href="<?php echo esc_url($subscription_url); ?>"><?php esc_html_e('Jetzt abonnieren', 'der-flugschreiber-subscriptions'); ?></a>
                        <a class="df-home__text-link" href="#df-home-current-issue"><?php esc_html_e('Aktuelle Ausgabe ansehen', 'der-flugschreiber-subscriptions'); ?></a>
                    </div>
                </div>

                <div class="df-home__hero-visual">
                    <span class="df-home__discount" aria-label="<?php esc_attr_e('25 Prozent Rabatt', 'der-flugschreiber-subscriptions'); ?>">-25%</span>
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
                <div><strong><?php echo esc_html($atts['issues_per_year']); ?></strong><span><?php esc_html_e('hochwertige Ausgaben pro Jahr', 'der-flugschreiber-subscriptions'); ?></span></div>
                <div><strong><?php esc_html_e('Praxis', 'der-flugschreiber-subscriptions'); ?></strong><span><?php esc_html_e('Erfahrungen direkt aus dem Cockpit', 'der-flugschreiber-subscriptions'); ?></span></div>
                <div><strong><?php esc_html_e('Community', 'der-flugschreiber-subscriptions'); ?></strong><span><?php esc_html_e('Von und für Flugbegeisterte', 'der-flugschreiber-subscriptions'); ?></span></div>
            </section>

            <section class="df-home__current" id="df-home-current-issue">
                <div class="df-home__section-heading">
                    <p class="df-home__eyebrow"><?php esc_html_e('Aktuelle Ausgabe', 'der-flugschreiber-subscriptions'); ?></p>
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
                        <p class="df-home__issue-meta"><?php echo esc_html(trim($latest_number . ($latest_number && $latest_date_label ? ' | ' : '') . $latest_date_label)); ?></p>
                        <h3><?php esc_html_e('Geschichten, Wissen und neue Perspektiven aus der Luftfahrt.', 'der-flugschreiber-subscriptions'); ?></h3>
                        <p><?php echo esc_html($latest_issue && has_excerpt($latest_issue) ? get_the_excerpt($latest_issue) : __('Die neue Ausgabe verbindet fundierte Fachthemen mit persönlichen Reportagen, starken Bildern und Erfahrungen aus der fliegerischen Praxis.', 'der-flugschreiber-subscriptions')); ?></p>
                        <div class="df-home__actions">
                            <a class="df-home__button df-home__button--primary" href="<?php echo esc_url($subscription_url); ?>"><?php esc_html_e('Zum Abonnement', 'der-flugschreiber-subscriptions'); ?></a>
                            <?php if ($latest_issue) : ?>
                                <a class="df-home__text-link" href="<?php echo esc_url(get_permalink($latest_issue)); ?>"><?php esc_html_e('Mehr zur Ausgabe', 'der-flugschreiber-subscriptions'); ?></a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </section>

            <section class="df-home__topics">
                <div class="df-home__section-heading">
                    <p class="df-home__eyebrow"><?php esc_html_e('Was uns bewegt', 'der-flugschreiber-subscriptions'); ?></p>
                    <h2><?php esc_html_e('Luftfahrt in ihrer ganzen Vielfalt', 'der-flugschreiber-subscriptions'); ?></h2>
                </div>
                <div class="df-home__topic-grid">
                    <article><span>01</span><h3><?php esc_html_e('Flugsicherheit', 'der-flugschreiber-subscriptions'); ?></h3><p><?php esc_html_e('Praxisnahes Wissen für sichere Entscheidungen am Boden und in der Luft.', 'der-flugschreiber-subscriptions'); ?></p></article>
                    <article><span>02</span><h3><?php esc_html_e('Flugzeuge & Technik', 'der-flugschreiber-subscriptions'); ?></h3><p><?php esc_html_e('Klassiker, Innovationen und Technik verständlich erklärt.', 'der-flugschreiber-subscriptions'); ?></p></article>
                    <article><span>03</span><h3><?php esc_html_e('Menschen & Reisen', 'der-flugschreiber-subscriptions'); ?></h3><p><?php esc_html_e('Persönliche Geschichten, Reiseberichte und besondere Flugerlebnisse.', 'der-flugschreiber-subscriptions'); ?></p></article>
                    <article><span>04</span><h3><?php esc_html_e('Ausbildung & Praxis', 'der-flugschreiber-subscriptions'); ?></h3><p><?php esc_html_e('Erfahrungen und Impulse für Piloten in jeder Phase ihrer Laufbahn.', 'der-flugschreiber-subscriptions'); ?></p></article>
                </div>
            </section>

            <?php if (!empty($articles)) : ?>
                <section class="df-home__editorial">
                    <div class="df-home__section-heading df-home__section-heading--row">
                        <div>
                            <p class="df-home__eyebrow"><?php esc_html_e('Aus dem Magazin', 'der-flugschreiber-subscriptions'); ?></p>
                            <h2><?php esc_html_e('Aktuelle Geschichten', 'der-flugschreiber-subscriptions'); ?></h2>
                        </div>
                        <a class="df-home__text-link" href="<?php echo esc_url($articles_url); ?>"><?php esc_html_e('Alle Artikel', 'der-flugschreiber-subscriptions'); ?></a>
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
                                <a class="df-home__text-link" href="<?php echo esc_url(get_permalink($article)); ?>"><?php esc_html_e('Artikel lesen', 'der-flugschreiber-subscriptions'); ?></a>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

            <?php if (count($issues) > 1) : ?>
                <section class="df-home__archive">
                    <div class="df-home__section-heading df-home__section-heading--row">
                        <div>
                            <p class="df-home__eyebrow"><?php esc_html_e('Archiv', 'der-flugschreiber-subscriptions'); ?></p>
                            <h2><?php esc_html_e('Weitere Ausgaben', 'der-flugschreiber-subscriptions'); ?></h2>
                        </div>
                        <a class="df-home__text-link" href="<?php echo esc_url($issues_url); ?>"><?php esc_html_e('Alle Ausgaben', 'der-flugschreiber-subscriptions'); ?></a>
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
                    <p class="df-home__eyebrow"><?php esc_html_e('Über uns', 'der-flugschreiber-subscriptions'); ?></p>
                    <h2><?php esc_html_e('Ein Magazin, getragen von der fliegerischen Community.', 'der-flugschreiber-subscriptions'); ?></h2>
                </div>
                <div>
                    <p><?php esc_html_e('Unser Team besteht aus erfahrenen Piloten und Flugbegeisterten. Wir teilen Wissen, Erfahrungen und die Begeisterung für Motorflug, Segelflug, Fallschirmsport, Paragleiten und Modellflug.', 'der-flugschreiber-subscriptions'); ?></p>
                    <p><?php esc_html_e('Der Flugschreiber ist eine Plattform zum Lernen, Austauschen und gemeinsamen Erleben der Luftfahrt.', 'der-flugschreiber-subscriptions'); ?></p>
                </div>
            </section>

            <section class="df-home__final-cta">
                <img src="<?php echo esc_url(DF_SUBSCRIPTIONS_PLANE_URL); ?>" alt="" aria-hidden="true">
                <p class="df-home__eyebrow"><?php esc_html_e('Bereit zum Abheben?', 'der-flugschreiber-subscriptions'); ?></p>
                <h2><?php esc_html_e('Vier Ausgaben. Ein Jahr voller Luftfahrt.', 'der-flugschreiber-subscriptions'); ?></h2>
                <p><?php echo esc_html(sprintf(__('Jetzt für %s im ersten Jahr abonnieren.', 'der-flugschreiber-subscriptions'), $atts['price'])); ?></p>
                <a class="df-home__button df-home__button--light" href="<?php echo esc_url($subscription_url); ?>"><?php esc_html_e('Jetzt abonnieren', 'der-flugschreiber-subscriptions'); ?></a>
            </section>
        </main>
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
            ),
            $atts,
            'df_all_articles'
        );

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
            )
        );

        ob_start();
        ?>
        <section class="df-articles" data-initial="<?php echo esc_attr(absint($atts['initial'])); ?>" data-step="<?php echo esc_attr(absint($atts['step'])); ?>" data-page="1">
            <h2 class="df-articles__title"><?php echo esc_html($atts['title']); ?></h2>

            <div class="df-articles__filters" aria-label="<?php esc_attr_e('Article filters', 'der-flugschreiber-subscriptions'); ?>">
                <?php
                echo $this->render_filter_dropdown(
                    'magazine',
                    __('Magazine issue', 'der-flugschreiber-subscriptions'),
                    $selected_magazine_label,
                    $magazine_options,
                    $selected_magazine_id
                ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                echo $this->render_filter_dropdown(
                    'topic',
                    __('Topic category', 'der-flugschreiber-subscriptions'),
                    __('Topic category', 'der-flugschreiber-subscriptions'),
                    wp_list_pluck($topics, 'name', 'slug')
                ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                echo $this->render_filter_dropdown(
                    'sort',
                    __('Sort by', 'der-flugschreiber-subscriptions'),
                    __('Sort by', 'der-flugschreiber-subscriptions'),
                    array(
                        'newest' => __('Newest', 'der-flugschreiber-subscriptions'),
                        'oldest' => __('Oldest', 'der-flugschreiber-subscriptions'),
                        'title' => __('Title', 'der-flugschreiber-subscriptions'),
                    ),
                    'newest',
                    true
                ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                ?>
            </div>

            <?php if (!empty($topics)) : ?>
                <div class="df-articles__popular" aria-label="<?php esc_attr_e('Popular categories', 'der-flugschreiber-subscriptions'); ?>">
                    <span class="df-articles__popular-label"><?php esc_html_e('Popular categories:', 'der-flugschreiber-subscriptions'); ?></span>
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

            <p class="df-articles__empty" <?php echo $initial_result['found'] ? 'hidden' : ''; ?>><?php esc_html_e('No articles found.', 'der-flugschreiber-subscriptions'); ?></p>

            <div class="df-articles__more-wrap">
                <button type="button" class="df-articles__more" <?php echo $initial_result['has_more'] ? '' : 'hidden'; ?>><?php esc_html_e('Show more', 'der-flugschreiber-subscriptions'); ?></button>
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
                        <span><strong><?php esc_html_e('Text', 'der-flugschreiber-subscriptions'); ?></strong> <?php echo esc_html($author); ?></span>
                        <span><strong><?php esc_html_e('Date', 'der-flugschreiber-subscriptions'); ?></strong> <time datetime="<?php echo esc_attr($date); ?>"><?php echo esc_html($date_label); ?></time></span>
                        <span><strong><?php esc_html_e('Duration', 'der-flugschreiber-subscriptions'); ?></strong> <?php echo esc_html(sprintf(_n('%d Min', '%d Min', $duration, 'der-flugschreiber-subscriptions'), $duration)); ?></span>
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
                        <div><strong><?php esc_html_e('Date', 'der-flugschreiber-subscriptions'); ?></strong><span><?php echo esc_html($magazine_date_label); ?></span></div>
                        <div>
                            <strong><?php esc_html_e('Share', 'der-flugschreiber-subscriptions'); ?></strong>
                            <span class="df-article-page__share">
                                <a href="<?php echo esc_url('https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode(get_permalink($article))); ?>" target="_blank" rel="noopener">f</a>
                                <a href="<?php echo esc_url('https://twitter.com/intent/tweet?url=' . rawurlencode(get_permalink($article)) . '&text=' . rawurlencode(get_the_title($article))); ?>" target="_blank" rel="noopener">x</a>
                                <a href="<?php echo esc_url('mailto:?subject=' . rawurlencode(get_the_title($article)) . '&body=' . rawurlencode(get_permalink($article))); ?>">@</a>
                            </span>
                        </div>
                    </div>

                    <?php if ($pdf_url) : ?>
                        <a class="df-article-page__pdf" href="<?php echo esc_url($pdf_url); ?>" target="_blank" rel="noopener"><?php esc_html_e('Download PDF', 'der-flugschreiber-subscriptions'); ?></a>
                    <?php else : ?>
                        <span class="df-article-page__pdf is-disabled"><?php esc_html_e('Download PDF', 'der-flugschreiber-subscriptions'); ?></span>
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
                <span><?php esc_html_e('Date', 'der-flugschreiber-subscriptions'); ?></span>
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
                        <span><strong><?php esc_html_e('Text', 'der-flugschreiber-subscriptions'); ?></strong> <?php echo esc_html($author); ?></span>
                        <span><strong><?php esc_html_e('Duration', 'der-flugschreiber-subscriptions'); ?></strong> <?php echo esc_html(sprintf(_n('%d Min', '%d Min', $duration, 'der-flugschreiber-subscriptions'), $duration)); ?></span>
                    </div>

                    <div class="df-articles__text">
                        <h3 class="df-articles__item-title"><?php echo esc_html(get_the_title($article)); ?></h3>
                        <p class="df-articles__excerpt"><?php echo esc_html($excerpt); ?></p>
                    </div>

                    <a class="df-articles__button" href="<?php echo esc_url(get_permalink($article)); ?>"><?php esc_html_e('Read the full article', 'der-flugschreiber-subscriptions'); ?></a>
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

    private function get_articles_result($args)
    {
        $defaults = array(
            'magazine' => 0,
            'topic' => '',
            'sort' => 'newest',
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

        foreach ($query->posts as $article) {
            $html .= $this->render_article_card($article);
        }

        return array(
            'html' => $html,
            'found' => $query->found_posts > 0,
            'has_more' => ($offset + $limit) < (int) $query->found_posts,
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
}
