# Immobilien-Funnel → WPForms (Pro) — Einbau

Fertige Dateien:
- **`immobilien-wpforms-import.json`** — komplette WPForms-Form (27 Felder, exakte
  Auswahl-Werte inkl. `€`/Slashes, „Show Values" an). Direkt importierbar.
- **`immobilien-formular.html`** — der Funnel; die 27 Field-IDs sind **schon
  eingetragen** (passen 1:1 zur Import-Datei). Offen ist nur `WPF.formId`.

## Schritte (≈5 Min, keine Handarbeit an 27 Feldern)

1. **Form importieren:** WPForms → **Tools → Import** → `immobilien-wpforms-import.json`
   hochladen → importieren. Es entsteht die Form **„Immobilienbewertung – Funnel"**.
2. **Form-ID merken** (Formularliste oder Shortcode zeigt sie).
3. **Auf der Zielseite zwei Blöcke:**
   - **Funnel** in einen **Custom-HTML-Block**: den Inhalt von
     `immobilien-formular.html` ab `<div id="irvFunnel">` bis inkl. `</script>`
     einfügen (oder die ganze Datei im Template einbinden).
   - **Verstecktes WPForms-Formular** in einen zweiten Block (NICHT `display:none`):
     ```html
     <div aria-hidden="true" style="position:absolute;left:-99999px;top:0;width:1px;height:1px;overflow:hidden">
       [wpforms id="DEINE_FORM_ID"]
     </div>
     ```
4. **Eine Zahl eintragen:** im Funnel-`<script>` oben `formId: null` →
   `formId: DEINE_FORM_ID` (dieselbe Nummer wie im Shortcode). Field-IDs sind schon korrekt.
   → Sobald `formId` gesetzt ist, **versteckt sich die WPForms-Form automatisch**
   (per Script off-screen) UND der Vorschau-Dump unten verschwindet. Kein
   manuelles Gruppieren / kein CSS-Klasse-Setzen nötig.
5. **WPForms-Settings:** AJAX-Submit = an (im Import bereits `1`), **kein**
   interaktives Captcha auf dieser Form.

> Hinweis: Die WPForms-Form MUSS auf der Seite bleiben (sie speichert den Entry +
> sendet die Lead-Mail). Sie wird nur off-screen geschoben, **nie** `display:none`.

## Test
- Pfade Wohnung / Haus / Gewerbe / Liegenschaft durchklicken → je ein **Entry**
  unter WPForms → Entries (nur die beantworteten Felder gefüllt).
- **Lead-Mail** geht an `support@digirelation.com` (`{all_fields}`).
- Netzwerk-Tab: POST nur an `admin-ajax.php` (`action=wpforms_submit`), nichts extern.

## Getroffene Entscheidungen (Dev = Claude)
- **`telefon` = Text-Feld** statt Phone: robuster für die Bridge (Smart-Phone /
  intl-tel-input kann ein gescriptetes `value`-Setzen verlieren). Bridge bleibt gleich;
  bei Bedarf auf Phone umstellbar.
- **Lead-Notification:** an `support@digirelation.com`, Absender `office@digirelation.com`,
  Betreff mit Smart-Tags (Immobilientyp + PLZ). Anpassbar.
- **CRM-Webhook** (`crm.digirelation.com`) wie beim bestehenden `Kontaktformular`
  ist **NICHT** enthalten — auf Wunsch ergänze ich ihn (Mapping
  vorname/nachname/email/telefon/PLZ … auf die CRM-Felder). Kurz Bescheid geben.

Vollständige Hintergrund-Doku: `immobilien-formular-WPFORMS-SETUP.md` (im Task-ZIP).
