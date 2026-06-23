/**
 * FAQ-аккордеон. Анимация раскрытия — чистым CSS через
 * grid-template-rows: 0fr → 1fr (см. style.scss). JS только переключает классы.
 *
 * Прогрессивное улучшение: без JS обёртки открыты (контент доступен). JS
 * добавляет .is-enhanced (сворачивает закрытые пункты) и затем .is-ready
 * (включает переход) — чтобы первичное сворачивание прошло без анимации.
 */
( function () {
	function init() {
		var sections = document.querySelectorAll( '.wp-block-library-faq-section' );

		for ( var s = 0; s < sections.length; s++ ) {
			var section = sections[ s ];
			section.classList.add( 'is-enhanced' );

			var buttons = section.querySelectorAll( '.faq-section__q' );
			for ( var i = 0; i < buttons.length; i++ ) {
				buttons[ i ].addEventListener( 'click', function ( e ) {
					var item = e.currentTarget.closest( '.faq-section__item' );
					if ( ! item ) {
						return;
					}
					var open = item.classList.toggle( 'is-open' );
					e.currentTarget.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
				} );
			}
		}

		// Включаем переходы со следующего кадра — первичное сворачивание без анимации.
		requestAnimationFrame( function () {
			requestAnimationFrame( function () {
				for ( var k = 0; k < sections.length; k++ ) {
					sections[ k ].classList.add( 'is-ready' );
				}
			} );
		} );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
