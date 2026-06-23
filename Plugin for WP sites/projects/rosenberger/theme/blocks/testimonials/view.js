/**
 * Testimonials — карусель отзывов: нативный свайп (scroll-snap) + точки-пагинация.
 * Точки = страницы по perView (3 desktop / 2 tablet / 1 mobile). Без JS — обычный
 * горизонтальный скролл.
 */
( function () {
	document.querySelectorAll( '.testimonials__carousel' ).forEach( function ( root ) {
		var track = root.querySelector( '.testimonials__track' );
		var dots  = root.querySelector( '.testimonials__dots' );
		if ( ! track || ! dots ) {
			return;
		}
		var cards = Array.prototype.slice.call( track.children );
		if ( cards.length <= 1 ) {
			return;
		}

		function perView() {
			var w = window.innerWidth;
			return w > 1024 ? 3 : w > 640 ? 2 : 1;
		}

		var pages = 1;

		function buildDots() {
			pages = Math.ceil( cards.length / perView() );
			dots.innerHTML = '';
			if ( pages <= 1 ) {
				dots.style.display = 'none';
				return;
			}
			dots.style.display = '';
			for ( var i = 0; i < pages; i++ ) {
				var b = document.createElement( 'button' );
				b.type = 'button';
				b.className = 'testimonials__dot';
				b.setAttribute( 'role', 'tab' );
				b.setAttribute( 'aria-label', 'Seite ' + ( i + 1 ) );
				( function ( idx ) {
					b.addEventListener( 'click', function () { goTo( idx ); } );
				} )( i );
				dots.appendChild( b );
			}
			updateActive();
		}

		function goTo( page ) {
			var target = cards[ Math.min( page * perView(), cards.length - 1 ) ];
			var delta  = target.getBoundingClientRect().left - track.getBoundingClientRect().left;
			track.scrollTo( { left: track.scrollLeft + delta, behavior: 'smooth' } );
		}

		function updateActive() {
			var page = Math.round( track.scrollLeft / track.clientWidth );
			page = Math.max( 0, Math.min( page, pages - 1 ) );
			dots.querySelectorAll( '.testimonials__dot' ).forEach( function ( d, i ) {
				var on = i === page;
				d.classList.toggle( 'is-active', on );
				d.setAttribute( 'aria-selected', String( on ) );
			} );
		}

		var st;
		track.addEventListener( 'scroll', function () {
			clearTimeout( st );
			st = setTimeout( updateActive, 80 );
		} );

		var rt;
		window.addEventListener( 'resize', function () {
			clearTimeout( rt );
			rt = setTimeout( buildDots, 200 );
		} );

		buildDots();
	} );
} )();
