// Меняет номер активной карточки, когда она проходит центр экрана.
// Чистый DOM-JS без сборки — подключается через viewScript: file:./view.js.
( function () {
	var init = function ( root ) {
		var current = root.querySelector( '.cards-stack__current' );
		var cards = root.querySelectorAll( '.cards-stack__card' );
		if ( ! current || ! cards.length || ! ( 'IntersectionObserver' in window ) ) {
			return;
		}
		var io = new IntersectionObserver(
			function ( entries ) {
				entries.forEach( function ( entry ) {
					if ( entry.isIntersecting ) {
						current.textContent = String( entry.target.dataset.index ).padStart( 2, '0' );
					}
				} );
			},
			{ rootMargin: '-45% 0px -45% 0px', threshold: 0 }
		);
		cards.forEach( function ( card ) {
			io.observe( card );
		} );
	};
	document.querySelectorAll( '.wp-block-library-cards-stack' ).forEach( init );
} )();
