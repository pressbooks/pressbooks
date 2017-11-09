// This script is loaded when a user is on the [ Book Information ] page

jQuery( function ( $ ) {
	// Hack to get menu item highlighted
	$( '#' + PB_BookInfoToken.bookInfoMenuId ).removeClass( 'wp-not-current-submenu' ).addClass( 'current' );
	$( '#primary-subject' ).select2( {
		placeholder: PB_BookInfoToken.selectSubjectText,
		allowClear:  true,
		width:       '400px',
	} );
	$( '#additional-subjects' ).select2( {
		placeholder: PB_BookInfoToken.selectSubjectsText,
		allowClear:  true,
		width:       '100%',
	} );
} );
