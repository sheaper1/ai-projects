# Task: WPForms-Formular Immobilienverkauf

## Ziel

Ein mehrstufiges Verkäufer-Formular (Customer-Funnel) für eine Immobilien-Landingpage, das auf WordPress läuft und jede Eingabe als **echten WPForms-Entry** speichert plus **Lead-Mail** auslöst — ohne den Funnel in WPForms nachzubauen.

Vorlage war der Aroundhome-Funnel von `immobilie-richtig-verkaufen.at/formular/` (per `__NEXT_DATA__` extrahiert, 58 Slides). Der Klon ist eigenständig, sendet nichts an Aroundhome.

## Vorgaben / Entscheidungen (fix)

- **Stack:** WordPress + **WPForms Pro** (Entry-Speicherung ist Pro-only), AJAX-Submit an, kein interaktives CAPTCHA.
- **Architektur:** Funnel = sichtbare UI. Echtes WPForms-Formular liegt versteckt (offscreen) auf derselben Seite. Funnel füllt dessen Felder und triggert den nativen WPForms-Submit. Bridge ist im HTML eingebaut.
- **Datenmodell:** „alles als eigene Felder" → **27 WPForms-Felder** (22 Funnel-Slugs + 5 Kontaktfelder). Verzweigung: pro Lead nur ~10–13 gefüllt, Rest leer = gewollt.
- **Leere Felder:** Lead-Mail nutzt `{all_fields}` (lässt Leeres weg), Einzel-Eintrag blendet Leeres aus. Leere Spalten nur im CSV-Export — akzeptiert, weil Leads per Mail einzeln bearbeitet werden.
- **Maße:** im Funnel Slider, an WPForms als reine Zahl (Number-Felder).
- **Sprache:** Formular-UI Deutsch. Dev-Anleitung Englisch (Dev-Deliverable).

## Dateien

- [immobilien-formular.html](immobilien-formular.html) — produktionsreifes Formular, WPForms-Bridge eingebaut. Dev ändert **nur** den `WPF`-Block (formId + 27 Field-IDs). `formId: null` = lokale Vorschau.
- [immobilien-formular-WPFORMS-SETUP.md](immobilien-formular-WPFORMS-SETUP.md) — Dev-Anleitung (EN): Formular bauen, einbetten, IDs eintragen, Lead-Mail, Test-Checkliste, exakte Options-Werte.
- [memory.md](memory.md) — Stand, offene Punkte, nächste Schritte.

## Stil-/Arbeitsregeln

Es gelten die globalen Regeln aus der Root-`CLAUDE.md` (chirurgische Änderungen, erst denken, geprüft abgeben). Für Marketing-Text auf dem Formular: Skill `alexsprache`.

## Offene Punkte

Siehe [memory.md](memory.md). Kurzfassung: Branding (Farben/Logo) noch neutral; Ziel-Domain/Kunde der Landingpage noch offen.
