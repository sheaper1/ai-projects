# Changelog - WP Event Monitor

## Version 1.1.2 - May 29, 2026

### Improvements
- Added standalone event content support: the plugin now registers the `event` post type and its event category/city taxonomies.
- Added the `[wem_events]` shortcode for a complete Elementor-friendly events page with hero, filters, featured cards, event grid, image credits, and ticket actions.
- Imported events now store plugin-native `_wem_*` meta and real post content, while still syncing existing ACF fields for compatibility.
- Added source preview, so admins can inspect found events and keyword matches before creating drafts.
- Added German translation files and plugin textdomain loading.
- Added a ticket CTA in imported event content when the event link looks like booking, registration, or ticket purchase.
- Added image extraction, featured image import, and an expandable image source credit badge for imported event images.
- Added Elementor-friendly image credit shortcodes for templates that bypass the default featured image filter.
- Added advanced per-source selectors for title, date, time, description, and link while keeping Auto as the default.
- Added inline source configuration for tuning existing sources without deleting them.
- Added per-source parser mode with `Auto` as the default.
- Existing sources are upgraded to `Auto` automatically.
- Scraper now supports auto, HTML-only, and structured-data-only modes internally.
- Improved auto parsing for event headings and links near dates.
- Added support for German/English month-name dates and abbreviated dates like `02.Jun.2026`.
- PDF URLs now fail clearly instead of being parsed as HTML.
- Manual selectors now support comma-separated selectors and simple attribute selectors.
- Event deduplication now includes the event date, so recurring events with the same title/link can still be imported.
- Scrape logs now report post creation errors instead of silently showing success.

## Version 1.1.0 - April 1, 2026

### ✨ New Features
- **Instructions Tab**: Added dedicated "Instructions" tab with 4-step setup guide directly in admin panel
- **Improved Post Template**: Events now display with structured layout (Description + Details section)
- **Scheduling UI**: New Settings tab with granular control over scrape intervals
  - Weekly / Bi-weekly / Monthly options
  - Configurable day and time
  - Next scrape display
- **Description Extraction**: Events now include description field extracted from meta tags or element text
- **Custom Cron Intervals**: Added support for weekly, bi-weekly, and monthly scheduling

### 🔧 Improvements
- Enhanced post content template with better formatting
- Added `description` field to scraped events
- Improved admin interface navigation
- Better user guidance in UI

### 📚 Documentation
- Added comprehensive README.md with full setup instructions
- Added SETUP_CHECKLIST.md for step-by-step implementation
- Added ARCHITECTURE.md with technical documentation
- Added DOCS_INDEX.md for navigation between docs
- Added FIRST_STEPS.txt for quick onboarding

### 🐛 Bug Fixes
- None (initial stable release)

### ⚠️ Breaking Changes
- None

---

## Version 1.0.0 - Initial Release

### ✨ Features
- URL fetching and HTML parsing with DOMDocument
- Keyword and regex pattern matching
- Automatic WordPress draft post creation
- Activity logging
- Manual scrape triggering
- Hash-based duplicate prevention
- WordPress native Cron integration
- Admin panel with 3 tabs (Sources, Keywords, Activity Log)

### 🔒 Security
- CSRF protection with nonces
- Permission checks (manage_options)
- SQL injection prevention with prepared statements
- XSS prevention with proper escaping

### 📊 Database
- 4 custom tables: em_sources, em_keywords, em_seen, em_log
- Automatic cleanup on plugin uninstall

---

## Installation & Upgrade

### From 1.0.0 to 1.1.0
Simply update the plugin. No database migration needed - all changes are backward compatible.

1. Deactivate plugin
2. Replace plugin files
3. Activate plugin
4. Go to Event Monitor → Instructions to see new features

---

## Support

For issues or questions:
1. Check the Instructions tab in admin panel
2. Review Activity Log for error messages
3. Consult README.md for detailed documentation
4. Check SETUP_CHECKLIST.md for implementation steps

---

**Made with ❤️ by Digirelation**

Plugin License: GPL v2 or later
