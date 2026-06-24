<?php
/**
 * Tipp einsenden — 3-шаговая форма (Das Objekt → Die Situation → Ihre Daten).
 * Видимая форма заполняет скрытую WPForms-форму (мост в view.js) и шлёт её нативно.
 * @package library
 */
defined( 'ABSPATH' ) || exit;

$a = wp_parse_args( $attributes, [
	'heading'   => 'Tipp einsenden',
	'lead'      => '',
	'formSlug'  => 'tippgeber',
	'wpformsId' => 0,
] );

// WPForms-форма: явный wpformsId → форма по slug → первая существующая.
$form_slug = $a['formSlug'] ? $a['formSlug'] : 'tippgeber';
$form_id   = (int) $a['wpformsId'];
if ( ! $form_id ) {
	$f = get_posts( [ 'post_type' => 'wpforms', 'name' => $form_slug, 'posts_per_page' => 1, 'post_status' => 'publish' ] );
	if ( empty( $f ) ) {
		$f = get_posts( [ 'post_type' => 'wpforms', 'posts_per_page' => 1, 'post_status' => 'publish', 'orderby' => 'date', 'order' => 'ASC' ] );
	}
	if ( ! empty( $f ) ) {
		$form_id = $f[0]->ID;
	}
}

// Маппинг slug → WPForms field-ID по label поля.
$field_map = [];
if ( $form_id ) {
	$fp = get_post( $form_id );
	$fd = $fp ? ( function_exists( 'wpforms_decode' ) ? wpforms_decode( $fp->post_content ) : json_decode( $fp->post_content, true ) ) : null;
	$by_label = [
		'anrede'   => 'Anrede',
		'vorname'  => 'Vorname',
		'nachname' => 'Nachname',
		'email'    => 'Email',
		'telefon'  => 'Telefon',
		'summary'  => 'Objektangaben',
	];
	if ( ! empty( $fd['fields'] ) ) {
		foreach ( $fd['fields'] as $fid => $field ) {
			$label = isset( $field['label'] ) ? $field['label'] : '';
			foreach ( $by_label as $slug => $want ) {
				if ( strcasecmp( $label, $want ) === 0 ) {
					$field_map[ $slug ] = (int) $fid;
				}
			}
		}
	}
}

$has_form = $form_id && function_exists( 'wpforms' ) && ! empty( $field_map );

$objektarten = [ 'Haus', 'Wohnung', 'Grundstück', 'Gewerbe', 'Sonstiges' ];
$bezug       = [ 'Nachbar / Bekannter', 'Familie / Angehöriger', 'Beruflicher Kontakt', 'Eigentümer', 'Sonstiges' ];
?>
<section <?php echo get_block_wrapper_attributes( [ 'class' => 'tip-form' ] ); // phpcs:ignore WordPress.Security.EscapeOutput ?>>
	<div class="tip-form__header">
		<?php if ( $a['heading'] ) : ?>
			<h2 class="tip-form__heading"><?php echo esc_html( $a['heading'] ); ?></h2>
		<?php endif; ?>
		<?php if ( $a['lead'] ) : ?>
			<p class="tip-form__lead"><?php echo esc_html( $a['lead'] ); ?></p>
		<?php endif; ?>
	</div>

	<form class="tip-form__card"
		novalidate
		data-tip-form
		data-wpforms-id="<?php echo esc_attr( $form_id ); ?>"
		data-wpforms-fields="<?php echo esc_attr( wp_json_encode( $field_map ) ); ?>"
	>
		<ol class="tip-form__steps" aria-hidden="true">
			<li class="tip-form__step is-active" data-step-ind="1"><span class="tip-form__dot">1</span><span class="tip-form__step-label">Das Objekt</span></li>
			<li class="tip-form__step" data-step-ind="2"><span class="tip-form__dot">2</span><span class="tip-form__step-label">Die Situation</span></li>
			<li class="tip-form__step" data-step-ind="3"><span class="tip-form__dot">3</span><span class="tip-form__step-label">Ihre Daten</span></li>
		</ol>

		<!-- Schritt 1 — Das Objekt -->
		<div class="tip-form__panel" data-step="1">
			<div class="tip-form__field">
				<label class="tip-form__label" for="tf-adresse">Adresse der Immobilie *</label>
				<input class="tip-form__input" id="tf-adresse" type="text" data-tf="adresse" required
					placeholder="z.B. Goethestraße 12, 6800 Feldkirch" />
				<p class="tip-form__hint">Eine ungefähre Ortsangabe reicht, wenn die genaue Adresse nicht bekannt ist.</p>
			</div>
			<div class="tip-form__field">
				<label class="tip-form__label" for="tf-objektart">Objektart</label>
				<div class="tip-form__select">
					<select class="tip-form__input" id="tf-objektart" data-tf="objektart">
						<option value="">Bitte wählen (optional)</option>
						<?php foreach ( $objektarten as $opt ) : ?>
							<option value="<?php echo esc_attr( $opt ); ?>"><?php echo esc_html( $opt ); ?></option>
						<?php endforeach; ?>
					</select>
					<svg class="tip-form__caret" width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M6 8l4 4 4-4" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
				</div>
			</div>
			<div class="tip-form__actions tip-form__actions--end">
				<button class="tip-form__btn tip-form__btn--primary" type="button" data-next>Weiter →</button>
			</div>
		</div>

		<!-- Schritt 2 — Die Situation -->
		<div class="tip-form__panel" data-step="2" hidden>
			<div class="tip-form__field">
				<label class="tip-form__label" for="tf-bezug">Ihr Bezug zur Immobilie</label>
				<div class="tip-form__select">
					<select class="tip-form__input" id="tf-bezug" data-tf="bezug">
						<option value="">Bitte wählen (optional)</option>
						<?php foreach ( $bezug as $opt ) : ?>
							<option value="<?php echo esc_attr( $opt ); ?>"><?php echo esc_html( $opt ); ?></option>
						<?php endforeach; ?>
					</select>
					<svg class="tip-form__caret" width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M6 8l4 4 4-4" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
				</div>
			</div>
			<div class="tip-form__field">
				<label class="tip-form__label" for="tf-situation">Was wissen Sie über die Situation?</label>
				<textarea class="tip-form__input tip-form__textarea" id="tf-situation" data-tf="situation"
					placeholder="Kurz: warum ein Verkauf ansteht oder wahrscheinlich ist (z.B. Erbschaft, Umzug, Leerstand)."></textarea>
				<p class="tip-form__hint">Optional. Jeder Hinweis hilft mir, das Gespräch passend vorzubereiten.</p>
			</div>
			<div class="tip-form__actions">
				<button class="tip-form__btn tip-form__btn--ghost" type="button" data-back>← Zurück</button>
				<button class="tip-form__btn tip-form__btn--primary" type="button" data-next>Weiter →</button>
			</div>
		</div>

		<!-- Schritt 3 — Ihre Daten -->
		<div class="tip-form__panel" data-step="3" hidden>
			<div class="tip-form__field">
				<label class="tip-form__label" for="tf-anrede">Anrede</label>
				<div class="tip-form__select">
					<select class="tip-form__input" id="tf-anrede" data-tf="anrede">
						<option value="">Bitte wählen</option>
						<option>Frau</option>
						<option>Herr</option>
						<option>Divers</option>
					</select>
					<svg class="tip-form__caret" width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M6 8l4 4 4-4" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
				</div>
			</div>
			<div class="tip-form__row2">
				<div class="tip-form__field">
					<label class="tip-form__label" for="tf-vorname">Vorname</label>
					<input class="tip-form__input" id="tf-vorname" type="text" data-tf="vorname" />
				</div>
				<div class="tip-form__field">
					<label class="tip-form__label" for="tf-nachname">Nachname</label>
					<input class="tip-form__input" id="tf-nachname" type="text" data-tf="nachname" />
				</div>
			</div>
			<div class="tip-form__field">
				<label class="tip-form__label" for="tf-email">E-Mail-Adresse *</label>
				<input class="tip-form__input" id="tf-email" type="email" data-tf="email" required placeholder="name@beispiel.at" />
			</div>
			<div class="tip-form__field">
				<label class="tip-form__label" for="tf-telefon">Telefonnummer</label>
				<input class="tip-form__input" id="tf-telefon" type="tel" data-tf="telefon" placeholder="+43 ..." />
			</div>
			<p class="tip-form__legal">Mit dem Absenden stellen Sie eine Tippgeber-Anfrage und bestätigen, dass Sie die <a href="/datenschutz/">Datenschutzerklärung</a> zur Kenntnis genommen haben.</p>
			<div class="tip-form__actions">
				<button class="tip-form__btn tip-form__btn--ghost" type="button" data-back>← Zurück</button>
				<button class="tip-form__btn tip-form__btn--primary" type="submit"<?php echo $has_form ? '' : ' disabled'; ?>>Tipp absenden</button>
			</div>
			<p class="tip-form__error" data-tf-error hidden>Übermittlung fehlgeschlagen – bitte erneut versuchen.</p>
			<?php if ( ! $has_form ) : ?>
				<p class="tip-form__error" data-tf-static>Das Formular ist noch nicht eingerichtet.</p>
			<?php endif; ?>
		</div>
	</form>

	<?php if ( $has_form ) : ?>
		<div aria-hidden="true" style="position:absolute;left:-99999px;top:0;width:1px;height:1px;overflow:hidden">
			<?php echo do_shortcode( '[wpforms id="' . $form_id . '" title="false" description="false"]' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</div>
	<?php endif; ?>
</section>
