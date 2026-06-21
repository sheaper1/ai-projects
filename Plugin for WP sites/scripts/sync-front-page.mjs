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
		headers: {
			Authorization: AUTH,
			'Content-Type': 'application/json',
			...( opts.headers || {} ),
		},
	} );
	const text = await res.text();
	let body;
	try {
		body = JSON.parse( text );
	} catch {
		body = text;
	}
	return { status: res.status, body };
};

const main = async () => {
	const settings = await api( '/wp-json/wp/v2/settings' );
	const frontPageId = Number( settings.body?.page_on_front || 0 );
	if ( ! frontPageId ) throw new Error( 'Front page is not configured.' );

	const testPages = await api( '/wp-json/wp/v2/pages?slug=hero-cover-test&context=edit' );
	const testPage = Array.isArray( testPages.body ) ? testPages.body[ 0 ] : null;
	if ( ! testPage?.content?.raw ) throw new Error( 'hero-cover-test page not found.' );

	const update = await api( `/wp-json/wp/v2/pages/${ frontPageId }`, {
		method: 'POST',
		body: JSON.stringify( { content: testPage.content.raw } ),
	} );

	if ( update.status !== 200 ) {
		throw new Error( `Failed to sync front page: ${ update.status }` );
	}

	console.log( `Front page ${ frontPageId } synced from hero-cover-test (${ testPage.id }).` );
};

main().catch( ( error ) => {
	console.error( error );
	process.exit( 1 );
} );
