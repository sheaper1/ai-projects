# -*- coding: utf-8 -*-
import os

HERE = os.path.dirname(__file__)
STRINGS = os.path.join(HERE, 'strings.txt')
PO = os.path.abspath(os.path.join(HERE, '..', 'der-flugschreiber-subscriptions',
                                  'languages', 'der-flugschreiber-subscriptions-de_DE.po'))

# English source string -> German translation.
TRANSLATIONS = {
    "A free PDF issue requires a PDF URL. The issue was kept as paid.": "Eine kostenlose PDF-Ausgabe benötigt eine PDF-URL. Die Ausgabe wurde als kostenpflichtig beibehalten.",
    "A user with this email already exists.": "Ein Benutzer mit dieser E-Mail-Adresse existiert bereits.",
    "Active": "Aktiv",
    "Active until %s": "Aktiv bis %s",
    "Add New Article": "Neuen Artikel hinzufügen",
    "Add New Magazine": "Neue Ausgabe hinzufügen",
    "All Articles": "Alle Artikel",
    "All Issues": "Alle Ausgaben",
    "All Posts": "Alle Beiträge",
    "Apply": "Anwenden",
    "Article access": "Artikel-Zugriff",
    "Article filters": "Artikel-Filter",
    "Article image URL": "Bild-URL des Artikels",
    "Article magazine requirement": "Ausgaben-Pflicht für Artikel",
    "Bulk action completed.": "Massenaktion abgeschlossen.",
    "Bulk actions": "Massenaktionen",
    "Cancel": "Kündigen",
    "Cancelled": "Gekündigt",
    "Change expiration date": "Ablaufdatum ändern",
    "Change password": "Passwort ändern",
    "Choose PDF": "PDF auswählen",
    "Choose a valid CSV file.": "Bitte eine gültige CSV-Datei auswählen.",
    "Choose image": "Bild auswählen",
    "Clear": "Zurücksetzen",
    "Columns: email, name, expires_at, status. Existing subscribers are updated by email.": "Spalten: email, name, expires_at, status. Bestehende Abonnenten werden anhand der E-Mail aktualisiert.",
    "Could not load issues. Please try again.": "Ausgaben konnten nicht geladen werden. Bitte erneut versuchen.",
    "Cover image URL": "Cover-Bild-URL",
    "Enter the URL of the subscriber account page. Guests will use the same page to log in.": "Tragen Sie die URL der Konto-Seite für Abonnenten ein. Gäste melden sich auf derselben Seite an.",
    "Create demo content": "Demo-Inhalte erstellen",
    "Create sample magazines, topic categories, issue years, and linked articles for testing the subscription architecture.": "Erstellt Beispiel-Ausgaben, Themen-Kategorien, Jahrgänge und verknüpfte Artikel zum Testen der Abo-Struktur.",
    "Create subscriber": "Abonnent erstellen",
    "DF Subscriber": "DF Abonnent",
    "DF Subscription": "DF Abonnement",
    "DF Subscriptions": "DF Abonnements",
    "Delete data on uninstall": "Daten bei Deinstallation löschen",
    "Delete the selected subscriber accounts permanently?": "Die ausgewählten Abonnenten-Konten endgültig löschen?",
    "Delete users": "Benutzer löschen",
    "Demo content": "Demo-Inhalte",
    "Demo content ready. Created %1$d magazines and %2$d articles. Existing matching demo titles were skipped.": "Demo-Inhalte bereit. %1$d Ausgaben und %2$d Artikel erstellt. Bereits vorhandene Demo-Titel wurden übersprungen.",
    "Demo issue preview for testing filters, subscriptions, and issue cards.": "Demo-Vorschau zum Testen von Filtern, Abos und Ausgaben-Karten.",
    "Der Flugschreiber Subscriptions": "Der Flugschreiber Subscriptions",
    "Display name": "Anzeigename",
    "Edit Article": "Artikel bearbeiten",
    "Edit Magazine": "Ausgabe bearbeiten",
    "Email": "E-Mail",
    "Email, name, or username": "E-Mail, Name oder Benutzername",
    "Expired on %s": "Abgelaufen am %s",
    "Expires at": "Läuft ab am",
    "Export subscribers as CSV": "Abonnenten als CSV exportieren",
    "Extend by 30 days": "Um 30 Tage verlängern",
    "Free article": "Kostenloser Artikel",
    "Go Back": "Zurück",
    "Import and export": "Import und Export",
    "Import subscribers from CSV": "Abonnenten aus CSV importieren",
    "Imported or updated %d subscribers.": "%d Abonnenten importiert oder aktualisiert.",
    "Incorrect email, username, or password. Please try again.": "E-Mail, Benutzername oder Passwort ist falsch. Bitte erneut versuchen.",
    "Inherit from issue": "Von der Ausgabe übernehmen",
    "Issue Date": "Ausgabe-Datum",
    "Issue Details": "Ausgabe-Details",
    "Issue Type": "Ausgabe-Typ",
    "Issue Year": "Jahrgang",
    "Issue Years": "Jahrgänge",
    "Issue date": "Ausgabe-Datum",
    "Issue filters": "Ausgaben-Filter",
    "Issue number": "Ausgabennummer",
    "Issue type": "Ausgabe-Typ",
    "Last change": "Letzte Änderung",
    "Log in": "Anmelden",
    "Log out": "Abmelden",
    "Log in to manage your subscription and account details.": "Melden Sie sich an, um Ihr Abonnement und Ihre Kontodaten zu verwalten.",
    "Manage your subscription and personal account details.": "Verwalten Sie Ihr Abonnement und Ihre persönlichen Kontodaten.",
    "My account": "Mein Konto",
    "Magazine": "Ausgabe",
    "Magazine Article": "Magazin-Artikel",
    "Magazine Articles": "Magazin-Artikel",
    "Magazine Issue": "Magazin-Ausgabe",
    "Magazine article": "Magazin-Artikel",
    "Magazine issue": "Magazin-Ausgabe",
    "Magazines": "Ausgaben",
    "My subscription": "Mein Abonnement",
    "Name": "Name",
    "New paid": "Neu (kostenpflichtig)",
    "New paid issue": "Neue kostenpflichtige Ausgabe",
    "Next": "Weiter",
    "No expiration date": "Kein Ablaufdatum",
    "No history": "Kein Verlauf",
    "No magazine selected": "Keine Ausgabe ausgewählt",
    "No matching subscribers found.": "Keine passenden Abonnenten gefunden.",
    "No subscribers yet.": "Noch keine Abonnenten.",
    "Not set": "Nicht festgelegt",
    "Old free PDF": "Alt (kostenloses PDF)",
    "Old free PDF issue": "Alte kostenlose PDF-Ausgabe",
    "Old free PDF issues can redirect directly to this PDF when opened.": "Alte kostenlose PDF-Ausgaben können beim Öffnen direkt zu dieser PDF weiterleiten.",
    "Only valid PDF files can be uploaded as protected issues.": "Als geschützte Ausgaben können nur gültige PDF-Dateien hochgeladen werden.",
    "Open PDF": "PDF öffnen",
    "PDF": "PDF",
    "PDF URL for old issue": "PDF-URL für alte Ausgabe",
    "Paid article": "Kostenpflichtiger Artikel",
    "Password": "Passwort",
    "Pause": "Pausieren",
    "Paused": "Pausiert",
    "Profile": "Profil",
    "Payment page URL": "URL der Bezahlseite",
    "Permanently delete plugin content, settings, and subscriber metadata when the plugin is deleted.": "Plugin-Inhalte, Einstellungen und Abonnenten-Metadaten beim Löschen des Plugins endgültig entfernen.",
    "Please enter a password and a valid expiration date.": "Bitte ein Passwort und ein gültiges Ablaufdatum eingeben.",
    "Please enter a valid email address.": "Bitte eine gültige E-Mail-Adresse eingeben.",
    "Please enter a valid expiration date.": "Bitte ein gültiges Ablaufdatum eingeben.",
    "Please enter your email or username and password.": "Bitte E-Mail oder Benutzername und Passwort eingeben.",
    "Please log in to view your subscription.": "Bitte melden Sie sich an, um Ihr Abonnement zu sehen.",
    "Post filters": "Beitrags-Filter",
    "Preview length in words": "Vorschaulänge in Wörtern",
    "Previous": "Zurück",
    "Protected PDF for paid issue": "Geschützte PDF für kostenpflichtige Ausgabe",
    "Purchase subscription": "Abonnement kaufen",
    "Read the full article": "Vollständigen Artikel lesen",
    "Remove protected PDF": "Geschützte PDF entfernen",
    "Renew subscription": "Abonnement verlängern",
    "Require every magazine article to be linked to an issue.": "Jeden Magazin-Artikel verpflichtend mit einer Ausgabe verknüpfen.",
    "Save": "Speichern",
    "Save profile": "Profil speichern",
    "Save settings": "Einstellungen speichern",
    "Search subscribers": "Abonnenten suchen",
    "Security check failed.": "Sicherheitsprüfung fehlgeschlagen.",
    "Select %s": "%s auswählen",
    "Select all subscribers": "Alle Abonnenten auswählen",
    "Select magazine issue": "Ausgabe auswählen",
    "Select subscribers and a valid bulk action.": "Bitte Abonnenten und eine gültige Massenaktion auswählen.",
    "Send welcome, expiration reminder, and expired subscription emails.": "Willkommens-, Erinnerungs- und Ablauf-E-Mails senden.",
    "Set active": "Auf aktiv setzen",
    "Settings": "Einstellungen",
    "Settings saved.": "Einstellungen gespeichert.",
    "Shortcode": "Shortcode",
    "Status": "Status",
    "Subscriber created.": "Abonnent erstellt.",
    "Subscriber login page URL": "URL der Login-Seite für Abonnenten",
    "Subscriber area": "Abonnentenbereich",
    "Subscribers": "Abonnenten",
    "Subscription emails": "Abo-E-Mails",
    "Subscription updated.": "Abonnement aktualisiert.",
    "System": "System",
    "The CSV columns are invalid.": "Die CSV-Spalten sind ungültig.",
    "The PDF file could not be found.": "Die PDF-Datei konnte nicht gefunden werden.",
    "The article was saved as a draft because no magazine issue was selected.": "Der Artikel wurde als Entwurf gespeichert, da keine Ausgabe ausgewählt wurde.",
    "The download link is invalid or expired.": "Der Download-Link ist ungültig oder abgelaufen.",
    "The login form expired. Please try again.": "Das Anmeldeformular ist abgelaufen. Bitte erneut versuchen.",
    "The profile could not be updated.": "Das Profil konnte nicht aktualisiert werden.",
    "The protected PDF could not be saved.": "Die geschützte PDF konnte nicht gespeichert werden.",
    "The protected PDF directory could not be created.": "Das Verzeichnis für geschützte PDFs konnte nicht erstellt werden.",
    "The selected user is not a subscriber.": "Der ausgewählte Benutzer ist kein Abonnent.",
    "This is demo issue content. New paid issues are protected by the subscription gate; old PDF issues can redirect directly to the PDF URL.": "Dies ist ein Demo-Ausgabeninhalt. Neue kostenpflichtige Ausgaben sind durch die Abo-Sperre geschützt; alte PDF-Ausgaben können direkt zur PDF-URL weiterleiten.",
    "This is the full demo article text. It should only be visible to administrators and subscribers with an active subscription date.": "Dies ist der vollständige Demo-Artikeltext. Er sollte nur für Administratoren und Abonnenten mit aktivem Abo sichtbar sein.",
    "This public excerpt is visible to visitors without an active subscription.": "Dieser öffentliche Auszug ist für Besucher ohne aktives Abo sichtbar.",
    "To read the full text, please log in or purchase a subscription.": "Um den vollständigen Text zu lesen, melden Sie sich bitte an oder kaufen Sie ein Abonnement.",
    "Topic Categories": "Themen-Kategorien",
    "Topic Category": "Themen-Kategorie",
    "Trial": "Testzugang",
    "User": "Benutzer",
    "Visitors without active subscription will see this link below the article preview.": "Besucher ohne aktives Abo sehen diesen Link unterhalb der Artikel-Vorschau.",
    "You do not have access to this PDF.": "Sie haben keinen Zugriff auf diese PDF.",
    "Your Der Flugschreiber subscription account": "Ihr Der-Flugschreiber-Abonnentenkonto",
    "Your Der Flugschreiber subscription expired on %s. Use the subscription page to renew it.": "Ihr Der-Flugschreiber-Abonnement ist am %s abgelaufen. Verlängern Sie es über die Abo-Seite.",
    "Your Der Flugschreiber subscription is active until %s. Use the subscription page to renew it.": "Ihr Der-Flugschreiber-Abonnement ist bis %s aktiv. Verlängern Sie es über die Abo-Seite.",
    "Your profile was updated.": "Ihr Profil wurde aktualisiert.",
    "Your subscriber account is ready. You can log in here: %s": "Ihr Abonnentenkonto ist bereit. Sie können sich hier anmelden: %s",
    "Your subscription expires soon": "Ihr Abonnement läuft bald ab",
    "Your subscription has expired": "Ihr Abonnement ist abgelaufen",
}

# German-source strings (UI written directly in German). msgstr == msgid (identity).
# Listed explicitly so the script can assert nothing English slips through untranslated.
GERMAN_IDENTITY = {
    "Abonnenten anlegen", "Alle Artikel", "Alle Ausgaben",
    "Alle Felder sind optional — leere Felder verwenden den Standardtext (grau angezeigt).",
    "Alle sichtbaren Texte (Überschriften, Buttons, Hinweise) änderst du unter %s. Leere Felder verwenden den Standardtext.",
    "Anleitung", "Anleitung — Magazin einrichten",
    "Arbeite diese Schritte einmal von oben nach unten ab. Klicke einen Shortcode an, um ihn zu kopieren.",
    "Artikel dieser Ausgabe", "Artikel → Neu", "Ausgaben und Artikel erstellen", "Ausgaben → Neu",
    "Bezahlseite (Payment page URL):", "Blog", "Der Flugschreiber",
    "Die öffentliche PDF-URL ist nur für alte, kostenlose Ausgaben gedacht. Bezahlte Ausgaben nutzen den geschützten PDF-Upload.",
    "DF Subscriptions → Einstellungen",
    "Entdecken Sie aktuelle und vergangene Ausgaben des Flugschreibers. Neue Ausgaben sind Teil des Abonnements, ältere Ausgaben stehen teilweise als freie PDF-Dateien bereit.",
    "Erschienen im %s",
    "Erstelle Abonnenten von Hand unter %s mit E-Mail, Passwort und Ablaufdatum — oder importiere sie als CSV (Spalten: email, name, expires_at, status). Über Massenaktionen kannst du Abos pausieren, verlängern oder beenden.",
    "Erstelle eine Seite mit %s und kopiere ihre URL in Schritt 1 (Login-Seite).",
    "Erstelle eine Seite mit %s, damit Abonnenten ihren Status und ihr Ablaufdatum sehen.",
    "Für automatische E-Mails (Willkommen, Erinnerung, Ablauf) muss WordPress-Cron laufen.",
    "Grundeinstellungen festlegen", "Gut zu wissen",
    "Hier legst du außerdem fest, ob E-Mails verschickt werden und ob Daten beim Löschen des Plugins erhalten bleiben.",
    "Ihre Vorteile", "Inhalte",
    "Lege für jeden Bereich eine %s an und füge genau einen Shortcode in den Inhalt ein. Der Shortcode baut das fertige Layout automatisch.",
    "Lege unter %s eine Ausgabe an. Wähle den Zugriff: „bezahlt“ (nur Abonnenten) oder „kostenloses PDF“ (frei). Setze Cover, Ausgabennummer und Datum.",
    "Lege unter %s einen Artikel an und verknüpfe ihn mit seiner Ausgabe. Der öffentliche Auszug ist für alle sichtbar, der volle Text nur für aktive Abonnenten.",
    "Login- und Konto-Seite verbinden", "Login-Seite (Subscriber login page URL):",
    "Optional für einen Blog mit normalen WordPress-Beiträgen:",
    "Reportagen, Fachwissen und persönliche Geschichten aus der Welt der Luftfahrt - geschrieben von Piloten und Flugbegeisterten.",
    "Seiten anlegen und Shortcodes einfügen", "Texte anpassen und testen", "Texte gespeichert.",
    "Website-Texte", "Wofür",
    "Zahlungen laufen extern: Das Plugin verarbeitet kein Geld, es leitet nur zur Bezahlseite weiter.",
    "Zum Ausprobieren erzeugst du unter %s Demo-Ausgaben und -Artikel. Öffne die Seiten danach in einem privaten Browserfenster (abgemeldet), um die Besucher-Ansicht zu prüfen.",
    "die Adresse deiner Anmelde-Seite aus Schritt 3.",
    "die externe Seite, auf der das Abo gekauft wird. Besucher ohne aktives Abo werden dorthin geleitet.",
    "kopiert", "neue WordPress-Seite", "Öffne %s und trage zwei Adressen ein:",
}


def esc(s):
    return s.replace('\\', '\\\\').replace('"', '\\"').replace('\n', '\\n')


def main():
    sources = [l.rstrip('\n') for l in open(STRINGS, encoding='utf-8') if l.strip()]
    missing = []
    entries = []
    for s in sources:
        if s in TRANSLATIONS:
            entries.append((s, TRANSLATIONS[s]))
        elif s in GERMAN_IDENTITY:
            entries.append((s, s))
        else:
            missing.append(s)

    if missing:
        print("!!! UNCLASSIFIED (neither translated nor German-identity):")
        for m in missing:
            print("   ", m)
        raise SystemExit(1)

    entries.sort(key=lambda e: e[0])
    lines = []
    lines.append('msgid ""')
    lines.append('msgstr ""')
    lines.append('"Project-Id-Version: Der Flugschreiber Subscriptions 1.4.0\\n"')
    lines.append('"Language: de_DE\\n"')
    lines.append('"Content-Type: text/plain; charset=UTF-8\\n"')
    lines.append('"Content-Transfer-Encoding: 8bit\\n"')
    lines.append('')
    for src, tgt in entries:
        lines.append('msgid "%s"' % esc(src))
        lines.append('msgstr "%s"' % esc(tgt))
        lines.append('')
    open(PO, 'w', encoding='utf-8', newline='\n').write('\n'.join(lines))
    print("OK: wrote %d entries to %s" % (len(entries), PO))


if __name__ == '__main__':
    main()
