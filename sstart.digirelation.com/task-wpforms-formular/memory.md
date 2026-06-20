# Memory: WPForms-Formular — Stand & nächste Schritte

Stand: 2026-06-18

## Was fertig ist

- Aroundhome-Funnel extrahiert (58 Slides, inkl. Verzweigungslogik) → eigenständiger Klon.
- Produktionsreifes [immobilien-formular.html](immobilien-formular.html) mit eingebauter WPForms-Bridge (`wpfSet`, `pushToWPForms`, Success/Fail-Listener, pfadbasiertes Einsammeln nur beantworteter Felder).
- Läuft standalone als Vorschau (`WPF.formId = null` → zeigt das JSON, das an WPForms ginge).
- Dev-Anleitung [immobilien-formular-WPFORMS-SETUP.md](immobilien-formular-WPFORMS-SETUP.md) inkl. 27-Felder-Tabelle + exakte Options-Werte.

## Geprüft

JSON valide, 22 Funnel-Slugs + 5 Kontaktfelder = 27 Felder, JS parst fehlerfrei.

## Offene Punkte (Entscheidung Alex / Input nötig)

- **Kunde / Ziel-Domain** der Landingpage: noch nicht festgelegt.
- **Branding:** Farben (aktuell Teal `#0e8a80` + Orange-CTA `#ff7a18`) und Logo noch neutral — auf Kunde anpassen.
- **Rechtstexte:** AGB-/Datenschutz-Links im Kontakt-Step sind Platzhalter (`#`).
- **Verkaufszeitpunkt** hat in einer Original-Variante zusätzlich „Kauf" — als Option drin gelassen, ggf. entfernen.

## Nächste Schritte (wenn beauftragt)

1. Branding/Copy auf konkreten Kunden ziehen (Skill `alexsprache` für Texte).
2. Dev liefert WPForms-Form-ID + 27 Field-IDs → in `WPF`-Block eintragen.
3. Auf der WP-Seite einbetten (Funnel-Block + verstecktes Shortcode), Test-Checkliste durchgehen.

## Wichtige technische Notizen

- Bridge-Selektoren: `#wpforms-{formId}-field_{id}` (+ `_{index}` für Radio/Checkbox).
- #1-Fehlerquelle: WPForms-Dropdown/Checkbox-**Werte** müssen zeichengenau den deutschen Labels entsprechen (siehe Appendix der Anleitung).
- Quelle der Funnel-Daten: `__NEXT_DATA__` der Aroundhome-Seite, `widgets[2]` (`formulars/questionnaire`).
