/**
 * Универсальный scroll-reveal для всех блоков библиотеки.
 *
 * Навешивается автоматически на каждый блок (класс wp-block-library-*) —
 * правка отдельных блоков не нужна. Блок плавно проявляется (fade-up) при
 * попадании в зону видимости.
 *
 * Безопасность:
 *  - при отключённом JS / для краулеров контент виден всегда (скрытое
 *    состояние включается классом .reveal-ready, который ставит сам JS);
 *  - уважает prefers-reduced-motion: при «уменьшить движение» анимации нет.
 */
( function () {
	var SELECTOR = '[class*="wp-block-library-"]';
	var docEl = document.documentElement;

	// Полный выход (контент остаётся видимым) — без анимаций.
	var prefersReduced =
		window.matchMedia &&
		window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
	if ( prefersReduced || ! ( 'IntersectionObserver' in window ) ) {
		return;
	}

	// Помечаем как JS-capable ДО первой отрисовки, чтобы блоки стартовали
	// скрытыми без «мигания» (скрипт подключён в <head>, не в футере).
	docEl.classList.add( 'reveal-ready' );

	var observer = new IntersectionObserver(
		function ( entries ) {
			entries.forEach( function ( entry ) {
				if ( entry.isIntersecting ) {
					entry.target.classList.add( 'is-revealed' );
					observer.unobserve( entry.target );
				}
			} );
		},
		{ threshold: 0.08, rootMargin: '0px 0px -8% 0px' }
	);

	function start() {
		var blocks = document.querySelectorAll( SELECTOR );
		for ( var i = 0; i < blocks.length; i++ ) {
			observer.observe( blocks[ i ] );
		}
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', start );
	} else {
		start();
	}
} )();
