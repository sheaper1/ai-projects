document.addEventListener( 'DOMContentLoaded', function () {
	var header = document.querySelector( '.site-header' );
	var toggle = document.querySelector( '.site-menu-toggle' );
	var menu = document.querySelector( '.site-menu' );
	var close = document.querySelector( '.site-menu__close' );
	var hasHero = document.querySelector( '.wp-block-library-hero-cover, .wp-block-library-bio-hero' );

	if ( ! header || ! toggle || ! menu || ! close ) return;

	var lastY = window.scrollY;

	function updateHeader() {
		var y = window.scrollY;
		header.classList.toggle( 'is-scrolled', ! hasHero || y > 24 );
		// Авто-скрытие: вниз — прячем, вверх — показываем. У самого верха и при
		// открытом меню — всегда видна (порог 96px, чтобы не дёргалась на мелочи).
		var menuOpen = menu.classList.contains( 'is-open' );
		if ( menuOpen || y <= 96 ) {
			header.classList.remove( 'is-hidden' );
		} else if ( y > lastY + 4 ) {
			header.classList.add( 'is-hidden' );      // скролл вниз
		} else if ( y < lastY - 4 ) {
			header.classList.remove( 'is-hidden' );    // скролл вверх
		}
		lastY = y;
	}

	function openMenu() {
		menu.classList.add( 'is-open' );
		menu.setAttribute( 'aria-hidden', 'false' );
		toggle.setAttribute( 'aria-expanded', 'true' );
		document.body.classList.add( 'site-menu-open' );
		close.focus();
	}

	function closeMenu() {
		menu.classList.remove( 'is-open' );
		menu.setAttribute( 'aria-hidden', 'true' );
		toggle.setAttribute( 'aria-expanded', 'false' );
		document.body.classList.remove( 'site-menu-open' );
		toggle.focus();
	}

	updateHeader();
	window.addEventListener( 'scroll', updateHeader, { passive: true } );
	toggle.addEventListener( 'click', openMenu );
	close.addEventListener( 'click', closeMenu );
	menu.addEventListener( 'click', function ( event ) {
		if ( event.target === menu || event.target.closest( '.site-menu__nav a' ) ) closeMenu();
	} );
	document.addEventListener( 'keydown', function ( event ) {
		if ( ! menu.classList.contains( 'is-open' ) ) return;
		if ( event.key === 'Escape' ) closeMenu();
		if ( event.key !== 'Tab' ) return;

		var focusable = menu.querySelectorAll( 'a[href], button:not([disabled])' );
		var first = focusable[ 0 ];
		var last = focusable[ focusable.length - 1 ];
		if ( event.shiftKey && document.activeElement === first ) {
			event.preventDefault();
			last.focus();
		} else if ( ! event.shiftKey && document.activeElement === last ) {
			event.preventDefault();
			first.focus();
		}
	} );
} );
