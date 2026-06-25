<?php
defined( 'ABSPATH' ) || exit;
/** @var int|null $property_id */
$privacy_text    = get_option( 'propstack_re_privacy_text', 'Ich stimme der Verarbeitung meiner Daten gemäß der Datenschutzerklärung zu.' );
$privacy_page_id = get_option( 'propstack_re_privacy_page', 0 );
$privacy_url     = $privacy_page_id ? get_permalink( (int) $privacy_page_id ) : '';
$prop_title      = $property_id ? get_the_title( $property_id ) : '';
$form_id         = 'propstack-form-' . ( $property_id ?: 'general' );
?>
<div class="propstack-contact-form" id="<?php echo esc_attr( $form_id ); ?>">
    <h3 class="propstack-contact-form__title">
        <?php echo $property_id ? esc_html__( 'Anfrage zu diesem Objekt', 'propstack-re' ) : esc_html__( 'Kontakt aufnehmen', 'propstack-re' ); ?>
    </h3>

    <form class="propstack-contact-form__form" novalidate>
        <?php wp_nonce_field( 'propstack_re_nonce', 'nonce' ); ?>
        <input type="hidden" name="action"         value="propstack_re_submit">
        <input type="hidden" name="property_id"    value="<?php echo esc_attr( $property_id ?? '' ); ?>">
        <input type="hidden" name="property_title" value="<?php echo esc_attr( $prop_title ); ?>">
        <input type="hidden" name="source_url"     value="<?php echo esc_url( get_permalink() ?: home_url( $_SERVER['REQUEST_URI'] ) ); ?>">
        <input type="hidden" name="_time"          value="<?php echo time(); ?>">
        <!-- Honeypot -->
        <input type="text" name="_gotcha" tabindex="-1" autocomplete="off" style="position:absolute;left:-9999px;opacity:0;" aria-hidden="true">

        <div class="propstack-form__row propstack-form__row--two">
            <div class="propstack-form__group">
                <label for="<?php echo esc_attr( $form_id ); ?>-first-name"><?php esc_html_e( 'Vorname', 'propstack-re' ); ?> <span aria-hidden="true">*</span></label>
                <input type="text" id="<?php echo esc_attr( $form_id ); ?>-first-name" name="first_name" required autocomplete="given-name">
                <span class="propstack-form__error" data-field="first_name"></span>
            </div>
            <div class="propstack-form__group">
                <label for="<?php echo esc_attr( $form_id ); ?>-last-name"><?php esc_html_e( 'Nachname', 'propstack-re' ); ?> <span aria-hidden="true">*</span></label>
                <input type="text" id="<?php echo esc_attr( $form_id ); ?>-last-name" name="last_name" required autocomplete="family-name">
                <span class="propstack-form__error" data-field="last_name"></span>
            </div>
        </div>

        <div class="propstack-form__row propstack-form__row--two">
            <div class="propstack-form__group">
                <label for="<?php echo esc_attr( $form_id ); ?>-email"><?php esc_html_e( 'E-Mail', 'propstack-re' ); ?> <span aria-hidden="true">*</span></label>
                <input type="email" id="<?php echo esc_attr( $form_id ); ?>-email" name="email" required autocomplete="email">
                <span class="propstack-form__error" data-field="email"></span>
            </div>
            <div class="propstack-form__group">
                <label for="<?php echo esc_attr( $form_id ); ?>-phone"><?php esc_html_e( 'Telefon', 'propstack-re' ); ?> <span aria-hidden="true">*</span></label>
                <input type="tel" id="<?php echo esc_attr( $form_id ); ?>-phone" name="phone" required autocomplete="tel">
                <span class="propstack-form__error" data-field="phone"></span>
            </div>
        </div>

        <div class="propstack-form__group">
            <label for="<?php echo esc_attr( $form_id ); ?>-message"><?php esc_html_e( 'Nachricht', 'propstack-re' ); ?></label>
            <textarea id="<?php echo esc_attr( $form_id ); ?>-message" name="message" rows="4" placeholder="<?php esc_attr_e( 'Ihre Nachricht...', 'propstack-re' ); ?>"></textarea>
        </div>

        <div class="propstack-form__group propstack-form__group--checkbox">
            <label>
                <input type="checkbox" name="privacy" value="1" required>
                <span>
                    <?php if ( $privacy_url ) : ?>
                        <?php echo wp_kses( str_replace(
                            '{link_start}',
                            '<a href="' . esc_url( $privacy_url ) . '" target="_blank" rel="noopener">',
                            str_replace( '{link_end}', '</a>', $privacy_text )
                        ), [ 'a' => [ 'href' => [], 'target' => [], 'rel' => [] ] ] ); ?>
                    <?php else : ?>
                        <?php echo wp_kses_post( $privacy_text ); ?>
                    <?php endif; ?>
                    <span aria-hidden="true"> *</span>
                </span>
            </label>
            <span class="propstack-form__error" data-field="privacy"></span>
        </div>

        <div class="propstack-form__actions">
            <button type="submit" class="propstack-btn propstack-btn--primary propstack-btn--submit">
                <span class="propstack-btn__text"><?php esc_html_e( 'Anfrage senden', 'propstack-re' ); ?></span>
                <span class="propstack-btn__loading" hidden><?php esc_html_e( 'Wird gesendet…', 'propstack-re' ); ?></span>
            </button>
        </div>

        <div class="propstack-form__feedback" role="alert" aria-live="polite"></div>
    </form>
</div>
