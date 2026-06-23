/* Objektdaten: переключение аккордеонов (несколько могут быть открыты). */
( function () {
	document.querySelectorAll( '.property-details__header[data-toggle]' ).forEach( function ( btn ) {
		btn.addEventListener( 'click', function () {
			var item = btn.closest( '.property-details__item' );
			var open = item.classList.toggle( 'is-open' );
			btn.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
		} );
	} );
}() );
