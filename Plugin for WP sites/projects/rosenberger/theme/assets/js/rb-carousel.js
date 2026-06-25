/**
 * RbCarousel — общий движок каруселей темы. Чистый DOM-JS (без импортов).
 *
 * Возможности (ПРАВИЛО проекта: любая карусель должна их иметь):
 *  - бесконечный цикл (клонирование набора слева/справа, бесшовный rebase);
 *  - drag мышью + свайп тачем (pointer events);
 *  - стрелки [data-prev]/[data-next], точки [data-dots] (строятся автоматически);
 *  - transform-позиционирование, активный слайд по центру кадра.
 *
 * Разметка: контейнер с [data-track] (дети = слайды). Опц. [data-prev]/[data-next]/[data-dots].
 *
 * window.RbCarousel(carouselEl, {
 *   frame:     элемент-кадр, по чьей ширине центрируем (default carouselEl),
 *   clickToGo: клик по соседнему слайду листает к нему (для coverflow),
 *   autoplay:  мс между авто-листанием (0/undefined = выкл.)
 * })
 */
window.RbCarousel = function ( carousel, opts ) {
	opts = opts || {};
	var track = carousel.querySelector( '[data-track]' );
	if ( ! track ) { return; }
	var reals = Array.prototype.slice.call( track.children );
	var n = reals.length;
	if ( n < 2 ) { return; }

	var frame = opts.frame || carousel;

	// Клоны полного набора слева и справа → бесконечность.
	function cloneSet() {
		return reals.map( function ( s ) {
			var c = s.cloneNode( true );
			c.classList.add( 'rb-clone' );
			c.setAttribute( 'aria-hidden', 'true' );
			c.removeAttribute( 'data-index' );
			return c;
		} );
	}
	cloneSet().forEach( function ( c ) { track.insertBefore( c, track.firstChild ); } );
	cloneSet().forEach( function ( c ) { track.appendChild( c ); } );

	var all = Array.prototype.slice.call( track.children );

	// Картинки слайдов делаем инертными: без нативного перетаскивания картинки и
	// без клика по ней — тащим саму карусель, а не изображение (фикс для всех каруселей).
	Array.prototype.slice.call( track.querySelectorAll( 'img' ) ).forEach( function ( im ) {
		im.setAttribute( 'draggable', 'false' );
		im.style.pointerEvents = 'none';
		im.style.userSelect = 'none';
		im.style.webkitUserDrag = 'none';
	} );

	var base = n;          // индекс первого реального слайда в all
	var curDom = base;     // текущий слайд (в all)
	var currentX = 0;

	function targetX( dom ) {
		var s = all[ dom ];
		return -( s.offsetLeft + s.offsetWidth / 2 - frame.clientWidth / 2 );
	}
	function apply( x, animate ) {
		currentX = x;
		track.style.transition = animate ? '' : 'none';
		track.style.transform = 'translate3d(' + x + 'px,0,0)';
	}
	function realIndex( dom ) { return ( ( dom - base ) % n + n ) % n; }

	var dotsEls = [];
	function markActive() {
		all.forEach( function ( s ) { s.classList.remove( 'is-active' ); } );
		all[ curDom ].classList.add( 'is-active' );
		var ri = realIndex( curDom );
		dotsEls.forEach( function ( d, i ) { d.setAttribute( 'aria-current', i === ri ? 'true' : 'false' ); } );
	}
	function go( dom, animate ) {
		curDom = dom;
		apply( targetX( dom ), animate !== false );
		markActive();
	}
	function rebase() {
		if ( curDom >= base + n ) { curDom -= n; apply( targetX( curDom ), false ); }
		else if ( curDom < base ) { curDom += n; apply( targetX( curDom ), false ); }
	}
	track.addEventListener( 'transitionend', rebase );

	// Стрелки
	var prev = carousel.querySelector( '[data-prev]' );
	var next = carousel.querySelector( '[data-next]' );
	if ( prev ) { prev.addEventListener( 'click', function () { go( curDom - 1 ); } ); }
	if ( next ) { next.addEventListener( 'click', function () { go( curDom + 1 ); } ); }

	// Точки
	var dots = carousel.querySelector( '[data-dots]' );
	if ( dots ) {
		for ( var i = 0; i < n; i++ ) {
			( function ( idx ) {
				var b = document.createElement( 'button' );
				b.type = 'button';
				b.setAttribute( 'aria-label', 'Bild ' + ( idx + 1 ) );
				b.addEventListener( 'click', function () {
					var diff = ( ( idx - realIndex( curDom ) ) % n + n ) % n;
					if ( diff > n / 2 ) { diff -= n; }
					go( curDom + diff );
				} );
				dots.appendChild( b );
				dotsEls.push( b );
			} )( i );
		}
	}

	// Drag / свайп
	var down = false, sx = 0, sX = 0, moved = false;
	track.addEventListener( 'pointerdown', function ( e ) {
		down = true; moved = false; sx = e.clientX; sX = currentX;
		apply( currentX, false );
		carousel.classList.add( 'is-dragging' );
		try { track.setPointerCapture( e.pointerId ); } catch ( err ) {}
	} );
	track.addEventListener( 'pointermove', function ( e ) {
		if ( ! down ) { return; }
		var dx = e.clientX - sx;
		if ( Math.abs( dx ) > 3 ) { moved = true; }
		apply( sX + dx, false );
	} );
	function release() {
		if ( ! down ) { return; }
		down = false;
		carousel.classList.remove( 'is-dragging' );
		var bestDom = curDom, bestD = Infinity;
		for ( var d = base - 1; d <= base + n; d++ ) {
			var dist = Math.abs( targetX( d ) - currentX );
			if ( dist < bestD ) { bestD = dist; bestDom = d; }
		}
		go( bestDom );
	}
	track.addEventListener( 'pointerup', release );
	track.addEventListener( 'pointercancel', release );
	track.addEventListener( 'click', function ( e ) {
		if ( moved ) { e.preventDefault(); e.stopPropagation(); }
	}, true );

	if ( opts.clickToGo ) {
		all.forEach( function ( s, d ) {
			s.addEventListener( 'click', function () { if ( ! moved && d !== curDom ) { go( d ); } } );
		} );
	}

	var rt;
	window.addEventListener( 'resize', function () {
		clearTimeout( rt );
		rt = setTimeout( function () { apply( targetX( curDom ), false ); }, 120 );
	} );

	requestAnimationFrame( function () { go( base, false ); } );
	// Второй проход после загрузки картинок (ширины могли измениться).
	window.addEventListener( 'load', function () { apply( targetX( curDom ), false ); } );

	if ( opts.autoplay ) {
		var timer = setInterval( function () { if ( ! down ) { go( curDom + 1 ); } }, opts.autoplay );
		carousel.addEventListener( 'mouseenter', function () { clearInterval( timer ); } );
	}
};
