/* Lage & Erreichbarkeit: Leaflet/OSM-Karte. Координаты из data-lat/lng,
 * иначе геокодинг адреса через Nominatim. Leaflet грузится с CDN по требованию. */
( function () {
	var maps = document.querySelectorAll( '.property-location__map[data-map]' );
	if ( ! maps.length ) return;

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

	// Карта грузится только после согласия (DSGVO) — через общий гейт RbMap.
	function start() {
		maps.forEach( function ( el ) {
			window.RbMap.gate( el, function () {
				var lat = parseFloat( el.getAttribute( 'data-lat' ) );
				var lng = parseFloat( el.getAttribute( 'data-lng' ) );
				if ( ! isNaN( lat ) && ! isNaN( lng ) ) {
					init( el, [ lat, lng ] );
				} else {
					geocode( el.getAttribute( 'data-address' ) || '' ).then( function ( c ) { init( el, c ); } );
				}
			} );
		} );
	}

	if ( window.RbMap ) {
		start();
	} else {
		var t = setInterval( function () { if ( window.RbMap ) { clearInterval( t ); start(); } }, 50 );
		setTimeout( function () { clearInterval( t ); }, 5000 );
	}
}() );
