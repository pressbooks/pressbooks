jQuery( document ).ready( function ( $ ) {
	// Set an initial focus to help users of assistive technology
	$( '#pb_title' ).trigger( 'focus' );

	const datePickers = document.querySelectorAll( 'duet-date-picker' );
	datePickers.forEach( datePicker => {
		datePicker.addEventListener( 'duetFocus', () => {
			const input = datePicker.querySelector( 'input.duet-date__input' );
			const ariaDescribedBy = datePicker.getAttribute( 'aria-describedby' );
			if ( input ) {
				input.setAttribute( 'aria-describedby', ariaDescribedBy );
			}
		} );
	} );
} );
