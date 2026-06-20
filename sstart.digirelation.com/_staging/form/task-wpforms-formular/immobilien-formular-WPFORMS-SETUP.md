# Multi-step funnel → WPForms (Pro) — Developer Setup Guide

Front-end file: **`immobilien-formular.html`** (self-contained: markup + CSS + JS).
Goal: every answer the visitor selects lands as a real **WPForms entry** + triggers the native **lead email**. No third-party calls.

---

## How it works

The custom funnel is the only thing the visitor sees. A **real WPForms form** sits on the same page, hidden offscreen. When the visitor finishes, JS copies each collected answer into the matching WPForms field and triggers WPForms' own AJAX submit. WPForms handles nonce, validation, **entry storage**, and the notification email.

```
[Funnel UI]  --fills-->  [Hidden WPForms form]  --native AJAX submit-->  [WPForms Entry + Lead email]
```

The funnel branches: each lead only answers ~10–13 of the questions for their property type. Only answered fields are pushed; unanswered fields stay empty and are **auto-omitted** from the `{all_fields}` email and hidden in the single-entry view.

---

## Prerequisites

- **WPForms Pro** (entry storage is Pro-only).
- WPForms → Settings → General → **Enable AJAX form submission = ON** (no page reload).
- **No interactive CAPTCHA** (reCAPTCHA / hCaptcha / Turnstile) on this form — it blocks scripted submit. WPForms' automatic anti-spam token is fine.

---

## Step 1 — Build the WPForms form (27 fields)

Create **one** form. Set **every field to "not required"** (validation happens in the funnel; required + offscreen fights jQuery validation).

For every **Dropdown** and **Checkbox** field, turn on **"Show Values"** and set each choice's *value* to **exactly** the German label listed in the appendix below. The bridge sets fields by value, so they must match character-for-character.

| Slug (funnel key) | Field label | WPForms field type |
|---|---|---|
| `anrede` | Anrede | Dropdown |
| `vorname` | Vorname | Single Line Text |
| `nachname` | Nachname | Single Line Text |
| `email` | E-Mail | Email |
| `telefon` | Telefon | Phone |
| `plz` | PLZ | Single Line Text |
| `immobilientyp` | Immobilientyp | Dropdown |
| `objektart` | Objektart (Haus-/Wohnungs-/Gebäudeart) | Dropdown |
| `zustand` | Zustand | Dropdown |
| `innenausstattung` | Innenausstattung | Dropdown |
| `wertsteigernd` | Wertsteigernde Eigenschaften | Checkboxes |
| `vermietung` | Vermietungsstatus | Dropdown |
| `nettokaltmiete` | Nettokaltmiete / Monat | Dropdown |
| `geschosse` | Geschosse | Dropdown |
| `wohnflaeche` | Wohnfläche (m²) | Number |
| `grundstuecksflaeche` | Grundstücksfläche (m²) | Number |
| `nutzflaeche` | Nutzfläche (m²) | Number |
| `zimmer` | Zimmeranzahl | Number |
| `baujahr` | Baujahr | Number |
| `wohneinheiten` | Wohneinheiten | Number |
| `verkaufszeitpunkt` | Verkaufszeitpunkt | Dropdown |
| `verkaufsgrund` | Verkaufsgrund | Dropdown |
| `maklervertrag` | Maklervertrag vorhanden | Dropdown |
| `eigentuemer` | Eigentümer / Entscheider | Dropdown |
| `teileigentuemer_ok` | Teileigentümer einverstanden | Dropdown |
| `erschliessung` | Erschließung | Dropdown |
| `bebaubarkeit` | Bebauungsmöglichkeiten | Dropdown |

Write down each field's **Field ID** (WPForms shows it in the builder, field → Advanced).

---

## Step 2 — Place both on the page (same WP page)

1. Paste the **funnel** (`#irvFunnel` block + `<style>` + `<script>` from `immobilien-formular.html`) into a **Custom HTML** block, or enqueue it in the template.
2. Add the **WPForms shortcode** in a second block, kept in the DOM but offscreen — **not** `display:none` (that makes inputs unsubmittable):

```html
<div aria-hidden="true" style="position:absolute;left:-99999px;top:0;width:1px;height:1px;overflow:hidden">
  [wpforms id="123"]
</div>
```

---

## Step 3 — Fill in IDs (the only code change)

At the top of the funnel `<script>` there is one config block. Set `formId` and every Field ID:

```js
const WPF = {
  formId: 123,                       // your WPForms form ID
  field: {                           // slug : WPForms field ID
    anrede:1, vorname:2, nachname:3, email:4, telefon:5, plz:6,
    immobilientyp:7, objektart:8, zustand:9, innenausstattung:10,
    wertsteigernd:11, vermietung:12, nettokaltmiete:13, geschosse:14,
    wohnflaeche:15, grundstuecksflaeche:16, nutzflaeche:17,
    zimmer:18, baujahr:19, wohneinheiten:20,
    verkaufszeitpunkt:21, verkaufsgrund:22, maklervertrag:23,
    eigentuemer:24, teileigentuemer_ok:25, erschliessung:26, bebaubarkeit:27
  }
};
```

That's it — the bridge (`wpfSet`, `pushToWPForms`, success/fail listeners) is already wired. While `formId` is `null` the funnel runs in local preview mode and shows the collected data instead of submitting.

---

## Step 4 — Lead email

WPForms → form → Settings → **Notifications**:
- **Send To:** recipient address.
- **Message:** keep `{all_fields}` — it lists only the answered fields (empty ones skipped automatically).
- Optional subject with smart tags, e.g. `Neue Anfrage – {field_id="7"} in {field_id="6"}` (type + PLZ).

---

## Step 5 — Test checklist

- **Wohnung** path → entry under WPForms → Entries with only the Wohnung fields filled; lead email contains only answered questions.
- **Haus** and **Gewerbe** paths → correct field sets populate.
- No page reload on submit (AJAX working).
- Network tab: POST to `admin-ajax.php` (`action=wpforms_submit`), nothing to any external domain.
- Back-button in the funnel + switching branch → no stale answers from the abandoned branch in the entry.

---

## Gotchas

- **Value matching is the #1 failure mode.** Dropdown/Checkbox *values* in WPForms must equal the labels in the appendix exactly (incl. spaces, `€`, slashes).
- Keep all WPForms fields **not required**.
- The bridge targets `#wpforms-{formId}-field_{id}` (and `_{index}` for radio/checkbox). Verify once in the inspector if your WPForms version differs.
- Numbers (`wohnflaeche`, `zimmer`, `baujahr`, …) are sent as plain numeric strings — use **Number** fields, no thousands separators.

---

## Appendix — exact option values (set these as WPForms choice values)

```
immobilientyp:      Haus | Wohnung | Gewerbe | Liegenschaft
objektart:          Einfamilienhaus | Reihenhaus | Doppelhaushälfte | Zweifamilienhaus | Mehrfamilienhaus |
                    Erdgeschosswohnung | Etagenwohnung | Dachgeschosswohnung | Maisonette |
                    Büro- oder Lagergebäude | Wohn- und Geschäftsgebäude | Industrie- oder Gewerbegebäude | Sonstiges
zustand:            Neu | Kürzlich renoviert | Guter Zustand | Renovierungsbedürftig
innenausstattung:   Gehoben | Normal | Einfach
wertsteigernd:      Balkon/Terrasse | Einbauküche | Solaranlage/PV-Anlage | Wintergarten | Sonstiges | Keine
vermietung:         Eigennutzung | Vermietet | Leerstand
nettokaltmiete:     Bis 500 € | 500 € - 1.000 € | 1.000 € - 2.000 € | 2.000 € - 3.000 € | Über 3.000 €
geschosse:          Eine | Eineinhalb | Zwei | Mehr als zwei
verkaufszeitpunkt:  1-3 Monate | 4-6 Monate | 7-12 Monate | Später als 1 Jahr | Kauf
verkaufsgrund:      Erbe | Marktsituation | Alter/Rente | Umzug | Sonstiges
maklervertrag:      Nein | Ja, laufender Vertrag
eigentuemer:        Ja | Teileigentümer | Angehöriger | Nein
teileigentuemer_ok: Ja | Nein
erschliessung:      Erschlossen | Teilerschlossen | Unerschlossen
bebaubarkeit:       Kurzfristig bebaubar | Eingeschränkt bebaubar | Nicht bebaubar | Abrissreif bebaut | Weiß nicht

Number fields (no fixed choices): wohnflaeche, grundstuecksflaeche, nutzflaeche, zimmer, baujahr, wohneinheiten
Text/Email/Phone: vorname, nachname, plz, email, telefon
anrede (Dropdown): Frau | Herr | Divers
```
