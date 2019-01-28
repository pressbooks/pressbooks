/* global PB_ThemeLockToken */

jQuery( function ( $ ) {
	$( '#theme_lock' ).change( function () {
		if ( ! this.checked ) {
			if ( window.confirm( PB_ThemeLockToken.confirmation ) ) {
				$( '#theme_lock' ).attr( 'checked', false );
			} else {
				$( '#theme_lock' ).attr( 'checked', true );
			}
		}
	} );
} );
