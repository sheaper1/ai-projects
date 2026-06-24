/* Объект-Галерея: coverflow (активный по центру, соседи приглушены).
 * Drag + бесконечный цикл через общий RbCarousel. */
( function () {
	function init() {
		document.querySelectorAll( '.property-gallery__carousel' ).forEach( function ( el ) {
			var viewport = el.querySelector( '.property-gallery__viewport' );
			if ( window.RbCarousel ) {
				window.RbCarousel( el, { frame: viewport || el, clickToGo: true } );
			}
		} );
	}
	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}() );
