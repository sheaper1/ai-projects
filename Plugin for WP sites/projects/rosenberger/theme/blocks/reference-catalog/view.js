/**
 * Reference Catalog — AJAX-фильтрация (без перезагрузки).
 * Typ-Tabs / Lage / Sortierung / Seite → REST → подмена .rc-results.
 * Прогрессивное улучшение: без JS форма работает обычным GET.
 */
( function () {
	// Кастомный дропдаун поверх нативного <select> (нативный остаётся для значения).
	function enhanceSelect( select ) {
		if ( select.dataset.enhanced ) {
			return;
		}
		select.dataset.enhanced = '1';

		var wrap = document.createElement( 'div' );
		wrap.className = 'rc-select';
		var trigger = document.createElement( 'button' );
		trigger.type = 'button';
		trigger.className = 'rc-select__trigger';
		trigger.setAttribute( 'aria-haspopup', 'listbox' );
		trigger.setAttribute( 'aria-expanded', 'false' );
		var lead = select.classList.contains( 'rc-sort-select' )
			? '<svg class="rc-select__lead" width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 7h11M4 12h7M4 17h4M17 5v12m0 0 3-3m-3 3-3-3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>'
			: '';
		trigger.innerHTML = lead + '<span class="rc-select__value"></span><svg class="rc-select__chev" width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="m5 7.5 5 5 5-5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>';
		var valueEl = trigger.querySelector( '.rc-select__value' );
		var menu = document.createElement( 'div' );
		menu.className = 'rc-select__menu';
		menu.setAttribute( 'role', 'listbox' );

		function sync() {
			valueEl.textContent = select.options[ select.selectedIndex ].textContent;
			menu.querySelectorAll( '.rc-select__option' ).forEach( function ( o ) {
				var on = o.dataset.value === select.value;
				o.classList.toggle( 'is-selected', on );
				o.setAttribute( 'aria-selected', String( on ) );
			} );
		}

		Array.prototype.forEach.call( select.options, function ( opt ) {
			var item = document.createElement( 'button' );
			item.type = 'button';
			item.className = 'rc-select__option';
			item.setAttribute( 'role', 'option' );
			item.dataset.value = opt.value;
			item.textContent = opt.textContent;
			item.addEventListener( 'click', function () {
				select.value = opt.value;
				wrap.classList.remove( 'is-open' );
				trigger.setAttribute( 'aria-expanded', 'false' );
				select.dispatchEvent( new Event( 'change', { bubbles: true } ) );
			} );
			menu.appendChild( item );
		} );

		trigger.addEventListener( 'click', function ( e ) {
			e.stopPropagation();
			var open = wrap.classList.toggle( 'is-open' );
			trigger.setAttribute( 'aria-expanded', String( open ) );
		} );
		document.addEventListener( 'click', function ( e ) {
			if ( ! wrap.contains( e.target ) ) {
				wrap.classList.remove( 'is-open' );
				trigger.setAttribute( 'aria-expanded', 'false' );
			}
		} );
		select.addEventListener( 'change', sync );

		select.classList.add( 'rc-select__native' );
		select.parentNode.insertBefore( wrap, select );
		wrap.appendChild( trigger );
		wrap.appendChild( menu );
		sync();
	}

	// Карусель фото на карточке (общий RbCarousel: drag + бесконечный цикл).
	// Карточка — <a>: гасим переход по клику на стрелки и по завершении драга.
	function initCardCarousels( scope ) {
		scope.querySelectorAll( '.rc-card__carousel' ).forEach( function ( car ) {
			if ( car.dataset.rbInit ) {
				return;
			}
			car.dataset.rbInit = '1';
			car.querySelectorAll( '[data-prev], [data-next]' ).forEach( function ( btn ) {
				btn.addEventListener( 'click', function ( e ) {
					e.preventDefault();
					e.stopPropagation();
				} );
			} );
			if ( window.RbCarousel ) {
				window.RbCarousel( car, { frame: car } );
			}
		} );
	}

	document.querySelectorAll( '.reference-catalog' ).forEach( function ( root ) {
		var form    = root.querySelector( '.rc-bar' );
		var results = root.querySelector( '.rc-results' );
		if ( ! form || ! results ) {
			return;
		}
		initCardCarousels( results );
		var endpoint = results.dataset.endpoint;
		var perPage  = ( form.querySelector( '[name="rc_per_page"]' ) || {} ).value || 8;
		var sort     = form.querySelector( '.rc-sort-select' );
		var page     = 1;

		function cleanParams() {
			var p = new URLSearchParams();
			new FormData( form ).forEach( function ( v, k ) {
				if ( 'rc_per_page' === k || '' === v ) {
					return;
				}
				p.append( k, v );
			} );
			if ( page > 1 ) {
				p.set( 'rc_page', page );
			}
			return p;
		}

		function syncTabs() {
			form.querySelectorAll( '.rc-tab' ).forEach( function ( tab ) {
				var input = tab.querySelector( 'input' );
				tab.classList.toggle( 'is-active', !! ( input && input.checked ) );
			} );
		}

		function refresh() {
			var urlParams   = cleanParams();
			var fetchParams = new URLSearchParams( urlParams.toString() );
			fetchParams.set( 'rc_per_page', perPage );
			fetchParams.set( 'rc_page', page );

			results.classList.add( 'is-loading' );
			fetch( endpoint + '?' + fetchParams.toString() )
				.then( function ( r ) { return r.json(); } )
				.then( function ( data ) {
					results.innerHTML = data && data.html ? data.html : '';
					results.classList.remove( 'is-loading' );
					initCardCarousels( results );
				} )
				.catch( function () { results.classList.remove( 'is-loading' ); } );

			var qs = urlParams.toString();
			history.replaceState( null, '', location.pathname + ( qs ? '?' + qs : '' ) );
		}

		form.addEventListener( 'change', function () {
			syncTabs();
			page = 1;
			refresh();
		} );

		if ( sort ) {
			enhanceSelect( sort );
		}
		var ort = form.querySelector( '.rc-ort-select' );
		if ( ort ) {
			enhanceSelect( ort );
		}

		results.addEventListener( 'click', function ( e ) {
			var btn = e.target.closest( '.rc-page-btn' );
			if ( ! btn ) {
				return;
			}
			e.preventDefault();
			page = parseInt( btn.getAttribute( 'data-page' ), 10 ) || 1;
			refresh();
			window.scrollTo( {
				top: root.getBoundingClientRect().top + window.scrollY - 80,
				behavior: 'smooth',
			} );
		} );

		syncTabs();
	} );
}() );
