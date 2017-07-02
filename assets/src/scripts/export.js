// This script is loaded when a user is on the [ Export ] page

jQuery( function ( $ ) {
    /* Swap out and animate the 'Export Your Book' button */
	$( '#pb-export-button' ).click( function () {
		$( '.export-file-container' ).unbind( 'mouseenter mouseleave' ); // Disable Download & Delete Buttons
		$( '#loader' ).show();
		$( '#pb-export-button' ).hide();
		$( '#pb-export-form' ).submit();
	} );
    /* Show and hide download & delete button */
	$( '.export-file-container' ).hover(
		function () {
			$( this ).children( '.file-actions' ).css( 'visibility', 'visible' );
		},
	  function () {
		$( this ).children( '.file-actions' ).css( 'visibility', 'hidden' );
	} );

    /* Remember User Checkboxes */
	$( '#pb-export-form' ).find( 'input' ).each( function () {
		let name = $( this ).attr( 'name' );
		let val = Cookies.get( 'pb_' + name );
		let v;
        // Defaults
		if ( typeof val === 'undefined' ) {
            // Defaults
			if (
        name === 'export_formats[pdf]' ||
        name === 'export_formats[mpdf]' ||
        name === 'export_formats[epub]' ||
        name === 'export_formats[mobi]'
      ) {
				$( this ).prop( 'checked', true );
			} else {
				$( this ).prop( 'checked', false );
			}
		} else {
      // Toggle based on user's cookie
			if ( typeof val === 'boolean' ) {
				v = val;
			} else {
				v = ( val === 'true' );
			}
			$( this ).prop( 'checked', v );
		}
		if ( $( this ).attr( 'disabled' ) ) {
			$( this ).prop( 'checked', false );
		}
	} ).change( function () {
		Cookies.set( 'pb_' + $( this ).attr( 'name' ), $( this ).prop( 'checked' ), {
			path:    '/',
			expires: 365,
		} );
	} );
} );
