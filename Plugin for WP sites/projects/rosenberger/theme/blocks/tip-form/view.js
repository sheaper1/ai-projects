/* Tipp einsenden — 3-шаговая форма. Навигация по шагам + мост к скрытой WPForms.
 * Видимая форма заполняет скрытое настоящее WPForms-поле и кликает нативный submit
 * → реальный entry + письмо + редирект /danke/. Принцип как у contact-section. */
( function () {
	var form = document.querySelector( '.tip-form__card[data-tip-form]' );
	if ( ! form ) return;

	var formId = parseInt( form.getAttribute( 'data-wpforms-id' ), 10 ) || 0;
	var fields = {};
	try { fields = JSON.parse( form.getAttribute( 'data-wpforms-fields' ) || '{}' ); } catch ( e ) { fields = {}; }

	var panels = [].slice.call( form.querySelectorAll( '.tip-form__panel' ) );
	var stepInds = [].slice.call( form.querySelectorAll( '.tip-form__step' ) );
	var errorEl = form.querySelector( '[data-tf-error]' );
	var submitBtn = form.querySelector( 'button[type="submit"]' );
	var current = 1;

	function showStep( n ) {
		current = n;
		panels.forEach( function ( p ) {
			p.hidden = parseInt( p.getAttribute( 'data-step' ), 10 ) !== n;
		} );
		stepInds.forEach( function ( s ) {
			var i = parseInt( s.getAttribute( 'data-step-ind' ), 10 );
			s.classList.toggle( 'is-active', i === n );
			s.classList.toggle( 'is-done', i < n );
		} );
		var top = form.getBoundingClientRect().top + window.pageYOffset - 120;
		if ( window.pageYOffset > top ) window.scrollTo( { top: top, behavior: 'smooth' } );
	}

	function validatePanel( n ) {
		var panel = panels[ n - 1 ];
		var ok = true;
		panel.querySelectorAll( '[required]' ).forEach( function ( el ) {
			var valid = el.value && el.value.trim() !== '' && ( el.type !== 'email' || /.+@.+\..+/.test( el.value ) );
			el.classList.toggle( 'is-invalid', ! valid );
			if ( ! valid && ok ) { el.focus(); ok = false; }
		} );
		return ok;
	}

	form.addEventListener( 'click', function ( e ) {
		var next = e.target.closest( '[data-next]' );
		var back = e.target.closest( '[data-back]' );
		if ( next ) { e.preventDefault(); if ( validatePanel( current ) ) showStep( Math.min( 3, current + 1 ) ); }
		if ( back ) { e.preventDefault(); showStep( Math.max( 1, current - 1 ) ); }
	} );

	// Сводка Objektangaben для письма (всё, что не контакт).
	function buildSummary() {
		var get = function ( slug ) { var el = form.querySelector( '[data-tf="' + slug + '"]' ); return el ? el.value.trim() : ''; };
		var lines = [];
		var add = function ( label, slug ) { var v = get( slug ); if ( v ) lines.push( label + ': ' + v ); };
		add( 'Adresse', 'adresse' );
		add( 'Objektart', 'objektart' );
		add( 'Bezug zur Immobilie', 'bezug' );
		add( 'Situation', 'situation' );
		return lines.join( '\n' );
	}

	function hiddenSubmit() { return document.getElementById( 'wpforms-submit-' + formId ); }
	function wpfSet( fieldId, value ) {
		if ( fieldId == null ) return;
		var el = document.getElementById( 'wpforms-' + formId + '-field_' + fieldId );
		if ( el && ( el.tagName === 'SELECT' || el.tagName === 'INPUT' || el.tagName === 'TEXTAREA' ) ) {
			el.value = value;
			el.dispatchEvent( new Event( 'input', { bubbles: true } ) );
			el.dispatchEvent( new Event( 'change', { bubbles: true } ) );
		}
	}
	function showError() {
		if ( errorEl ) errorEl.hidden = false;
		if ( submitBtn ) { submitBtn.disabled = false; submitBtn.textContent = 'Tipp absenden'; }
	}

	form.addEventListener( 'submit', function ( e ) {
		e.preventDefault();
		if ( errorEl ) errorEl.hidden = true;
		if ( ! validatePanel( 3 ) ) return;
		if ( ! formId || ! hiddenSubmit() ) { showError(); return; }

		var contact = { anrede: 'anrede', vorname: 'vorname', nachname: 'nachname', email: 'email', telefon: 'telefon' };
		Object.keys( contact ).forEach( function ( slug ) {
			var el = form.querySelector( '[data-tf="' + contact[ slug ] + '"]' );
			if ( el && el.value.trim() !== '' && fields[ slug ] != null ) wpfSet( fields[ slug ], el.value.trim() );
		} );
		if ( fields.summary != null ) wpfSet( fields.summary, buildSummary() );

		if ( submitBtn ) { submitBtn.disabled = true; submitBtn.textContent = 'Wird gesendet …'; }
		hiddenSubmit().click(); // WPForms validiert, sendet per AJAX und macht den Redirect /danke/
	} );

	if ( window.jQuery && formId ) {
		window.jQuery( document ).on( 'wpformsAjaxSubmitFailed', '#wpforms-form-' + formId, showError );
	}
}() );
