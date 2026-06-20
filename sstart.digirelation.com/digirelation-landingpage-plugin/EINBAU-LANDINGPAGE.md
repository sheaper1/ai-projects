# digirelation – Performance Landingpage (ACF-Block) — Einbau

Fertiges Plugin: **`digirelation-landingpage.zip`** (ACF-Block + CSS/JS + alle Bilder gebündelt).
Voraussetzung: **ACF Pro** (ist installiert).

## Installation (≈2 Min)

1. **Plugins → Installieren → Plugin hochladen** → `digirelation-landingpage.zip`
   → Installieren → **Aktivieren**.
2. Neue **Seite** anlegen (oder vorhandene öffnen).
3. **Für volle Breite ohne Theme-Header/Footer:** rechts unter *Seite → Vorlage*
   **„Elementor Canvas"** wählen (sauberster Landing-Look). Alternativ normale Vorlage.
4. Block hinzufügen → nach **„Performance Landingpage"** suchen → einfügen.
   In der Block-Toolbar **Ausrichtung = volle Breite**.
5. **Veröffentlichen.**

## Bearbeiten (ACF-Felder in der Block-Seitenleiste)

- **CTA-Link** – wohin alle Buttons führen (Standard `/kontakt/`).
- **VSL Video-Embed** – iframe/`<video>`-Code; leer = Play-Platzhalter.
- **Video-Badge Text** – Standard „In 90 Sek. erklärt".
- **Google Bewertung** + **Anzahl Bewertungen** – sobald die Anzahl gefüllt ist,
  verschwindet das gelbe „Offen"-Badge.
- **Testimonials** (Repeater) – Zitat/Name/Firma. Leer = Platzhalter-Karten.

## Technische Hinweise / Entscheidungen

- **Bilder** sind im Plugin gebündelt (`assets/`), keine Medien-Upload nötig.
  Referenzkarten & Team-/Bento-Bilder kommen direkt aus dem Plugin.
- **CSS ist unter `.digi-lp` gescoped** → kollidiert nicht mit Theme/Elementor.
- **Texte** der Sektionen (Problem, Lösung, Vergleich, FAQ …) stehen im
  Render-Template `blocks/landingpage/render.php`. Bei Bedarf können einzelne
  Sektionen später als eigene ACF-Felder/Blocks ausgelagert werden.
- CTAs zeigen auf die Kontakt-/Strategiegespräch-Seite (CTA-Link-Feld).

## Noch offen (inhaltlich, vor Live)
- Echte Google-Bewertung + Anzahl.
- Echte Testimonials.
- VSL-Video.
- Ziel-Seite des CTA (Kontaktseite / Strategiegespräch-Funnel).
