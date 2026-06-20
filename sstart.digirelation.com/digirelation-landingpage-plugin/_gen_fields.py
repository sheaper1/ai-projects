# -*- coding: utf-8 -*-
PLUG = "A:/AI project/sstart.digirelation.com/digirelation-landingpage-plugin/digirelation-landingpage"

def pq(t):
    return "'" + str(t).replace("\\", "\\\\").replace("'", "\\'") + "'"

def php(v, ind=2):
    pad = "\t" * ind
    if isinstance(v, dict):
        out = "array(\n"
        for k, val in v.items():
            out += pad + "'%s' => %s,\n" % (k, php(val, ind+1))
        return out + ("\t"*(ind-1)) + ")"
    if isinstance(v, list):
        out = "array(\n"
        for val in v:
            out += pad + "%s,\n" % php(val, ind+1)
        return out + ("\t"*(ind-1)) + ")"
    if isinstance(v, bool): return "true" if v else "false"
    if isinstance(v, int): return str(v)
    return pq(v)

KEY = [0]
def fk(n):
    KEY[0] += 1
    return "field_dlp_%03d_%s" % (KEY[0], n)

ICONS = {
    "chart":"Diagramm (fallend)","screen":"Bildschirm","circle-x":"Kreis (X)","target":"Ziel",
    "bolt":"Blitz","shield":"Schild","info":"Info","monitor":"Monitor","search":"Lupe",
    "rocket":"Rakete","star":"Stern","heart":"Herz","clock":"Uhr","users":"Personen",
}

def F(name, label, ftype="text", default=None, **extra):
    d = {"key": fk(name), "label": label, "name": name, "type": ftype}
    if default is not None: d["default_value"] = default
    d.update(extra)
    return d

def TA(name, label, default=None, rows=3):
    return F(name, label, "textarea", default, rows=rows, new_lines="")

def ICON(name, label, default):
    return F(name, label, "select", default, choices=ICONS, ui=1, return_format="value", allow_null=0)

def TAB(label):
    return {"key": fk("tab"), "label": label, "type": "tab", "placement": "left"}

def REP(name, label, subs, button):
    return {"key": fk(name), "label": label, "name": name, "type": "repeater",
            "layout": "block", "button_label": button, "sub_fields": subs}

def IMG(name, label):
    return F(name, label, "image", None, return_format="url", preview_size="medium", library="all")

fields = []
fields += [TAB("Allgemein"),
    F("brand","Marken-/Logo-Text","text","digirelation"),
    F("cta_url","CTA-Link","url","/kontakt/"),
    F("cta_btn","CTA-Button Text","text","Strategiegespräch anfragen"),
    TA("vsl_embed","VSL Video-Embed (iframe/video)",""),
    F("vsl_badge","Video-Badge Text","text","In 90 Sek. erklärt"),
    F("google_rating","Google Bewertung","text","4,9"),
    F("google_reviews","Anzahl Google-Bewertungen","text"),
]
fields += [TAB("Hero"),
    F("hero_eyebrow","Eyebrow","text","Webdesign mit Performance-Fokus"),
    F("hero_h1a","H1 – Teil 1","text","Deine "),
    F("hero_h1b","H1 – farbiges Wort","text","neue Website"),
    F("hero_h1c","H1 – Teil 2","text",", gebaut für dein Geschäft"),
    TA("hero_sub","Subtext","Wir bauen dir eine Website, die modern aussieht und zu dem passt, was du wirklich machst. Deine alte hat ausgedient."),
    REP("kpis","KPIs (Zahlen-Leiste)",[F("value","Wert","text"),F("label","Label","text")],"KPI hinzufügen"),
]
fields += [TAB("Problem"),
    F("problem_eyebrow","Eyebrow","text","Das Problem"),
    F("problem_h2","Überschrift","text","Kommt dir das bekannt vor?"),
    TA("problem_intro","Intro","Drei Probleme, an denen die meisten Websites scheitern. Wahrscheinlich kennst du mindestens eins."),
    REP("problem_cards","Problem-Karten",[ICON("icon","Icon","chart"),F("title","Titel","text"),TA("text","Text","")],"Karte hinzufügen"),
]
fields += [TAB("Lösung"),
    F("sol_eyebrow","Eyebrow","text","Die Lösung"),
    F("sol_h2","Überschrift","text","Deine Website arbeitet endlich für dich"),
    TA("sol_intro","Intro","Deine Seite bekommt einen Plan, der zu deinem Geschäft passt. Fertig ist sie erst, wenn sie dir Anfragen bringt."),
    REP("solution_cards","Lösungs-Karten",[ICON("icon","Icon","target"),F("title","Titel","text"),TA("text","Text",""),TA("checks","Häkchen-Liste (1 pro Zeile)","",rows=3)],"Karte hinzufügen"),
]
fields += [TAB("Vergleich"),
    F("cmp_eyebrow","Eyebrow","text","Der Unterschied"),
    F("cmp_h2","Überschrift","text","Baukasten, Agentur oder digirelation?"),
    TA("cmp_intro","Intro","Worin sich eine Website von uns von den üblichen Wegen unterscheidet."),
    F("cmp_crit_label","Spalte: Kriterium","text","Was zählt"),
    F("cmp_col1","Spalte 1 Titel","text","Website-Baukasten"),
    F("cmp_col2","Spalte 2 Titel","text","Klassische Agentur"),
    F("cmp_col3","Spalte 3 Titel (digirelation)","text","digirelation"),
    REP("compare_rows","Vergleichs-Zeilen",[F("crit","Kriterium","text"),F("c1","Spalte 1","text"),F("c2","Spalte 2","text"),F("c3","Spalte 3 (digirelation)","text")],"Zeile hinzufügen"),
]
fields += [TAB("Leistungen"),
    F("feat_h2","Überschrift","text","Das steckt in deiner neuen Website"),
    TA("feat_intro","Intro","Deine Seite läuft auf WordPress und ist von Grund auf für Tempo, Handy und Sichtbarkeit gebaut."),
    IMG("feat_image","Bild (links)"),
    ICON("feat_accent_icon","Akzent-Karte Icon","info"), F("feat_accent_t","Akzent-Karte Titel","text","Für dein Geschäft gebaut"), TA("feat_accent_p","Akzent-Karte Text","Deine Seite entsteht neu für dein Geschäft und deinen Verkauf, statt fertig aus dem Baukasten zu kommen."),
    ICON("feat_tech_icon","Technik-Karte Icon","monitor"), F("feat_tech_t","Technik-Karte Titel","text","Technik, die mitwächst"), TA("feat_tech_list","Technik-Karte Liste (1 pro Zeile)","Responsive auf Handy, Tablet und Desktop\nSchnelle Ladezeiten, auch mit vielen Bildern"),
    ICON("feat_find_icon","Sichtbarkeit-Karte Icon","search"), F("feat_find_t","Sichtbarkeit-Karte Titel","text","Gefunden werden"), TA("feat_find_list","Sichtbarkeit-Karte Liste (1 pro Zeile)","SEO-Grundlagen von Anfang an\nSauberer, schneller Code\nDSGVO-konform aufgesetzt"),
]
fields += [TAB("Ablauf"),
    F("how_eyebrow","Eyebrow","text","So läuft es ab"),
    F("how_h2","Überschrift","text","So entsteht deine neue Website"),
    TA("how_intro","Intro","Vier Schritte, klar und ohne Überraschungen. Du weißt jederzeit, woran wir gerade arbeiten."),
    IMG("how_image","Banner-Bild"),
    REP("steps","Schritte (automatisch nummeriert)",[F("title","Titel","text"),TA("text","Text","")],"Schritt hinzufügen"),
]
fields += [TAB("Referenzen"),
    F("ref_eyebrow","Eyebrow","text","Referenzen"),
    F("ref_h2","Überschrift","text","Echte Projekte aus 12 Branchen"),
    TA("ref_intro","Intro","Ein Auszug aus über 100 Projekten in 6 Ländern. Die Bandbreite reicht vom Energieberater bis zum Premium-Gin."),
    REP("references","Referenz-Karten",[IMG("image","Bild"),F("name","Name","text"),F("branche","Branche","text"),F("geo","Ort","text")],"Referenz hinzufügen"),
]
fields += [TAB("Kundenstimmen"),
    F("proof_eyebrow","Eyebrow","text","Kundenstimmen"),
    F("proof_h2","Überschrift","text","Was Kunden über uns sagen"),
    REP("testimonials","Testimonials",[TA("quote","Zitat",""),F("name","Name","text"),F("firma","Firma","text")],"Testimonial hinzufügen"),
    F("logos_title","Logo-Leiste Text","text","Vertraut von Unternehmen in 6 Ländern"),
    REP("logos","Logo-Leiste (Namen)",[F("name","Name","text")],"Logo hinzufügen"),
]
fields += [TAB("FAQ"),
    F("faq_eyebrow","Eyebrow","text","Häufige Fragen"),
    F("faq_h2","Überschrift","text","Was du dich vor dem Start fragst"),
    REP("faqs","FAQ",[F("frage","Frage","text"),TA("antwort","Antwort","")],"Frage hinzufügen"),
]
fields += [TAB("CTA"),
    F("cta_eyebrow","Eyebrow","text","Strategiegespräch"),
    F("cta_h2","Überschrift","text","Finde heraus, warum deine Website keine Anfragen bringt"),
    TA("cta_p","Text","Erzähl uns von deiner aktuellen Seite und deinem Ziel. Im Strategiegespräch bekommst du eine ehrliche Einschätzung, woran es liegt und was sich zuerst lohnt."),
    TA("cta_checks","Häkchen-Liste (1 pro Zeile)","Konkrete Schwachstellen statt allgemeiner Tipps\nEin klarer Plan für die neue Website\nUnverbindlich, mit Antwort in 24 Stunden"),
    F("cta_foot","Fußnote","text","Unverbindlich und keine Akquise-Anrufe ohne deine Zustimmung"),
]
fields += [TAB("Footer"),
    F("footer_copy","Copyright","text","© 2026 digirelation. Webdesign mit Performance-Fokus."),
]

group = {"key":"group_digi_lp","title":"Performance Landingpage","fields":fields,
         "location":[[{"param":"block","operator":"==","value":"digirelation/landingpage"}]],
         "menu_order":0,"active":True}

out = "<?php\n// Auto-generiert – ACF-Felder für Block digirelation/landingpage.\nif ( ! defined('ABSPATH') ) exit;\nadd_action('acf/init', function () {\n\tif ( ! function_exists('acf_add_local_field_group') ) return;\n\tacf_add_local_field_group(" + php(group,2) + ");\n});\n"
open(PLUG + "/acf-fields.php","w",encoding="utf-8").write(out)
# count repeaters
reps = [f for f in fields if f.get("type")=="repeater"]
print("total fields(top):", len(fields), "| repeaters:", len(reps), "->", [r["name"] for r in reps])
