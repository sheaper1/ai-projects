document.querySelectorAll( '.sold-showcase' ).forEach( function ( block ) {
	var slides  = block.querySelectorAll( '.sold-showcase__slide' );
	if ( slides.length <= 1 ) return;

	var prev    = block.querySelector( '.sold-showcase__nav--prev' );
	var next    = block.querySelector( '.sold-showcase__nav--next' );
	var dots    = block.querySelectorAll( '.sold-showcase__dot' );
	var current = 0;
	var total   = slides.length;

	function show( index ) {
		slides[ current ].classList.remove( 'is-active' );
		slides[ current ].setAttribute( 'aria-hidden', 'true' );
		dots[ current ].classList.remove( 'is-active' );

		current = ( index + total ) % total;

		slides[ current ].classList.add( 'is-active' );
		slides[ current ].setAttribute( 'aria-hidden', 'false' );
		dots[ current ].classList.add( 'is-active' );
	}

	if ( prev ) prev.addEventListener( 'click', function () { show( current - 1 ); } );
	if ( next ) next.addEventListener( 'click', function () { show( current + 1 ); } );

	// Keyboard navigation
	block.setAttribute( 'tabindex', '0' );
	block.addEventListener( 'keydown', function ( e ) {
		if ( e.key === 'ArrowLeft' )  show( current - 1 );
		if ( e.key === 'ArrowRight' ) show( current + 1 );
	} );

	// Drag / swipe
	var track    = block.querySelector( '.sold-showcase__track' );
	var startX   = 0;
	var dragging = false;

	function onStart( x ) { startX = x; dragging = true; }
	function onEnd( x ) {
		if ( ! dragging ) return;
		dragging = false;
		var delta = x - startX;
		if ( Math.abs( delta ) < 40 ) return;
		delta < 0 ? show( current + 1 ) : show( current - 1 );
	}

	if ( track ) {
		// Touch
		track.addEventListener( 'touchstart', function ( e ) { onStart( e.touches[ 0 ].clientX ); }, { passive: true } );
		track.addEventListener( 'touchend',   function ( e ) { onEnd( e.changedTouches[ 0 ].clientX ); } );

		// Mouse drag
		track.addEventListener( 'mousedown', function ( e ) { onStart( e.clientX ); } );
		track.addEventListener( 'mouseup',   function ( e ) { onEnd( e.clientX ); } );
		track.addEventListener( 'mouseleave',function ()    { dragging = false; } );
		track.style.cursor = 'grab';
	}
} );
