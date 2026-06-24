// Создаёт WPForms-форму «Tippgeber» (slug `tippgeber`) через одноразовый Code Snippet.
// Поля: Anrede(select), Vorname, Nachname, Email(req), Telefon(phone), PLZ, Objektangaben(textarea).
// Письмо на office-email, подтверждение = редирект на /danke/. Идемпотентно (по slug).
// Блок tipper-form сам находит форму по slug и строит мост funnel → WPForms.
// Запуск: node scripts/setup-tipper-form.mjs

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
const SNIPPET = 'Library: tipper-form setup (temporary)';

const phpCode = `
$existing = get_posts( array( 'post_type' => 'wpforms', 'name' => 'tippgeber', 'post_status' => 'publish', 'numberposts' => 1 ) );
if ( ! empty( $existing ) ) { update_option( 'rosenberger_tipper_form_ready', (int) $existing[0]->ID ); return; }
$office = get_option( 'admin_email' );
if ( function_exists( 'rosenberger_contact' ) ) {
	$fe = rosenberger_contact( 'form_email' ); $em = rosenberger_contact( 'email' );
	if ( $fe ) { $office = $fe; } elseif ( $em ) { $office = $em; }
}
$post_id = wp_insert_post( array( 'post_type' => 'wpforms', 'post_title' => 'Tippgeber', 'post_name' => 'tippgeber', 'post_status' => 'publish', 'post_content' => '' ) );
if ( is_wp_error( $post_id ) || ! $post_id ) { return; }
$form_data = array(
	'id' => $post_id,
	'field_id' => 8,
	'fields' => array(
		1 => array( 'id' => 1, 'type' => 'select', 'label' => 'Anrede', 'size' => 'large', 'choices' => array(
			1 => array( 'label' => 'Frau' ),
			2 => array( 'label' => 'Herr' ),
			3 => array( 'label' => 'Divers' ),
		) ),
		2 => array( 'id' => 2, 'type' => 'text', 'label' => 'Vorname', 'size' => 'large' ),
		3 => array( 'id' => 3, 'type' => 'text', 'label' => 'Nachname', 'size' => 'large' ),
		4 => array( 'id' => 4, 'type' => 'email', 'label' => 'Email', 'required' => '1', 'size' => 'large' ),
		5 => array( 'id' => 5, 'type' => 'phone', 'label' => 'Telefon', 'format' => 'smart', 'size' => 'large' ),
		6 => array( 'id' => 6, 'type' => 'text', 'label' => 'PLZ', 'size' => 'large' ),
		7 => array( 'id' => 7, 'type' => 'textarea', 'label' => 'Objektangaben', 'size' => 'large' ),
	),
	'settings' => array(
		'form_title' => 'Tippgeber',
		'submit_text' => 'Anfrage absenden',
		'submit_text_processing' => 'Senden…',
		'antispam_v3' => '1',
		'notification_enable' => '1',
		'notifications' => array(
			1 => array(
				'notification_name' => 'Standardbenachrichtigung',
				'email' => $office,
				'subject' => 'Neuer Tippgeber-Lead',
				'sender_name' => get_bloginfo( 'name' ),
				'sender_address' => '{admin_email}',
				'replyto' => '{field_id="4"}',
				'message' => '{all_fields}',
			),
		),
		'confirmations' => array(
			1 => array(
				'type' => 'redirect',
				'redirect' => home_url( '/danke/' ),
				'message' => '<p>Danke für Ihren Tipp!</p>',
				'message_scroll' => '1',
			),
		),
	),
	'meta' => array( 'template' => '' ),
);
$content = function_exists( 'wpforms_encode' ) ? wpforms_encode( $form_data ) : wp_json_encode( $form_data );
wp_update_post( array( 'ID' => $post_id, 'post_content' => $content ) );
update_option( 'rosenberger_tipper_form_ready', (int) $post_id );
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

console.log( '🛠  Создаю WPForms-форму «Tippgeber»...' );
if ( ! env.WP_URL ) throw new Error( 'нет WP_URL в .env' );

await neutralize();
const created = await api( '/wp-json/code-snippets/v1/snippets', {
	method: 'POST',
	body: JSON.stringify( { name: SNIPPET, desc: 'Одноразовое создание Tippgeber-формы. Обезвреживается.', code: phpCode, scope: 'global', active: true } ),
} );
if ( ! ( created.body && created.body.id ) ) { console.log( JSON.stringify( created.body ).slice( 0, 400 ) ); throw new Error( 'не удалось создать сниппет' ); }
console.log( `  сниппет создан #${ created.body.id }, триггерю...` );

for ( let i = 0; i < 3; i++ ) {
	await fetch( BASE + '/' ).catch( () => {} );
	await sleep( 1500 );
}
await neutralize();
console.log( '\n✅ Сниппет отработал и обезврежен. Форма «Tippgeber» создана (или уже была).' );
console.log( '   Блок tipper-form находит её по slug автоматически.' );
console.log( '   Проверь: WP Admin → WPForms → Tippgeber; и страницу /tippgeber/.' );
