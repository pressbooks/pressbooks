import addAriaDescribedBy from './utils/addAriaDescribedBy';

/* global PB_BookInfoToken */
jQuery( document ).ready( function ( $ ) {
	const inputs = [
		'pb_short_title', 'pb_publisher', 'pb_publisher_city', 'pb_publication_date', 'pb_onsale_date', 'pb_ebook_isbn',
		'pb_print_isbn', 'pb_language', 'pb_cover_image', 'primary-subject', 'additional-subjects', 'pb_is_based_on',
		'pb_copyright_year', 'pb_copyright_holder', 'pb_book_license', 'pb_about_140', 'pb_about_50', 'pb_series_title',
		'pb_series_number', 'pb_keywords_tags', 'pb_hashtag', 'pb_list_price_print', 'pb_list_price_pdf', 'pb_list_price_epub',
		'pb_list_price_web', 'pb_audience', 'pb_bisac_subject', 'pb_bisac_regional_theme',
	];

	for ( let input of inputs ) {
		addAriaDescribedBy( `#${input}`, 'span[class=description]', `${input}_description` );
	}

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
