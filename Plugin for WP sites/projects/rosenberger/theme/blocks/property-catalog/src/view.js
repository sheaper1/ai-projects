// Auto-submit filter form on dropdown change
document.querySelectorAll( '.pc-filters select' ).forEach( sel => {
	sel.addEventListener( 'change', () => sel.closest( 'form' ).submit() );
} );
