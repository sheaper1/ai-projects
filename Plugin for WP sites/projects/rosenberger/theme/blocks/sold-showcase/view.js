document.querySelectorAll( '.sold-showcase' ).forEach( function ( block ) {
	var slides = block.querySelectorAll( '.sold-showcase__slide' );
	if ( slides.length <= 1 ) return;

	var prev  = block.querySelector( '.sold-showcase__nav--prev' );
	var next  = block.querySelector( '.sold-showcase__nav--next' );
	var dots  = block.querySelectorAll( '.sold-showcase__dot' );
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

	prev.addEventListener( 'click', function () { show( current - 1 ); } );
	next.addEventListener( 'click', function () { show( current + 1 ); } );

	// Keyboard
	block.addEventListener( 'keydown', function ( e ) {
		if ( e.key === 'ArrowLeft' )  show( current - 1 );
		if ( e.key === 'ArrowRight' ) show( current + 1 );
	} );
} );
