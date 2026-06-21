document.querySelectorAll( '.pc-filters' ).forEach( function( form ) {
	var selects = form.querySelectorAll( 'select' );

	function closeAllCustomSelects( except ) {
		form.querySelectorAll( '.pc-filter.is-open' ).forEach( function( filter ) {
			if ( filter !== except ) {
				filter.classList.remove( 'is-open' );
			}
		} );
	}

	selects.forEach( function( sel ) {
		var filter = sel.closest( '.pc-filter' );
		if ( ! filter || filter.querySelector( '.pc-select' ) ) {
			return;
		}

		sel.classList.add( 'pc-native-select' );

		var custom = document.createElement( 'div' );
		custom.className = 'pc-select';

		var trigger = document.createElement( 'button' );
		trigger.className = 'pc-select__trigger';
		trigger.type = 'button';
		trigger.setAttribute( 'aria-haspopup', 'listbox' );
		trigger.setAttribute( 'aria-expanded', 'false' );
		trigger.innerHTML = '<span class="pc-select__label"></span><span class="pc-select__icon" aria-hidden="true"></span>';

		var list = document.createElement( 'div' );
		list.className = 'pc-select__menu';
		list.setAttribute( 'role', 'listbox' );

		function syncLabel() {
			var selectedOption = sel.options[ sel.selectedIndex ];
			trigger.querySelector( '.pc-select__label' ).textContent = selectedOption ? selectedOption.textContent : '';
		}

		Array.prototype.forEach.call( sel.options, function( option, index ) {
			var item = document.createElement( 'button' );
			item.className = 'pc-select__option';
			item.type = 'button';
			item.setAttribute( 'role', 'option' );
			item.setAttribute( 'data-value', option.value );
			item.textContent = option.textContent;

			if ( index === sel.selectedIndex ) {
				item.classList.add( 'is-selected' );
				item.setAttribute( 'aria-selected', 'true' );
			} else {
				item.setAttribute( 'aria-selected', 'false' );
			}

			item.addEventListener( 'click', function() {
				sel.value = option.value;
				sel.dispatchEvent( new Event( 'change', { bubbles: true } ) );
			} );

			list.appendChild( item );
		} );

		trigger.addEventListener( 'click', function() {
			var isOpen = filter.classList.contains( 'is-open' );
			closeAllCustomSelects( filter );
			filter.classList.toggle( 'is-open', ! isOpen );
			trigger.setAttribute( 'aria-expanded', String( ! isOpen ) );
		} );

		sel.addEventListener( 'change', function() {
			syncLabel();

			list.querySelectorAll( '.pc-select__option' ).forEach( function( optionButton ) {
				var isSelected = optionButton.getAttribute( 'data-value' ) === sel.value;
				optionButton.classList.toggle( 'is-selected', isSelected );
				optionButton.setAttribute( 'aria-selected', String( isSelected ) );
			} );

			filter.classList.remove( 'is-open' );
			trigger.setAttribute( 'aria-expanded', 'false' );
			form.submit();
		} );

		custom.appendChild( trigger );
		custom.appendChild( list );
		filter.appendChild( custom );
		syncLabel();
	} );

	document.addEventListener( 'click', function( event ) {
		if ( ! form.contains( event.target ) ) {
			closeAllCustomSelects();
		}
	} );

	document.addEventListener( 'keydown', function( event ) {
		if ( event.key === 'Escape' ) {
			closeAllCustomSelects();
		}
	} );
} );
