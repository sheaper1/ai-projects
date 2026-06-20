# Performance-Landingpage — Webseiten-Verkauf

Eigene Landingpage von digirelation für den Verkauf von Websites. Ziel ist eine kurze, conversion-fokussierte Seite, die den häufigsten Einwand abräumt und in den bestehenden Lead-Funnel führt.

## Positionierung

- Zielgruppe sind kaufbereite Unternehmer mit akutem Problem, die jetzt eine neue Website wollen.
- USP ist die professionelle Website mit echter Strategie dahinter, statt fertiger Templates von der Stange.
- Kein Lead-Magnet-PDF. Der Einstieg ist das Strategiegespräch (nicht als „kostenlos" beworben).

## Auftrag

- Kurze Landingpage für den Verkauf von Websites (Webdesign mit Performance-Fokus).
- Spricht die drei Hauptprobleme an, mit denen Kunden zu digirelation kommen.
- Enthält eine Referenz-Sektion und einen Social-Proof-Block.
- Modernes, dunkles Design im Stil der digirelation-CI (Nexbet-Referenz).
- Eine self-contained `.html`, direkt im Browser öffenbar.

## Die drei Probleme (aus Beratungsprotokollen, Fabio 15.06.2026)

1. Keine Anfragen, also kein Traffic oder Traffic ohne Conversion.
2. Schlechte Außenwirkung, also alt, nicht mobil, kein Vertrauen, passt nicht zum echten Wert.
3. Vorherige Agentur hat versagt, also Geld weg, kein Ergebnis, kein Vertrauen mehr.

Der **meistgenannte Einwand** ist Punkt 3 (Agentur hat verkackt). Die Seite räumt ihn in Problem-Sektion und Differenzierungs-Sektion gezielt ab.

## Funnel

- Die Landingpage verkauft, die Kontaktseite konvertiert. Das Formular liegt auf einer eigenen Seite (`kontakt.html`), nicht auf der Landingpage.
- Alle Primär-CTAs der Landingpage (Navi, Hero, mittlerer CTA-Block, Footer) führen auf `kontakt.html` und heißen einheitlich „Strategiegespräch anfragen".
- Angebot ist das Strategiegespräch als Einstieg. „Kostenlos" und „Website-Check" werden nicht mehr verwendet; „unverbindlich" bleibt als Aussage.
- Formularfelder analog zum bestehenden Funnel (`https://start.digirelation.com/pdf-herunterladen/`): Unternehmen, Vorname, Nachname, E-Mail, Mobilnummer, plus optional die aktuelle Website-URL.

## Referenzen (echt, von digirelation.com/referenzen)

Kennzahlen: 100+ Projekte live (Stand laut Alex), 12 Branchen, 6 Länder.

Verwendete Projekte auf der Seite (9 Cards mit echten Screenshots): Blickwinkel, European Tennis Academy, Viridis Planungsbüro, Frachtgut Food Truck, Rebuild Ingenieurbüro, JMC Eventtechnik, Silke Scholz Atelier, NM Pro Assistant, BK Partners.

Die Screenshots liegen lokal in `assets/portfolio/` (von digirelation.com geladen, eigene Assets). Der Button „Alle Referenzen ansehen" wurde entfernt, ebenso die Leistungs-Tags auf den Cards.

## Leistungs-Sektion (Bento)

Sektion „Was in jeder Website von uns steckt" zwischen Lösung und Referenzen. Bento-Grid im dunklen Design (Vorbild war ein heller Webflow-About-Block, an die CI angepasst). Links das Bild `assets/about/ueberuns.png`, rechts drei Karten mit den Leistungen als Bulletpoints (responsive, schnelle Ladezeit, individuell gebaut, SEO, DSGVO).

## How-it-works-Sektion

Sektion „So entsteht deine neue Website" zwischen Leistungen und Referenzen. Vorbild war der Framify-How-it-works-Stil, an die CI angepasst. Links das Foto `assets/about/muki-alex-workspace.jpg` (sticky), rechts vier nummerierte Steps mit Verbindungslinie (Erstgespräch, Strategie, Umsetzung, Launch und Übergabe). Die Selbstpflege per WordPress wurde aus der Leistungs-Sektion hierher in Step 4 verschoben.

## Offene Punkte (vor Live-Gang zu klären)

- `(Offen)` Echte Google-Bewertung und Anzahl der Rezensionen für das Google-Banner.
- `(Offen)` Echte Testimonials (Name, Firma, Zitat) für die Social-Proof-Karten. Aktuell als Platzhalter markiert.
- `(Offen)` Form-Action-URL bzw. Endpoint des bestehenden Funnels eintragen.
- `(Offen)` Logo-Strip nutzt noch Wortmarken statt echter Logo-Bilddateien (die Referenz-Cards selbst haben echte Screenshots).
- `(Offen)` VSL-Video in den Hero-Slot rechts einsetzen (iframe YouTube/Vimeo oder `<video>`). Der Hero ist jetzt zweispaltig (Copy links, Video rechts), der sekundäre CTA „Referenzen ansehen" wurde entfernt. Solange kein Video drin ist, zeigt der Slot Play-Button und Badge.

## Dateien

- `design-system.md` — Tokens und Komponenten dieser Seite, abgeleitet aus der digirelation-CI.
- `landingpage.html` — die Verkaufsseite, CTAs verlinken auf die Kontaktseite.
- `kontakt.html` — eigene Kontaktseite mit Formular, Logos und Trust-Elementen.
- `assets/portfolio/` — lokale Projekt-Screenshots für die Referenz-Cards.
