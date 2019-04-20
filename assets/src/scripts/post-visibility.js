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
	web_visibility.change( function () {
		if ( this.checked ) {
			$( '#pb-password-protected' ).show();
		} else {
			$( '#pb-password-protected' ).hide();
		}
	} );
	require_password.change( function () {
		if ( this.checked ) {
			$( '#post_password' ).show();
		} else {
			$( '#post_password' ).hide();
		}
	} );

	// Accessibility fix: Chapter Type, Front Matter Type, and Back Matter Type selection menus in the sidebar are mising aria-labels
	// https://github.com/johnbillion/extended-cpts/issues/119
	$( '#front-matter-typedropdown' ).attr( 'aria-label', $( '#front-matter-typediv' ).find( 'h2:first' ).text() );
	$( '#chapter-typedropdown' ).attr( 'aria-label', $( '#chapter-typediv' ).find( 'h2:first' ).text() );
	$( '#back-matter-typedropdown' ).attr( 'aria-label', $( '#back-matter-typediv' ).find( 'h2:first' ).text() );
} );
