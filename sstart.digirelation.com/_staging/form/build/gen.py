# -*- coding: utf-8 -*-
import json, collections

OD = collections.OrderedDict

def choice_block(labels):
    ch = OD()
    for i, lab in enumerate(labels, start=1):
        ch[str(i)] = OD([("label", lab), ("value", lab), ("image", "")])
    return ch

def select_field(fid, label, labels, slug):
    return OD([
        ("id", str(fid)), ("type", "select"), ("label", label),
        ("choices", choice_block(labels)),
        ("show_values", "1"), ("style", "classic"),
        ("size", "large"), ("placeholder", "--- Bitte wählen ---"),
        ("dynamic_choices", ""), ("css", ""),
    ])

def checkbox_field(fid, label, labels, slug):
    return OD([
        ("id", str(fid)), ("type", "checkbox"), ("label", label),
        ("choices", choice_block(labels)),
        ("show_values", "1"),
        ("input_columns", ""), ("size", "large"),
        ("dynamic_choices", ""), ("css", ""),
    ])

def number_field(fid, label, slug):
    return OD([
        ("id", str(fid)), ("type", "number"), ("label", label),
        ("size", "large"), ("placeholder", ""), ("default_value", ""), ("css", ""),
    ])

def text_field(fid, label, slug):
    return OD([
        ("id", str(fid)), ("type", "text"), ("label", label),
        ("size", "large"), ("placeholder", ""),
        ("limit_count", "1"), ("limit_mode", "characters"),
        ("default_value", ""), ("input_mask", ""), ("css", ""),
    ])

def email_field(fid, label, slug):
    return OD([
        ("id", str(fid)), ("type", "email"), ("label", label),
        ("size", "large"), ("placeholder", ""), ("default_value", False), ("css", ""),
    ])

# id : (builder, label, [choices], slug)
SEL = "select"; CHK="checkbox"; NUM="number"; TXT="text"; EML="email"
spec = [
 (1, SEL, "Anrede", ["Frau","Herr","Divers"], "anrede"),
 (2, TXT, "Vorname", None, "vorname"),
 (3, TXT, "Nachname", None, "nachname"),
 (4, EML, "E-Mail", None, "email"),
 (5, TXT, "Telefon", None, "telefon"),   # Text statt Phone: robuster fuer die Bridge
 (6, TXT, "PLZ", None, "plz"),
 (7, SEL, "Immobilientyp", ["Haus","Wohnung","Gewerbe","Liegenschaft"], "immobilientyp"),
 (8, SEL, "Objektart (Haus-/Wohnungs-/Gebäudeart)", [
     "Einfamilienhaus","Reihenhaus","Doppelhaushälfte","Zweifamilienhaus","Mehrfamilienhaus",
     "Erdgeschosswohnung","Etagenwohnung","Dachgeschosswohnung","Maisonette",
     "Büro- oder Lagergebäude","Wohn- und Geschäftsgebäude","Industrie- oder Gewerbegebäude","Sonstiges"], "objektart"),
 (9, SEL, "Zustand", ["Neu","Kürzlich renoviert","Guter Zustand","Renovierungsbedürftig"], "zustand"),
 (10, SEL, "Innenausstattung", ["Gehoben","Normal","Einfach"], "innenausstattung"),
 (11, CHK, "Wertsteigernde Eigenschaften", ["Balkon/Terrasse","Einbauküche","Solaranlage/PV-Anlage","Wintergarten","Sonstiges","Keine"], "wertsteigernd"),
 (12, SEL, "Vermietungsstatus", ["Eigennutzung","Vermietet","Leerstand"], "vermietung"),
 (13, SEL, "Nettokaltmiete / Monat", ["Bis 500 €","500 € - 1.000 €","1.000 € - 2.000 €","2.000 € - 3.000 €","Über 3.000 €"], "nettokaltmiete"),
 (14, SEL, "Geschosse", ["Eine","Eineinhalb","Zwei","Mehr als zwei"], "geschosse"),
 (15, NUM, "Wohnfläche (m²)", None, "wohnflaeche"),
 (16, NUM, "Grundstücksfläche (m²)", None, "grundstuecksflaeche"),
 (17, NUM, "Nutzfläche (m²)", None, "nutzflaeche"),
 (18, NUM, "Zimmeranzahl", None, "zimmer"),
 (19, NUM, "Baujahr", None, "baujahr"),
 (20, NUM, "Wohneinheiten", None, "wohneinheiten"),
 (21, SEL, "Verkaufszeitpunkt", ["1-3 Monate","4-6 Monate","7-12 Monate","Später als 1 Jahr","Kauf"], "verkaufszeitpunkt"),
 (22, SEL, "Verkaufsgrund", ["Erbe","Marktsituation","Alter/Rente","Umzug","Sonstiges"], "verkaufsgrund"),
 (23, SEL, "Maklervertrag vorhanden", ["Nein","Ja, laufender Vertrag"], "maklervertrag"),
 (24, SEL, "Eigentümer / Entscheider", ["Ja","Teileigentümer","Angehöriger","Nein"], "eigentuemer"),
 (25, SEL, "Teileigentümer einverstanden", ["Ja","Nein"], "teileigentuemer_ok"),
 (26, SEL, "Erschließung", ["Erschlossen","Teilerschlossen","Unerschlossen"], "erschliessung"),
 (27, SEL, "Bebauungsmöglichkeiten", ["Kurzfristig bebaubar","Eingeschränkt bebaubar","Nicht bebaubar","Abrissreif bebaut","Weiß nicht"], "bebaubarkeit"),
]

fields = OD()
slugmap = OD()
for fid, typ, label, choices, slug in spec:
    if typ == SEL: f = select_field(fid, label, choices, slug)
    elif typ == CHK: f = checkbox_field(fid, label, choices, slug)
    elif typ == NUM: f = number_field(fid, label, slug)
    elif typ == EML: f = email_field(fid, label, slug)
    else: f = text_field(fid, label, slug)
    fields[str(fid)] = f
    slugmap[slug] = fid

settings = OD([
    ("form_title", "Immobilienbewertung – Funnel"),
    ("form_desc", ""),
    ("submit_text", "Anfrage absenden"),
    ("submit_text_processing", "Senden..."),
    ("ajax_submit", "1"),
    ("purge_entries_days", "365"),
    ("notification_enable", "1"),
    ("notifications", OD([("1", OD([
        ("enable", "1"),
        ("notification_name", "Neuer Immobilien-Lead"),
        ("email", "support@digirelation.com"),
        ("subject", 'Neue Immobilien-Anfrage – {field_id="7"} in {field_id="6"}'),
        ("sender_name", "Funnel - digirelation"),
        ("sender_address", "office@digirelation.com"),
        ("replyto", '{field_id="4"}'),
        ("message", "{all_fields}"),
        ("template", ""),
    ]))])),
    ("confirmations", OD([("1", OD([
        ("name", "Default Confirmation"),
        ("type", "message"),
        ("message", "<p>Vielen Dank! Ihre Anfrage wurde übermittelt.</p>"),
        ("message_scroll", "1"),
        ("message_entry_preview_style", "basic"),
    ]))])),
    ("antispam_v3", "1"),
    ("store_spam_entries", "1"),
    ("anti_spam", OD([
        ("time_limit", OD([("enable", "1"), ("duration", "2")])),
        ("filtering_store_spam", "1"),
    ])),
    ("form_tags", []),
])

form = OD([
    ("fields", fields),
    ("id", "0"),
    ("field_id", 28),
    ("settings", settings),
    ("meta", OD([("template", "blank")])),
])

out = [form]
with open("immobilien-wpforms-import.json", "w", encoding="utf-8") as fh:
    json.dump(out, fh, ensure_ascii=False, separators=(",", ":"))

print("OK fields:", len(fields))
print("slug -> id:", json.dumps(slugmap, ensure_ascii=False))
