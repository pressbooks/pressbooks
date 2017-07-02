jQuery( function ( $  ) { // eslint-disable-line
	$( '#theme_lock' ).change( function () {
		if ( ! this.checked ) {
			if ( window.confirm( PB_ThemeLockToken.confirmation ) ) { // eslint-disable-line
				$( '#theme_lock' ).attr( 'checked', false );
			} else {
				$( '#theme_lock' ).attr( 'checked', true );
			}
		}
	} );
} );
