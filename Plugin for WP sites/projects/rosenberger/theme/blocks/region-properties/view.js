/* Region Properties — карусель объектов (1 слайд, стрелки + точки, drag + цикл). */
( function () {
	function init() {
		document.querySelectorAll( '.region-properties__carousel[data-rp-carousel]' ).forEach( function ( el ) {
			if ( window.RbCarousel ) {
				window.RbCarousel( el, { frame: el } );
			}
		} );
	}
	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}() );
