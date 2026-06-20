# Mallorca Distillery — Shopify Handoff Changelog

> Handoff log between agents (Claude + Codex). **All edits are made directly in the Shopify admin code editor — NOT in git.** This file is the only change history. Append a dated entry after every meaningful change.

## Environment / facts
- **Store:** `mallorcadistillerypalmagin.myshopify.com`
- **Active (published) theme:** "Glimpse x Mallorca Distillery (Final)" — Beyond Theme 2.6.0 by Troop Themes — **theme ID `134513918177`**
- **Editor:** `https://admin.shopify.com/store/mallorcadistillerypalmagin/themes/134513918177`
- **Products:** Palma Gin 70cl → variant `43103281152225` (€45), handle `palma-gin-70cl` · Palma Vodka 70cl → variant `43103281250529` (€40), handle `palma-vodka-70cl`

## Domains (Settings → Domains)
- `mallorcadistillery.com` = **Primary**
- `palma-gin.com` / `www` = **Alias domain** (serves storefront directly, no redirect)
- `palma-vodka.com` / `www` = **Alias domain** (was "Redirect to primary" — user changed it to Alias so it serves directly)

## Current state (all live & verified)
Both `palma-gin.com` and `palma-vodka.com` serve a single-brand landing at `/` (no redirect), built on the existing theme via domain-based logic in `theme.liquid` + two sections in `index.json`. QA passed on both (desktop + mobile): domain routing, single brand section visible, anchor menu, mobile burger (burger left / logo center / cart right), add-to-cart → side cart drawer, geo-popup suppressed, account removed. Main site `mallorcadistillery.com` unaffected.

## How it works (key implementation)
- **`templates/index.json`** — added sections `palma_gin` (type `palma-gin`) and `palma_vodka` (type `palma-vodka`) to both `sections` and `order`. They render as `shopify-section-template--16315452784865__palma_gin` (note the `template--<id>__` prefix). CSS targets them via suffix: `[id$="__palma_gin"]`.
- **`layout/theme.liquid`** — blocks added **before `</head>`**, all guarded to palma hosts (`request.host contains 'palma-gin'/'palma-vodka'`), only on index template:
  1. **Domain-routing CSS:** `#main-content > .shopify-section` — show only the matching brand section, hide the rest. Default domain hides both brand sections.
  2. **Geo-popup kill + add-to-cart drawer:** removes SpiceGems Country-Redirect popup (`[class*="spicegems_cr"]`) + clears scroll-lock; intercepts clicks on `.pg-atc-btn` / `.pv-atc-btn` → AJAX `POST /cart/add.js` (variant resolved dynamically from the button's `/products/HANDLE` link) → opens the theme cart drawer by clicking `.header--cart-toggle` (no redirect).
  3. **Header + anchor menu** (built by other session / Codex): shows `#shopify-section-header`, hides `.header--account` / `.mobile-nav--login` / original `.x-menu`; `setupLandingNav()` builds `.palma-anchor-nav` (desktop) + `.palma-mobile-anchor-nav` (mobile). Gin: Botanicals/Story/Our Gins/Awards. Vodka: Ingredients/Tasting/Serves/Awards.
  4. **Anchor-collision fix:** the hidden brand section shares ids (`#story`, `#awards`) with the visible one → strips ids from the hidden section so anchors resolve to the visible section. On vodka also renames `#story → #ingredients` and re-points nav links (legacy id cleanup).

## Editor gotchas (read before editing the theme)
- Shopify code editor = **Monaco inside a cross-origin iframe** (`online-store-web.shopifyapps.com`) → no JS/DOM access to it. Edit via `computer` clicks + keyboard.
- **Cursor positioning by click is unreliable.** Use `triple_click` to select a whole line, or `Ctrl+End` + arrow keys for deterministic positioning. Verify via the status-bar Ln/Col before typing.
- `Ctrl+F` / `Ctrl+G` / `Ctrl+H` / `Ctrl+S` only work **when Monaco is focused** — otherwise they hit the browser (history / save-page). Always confirm focus first (e.g. an arrow key moves the cursor).
- **Paste from clipboard** (write via `navigator.clipboard.writeText` on the admin tab, then `Ctrl+V`) to avoid Monaco auto-closing brackets/quotes when typing JS/CSS.
- Clipboard write needs the doc focused → click in the editor first.
- Save = `Ctrl+S` (Monaco focused); the tab's unsaved-dot `●` turns into a close `×` when saved.
- The editor tab renders **blank when backgrounded** — click in it to force a re-render.

## Landing-pages redesign status
A new design package was delivered: `A:\AI project\for all small things\landingpages_new\landingpages\` — `palma-gin.html`, `palma-vodka.html`, `win-palma.html`, `win-palma-terms.html` + `README.md` (German) + `Landing pages.docx`. New "Aurae" editorial style (GSAP 3.13 CDN, Google Fonts Montserrat + Libre Caslon, custom accordion/gallery JS).
New/changed sections — Gin: `#serve` "Perfect Serve" (new), collection → 6 bottles, reworked awards+reviews, final shop CTA (new), exit-intent popup (new). Vodka: `#story` "Our Story" (now separate), `#collection` (new), `#serve` rebuilt as cocktail cards, ⭐ rating by price, final CTA, exit popup. (Note: new vodka design separates ingredients/story properly — supersedes the runtime `#story→#ingredients` workaround.)
Manager task: (1) slug `win-maolrca` → `win-palma` + redirect; (2) integrate new sections (same classes/markup); (3) images marked `(Offen)` — Vadym to provide; (4) forms have `onsubmit="return false"` → bind to Klaviyo/Shopify, want a dedicated newsletter list; (5) publish `win-palma-terms` page + nav + fix outbound links.
**Decisions made:**
- **Forms = native Shopify, NO Klaviyo** (store has no Klaviyo). Wire newsletter/popup/giveaway forms to Shopify customer capture: `POST /contact` with `form_type=customer`, `contact[email]`, marketing consent, and `contact[tags]` for separation. "Separate list" = Shopify customer **segment by tag**. Proposed tags: `landing-newsletter` (newsletter + exit popup; optionally `palma-gin`/`palma-vodka` per page) and `win-palma-giveaway` (giveaway entries). Caveat surfaced to user: Shopify Email lacks advanced automation (no auto "entry received" email) — revisit an ESP only if auto-flows become required.
- **Integration approach = hybrid on the LIVE theme** (keep our wrapper: theme header/anchor-menu/cart-drawer/domain-routing; pull new design where clearly better).

**Integration pattern (how landing pages map to the theme — confirmed from live `win-palma`):**
- Page → JSON template `templates/page.<name>.json` → section `sections/<name>.liquid` (the HTML). The **theme provides header + footer** (`#shopify-section-header` / `#shopify-section-footer`); the landing's own `<nav>`/`<footer>` are dropped. Single section sits inside `#main-content`. Body class `template--page page--<handle>`.
- So: strip each new HTML file's own nav/footer, scope its global CSS (no bare `*{}`/`body{}` resets — prefix under a wrapper class), keep fonts via `<link>`/`@import`, fix `*.html` links to real `/pages/...` URLs.

**Still blocked (external):** Vadym images (placeholders `(Offen)`); legal review of giveaway terms (draft, many `(to confirm)`); Instagram handle + hashtag-feed widget choice.

## Execution status
**Step 2 — Terms page (DONE):**
- Ready-to-paste files in `landingpages_new/`: **`READY-win-palma-terms.section.liquid`** (cleaned section, CSS scoped under `.wpt`, nav/footer removed, links fixed to `/pages/win-palma`) and **`READY-page.win-palma-terms.json`** (template referencing section type `win-palma-terms`).
- Live page created at `/pages/win-palma-terms`, assigned template `win-palma-terms`, added to `Footer col 2`, and verified. The links in `win-palma.html` still need to point to this page during Step 5.

**Steps 3–5 (DONE):** Gin, Vodka and Win Palma redesigns are integrated live with Shopify forms. Remaining work is limited to external content inputs: Vadym's three Vodka images, final giveaway legal details, and the Instagram hashtag-feed/widget decision.

---

## Log
### 2026-06-19
- **Gin/Vodka product media correction (DONE):** replaced the four-image landing galleries with the first four official images from each matching Shopify product page. Verified live on `palma-gin.com` and `palma-vodka.com`: Gin now uses `freepik__talk__347731` + `LowRes-081copy1/2/4`; Vodka now uses `LowRes-064copy5/1/3/4`; the previously mixed Citrus Gin/Vodka imagery is gone.
- **Google Reviews restoration (DONE):** restored the real Elfsight Google Reviews widget (`cff8a79b-9ce9-4e16-9940-5f54ea0cffda`) in both Gin and Vodka landing sections, conditionally rendered by domain to avoid duplicate instances. Used Shopify's working `elfsight-sapp-*` embed class (matching the live product page) rather than the generic Elfsight embed. Verified live on both alias domains: the app hydrates, review text is visible, and each page contains 16 outbound Google review links.
- **Win Palma top-gap fix (DONE):** added a page-scoped override in the existing `sections/win-mallorca.liquid`: `body.page--win-palma #main-content{padding-top:0!important}`. The theme was adding `120px` top padding to `#main-content`; verified live that the Win Palma hero now starts exactly at the header bottom, while other pages remain untouched.
- **Win Palma giveaway redesign (DONE):** replaced the existing live `sections/win-mallorca.liquid` used by `/pages/win-palma` (no new section/template created). Imported the new full-bleed hero, monthly/grand-prize cards, entry steps, submissions wall placeholders, collection teaser, trust/why sections and final email-entry CTA; removed standalone nav/footer; scoped CSS under `.wp-new`; retained the theme header/footer and existing `page.win-mallorca.json`. Wired the email entry to Shopify customer capture with tags `win-palma-giveaway,landing-newsletter` and marketing consent. Updated both "Terms apply" links to `/pages/win-palma-terms`. Verified live: all page anchors are unique, the form posts to `/contact`, both terms links resolve correctly, and the old `/pages/win-maolrca` URL still redirects to `/pages/win-palma`. Only observed console errors are from existing third-party GDPR/Consentmo scripts. Remaining external items: legal confirmation of giveaway details, Instagram feed/widget choice, and any optional replacement campaign images.
- **Palma Vodka Aurae redesign (DONE):** replaced the existing live `sections/palma-vodka.liquid` in theme `134513918177` (no new section/template created). Imported the new product showcase, separate ingredients/tasting/story sections, six-product collection, rebuilt serve cards, awards/reviews, final CTA and exit popup; removed standalone nav/footer; scoped CSS under `.pv-new`; retained theme header/footer/domain routing; wired both forms to Shopify customer capture with tags `landing-newsletter,palma-vodka`; added `.pv-atc-btn` to both product CTAs. Updated the legacy `theme.liquid` anchor workaround so `#story` is renamed only when the section has no native `#ingredients`, preventing duplicate IDs in the new design. Verified live on `palma-vodka.com`: all anchors are unique, both forms post to `/contact`, both ATC hooks exist, and the page renders. Cross-check on `palma-gin.com`: Gin remains visible and Vodka hidden. Only observed console error is from the existing third-party GDPR cookie script. Vadym still needs to supply the three marked Vodka images (coastal bottle, Vodka Soda, Palma Mule).
- **Palma Gin Aurae redesign (DONE):** replaced the existing live `sections/palma-gin.liquid` in theme `134513918177` (no new section/template created). Imported the new editorial product showcase, six-bottle collection, Perfect Serve cards, awards/reviews, final CTA and exit popup; removed the standalone HTML nav/footer; scoped CSS under `.pg-new`; retained theme header/footer/domain routing; wired both newsletter forms to Shopify customer capture with tags `landing-newsletter,palma-gin`; added `.pg-atc-btn` to both primary CTAs for the existing AJAX cart drawer. Verified live on `palma-gin.com`: all expected anchors are unique, both forms post to `/contact`, both ATC hooks exist, and the page renders. Verified `palma-vodka.com` still shows Vodka and keeps the Gin section hidden. Only observed console error is from the existing third-party GDPR cookie script.
- **Win Palma giveaway terms page (DONE):** created and saved `sections/win-palma-terms.liquid` and `templates/page.win-palma-terms.json` in live theme `134513918177`; created visible Shopify page id `697893159235` titled "Win Palma — Giveaway Terms" with handle `/pages/win-palma-terms`, assigned template `win-palma-terms`, and added "Giveaway Terms" to `Footer col 2`. Verified the public page renders the full terms content and the new footer link resolves correctly.
- **win-palma slug + redirect (DONE):** page "Win maolrca landing" (id 697864225091, template `win-mallorca`) — handle changed `win-maolrca` → `win-palma`; Shopify URL redirect `win-maolrca → win-palma` created. Verified live: old `/pages/win-maolrca` 301s to `/pages/win-palma`. Page title also fixed: "Win maolrca landing" → "Win Palma". (Gotcha: Shopify admin Polaris fields commit on **blur** — JS native-setter works for the handle field but the title needed real keystrokes + Tab to register as a change.) NOTE: storefront `<title>` may lag due to page cache.
- Domain/homepage routing for palma-gin.com & palma-vodka.com (index.json sections + theme.liquid CSS). Verified live.
- palma-vodka.com domain changed Redirect → Alias (by user).
- Add-to-cart wired on landing buttons → AJAX add + open theme cart drawer (no redirect); geo-popup suppressed.
- Header reworked for landings: anchor menu (desktop + mobile burger), account removed.
- Fixed anchor-collision bug (duplicate `#story`/`#awards` between gin & vodka sections); renamed vodka `#story → #ingredients`. Full QA passed both sites.
- Received new landing-pages redesign package (`landingpages_new/`) — reviewed, not yet integrated (awaiting go-ahead + Klaviyo/Vadym/legal inputs).
