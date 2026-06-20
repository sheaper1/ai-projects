<?php
/**
 * Plugin Name:       digirelation – Performance Landingpage
 * Description:        ACF-Block, der die komplette Performance-Landingpage rendert (dunkles CI-Design). Bilder im Plugin gebündelt, alle Texte/Bilder via ACF (nach Sektionen gruppiert) editierbar. Benötigt ACF Pro 6+.
 * Version:           1.9.3
 * Author:            digirelation
 * Text Domain:       digirelation-landingpage
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'DIGI_LP_VER', '1.9.3' );
define( 'DIGI_LP_URL', plugin_dir_url( __FILE__ ) );
define( 'DIGI_LP_DIR', plugin_dir_path( __FILE__ ) );

/**
 * ACF-Block registrieren (über block.json).
 */
add_action( 'init', function () {
	register_block_type( DIGI_LP_DIR . 'blocks/landingpage' );
	register_block_type( DIGI_LP_DIR . 'blocks/thanks' );
	add_shortcode( 'digirelation_contact', function () {
		ob_start();
		require DIGI_LP_DIR . 'contact.php';
		return ob_get_clean();
	} );
	add_shortcode( 'digirelation_thanks', function () {
		ob_start();
		require DIGI_LP_DIR . 'thanks.php';
		return ob_get_clean();
	} );
} );

/**
 * Create the thank-you page once on install/update and keep its ID stable.
 */
function digi_lp_ensure_thanks_page() {
	if ( get_option( 'digi_lp_version' ) === DIGI_LP_VER ) {
		return;
	}

	$block_content = '<!-- wp:digirelation/thanks {"align":"full"} /-->';

	$page = get_page_by_path( 'vielen-dank', OBJECT, 'page' );
	if ( ! $page ) {
		$page_id = wp_insert_post(
			array(
				'post_title'   => 'Vielen Dank',
				'post_name'    => 'vielen-dank',
				'post_status'  => 'publish',
				'post_type'    => 'page',
				'post_content' => $block_content,
			),
			true
		);
		if ( ! is_wp_error( $page_id ) ) {
			update_option( 'digi_lp_thanks_page_id', (int) $page_id );
		}
	} else {
		wp_update_post( array(
			'ID'           => $page->ID,
			'post_content' => $block_content,
		) );
		delete_post_meta( $page->ID, '_elementor_data' );
		delete_post_meta( $page->ID, '_elementor_edit_mode' );
		delete_post_meta( $page->ID, '_elementor_template_type' );
		update_option( 'digi_lp_thanks_page_id', (int) $page->ID );
	}

	update_option( 'digi_lp_version', DIGI_LP_VER );
}
add_action( 'init', 'digi_lp_ensure_thanks_page', 20 );

/**
 * Stile laden – im Frontend (wenn Block vorhanden) UND im Block-Editor (Vorschau).
 * enqueue_block_assets greift auch in den Editor-Iframe.
 */
add_action( 'enqueue_block_assets', function () {
	$in_editor = is_admin();
	$is_contact = function_exists( 'is_page' ) && is_page( 1055 );
	$is_thanks = function_exists( 'is_page' ) && is_page( (int) get_option( 'digi_lp_thanks_page_id' ) );
	$has_thanks_block = function_exists( 'has_block' ) && ( has_block( 'digirelation/thanks' ) || $is_thanks );
	if ( ! $in_editor && ! $is_contact && ! $has_thanks_block && ! ( function_exists( 'has_block' ) && has_block( 'digirelation/landingpage' ) ) ) {
		return;
	}
	wp_enqueue_style( 'digi-lp-font', 'https://api.fontshare.com/v2/css?f[]=satoshi@300,400,500,600,700,900&display=swap', array(), null );
	wp_enqueue_style( 'digi-lp', DIGI_LP_URL . 'assets/css/landing.css', array(), DIGI_LP_VER );

	// Im Editor: Reveal-Animation (startet auf opacity:0) deaktivieren, sonst bleibt Inhalt unsichtbar.
	if ( $in_editor ) {
		wp_add_inline_style( 'digi-lp', '.digi-lp .reveal{opacity:1 !important;transform:none !important}' );
	}
} );

/**
 * Страница контакта хранится как отдельная WP-страница, а разметка и форма — в плагине.
 */
add_filter( 'the_content', function ( $content ) {
	if ( ! is_admin() && is_page( 1055 ) && in_the_loop() && is_main_query() ) {
		return do_shortcode( '[digirelation_contact]' );
	}
	if ( ! is_admin() && is_page( (int) get_option( 'digi_lp_thanks_page_id' ) ) && in_the_loop() && is_main_query() ) {
		return do_shortcode( '[digirelation_thanks]' );
	}
	return $content;
}, 20 );

add_filter( 'wp_robots', function ( $robots ) {
	if ( is_page( (int) get_option( 'digi_lp_thanks_page_id' ) ) ) {
		$robots['noindex']  = true;
		$robots['nofollow'] = true;
	}
	return $robots;
} );

/**
 * Безопасная обработка формы стратегии.
 */
function digi_lp_handle_contact() {
	$redirect = get_permalink( 1055 );
	$thanks   = get_permalink( (int) get_option( 'digi_lp_thanks_page_id' ) );
	if ( ! isset( $_POST['digi_lp_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['digi_lp_nonce'] ) ), 'digi_lp_contact' ) ) {
		wp_safe_redirect( add_query_arg( 'contact_status', 'invalid', $redirect ) );
		exit;
	}

	// Honeypot: бот получает обычный успешный ответ, письмо не отправляется.
	if ( ! empty( $_POST['website_confirm'] ) ) {
		wp_safe_redirect( $thanks ? $thanks : add_query_arg( 'contact_status', 'sent', $redirect ) );
		exit;
	}

	// Längenbegrenzung gegen Missbrauch/aufgeblähte Mails; sanitize_text_field entfernt bereits Zeilenumbrüche (Header-Injection-Schutz).
	$cap = function ( $s, $n ) { return function_exists( 'mb_substr' ) ? mb_substr( $s, 0, $n ) : substr( $s, 0, $n ); };

	$company = $cap( sanitize_text_field( wp_unslash( $_POST['company'] ?? '' ) ), 150 );
	$first   = $cap( sanitize_text_field( wp_unslash( $_POST['first'] ?? '' ) ), 100 );
	$last    = $cap( sanitize_text_field( wp_unslash( $_POST['last'] ?? '' ) ), 100 );
	$email   = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
	$phone   = $cap( sanitize_text_field( wp_unslash( $_POST['phone'] ?? '' ) ), 40 );
	$url     = esc_url_raw( wp_unslash( $_POST['url'] ?? '' ) );

	if ( ! $company || ! $first || ! is_email( $email ) || ! $phone ) {
		wp_safe_redirect( add_query_arg( 'contact_status', 'invalid', $redirect ) );
		exit;
	}

	// Absender wie im funktionierenden WPForms-Setup: From = eigene Domain (office@digirelation.com)
	// → bessere Zustellbarkeit/SPF; Antworten gehen direkt an den Anfragenden.
	$reply_name = trim( $first . ' ' . $last );
	$headers    = array(
		'From: digirelation <office@digirelation.com>',
		'Reply-To: ' . ( '' !== $reply_name ? $reply_name . ' ' : '' ) . '<' . $email . '>',
	);

	$subject = sprintf( 'Neue Strategiegespräch-Anfrage – %s', $company );
	$message = "Unternehmen: {$company}\nName: {$first} {$last}\nE-Mail: {$email}\nMobilnummer: {$phone}\nWebsite: {$url}\n";
	$sent    = wp_mail( 'support@digirelation.com', $subject, $message, $headers );

	wp_safe_redirect( $sent && $thanks ? $thanks : add_query_arg( 'contact_status', $sent ? 'sent' : 'error', $redirect ) );
	exit;
}
add_action( 'admin_post_digi_lp_contact', 'digi_lp_handle_contact' );
add_action( 'admin_post_nopriv_digi_lp_contact', 'digi_lp_handle_contact' );

/**
 * ACF-Felder (nach Sektionen gruppiert) – im Code registriert.
 */
require_once DIGI_LP_DIR . 'acf-fields.php';
require_once DIGI_LP_DIR . 'acf-editor-defaults.php';

/**
 * Полноширинная форма ACF в редакторе блока.
 */
add_action( 'enqueue_block_editor_assets', function () {
	wp_enqueue_style( 'digi-lp-editor', DIGI_LP_URL . 'assets/css/editor.css', array(), DIGI_LP_VER );
} );

/**
 * Admin-Hinweis, falls ACF (Pro) fehlt.
 */
add_action( 'admin_notices', function () {
	if ( function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}
	echo '<div class="notice notice-error"><p><strong>digirelation – Performance Landingpage</strong> benötigt <strong>ACF Pro</strong> (für Block-Felder &amp; Repeater). Bitte ACF aktivieren.</p></div>';
} );
