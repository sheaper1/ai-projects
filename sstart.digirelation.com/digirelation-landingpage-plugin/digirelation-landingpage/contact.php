<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$pid    = 1055;
$status = sanitize_key( wp_unslash( $_GET['contact_status'] ?? '' ) );

if ( ! function_exists( 'digi_ct' ) ) {
	function digi_ct( $key, $default = '' ) {
		$v = get_field( $key, 1055 );
		return ( $v === null || $v === '' ) ? $default : $v;
	}
}

$lp_url    = digi_ct( 'contact_lp_url', home_url( '/performance-landingpage/' ) );
$eyebrow   = digi_ct( 'contact_eyebrow', 'Strategiegespräch' );
$h1        = digi_ct( 'contact_h1', 'Finde heraus, warum deine Website keine Anfragen bringt' );
$sub       = digi_ct( 'contact_sub', 'Erzähl uns von deiner aktuellen Seite und deinem Ziel. Im Strategiegespräch bekommst du eine ehrliche Einschätzung, woran es liegt und was sich zuerst lohnt.' );
$g_rating  = digi_ct( 'contact_google_rating', '4,9' );
$g_reviews = get_field( 'contact_google_reviews', $pid );
$g_link = get_field( 'contact_google_link', $pid );
if ( ! $g_link ) {
	$lp = get_post( 1054 );
	if ( $lp ) {
		foreach ( parse_blocks( $lp->post_content ) as $b ) {
			if ( ( $b['blockName'] ?? '' ) === 'digirelation/landingpage' ) {
				$g_link = $b['attrs']['data']['google_link'] ?? '';
				break;
			}
		}
	}
}
$form_h2   = digi_ct( 'contact_form_h2', 'Strategiegespräch anfragen' );
$form_sub  = digi_ct( 'contact_form_sub', 'Trag deine Daten ein. Wir melden uns innerhalb von 24 Stunden.' );
$submit    = digi_ct( 'contact_submit', 'Strategiegespräch anfragen' );
$foot      = digi_ct( 'contact_foot', 'Unverbindlich und keine Akquise-Anrufe ohne deine Zustimmung' );

$values = get_field( 'contact_values', $pid );
if ( ! is_array( $values ) || ! $values ) {
	$values = array(
		array( 'bold' => 'Konkrete Schwachstellen', 'text' => ' statt allgemeiner Tipps, an deiner echten Seite geprüft.' ),
		array( 'bold' => 'Ein klarer Plan', 'text' => ' für die neue Website, auf deinen Verkauf zugeschnitten.' ),
		array( 'bold' => 'Unverbindlich.', 'text' => ' Antwort innerhalb von 24 Stunden.' ),
	);
}
?>
<div class="digi-lp digi-contact">
  <header class="contact-header">
    <div class="wrap nav">
      <a href="<?php echo esc_url( $lp_url ); ?>" class="logo"><span class="mark">d</span> digirelation</a>
      <a href="<?php echo esc_url( $lp_url ); ?>" class="contact-back">← Zurück zur Übersicht</a>
    </div>
  </header>

  <main class="contact-main">
    <div class="wrap contact-grid">
      <div class="contact-lead">
        <span class="eyebrow"><span class="dot"></span><?php echo esc_html( $eyebrow ); ?></span>
        <h1><?php echo esc_html( $h1 ); ?></h1>
        <p class="contact-sub"><?php echo esc_html( $sub ); ?></p>
        <ul class="contact-values">
          <?php foreach ( $values as $v ) : ?>
          <li><span class="contact-check">✓</span><span><b><?php echo esc_html( $v['bold'] ?? '' ); ?></b><?php echo esc_html( $v['text'] ?? '' ); ?></span></li>
          <?php endforeach; ?>
        </ul>
        <div class="contact-trust">
          <?php if ( $g_link ) : ?><a href="<?php echo esc_url( $g_link ); ?>" class="google-badge" target="_blank" rel="noopener noreferrer"><?php else : ?><div class="google-badge"><?php endif; ?>
            <svg class="g" viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.27-4.74 3.27-8.1z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84A11 11 0 0 0 12 23z"/><path fill="#FBBC05" d="M5.84 14.1a6.6 6.6 0 0 1 0-4.2V7.06H2.18a11 11 0 0 0 0 9.88l3.66-2.84z"/><path fill="#EA4335" d="M12 4.75c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 1.45 14.97.5 12 .5A11 11 0 0 0 2.18 7.06l3.66 2.84C6.71 7.3 9.14 5.37 12 5.37z"/></svg>
            <div class="rate"><div class="stars">★★★★★</div><b><?php echo esc_html( $g_rating ); ?> von 5</b><span><?php echo $g_reviews ? esc_html( $g_reviews ) . ' Bewertungen auf Google' : 'Bewertungen auf Google'; ?></span></div>
          <?php if ( $g_link ) : ?></a><?php else : ?></div><?php endif; ?>
          <div class="kpi"><b>100+</b><span>Projekte</span></div>
          <div class="kpi"><b>6</b><span>Länder</span></div>
        </div>
      </div>

      <form class="form-card contact-form" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
        <input type="hidden" name="action" value="digi_lp_contact">
        <?php wp_nonce_field( 'digi_lp_contact', 'digi_lp_nonce' ); ?>
        <div class="contact-hp" aria-hidden="true"><label>Website bestätigen<input name="website_confirm" type="text" tabindex="-1" autocomplete="off"></label></div>
        <h2><?php echo esc_html( $form_h2 ); ?></h2>
        <p class="fsub"><?php echo esc_html( $form_sub ); ?></p>

        <?php if ( 'sent' === $status ) : ?>
          <div class="contact-notice success" role="status">Danke! Deine Anfrage wurde gesendet. Wir melden uns innerhalb von 24 Stunden.</div>
        <?php elseif ( 'invalid' === $status ) : ?>
          <div class="contact-notice error" role="alert">Bitte prüfe die Pflichtfelder und deine E-Mail-Adresse.</div>
        <?php elseif ( 'error' === $status ) : ?>
          <div class="contact-notice error" role="alert">Die Nachricht konnte nicht gesendet werden. Bitte versuche es erneut.</div>
        <?php endif; ?>

        <div class="field"><label for="company">Unternehmen</label><input id="company" name="company" type="text" autocomplete="organization" required></div>
        <div class="row2">
          <div class="field"><label for="first">Vorname</label><input id="first" name="first" type="text" autocomplete="given-name" required></div>
          <div class="field"><label for="last">Nachname</label><input id="last" name="last" type="text" autocomplete="family-name"></div>
        </div>
        <div class="field"><label for="email">E-Mail</label><input id="email" name="email" type="email" autocomplete="email" required></div>
        <div class="field"><label for="phone">Mobilnummer</label><input id="phone" name="phone" type="tel" autocomplete="tel" required></div>
        <div class="field"><label for="url">Deine aktuelle Website</label><input id="url" name="url" type="url" inputmode="url" placeholder="https://"></div>
        <button type="submit" class="btn btn-primary btn-lg"><?php echo esc_html( $submit ); ?></button>
        <div class="form-foot">✓ <?php echo esc_html( $foot ); ?></div>
      </form>
    </div>
  </main>

  <footer>
    <div class="wrap foot-grid">
      <a href="<?php echo esc_url( $lp_url ); ?>" class="logo"><span class="mark">d</span> digirelation</a>
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
