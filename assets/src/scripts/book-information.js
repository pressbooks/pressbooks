// This script is loaded when a user is on the [ Book Information ] page

jQuery( document ).ready( function ( $ ) {
	$( '#primary-subject' ).select2( {
		placeholder: PB_BookInfoToken.selectSubjectText,
		allowClear: true,
		width: '400px',
	} );
	$( '#additional-subjects' ).select2( {
		placeholder: PB_BookInfoToken.selectSubjectsText,
		allowClear: true,
		width: '100%',
		minimumInputLength: 2,
	} );
} );
