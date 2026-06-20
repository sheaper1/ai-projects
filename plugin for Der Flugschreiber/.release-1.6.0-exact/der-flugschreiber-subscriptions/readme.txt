=== Der Flugschreiber Subscriptions ===
Contributors: derflugschreiber
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.6.0
License: GPLv2 or later

Paid magazine and article access for Der Flugschreiber subscribers.

== Description ==

This plugin adds magazine issues, magazine articles, manual subscriber management, subscription statuses and expiration dates, protected paid PDFs, subscriber account pages, email reminders, CSV tools, Gutenberg blocks, and frontend content locking.

Payments remain external: the plugin sends visitors to the configured payment page URL and does not process payments or webhooks.

== Installation ==

1. Upload and activate the plugin.
2. Open DF Subscriptions in the WordPress admin area.
3. Configure the payment and subscriber login page URLs.
4. Create subscriber accounts with an expiration date.
5. Add magazine issues and link magazine articles to them.
6. Create one subscriber account page with the DF Subscriber Account block and use its URL as the subscriber login page.

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

= 1.6.0 =
* Unified subscriber login, password reset, profile, subscription status, and logout on one account page.
* The DF Subscriber Account block now shows the login flow to guests and the account to authenticated subscribers.
* Removed technical setup-code details from the admin guide and readme.

= 1.5.0 =
* Added a fully styled, on-brand password reset flow. Subscribers are no longer sent to the default WordPress login/lost-password screen.
* The reset request and new-password forms render on the login page with the same design; the reset email links back to that page.
* Added editable reset texts and email under Inhalte → Anmeldung.

= 1.4.0 =
* Added an in-admin step-by-step setup guide ("Anleitung").
* Translated all front-end interface defaults (filters, sorting, tabs, login form, article meta, empty states, buttons) to German.
* Completed the German translation of the admin area, post type labels, emails, and notices.
* Switched demo content topics to German labels.
* Replaced the deprecated get_page_by_title() call used by the demo content generator.

= 1.3.1 =
* Replaced the simplified single magazine article cards with the full filtered article list.

= 1.3.0 =
* Added designed archive views for magazine issues and articles.
* Added a complete single magazine view with protected PDF access, subscription actions, and related articles.
* Improved homepage semantics, configurable discount text, issue metadata, and demo excerpt handling.

= 1.2.0 =
* Added a subscription-focused dynamic homepage.
* Added responsive homepage sections for the latest issue, articles, issue archive, topics, community story, and subscription calls to action.

= 1.1.0 =
* Added subscriber account and Gutenberg blocks.
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
