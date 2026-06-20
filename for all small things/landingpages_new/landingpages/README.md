# Landingpages — Palma Gin & Palma Vodka

Zwei eigenständige Produkt-Landingpages für Mallorca Distillery. Für den Einbau in Shopify als Liquid durch den Entwickler.

| Datei | Produkt | Stand |
|---|---|---|
| [palma-gin.html](palma-gin.html) | Palma Gin — Produkt-Showcase | fertig |
| [palma-vodka.html](palma-vodka.html) | Palma Vodka — Produkt-Showcase | fertig |
| [win-palma.html](win-palma.html) | „Win Palma" — Gewinnspiel-Landingpage | fertig (Inhalte offen) |

## Gewinnspiel-Seite (`win-palma.html`) — Instagram-UGC-Contest

Kampagnen-Landingpage im gleichen Design-System. Mechanik: **Foto-Contest auf Instagram** — Einreichung per Post/Story mit `#TakePalmaHome` + Tag `@mallorcadistillery`; die besten Einreichungen gewinnen. E-Mail läuft **parallel** (Liste/10% off), ist aber **nicht** der Eintrag.

- **Preise (wie gebrieft):** Hauptpreis = Reise nach Mallorca, **max. 3 Gewinner** (beste Einreichungen); monatliche Gewinner = **Komplettsets**.
- **Sektionen:** Hero (IG-CTA + Hashtag) · Preise · How to enter (3 IG-Schritte) · **Submissions-Wall** (`#TakePalmaHome`) · Kollektions-Teaser · E-Mail-CTA · Footer.
- **Submissions-Wall:** aktuell Beispiel-Kacheln aus echten Lifestyle-Bildern → an ein **Instagram-Hashtag-Feed-Widget** binden (z. B. Behold, EmbedSocial, Taggbox). Im HTML als `(Offen)` markiert.
- **Instagram-Realität:** Follow/Tag lässt sich **nicht** automatisch verifizieren (API tot) — Gewinner-Auswahl + Compliance-Check erfolgt manuell aus dem Hashtag-Feed.
- **Festgelegt:** Instagram-Handle `@mallorcadistillery` (Link `instagram.com/mallorcadistillery/` vor Live prüfen) · Laufzeit **bis 31.12.2026**, monatliches Window **immer Monatsende** · Eintrag **per #TakePalmaHome auf Instagram ODER per E-Mail** (E-Mail = vollwertige, kostenfreie Teilnahme) · Teilnahmebedingungen als **Entwurf** unter [win-palma-terms.html](win-palma-terms.html) (juristisch prüfen lassen).
- **`(Offen)` — vor Live-Gang:** Veranstalter-Angaben (Rechtsträger/Adresse), zulässige Länder, genauer Set-Inhalt + Anzahl monatlicher Gewinner, Reise-Details (Unterkunft/Reisedaten), Benachrichtigungsfrist — alle in der Terms-Seite als `(to confirm)` markiert.
- **Compliance eingebaut:** „not sponsored/endorsed by Instagram"-Hinweis, 18+, „No purchase necessary". Länder-Ausschlüsse je Markt (DE/UK/ES) noch festlegen.
- **E-Mail-CTA:** Formular (`onsubmit="return false"`) → an Klaviyo/Shopify binden; bewusst als „Subscribe & save 10%" formuliert, damit es nicht als Teilnahme missverstanden wird.
- **Sprache:** Englisch — bei DE-Zielgruppe umstellbar.

## Design — Produkt-Showcase (Aurae-Stil)

Editoriales **Product-Detail-Layout** nach der Aurae-Referenz: zweispaltig mit Produktinfos links und vertikaler **Bildgalerie** (4 Thumbnails + großes Hauptbild, Crossfade) rechts; **Akkordeons** unter den Infos. Flach/editorial mit viel Weißraum und feinen 1px-Linien. **Marken-Palette beibehalten** (Navy/Gold/Creme).

Sektionen pro Seite:
- **Gin:** Showcase · Botanicals (Split + Grid) · Lifestyle-Band · Story · **Collection / Gin-Range** (4 Sorten) · Awards · Reviews · Newsletter · Footer. Akkordeons: Tasting, Shipping, Returns.
- **Vodka:** Showcase · Lifestyle-Band · Ingredients/Story (Split) · **Tasting** (Nose/Palate/Finish) · **Serve** (3 Wege) · Awards · Reviews · Newsletter · Footer. Akkordeons: Ingredients, Shipping, Returns.

- **Farben:** Ink/Navy `#162F53` · Sekundär `#5B6976` · Linien `#E6E3DC` · Soft-BG `#F4F2ED` · Image-Well `#E7ECE6` · Gold `#FAD380` / Gold-tief `#B68C39` (lesbar auf Hell) · Stern-Amber `#FBBC04`
- **Fonts:** Montserrat (Headlines/Body, Sans) · **Libre Caslon Text** *italic* (Akzentwörter, z. B. „Palma *Gin*", Review-Zitate) — Ersatz für die Aurae-Fonts Nacelle/Librecasloncondensed, via Google Fonts
- **Identisch in beiden Dateien:** Sticky-Nav, Produkt-Showcase (Galerie + Akkordeons), Lifestyle-Band, Awards-Siegel, Google-Reviews, Newsletter, Footer. Unterschied nur im Showcase-Inhalt (Gin: Botanicals-Akkordeon · Vodka: Ingredients/Tasting).
- **Sprache:** Englisch.

## Interaktion & Abhängigkeiten

- **Galerie:** Crossfade des Hauptbilds bei Thumbnail-Klick (eigenes JS, Opacity-Transition).
- **Akkordeons:** Logik **verbatim aus dem `accordion.js` der Referenz** übernommen (misst `scrollHeight`, animiert `max-height` in beide Richtungen; ARIA-Rollen + Enter/Space).
- **GSAP 3.13** (CDN): `ScrollTrigger` für Fade/Slide-up beim Eintritt, `SplitText` für den zeilenweisen Reveal der Beschreibung (`[data-split]`).
- Google Fonts (Montserrat + Libre Caslon Text).
- Für Shopify: Entwickler kann GSAP/SplitText und Fonts auf Theme-Assets / `npm i gsap` + self-hosted Fonts umstellen (Spec der Referenz: Nacelle/Librecasloncondensed self-hosten). Markup/Klassen bleiben gleich.

## Bilder

Echte Shopify-CDN-Bilder fest verdrahtet (Logo, Produkt-Renders). Auflösung über `&width=…`.

| Produkt | CDN-URL (Basis) |
|---|---|
| Palma Gin | `…/Frame_148_f44972fb-…png` |
| Citrus Gin | `…/Frame_148_a176364e-…png` |
| Rosé Gin | `…/Frame_149_ae6dad33-…png` |
| Spiced Gin | `…/Frame_147_a0dc56fa-…png` |
| Monastrell Gin | `…/Frame_158_cbe20022-…png` |
| Palma Vodka | `…/Frame_152_e5a944b7-…png` |

**Lifestyle-Fotos (echte Shop-Assets, direkt eingebunden):**

| Verwendung | Datei |
|---|---|
| Gin-Hero (G&T mit Zitrone/Lavendel) | `…/Low_Res_-_091_1024x1024.jpg` |
| Gin-Band (Outdoor-Bar, Mandelblüte) | `…/Frame_260.jpg` |
| Vodka-Hero (Flasche auf Holzbrett, Flor de Sal) | `…/LowRes-096copy2_1024x1024.png` |
| Vodka-Band (Kathedrale Palma) | `…/Frame_257.jpg` |

Weitere verfügbare Assets im selben CDN-Ordner: `Frame_261.jpg` (Bergdorf), `DSC03227_1_…jpg` (Mandelblüte-Closeup), `Low_Res_-_099_1.jpg` (Citrus-Gin-Lifestyle), `LowRes-111copy2_…png` (Spiced-Gin-Lifestyle) — falls Hero/Band-Motive getauscht werden sollen.

## Galerie / Bilder tauschen

Jede Seite zeigt **ein Produkt** mit 4-Bild-Galerie. Bild tauschen = `src` im passenden `[data-gthumb]`-Thumbnail **und** im zugehörigen `[data-gmain]`-Hauptbild (gleicher Index) ersetzen. Freigestellte Flaschen-Renders tragen die Klasse `is-render` (Galerie zeigt sie mit `contain` + Padding); Lifestyle-Fotos ohne die Klasse füllen formatfüllend (`cover`).

## Awards- & Google-Sektion (noch zu befüllen)

- **Awards:** Belegt ist nur **„Spirit of the Year — Spain 2026"** (typografisches Siegel). Weitere Awards lt. `neue-strategie/tasks.md` offen — HTML-Kommentar im Markup zeigt, wo zusätzliche Siegel rein.
- **Google-Reviews:** Bewusst **keine erfundene Zahl** — nur 5 Sterne + Trust-Zeile. Echte Bewertung per Liquid an die Review-App binden. Die drei Karten sind klar markierte **Platzhalter** („Verified Google review", keine erfundenen Personen) und vor Live-Gang durch echte Judge.me-/Google-Reviews zu ersetzen.

## Responsive

- Desktop (Info links / Galerie rechts, Info sticky) · ≤991px (gestapelt, Galerie zuerst, Nav-Links aus) · ≤600px (Thumbnails als horizontale Reihe unter dem Hauptbild).

## UX-/Qualität

- `prefers-reduced-motion` respektiert (Reveal/SplitText aus, Transitions neutralisiert); `:focus-visible`-Ringe; Galerie-Thumbnails sind echte `<button>` mit `role="tab"` und Tastatur-Fokus.
- Eine einzige Social-Proof-Stelle (Google-Sektion), keine erfundenen Bewertungszahlen oder Personen.

## Hinweise für den Liquid-Einbau

- Statisches HTML/CSS/JS + drei GSAP-CDN-Skripte (gsap, ScrollTrigger, SplitText) + Google Fonts.
- Produkt-Titel/Preis/Links sind Beispielwerte → durch Liquid-Objekte ersetzbar (`{{ product.title }}`, `{{ product.price | money }}`, `{{ product.featured_image | image_url }}`). Galeriebilder ggf. aus `product.media` rendern.
- Mengen-Input + „Add to Cart"-Link → an das echte Produktformular/Cart binden.
- Newsletter-Form hat `onsubmit="return false"` → an Shopify/Klaviyo anbinden.
- `[data-reveal]` startet unsichtbar; das Inline-Script am Seitenende blendet via GSAP ein (bzw. sofort sichtbar bei reduced-motion / fehlendem GSAP).
