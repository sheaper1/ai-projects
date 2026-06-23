/**
 * Property Catalog — AJAX-фильтрация каталога (без перезагрузки).
 * Изменение фильтра/сортировки/страницы → REST → подмена .pc-results.
 * Прогрессивное улучшение: без JS форма работает обычным GET.
 */
( function () {
	document.querySelectorAll( '.pc-catalog' ).forEach( function ( root ) {
		var form    = root.querySelector( '.pc-filter-form' );
		var sort    = root.querySelector( '.pc-sort-select' );
		var results = root.querySelector( '.pc-results' );
		if ( ! form || ! results ) {
			return;
		}
		var endpoint = results.dataset.endpoint;
		var perPage  = ( form.querySelector( '[name="pc_per_page"]' ) || {} ).value || 9;
		var page     = 1;
		var debounce;

		// Параметры фильтра без пустых значений (для shareable URL).
		function cleanParams() {
			var p = new URLSearchParams();
			new FormData( form ).forEach( function ( v, k ) {
				if ( 'pc_per_page' === k || '' === v ) {
					return;
				}
				p.append( k, v );
			} );
			if ( sort && 'newest' !== sort.value ) {
				p.set( 'pc_sort', sort.value );
			}
			if ( page > 1 ) {
				p.set( 'pc_page', page );
			}
			return p;
		}

		function syncToggle() {
			form.querySelectorAll( '.pc-toggle__option' ).forEach( function ( opt ) {
				var input = opt.querySelector( 'input' );
				opt.classList.toggle( 'is-active', !! ( input && input.checked ) );
			} );
		}

		function refresh() {
			var urlParams   = cleanParams();
			var fetchParams = new URLSearchParams( urlParams.toString() );
			fetchParams.set( 'pc_per_page', perPage );
			fetchParams.set( 'pc_page', page );

			results.classList.add( 'is-loading' );
			fetch( endpoint + '?' + fetchParams.toString() )
				.then( function ( r ) { return r.json(); } )
				.then( function ( data ) {
					results.innerHTML = data && data.html ? data.html : '';
					results.classList.remove( 'is-loading' );
				} )
				.catch( function () { results.classList.remove( 'is-loading' ); } );

			var qs = urlParams.toString();
			history.replaceState( null, '', location.pathname + ( qs ? '?' + qs : '' ) );
		}

		// Чекбоксы / радио / прочее (кроме числовых — у них input ниже).
		form.addEventListener( 'change', function ( e ) {
			if ( 'number' === e.target.type ) {
				return;
			}
			syncToggle();
			page = 1;
			refresh();
		} );

		// Числовые диапазоны — с задержкой, пока пользователь печатает.
		form.addEventListener( 'input', function ( e ) {
			if ( 'number' !== e.target.type ) {
				return;
			}
			page = 1;
			clearTimeout( debounce );
			debounce = setTimeout( refresh, 450 );
		} );

		if ( sort ) {
			sort.addEventListener( 'change', function () { page = 1; refresh(); } );
		}

		// Пагинация — делегирование (сетка перерисовывается).
		results.addEventListener( 'click', function ( e ) {
			var btn = e.target.closest( '.pc-page-btn' );
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

		// Сброс фильтра.
		var reset = form.querySelector( '.pc-filter-reset' );
		if ( reset ) {
			reset.addEventListener( 'click', function ( e ) {
				e.preventDefault();
				form.querySelectorAll( 'input[type="checkbox"]' ).forEach( function ( c ) { c.checked = false; } );
				form.querySelectorAll( 'input[type="number"]' ).forEach( function ( n ) { n.value = ''; } );
				var kaufen = form.querySelector( '.pc-toggle__option input[value=""]' );
				if ( kaufen ) { kaufen.checked = true; }
				if ( sort ) { sort.value = 'newest'; }
				page = 1;
				syncToggle();
				refresh();
			} );
		}

		// Мобильный аккордеон фильтра.
		var toggle = form.querySelector( '.pc-filter-toggle' );
		if ( toggle ) {
			toggle.addEventListener( 'click', function () {
				var open = form.classList.toggle( 'is-filter-open' );
				toggle.setAttribute( 'aria-expanded', String( open ) );
			} );
		}

		syncToggle();
	} );
} )();
