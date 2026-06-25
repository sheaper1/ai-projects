<?php
defined( 'ABSPATH' ) || exit;

class Propstack_RE_Admin_Docs {

    public function init(): void {
        add_action( 'admin_menu', [ $this, 'register_page' ] );
    }

    public function register_page(): void {
        add_submenu_page(
            'propstack-re',
            __( 'Dokumentation', 'propstack-re' ),
            __( 'Dokumentation', 'propstack-re' ),
            'manage_options',
            'propstack-re-docs',
            [ $this, 'render' ]
        );
    }

    public function render(): void {
        $webhook_url = rest_url( 'propstack/v1/webhook' );
        $cpt_slug    = get_option( 'propstack_re_cpt_slug', 'immobilien' );
        ?>
        <div class="wrap propstack-re-admin propstack-re-docs-page">
            <h1>Propstack Real Estate Sync — Dokumentation</h1>

            <nav class="propstack-docs-nav">
                <a href="#einrichtung">Einrichtung</a>
                <a href="#sync">Synchronisierung</a>
                <a href="#shortcodes">Shortcodes</a>
                <a href="#template-override">Template-Overrides</a>
                <a href="#php-funktionen">PHP-Funktionen</a>
                <a href="#meta-felder">Meta-Felder</a>
                <a href="#taxonomien">Taxonomien</a>
                <a href="#seo">SEO</a>
                <a href="#webhook">Webhook</a>
                <a href="#changelog">Changelog</a>
            </nav>

            <div class="propstack-docs-body">

                <?php /* =========================================================
                   EINRICHTUNG
                ========================================================= */ ?>
                <section id="einrichtung">
                    <h2>Einrichtung</h2>

                    <h3>1. API-Key eingeben</h3>
                    <p>
                        Im Propstack-Backend unter <strong>Einstellungen → API</strong> einen API-Key mit folgenden
                        Berechtigungen erstellen:
                    </p>
                    <ul>
                        <li>Units: Lesen</li>
                        <li>Contacts: Lesen + Schreiben (für Lead-Formular)</li>
                        <li>Activities: Schreiben (für Lead-Formular)</li>
                        <li>Webhooks: Lesen + Schreiben (optional)</li>
                    </ul>
                    <p>
                        Den Key unter <a href="<?php echo esc_url( admin_url( 'admin.php?page=propstack-re-api' ) ); ?>">
                        Propstack → API Verbindung</a> eintragen und mit <em>Verbindung testen</em> prüfen.
                    </p>

                    <h3>2. CPT-Slug festlegen</h3>
                    <p>
                        Unter <a href="<?php echo esc_url( admin_url( 'admin.php?page=propstack-re-display' ) ); ?>">
                        Darstellung</a> kann der URL-Slug für Immobilien geändert werden
                        (Standard: <code><?php echo esc_html( $cpt_slug ); ?></code>).
                        Nach einer Änderung Permalinks unter <em>Einstellungen → Permalinks</em> neu speichern.
                    </p>

                    <h3>3. Erste Synchronisierung</h3>
                    <p>
                        Auf dem <a href="<?php echo esc_url( admin_url( 'admin.php?page=propstack-re' ) ); ?>">Dashboard</a>
                        auf <strong>Jetzt synchronisieren</strong> klicken. Das Plugin ruft alle Objekte von
                        <code>https://api.propstack.de/v1/units</code> ab.
                    </p>

                    <div class="propstack-docs-info">
                        <strong>Hinweis:</strong> Objekte werden nur als <em>Veröffentlicht</em> angelegt, wenn
                        ihr Status-Name "Vermarktung", "reserviert" oder "aktiv" enthält — oder wenn die
                        Status-IDs unter <a href="<?php echo esc_url( admin_url( 'admin.php?page=propstack-re-sync' ) ); ?>">
                        Sync → Öffentliche Status</a> konfiguriert sind.
                    </div>
                </section>

                <?php /* =========================================================
                   SYNC
                ========================================================= */ ?>
                <section id="sync">
                    <h2>Synchronisierung</h2>

                    <h3>Manueller Sync</h3>
                    <p>
                        Dashboard → <strong>Jetzt synchronisieren</strong>: Nur geänderte Objekte (Hash-Vergleich).<br>
                        Dashboard → <strong>Kompletter Re-Sync</strong>: Alle Objekte werden neu geschrieben (ignoriert Hash).
                    </p>

                    <h3>Automatischer Cron</h3>
                    <p>
                        Unter <a href="<?php echo esc_url( admin_url( 'admin.php?page=propstack-re-sync' ) ); ?>">Sync</a>
                        kann das Intervall gewählt werden:
                    </p>
                    <table class="propstack-re-stats">
                        <thead><tr><th>Wert</th><th>Intervall</th></tr></thead>
                        <tbody>
                            <tr><td><code>fifteen_min</code></td><td>15 Minuten</td></tr>
                            <tr><td><code>hourly</code></td><td>stündlich</td></tr>
                            <tr><td><code>twicedaily</code></td><td>zweimal täglich</td></tr>
                            <tr><td><code>daily</code></td><td>täglich</td></tr>
                        </tbody>
                    </table>
                    <p class="propstack-docs-hint">WP Cron läuft nur bei Website-Besuchern. Für zuverlässigen Cron einen
                    echten Server-Cron einrichten: <code>*/15 * * * * wget -q -O - https://example.com/wp-cron.php?doing_wp_cron</code></p>

                    <h3>Inaktive Objekte</h3>
                    <p>
                        Objekte, die in Propstack nicht mehr vorhanden sind, werden automatisch auf
                        <em>Entwurf</em> oder <em>Papierkorb</em> gesetzt — konfigurierbar unter
                        Sync → Inaktive Objekte.
                    </p>

                    <h3>Bilder</h3>
                    <p>
                        Modus <strong>Importieren</strong> (Standard): Bilder werden in die WordPress-Mediathek
                        heruntergeladen. Duplikate werden anhand der Quell-URL erkannt und übersprungen.<br>
                        Modus <strong>Extern verlinken</strong>: Bilder werden direkt von Propstack-Servern geladen
                        (kein lokaler Speicher, aber von CDN-Verfügbarkeit abhängig).
                    </p>
                </section>

                <?php /* =========================================================
                   SHORTCODES
                ========================================================= */ ?>
                <section id="shortcodes">
                    <h2>Shortcodes</h2>

                    <h3><code>[propstack_listing]</code></h3>
                    <p>Zeigt eine gefilterte, paginierte Übersichtsliste aller Immobilien.</p>

                    <table class="propstack-re-stats">
                        <thead><tr><th>Attribut</th><th>Standard</th><th>Beschreibung</th></tr></thead>
                        <tbody>
                            <tr><td><code>limit</code></td><td><code>12</code></td><td>Anzahl Objekte pro Seite</td></tr>
                            <tr><td><code>layout</code></td><td><code>grid</code></td><td><code>grid</code> oder <code>list</code></td></tr>
                            <tr><td><code>columns</code></td><td><code>3</code></td><td>Spalten bei Grid-Layout (<code>2</code>–<code>4</code>)</td></tr>
                            <tr><td><code>show_filters</code></td><td><code>true</code></td><td>Filter-Bar anzeigen (<code>true</code>/<code>false</code>)</td></tr>
                            <tr><td><code>type</code></td><td>—</td><td>Slug einer Immobilien-Art (Taxonomy <code>property_type</code>)</td></tr>
                            <tr><td><code>city</code></td><td>—</td><td>Slug einer Stadt (Taxonomy <code>property_city</code>)</td></tr>
                            <tr><td><code>region</code></td><td>—</td><td>Slug einer Region (Taxonomy <code>property_region</code>)</td></tr>
                            <tr><td><code>marketing</code></td><td>—</td><td><code>buy</code> oder <code>rent</code></td></tr>
                            <tr><td><code>status</code></td><td>—</td><td>Slug eines Status-Terms</td></tr>
                            <tr><td><code>project</code></td><td>—</td><td>Slug eines Projekts</td></tr>
                            <tr><td><code>orderby</code></td><td><code>date</code></td><td>WP-Orderby-Wert (<code>date</code>, <code>title</code>, <code>menu_order</code>)</td></tr>
                            <tr><td><code>order</code></td><td><code>DESC</code></td><td><code>ASC</code> oder <code>DESC</code></td></tr>
                        </tbody>
                    </table>

                    <p><strong>URL-Parameter</strong> (aus GET) überschreiben die Shortcode-Attribute und werden von der Filter-Bar gesetzt:</p>
                    <table class="propstack-re-stats">
                        <thead><tr><th>Parameter</th><th>Beschreibung</th></tr></thead>
                        <tbody>
                            <tr><td><code>?type=</code></td><td>Filter nach Immobilien-Art</td></tr>
                            <tr><td><code>?city=</code></td><td>Filter nach Stadt</td></tr>
                            <tr><td><code>?region=</code></td><td>Filter nach Region</td></tr>
                            <tr><td><code>?marketing=</code></td><td>Filter nach Vermarktungsart</td></tr>
                            <tr><td><code>?price_min=</code> / <code>?price_max=</code></td><td>Preisbereich</td></tr>
                            <tr><td><code>?area_min=</code></td><td>Mindest-Wohnfläche (m²)</td></tr>
                            <tr><td><code>?rooms_min=</code></td><td>Mindestzimmeranzahl</td></tr>
                            <tr><td><code>?sort=price_asc</code> / <code>price_desc</code> / <code>area_desc</code></td><td>Sortierung</td></tr>
                        </tbody>
                    </table>

                    <div class="propstack-docs-example">
                        <code>[propstack_listing limit="6" marketing="rent" layout="list" show_filters="false"]</code><br>
                        <code>[propstack_listing city="wien" columns="2" orderby="title" order="ASC"]</code>
                    </div>

                    <h3><code>[propstack_filters]</code></h3>
                    <p>
                        Gibt nur die Filter-Bar aus — nützlich wenn die Filter z.B. in der Sidebar stehen sollen
                        und die Liste separat via <code>[propstack_listing show_filters="false"]</code> eingebunden wird.
                    </p>
                    <div class="propstack-docs-example">
                        <code>[propstack_filters]</code>
                    </div>

                    <h3><code>[propstack_property id="…"]</code></h3>
                    <p>
                        Zeigt die Detailansicht eines einzelnen Objekts. <code>id</code> kann entweder die
                        WordPress-Post-ID oder die Propstack-ID sein.
                    </p>
                    <div class="propstack-docs-example">
                        <code>[propstack_property id="5284867"]</code> &nbsp;← Propstack-ID<br>
                        <code>[propstack_property id="42"]</code> &nbsp;← WordPress-Post-ID
                    </div>

                    <h3><code>[propstack_contact_form property_id="…"]</code></h3>
                    <p>
                        Gibt das Kontaktformular für ein Objekt aus. Ohne <code>property_id</code> wird
                        automatisch der aktuelle Post verwendet (z.B. auf der Detailseite).
                        Das Formular sendet eine DSGVO-konforme Anfrage und legt in Propstack automatisch
                        einen Lead (Kontakt + Aktivität) an.
                    </p>
                    <div class="propstack-docs-example">
                        <code>[propstack_contact_form property_id="5284867"]</code><br>
                        <code>[propstack_contact_form]</code> &nbsp;← auf Detailseite (ID wird automatisch ermittelt)
                    </div>
                </section>

                <?php /* =========================================================
                   TEMPLATE-OVERRIDES
                ========================================================= */ ?>
                <section id="template-override">
                    <h2>Template-Overrides</h2>
                    <p>
                        Templates können im aktiven Theme überschrieben werden — nach dem WooCommerce-Muster.
                        Das Plugin sucht in folgender Reihenfolge:
                    </p>
                    <ol>
                        <li><code>wp-content/themes/<em>mytheme</em>/propstack/{template}.php</code></li>
                        <li><code>wp-content/themes/<em>parent-theme</em>/propstack/{template}.php</code></li>
                        <li><code>wp-content/plugins/propstack-real-estate/templates/{template}.php</code> (Plugin-Fallback)</li>
                    </ol>

                    <h3>Verfügbare Templates</h3>
                    <table class="propstack-re-stats">
                        <thead><tr><th>Datei</th><th>Beschreibung</th></tr></thead>
                        <tbody>
                            <tr><td><code>listing.php</code></td><td>Übersichtsliste (Grid/List-Container + Pagination)</td></tr>
                            <tr><td><code>card.php</code></td><td>Einzelne Kachel in der Übersicht</td></tr>
                            <tr><td><code>filters.php</code></td><td>Filter-Bar</td></tr>
                            <tr><td><code>single-property.php</code></td><td>Detailseite</td></tr>
                            <tr><td><code>contact-form.php</code></td><td>Kontaktformular</td></tr>
                            <tr><td><code>partials/gallery.php</code></td><td>Bildergalerie (Lightbox-fähig)</td></tr>
                            <tr><td><code>partials/facts.php</code></td><td>Eckdaten (Zimmer, Fläche, Etage …)</td></tr>
                            <tr><td><code>partials/price.php</code></td><td>Preisdarstellung</td></tr>
                            <tr><td><code>partials/address.php</code></td><td>Adresse + Google Maps-Link</td></tr>
                            <tr><td><code>partials/energy.php</code></td><td>Energieausweis-Daten</td></tr>
                            <tr><td><code>partials/broker.php</code></td><td>Makler-Kontaktbox</td></tr>
                            <tr><td><code>partials/description.php</code></td><td>Beschreibungstexte</td></tr>
                        </tbody>
                    </table>

                    <p>
                        Beispiel — eigene Kachel anlegen:<br>
                        Datei kopieren von<br>
                        <code>plugins/propstack-real-estate/templates/card.php</code><br>
                        nach<br>
                        <code>themes/mytheme/propstack/card.php</code><br>
                        und anpassen. Das Plugin erkennt die Theme-Datei automatisch.
                    </p>

                    <h3>Verfügbare Variablen in Templates</h3>
                    <p>
                        Alle Templates erhalten ihre Variablen via <code>extract()</code>.
                        In <code>listing.php</code> stehen u.a. <code>$query</code> (WP_Query), <code>$atts</code>,
                        <code>$layout</code>, <code>$columns</code> zur Verfügung.<br>
                        In <code>single-property.php</code> und <code>contact-form.php</code> steht <code>$property_id</code>
                        zur Verfügung. Alle Objekt-Meta-Daten können über
                        <code>propstack_get_property( $property_id )</code> abgerufen werden.
                    </p>
                </section>

                <?php /* =========================================================
                   PHP-FUNKTIONEN
                ========================================================= */ ?>
                <section id="php-funktionen">
                    <h2>PHP-Funktionen (für Entwickler)</h2>

                    <h3><code>propstack_get_property( int $post_id ): array</code></h3>
                    <p>
                        Gibt alle Meta-Felder eines Objekts als assoziatives Array zurück.
                        Keys sind ohne führenden Unterstrich (z.B. <code>property_price</code> statt <code>_property_price</code>).
                        <code>property_gallery</code> und <code>property_features</code> werden automatisch deserialisiert.
                    </p>
                    <div class="propstack-docs-example">
<pre>$prop = propstack_get_property( get_the_ID() );
echo $prop['property_price_display'];   // z.B. "450.000 €"
echo $prop['property_city'];            // z.B. "Wien"
echo $prop['propstack_id'];             // Propstack-Objekt-ID</pre>
                    </div>

                    <h3><code>propstack_format_price( float|int|null $price, string $currency = '€' ): string</code></h3>
                    <p>Formatiert einen Preis mit Tausender-Punkt und Währungssymbol. Gibt <em>Auf Anfrage</em> zurück wenn null/0.</p>
                    <div class="propstack-docs-example">
                        <code>echo propstack_format_price( 450000 );        // "450.000 €"</code><br>
                        <code>echo propstack_format_price( 1200, 'CHF' );   // "1.200 CHF"</code>
                    </div>

                    <h3><code>propstack_format_area( float|int|null $area, string $unit = 'm²' ): string</code></h3>
                    <p>Formatiert eine Fläche mit zwei Dezimalstellen.</p>
                    <div class="propstack-docs-example">
                        <code>echo propstack_format_area( 87.5 );   // "87,50 m²"</code>
                    </div>

                    <h3><code>propstack_get_status_label( string|int $status ): string</code></h3>
                    <p>Gibt ein lokalisiertes Label für einen Propstack-Status zurück.</p>
                    <div class="propstack-docs-example">
                        <code>echo propstack_get_status_label( 'vermarktung' );   // "Verfügbar"</code><br>
                        <code>echo propstack_get_status_label( 'reserviert' );    // "Reserviert"</code>
                    </div>

                    <h3><code>propstack_get_marketing_type_label( string $type ): string</code></h3>
                    <div class="propstack-docs-example">
                        <code>echo propstack_get_marketing_type_label( 'BUY' );   // "Kaufen"</code><br>
                        <code>echo propstack_get_marketing_type_label( 'RENT' );  // "Mieten"</code>
                    </div>

                    <h3><code>propstack_render_listing( array $args = [] ): string</code></h3>
                    <p>Rendert die Übersichtsliste und gibt den HTML-String zurück. Akzeptiert dieselben Args wie die WP_Query.</p>

                    <h3><code>propstack_render_property_detail( int $property_id ): string</code></h3>
                    <p>Rendert die Detailansicht eines Objekts.</p>

                    <h3><code>propstack_render_contact_form( ?int $property_id = null ): string</code></h3>
                    <p>Rendert das Kontaktformular. Ohne <code>$property_id</code> wird das Formular ohne Objektbezug ausgegeben.</p>

                    <h3>Template-Loader direkt nutzen</h3>
                    <div class="propstack-docs-example">
<pre>// Template direkt ausgeben
Propstack_RE_Template_Loader::get_template( 'card.php', [
    'property_id' => 42,
] );

// Template als String
$html = Propstack_RE_Template_Loader::get_template_html( 'partials/price.php', [
    'property_id' => 42,
] );

// Partial ausgeben
Propstack_RE_Template_Loader::get_partial( 'gallery.php', [
    'property_id' => 42,
] );</pre>
                    </div>
                </section>

                <?php /* =========================================================
                   META-FELDER
                ========================================================= */ ?>
                <section id="meta-felder">
                    <h2>Meta-Felder</h2>
                    <p>Alle Felder sind als WordPress-Post-Meta gespeichert. Zugriff via
                    <code>get_post_meta( $post_id, '_property_price', true )</code>
                    oder bequemer über <code>propstack_get_property()</code>.</p>

                    <h3>Propstack-Kerndaten</h3>
                    <table class="propstack-re-stats">
                        <thead><tr><th>Meta-Key</th><th>Beschreibung</th><th>Propstack-Feld</th></tr></thead>
                        <tbody>
                            <tr><td><code>_propstack_id</code></td><td>Propstack Unit-ID</td><td><code>id</code></td></tr>
                            <tr><td><code>_propstack_status</code></td><td>Status-Name (z.B. "Vermarktung")</td><td><code>status.name</code></td></tr>
                            <tr><td><code>_propstack_status_id</code></td><td>Status-ID</td><td><code>status.id</code></td></tr>
                            <tr><td><code>_propstack_last_sync</code></td><td>Zeitpunkt der letzten Synchronisierung</td><td>—</td></tr>
                            <tr><td><code>_propstack_last_hash</code></td><td>Hash für Änderungsvergleich</td><td>—</td></tr>
                        </tbody>
                    </table>

                    <h3>Preise</h3>
                    <table class="propstack-re-stats">
                        <thead><tr><th>Meta-Key</th><th>Beschreibung</th><th>Propstack-Feld</th></tr></thead>
                        <tbody>
                            <tr><td><code>_property_price</code></td><td>Kaufpreis oder Miete (float)</td><td><code>price</code> / <code>base_rent</code></td></tr>
                            <tr><td><code>_property_price_display</code></td><td>Formatierter Preis-String</td><td>berechnet</td></tr>
                            <tr><td><code>_property_price_on_request</code></td><td>"1" wenn Preis auf Anfrage</td><td><code>price_on_request</code></td></tr>
                            <tr><td><code>_property_price_brutto</code></td><td>Kaufpreis (brutto)</td><td><code>price</code></td></tr>
                            <tr><td><code>_property_price_per_sqm</code></td><td>Preis pro m²</td><td><code>property_rent_per_sqm_from_value</code></td></tr>
                            <tr><td><code>_property_rent_gross</code></td><td>Bruttomiete</td><td><code>base_rent</code></td></tr>
                            <tr><td><code>_property_operating_costs</code></td><td>Betriebskosten</td><td><code>property_additional_costs_per_sqm_from_value</code></td></tr>
                            <tr><td><code>_property_currency</code></td><td>Währung (z.B. "EUR")</td><td><code>currency</code></td></tr>
                        </tbody>
                    </table>

                    <h3>Flächen &amp; Kennzahlen</h3>
                    <table class="propstack-re-stats">
                        <thead><tr><th>Meta-Key</th><th>Beschreibung</th><th>Propstack-Feld</th></tr></thead>
                        <tbody>
                            <tr><td><code>_property_living_area</code></td><td>Wohnfläche in m²</td><td><code>living_space</code></td></tr>
                            <tr><td><code>_property_plot_area</code></td><td>Grundstücksfläche in m²</td><td><code>property_space_value</code></td></tr>
                            <tr><td><code>_property_usable_area</code></td><td>Nutzfläche in m²</td><td><code>usable_area</code></td></tr>
                            <tr><td><code>_property_rooms</code></td><td>Zimmeranzahl</td><td><code>number_of_rooms</code></td></tr>
                            <tr><td><code>_property_bedrooms</code></td><td>Schlafzimmer</td><td><code>number_of_bed_rooms</code></td></tr>
                            <tr><td><code>_property_bathrooms</code></td><td>Badezimmer</td><td><code>number_of_bath_rooms</code></td></tr>
                            <tr><td><code>_property_toilets</code></td><td>WC</td><td><code>number_of_toilets</code></td></tr>
                            <tr><td><code>_property_floor</code></td><td>Stockwerk</td><td><code>floor</code></td></tr>
                            <tr><td><code>_property_available_from</code></td><td>Verfügbar ab</td><td><code>free_from</code></td></tr>
                            <tr><td><code>_property_object_number</code></td><td>Objektnummer</td><td><code>unit_id</code></td></tr>
                            <tr><td><code>_property_rented</code></td><td>"1" wenn vermietet</td><td><code>rented</code></td></tr>
                        </tbody>
                    </table>

                    <h3>Adresse</h3>
                    <table class="propstack-re-stats">
                        <thead><tr><th>Meta-Key</th><th>Propstack-Feld</th></tr></thead>
                        <tbody>
                            <tr><td><code>_property_street</code></td><td><code>street</code></td></tr>
                            <tr><td><code>_property_house_number</code></td><td><code>house_number</code></td></tr>
                            <tr><td><code>_property_city</code></td><td><code>city</code></td></tr>
                            <tr><td><code>_property_zip</code></td><td><code>zip_code</code></td></tr>
                            <tr><td><code>_property_region</code></td><td><code>region</code> / <code>district</code></td></tr>
                            <tr><td><code>_property_country</code></td><td><code>country</code> (ISO 3, z.B. "AUT")</td></tr>
                            <tr><td><code>_property_lat</code></td><td><code>lat</code></td></tr>
                            <tr><td><code>_property_lng</code></td><td><code>lng</code></td></tr>
                        </tbody>
                    </table>

                    <h3>Bilder</h3>
                    <table class="propstack-re-stats">
                        <thead><tr><th>Meta-Key</th><th>Beschreibung</th></tr></thead>
                        <tbody>
                            <tr><td><code>_property_gallery</code></td><td>Serialisiertes Array mit Bild-Objekten (<code>id</code>, <code>url</code>, <code>thumb</code>, <code>title</code>, <code>sort</code>)</td></tr>
                            <tr><td><code>_property_featured_image_url</code></td><td>URL des Hauptbilds (Propstack-Remote-URL als Fallback)</td></tr>
                        </tbody>
                    </table>

                    <h3>Makler / Kontaktperson</h3>
                    <table class="propstack-re-stats">
                        <thead><tr><th>Meta-Key</th><th>Beschreibung</th></tr></thead>
                        <tbody>
                            <tr><td><code>_property_contact_name</code></td><td>Vor- und Nachname</td></tr>
                            <tr><td><code>_property_contact_email</code></td><td>E-Mail-Adresse</td></tr>
                            <tr><td><code>_property_contact_phone</code></td><td>Telefonnummer</td></tr>
                            <tr><td><code>_property_contact_avatar</code></td><td>Avatar-URL</td></tr>
                        </tbody>
                    </table>

                    <h3>Energie</h3>
                    <table class="propstack-re-stats">
                        <thead><tr><th>Meta-Key</th><th>Beschreibung</th></tr></thead>
                        <tbody>
                            <tr><td><code>_property_energy_hwb</code></td><td>Heizwärmebedarf (kWh/m²/a)</td></tr>
                            <tr><td><code>_property_energy_hwb_class</code></td><td>HWB-Klasse (A–G)</td></tr>
                            <tr><td><code>_property_energy_fgee</code></td><td>Gesamtenergieeffizienzfaktor</td></tr>
                            <tr><td><code>_property_energy_fgee_class</code></td><td>fGEE-Klasse</td></tr>
                            <tr><td><code>_property_heating_type</code></td><td>Heizungsart</td></tr>
                            <tr><td><code>_property_energy_carrier</code></td><td>Energieträger</td></tr>
                            <tr><td><code>_property_energy_cert_date</code></td><td>Ausstellungsdatum Energieausweis</td></tr>
                            <tr><td><code>_property_energy_cert_valid</code></td><td>Gültig bis</td></tr>
                        </tbody>
                    </table>

                    <h3>Ausstattung</h3>
                    <p><code>_property_features</code> enthält ein serialisiertes Array mit Ausstattungsmerkmalen als
                    Key-Label-Paare, z.B. <code>['balcony' => 'Balkon', 'elevator' => 'Aufzug']</code>.</p>
                </section>

                <?php /* =========================================================
                   TAXONOMIEN
                ========================================================= */ ?>
                <section id="taxonomien">
                    <h2>Taxonomien</h2>
                    <p>Das Plugin registriert 6 hierarchische Taxonomien für den CPT <code>propstack_property</code>:</p>
                    <table class="propstack-re-stats">
                        <thead><tr><th>Slug</th><th>Label</th><th>Quelle</th><th>URL-Parameter</th></tr></thead>
                        <tbody>
                            <tr><td><code>property_type</code></td><td>Immobilien-Art</td><td><code>property_type</code></td><td><code>?type=</code></td></tr>
                            <tr><td><code>property_city</code></td><td>Stadt</td><td><code>city</code></td><td><code>?city=</code></td></tr>
                            <tr><td><code>property_region</code></td><td>Region</td><td><code>region</code> / <code>district</code></td><td><code>?region=</code></td></tr>
                            <tr><td><code>property_status</code></td><td>Status</td><td><code>status.name</code></td><td><code>?status=</code></td></tr>
                            <tr><td><code>property_marketing_type</code></td><td>Vermarktungsart</td><td><code>marketing_type</code></td><td><code>?marketing=</code></td></tr>
                            <tr><td><code>property_project</code></td><td>Projekt</td><td><code>project_name</code></td><td>—</td></tr>
                        </tbody>
                    </table>
                    <p>Die Terms werden bei jedem Sync automatisch angelegt und zugewiesen. Manuelles Bearbeiten über
                    <em>Beiträge → Immobilien → [Taxonomy-Name]</em> ist möglich, wird aber beim nächsten Sync
                    von Propstack-Daten überschrieben.</p>
                </section>

                <?php /* =========================================================
                   SEO
                ========================================================= */ ?>
                <section id="seo">
                    <h2>SEO</h2>

                    <p>
                        Das Plugin setzt automatisch <code>&lt;title&gt;</code>, Meta-Description,
                        Canonical-Tag, Open-Graph-Tags und JSON-LD (Schema.org <code>RealEstateListing</code>)
                        für Detailseiten.
                    </p>

                    <h3>Yoast SEO / RankMath</h3>
                    <p>
                        Sind Yoast oder RankMath aktiv, werden die eigenen <code>&lt;meta&gt;</code>-Tags
                        deaktiviert und stattdessen deren Filter (<code>wpseo_title</code>, <code>wpseo_metadesc</code>,
                        <code>rank_math/title</code> etc.) befüllt — kein Konflikt, keine doppelten Tags.
                        JSON-LD wird immer ausgegeben.
                    </p>

                    <h3>Title- und Description-Templates</h3>
                    <p>Konfigurierbar unter <a href="<?php echo esc_url( admin_url( 'admin.php?page=propstack-re-seo' ) ); ?>">SEO</a>.
                    Verfügbare Platzhalter:</p>
                    <table class="propstack-re-stats">
                        <thead><tr><th>Platzhalter</th><th>Wert</th></tr></thead>
                        <tbody>
                            <tr><td><code>{title}</code></td><td>Objekt-Titel</td></tr>
                            <tr><td><code>{city}</code></td><td>Stadt</td></tr>
                            <tr><td><code>{region}</code></td><td>Region / Bundesland</td></tr>
                            <tr><td><code>{type}</code></td><td>Immobilien-Art</td></tr>
                            <tr><td><code>{price}</code></td><td>Formatierter Preis</td></tr>
                            <tr><td><code>{rooms}</code></td><td>Zimmeranzahl</td></tr>
                            <tr><td><code>{area}</code></td><td>Wohnfläche formatiert</td></tr>
                            <tr><td><code>{short_description}</code></td><td>Kurzbeschreibung (plain text)</td></tr>
                            <tr><td><code>{object_number}</code></td><td>Objektnummer</td></tr>
                            <tr><td><code>{site_name}</code></td><td>Name der WordPress-Website</td></tr>
                        </tbody>
                    </table>
                    <p><strong>Standard-Templates:</strong><br>
                    Title: <code>{title} in {city} | {site_name}</code><br>
                    Description: <code>{short_description}</code> (auf 160 Zeichen gekürzt)</p>
                </section>

                <?php /* =========================================================
                   WEBHOOK
                ========================================================= */ ?>
                <section id="webhook">
                    <h2>Webhook</h2>
                    <p>
                        Der Webhook ermöglicht Echtzeit-Synchronisierung: Propstack ruft die URL auf,
                        sobald ein Objekt erstellt, geändert oder gelöscht wird.
                    </p>

                    <h3>Webhook-URL</h3>
                    <div class="propstack-docs-example">
                        <code><?php echo esc_html( $webhook_url ); ?></code>
                    </div>
                    <p>Diese URL im Propstack-Backend unter <strong>Einstellungen → Webhooks</strong> eintragen
                    (oder im Plugin-Backend unter <a href="<?php echo esc_url( admin_url( 'admin.php?page=propstack-re-api' ) ); ?>">
                    API Verbindung</a> automatisch registrieren lassen).</p>

                    <h3>Unterstützte Events</h3>
                    <table class="propstack-re-stats">
                        <thead><tr><th>Event</th><th>Aktion</th></tr></thead>
                        <tbody>
                            <tr><td><code>property.created</code></td><td>Objekt wird importiert oder aktualisiert</td></tr>
                            <tr><td><code>property.updated</code></td><td>Objekt wird aktualisiert</td></tr>
                            <tr><td><code>property.deleted</code></td><td>Objekt wird auf Entwurf/Papierkorb gesetzt</td></tr>
                            <tr><td><code>property.archived</code></td><td>Objekt wird deaktiviert</td></tr>
                        </tbody>
                    </table>

                    <h3>Signatur-Verifikation (HMAC)</h3>
                    <p>
                        Unter <a href="<?php echo esc_url( admin_url( 'admin.php?page=propstack-re-api' ) ); ?>">API Verbindung → Webhook Secret</a>
                        ein Geheimnis setzen. Propstack sendet dann den Header
                        <code>X-Propstack-Signature: sha256=&lt;HMAC-SHA256&gt;</code>.
                        Das Plugin verifiziert die Signatur mit <code>hash_equals()</code> (Timing-Safe).
                    </p>
                    <p>Ohne gesetztes Secret werden Webhook-Requests ohne Prüfung akzeptiert (nur für Testzwecke empfohlen).</p>

                    <h3>Async-Verarbeitung</h3>
                    <p>
                        Der Webhook antwortet sofort mit HTTP 200. Der eigentliche Sync wird via
                        <code>wp_schedule_single_event()</code> asynchron durch WP Cron ausgeführt —
                        kein Timeout-Risiko bei großen Objekten.
                    </p>
                </section>

                <?php /* =========================================================
                   CHANGELOG
                ========================================================= */ ?>
                <section id="changelog">
                    <h2>Changelog</h2>
                    <table class="propstack-re-stats">
                        <thead><tr><th>Version</th><th>Datum</th><th>Änderungen</th></tr></thead>
                        <tbody>
                            <tr>
                                <td><strong>1.0.0</strong></td>
                                <td>2026-06-23</td>
                                <td>
                                    Erste Version — Propstack Units-API Sync, CPT + 6 Taxonomien,
                                    Listing/Detail-Templates, Kontaktformular → Propstack Lead,
                                    SEO (eigene Tags + Yoast/RankMath-Integration), Webhook,
                                    Admin-Settings, Logs.<br>
                                    <em>Fix: Endpoint /properties → /units (Propstack API V1)</em><br>
                                    <em>Fix: Status als Objekt-Format {id, name} korrekt auslesen</em>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </section>

            </div>
        </div>

        <style>
            .propstack-re-docs-page h2 { border-bottom: 2px solid #e2e4e7; padding-bottom: .5rem; margin-top: 2.5rem; font-size: 1.35rem; }
            .propstack-re-docs-page h3 { margin-top: 1.75rem; font-size: 1rem; }
            .propstack-docs-nav { position: sticky; top: 32px; background: #fff; border: 1px solid #e2e4e7; border-radius: 6px; padding: .75rem 1rem; margin-bottom: 2rem; display: flex; flex-wrap: wrap; gap: .5rem 1.25rem; z-index: 9; }
            .propstack-docs-nav a { text-decoration: none; color: #2271b1; font-size: .875rem; white-space: nowrap; }
            .propstack-docs-nav a:hover { text-decoration: underline; }
            .propstack-docs-body { max-width: 960px; }
            .propstack-docs-body section { margin-bottom: 3rem; }
            .propstack-docs-example { background: #f6f7f7; border-left: 4px solid #2271b1; padding: .75rem 1rem; margin: .75rem 0; border-radius: 0 4px 4px 0; font-family: monospace; font-size: .85rem; line-height: 1.6; }
            .propstack-docs-example pre { margin: 0; font-family: inherit; font-size: inherit; background: none; padding: 0; }
            .propstack-docs-info { background: #f0f6fc; border: 1px solid #c3d9f5; border-radius: 4px; padding: .75rem 1rem; margin: .75rem 0; }
            .propstack-docs-hint { color: #646970; font-style: italic; font-size: .875rem; }
            .propstack-re-docs-page .propstack-re-stats { margin: .75rem 0 1.25rem; }
            .propstack-re-docs-page ul { list-style: disc; margin-left: 1.5rem; }
            .propstack-re-docs-page ol { margin-left: 1.5rem; }
            .propstack-re-docs-page li { margin: .25rem 0; }
            code { background: #f0f0f0; padding: .1em .35em; border-radius: 3px; font-size: .875em; }
            .propstack-docs-example code { background: none; padding: 0; }
        </style>
        <?php
    }
}
