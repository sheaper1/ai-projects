<?php
/**
 * ACF-Block render: Performance Landingpage (voll ACF-getrieben).
 * Alle Texte, Karten (Repeater) und Icons editierbar. Inhalte aus der Datei = Defaults.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

wp_enqueue_script( 'digi-lp', DIGI_LP_URL . 'assets/js/landing.js', array(), DIGI_LP_VER, true );

/* ---- Helfer ---- */
if ( ! function_exists( 'digi_lp_t' ) ) {
	function digi_lp_t( $k, $d = '' ) { $v = get_field( $k ); return ( $v === null || $v === '' ) ? $d : $v; }
}
if ( ! function_exists( 'digi_lp_rows' ) ) {
	// Repeater-Wert oder Default-Array, falls leer.
	function digi_lp_rows( $k, $default ) { $v = get_field( $k ); return ( empty( $v ) || ! is_array( $v ) ) ? $default : $v; }
}
if ( ! function_exists( 'digi_lp_icon' ) ) {
	function digi_lp_icon( $slug ) {
		$i = array(
			'chart'    => '<path d="M3 3v18h18"/><path d="M19 9l-5 5-3-3-4 4"/>',
			'screen'   => '<rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/><path d="M6 8l3 3-3 3"/>',
			'circle-x' => '<circle cx="12" cy="12" r="9"/><path d="M15 9l-6 6M9 9l6 6"/>',
			'target'   => '<path d="M12 2a10 10 0 1 0 10 10"/><path d="M22 2l-10 10"/><circle cx="12" cy="12" r="4"/>',
			'bolt'     => '<path d="M13 2L3 14h9l-1 8 10-12h-9z"/>',
			'shield'   => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="M9 12l2 2 4-4"/>',
			'info'     => '<circle cx="12" cy="12" r="9"/><path d="M12 16v-5"/><path d="M12 8h.01"/>',
			'monitor'  => '<rect x="3" y="4" width="18" height="14" rx="2"/><path d="M3 9h18M8 21h8"/>',
			'search'   => '<circle cx="11" cy="11" r="7"/><path d="M21 21l-4-4"/>',
			'rocket'   => '<path d="M5 13c-1 1-2 5-2 5s4-1 5-2"/><path d="M12 15l-3-3a14 14 0 0 1 8-8c2 0 3 1 3 3a14 14 0 0 1-8 8z"/>',
			'star'     => '<path d="M12 2l3 7 7 .5-5.5 4.5 2 7-6.5-4-6.5 4 2-7L2 9.5 9 9z"/>',
			'heart'    => '<path d="M12 21s-8-4.5-8-11a4.5 4.5 0 0 1 8-3 4.5 4.5 0 0 1 8 3c0 6.5-8 11-8 11z"/>',
			'clock'    => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
			'users'    => '<circle cx="9" cy="8" r="3"/><path d="M3 20a6 6 0 0 1 12 0"/><path d="M16 6a3 3 0 0 1 0 6"/><path d="M21 20a6 6 0 0 0-3-5"/>',
		);
		$inner = isset( $i[ $slug ] ) ? $i[ $slug ] : $i['circle-x'];
		return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">' . $inner . '</svg>';
	}
}
if ( ! function_exists( 'digi_lp_checks' ) ) {
	function digi_lp_checks( $text ) {
		$out = '';
		foreach ( preg_split( '/\r\n|\r|\n/', (string) $text ) as $line ) {
			$line = trim( $line );
			if ( $line === '' ) continue;
			$out .= '<li><svg viewBox="0 0 24 24" fill="none" stroke-width="2"><path d="M20 6L9 17l-5-5"/></svg>' . esc_html( $line ) . '</li>';
		}
		return $out;
	}
}
if ( ! function_exists( 'digi_lp_clip' ) ) {
	function digi_lp_clip( $text, $max = 240 ) {
		$text = trim( preg_replace( '/\s+/', ' ', (string) $text ) );
		$len  = function_exists( 'mb_strlen' ) ? mb_strlen( $text ) : strlen( $text );
		if ( $len <= $max ) {
			return $text;
		}
		$cut = function_exists( 'mb_substr' ) ? mb_substr( $text, 0, $max ) : substr( $text, 0, $max );
		$sp  = strrpos( $cut, ' ' );
		if ( $sp > $max * 0.6 ) {
			$cut = substr( $cut, 0, $sp );
		}
		return rtrim( $cut, " ,.;:" ) . ' …';
	}
}
if ( ! function_exists( 'digi_lp_parse_grw' ) ) {
	// Liest echte Google-Bewertungen aus dem [grw]-Widget (RichPlugins) server-seitig aus.
	function digi_lp_parse_grw( $html ) {
		if ( ! $html || stripos( $html, 'grw-review' ) === false || ! class_exists( 'DOMDocument' ) ) {
			return array( 'count' => 0, 'reviews' => array() );
		}
		$prev = libxml_use_internal_errors( true );
		$doc  = new DOMDocument();
		$doc->loadHTML( '<?xml encoding="utf-8" ?>' . $html );
		libxml_clear_errors();
		libxml_use_internal_errors( $prev );
		$xp = new DOMXPath( $doc );

		$count       = 0;
		$slides_node = $xp->query( '//*[contains(@class,"rpi-slides")]' )->item( 0 );
		if ( $slides_node && $slides_node->getAttribute( 'data-count' ) ) {
			$count = (int) $slides_node->getAttribute( 'data-count' );
		}

		$reviews = array();
		foreach ( $xp->query( '//*[contains(concat(" ",normalize-space(@class)," ")," grw-review ")]' ) as $node ) {
			$nameN = $xp->query( './/*[contains(@class,"wp-google-name")]', $node )->item( 0 );
			$textN = $xp->query( './/*[contains(@class,"wp-google-text")]', $node )->item( 0 );
			$starN = $xp->query( './/*[contains(@class,"rpi-stars")]', $node )->item( 0 );
			$name  = $nameN ? trim( $nameN->textContent ) : '';
			$text  = $textN ? trim( $textN->textContent ) : '';
			if ( $name === '' || $text === '' ) {
				continue;
			}
			$rating = 5;
			if ( $starN && preg_match( '/--rating:\s*([0-9.]+)/', $starN->getAttribute( 'style' ), $m ) ) {
				$rating = (int) round( (float) $m[1] );
			}
			$reviews[] = array( 'name' => $name, 'text' => $text, 'rating' => $rating );
		}
		if ( ! $count ) {
			$count = count( $reviews );
		}
		return array( 'count' => $count, 'reviews' => $reviews );
	}
}
if ( ! function_exists( 'digi_lp_video_embed' ) ) {
	function digi_lp_video_embed( $url ) {
		if ( preg_match( '~youtu(?:\.be/|be\.com/(?:watch\?v=|embed/|shorts/))([a-zA-Z0-9_-]{11})~', $url, $m ) ) {
			return '<iframe src="https://www.youtube-nocookie.com/embed/' . $m[1] . '?rel=0" width="560" height="315" title="Video" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen loading="lazy"></iframe>';
		}
		if ( preg_match( '~vimeo\.com/(?:video/)?(\d+)~', $url, $m ) ) {
			return '<iframe src="https://player.vimeo.com/video/' . $m[1] . '" width="560" height="315" title="Video" frameborder="0" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen loading="lazy"></iframe>';
		}
		return '';
	}
}

/* ---- Allgemein ---- */
$cta_url        = digi_lp_t( 'cta_url', '/kontakt/' );
$cta_btn        = digi_lp_t( 'cta_btn', 'Strategiegespräch anfragen' );
$vsl_url        = get_field( 'vsl_url' );
$vsl_embed      = get_field( 'vsl_embed' );
$vsl_badge      = digi_lp_t( 'vsl_badge', 'In 90 Sek. erklärt' );
$google_rating  = digi_lp_t( 'google_rating', '4,9' );
$google_link    = get_field( 'google_link' );
$testimonials   = get_field( 'testimonials' );

/* ---- Defaults für Repeater ---- */
$U = DIGI_LP_URL . 'assets/';
$def_problem = array(
	array( 'icon' => 'chart',    'title' => 'Keine Anfragen', 'text' => 'Deine Seite hat Besucher, aber daraus wird nichts. Oder es kommt gar kein Traffic. So oder so bleibt das Telefon still und neue Kunden suchst du woanders.' ),
	array( 'icon' => 'screen',   'title' => 'Eine Außenwirkung, die nicht zu dir passt', 'text' => 'Die Seite wirkt alt, lädt langsam und sieht auf dem Handy kaputt aus. Wer dich nicht kennt, klickt weg, bevor er versteht, wie gut du wirklich bist.' ),
	array( 'icon' => 'circle-x', 'title' => 'Die letzte Agentur hat kassiert und nichts geliefert', 'text' => 'Das Geld ist weg, das Ergebnis blieb aus und am Ende hat keiner Verantwortung übernommen. Jetzt fragst du dich, ob sich das Ganze überhaupt nochmal lohnt.' ),
);
$def_solution = array(
	array( 'icon' => 'target', 'title' => 'Besucher, die dich auch anfragen', 'text' => 'Deine Seite führt Besucher Schritt für Schritt bis zur Anfrage, statt sie nur hübsch zu empfangen. So wird aus einem Klick echtes Geschäft.', 'checks' => "Ein klarer Weg bis zur Anfrage\nMessbar an echten Anfragen" ),
	array( 'icon' => 'bolt',   'title' => 'Ein erster Eindruck, der überzeugt', 'text' => 'Deine Seite lädt schnell, sieht auf dem Handy gut aus und überzeugt Fremde in den ersten Sekunden. Endlich sieht dein Auftritt so aus, wie gut deine Arbeit wirklich ist.', 'checks' => "Mobil zuerst, schnelle Ladezeit\nDesign, das im ersten Moment überzeugt" ),
	array( 'icon' => 'shield', 'title' => 'Du weißt jederzeit, was passiert', 'text' => 'Du hast einen festen Ansprechpartner, klare Zahlen und kein Fachchinesisch. Du siehst jederzeit, woran wir arbeiten und was es dir bringt.', 'checks' => "Ein fester Ansprechpartner\nZahlen offen statt versteckt" ),
);
$def_kpis = array(
	array( 'value' => '100+', 'label' => 'Projekte live' ),
	array( 'value' => '12',   'label' => 'Branchen' ),
	array( 'value' => '6',    'label' => 'Länder' ),
);
$def_compare = array(
	array( 'crit' => 'Strategie und Plan dahinter', 'c1' => 'Vorlage ohne Plan', 'c2' => 'oft Design ohne Verkaufsfokus', 'c3' => 'Plan vom ersten Besuch bis zur Anfrage' ),
	array( 'crit' => 'Neu für dein Geschäft gebaut', 'c1' => 'Template von der Stange', 'c2' => 'individuell, aber teuer', 'c3' => 'neu für dein Geschäft gebaut' ),
	array( 'crit' => 'Auf Anfragen gebaut', 'c1' => 'sieht nett aus, verkauft nicht', 'c2' => 'Design steht im Vordergrund', 'c3' => 'auf Anfragen gebaut und messbar' ),
	array( 'crit' => 'Fester Ansprechpartner', 'c1' => 'du machst alles selbst', 'c2' => 'wechselnde Kontakte', 'c3' => 'ein fester Ansprechpartner' ),
	array( 'crit' => 'Schnell, mobil, SEO', 'c1' => 'Standard, oft langsam', 'c2' => 'sehr unterschiedlich', 'c3' => 'schnell, mobil, SEO von Anfang an' ),
);
$def_steps = array(
	array( 'title' => 'Strategiegespräch', 'text' => 'Wir schauen uns deine aktuelle Seite an und klären, wo die Anfragen verloren gehen. Du bekommst eine ehrliche Einschätzung, unverbindlich.' ),
	array( 'title' => 'Strategie und Konzept', 'text' => 'Wir legen Struktur, Inhalte und den Weg zur Anfrage fest, bevor wir etwas bauen. So weißt du vorher, wie deine Seite Kunden gewinnt.' ),
	array( 'title' => 'Design und Umsetzung', 'text' => 'Wir bauen deine neue Website und zeigen dir Zwischenstände. Du gibst Feedback, wir setzen es um, bis alles sitzt.' ),
	array( 'title' => 'Launch und Übergabe', 'text' => 'Wir gehen live und zeigen dir, wie du Inhalte selbst pflegst. Auf Wunsch übernehmen wir die laufende Betreuung.' ),
);
$def_testimonials = array(
	array( 'quote' => '(Offen) Echtes Kundenzitat einsetzen. Hier steht später, was ein Kunde konkret über das Ergebnis und die Zusammenarbeit gesagt hat.', 'name' => '(Offen) Name', 'firma' => '(Offen) Firma' ),
	array( 'quote' => '(Offen) Echtes Kundenzitat einsetzen. Idealerweise mit einer Zahl, also mehr Anfragen oder kürzere Ladezeit.', 'name' => '(Offen) Name', 'firma' => '(Offen) Firma' ),
	array( 'quote' => '(Offen) Echtes Kundenzitat einsetzen. Am stärksten wirkt ein Zitat, das den Wechsel von einer alten Agentur beschreibt.', 'name' => '(Offen) Name', 'firma' => '(Offen) Firma' ),
);
$testimonials = is_array( $testimonials ) && $testimonials ? $testimonials : $def_testimonials;

/* ---- Echte Google-Bewertungen aus [grw]-Widget (server-seitig) ---- */
$grw_id = get_field( 'grw_id' );
if ( $grw_id && ! is_admin() && function_exists( 'do_shortcode' ) ) {
	$grw = digi_lp_parse_grw( do_shortcode( '[grw id="' . intval( $grw_id ) . '"]' ) );
	if ( ! empty( $grw['reviews'] ) ) {
		$gt = array();
		foreach ( $grw['reviews'] as $r ) {
			if ( ( function_exists( 'mb_strlen' ) ? mb_strlen( $r['text'] ) : strlen( $r['text'] ) ) < 40 ) {
				continue;
			}
			$gt[] = array( 'quote' => digi_lp_clip( $r['text'], 240 ), 'name' => $r['name'], 'firma' => 'Google Rezension' );
			if ( count( $gt ) >= 6 ) {
				break;
			}
		}
		if ( $gt ) {
			$testimonials = $gt;
		}
	}
}
$def_refs = array(
	array( 'image' => $U.'portfolio/blickwinkel.jpg', 'name' => 'Blickwinkel', 'branche' => 'Digitale Landwirtschaft', 'geo' => 'Kirchdorf am Inn, Österreich' ),
	array( 'image' => $U.'portfolio/tennis-academy.jpg', 'name' => 'European Tennis Academy', 'branche' => 'Hotellerie', 'geo' => 'Schweiz' ),
	array( 'image' => $U.'portfolio/viridis.png', 'name' => 'Viridis Planungsbüro', 'branche' => 'Energieberatung', 'geo' => 'Hamburg, Deutschland' ),
	array( 'image' => $U.'portfolio/frachtgut.png', 'name' => 'Frachtgut Food Truck', 'branche' => 'Catering & Food Truck', 'geo' => 'Augsburg, Deutschland' ),
	array( 'image' => $U.'portfolio/rebuild.png', 'name' => 'Rebuild Ingenieurbüro', 'branche' => 'Bauplanung', 'geo' => 'Deutschland' ),
	array( 'image' => $U.'portfolio/jmc.png', 'name' => 'JMC Eventtechnik', 'branche' => 'Eventtechnik', 'geo' => 'Vorarlberg, Österreich' ),
	array( 'image' => $U.'portfolio/silke-scholz.jpg', 'name' => 'Silke Scholz Atelier', 'branche' => 'Schneiderei', 'geo' => 'Frankfurt, Deutschland' ),
	array( 'image' => $U.'portfolio/nm-pro-assistant.png', 'name' => 'NM Pro Assistant', 'branche' => 'Virtuelle Assistenz', 'geo' => 'Österreich' ),
	array( 'image' => $U.'portfolio/bk-partners.png', 'name' => 'BK Partners', 'branche' => 'Immobilienverwaltung', 'geo' => 'Kloten & Zug, Schweiz' ),
);
$def_logos = array(
	array('name'=>'Blickwinkel'), array('name'=>'European Tennis Academy'), array('name'=>'Viridis'),
	array('name'=>'Frachtgut'), array('name'=>'JMC Eventtechnik'), array('name'=>'Silke Scholz'),
);
$def_faq = array(
	array( 'frage' => 'Was, wenn ich mit einer Agentur schon schlechte Erfahrung gemacht habe?', 'antwort' => 'Damit kommen die meisten zu uns. Du bekommst einen festen Ansprechpartner, klare Zahlen und siehst jeden Zwischenstand, bevor wir live gehen. Im Strategiegespräch zeigen wir erst, was deine Seite braucht, damit du nichts blind entscheidest.' ),
	array( 'frage' => 'Was kostet eine neue Website?', 'antwort' => 'Das hängt von Aufwand, Größe und Technik deiner Website ab, deshalb gibt es keinen Festpreis von der Stange. Im Strategiegespräch bekommst du einen klaren Preis für genau deinen Umfang, bevor du dich entscheidest, ganz ohne versteckte Kosten.' ),
	array( 'frage' => 'Wie lange dauert das?', 'antwort' => 'Im Schnitt dauert eine neue Website 4 bis 8 Wochen, vom Erstgespräch bis zum Launch. Den genauen Zeitplan legen wir nach dem Strategiegespräch fest, damit du jederzeit weißt, woran du bist.' ),
	array( 'frage' => 'Was, wenn die Seite am Ende keine Anfragen bringt?', 'antwort' => 'Wir bauen jede Seite auf Anfragen, nicht auf Optik allein, und messen, was Besucher zu Kunden macht. Wenn etwas nicht funktioniert, sehen wir es in den Zahlen und bessern nach, statt dich damit allein zu lassen.' ),
	array( 'frage' => 'Ich habe schon eine Website. Wird die umgebaut oder neu gemacht?', 'antwort' => 'Wir bauen dir eine neue Seite, statt an der alten herumzuflicken. Was an deiner aktuellen funktioniert, nehmen wir mit, den Rest machen wir besser. Deine Inhalte und deine Domain bleiben, der Auftritt wird neu.' ),
);
$def_cta_checks = "Konkrete Schwachstellen statt allgemeiner Tipps\nEin klarer Plan für die neue Website\nUnverbindlich, mit Antwort in 24 Stunden";

$icon = function( $slug ) { return digi_lp_icon( $slug ); };
?>
<div class="digi-lp">
<!-- ============ HEADER ============ -->
<header>
  <div class="wrap nav">
    <a href="#top" class="logo"><span class="mark">d</span> <?php echo esc_html( digi_lp_t( 'brand', 'digirelation' ) ); ?></a>
    <nav class="nav-links" id="navLinks">
      <a href="#problem" class="link">Problem</a>
      <a href="#loesung" class="link">Lösung</a>
      <a href="#features" class="link">Leistungen</a>
      <a href="#ablauf" class="link">Ablauf</a>
      <a href="#referenzen" class="link">Referenzen</a>
      <a href="<?php echo esc_url( $cta_url ); ?>" class="btn btn-primary"><?php echo esc_html( $cta_btn ); ?></a>
    </nav>
    <button class="menu-toggle" id="menuToggle" aria-label="Menü">
      <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
    </button>
  </div>
</header>

<!-- ============ HERO ============ -->
<section class="hero" id="top">
  <div class="wrap">
   <div class="hero-copy">
    <span class="eyebrow reveal"><span class="dot"></span><?php echo esc_html( digi_lp_t( 'hero_eyebrow', 'Webdesign mit Performance-Fokus' ) ); ?></span>
    <h1 class="reveal"><?php echo esc_html( digi_lp_t( 'hero_h1a', 'Deine ' ) ); ?><span class="hl"><?php echo esc_html( digi_lp_t( 'hero_h1b', 'neue Website' ) ); ?></span><?php echo esc_html( digi_lp_t( 'hero_h1c', ', gebaut für dein Geschäft' ) ); ?></h1>
    <p class="sub reveal"><?php echo esc_html( digi_lp_t( 'hero_sub', 'Wir bauen dir eine Website, die modern aussieht und zu dem passt, was du wirklich machst. Deine alte hat ausgedient.' ) ); ?></p>
    <div class="hero-cta reveal">
      <a href="<?php echo esc_url( $cta_url ); ?>" class="btn btn-primary btn-lg"><?php echo esc_html( $cta_btn ); ?></a>
    </div>
    <div class="trust-row reveal">
      <?php if ( $google_link ) : ?><a href="<?php echo esc_url( $google_link ); ?>" class="google-badge" target="_blank" rel="noopener noreferrer"><?php else : ?><div class="google-badge"><?php endif; ?>
        <svg class="g" viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.27-4.74 3.27-8.1z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84A11 11 0 0 0 12 23z"/><path fill="#FBBC05" d="M5.84 14.1a6.6 6.6 0 0 1 0-4.2V7.06H2.18a11 11 0 0 0 0 9.88l3.66-2.84z"/><path fill="#EA4335" d="M12 4.75c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 1.45 14.97.5 12 .5A11 11 0 0 0 2.18 7.06l3.66 2.84C6.71 7.3 9.14 5.37 12 5.37z"/></svg>
        <div class="rate">
          <div class="stars">★★★★★</div>
          <b><?php echo esc_html( $google_rating ); ?> von 5</b>
          <span>Bewertungen auf Google</span>
        </div>
      <?php if ( $google_link ) : ?></a><?php else : ?></div><?php endif; ?>
      <div class="kpi-strip">
        <?php $kpis = digi_lp_rows( 'kpis', $def_kpis ); $ki = 0; foreach ( $kpis as $k ) : $val = is_array($k)?($k['value']??''):''; $lab = is_array($k)?($k['label']??''):''; if ( $ki++ ) : ?><div class="sep"></div><?php endif; ?>
        <div class="kpi"><b class="tnum"><?php echo esc_html( $val ); ?></b><span><?php echo esc_html( $lab ); ?></span></div>
        <?php endforeach; ?>
      </div>
    </div>
   </div>
   <div class="hero-video reveal">
     <div class="vsl">
       <?php if ( $vsl_url ) : ?>
       <?php echo digi_lp_video_embed( esc_url_raw( $vsl_url ) ); ?>
       <?php elseif ( $vsl_embed ) : ?>
       <?php echo wp_kses( $vsl_embed, array( 'iframe' => array( 'src' => true, 'title' => true, 'width' => true, 'height' => true, 'allow' => true, 'allowfullscreen' => true, 'loading' => true, 'referrerpolicy' => true ), 'video' => array( 'src' => true, 'controls' => true, 'poster' => true, 'preload' => true ), 'source' => array( 'src' => true, 'type' => true ) ) ); ?>
       <?php else : ?>
       <button class="vsl-play" type="button" aria-label="Video abspielen">
         <svg viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
       </button>
       <span class="vsl-badge"><?php echo esc_html( $vsl_badge ); ?></span>
       <?php endif; ?>
     </div>
   </div>
  </div>
</section>

<!-- ============ PROBLEM ============ -->
<section class="problem section-pad" id="problem">
  <div class="wrap">
    <div class="section-head reveal">
      <span class="eyebrow"><span class="dot"></span><?php echo esc_html( digi_lp_t( 'problem_eyebrow', 'Das Problem' ) ); ?></span>
      <h2><?php echo esc_html( digi_lp_t( 'problem_h2', 'Kommt dir das bekannt vor?' ) ); ?></h2>
      <p><?php echo esc_html( digi_lp_t( 'problem_intro', 'Drei Probleme, an denen die meisten Websites scheitern. Wahrscheinlich kennst du mindestens eins.' ) ); ?></p>
    </div>
    <div class="grid-3">
      <?php foreach ( digi_lp_rows( 'problem_cards', $def_problem ) as $c ) : ?>
      <div class="card reveal">
        <div class="ic"><?php echo digi_lp_icon( $c['icon'] ?? 'circle-x' ); ?></div>
        <h3><?php echo esc_html( $c['title'] ?? '' ); ?></h3>
        <p><?php echo esc_html( $c['text'] ?? '' ); ?></p>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ============ LÖSUNG ============ -->
<section class="solution section-pad" id="loesung">
  <div class="wrap">
    <div class="section-head reveal">
      <span class="eyebrow"><span class="dot"></span><?php echo esc_html( digi_lp_t( 'sol_eyebrow', 'Die Lösung' ) ); ?></span>
      <h2><?php echo esc_html( digi_lp_t( 'sol_h2', 'Deine Website arbeitet endlich für dich' ) ); ?></h2>
      <p><?php echo esc_html( digi_lp_t( 'sol_intro', 'Deine Seite bekommt einen Plan, der zu deinem Geschäft passt. Fertig ist sie erst, wenn sie dir Anfragen bringt.' ) ); ?></p>
    </div>
    <div class="grid-3">
      <?php foreach ( digi_lp_rows( 'solution_cards', $def_solution ) as $c ) : ?>
      <div class="card reveal">
        <div class="ic"><?php echo digi_lp_icon( $c['icon'] ?? 'target' ); ?></div>
        <h3><?php echo esc_html( $c['title'] ?? '' ); ?></h3>
        <p><?php echo esc_html( $c['text'] ?? '' ); ?></p>
        <ul class="check-list"><?php echo digi_lp_checks( $c['checks'] ?? '' ); ?></ul>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ============ VERGLEICH ============ -->
<section class="compare section-pad" id="vergleich">
  <div class="wrap">
    <div class="section-head reveal">
      <span class="eyebrow"><span class="dot"></span><?php echo esc_html( digi_lp_t( 'cmp_eyebrow', 'Der Unterschied' ) ); ?></span>
      <h2><?php echo esc_html( digi_lp_t( 'cmp_h2', 'Baukasten, Agentur oder digirelation?' ) ); ?></h2>
      <p><?php echo esc_html( digi_lp_t( 'cmp_intro', 'Worin sich eine Website von uns von den üblichen Wegen unterscheidet.' ) ); ?></p>
    </div>
    <?php $c1l = digi_lp_t('cmp_col1','Website-Baukasten'); $c2l = digi_lp_t('cmp_col2','Klassische Agentur'); $c3l = digi_lp_t('cmp_col3','digirelation'); ?>
    <div class="compare-table reveal">
      <div class="ct-head">
        <div class="ct-crit"><?php echo esc_html( digi_lp_t('cmp_crit_label','Was zählt') ); ?></div>
        <div><?php echo esc_html( $c1l ); ?></div>
        <div><?php echo esc_html( $c2l ); ?></div>
        <div class="ct-digi"><?php echo esc_html( $c3l ); ?></div>
      </div>
      <?php foreach ( digi_lp_rows( 'compare_rows', $def_compare ) as $r ) : ?>
      <div class="ct-row">
        <div class="ct-crit"><?php echo esc_html( $r['crit'] ?? '' ); ?></div>
        <div class="ct-cell" data-col="<?php echo esc_attr( $c1l ); ?>"><?php echo esc_html( $r['c1'] ?? '' ); ?></div>
        <div class="ct-cell" data-col="<?php echo esc_attr( $c2l ); ?>"><?php echo esc_html( $r['c2'] ?? '' ); ?></div>
        <div class="ct-digi" data-col="<?php echo esc_attr( $c3l ); ?>"><svg viewBox="0 0 24 24" fill="none" stroke-width="2"><path d="M20 6L9 17l-5-5"/></svg><?php echo esc_html( $r['c3'] ?? '' ); ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ============ FEATURES (BENTO) ============ -->
<section class="features section-pad" id="features">
  <div class="wrap">
    <div class="feat-head reveal">
      <h2><?php echo esc_html( digi_lp_t( 'feat_h2', 'Das steckt in deiner neuen Website' ) ); ?></h2>
      <p><?php echo esc_html( digi_lp_t( 'feat_intro', 'Deine Seite läuft auf WordPress und ist von Grund auf für Tempo, Handy und Sichtbarkeit gebaut.' ) ); ?></p>
    </div>
    <div class="feat-grid">
      <?php $fimg = get_field('feat_image'); $fimg = is_array($fimg) ? ($fimg['url'] ?? '') : ( $fimg ?: $U.'about/ueberuns.png' ); ?>
      <div class="feat-img reveal"><img src="<?php echo esc_url( $fimg ); ?>" alt="<?php echo esc_attr( digi_lp_t('feat_accent_t','Für dein Geschäft gebaut') ); ?>" loading="lazy"></div>

      <div class="feat-card feat-accent reveal">
        <div class="ic"><?php echo digi_lp_icon( digi_lp_t('feat_accent_icon','info') ); ?></div>
        <h3><?php echo esc_html( digi_lp_t( 'feat_accent_t', 'Für dein Geschäft gebaut' ) ); ?></h3>
        <p><?php echo esc_html( digi_lp_t( 'feat_accent_p', 'Deine Seite entsteht neu für dein Geschäft und deinen Verkauf, statt fertig aus dem Baukasten zu kommen.' ) ); ?></p>
      </div>

      <div class="feat-card feat-tech reveal">
        <div class="ic"><?php echo digi_lp_icon( digi_lp_t('feat_tech_icon','monitor') ); ?></div>
        <h3><?php echo esc_html( digi_lp_t( 'feat_tech_t', 'Technik, die mitwächst' ) ); ?></h3>
        <ul class="feat-list"><?php echo digi_lp_checks( digi_lp_t( 'feat_tech_list', "Responsive auf Handy, Tablet und Desktop\nSchnelle Ladezeiten, auch mit vielen Bildern" ) ); ?></ul>
      </div>

      <div class="feat-card feat-find reveal">
        <div class="ic"><?php echo digi_lp_icon( digi_lp_t('feat_find_icon','search') ); ?></div>
        <h3><?php echo esc_html( digi_lp_t( 'feat_find_t', 'Gefunden werden' ) ); ?></h3>
        <ul class="feat-list"><?php echo digi_lp_checks( digi_lp_t( 'feat_find_list', "SEO-Grundlagen von Anfang an\nSauberer, schneller Code\nDSGVO-konform aufgesetzt" ) ); ?></ul>
      </div>
    </div>
  </div>
</section>

<!-- ============ ABLAUF ============ -->
<section class="how section-pad" id="ablauf">
  <div class="wrap">
    <div class="section-head reveal">
      <span class="eyebrow"><span class="dot"></span><?php echo esc_html( digi_lp_t( 'how_eyebrow', 'So läuft es ab' ) ); ?></span>
      <h2><?php echo esc_html( digi_lp_t( 'how_h2', 'So entsteht deine neue Website' ) ); ?></h2>
      <p><?php echo esc_html( digi_lp_t( 'how_intro', 'Vier Schritte, klar und ohne Überraschungen. Du weißt jederzeit, woran wir gerade arbeiten.' ) ); ?></p>
    </div>
    <?php $bimg = get_field('how_image'); $bimg = is_array($bimg) ? ($bimg['url'] ?? '') : ( $bimg ?: $U.'about/muki-alex-workspace.jpg' ); ?>
    <div class="how-banner reveal"><img src="<?php echo esc_url( $bimg ); ?>" alt="<?php echo esc_attr( digi_lp_t('how_h2','So entsteht deine neue Website') ); ?>" loading="lazy"></div>
    <div class="steps-row">
      <?php $sn = 0; foreach ( digi_lp_rows( 'steps', $def_steps ) as $st ) : $sn++; ?>
      <div class="hstep reveal">
        <div class="num"><?php echo $sn; ?></div>
        <h3><?php echo esc_html( $st['title'] ?? '' ); ?></h3>
        <p><?php echo esc_html( $st['text'] ?? '' ); ?></p>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ============ REFERENZEN ============ -->
<section class="section-pad" id="referenzen">
  <div class="wrap">
    <div class="section-head reveal">
      <span class="eyebrow"><span class="dot"></span><?php echo esc_html( digi_lp_t( 'ref_eyebrow', 'Referenzen' ) ); ?></span>
      <h2><?php echo esc_html( digi_lp_t( 'ref_h2', 'Echte Projekte aus 12 Branchen' ) ); ?></h2>
      <p><?php echo esc_html( digi_lp_t( 'ref_intro', 'Ein Auszug aus über 100 Projekten in 6 Ländern. Die Bandbreite reicht vom Energieberater bis zum Premium-Gin.' ) ); ?></p>
    </div>
    <div class="ref-grid">
      <?php foreach ( digi_lp_rows( 'references', $def_refs ) as $r ) : $ri = $r['image'] ?? ''; $ri = is_array($ri) ? ($ri['url'] ?? '') : $ri; ?>
      <div class="ref-card reveal">
        <div class="ref-thumb"><img src="<?php echo esc_url( $ri ); ?>" alt="<?php echo esc_attr( $r['name'] ?? '' ); ?>" loading="lazy"></div>
        <div class="ref-body">
          <h3><?php echo esc_html( $r['name'] ?? '' ); ?></h3>
          <div class="ref-meta"><?php echo esc_html( $r['branche'] ?? '' ); ?> <span class="geo">· <?php echo esc_html( $r['geo'] ?? '' ); ?></span></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ============ SOCIAL PROOF ============ -->
<section class="proof section-pad">
  <div class="wrap">
    <div class="section-head reveal">
      <span class="eyebrow"><span class="dot"></span><?php echo esc_html( digi_lp_t( 'proof_eyebrow', 'Kundenstimmen' ) ); ?></span>
      <h2><?php echo esc_html( digi_lp_t( 'proof_h2', 'Was Kunden über uns sagen' ) ); ?></h2>
    </div>
    <?php if ( $google_link ) : ?><a href="<?php echo esc_url( $google_link ); ?>" class="google-banner reveal" target="_blank" rel="noopener noreferrer"><?php else : ?><div class="google-banner reveal"><?php endif; ?>
      <svg class="gbig" viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.27-4.74 3.27-8.1z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84A11 11 0 0 0 12 23z"/><path fill="#FBBC05" d="M5.84 14.1a6.6 6.6 0 0 1 0-4.2V7.06H2.18a11 11 0 0 0 0 9.88l3.66-2.84z"/><path fill="#EA4335" d="M12 4.75c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 1.45 14.97.5 12 .5A11 11 0 0 0 2.18 7.06l3.66 2.84C6.71 7.3 9.14 5.37 12 5.37z"/></svg>
      <div>
        <div class="score"><?php echo esc_html( $google_rating ); ?></div>
        <div class="stars-lg">★★★★★</div>
      </div>
      <div class="meta">Bewertungen<br>auf Google</div>
    <?php if ( $google_link ) : ?></a><?php else : ?></div><?php endif; ?>
    <?php if ( is_array( $testimonials ) && $testimonials ) : ?>
    <div class="tcards">
      <?php foreach ( $testimonials as $t ) : $tn = $t['name'] ?? ''; $av = $tn ? ( function_exists( 'mb_substr' ) ? mb_strtoupper( mb_substr( $tn, 0, 1 ) ) : strtoupper( substr( $tn, 0, 1 ) ) ) : '?'; ?>
      <div class="tcard reveal">
        <div class="qs">&ldquo;</div>
        <p class="quote"><?php echo esc_html( $t['quote'] ?? '' ); ?></p>
        <div class="who"><span class="av"><?php echo esc_html( $av ); ?></span><div><b><?php echo esc_html( $tn ); ?></b><span><?php echo esc_html( $t['firma'] ?? '' ); ?></span></div></div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>

<!-- ============ LOGOS ============ -->
<section class="logos">
  <div class="wrap">
    <p><?php echo esc_html( digi_lp_t( 'logos_title', 'Vertraut von Unternehmen in 6 Ländern' ) ); ?></p>
    <div class="logo-strip">
      <?php foreach ( digi_lp_rows( 'logos', $def_logos ) as $l ) : ?><span><?php echo esc_html( $l['name'] ?? '' ); ?></span><?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ============ FAQ ============ -->
<section class="faq section-pad" id="faq">
  <div class="wrap">
    <div class="section-head reveal">
      <span class="eyebrow"><span class="dot"></span><?php echo esc_html( digi_lp_t( 'faq_eyebrow', 'Häufige Fragen' ) ); ?></span>
      <h2><?php echo esc_html( digi_lp_t( 'faq_h2', 'Was du dich vor dem Start fragst' ) ); ?></h2>
    </div>
    <div class="faq-list">
      <?php foreach ( digi_lp_rows( 'faqs', $def_faq ) as $f ) : ?>
      <div class="faq-item reveal">
        <button class="faq-q" aria-expanded="false">
          <span><?php echo esc_html( $f['frage'] ?? '' ); ?></span>
          <svg class="faq-ic" viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
        </button>
        <div class="faq-a"><p><?php echo esc_html( $f['antwort'] ?? '' ); ?></p></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ============ CTA ============ -->
<section class="cta section-pad" id="check">
  <div class="wrap">
    <div class="cta-panel reveal">
      <span class="eyebrow"><span class="dot"></span><?php echo esc_html( digi_lp_t( 'cta_eyebrow', 'Strategiegespräch' ) ); ?></span>
      <h2><?php echo esc_html( digi_lp_t( 'cta_h2', 'Finde heraus, warum deine Website keine Anfragen bringt' ) ); ?></h2>
      <p><?php echo esc_html( digi_lp_t( 'cta_p', 'Erzähl uns von deiner aktuellen Seite und deinem Ziel. Im Strategiegespräch bekommst du eine ehrliche Einschätzung, woran es liegt und was sich zuerst lohnt.' ) ); ?></p>
      <ul class="cta-list"><?php echo digi_lp_checks( digi_lp_t( 'cta_checks', $def_cta_checks ) ); ?></ul>
      <a href="<?php echo esc_url( $cta_url ); ?>" class="btn btn-primary btn-lg"><?php echo esc_html( $cta_btn ); ?></a>
      <div class="form-foot">
        <svg viewBox="0 0 24 24" fill="none" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
        <?php echo esc_html( digi_lp_t( 'cta_foot', 'Unverbindlich und keine Akquise-Anrufe ohne deine Zustimmung' ) ); ?>
      </div>
    </div>
  </div>
</section>

<!-- ============ FOOTER ============ -->
<footer>
  <div class="wrap">
    <div class="foot-grid">
      <a href="#top" class="logo"><span class="mark">d</span> <?php echo esc_html( digi_lp_t( 'brand', 'digirelation' ) ); ?></a>
      <nav class="foot-links">
        <a href="#problem">Problem</a>
        <a href="#loesung">Lösung</a>
        <a href="#referenzen">Referenzen</a>
        <a href="https://www.digirelation.com/referenzen/" target="_blank" rel="noopener">Alle Referenzen</a>
        <a href="https://www.digirelation.com/impressum/" target="_blank" rel="noopener">Impressum</a>
        <a href="https://www.digirelation.com/datenschutz/" target="_blank" rel="noopener">Datenschutz</a>
        <a href="<?php echo esc_url( $cta_url ); ?>"><?php echo esc_html( $cta_btn ); ?></a>
      </nav>
    </div>
    <div class="copy"><?php echo esc_html( digi_lp_t( 'footer_copy', '© 2026 digirelation. Webdesign mit Performance-Fokus.' ) ); ?></div>
  </div>
</footer>
</div>
