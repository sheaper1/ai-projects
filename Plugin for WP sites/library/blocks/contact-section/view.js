/* Contact Section: Leaflet/OSM-Karte mit rotem Pin. Координаты из data-lat/lng,
 * иначе геокодинг адреса через Nominatim. Leaflet грузится с CDN по требованию. */
( function () {
	var maps = document.querySelectorAll( '.contact-section__map[data-contact-map]' );
	if ( ! maps.length ) return;

	var LEAFLET_CSS = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
	var LEAFLET_JS = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';

	function loadCss( href ) {
		if ( document.querySelector( 'link[href="' + href + '"]' ) ) return;
		var l = document.createElement( 'link' );
		l.rel = 'stylesheet';
		l.href = href;
		document.head.appendChild( l );
	}

	function loadJs( src ) {
		return new Promise( function ( resolve ) {
			if ( window.L ) return resolve();
			var existing = document.querySelector( 'script[src="' + src + '"]' );
			if ( existing ) {
				existing.addEventListener( 'load', function () { resolve(); } );
				return;
			}
			var s = document.createElement( 'script' );
			s.src = src;
			s.onload = resolve;
			document.head.appendChild( s );
		} );
	}

	function redPin() {
		return window.L.divIcon( {
			className: 'contact-section__pin',
			html: '<svg width="48" height="58" viewBox="0 0 48 58" xmlns="http://www.w3.org/2000/svg"><path d="M24 0C10.7 0 0 10.7 0 24c0 16.5 21.6 32.4 22.5 33.1a2.5 2.5 0 0 0 3 0C26.4 56.4 48 40.5 48 24 48 10.7 37.3 0 24 0z" fill="#e3392f"/><circle cx="24" cy="23" r="9" fill="#fff"/></svg>',
			iconSize: [ 48, 58 ],
			iconAnchor: [ 24, 58 ],
		} );
	}

	function geocode( address ) {
		var q = address.replace( /·/g, ',' );
		return fetch( 'https://nominatim.openstreetmap.org/search?format=json&limit=1&q=' + encodeURIComponent( q ) )
			.then( function ( r ) { return r.json(); } )
			.then( function ( d ) { return d && d[ 0 ] ? [ parseFloat( d[ 0 ].lat ), parseFloat( d[ 0 ].lon ) ] : null; } )
			.catch( function () { return null; } );
	}

	function init( el, coords ) {
		if ( ! coords ) return;
		var map = window.L.map( el, { scrollWheelZoom: false } ).setView( coords, 15 );
		window.L.tileLayer( 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
			maxZoom: 19,
			attribution: '© OpenStreetMap',
		} ).addTo( map );
		window.L.marker( coords, { icon: redPin() } ).addTo( map );
		setTimeout( function () { map.invalidateSize(); }, 200 );
	}

	loadCss( LEAFLET_CSS );
	loadJs( LEAFLET_JS ).then( function () {
		maps.forEach( function ( el ) {
			var lat = parseFloat( el.getAttribute( 'data-lat' ) );
			var lng = parseFloat( el.getAttribute( 'data-lng' ) );
			if ( ! isNaN( lat ) && ! isNaN( lng ) ) {
				init( el, [ lat, lng ] );
			} else {
				geocode( el.getAttribute( 'data-address' ) || '' ).then( function ( c ) { init( el, c ); } );
			}
		} );
	} ).catch( function () {} );
}() );

/* ---- WPForms Bridge (Prinzip wie Tippgeber) ----
 * Sichtbares Custom-Formular füllt das versteckte echte WPForms-Formular und
 * löst dessen nativen AJAX-Submit aus → echte Entries, Lead-Mail, Redirect /danke/. */
( function () {
	var form = document.querySelector( '.contact-section__form[data-contact-form]' );
	if ( ! form ) return;

	var formId = parseInt( form.getAttribute( 'data-wpforms-id' ), 10 ) || 0;
	var fields = {};
	try { fields = JSON.parse( form.getAttribute( 'data-wpforms-fields' ) || '{}' ); } catch ( e ) { fields = {}; }

	var submitBtn = form.querySelector( '.cs-field__submit' );
	var errorEl = form.querySelector( '[data-cs-error]' );

	function hiddenSubmit() { return document.getElementById( 'wpforms-submit-' + formId ); }

	function wpfSet( fieldId, value ) {
		if ( fieldId == null ) return;
		var el = document.getElementById( 'wpforms-' + formId + '-field_' + fieldId );
		if ( el && ( el.tagName === 'SELECT' || el.tagName === 'INPUT' || el.tagName === 'TEXTAREA' ) ) {
			el.value = value;
			el.dispatchEvent( new Event( 'input', { bubbles: true } ) );
			el.dispatchEvent( new Event( 'change', { bubbles: true } ) );
		}
	}

	function showError() {
		if ( errorEl ) errorEl.hidden = false;
		if ( submitBtn ) { submitBtn.disabled = false; submitBtn.textContent = 'JETZT ANFRAGEN'; }
	}

	form.addEventListener( 'submit', function ( e ) {
		e.preventDefault();
		if ( errorEl ) errorEl.hidden = true;

		// einfache Pflichtprüfung (Name + Email)
		var nameVal = ( form.querySelector( '[data-cs-field="name"]' ) || {} ).value || '';
		var emailVal = ( form.querySelector( '[data-cs-field="email"]' ) || {} ).value || '';
		if ( ! nameVal.trim() || ! emailVal.trim() ) {
			form.reportValidity ? form.reportValidity() : showError();
			return;
		}

		if ( ! formId || ! hiddenSubmit() ) { showError(); return; }

		form.querySelectorAll( '[data-cs-field]' ).forEach( function ( input ) {
			var slug = input.getAttribute( 'data-cs-field' );
			if ( fields[ slug ] != null && input.value !== '' ) {
				wpfSet( fields[ slug ], input.value );
			}
		} );

		if ( submitBtn ) { submitBtn.disabled = true; submitBtn.textContent = 'Wird gesendet …'; }
		hiddenSubmit().click(); // WPForms validiert, sendet per AJAX und macht den Redirect auf /danke/
	} );

	// WPForms-Fehler-Event → Button wieder freigeben und Hinweis zeigen
	if ( window.jQuery && formId ) {
		window.jQuery( document ).on( 'wpformsAjaxSubmitFailed', '#wpforms-form-' + formId, showError );
	}
}() );
