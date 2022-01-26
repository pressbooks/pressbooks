/* global PB_BookInfoToken */

jQuery( document ).ready( function ( $ ) {
	// Set an initial focus to help users of assistive technology
	$( '#pb_title' ).trigger( 'focus' );
	// Select2, The jQuery replacement for select boxes
	$( '#primary-subject' ).select2( {
		placeholder: PB_BookInfoToken.selectSubjectText,
		allowClear: true,
		width: '400px',
		ajax: {
			url: PB_BookInfoToken.ajaxUrl + ( PB_BookInfoToken.ajaxUrl.includes( '?' ) ? '&' : '?' ) + 'includeQualifiers=0',
			dataType: 'json',
			delay: 250,
		},
	} );
	$( '#additional-subjects' ).select2( {
		placeholder: PB_BookInfoToken.selectSubjectsText,
		allowClear: true,
		width: '100%',
		minimumInputLength: 2,
		ajax: {
			url: PB_BookInfoToken.ajaxUrl + ( PB_BookInfoToken.ajaxUrl.includes( '?' ) ? '&' : '?' ) + 'includeQualifiers=1',
			dataType: 'json',
			delay: 250,
		},
	} );
} );
