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
	$( '.web_visibility, .export_visibility' ).change( function () {
		let id = $( this ).attr( 'data-id' );
		let export_visibility = $( `#export_visibility_${id}` );
		let web_visibility = $( `#web_visibility_${id}` );
		let status_indicator = $( `#status_${id}` );

		let post_status;

		if ( web_visibility.is( ':checked' ) ) {
			if ( export_visibility.is( ':checked' ) ) {
				post_status = 'publish';
			} else {
				post_status = 'web-only';
			}
		} else {
			if ( export_visibility.is( ':checked' ) ) {
				post_status = 'private';
			} else {
				post_status = 'draft';
			}
		}

		$.ajax( {
			url:  ajaxurl,
			type: 'POST',
			data: {
				action:      'pb_update_visibility',
				post_id:     id,
				post_status: post_status,
				_ajax_nonce: PB_OrganizeToken.visibilityNonce,
			},
			beforeSend: function () {
				if (
					post_status === 'publish' ||
					post_status === 'web-only' ||
					post_status === 'private'
				) {
					status_indicator.text( PB_OrganizeToken.published );
				} else {
					status_indicator.text( PB_OrganizeToken.draft );
				}
			},
			success: function () {
				updateWordCountForExport();
			},
			error: function ( xhr, ajaxOptions, thrownError ) {
				// TODO
			},
		} );
	} );

	$( '.show_title' ).change( function ( event ) {
		let target = $( event.target )
			.parent()
			.parent()
			.attr( 'id' );
		target = target.split( '_' );
		target = {
			id:        target[target.length - 1],
			post_type: target[0],
		};

		let showtitle = '';

		if ( $( event.target ).is( ':checked' ) ) {
			showtitle = 'on';
		}

		let post = instantiateTypeModel( target );
		post.fetch( {
			success: function ( model, response, options ) {
				post.save( { meta: { pb_show_title: showtitle } }, { patch: true } );
			},
		} );
	} );

	function getRowFromAction( el ) {
		return $( el )
			.parent()
			.parent()
			.parent()
			.parent();
	}

	function instantiateTypeModel( item ) {
		let model;
		if ( item.post_type === 'chapter' ) {
			model = new wp.api.models.Chapters( { id: item.id } );
		} else if ( item.post_type === 'front-matter' ) {
			model = new wp.api.models.FrontMatter( { id: item.id } );
		} else if ( item.post_type === 'back-matter' ) {
			model = new wp.api.models.BackMatter( { id: item.id } );
		} else if ( item.post_type === 'part' ) {
			model = new wp.api.models.BackMatter( { id: item.id } );
		}
		return model;
	}

	function adjustMenuOrder( item, adjustment ) {
		let post = instantiateTypeModel( item );
		post.fetch( {
			success: function ( model, response, options ) {
				post.save(
					{ menu_order: model.attributes.menu_order + adjustment },
					{ patch: true }
				);
			},
		} );
	}

	function swap( firstItem, secondItem ) {
		adjustMenuOrder( firstItem, 1 );
		adjustMenuOrder( secondItem, -1 );
		$( `#${firstItem.post_type}_${firstItem.id}` )
			.next()
			.after( $( `#${firstItem.post_type}_${firstItem.id}` ) );
		// updateControls( firstItem, secondItem );
	}

	$( '.move-down' ).click( event => {
		event.preventDefault();
		let target = getRowFromAction( event.target ).attr( 'id' );
		target = target.split( '_' );
		target = {
			id:        target[target.length - 1],
			post_type: target[0],
		};
		let next = getRowFromAction( event.target )
			.next()
			.attr( 'id' );
		next = next.split( '_' );
		next = {
			id:        next[next.length - 1],
			post_type: next[0],
		};
		swap( target, next );
	} );

	$( '.move-up' ).click( event => {
		event.preventDefault();
		let target = getRowFromAction( event.target ).attr( 'id' );
		target = target.split( '_' );
		target = {
			id:        target[target.length - 1],
			post_type: target[0],
		};
		let prev = getRowFromAction( event.target )
			.prev()
			.attr( 'id' );
		prev = prev.split( '_' );
		prev = {
			id:        prev[prev.length - 1],
			post_type: prev[0],
		};
		swap( prev, target );
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
