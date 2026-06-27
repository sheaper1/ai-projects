// Создаёт WPForms-форму «Kontakt» (slug `kontakt`) через одноразовый Code Snippet.
// Поля: Name, Email, Phone(Smart), Subject(select), Nachricht. Письмо на office-email,
// подтверждение = редирект на /danke/. Идемпотентно (по slug). Блок contact-section
// сам находит форму по slug, поэтому ID никуда передавать не нужно.
// Запуск: node scripts/setup-contact-form.mjs

import { readFileSync } from 'node:fs';
import { resolve, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = resolve( dirname( fileURLToPath( import.meta.url ) ), '..' );
const env = {};
for ( const line of readFileSync( resolve( root, '.env' ), 'utf8' ).split( /\r?\n/ ) ) {
	const m = line.match( /^([A-Z_]+)=(.*)$/ );
	if ( m ) env[ m[ 1 ] ] = m[ 2 ];
}
const BASE = env.WP_URL.replace( /\/$/, '' );
const AUTH = 'Basic ' + Buffer.from( `${ env.WP_USER }:${ env.WP_APP_PASSWORD }` ).toString( 'base64' );
const api = async ( path, opts = {} ) => {
	const res = await fetch( `${ BASE }${ path }`, {
		...opts,
		headers: { Authorization: AUTH, 'Content-Type': 'application/json', ...( opts.headers || {} ) },
	} );
	const text = await res.text();
	let body; try { body = JSON.parse( text ); } catch { body = text; }
	return { status: res.status, ok: res.ok, body };
};
const sleep = ( ms ) => new Promise( ( r ) => setTimeout( r, ms ) );
const SNIPPET = 'Library: contact-form setup (temporary)';

const phpCode = `
$office = get_option( 'admin_email' );
if ( function_exists( 'rosenberger_contact' ) ) {
	$fe = rosenberger_contact( 'form_email' ); $em = rosenberger_contact( 'email' );
	if ( $fe ) { $office = $fe; } elseif ( $em ) { $office = $em; }
}
$existing = get_posts( array( 'post_type' => 'wpforms', 'name' => 'kontakt', 'post_status' => 'publish', 'numberposts' => 1 ) );
if ( ! empty( $existing ) ) {
	$post_id = (int) $existing[0]->ID; // переиспользуем — поля ниже перезапишутся (перевод на DE)
} else {
	$post_id = wp_insert_post( array( 'post_type' => 'wpforms', 'post_title' => 'Kontakt', 'post_name' => 'kontakt', 'post_status' => 'publish', 'post_content' => '' ) );
}
if ( is_wp_error( $post_id ) || ! $post_id ) { return; }
$form_data = array(
	'id' => $post_id,
	'field_id' => 6,
	'fields' => array(
		1 => array( 'id' => 1, 'type' => 'name', 'label' => 'Name', 'format' => 'simple', 'required' => '1', 'size' => 'large', 'placeholder' => 'Ihr Name' ),
		2 => array( 'id' => 2, 'type' => 'email', 'label' => 'E-Mail', 'required' => '1', 'size' => 'large', 'placeholder' => 'Ihre E-Mail-Adresse' ),
		3 => array( 'id' => 3, 'type' => 'phone', 'label' => 'Telefon', 'format' => 'smart', 'size' => 'large', 'placeholder' => '+43 660 1234567' ),
		4 => array( 'id' => 4, 'type' => 'select', 'label' => 'Betreff der Anfrage', 'size' => 'large', 'choices' => array(
			1 => array( 'label' => 'Immobilienverkauf' ),
			2 => array( 'label' => 'Immobilienbewertung' ),
			3 => array( 'label' => 'Vermietung' ),
			4 => array( 'label' => 'Sonstiges' ),
		) ),
		5 => array( 'id' => 5, 'type' => 'textarea', 'label' => 'Nachricht', 'size' => 'large', 'placeholder' => 'Worum geht es bei Ihrem Projekt?' ),
	),
	'settings' => array(
		'form_title' => 'Kontakt',
		'submit_text' => 'JETZT ANFRAGEN',
		'submit_text_processing' => 'Senden…',
		'antispam_v3' => '1',
		'notification_enable' => '1',
		'notifications' => array(
			1 => array(
				'notification_name' => 'Standardbenachrichtigung',
				'email' => $office,
				'subject' => 'Neue Kontaktanfrage',
				'sender_name' => get_bloginfo( 'name' ),
				'sender_address' => '{admin_email}',
				'replyto' => '{field_id="2"}',
				'message' => '{all_fields}',
			),
		),
		'confirmations' => array(
			1 => array(
				'type' => 'redirect',
				'redirect' => home_url( '/danke/' ),
				'message' => '<p>Danke für Ihre Anfrage!</p>',
				'message_scroll' => '1',
			),
		),
	),
	'meta' => array( 'template' => '' ),
);
$content = function_exists( 'wpforms_encode' ) ? wpforms_encode( $form_data ) : wp_json_encode( $form_data );
wp_update_post( array( 'ID' => $post_id, 'post_content' => $content ) );
update_option( 'rosenberger_contact_form_ready', (int) $post_id );
`;

const neutralize = async () => {
	const list = await api( '/wp-json/code-snippets/v1/snippets' );
	if ( ! Array.isArray( list.body ) ) return;
	for ( const s of list.body ) {
		if ( s.name === SNIPPET && ( s.active || s.code !== '// removed' ) ) {
			await api( `/wp-json/code-snippets/v1/snippets/${ s.id }`, {
				method: 'POST', body: JSON.stringify( { active: false, code: '// removed' } ),
			} );
			console.log( `  обезврежен сниппет #${ s.id }` );
		}
	}
};

console.log( '🛠  Создаю WPForms-форму «Kontakt»...' );
if ( ! env.WP_URL ) throw new Error( 'нет WP_URL в .env' );

await neutralize();
const created = await api( '/wp-json/code-snippets/v1/snippets', {
	method: 'POST',
	body: JSON.stringify( { name: SNIPPET, desc: 'Одноразовое создание контактной формы. Обезвреживается.', code: phpCode, scope: 'global', active: true } ),
} );
if ( ! ( created.body && created.body.id ) ) { console.log( JSON.stringify( created.body ).slice( 0, 400 ) ); throw new Error( 'не удалось создать сниппет' ); }
console.log( `  сниппет создан #${ created.body.id }, триггерю...` );

// Global-scope сниппет исполняется на загрузке страницы. Триггерим несколько раз.
for ( let i = 0; i < 3; i++ ) {
	await fetch( BASE + '/' ).catch( () => {} );
	await sleep( 1500 );
}
await neutralize();
console.log( '\n✅ Сниппет отработал и обезврежен. Форма «Kontakt» создана (или уже была).' );
console.log( '   Блок contact-section находит её по slug автоматически.' );
console.log( '   Проверь: WP Admin → WPForms → Kontakt; и страницу /kontakt/.' );
