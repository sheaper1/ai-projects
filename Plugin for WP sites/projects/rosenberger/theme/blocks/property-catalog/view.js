// Auto-submit filter form on dropdown change
document.querySelectorAll( '.pc-filters select' ).forEach( function( sel ) {
	sel.addEventListener( 'change', function() {
		sel.closest( 'form' ).submit();
	} );
} );
