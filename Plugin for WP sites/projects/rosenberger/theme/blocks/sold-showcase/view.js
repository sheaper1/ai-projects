document.querySelectorAll( '.sold-showcase' ).forEach( function ( block ) {
	var strip = block.querySelector( '.sold-showcase__strip' );
	var track = block.querySelector( '.sold-showcase__track' );
	var slides = block.querySelectorAll( '.sold-showcase__slide' );
	var dots = block.querySelectorAll( '.sold-showcase__dot' );
	var prev = block.querySelector( '.sold-showcase__nav--prev' );
	var next = block.querySelector( '.sold-showcase__nav--next' );

	if ( ! strip || slides.length <= 1 ) return;

	var current = 0;
	var startX = 0;
	var currentX = 0;
	var dragging = false;

	function update() {
		strip.style.transform = 'translate3d(' + ( current * -100 ) + '%, 0, 0)';

		slides.forEach( function ( slide, index ) {
			slide.setAttribute( 'aria-hidden', index === current ? 'false' : 'true' );
		} );

		dots.forEach( function ( dot, index ) {
			dot.classList.toggle( 'is-active', index === current );
		} );
	}

	function show( index ) {
		current = ( index + slides.length ) % slides.length;
		update();
	}

	function startDrag( clientX ) {
		startX = clientX;
		currentX = clientX;
		dragging = true;
		strip.classList.add( 'is-dragging' );
	}

	function moveDrag( clientX ) {
		if ( ! dragging ) return;
		currentX = clientX;
		strip.style.transform = 'translate3d(calc(' + ( current * -100 ) + '% + ' + ( currentX - startX ) + 'px), 0, 0)';
	}

	function endDrag() {
		if ( ! dragging ) return;
		dragging = false;
		strip.classList.remove( 'is-dragging' );

		var delta = currentX - startX;
		if ( Math.abs( delta ) >= 40 ) {
			show( delta < 0 ? current + 1 : current - 1 );
			return;
		}

		update();
	}

	prev.addEventListener( 'click', function () { show( current - 1 ); } );
	next.addEventListener( 'click', function () { show( current + 1 ); } );

	block.addEventListener( 'keydown', function ( event ) {
		if ( event.key === 'ArrowLeft' ) show( current - 1 );
		if ( event.key === 'ArrowRight' ) show( current + 1 );
	} );

	track.addEventListener( 'touchstart', function ( event ) {
		startDrag( event.touches[ 0 ].clientX );
	}, { passive: true } );
	track.addEventListener( 'touchmove', function ( event ) {
		moveDrag( event.touches[ 0 ].clientX );
	}, { passive: true } );
	track.addEventListener( 'touchend', endDrag );
	track.addEventListener( 'touchcancel', endDrag );
	track.addEventListener( 'mousedown', function ( event ) {
		startDrag( event.clientX );
	} );
	track.addEventListener( 'mousemove', function ( event ) {
		moveDrag( event.clientX );
	} );
	track.addEventListener( 'mouseup', endDrag );
	track.addEventListener( 'mouseleave', endDrag );

	update();
} );
