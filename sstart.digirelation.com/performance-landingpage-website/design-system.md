# Design-System — Performance-Landingpage

Abgeleitet aus der digirelation-CI (v0.7) und angepasst an einen durchgehend dunklen, modernen Look (Nexbet-Referenz). Gilt nur für diese Landingpage.

## Grundentscheidung

Die Seite ist durchgehend dunkel. Das passt zur Nexbet-Referenz und lässt die Referenz-Screenshots und den Akzent leuchten. Die CI-Regel „Primary-CTA schwarz" gilt für helle Flächen. Auf dunklem Grund funktioniert Schwarz nicht, deshalb ist der Primär-CTA hier der Crystal-Arctic-Akzent mit weichem Glow. Der Akzent bleibt sparsam, ein Akzent-Element pro Sektion.

## Farben

| Rolle | Wert |
|---|---|
| Background base | `#070F16` |
| Panel / Section lift | `#0A151C` |
| Card | `rgba(255,255,255,0.03)` auf `#0d1820`, Border `rgba(255,255,255,0.08)` |
| Hero-Gradient | `radial-gradient(circle at 60% 40%, #0f2226 0%, #0a151c 38%, #070F16 78%)` |
| Heading-Text | `#F2F8FA` |
| Body-Text | `#9AA7B4` |
| Muted-Text | `#6B7886` |
| Akzent Primary | `#AECFE4` |
| Akzent Top (Verlauf) | `#C8DFEF` |
| Akzent Hover | `#7DAACD` |
| Akzent Ink (Text auf Akzent) | `#0A2540` |
| Erfolg / Check | `#10D180` |

## Typografie

- Font: Satoshi (Fontshare), Fallback `Inter, -apple-system, sans-serif`.
- Display / H1: 300 bis 900 je nach Gewicht, `letter-spacing: -0.03em`, `clamp()` für Responsivität.
- Zahlen (KPI): `font-variant-numeric: tabular-nums`, Gewicht 700.
- Body: 400, Zeilenhöhe 1.6.

## Radius & Tiefe

- Buttons `8px`, Cards `16px`, Hero/Großflächen `20px`.
- Card-Shadow dezent, Tiefe kommt aus Border und leichtem Inset-Glow.
- Primär-CTA mit Glow `0 0 0 1px` plus `box-shadow` in Akzent bei Hover.

## Komponenten

- **Header** sticky, mit Backdrop-Blur, Logo links, ein CTA rechts.
- **Hero** zentriert, Eyebrow-Badge, H1, Subline, zwei CTAs, Trust-Zeile (Google-Banner + KPI 45/12/6).
- **Problem-Cards** drei Karten, Icon, Titel, Fließtext.
- **Differenzierungs-Cards** drei Pillars als Antwort auf die drei Probleme.
- **Referenz-Cards** Grid, je Karte Name, Branche, Region, Leistungs-Tags.
- **Google-Banner** Bewertungs-Badge mit Sternen plus Testimonial-Karten.
- **Logo-Strip** Wortmarken echter Kunden.
- **CTA-Sektion** mit Formular (Unternehmen, Vorname, Nachname, E-Mail, Mobil).

## Tonalität

Immer du, nie Sie. Direkt, messbar, ehrlich. Regeln aus `alexsprache` gelten für jeden sichtbaren Text.
