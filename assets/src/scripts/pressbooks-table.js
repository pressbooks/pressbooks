// document ready
document.addEventListener( 'DOMContentLoaded', function () {
	document.querySelectorAll( '.sorting-button' ).forEach( button => {
		button.addEventListener( 'click', function ( event ) {
			event.preventDefault();
			window.location.href = this.dataset.href;
		} );
	} );
} );
