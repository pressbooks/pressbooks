// This script is loaded when a user is on the [ Book Information ] page

jQuery( function ( $ ) {
	// Hack to get menu item highlighted
	$( '#' + PB_BookInfoToken.bookInfoMenuId ).removeClass( 'wp-not-current-submenu' ).addClass( 'current' );
	$( '#general-subject, #academic-subject' ).select2( {
		placeholder: PB_BookInfoToken.selectSubjectText,
		allowClear:  true,
		width:       '400px',
	} );
	if ( $( '#subject-type' ).val() === 'general' ) {
		$( '#s2id_general-subject' ).show();
		$( '#s2id_academic-subject' ).hide();
		$( '#academic-subject' ).select2( 'val', '' );
	} else {
		$( '#s2id_academic-subject' ).show();
		$( '#s2id_general-subject' ).hide();
		$( '#general-subject' ).select2( 'val', '' );
	}
	$( '#subject-type' ).on( 'change', function ( e ) {
		if ( e.currentTarget.value === 'general' ) {
			$( '#s2id_general-subject' ).show();
			$( '#s2id_academic-subject' ).hide();
			$( '#academic-subject' ).select2( 'val', '' );
		} else {
			$( '#s2id_academic-subject' ).show();
			$( '#s2id_general-subject' ).hide();
			$( '#general-subject' ).select2( 'val', '' );
		}
	} );
	$( '#general-subject, #academic-subject' ).on( 'change', function ( e ) {
		if ( e.currentTarget.value !== '' ) {
			$( '#pb-subject' ).val( e.currentTarget.value );
		}
	} );
} );
