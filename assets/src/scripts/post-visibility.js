jQuery( document ).ready( function ( $ ) {
	// Checkboxes
	let web_visibility = $( '#web_visibility' );
	let require_password = $( '#require_password' );

	// Init
	if ( web_visibility.is( ':checked' ) ) {
		$( '#pb-password-protected' ).show();
	} else {
		$( '#pb-password-protected' ).hide();
	}
	if ( require_password.is( ':checked' ) ) {
		$( '#post_password' ).show();
	} else {
		$( '#post_password' ).hide();
	}

	// On Change
	web_visibility.on( 'change', function () {
		if ( this.checked ) {
			$( '#pb-password-protected' ).show();
		} else {
			$( '#pb-password-protected' ).hide();
		}
	} );
	require_password.on( 'change', function () {
		if ( this.checked ) {
			$( '#post_password' ).show();
		} else {
			$( '#post_password' ).hide();
		}
	} );
} );
