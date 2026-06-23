/* Lage & Erreichbarkeit: Leaflet/OSM-Karte. Координаты из data-lat/lng,
 * иначе геокодинг адреса через Nominatim. Leaflet грузится с CDN по требованию. */
( function () {
	var maps = document.querySelectorAll( '.property-location__map[data-map]' );
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
		return new Promise( function ( resolve, reject ) {
			if ( window.L ) return resolve();
			var existing = document.querySelector( 'script[src="' + src + '"]' );
			if ( existing ) {
				existing.addEventListener( 'load', function () { resolve(); } );
				return;
			}
			var s = document.createElement( 'script' );
			s.src = src;
			s.onload = resolve;
			s.onerror = reject;
			document.head.appendChild( s );
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
		window.L.marker( coords ).addTo( map );
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
