// Stack-on-scroll: активная карточка полная, прошлые уезжают вверх с уменьшением,
// счётчик-одометр сдвигается, линия-прогресс растёт. Чистый DOM-JS (file:./view.js).
( function () {
	function clamp( v, min, max ) {
		return Math.min( Math.max( v, min ), max );
	}
	function pageTop( el ) {
		var t = 0;
		while ( el ) {
			t += el.offsetTop || 0;
			el = el.offsetParent;
		}
		return t;
	}
	function init( root ) {
		if ( root.dataset.cardsInit === '1' ) {
			return;
		}
		root.dataset.cardsInit = '1';

		var cards = Array.prototype.slice.call( root.querySelectorAll( '.cards-stack__card' ) );
		var track = root.querySelector( '.cards-stack__track' );
		var lineFill = root.querySelector( '.cards-stack__line span' );
		if ( ! cards.length ) {
			return;
		}

		function stickyTop() {
			var v = parseFloat( getComputedStyle( root ).getPropertyValue( '--cards-sticky-top' ) );
			return isFinite( v ) ? v : 120;
		}

		function update() {
			var checkpoint = window.scrollY + stickyTop() + 80;
			var active = 0;
			cards.forEach( function ( c, i ) {
				if ( pageTop( c ) <= checkpoint ) {
					active = i;
				}
			} );
			active = clamp( active, 0, cards.length - 1 );

			cards.forEach( function ( c, i ) {
				c.classList.remove( 'is-active', 'is-past', 'is-future' );
				if ( i === active ) {
					c.classList.add( 'is-active' );
					c.style.setProperty( '--card-scale', '1' );
					c.style.setProperty( '--card-y', '0px' );
					c.style.setProperty( '--card-opacity', '1' );
				} else if ( i < active ) {
					c.classList.add( 'is-past' );
					var d = clamp( active - i, 1, 4 );
					c.style.setProperty( '--card-scale', ( 1 - d * 0.035 ).toFixed( 3 ) );
					c.style.setProperty( '--card-y', d * -14 + 'px' );
					c.style.setProperty( '--card-opacity', ( 1 - d * 0.12 ).toFixed( 2 ) );
				} else {
					c.classList.add( 'is-future' );
					c.style.setProperty( '--card-scale', '1' );
					c.style.setProperty( '--card-y', '0px' );
					c.style.setProperty( '--card-opacity', '1' );
				}
			} );

			if ( track ) {
				track.style.setProperty( '--active-index', active );
			}
			if ( lineFill ) {
				var p = cards.length > 1 ? active / ( cards.length - 1 ) : 1;
				lineFill.style.transform = 'scaleY(' + clamp( p, 0.08, 1 ) + ')';
			}
		}

		update();
		var ticking = false;
		window.addEventListener( 'scroll', function () {
			if ( ticking ) {
				return;
			}
			ticking = true;
			window.requestAnimationFrame( function () {
				update();
				ticking = false;
			} );
		}, { passive: true } );
		window.addEventListener( 'resize', update );
		window.addEventListener( 'load', update );
	}
	document.querySelectorAll( '.wp-block-library-cards-stack' ).forEach( init );
} )();
