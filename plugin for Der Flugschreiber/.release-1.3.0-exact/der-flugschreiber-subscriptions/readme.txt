=== Der Flugschreiber Subscriptions ===
Contributors: derflugschreiber
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.3.0
License: GPLv2 or later

Paid magazine and article access for Der Flugschreiber subscribers.

== Description ==

This plugin adds magazine issues, magazine articles, manual subscriber management, subscription statuses and expiration dates, protected paid PDFs, subscriber account pages, email reminders, CSV tools, Gutenberg blocks, and frontend content locking.

Payments remain external: the plugin sends visitors to the configured payment page URL and does not process payments or webhooks.

== Shortcodes ==

* [df_login_form]
* [df_logout_link]
* [df_all_issues]
* [df_all_articles]
* [df_article_page]
* [df_account]
* [df_homepage]
* [df_magazine_archive]
* [df_article_archive]
* [df_magazine_page]

== Installation ==

1. Upload and activate the plugin.
2. Open DF Subscriptions in the WordPress admin area.
3. Configure the payment and subscriber login page URLs.
4. Create subscriber accounts with an expiration date.
5. Add magazine issues and link magazine articles to them.
6. Optionally create a page with [df_account] for subscriber self-service.

== Shortcode options ==

* [df_login_form redirect="https://example.com/account/"]
* [df_logout_link text="Log out" redirect="https://example.com/"]
* [df_all_issues initial="12" step="4" title="All Issues"]
* [df_all_articles initial="5" step="5" magazine="123" title="All Articles"]
* [df_article_page article="123" show_back="yes" back_url="" back_text="Go Back" button_text="Read the full article"]
* [df_account]
* [df_homepage price="38,25 €" regular_price="51,00 €" issues_per_year="4" discount="-25%" subscription_url="" issues_url="" articles_url=""]
* [df_magazine_archive title="Alle Ausgaben" intro="" initial="12" step="4"]
* [df_article_archive title="Alle Artikel" intro="" initial="6" step="6" magazine="0"]
* [df_magazine_page magazine="123" show_back="yes" back_url="" back_text="Alle Ausgaben" articles_title="Artikel dieser Ausgabe"]

== Gutenberg blocks ==

* DF All Issues
* DF All Articles
* DF Subscriber Account
* DF Article Page

== Subscriber management ==

Subscribers support active, trial, paused, and cancelled statuses. Administrators can update individual subscriptions, apply bulk actions, extend subscriptions by 30 days, search subscribers, and import or export CSV files.

The CSV columns are: email, name, expires_at, status.

== Email notices ==

When enabled, the plugin sends account emails, a reminder during the final seven days, and one expiration notice. WordPress Cron must be working for scheduled notices.

== PDF access ==

Use the public PDF URL only for old free issues. Paid issues can use the protected PDF upload field. Protected files are stored outside the WordPress installation when possible and are delivered only after the subscription check.

== Data removal ==

Plugin data is retained by default. Enable "Delete data on uninstall" before deleting the plugin if all plugin posts, terms, settings, subscriber metadata, and protected PDFs should be permanently removed.

== Public interfaces ==

* Post types: df_magazine, df_article
* Taxonomies: df_topic_category, df_issue_year
* Role: df_subscriber
* User meta: df_subscription_expires_at
* Option: df_subscription_payment_url
* Option: df_subscription_login_url
* Magazine meta: _df_magazine_access, _df_magazine_pdf_url, _df_magazine_cover_url, _df_magazine_issue_number, _df_magazine_issue_date
* Article meta: _df_article_image_url
* Article access meta: _df_article_access, _df_article_preview_words

== Changelog ==

= 1.3.0 =
* Added designed archive shortcodes for magazine issues and articles.
* Added a complete single magazine shortcode with protected PDF access, subscription actions, and related articles.
* Improved homepage semantics, configurable discount text, issue metadata, and demo excerpt handling.

= 1.2.0 =
* Added a subscription-focused dynamic homepage shortcode.
* Added responsive homepage sections for the latest issue, articles, issue archive, topics, community story, and subscription calls to action.

= 1.1.0 =
* Added subscriber account shortcode and Gutenberg blocks.
* Added active, trial, paused, and cancelled subscription statuses.
* Added bulk subscription management, CSV import/export, and change history.
* Added welcome, expiration reminder, and expired subscription emails.
* Added protected PDF storage and controlled downloads for paid issues.
* Added per-article access and preview length settings.
* Added WordPress media library selectors and content validation.
* Added conditional frontend asset loading and optional uninstall cleanup.
* Added German translation files and PHPUnit access tests.

= 1.0.20 =
* Fixed duplicate results when loading additional issues or articles.
* Prevented stale AJAX responses from overwriting newly selected filters.
* Fixed subscription expiration checks for the configured WordPress timezone.
* Added editor previews, unique login form IDs, translation loading, and cache protection for authorized paid views.
* Included paid issues that were created before the issue type metadata existed.
* Added subscriber search and pagination in the administration screen.
* Added both Der Flugschreiber plane artworks to empty image and locked-content states.

= 1.0.19 =
* Prevented paid content from leaking through REST API and full-content feeds.
* Prevented paid PDF links from appearing in locked article views.
* Added bounds to public AJAX pagination requests.
* Validated magazine relationships before saving article metadata.
