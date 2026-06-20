# -*- coding: utf-8 -*-
import re, os

PLUG = "A:/AI project/sstart.digirelation.com/digirelation-landingpage-plugin/digirelation-landingpage"
R = PLUG + "/blocks/landingpage/render.php"
s = open(R, encoding="utf-8").read()

def pq(t):
    return "'" + t.replace("\\", "\\\\").replace("'", "\\'") + "'"

# ---------- helper ----------
helper = """
if ( ! function_exists('digi_lp_t') ) {
  function digi_lp_t($k,$d=''){ $v = get_field($k); return ($v===null || $v==='') ? $d : $v; }
}
"""
s = s.replace("$google_reviews = get_field('google_reviews');",
              "$google_reviews = get_field('google_reviews');\n" + helper, 1)

# ---------- scalars ----------
SC = [
 ("hero_eyebrow", '<span class="dot"></span>Webdesign mit Performance-Fokus</span>', 'Webdesign mit Performance-Fokus'),
 ("hero_sub", 'Wir bauen dir eine Website, die modern aussieht und zu dem passt, was du wirklich machst. Deine alte hat ausgedient.', 'Wir bauen dir eine Website, die modern aussieht und zu dem passt, was du wirklich machst. Deine alte hat ausgedient.'),
 ("problem_h2", '<h2>Kommt dir das bekannt vor?</h2>', 'Kommt dir das bekannt vor?'),
 ("problem_intro", 'Drei Probleme, an denen die meisten Websites scheitern. Wahrscheinlich kennst du mindestens eins.', 'Drei Probleme, an denen die meisten Websites scheitern. Wahrscheinlich kennst du mindestens eins.'),
 ("prob1_t", '<h3>Keine Anfragen</h3>', 'Keine Anfragen'),
 ("prob1_p", 'Deine Seite hat Besucher, aber daraus wird nichts. Oder es kommt gar kein Traffic. So oder so bleibt das Telefon still und neue Kunden suchst du woanders.', 'Deine Seite hat Besucher, aber daraus wird nichts. Oder es kommt gar kein Traffic. So oder so bleibt das Telefon still und neue Kunden suchst du woanders.'),
 ("prob2_t", '<h3>Eine Außenwirkung, die nicht zu dir passt</h3>', 'Eine Außenwirkung, die nicht zu dir passt'),
 ("prob2_p", 'Die Seite wirkt alt, lädt langsam und sieht auf dem Handy kaputt aus. Wer dich nicht kennt, klickt weg, bevor er versteht, wie gut du wirklich bist.', 'Die Seite wirkt alt, lädt langsam und sieht auf dem Handy kaputt aus. Wer dich nicht kennt, klickt weg, bevor er versteht, wie gut du wirklich bist.'),
 ("prob3_t", '<h3>Die letzte Agentur hat kassiert und nichts geliefert</h3>', 'Die letzte Agentur hat kassiert und nichts geliefert'),
 ("prob3_p", 'Das Geld ist weg, das Ergebnis blieb aus und am Ende hat keiner Verantwortung übernommen. Jetzt fragst du dich, ob sich das Ganze überhaupt nochmal lohnt.', 'Das Geld ist weg, das Ergebnis blieb aus und am Ende hat keiner Verantwortung übernommen. Jetzt fragst du dich, ob sich das Ganze überhaupt nochmal lohnt.'),
 ("sol_h2", '<h2>Deine Website arbeitet endlich für dich</h2>', 'Deine Website arbeitet endlich für dich'),
 ("sol_intro", 'Deine Seite bekommt einen Plan, der zu deinem Geschäft passt. Fertig ist sie erst, wenn sie dir Anfragen bringt.', 'Deine Seite bekommt einen Plan, der zu deinem Geschäft passt. Fertig ist sie erst, wenn sie dir Anfragen bringt.'),
 ("sol1_t", '<h3>Besucher, die dich auch anfragen</h3>', 'Besucher, die dich auch anfragen'),
 ("sol1_p", 'Deine Seite führt Besucher Schritt für Schritt bis zur Anfrage, statt sie nur hübsch zu empfangen. So wird aus einem Klick echtes Geschäft.', 'Deine Seite führt Besucher Schritt für Schritt bis zur Anfrage, statt sie nur hübsch zu empfangen. So wird aus einem Klick echtes Geschäft.'),
 ("sol2_t", '<h3>Ein erster Eindruck, der überzeugt</h3>', 'Ein erster Eindruck, der überzeugt'),
 ("sol2_p", 'Deine Seite lädt schnell, sieht auf dem Handy gut aus und überzeugt Fremde in den ersten Sekunden. Endlich sieht dein Auftritt so aus, wie gut deine Arbeit wirklich ist.', 'Deine Seite lädt schnell, sieht auf dem Handy gut aus und überzeugt Fremde in den ersten Sekunden. Endlich sieht dein Auftritt so aus, wie gut deine Arbeit wirklich ist.'),
 ("sol3_t", '<h3>Du weißt jederzeit, was passiert</h3>', 'Du weißt jederzeit, was passiert'),
 ("sol3_p", 'Du hast einen festen Ansprechpartner, klare Zahlen und kein Fachchinesisch. Du siehst jederzeit, woran wir arbeiten und was es dir bringt.', 'Du hast einen festen Ansprechpartner, klare Zahlen und kein Fachchinesisch. Du siehst jederzeit, woran wir arbeiten und was es dir bringt.'),
 ("cmp_h2", '<h2>Baukasten, Agentur oder digirelation?</h2>', 'Baukasten, Agentur oder digirelation?'),
 ("cmp_intro", 'Worin sich eine Website von uns von den üblichen Wegen unterscheidet.', 'Worin sich eine Website von uns von den üblichen Wegen unterscheidet.'),
 ("feat_h2", '<h2>Das steckt in deiner neuen Website</h2>', 'Das steckt in deiner neuen Website'),
 ("feat_intro", 'Deine Seite läuft auf WordPress und ist von Grund auf für Tempo, Handy und Sichtbarkeit gebaut.', 'Deine Seite läuft auf WordPress und ist von Grund auf für Tempo, Handy und Sichtbarkeit gebaut.'),
 ("feat_accent_t", '<h3>Für dein Geschäft gebaut</h3>', 'Für dein Geschäft gebaut'),
 ("feat_accent_p", 'Deine Seite entsteht neu für dein Geschäft und deinen Verkauf, statt fertig aus dem Baukasten zu kommen.', 'Deine Seite entsteht neu für dein Geschäft und deinen Verkauf, statt fertig aus dem Baukasten zu kommen.'),
 ("feat_tech_t", '<h3>Technik, die mitwächst</h3>', 'Technik, die mitwächst'),
 ("feat_find_t", '<h3>Gefunden werden</h3>', 'Gefunden werden'),
 ("how_h2", '<h2>So entsteht deine neue Website</h2>', 'So entsteht deine neue Website'),
 ("how_intro", 'Vier Schritte, klar und ohne Überraschungen. Du weißt jederzeit, woran wir gerade arbeiten.', 'Vier Schritte, klar und ohne Überraschungen. Du weißt jederzeit, woran wir gerade arbeiten.'),
 ("step1_t", '<h3>Strategiegespräch</h3>', 'Strategiegespräch'),
 ("step1_p", 'Wir schauen uns deine aktuelle Seite an und klären, wo die Anfragen verloren gehen. Du bekommst eine ehrliche Einschätzung, unverbindlich.', 'Wir schauen uns deine aktuelle Seite an und klären, wo die Anfragen verloren gehen. Du bekommst eine ehrliche Einschätzung, unverbindlich.'),
 ("step2_t", '<h3>Strategie und Konzept</h3>', 'Strategie und Konzept'),
 ("step2_p", 'Wir legen Struktur, Inhalte und den Weg zur Anfrage fest, bevor wir etwas bauen. So weißt du vorher, wie deine Seite Kunden gewinnt.', 'Wir legen Struktur, Inhalte und den Weg zur Anfrage fest, bevor wir etwas bauen. So weißt du vorher, wie deine Seite Kunden gewinnt.'),
 ("step3_t", '<h3>Design und Umsetzung</h3>', 'Design und Umsetzung'),
 ("step3_p", 'Wir bauen deine neue Website und zeigen dir Zwischenstände. Du gibst Feedback, wir setzen es um, bis alles sitzt.', 'Wir bauen deine neue Website und zeigen dir Zwischenstände. Du gibst Feedback, wir setzen es um, bis alles sitzt.'),
 ("step4_t", '<h3>Launch und Übergabe</h3>', 'Launch und Übergabe'),
 ("step4_p", 'Wir gehen live und zeigen dir, wie du Inhalte selbst pflegst. Auf Wunsch übernehmen wir die laufende Betreuung.', 'Wir gehen live und zeigen dir, wie du Inhalte selbst pflegst. Auf Wunsch übernehmen wir die laufende Betreuung.'),
 ("ref_h2", '<h2>Echte Projekte aus 12 Branchen</h2>', 'Echte Projekte aus 12 Branchen'),
 ("ref_intro", 'Ein Auszug aus über 100 Projekten in 6 Ländern. Die Bandbreite reicht vom Energieberater bis zum Premium-Gin.', 'Ein Auszug aus über 100 Projekten in 6 Ländern. Die Bandbreite reicht vom Energieberater bis zum Premium-Gin.'),
 ("proof_h2", '<h2>Was Kunden über uns sagen</h2>', 'Was Kunden über uns sagen'),
 ("faq_h2", '<h2>Was du dich vor dem Start fragst</h2>', 'Was du dich vor dem Start fragst'),
 ("cta_h2", '<h2>Finde heraus, warum deine Website keine Anfragen bringt</h2>', 'Finde heraus, warum deine Website keine Anfragen bringt'),
 ("cta_p", 'Erzähl uns von deiner aktuellen Seite und deinem Ziel. Im Strategiegespräch bekommst du eine ehrliche Einschätzung, woran es liegt und was sich zuerst lohnt.', 'Erzähl uns von deiner aktuellen Seite und deinem Ziel. Im Strategiegespräch bekommst du eine ehrliche Einschätzung, woran es liegt und was sich zuerst lohnt.'),
 ("footer_copy", '© 2026 digirelation. Webdesign mit Performance-Fokus.', '© 2026 digirelation. Webdesign mit Performance-Fokus.'),
]
defaults = {}
for k, old, dft in SC:
    new = old.replace(dft, "<?php echo digi_lp_t('%s', %s); ?>" % (k, pq(dft)))
    assert old in s, "MISSING " + k
    assert s.count(old) == 1, "AMBIG %d %s" % (s.count(old), k)
    s = s.replace(old, new)
    defaults[k] = dft

# cta button (mehrfach)
s = s.replace('>Strategiegespräch anfragen</a>',
              "><?php echo digi_lp_t('cta_btn','Strategiegespräch anfragen'); ?></a>")
defaults['cta_btn'] = 'Strategiegespräch anfragen'

# ---------- balanced div helper ----------
def div_span(txt, start):
    depth = 0; pos = start
    rx = re.compile(r'<div\b|</div>')
    while True:
        m = rx.search(txt, pos)
        if not m: return None
        if m.group() == '</div>':
            depth -= 1
            if depth == 0: return (start, m.end())
        else:
            depth += 1
        pos = m.end()

# ---------- references repeater ----------
start = s.index('<div class="ref-grid">')
span = div_span(s, start)
orig = s[span[0]:span[1]]
inner = orig[len('<div class="ref-grid">'):-len('</div>')]
ref_loop = (
'<div class="ref-grid">\n'
'<?php if ( have_rows(\'references\') ) : while ( have_rows(\'references\') ) : the_row();\n'
'        $ri=get_sub_field(\'image\'); $rn=get_sub_field(\'name\'); $rb=get_sub_field(\'branche\'); $rg=get_sub_field(\'geo\'); ?>\n'
'      <div class="ref-card reveal">\n'
'        <div class="ref-thumb"><img src="<?php echo esc_url(is_array($ri)?$ri[\'url\']:$ri); ?>" alt="<?php echo esc_attr($rn); ?>" loading="lazy"></div>\n'
'        <div class="ref-body">\n'
'          <h3><?php echo esc_html($rn); ?></h3>\n'
'          <div class="ref-meta"><?php echo esc_html($rb); ?> <span class="geo">· <?php echo esc_html($rg); ?></span></div>\n'
'        </div>\n'
'      </div>\n'
'<?php endwhile; else : ?>' + inner + '<?php endif; ?>\n'
'    </div>'
)
s = s[:span[0]] + ref_loop + s[span[1]:]

# ---------- faq repeater ----------
start = s.index('<div class="faq-list">')
span = div_span(s, start)
orig = s[span[0]:span[1]]
inner = orig[len('<div class="faq-list">'):-len('</div>')]
faq_loop = (
'<div class="faq-list">\n'
'<?php if ( have_rows(\'faqs\') ) : while ( have_rows(\'faqs\') ) : the_row();\n'
'        $fq=get_sub_field(\'frage\'); $fa=get_sub_field(\'antwort\'); ?>\n'
'      <div class="faq-item reveal">\n'
'        <button class="faq-q" aria-expanded="false">\n'
'          <span><?php echo esc_html($fq); ?></span>\n'
'          <svg class="faq-ic" viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>\n'
'        </button>\n'
'        <div class="faq-a"><p><?php echo esc_html($fa); ?></p></div>\n'
'      </div>\n'
'<?php endwhile; else : ?>' + inner + '<?php endif; ?>\n'
'    </div>'
)
s = s[:span[0]] + faq_loop + s[span[1]:]

open(R, "w", encoding="utf-8").write(s)
print("scalars:", len(defaults), "| references+faq repeaters injected")

# ====================== ACF FIELD GROUP (acf-fields.php) ======================
def php(v, ind=1):
    pad = "\t" * ind
    if isinstance(v, dict):
        out = "array(\n"
        for k, val in v.items():
            out += pad + "'%s' => %s,\n" % (k, php(val, ind+1))
        return out + ("\t" * (ind-1)) + ")"
    if isinstance(v, list):
        out = "array(\n"
        for val in v:
            out += pad + "%s,\n" % php(val, ind+1)
        return out + ("\t" * (ind-1)) + ")"
    if isinstance(v, bool): return "true" if v else "false"
    if isinstance(v, int): return str(v)
    return pq(v)

KEY = [0]
def fk(name):
    KEY[0] += 1
    return "field_dlp_%02d_%s" % (KEY[0], name)

def f_text(name, label, default=None, ftype="text", **extra):
    d = {"key": fk(name), "label": label, "name": name, "type": ftype}
    if default is not None: d["default_value"] = default
    d.update(extra)
    return d

def f_tab(label):
    return {"key": fk("tab"), "label": label, "type": "tab", "placement": "left"}

def ta(name, label, default):
    return f_text(name, label, default, ftype="textarea", rows=3, new_lines="")

fields = []
# --- Allgemein ---
fields += [f_tab("Allgemein"),
    f_text("cta_url", "CTA-Link (Strategiegespräch)", "/kontakt/", ftype="url"),
    f_text("cta_btn", "CTA-Button Text", defaults['cta_btn']),
    ta("vsl_embed", "VSL Video-Embed (iframe/video)", ""),
    f_text("vsl_badge", "Video-Badge Text", "In 90 Sek. erklärt"),
    f_text("google_rating", "Google Bewertung", "4,9"),
    f_text("google_reviews", "Anzahl Google-Bewertungen", ""),
]
# --- Hero ---
fields += [f_tab("Hero"),
    f_text("hero_eyebrow", "Eyebrow", defaults['hero_eyebrow']),
    ta("hero_sub", "Subtext", defaults['hero_sub']),
]
# --- Problem ---
fields += [f_tab("Problem"),
    f_text("problem_h2", "Überschrift", defaults['problem_h2']),
    ta("problem_intro", "Intro", defaults['problem_intro']),
    f_text("prob1_t", "Karte 1 Titel", defaults['prob1_t']), ta("prob1_p", "Karte 1 Text", defaults['prob1_p']),
    f_text("prob2_t", "Karte 2 Titel", defaults['prob2_t']), ta("prob2_p", "Karte 2 Text", defaults['prob2_p']),
    f_text("prob3_t", "Karte 3 Titel", defaults['prob3_t']), ta("prob3_p", "Karte 3 Text", defaults['prob3_p']),
]
# --- Lösung ---
fields += [f_tab("Lösung"),
    f_text("sol_h2", "Überschrift", defaults['sol_h2']), ta("sol_intro", "Intro", defaults['sol_intro']),
    f_text("sol1_t", "Karte 1 Titel", defaults['sol1_t']), ta("sol1_p", "Karte 1 Text", defaults['sol1_p']),
    f_text("sol2_t", "Karte 2 Titel", defaults['sol2_t']), ta("sol2_p", "Karte 2 Text", defaults['sol2_p']),
    f_text("sol3_t", "Karte 3 Titel", defaults['sol3_t']), ta("sol3_p", "Karte 3 Text", defaults['sol3_p']),
]
# --- Vergleich ---
fields += [f_tab("Vergleich"),
    f_text("cmp_h2", "Überschrift", defaults['cmp_h2']), ta("cmp_intro", "Intro", defaults['cmp_intro']),
]
# --- Leistungen ---
fields += [f_tab("Leistungen"),
    f_text("feat_h2", "Überschrift", defaults['feat_h2']), ta("feat_intro", "Intro", defaults['feat_intro']),
    f_text("feat_accent_t", "Akzent-Karte Titel", defaults['feat_accent_t']), ta("feat_accent_p", "Akzent-Karte Text", defaults['feat_accent_p']),
    f_text("feat_tech_t", "Technik-Karte Titel", defaults['feat_tech_t']),
    f_text("feat_find_t", "Sichtbarkeit-Karte Titel", defaults['feat_find_t']),
]
# --- Ablauf ---
fields += [f_tab("Ablauf"),
    f_text("how_h2", "Überschrift", defaults['how_h2']), ta("how_intro", "Intro", defaults['how_intro']),
    f_text("step1_t", "Schritt 1 Titel", defaults['step1_t']), ta("step1_p", "Schritt 1 Text", defaults['step1_p']),
    f_text("step2_t", "Schritt 2 Titel", defaults['step2_t']), ta("step2_p", "Schritt 2 Text", defaults['step2_p']),
    f_text("step3_t", "Schritt 3 Titel", defaults['step3_t']), ta("step3_p", "Schritt 3 Text", defaults['step3_p']),
    f_text("step4_t", "Schritt 4 Titel", defaults['step4_t']), ta("step4_p", "Schritt 4 Text", defaults['step4_p']),
]
# --- Referenzen ---
fields += [f_tab("Referenzen"),
    f_text("ref_h2", "Überschrift", defaults['ref_h2']), ta("ref_intro", "Intro", defaults['ref_intro']),
    {"key": fk("references"), "label": "Referenz-Karten (leer = Standard-9)", "name": "references", "type": "repeater",
     "layout": "block", "button_label": "Referenz hinzufügen", "sub_fields": [
        f_text("image", "Bild", None, ftype="image", return_format="url", preview_size="medium"),
        f_text("name", "Name", None),
        f_text("branche", "Branche", None),
        f_text("geo", "Ort", None),
     ]},
]
# --- Kundenstimmen ---
fields += [f_tab("Kundenstimmen"),
    f_text("proof_h2", "Überschrift", defaults['proof_h2']),
    {"key": fk("testimonials"), "label": "Testimonials (leer = Platzhalter)", "name": "testimonials", "type": "repeater",
     "layout": "block", "button_label": "Testimonial hinzufügen", "sub_fields": [
        ta("quote", "Zitat", ""), f_text("name", "Name", None), f_text("firma", "Firma", None),
     ]},
]
# --- FAQ ---
fields += [f_tab("FAQ"),
    f_text("faq_h2", "Überschrift", defaults['faq_h2']),
    {"key": fk("faqs"), "label": "FAQ (leer = Standard)", "name": "faqs", "type": "repeater",
     "layout": "block", "button_label": "Frage hinzufügen", "sub_fields": [
        f_text("frage", "Frage", None), ta("antwort", "Antwort", ""),
     ]},
]
# --- CTA ---
fields += [f_tab("CTA"),
    f_text("cta_h2", "Überschrift", defaults['cta_h2']), ta("cta_p", "Text", defaults['cta_p']),
]
# --- Footer ---
fields += [f_tab("Footer"),
    f_text("footer_copy", "Copyright", defaults['footer_copy']),
]

group = {
    "key": "group_digi_lp",
    "title": "Performance Landingpage",
    "fields": fields,
    "location": [[{"param": "block", "operator": "==", "value": "digirelation/landingpage"}]],
    "menu_order": 0,
    "active": True,
}

php_out = "<?php\n// Auto-generiert. ACF-Felder für den Block digirelation/landingpage.\nif ( ! defined('ABSPATH') ) exit;\nadd_action('acf/init', function () {\n\tif ( ! function_exists('acf_add_local_field_group') ) return;\n\tacf_add_local_field_group(" + php(group, 2) + ");\n});\n"
open(PLUG + "/acf-fields.php", "w", encoding="utf-8").write(php_out)
print("acf-fields.php written, fields:", len(fields))
