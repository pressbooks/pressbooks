// This script is loaded when a user is on the [ Theme Options ] page

jQuery( function ( $ ) {
	$( '.select2' ).select2();
	$( '.color-picker' ).wpColorPicker();
	let chapter_numbers = $( '#chapter_numbers' );

	$( document ).ready( function () {
		// Init
		if ( chapter_numbers.is( ':checked' ) ) {
			$( '#part_label, #chapter_label' )
				.parent()
				.parent()
				.show();
		} else {
			$( '#part_label, #chapter_label' )
				.parent()
				.parent()
				.hide();
		}

		// On change
		chapter_numbers.change( function () {
			if ( this.checked ) {
				$( '#part_label, #chapter_label' )
					.parent()
					.parent()
					.show();
			} else {
				$( '#part_label, #chapter_label' )
					.parent()
					.parent()
					.hide();
			}
		} );
		if ( $( '#pdf_page_size' ).val() !== '10' ) {
			$( '#pdf_page_width, #pdf_page_height' )
				.parent()
				.parent()
				.hide();
		}

		let running_content_front_matter_left_custom = $( 'input#running_content_front_matter_left_custom' );
		running_content_front_matter_left_custom.attr( 'aria-labelledby', 'running_content_front_matter_left' );
		$( 'select#running_content_front_matter_left' ).change( function () {
			let runningcontent = $( this ).val();
			running_content_front_matter_left_custom.val( runningcontent );
			if ( runningcontent === '' ) {
				running_content_front_matter_left_custom.focus().val( '' );
			}
		} );

		let running_content_front_matter_right_custom = $( 'input#running_content_front_matter_right_custom' );
		running_content_front_matter_right_custom.attr( 'aria-labelledby', 'running_content_front_matter_right' );
		$( 'select#running_content_front_matter_right' ).change( function () {
			let runningcontent = $( this ).val();
			running_content_front_matter_right_custom.val( runningcontent );
			if ( runningcontent === '' ) {
				running_content_front_matter_right_custom.focus().val( '' );
			}
		} );

		let running_content_introduction_left_custom = $( 'input#running_content_introduction_left_custom' );
		running_content_introduction_left_custom.attr( 'aria-labelledby', 'running_content_introduction_left' );
		$( 'select#running_content_introduction_left' ).change( function () {
			let runningcontent = $( this ).val();
			running_content_introduction_left_custom.val( runningcontent );
			if ( runningcontent === '' ) {
				running_content_introduction_left_custom.focus().val( '' );
			}
		} );

		let running_content_introduction_right_custom = $( 'input#running_content_introduction_right_custom' );
		running_content_introduction_right_custom.attr( 'aria-labelledby', 'running_content_introduction_right' );
		$( 'select#running_content_introduction_right' ).change( function () {
			let runningcontent = $( this ).val();
			running_content_introduction_right_custom.val( runningcontent );
			if ( runningcontent === '' ) {
				running_content_introduction_right_custom.focus().val( '' );
			}
		} );

		let running_content_part_left_custom = $( 'input#running_content_part_left_custom' );
		running_content_part_left_custom.attr( 'aria-labelledby', 'running_content_part_left' );
		$( 'select#running_content_part_left' ).change( function () {
			let runningcontent = $( this ).val();
			running_content_part_left_custom.val( runningcontent );
			if ( runningcontent === '' ) {
				running_content_part_left_custom.focus().val( '' );
			}
		} );

		let running_content_part_right_custom = $( 'input#running_content_part_right_custom' );
		running_content_part_right_custom.attr( 'aria-labelledby', 'running_content_part_right' );
		$( 'select#running_content_part_right' ).change( function () {
			let runningcontent = $( this ).val();
			running_content_part_right_custom.val( runningcontent );
			if ( runningcontent === '' ) {
				running_content_part_right_custom.focus().val( '' );
			}
		} );

		let running_content_chapter_left_custom = $( 'input#running_content_chapter_left_custom' );
		running_content_chapter_left_custom.attr( 'aria-labelledby', 'running_content_chapter_left' );
		$( 'select#running_content_chapter_left' ).change( function () {
			let runningcontent = $( this ).val();
			running_content_chapter_left_custom.val( runningcontent );
			if ( runningcontent === '' ) {
				running_content_chapter_left_custom.focus().val( '' );
			}
		} );

		let running_content_chapter_right_custom = $( 'input#running_content_chapter_right_custom' );
		running_content_chapter_right_custom.attr( 'aria-labelledby', 'running_content_chapter_right' );
		$( 'select#running_content_chapter_right' ).change( function () {
			let runningcontent = $( this ).val();
			running_content_chapter_right_custom.val( runningcontent );
			if ( runningcontent === '' ) {
				running_content_chapter_right_custom.focus().val( '' );
			}
		} );

		let running_content_back_matter_left_custom = $( 'input#running_content_back_matter_left_custom' );
		running_content_back_matter_left_custom.attr( 'aria-labelledby', 'running_content_back_matter_left' );
		$( 'select#running_content_back_matter_left' ).change( function () {
			let runningcontent = $( this ).val();
			running_content_back_matter_left_custom.val( runningcontent );
			if ( runningcontent === '' ) {
				running_content_back_matter_left_custom.focus().val( '' );
			}
		} );

		let running_content_back_matter_right_custom = $( 'input#running_content_back_matter_right_custom' );
		running_content_back_matter_right_custom.attr( 'aria-labelledby', 'running_content_back_matter_right' );
		$( 'select#running_content_back_matter_right' ).change( function () {
			let runningcontent = $( this ).val();
			running_content_back_matter_right_custom.val( runningcontent );
			if ( runningcontent === '' ) {
				running_content_back_matter_right_custom.focus().val( '' );
			}
		} );

		$( '#pdf_page_size' ).change( function () {
			let size = $( '#pdf_page_size' ).val();
			switch ( size ) {
				case '1':
					$( '#pdf_page_width' )
						.val( '5.5in' )
						.parent()
						.parent()
						.hide();
					$( '#pdf_page_height' )
						.val( '8.5in' )
						.parent()
						.parent()
						.hide();
					break;
				case '2':
					$( '#pdf_page_width' )
						.val( '6in' )
						.parent()
						.parent()
						.hide();
					$( '#pdf_page_height' )
						.val( '9in' )
						.parent()
						.parent()
						.hide();
					break;
				case '3':
					$( '#pdf_page_width' )
						.val( '8.5in' )
						.parent()
						.parent()
						.hide();
					$( '#pdf_page_height' )
						.val( '11in' )
						.parent()
						.parent()
						.hide();
					break;
				case '4':
					$( '#pdf_page_width' )
						.val( '8.5in' )
						.parent()
						.parent()
						.hide();
					$( '#pdf_page_height' )
						.val( '9.25in' )
						.parent()
						.parent()
						.hide();
					break;
				case '5':
					$( '#pdf_page_width' )
						.val( '5in' )
						.parent()
						.parent()
						.hide();
					$( '#pdf_page_height' )
						.val( '7.75in' )
						.parent()
						.parent()
						.hide();
					break;
				case '6':
					$( '#pdf_page_width' )
						.val( '4.25in' )
						.parent()
						.parent()
						.hide();
					$( '#pdf_page_height' )
						.val( '7in' )
						.parent()
						.parent()
						.hide();
					break;
				case '7':
					$( '#pdf_page_width' )
						.val( '21cm' )
						.parent()
						.parent()
						.hide();
					$( '#pdf_page_height' )
						.val( '29.7cm' )
						.parent()
						.parent()
						.hide();
					break;
				case '8':
					$( '#pdf_page_width' )
						.val( '14.8cm' )
						.parent()
						.parent()
						.hide();
					$( '#pdf_page_height' )
						.val( '21cm' )
						.parent()
						.parent()
						.hide();
					break;
				case '9':
					$( '#pdf_page_width' )
						.val( '5in' )
						.parent()
						.parent()
						.hide();
					$( '#pdf_page_height' )
						.val( '8in' )
						.parent()
						.parent()
						.hide();
					break;
				case '10':
					$( '#pdf_page_width' )
						.val( '' )
						.parent()
						.parent()
						.fadeToggle();
					$( '#pdf_page_height' )
						.val( '' )
						.parent()
						.parent()
						.fadeToggle();
					break;
				default:
					$( '#pdf_page_width' )
						.val( '5.5in' )
						.parent()
						.parent()
						.hide();
					$( '#pdf_page_height' )
						.val( '8.5in' )
						.parent()
						.parent()
						.hide();
			}
		} );
	} );
} );
