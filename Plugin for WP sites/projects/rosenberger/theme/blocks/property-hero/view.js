/* Объект-Hero: карусель фото (1 слайд за раз, стрелки + точки). Чистый DOM-JS. */
( function () {
	document.querySelectorAll( '.property-hero__carousel' ).forEach( function ( root ) {
		var track = root.querySelector( '[data-track]' );
		var slides = track ? track.children.length : 0;
		if ( ! track || slides < 2 ) return;

		var dotsBox = root.querySelector( '[data-dots]' );
		var index = 0;
		var dots = [];

		for ( var i = 0; i < slides; i++ ) {
			var b = document.createElement( 'button' );
			b.type = 'button';
			b.setAttribute( 'aria-label', 'Bild ' + ( i + 1 ) );
			( function ( n ) { b.addEventListener( 'click', function () { go( n ); } ); } )( i );
			dotsBox.appendChild( b );
			dots.push( b );
		}

		function go( n ) {
			index = ( n + slides ) % slides;
			track.style.transform = 'translateX(' + ( -index * 100 ) + '%)';
			dots.forEach( function ( d, i ) { d.setAttribute( 'aria-current', i === index ? 'true' : 'false' ); } );
		}

		var prev = root.querySelector( '[data-prev]' );
		var next = root.querySelector( '[data-next]' );
		if ( prev ) prev.addEventListener( 'click', function () { go( index - 1 ); } );
		if ( next ) next.addEventListener( 'click', function () { go( index + 1 ); } );

		go( 0 );
	} );
}() );
