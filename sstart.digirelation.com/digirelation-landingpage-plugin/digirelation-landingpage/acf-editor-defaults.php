<?php
/**
 * Стартовые данные для удобного редактирования ACF-блока.
 *
 * Повторители получают те же данные, которые уже используются как fallback
 * на фронтенде. Встроенные изображения один раз копируются в медиатеку WP.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function digi_lp_editor_defaults() {
	return array(
		'kpis' => array(
			array( 'value' => '100+', 'label' => 'Projekte live' ),
			array( 'value' => '12', 'label' => 'Branchen' ),
			array( 'value' => '6', 'label' => 'Länder' ),
		),
		'problem_cards' => array(
			array( 'icon' => 'chart', 'title' => 'Keine Anfragen', 'text' => 'Deine Seite hat Besucher, aber daraus wird nichts. Oder es kommt gar kein Traffic. So oder so bleibt das Telefon still und neue Kunden suchst du woanders.' ),
			array( 'icon' => 'screen', 'title' => 'Eine Außenwirkung, die nicht zu dir passt', 'text' => 'Die Seite wirkt alt, lädt langsam und sieht auf dem Handy kaputt aus. Wer dich nicht kennt, klickt weg, bevor er versteht, wie gut du wirklich bist.' ),
			array( 'icon' => 'circle-x', 'title' => 'Die letzte Agentur hat kassiert und nichts geliefert', 'text' => 'Das Geld ist weg, das Ergebnis blieb aus und am Ende hat keiner Verantwortung übernommen. Jetzt fragst du dich, ob sich das Ganze überhaupt nochmal lohnt.' ),
		),
		'solution_cards' => array(
			array( 'icon' => 'target', 'title' => 'Besucher, die dich auch anfragen', 'text' => 'Deine Seite führt Besucher Schritt für Schritt bis zur Anfrage, statt sie nur hübsch zu empfangen. So wird aus einem Klick echtes Geschäft.', 'checks' => "Ein klarer Weg bis zur Anfrage\nMessbar an echten Anfragen" ),
			array( 'icon' => 'bolt', 'title' => 'Ein erster Eindruck, der überzeugt', 'text' => 'Deine Seite lädt schnell, sieht auf dem Handy gut aus und überzeugt Fremde in den ersten Sekunden. Endlich sieht dein Auftritt so aus, wie gut deine Arbeit wirklich ist.', 'checks' => "Mobil zuerst, schnelle Ladezeit\nDesign, das im ersten Moment überzeugt" ),
			array( 'icon' => 'shield', 'title' => 'Du weißt jederzeit, was passiert', 'text' => 'Du hast einen festen Ansprechpartner, klare Zahlen und kein Fachchinesisch. Du siehst jederzeit, woran wir arbeiten und was es dir bringt.', 'checks' => "Ein fester Ansprechpartner\nZahlen offen statt versteckt" ),
		),
		'compare_rows' => array(
			array( 'crit' => 'Strategie und Plan dahinter', 'c1' => 'Vorlage ohne Plan', 'c2' => 'oft Design ohne Verkaufsfokus', 'c3' => 'Plan vom ersten Besuch bis zur Anfrage' ),
			array( 'crit' => 'Neu für dein Geschäft gebaut', 'c1' => 'Template von der Stange', 'c2' => 'individuell, aber teuer', 'c3' => 'neu für dein Geschäft gebaut' ),
			array( 'crit' => 'Auf Anfragen gebaut', 'c1' => 'sieht nett aus, verkauft nicht', 'c2' => 'Design steht im Vordergrund', 'c3' => 'auf Anfragen gebaut und messbar' ),
			array( 'crit' => 'Fester Ansprechpartner', 'c1' => 'du machst alles selbst', 'c2' => 'wechselnde Kontakte', 'c3' => 'ein fester Ansprechpartner' ),
			array( 'crit' => 'Schnell, mobil, SEO', 'c1' => 'Standard, oft langsam', 'c2' => 'sehr unterschiedlich', 'c3' => 'schnell, mobil, SEO von Anfang an' ),
		),
		'steps' => array(
			array( 'title' => 'Strategiegespräch', 'text' => 'Wir schauen uns deine aktuelle Seite an und klären, wo die Anfragen verloren gehen. Du bekommst eine ehrliche Einschätzung, unverbindlich.' ),
			array( 'title' => 'Strategie und Konzept', 'text' => 'Wir legen Struktur, Inhalte und den Weg zur Anfrage fest, bevor wir etwas bauen. So weißt du vorher, wie deine Seite Kunden gewinnt.' ),
			array( 'title' => 'Design und Umsetzung', 'text' => 'Wir bauen deine neue Website und zeigen dir Zwischenstände. Du gibst Feedback, wir setzen es um, bis alles sitzt.' ),
			array( 'title' => 'Launch und Übergabe', 'text' => 'Wir gehen live und zeigen dir, wie du Inhalte selbst pflegst. Auf Wunsch übernehmen wir die laufende Betreuung.' ),
		),
		'testimonials' => array(
			array( 'quote' => '(Offen) Echtes Kundenzitat einsetzen. Hier steht später, was ein Kunde konkret über das Ergebnis und die Zusammenarbeit gesagt hat.', 'name' => '(Offen) Name', 'firma' => '(Offen) Firma' ),
			array( 'quote' => '(Offen) Echtes Kundenzitat einsetzen. Idealerweise mit einer Zahl, also mehr Anfragen oder kürzere Ladezeit.', 'name' => '(Offen) Name', 'firma' => '(Offen) Firma' ),
			array( 'quote' => '(Offen) Echtes Kundenzitat einsetzen. Am stärksten wirkt ein Zitat, das den Wechsel von einer alten Agentur beschreibt.', 'name' => '(Offen) Name', 'firma' => '(Offen) Firma' ),
		),
		'logos' => array(
			array( 'name' => 'Blickwinkel' ),
			array( 'name' => 'European Tennis Academy' ),
			array( 'name' => 'Viridis' ),
			array( 'name' => 'Frachtgut' ),
			array( 'name' => 'JMC Eventtechnik' ),
			array( 'name' => 'Silke Scholz' ),
		),
		'faqs' => array(
			array( 'frage' => 'Was, wenn ich mit einer Agentur schon schlechte Erfahrung gemacht habe?', 'antwort' => 'Damit kommen die meisten zu uns. Du bekommst einen festen Ansprechpartner, klare Zahlen und siehst jeden Zwischenstand, bevor wir live gehen. Im Strategiegespräch zeigen wir erst, was deine Seite braucht, damit du nichts blind entscheidest.' ),
			array( 'frage' => 'Was kostet eine neue Website?', 'antwort' => 'Das hängt von Aufwand, Größe und Technik deiner Website ab, deshalb gibt es keinen Festpreis von der Stange. Im Strategiegespräch bekommst du einen klaren Preis für genau deinen Umfang, bevor du dich entscheidest, ganz ohne versteckte Kosten.' ),
			array( 'frage' => 'Wie lange dauert das?', 'antwort' => 'Im Schnitt dauert eine neue Website 4 bis 8 Wochen, vom Erstgespräch bis zum Launch. Den genauen Zeitplan legen wir nach dem Strategiegespräch fest, damit du jederzeit weißt, woran du bist.' ),
			array( 'frage' => 'Was, wenn die Seite am Ende keine Anfragen bringt?', 'antwort' => 'Wir bauen jede Seite auf Anfragen, nicht auf Optik allein, und messen, was Besucher zu Kunden macht. Wenn etwas nicht funktioniert, sehen wir es in den Zahlen und bessern nach, statt dich damit allein zu lassen.' ),
			array( 'frage' => 'Ich habe schon eine Website. Wird die umgebaut oder neu gemacht?', 'antwort' => 'Wir bauen dir eine neue Seite, statt an der alten herumzuflicken. Was an deiner aktuellen funktioniert, nehmen wir mit, den Rest machen wir besser. Deine Inhalte und deine Domain bleiben, der Auftritt wird neu.' ),
		),
	);
}

function digi_lp_media_manifest() {
	return array(
		'feat_image' => array( 'file' => 'assets/about/ueberuns.png', 'title' => 'digirelation – Über uns' ),
		'how_image' => array( 'file' => 'assets/about/muki-alex-workspace.jpg', 'title' => 'digirelation – Arbeitsplatz' ),
		'blickwinkel' => array( 'file' => 'assets/portfolio/blickwinkel.jpg', 'title' => 'Blickwinkel' ),
		'tennis-academy' => array( 'file' => 'assets/portfolio/tennis-academy.jpg', 'title' => 'European Tennis Academy' ),
		'viridis' => array( 'file' => 'assets/portfolio/viridis.png', 'title' => 'Viridis Planungsbüro' ),
		'frachtgut' => array( 'file' => 'assets/portfolio/frachtgut.png', 'title' => 'Frachtgut Food Truck' ),
		'rebuild' => array( 'file' => 'assets/portfolio/rebuild.png', 'title' => 'Rebuild Ingenieurbüro' ),
		'jmc' => array( 'file' => 'assets/portfolio/jmc.png', 'title' => 'JMC Eventtechnik' ),
		'silke-scholz' => array( 'file' => 'assets/portfolio/silke-scholz.jpg', 'title' => 'Silke Scholz Atelier' ),
		'nm-pro-assistant' => array( 'file' => 'assets/portfolio/nm-pro-assistant.png', 'title' => 'NM Pro Assistant' ),
		'bk-partners' => array( 'file' => 'assets/portfolio/bk-partners.png', 'title' => 'BK Partners' ),
	);
}

function digi_lp_import_editor_media() {
	if ( ! is_admin() || ! current_user_can( 'upload_files' ) ) {
		return;
	}

	$media = get_option( 'digi_lp_editor_media_v1', array() );
	$media = is_array( $media ) ? $media : array();

	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	foreach ( digi_lp_media_manifest() as $key => $item ) {
		if ( ! empty( $media[ $key ] ) && get_post( (int) $media[ $key ] ) ) {
			continue;
		}

		$source = DIGI_LP_DIR . $item['file'];
		if ( ! is_readable( $source ) ) {
			continue;
		}

		$tmp = wp_tempnam( basename( $source ) );
		if ( ! $tmp || ! copy( $source, $tmp ) ) {
			continue;
		}

		$id = media_handle_sideload(
			array( 'name' => basename( $source ), 'tmp_name' => $tmp ),
			1054,
			$item['title']
		);

		if ( is_wp_error( $id ) ) {
			@unlink( $tmp );
			continue;
		}

		$media[ $key ] = (int) $id;
	}

	update_option( 'digi_lp_editor_media_v1', $media, false );
}
add_action( 'admin_init', 'digi_lp_import_editor_media', 5 );

function digi_lp_reference_defaults() {
	$media = get_option( 'digi_lp_editor_media_v1', array() );
	return array(
		array( 'image' => $media['blickwinkel'] ?? 0, 'name' => 'Blickwinkel', 'branche' => 'Digitale Landwirtschaft', 'geo' => 'Kirchdorf am Inn, Österreich' ),
		array( 'image' => $media['tennis-academy'] ?? 0, 'name' => 'European Tennis Academy', 'branche' => 'Hotellerie', 'geo' => 'Schweiz' ),
		array( 'image' => $media['viridis'] ?? 0, 'name' => 'Viridis Planungsbüro', 'branche' => 'Energieberatung', 'geo' => 'Hamburg, Deutschland' ),
		array( 'image' => $media['frachtgut'] ?? 0, 'name' => 'Frachtgut Food Truck', 'branche' => 'Catering & Food Truck', 'geo' => 'Augsburg, Deutschland' ),
		array( 'image' => $media['rebuild'] ?? 0, 'name' => 'Rebuild Ingenieurbüro', 'branche' => 'Bauplanung', 'geo' => 'Deutschland' ),
		array( 'image' => $media['jmc'] ?? 0, 'name' => 'JMC Eventtechnik', 'branche' => 'Eventtechnik', 'geo' => 'Vorarlberg, Österreich' ),
		array( 'image' => $media['silke-scholz'] ?? 0, 'name' => 'Silke Scholz Atelier', 'branche' => 'Schneiderei', 'geo' => 'Frankfurt, Deutschland' ),
		array( 'image' => $media['nm-pro-assistant'] ?? 0, 'name' => 'NM Pro Assistant', 'branche' => 'Virtuelle Assistenz', 'geo' => 'Österreich' ),
		array( 'image' => $media['bk-partners'] ?? 0, 'name' => 'BK Partners', 'branche' => 'Immobilienverwaltung', 'geo' => 'Kloten & Zug, Schweiz' ),
	);
}

function digi_lp_set_repeater_data( &$data, $name, $field_key, $sub_fields, $rows ) {
	if ( array_key_exists( $name, $data ) && (int) $data[ $name ] > 0 ) {
		return;
	}

	$data[ $name ]     = count( $rows );
	$data[ '_' . $name ] = $field_key;

	foreach ( $rows as $index => $row ) {
		foreach ( $sub_fields as $sub_name => $sub_key ) {
			$value_key                 = $name . '_' . $index . '_' . $sub_name;
			$data[ $value_key ]        = $row[ $sub_name ] ?? '';
			$data[ '_' . $value_key ]  = $sub_key;
		}
	}
}

function digi_lp_migrate_editor_block( $block, &$changed ) {
	if ( in_array( $block['blockName'] ?? '', array( 'digirelation/landingpage', 'acf/digirelation-landingpage' ), true ) ) {
		$block['attrs']['mode'] = 'edit';
		$data                   = $block['attrs']['data'] ?? array();
		$defaults               = digi_lp_editor_defaults();
		$media                  = get_option( 'digi_lp_editor_media_v1', array() );

		digi_lp_set_repeater_data( $data, 'kpis', 'field_dlp_017_kpis', array( 'value' => 'field_dlp_015_value', 'label' => 'field_dlp_016_label' ), $defaults['kpis'] );
		digi_lp_set_repeater_data( $data, 'problem_cards', 'field_dlp_025_problem_cards', array( 'icon' => 'field_dlp_022_icon', 'title' => 'field_dlp_023_title', 'text' => 'field_dlp_024_text' ), $defaults['problem_cards'] );
		digi_lp_set_repeater_data( $data, 'solution_cards', 'field_dlp_034_solution_cards', array( 'icon' => 'field_dlp_030_icon', 'title' => 'field_dlp_031_title', 'text' => 'field_dlp_032_text', 'checks' => 'field_dlp_033_checks' ), $defaults['solution_cards'] );
		digi_lp_set_repeater_data( $data, 'compare_rows', 'field_dlp_047_compare_rows', array( 'crit' => 'field_dlp_043_crit', 'c1' => 'field_dlp_044_c1', 'c2' => 'field_dlp_045_c2', 'c3' => 'field_dlp_046_c3' ), $defaults['compare_rows'] );
		digi_lp_set_repeater_data( $data, 'steps', 'field_dlp_068_steps', array( 'title' => 'field_dlp_066_title', 'text' => 'field_dlp_067_text' ), $defaults['steps'] );
		digi_lp_set_repeater_data( $data, 'references', 'field_dlp_077_references', array( 'image' => 'field_dlp_073_image', 'name' => 'field_dlp_074_name', 'branche' => 'field_dlp_075_branche', 'geo' => 'field_dlp_076_geo' ), digi_lp_reference_defaults() );
		digi_lp_set_repeater_data( $data, 'testimonials', 'field_dlp_084_testimonials', array( 'quote' => 'field_dlp_081_quote', 'name' => 'field_dlp_082_name', 'firma' => 'field_dlp_083_firma' ), $defaults['testimonials'] );
		digi_lp_set_repeater_data( $data, 'logos', 'field_dlp_087_logos', array( 'name' => 'field_dlp_086_name' ), $defaults['logos'] );
		digi_lp_set_repeater_data( $data, 'faqs', 'field_dlp_093_faqs', array( 'frage' => 'field_dlp_091_frage', 'antwort' => 'field_dlp_092_antwort' ), $defaults['faqs'] );

		foreach ( array( 'feat_image' => 'field_dlp_051_feat_image', 'how_image' => 'field_dlp_065_how_image' ) as $name => $field_key ) {
			if ( ! array_key_exists( $name, $data ) && ! empty( $media[ $name ] ) ) {
				$data[ $name ]       = (int) $media[ $name ];
				$data[ '_' . $name ] = $field_key;
			}
		}

		$block['attrs']['data'] = $data;
		$changed                = true;
	}

	if ( ! empty( $block['innerBlocks'] ) ) {
		foreach ( $block['innerBlocks'] as $index => $inner_block ) {
			$block['innerBlocks'][ $index ] = digi_lp_migrate_editor_block( $inner_block, $changed );
		}
	}

	return $block;
}

function digi_lp_run_editor_migration() {
	if ( ! is_admin() || get_option( 'digi_lp_editor_migration_v4' ) || ! current_user_can( 'edit_post', 1054 ) ) {
		return;
	}

	$post = get_post( 1054 );
	if ( ! $post ) {
		return;
	}

	$changed = false;
	$blocks  = parse_blocks( $post->post_content );
	foreach ( $blocks as $index => $block ) {
		$blocks[ $index ] = digi_lp_migrate_editor_block( $block, $changed );
	}

	if ( $changed ) {
		wp_update_post( array( 'ID' => 1054, 'post_content' => serialize_blocks( $blocks ) ) );
		update_option( 'digi_lp_editor_migration_v4', 1, false );
	}
}
add_action( 'admin_init', 'digi_lp_run_editor_migration', 20 );
