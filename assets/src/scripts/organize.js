// This script is loaded when a user is on the [ Text → Organize ] page

import CountUp from 'countup.js';

let Pressbooks = {
	oldPart:        null,
	newPart:        null,
	defaultOptions: {
		revert:      true,
		helper:      'clone',
		zIndex:      2700,
		distance:    3,
		opacity:     0.6,
		placeholder: 'ui-state-highlight',
		connectWith: '.chapters',
		dropOnEmpty: true,
		cursor:      'crosshair',
		items:       'tbody > tr',
		start:       function ( index, el ) {
			Pressbooks.oldPart = el.item.parents( 'table' ).attr( 'id' );
		},
		stop: function ( index, el ) {
			Pressbooks.newPart = el.item.parents( 'table' ).attr( 'id' );
			Pressbooks.update( el.item );
		},
	},
	frontMatterOptions: {
		revert:      true,
		helper:      'clone',
		zIndex:      2700,
		distance:    3,
		opacity:     0.6,
		placeholder: 'ui-state-highlight',
		dropOnEmpty: true,
		cursor:      'crosshair',
		items:       'tbody > tr',
		start:       function ( index, el ) {
			// alert(el);
		},
		stop: function ( index, el ) {
			Pressbooks.fmupdate( el.item );
		},
	},
	backMatterOptions: {
		revert:      true,
		helper:      'clone',
		zIndex:      2700,
		distance:    3,
		opacity:     0.6,
		placeholder: 'ui-state-highlight',
		dropOnEmpty: true,
		cursor:      'crosshair',
		items:       'tbody > tr',
		start:       function ( index, el ) {
			// alert(el);
		},
		stop: function ( index, el ) {
			Pressbooks.bmupdate( el.item );
		},
	},
	update: function ( el ) {
		jQuery.ajax( {
			beforeSend: function () {
				jQuery.blockUI.defaults.applyPlatformOpacityRules = false;
				jQuery.blockUI( { message: jQuery( '#loader.chapter' ) } );
			},
			url:  ajaxurl,
			type: 'POST',
			data: {
				action:         'pb_update_chapter',
				// see http://forum.jquery.com/topic/sortable-serialize-not-changing-sort-order-over-3-div-cols
				new_part_order: jQuery( '#' + Pressbooks.newPart ).sortable( 'serialize' ),
				old_part_order: jQuery( '#' + Pressbooks.oldPart ).sortable( 'serialize' ),
				new_part:       Pressbooks.newPart.replace( /^part-([0-9]+)$/i, '$1' ),
				old_part:       Pressbooks.oldPart.replace( /^part-([0-9]+)$/i, '$1' ),
				id:             jQuery( el )
					.attr( 'id' )
					.replace( /^chapter-([0-9]+)$/i, '$1' ),
				_ajax_nonce: PB_OrganizeToken.orderNonce,
			},
			cache:    false,
			dataType: 'html',
			error:    function ( obj, status, thrown ) {
				jQuery( '#message' )
					.html(
						'<p><strong>There has been an error updating your chapter data. Usually, <a href="' +
							window.location.href +
							'">refreshing the page</a> helps.</strong></p>'
					)
					.addClass( 'error' );
				// window.setTimeout(function(){window.location.replace(window.location.href)}, 5000, true);
			},
			success: function ( htmlStr ) {
				if ( htmlStr === 'NOCHANGE' ) {
					jQuery( '#message' )
						.html( '<p><strong>No changes were registered.</strong></p>' )
						.addClass( 'error' );
				} else {
					// Chapters have been reordered.
				}
			},
			complete: function () {
				jQuery.unblockUI();
			},
		} );
	},

	fmupdate: function ( el ) {
		jQuery.ajax( {
			beforeSend: function () {
				jQuery.blockUI.defaults.applyPlatformOpacityRules = false;
				jQuery.blockUI( { message: jQuery( '#loader.fm' ) } );
			},
			url:  ajaxurl,
			type: 'POST',
			data: {
				action:             'pb_update_front_matter',
				front_matter_order: jQuery( '#front-matter' ).sortable( 'serialize' ),
				_ajax_nonce:        PB_OrganizeToken.orderNonce,
			},
			cache:    false,
			dataType: 'html',
			error:    function ( obj, status, thrown ) {
				jQuery( '#message' )
					.html(
						'<p><strong>There has been an error updating your front matter data Usually, <a href="' +
							window.location.href +
							'">refreshing the page</a> helps.</strong></p>'
					)
					.addClass( 'error' );
				//window.setTimeout(function(){window.location.replace(window.location.href)}, 5000, true);
			},
			success: function ( htmlStr ) {
				if ( htmlStr === 'NOCHANGE' ) {
					jQuery( '#message' )
						.html( '<p><strong>No changes were registered.</strong></p>' )
						.addClass( 'error' );
				} else {
					// Front Matter has been reordered.
				}
			},
			complete: function () {
				jQuery.unblockUI();
			},
		} );
	},

	bmupdate: function ( el ) {
		jQuery.ajax( {
			beforeSend: function () {
				jQuery.blockUI.defaults.applyPlatformOpacityRules = false;
				jQuery.blockUI( { message: jQuery( '#loader.bm' ) } );
			},
			url:  ajaxurl,
			type: 'POST',
			data: {
				action:            'pb_update_back_matter',
				back_matter_order: jQuery( '#back-matter' ).sortable( 'serialize' ),
				_ajax_nonce:       PB_OrganizeToken.orderNonce,
			},
			cache:    false,
			dataType: 'html',
			error:    function ( obj, status, thrown ) {
				jQuery( '#message' )
					.html(
						'<p><strong>There has been an error updating your back matter data. Usually, <a href="' +
							window.location.href +
							'">refreshing the page</a> helps.</strong></p>'
					)
					.addClass( 'error' );
				//window.setTimeout(function(){window.location.replace(window.location.href)}, 5000, true);
			},
			success: function ( htmlStr ) {
				if ( htmlStr === 'NOCHANGE' ) {
					jQuery( '#message' )
						.html( '<p><strong>No changes were registered.</strong></p>' )
						.addClass( 'error' );
				} else {
					// Back Matter has been reordered.
				}
			},
			complete: function () {
				jQuery.unblockUI();
			},
		} );
	},
};

// --------------------------------------------------------------------------------------------------------------------

jQuery( document ).ready( function ( $ ) {
	// Init drag & drop
	$( 'table.chapters' )
		.sortable( Pressbooks.defaultOptions )
		.disableSelection();
	$( 'table#front-matter' )
		.sortable( Pressbooks.frontMatterOptions )
		.disableSelection();
	$( 'table#back-matter' )
		.sortable( Pressbooks.backMatterOptions )
		.disableSelection();

	// Public/Private form at top of page
	$( 'input[name=blog_public]' ).change( function () {
		let blog_public;
		if ( parseInt( this.value, 10 ) === 1 ) {
			blog_public = 1;
		} else {
			blog_public = 0;
		}
		$.ajax( {
			url:  ajaxurl,
			type: 'POST',
			data: {
				action:      'pb_update_global_privacy_options',
				blog_public: blog_public,
				_ajax_nonce: PB_OrganizeToken.privacyNonce,
			},
			beforeSend: function () {
				if ( blog_public === 0 ) {
					$( 'h4.publicize-alert > span' ).text( PB_OrganizeToken.private );
					$( 'label span.public' ).css( 'font-weight', 'normal' );
					$( 'label span.private' ).css( 'font-weight', 'bold' );
					$( '.publicize-alert' )
						.removeClass( 'public' )
						.addClass( 'private' );
				} else if ( blog_public === 1 ) {
					$( 'h4.publicize-alert > span' ).text( PB_OrganizeToken.public );
					$( 'label span.public' ).css( 'font-weight', 'bold' );
					$( 'label span.private' ).css( 'font-weight', 'normal' );
					$( '.publicize-alert' )
						.removeClass( 'private' )
						.addClass( 'public' );
				}
			},
			error: function ( xhr, ajaxOptions, thrownError ) {
				// TODO, catch error
			},
		} );
	} );

	// Chapter switches

	$( '.chapter_privacy' ).change( function () {
		let post_status;

		let col = $( this )
			.parent()
			.prev( '.column-status' );
		let id = $( this ).attr( 'id' );
		id = id.split( '_' );
		id = id[id.length - 1];

		if ( $( this ).is( ':checked' ) ) {
			post_status = 'private';
		} else {
			post_status = 'publish';
		}

		$.ajax( {
			url:  ajaxurl,
			type: 'POST',
			data: {
				action:      'pb_update_privacy_options',
				post_id:     id,
				post_status: post_status,
				_ajax_nonce: PB_OrganizeToken.privacyNonce,
			},
			beforeSend: function () {
				if ( post_status === 'private' ) {
					col.text( PB_OrganizeToken.private );
				} else {
					col.text( PB_OrganizeToken.published );
				}
			},
			error: function ( xhr, ajaxOptions, thrownError ) {
				// TODO, catch error
			},
		} );
	} );

	$( '.chapter_show_title_check' ).change( function () {
		let chapter_show_title;

		let id = $( this ).attr( 'id' );
		id = id.split( '_' );
		id = id[id.length - 1];

		if ( $( this ).is( ':checked' ) ) {
			chapter_show_title = 1;
		} else {
			chapter_show_title = 0;
		}

		$.ajax( {
			url:  ajaxurl,
			type: 'POST',
			data: {
				action:             'pb_update_show_title_options',
				post_id:            id,
				chapter_show_title: chapter_show_title,
				type:               'pb_show_title',
				_ajax_nonce:        PB_OrganizeToken.showTitleNonce,
			},
		} );
	} );

	$( '.chapter_export_check' ).change( function () {
		let chapter_export;

		let id = $( this ).attr( 'id' );
		id = id.split( '_' );
		id = id[id.length - 1];

		if ( $( this ).is( ':checked' ) ) {
			chapter_export = 1;
		} else {
			chapter_export = 0;
		}

		$.ajax( {
			url:  ajaxurl,
			type: 'POST',
			data: {
				action:         'pb_update_export_options',
				post_id:        id,
				chapter_export: chapter_export,
				type:           'pb_export',
				_ajax_nonce:    PB_OrganizeToken.exportNonce,
			},
			success: function () {
				updateWordCountForExport();
			},
		} );
	} );

	// Front-matter switches

	$( '.fm_privacy' ).change( function () {
		let post_status;

		let col = $( this )
			.parent()
			.prev( '.column-status' );
		let id = $( this ).attr( 'id' );
		id = id.split( '_' );
		id = id[id.length - 1];

		if ( $( this ).is( ':checked' ) ) {
			post_status = 'private';
		} else {
			post_status = 'publish';
		}

		$.ajax( {
			url:  ajaxurl,
			type: 'POST',
			data: {
				action:      'pb_update_privacy_options',
				post_id:     id,
				post_status: post_status,
				_ajax_nonce: PB_OrganizeToken.privacyNonce,
			},
			beforeSend: function () {
				if ( post_status === 'private' ) {
					col.text( PB_OrganizeToken.private );
				} else {
					col.text( PB_OrganizeToken.published );
				}
			},
			error: function ( xhr, ajaxOptions, thrownError ) {
				// TODO, catch error
			},
		} );
	} );

	$( '.fm_show_title_check' ).change( function () {
		let chapter_show_title;

		let id = $( this ).attr( 'id' );
		id = id.split( '_' );
		id = id[id.length - 1];

		if ( $( this ).is( ':checked' ) ) {
			chapter_show_title = 1;
		} else {
			chapter_show_title = 0;
		}

		$.ajax( {
			url:  ajaxurl,
			type: 'POST',
			data: {
				action:             'pb_update_show_title_options',
				post_id:            id,
				chapter_show_title: chapter_show_title,
				type:               'pb_show_title',
				_ajax_nonce:        PB_OrganizeToken.showTitleNonce,
			},
		} );
	} );

	$( '.fm_export_check' ).change( function () {
		let chapter_export;

		let id = $( this ).attr( 'id' );
		id = id.split( '_' );
		id = id[id.length - 1];

		if ( $( this ).is( ':checked' ) ) {
			chapter_export = 1;
		} else {
			chapter_export = 0;
		}

		$.ajax( {
			url:  ajaxurl,
			type: 'POST',
			data: {
				action:         'pb_update_export_options',
				post_id:        id,
				chapter_export: chapter_export,
				type:           'pb_export',
				_ajax_nonce:    PB_OrganizeToken.exportNonce,
			},
			success: function () {
				updateWordCountForExport();
			},
		} );
	} );

	// Back-matter switches

	$( '.bm_privacy' ).change( function () {
		let post_status;

		let col = $( this )
			.parent()
			.prev( '.column-status' );
		let id = $( this ).attr( 'id' );
		id = id.split( '_' );
		id = id[id.length - 1];

		if ( $( this ).is( ':checked' ) ) {
			post_status = 'private';
		} else {
			post_status = 'publish';
		}

		$.ajax( {
			url:  ajaxurl,
			type: 'POST',
			data: {
				action:      'pb_update_privacy_options',
				post_id:     id,
				post_status: post_status,
				_ajax_nonce: PB_OrganizeToken.privacyNonce,
			},
			beforeSend: function () {
				if ( post_status === 'private' ) {
					col.text( PB_OrganizeToken.private );
				} else {
					col.text( PB_OrganizeToken.published );
				}
			},
			error: function ( xhr, ajaxOptions, thrownError ) {
				// TODO, catch error
			},
		} );
	} );

	$( '.bm_show_title_check' ).change( function () {
		let chapter_show_title;

		let id = $( this ).attr( 'id' );
		id = id.split( '_' );
		id = id[id.length - 1];

		if ( $( this ).is( ':checked' ) ) {
			chapter_show_title = 1;
		} else {
			chapter_show_title = 0;
		}

		$.ajax( {
			url:  ajaxurl,
			type: 'POST',
			data: {
				action:             'pb_update_show_title_options',
				post_id:            id,
				chapter_show_title: chapter_show_title,
				type:               'pb_show_title',
				_ajax_nonce:        PB_OrganizeToken.showTitleNonce,
			},
		} );
	} );

	$( '.bm_export_check' ).change( function () {
		let chapter_export;

		let id = $( this ).attr( 'id' );
		id = id.split( '_' );
		id = id[id.length - 1];

		if ( $( this ).is( ':checked' ) ) {
			chapter_export = 1;
		} else {
			chapter_export = 0;
		}

		$.ajax( {
			url:  ajaxurl,
			type: 'POST',
			data: {
				action:         'pb_update_export_options',
				post_id:        id,
				chapter_export: chapter_export,
				type:           'pb_export',
				_ajax_nonce:    PB_OrganizeToken.exportNonce,
			},
			success: function () {
				updateWordCountForExport();
			},
		} );
	} );

	// Bulk action
	let pbOrganizeTdToggle = [];
	$( 'table thead th' ).click( function () {
		let tdIndex = $( this ).index() + 1;
		let tableIndex = $( this )
			.parents( 'table' )
			.index();
		let i = tableIndex + '_' + tdIndex;
		if ( pbOrganizeTdToggle[i] ) {
			$( this )
				.parents( 'table' )
				.find( 'tr td:nth-of-type(' + tdIndex + ')' )
				.find( 'input[type=checkbox]:checked' )
				.click();
			pbOrganizeTdToggle[i] = false;
		} else {
			$( this )
				.parents( 'table' )
				.find( 'tr td:nth-of-type(' + tdIndex + ')' )
				.find( 'input[type=checkbox]:not(:checked)' )
				.click();
			pbOrganizeTdToggle[i] = true;
		}
	} );

	// Warn of incomplete AJAX
	$( window ).on( 'beforeunload', function () {
		if ( $.active > 0 ) {
			return 'Changes you made may not be saved...';
		}
	} );

	// Update word count when needed.
	function updateWordCountForExport() {
		const data = {
			action:      'pb_update_word_count_for_export',
			_ajax_nonce: PB_OrganizeToken.wordCountNonce,
		};
		$.post( ajaxurl, data, function ( response ) {
			const current_count = parseInt( $( '#wc-selected-for-export' ).text(), 10 );
			let count_up = new CountUp(
				'wc-selected-for-export',
				current_count,
				response,
				0,
				2.5,
				{ separator: '' }
			);
			count_up.start();
		} );
	}
} );
