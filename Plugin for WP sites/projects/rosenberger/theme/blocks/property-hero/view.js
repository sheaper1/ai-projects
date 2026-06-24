/* Объект-Hero: фото-карусель (1 слайд, стрелки + точки, drag + бесконечный цикл). */
( function () {
	function init() {
		document.querySelectorAll( '.property-hero__carousel' ).forEach( function ( el ) {
			if ( window.RbCarousel ) { window.RbCarousel( el, { frame: el } ); }
		} );
	}
	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}() );
