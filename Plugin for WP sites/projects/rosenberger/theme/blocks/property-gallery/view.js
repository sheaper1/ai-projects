/* Объект-Галерея: coverflow — активный слайд по центру, соседи приглушены. */
( function () {
	document.querySelectorAll( '.property-gallery__carousel' ).forEach( function ( root ) {
		var viewport = root.querySelector( '.property-gallery__viewport' );
		var track = root.querySelector( '[data-track]' );
		var slides = track ? Array.prototype.slice.call( track.children ) : [];
		if ( slides.length === 0 ) return;

		var dotsBox = root.querySelector( '[data-dots]' );
		var index = 0;
		var dots = [];

		if ( dotsBox && slides.length > 1 ) {
			slides.forEach( function ( _, i ) {
				var b = document.createElement( 'button' );
				b.type = 'button';
				b.setAttribute( 'aria-label', 'Bild ' + ( i + 1 ) );
				b.addEventListener( 'click', function () { go( i ); } );
				dotsBox.appendChild( b );
				dots.push( b );
			} );
		}

		function go( n ) {
			index = Math.max( 0, Math.min( n, slides.length - 1 ) );
			var active = slides[ index ];
			var offset = active.offsetLeft + active.offsetWidth / 2 - viewport.offsetWidth / 2;
			track.style.transform = 'translateX(' + ( -offset ) + 'px)';
			slides.forEach( function ( s, i ) { s.classList.toggle( 'is-active', i === index ); } );
			dots.forEach( function ( d, i ) { d.setAttribute( 'aria-current', i === index ? 'true' : 'false' ); } );
		}

		// Клик по соседнему слайду — листаем к нему.
		slides.forEach( function ( s, i ) {
			s.addEventListener( 'click', function () { if ( i !== index ) go( i ); } );
		} );

		window.addEventListener( 'resize', function () { go( index ); } );
		go( 0 );
	} );
}() );
