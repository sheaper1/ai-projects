=== Der Flugschreiber Subscriptions ===
Contributors: derflugschreiber
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.8.9
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

= 1.8.9 =
* The demo content (created via "Create demo content") now uses German excerpts and body text for the demo issues and articles instead of English placeholder text.

= 1.8.8 =
* Subscribers no longer see the WordPress admin toolbar and can no longer open the wp-admin dashboard. Any logged-in user without content-editing rights (i.e. subscribers) is redirected from wp-admin to the account page (or the homepage if none is set). Editors and administrators are unaffected, and front-end AJAX filtering keeps working.

= 1.8.7 =
* All front-end text is now in German. Translated the previously English strings on the subscriber account page (My account, My subscription, Status, Expires at, Renew subscription, Profile, Display name, Save profile, Change password, …), the login header and login error messages, the paywall block (log in / purchase subscription), the PDF access error pages, the article author/category fallback label, the AJAX filter error message, and the default archive titles.

= 1.8.6 =
* New shortcode [df_404] for a styled, on-brand 404 page (aviation "off the radar" theme with an animated radar dial in place of the 0). Drop it into an Elementor 404 template or a theme 404.php; plugin styles now load automatically on 404 pages. Optional attributes: home_url, issues_url, articles_url, blog_url (all default to the matching site pages).

= 1.8.5 =
* Added a "Remove demo content" button next to "Create demo content". It moves only the plugin-generated demo magazines and articles (those tagged _df_demo_content) to the Trash, so accidentally created demo content can be undone with one click without affecting real imported issues and articles.

= 1.8.4 =
* New setting "Article author name" (Subscriptions → Settings): when filled in, it is shown as the author ("Text") on every article and blog post instead of the WordPress account display name (e.g. show "Raphael Rothmund" instead of "admin"). Leave it empty to keep using each post author's WordPress display name.

= 1.8.3 =
* Magazine archive filter bar now lays out cleanly on tablets and phones: the issue-type toggle and the sort control each take a full-width row, while the year and topic dropdowns share an even two-column row. Previously the bar wrapped awkwardly and left the sort control floating on its own line, because a base style silently disabled the responsive grid.

= 1.8.2 =
* Magazine archive ("Alle Ausgaben") filter bar is now readable on tablets: the year/topic/sort dropdowns, the issue-type toggle, and the popular-topic chips use fluid clamp() sizing with a minimum font size instead of pure viewport units, so the selected values no longer shrink to a few pixels or get cut off ("Alle Ja…") on tablet-width screens. The filter bar also wraps gracefully when space is tight.

= 1.8.1 =
* The subscriber login/account page setting now accepts a simple path or slug (e.g. /mein-konto/), not only a full URL.
* Added a "Generate password" button to the create-subscriber form for a strong random password that stays visible so it can be copied.

= 1.8.0 =
* Blog post lists now show three posts per row, while magazine article lists stay one per row.
* Single blog posts are now rendered in the magazine article design automatically (keeping the theme header and footer); no extra setup is required.
* The subscription price, regular price, discount badge, and issues-per-year are now editable under Inhalte → Startseite → "Preis & Angebot" without touching code or shortcodes.
* Smaller article hero images on the single article and blog post views.
* Old free PDF issues are no longer listed in the article "Ausgabe" filter (they contain no individual articles).
* Fixed the issue-archive filter dropdowns being overlapped by the following filters and topic chips when open.
* Rewrote the admin setup guide ("Anleitung") into a clear five-step, code-free walkthrough.

= 1.7.2 =
* Restored the cover in the homepage "Aktuelle Ausgabe" section (now a clickable cover next to the summary) so the section is no longer a bare text block.
* Added a "free older issues" link on the homepage that points to the issue archive.
* Single issue page: added an "Alle Ausgaben" action and an access note so the heading column is never left empty (e.g. for issues without a PDF).

= 1.7.1 =
* Fixed the magazine and article post-type archives loading without plugin styles (assets are now enqueued on those archives and the plugin taxonomy pages).
* Replaced the homepage hero cover "fan" with a single current-issue cover that links to the issue itself instead of the unstyled archive.
* Removed the duplicated cover from the "Aktuelle Ausgabe" section so the hero and the section no longer show the same cover twice.

= 1.7.0 =
* Homepage hero now shows a fanned stack of the latest covers linking to all issues, with the discount moved next to the subscription price.
* Homepage hero, current-issue and archive sections feature paid issues only (free/demo PDFs are no longer promoted as subscription offers).
* Fixed the article filter dropdown being painted under the category chips when open.
* Reworked the desktop articles page (cards and filters) to scale fluidly with clamp()/vw based on the 1920 design.

= 1.6.2 =
* Added a consistent account-page heading to login and password reset screens.
* Ensured the subscription purchase panel heading remains visible with theme styles.

= 1.6.1 =
* Restyled the subscriber account to match the magazine site.
* Fixed long German password-reset button labels being clipped.
* Improved account and authentication layouts on tablets and phones.

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
