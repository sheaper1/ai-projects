/**
 * Testimonials — карусель отзывов: листание ПО ОДНОЙ карточке.
 * - нативный свайп (scroll-snap) на тач;
 * - drag мышью (pointer) на десктопе;
 * - точки-пагинация = число позиций (cards - perView + 1), клик листает на 1.
 * Без JS — обычный горизонтальный скролл.
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

		// Шаг = расстояние между началами соседних карточек (ширина + gap).
		function step() {
			return cards.length > 1
				? cards[ 1 ].getBoundingClientRect().left - cards[ 0 ].getBoundingClientRect().left
				: cards[ 0 ].getBoundingClientRect().width;
		}

		var positions = 1;

		function buildDots() {
			positions = Math.max( 1, cards.length - perView() + 1 );
			dots.innerHTML = '';
			if ( positions <= 1 ) {
				dots.style.display = 'none';
				return;
			}
			dots.style.display = '';
			for ( var i = 0; i < positions; i++ ) {
				var b = document.createElement( 'button' );
				b.type = 'button';
				b.className = 'testimonials__dot';
				b.setAttribute( 'role', 'tab' );
				b.setAttribute( 'aria-label', 'Position ' + ( i + 1 ) );
				( function ( idx ) {
					b.addEventListener( 'click', function () { goTo( idx ); } );
				} )( i );
				dots.appendChild( b );
			}
			updateActive();
		}

		function goTo( i ) {
			var target = cards[ Math.min( i, cards.length - 1 ) ];
			var delta  = target.getBoundingClientRect().left - track.getBoundingClientRect().left;
			track.scrollTo( { left: track.scrollLeft + delta, behavior: 'smooth' } );
		}

		function updateActive() {
			var idx = Math.round( track.scrollLeft / step() );
			idx = Math.max( 0, Math.min( idx, positions - 1 ) );
			dots.querySelectorAll( '.testimonials__dot' ).forEach( function ( d, i ) {
				var on = i === idx;
				d.classList.toggle( 'is-active', on );
				d.setAttribute( 'aria-selected', String( on ) );
			} );
		}

		// --- Синхронизация точек со скроллом/свайпом ---
		var st;
		track.addEventListener( 'scroll', function () {
			clearTimeout( st );
			st = setTimeout( updateActive, 80 );
		} );

		// --- Drag мышью (на тач — нативный свайп) ---
		var down = false, startX = 0, startScroll = 0, moved = false;

		track.addEventListener( 'pointerdown', function ( e ) {
			if ( 'mouse' !== e.pointerType ) {
				return;
			}
			down = true;
			moved = false;
			startX = e.clientX;
			startScroll = track.scrollLeft;
			track.style.scrollSnapType = 'none';
			track.classList.add( 'is-dragging' );
		} );

		track.addEventListener( 'pointermove', function ( e ) {
			if ( ! down ) {
				return;
			}
			var dx = e.clientX - startX;
			if ( Math.abs( dx ) > 3 ) {
				moved = true;
			}
			track.scrollLeft = startScroll - dx;
		} );

		function endDrag() {
			if ( ! down ) {
				return;
			}
			down = false;
			track.classList.remove( 'is-dragging' );
			track.style.scrollSnapType = ''; // вернуть snap → защёлкнется к ближайшей
		}
		track.addEventListener( 'pointerup', endDrag );
		track.addEventListener( 'pointerleave', endDrag );
		track.addEventListener( 'pointercancel', endDrag );
		// Не давать «клику» после перетягивания (если внутри появятся ссылки).
		track.addEventListener( 'click', function ( e ) {
			if ( moved ) {
				e.preventDefault();
				e.stopPropagation();
			}
		}, true );

		var rt;
		window.addEventListener( 'resize', function () {
			clearTimeout( rt );
			rt = setTimeout( buildDots, 200 );
		} );

		buildDots();
	} );
} )();
