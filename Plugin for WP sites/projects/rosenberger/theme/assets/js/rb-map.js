/* Общий гейт карты под DSGVO: Leaflet/OSM грузятся только после согласия.
 * Карта = внешний контент (категория "marketing" в WP Consent API / Complianz).
 * До согласия показываем немецкий плейсхолдер с кнопкой «Karte laden».
 * Конфиг (URL Leaflet, ссылка на Datenschutz) приходит в window.RB_MAP. */
( function () {
	var cfg = window.RB_MAP || {};
	var loading = null;

	function loadLeaflet() {
		if ( window.L ) return Promise.resolve();
		if ( loading ) return loading;
		loading = new Promise( function ( resolve ) {
			if ( cfg.css && ! document.querySelector( 'link[data-rb-leaflet]' ) ) {
				var l = document.createElement( 'link' );
				l.rel = 'stylesheet';
				l.href = cfg.css;
				l.setAttribute( 'data-rb-leaflet', '' );
				document.head.appendChild( l );
			}
			var s = document.createElement( 'script' );
			s.src = cfg.js;
			s.onload = function () { resolve(); };
			document.head.appendChild( s );
		} );
		return loading;
	}

	// Согласие на внешний контент. Менеджер согласий (Complianz) реализует WP Consent API.
	function hasConsent() {
		try {
			if ( typeof window.wp_has_consent === 'function' ) {
				return !! window.wp_has_consent( 'marketing' );
			}
		} catch ( e ) {}
		return false; // нет явного согласия → не грузим автоматически
	}

	function placeholder( el, onConsent ) {
		if ( getComputedStyle( el ).position === 'static' ) el.style.position = 'relative';
		var box = document.createElement( 'div' );
		box.className = 'rb-map-consent';
		box.innerHTML =
			'<div class="rb-map-consent__inner">' +
				'<p class="rb-map-consent__text">Zum Schutz Ihrer Daten wird die Karte (OpenStreetMap) erst nach Ihrer Zustimmung geladen. Dabei werden Daten an Dritte übertragen.</p>' +
				'<button type="button" class="rb-map-consent__btn">Karte laden</button>' +
				( cfg.datenschutz ? '<a class="rb-map-consent__link" href="' + cfg.datenschutz + '">Datenschutzerklärung</a>' : '' ) +
			'</div>';
		el.appendChild( box );
		box.querySelector( '.rb-map-consent__btn' ).addEventListener( 'click', function () {
			try { if ( typeof window.wp_set_consent === 'function' ) window.wp_set_consent( 'marketing', 'allow' ); } catch ( e ) {}
			box.remove();
			onConsent();
		} );
		return box;
	}

	window.RbMap = {
		loadLeaflet: loadLeaflet,
		// Грузит карту сразу при наличии согласия; иначе ставит плейсхолдер и ждёт согласия.
		gate: function ( el, onReady ) {
			function go() { loadLeaflet().then( onReady ); }
			if ( hasConsent() ) { go(); return; }
			var box = placeholder( el, go );
			// Согласие через баннер менеджера (Complianz / WP Consent API).
			document.addEventListener( 'wp_listen_for_consent_change', function ( e ) {
				var c = ( e && e.detail ) || {};
				if ( c.marketing === 'allow' && box && box.parentNode ) { box.remove(); go(); }
			} );
			document.addEventListener( 'cmplz_event_marketing', function () {
				if ( box && box.parentNode ) { box.remove(); go(); }
			} );
		},
	};
}() );
