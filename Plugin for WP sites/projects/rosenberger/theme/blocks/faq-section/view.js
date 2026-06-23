/**
 * FAQ-аккордеон: плавное раскрытие/сворачивание ответа (Web Animations API),
 * синхронная анимация иконки. <details> сохраняется для работы без JS.
 */
( function () {
	var DURATION = 280;

	function initItem( item ) {
		var summary = item.querySelector( 'summary' );
		var answer = item.querySelector( '.faq-section__answer' );
		if ( ! summary || ! answer ) {
			return;
		}

		summary.addEventListener( 'click', function ( e ) {
			e.preventDefault();
			if ( item.dataset.animating ) {
				return;
			}
			item.dataset.animating = '1';

			if ( item.open ) {
				// Сворачиваем: иконка сразу в «плюс», затем анимируем высоту в 0.
				item.classList.remove( 'is-open' );
				var startH = answer.scrollHeight;
				var anim = answer.animate(
					[ { height: startH + 'px', opacity: 1 }, { height: '0px', opacity: 0 } ],
					{ duration: DURATION, easing: 'ease', fill: 'forwards' }
				);
				anim.onfinish = function () {
					// Сначала скрываем контент, потом снимаем fill — без кадра-возврата.
					item.open = false;
					anim.cancel();
					answer.style.height = '';
					delete item.dataset.animating;
				};
			} else {
				// Раскрываем: открываем details, анимируем высоту от 0 до контента.
				item.open = true;
				item.classList.add( 'is-open' );
				var endH = answer.scrollHeight;
				var anim2 = answer.animate(
					[ { height: '0px', opacity: 0 }, { height: endH + 'px', opacity: 1 } ],
					{ duration: DURATION, easing: 'ease', fill: 'forwards' }
				);
				anim2.onfinish = function () {
					anim2.cancel();
					answer.style.height = '';
					delete item.dataset.animating;
				};
			}
		} );
	}

	function init() {
		var items = document.querySelectorAll( '.wp-block-library-faq-section .faq-section__item' );
		for ( var i = 0; i < items.length; i++ ) {
			initItem( items[ i ] );
		}
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
