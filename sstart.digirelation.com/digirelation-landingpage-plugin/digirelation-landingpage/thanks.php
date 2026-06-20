<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$f = function( $name, $default = '' ) {
	if ( function_exists( 'get_field' ) ) {
		$v = get_field( $name );
		if ( $v !== false && $v !== null && $v !== '' ) return $v;
	}
	return $default;
};

$lp_url       = esc_url( $f( 'thanks_lp_url', home_url( '/performance-landingpage/' ) ) );
$confirm      = esc_html( $f( 'thanks_confirm', 'Deine Anfrage ist eingegangen' ) );
$eyebrow      = esc_html( $f( 'thanks_eyebrow', 'Letzter Schritt' ) );
$h1           = esc_html( $f( 'thanks_h1', 'Sicher dir jetzt deinen Termin mit Fabio' ) );
$sub          = esc_html( $f( 'thanks_sub', 'Damit du nicht auf einen Rückruf wartest, suchst du dir gleich selbst einen Slot aus. Im Gespräch geht Fabio deine aktuelle Seite mit dir durch. Du erfährst, woran es hängt, und bekommst eine konkrete Strategie, wie du deine Seite sofort besser machst.' ) );
$host_name    = esc_html( $f( 'thanks_host_name', 'Fabio Scatigna' ) );
$host_role    = esc_html( $f( 'thanks_host_role', 'Key Account & Success' ) );
$host_company = esc_html( $f( 'thanks_host_company', 'digirelation' ) );
$cal_url      = esc_url( $f( 'thanks_cal_url', 'https://cal.com/fabio-scatigna-ncbbbr/45min' ) );
$book_title   = esc_html( $f( 'thanks_book_title', 'Termin wählen' ) );
$book_dur     = esc_html( $f( 'thanks_book_duration', '◷ 30 Min' ) );
$book_sub     = esc_html( $f( 'thanks_book_sub', 'Wähl einen Tag und eine Uhrzeit, die dir passen. Den Rest erledigen wir im Gespräch.' ) );
$book_btn     = esc_html( $f( 'thanks_book_btn', 'Termin mit Fabio buchen' ) );
$book_note    = esc_html( $f( 'thanks_book_note', 'Du landest direkt im Kalender und wählst Tag und Uhrzeit, die dir passen.' ) );
$book_foot    = esc_html( $f( 'thanks_book_foot', '✓ Unverbindlich, du kannst den Termin jederzeit verschieben' ) );
$google_rat   = esc_html( $f( 'thanks_google_rating', '4,9 von 5' ) );
$kpi1_num     = esc_html( $f( 'thanks_kpi1_num', '100+' ) );
$kpi1_label   = esc_html( $f( 'thanks_kpi1_label', 'Projekte' ) );
$kpi2_num     = esc_html( $f( 'thanks_kpi2_num', '6' ) );
$kpi2_label   = esc_html( $f( 'thanks_kpi2_label', 'Länder' ) );

$raw_benefits = function_exists( 'get_field' ) ? get_field( 'thanks_benefits' ) : null;
if ( ! $raw_benefits ) {
	$raw_benefits = array(
		array( 'bold' => 'Wir schauen live auf deine Seite',      'rest' => ' und gehen sie gemeinsam Stück für Stück durch.' ),
		array( 'bold' => 'Du erfährst, woran es hängt,',          'rest' => ' dass heute zu wenig Anfragen reinkommen.' ),
		array( 'bold' => 'Du bekommst eine konkrete Strategie,',  'rest' => ' mit der du deine Seite sofort verbesserst, auch wenn du sie selbst umsetzt.' ),
	);
}
?>
<div class="digi-lp digi-thanks">
  <header class="thanks-header">
    <div class="wrap nav">
      <a href="<?php echo $lp_url; ?>" class="logo"><span class="mark">d</span> digirelation</a>
      <a href="<?php echo $lp_url; ?>" class="thanks-back">← Zurück zur Übersicht</a>
    </div>
  </header>

  <main class="thanks-main">
    <div class="wrap">
      <div class="thanks-confirm" role="status">
        <span class="thanks-confirm-icon" aria-hidden="true">✓</span>
        <?php echo $confirm; ?>
      </div>

      <div class="thanks-grid">
        <div class="thanks-lead">
          <span class="eyebrow"><span class="dot"></span><?php echo $eyebrow; ?></span>
          <h1><?php echo $h1; ?></h1>
          <p class="thanks-sub"><?php echo $sub; ?></p>

          <div class="thanks-host">
            <img src="<?php echo esc_url( DIGI_LP_URL . 'assets/team/fabio-s.webp' ); ?>" alt="<?php echo $host_name; ?>, <?php echo $host_role; ?> bei <?php echo $host_company; ?>" width="62" height="62">
            <div>
              <b><?php echo $host_name; ?></b>
              <span><em><?php echo $host_role; ?></em> bei <?php echo $host_company; ?></span>
            </div>
          </div>

          <ul class="thanks-benefits">
            <?php foreach ( $raw_benefits as $b ) : ?>
            <li>
              <span aria-hidden="true">✓</span>
              <div><b><?php echo esc_html( $b['bold'] ); ?></b><?php echo esc_html( $b['rest'] ); ?></div>
            </li>
            <?php endforeach; ?>
          </ul>

          <div class="thanks-trust">
            <div class="google-badge">
              <div class="rate"><div class="stars">★★★★★</div><b><?php echo $google_rat; ?></b><span>Bewertungen auf Google</span></div>
            </div>
            <div class="thanks-kpi"><b><?php echo $kpi1_num; ?></b><span><?php echo $kpi1_label; ?></span></div>
            <div class="thanks-kpi"><b><?php echo $kpi2_num; ?></b><span><?php echo $kpi2_label; ?></span></div>
          </div>
        </div>

        <section class="thanks-book" aria-labelledby="thanks-book-title">
          <div class="thanks-book-head">
            <h2 id="thanks-book-title"><?php echo $book_title; ?></h2>
            <span class="thanks-duration"><?php echo $book_dur; ?></span>
          </div>
          <p><?php echo $book_sub; ?></p>
          <a href="<?php echo $cal_url; ?>" target="_blank" rel="noopener noreferrer" class="btn btn-primary btn-lg thanks-book-button"><?php echo $book_btn; ?></a>
          <p class="thanks-book-note"><?php echo $book_note; ?></p>
          <div class="thanks-book-foot"><?php echo $book_foot; ?></div>
        </section>
      </div>
    </div>
  </main>

  <footer>
    <div class="wrap foot-grid">
      <a href="<?php echo $lp_url; ?>" class="logo"><span class="mark">d</span> digirelation</a>
      <nav class="foot-links">
        <a href="<?php echo esc_url( $lp_url . '#problem' ); ?>">Problem</a>
        <a href="<?php echo esc_url( $lp_url . '#loesung' ); ?>">Lösung</a>
        <a href="<?php echo esc_url( $lp_url . '#referenzen' ); ?>">Referenzen</a>
        <a href="https://www.digirelation.com/impressum/" target="_blank" rel="noopener noreferrer">Impressum</a>
        <a href="https://www.digirelation.com/datenschutz/" target="_blank" rel="noopener noreferrer">Datenschutz</a>
      </nav>
    </div>
  </footer>
</div>
