// This script is loaded when a user is on the [ Text → Organize ] page

import CountUp from 'countup.js';

let $ = window.jQuery;

function getRowFromAction( el ) {
	return $( el )
		.parent()
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
		model = new wp.api.models.Parts( { id: item.id } );
	}
	return model;
}

function updateParent( chapter, part ) {
	chapter = chapter.attr( 'id' ).split( '_' );
	chapter = chapter[chapter.length - 1];
	part = part.attr( 'id' ).split( '_' );
	part = part[part.length - 1];

	let post = new wp.api.models.Chapters( { id: chapter } );
	post.fetch( {
		success: function ( model, response, options ) {
			post.save( { part: part }, { patch: true } );
		},
	} );
}

function getAdjacentContainer( table, relationship ) {
	if ( relationship === 'prev' ) {
		return $( table ).prev( '[id^=part]' );
	} else if ( relationship === 'next' ) {
		return $( table ).next( '[id^=part]' );
	}
}

function updateIndex( table ) {
	table
		.children( 'tbody' )
		.children( 'tr' )
		.each( ( i, el ) => {
			let item = $( el )
				.attr( 'id' )
				.split( '_' );
			item = {
				id:         item[item.length - 1],
				post_type:  item[0],
				menu_order: i,
			};
			let post = instantiateTypeModel( item );
			post.fetch( {
				success: function ( model, response, options ) {
					post.save( { menu_order: item.menu_order }, { patch: true } );
				},
			} );
			i++;
		} );
}

function updateControls( table ) {
	table
		.children( 'tbody' )
		.children( 'tr' )
		.each( ( i, el ) => {
			let controls = '';
			let up = '<button class="move-up">Move Up</button>';
			let down = '<button class="move-down">Move Down</button>';

			if ( $( el ).is( 'tr:first-of-type' ) ) {
				if ( table.prev( '[id^=part]' ).length ) {
					controls = ` | ${up} | ${down}`;
				} else {
					controls = ` | ${down}`;
				}
			} else if ( $( el ).is( 'tr:last-of-type' ) ) {
				if ( $( table ).next( '[id^=part]' ).length ) {
					controls = ` | ${up} | ${down}`;
				} else {
					controls = ` | ${up}`;
				}
			} else {
				controls = ` | ${up} | ${down}`;
			}

			$( el )
				.children( '.has-row-actions' )
				.children( '.row-title' )
				.children( '.row-actions' )
				.children( '.reorder' )
				.html( controls );
		} );
}

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
		start:       function ( index, el ) {},
		stop:        function ( index, el ) {
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
		start:       function ( index, el ) {},
		stop:        function ( index, el ) {
			Pressbooks.bmupdate( el.item );
		},
	},
	update: function ( el ) {
		// $.blockUI.defaults.applyPlatformOpacityRules = false;
		// $.blockUI( { message: jQuery( '#loader.chapter' ) } );
		updateParent( el, $( '#' + Pressbooks.newPart ) );
		updateIndex( $( '#' + Pressbooks.oldPart ) );
		updateIndex( $( '#' + Pressbooks.newPart ) );
		updateControls( $( '#' + Pressbooks.oldPart ) );
		updateControls( $( '#' + Pressbooks.newPart ) );
		// $.unblockUI();
	},

	fmupdate: function ( el ) {
		// $.blockUI.defaults.applyPlatformOpacityRules = false;
		// $.blockUI( { message: jQuery( '#loader.front-matter' ) } );
		updateIndex(
			$( el )
				.parent()
				.parent()
		);
		updateControls(
			$( el )
				.parent()
				.parent()
		);
		// $.unblockUI();
	},

	bmupdate: function ( el ) {
		// $.blockUI.defaults.applyPlatformOpacityRules = false;
		// $.blockUI( { message: jQuery( '#loader.back-matter' ) } );
		updateIndex(
			$( el )
				.parent()
				.parent()
		);
		updateControls(
			$( el )
				.parent()
				.parent()
		);
		// $.unblockUI();
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
					$( '.publicize-alert' )
						.removeClass( 'public' )
						.addClass( 'private' );
				} else if ( blog_public === 1 ) {
					$( 'h4.publicize-alert > span' ).text( PB_OrganizeToken.public );
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
	$( '.web_visibility, .export_visibility' ).change( event => {
		let row = $( event.target )
			.parent()
			.parent();
		let item = row.attr( 'id' ).split( '_' );
		item = {
			id:        item[item.length - 1],
			post_type: item[0],
		};
		let export_visibility = $( `#export_visibility_${item.id}` );
		let web_visibility = $( `#web_visibility_${item.id}` );

		let postStatus;

		if ( web_visibility.is( ':checked' ) ) {
			if ( export_visibility.is( ':checked' ) ) {
				postStatus = 'publish';
			} else {
				postStatus = 'web-only';
			}
		} else {
			if ( export_visibility.is( ':checked' ) ) {
				postStatus = 'private';
			} else {
				postStatus = 'draft';
			}
		}

		let post = instantiateTypeModel( item );
		post.fetch( {
			success: function ( model, response, options ) {
				post.save(
					{ status: postStatus },
					{
						patch:   true,
						success: function () {
							updateWordCountForExport();
						},
					}
				);
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

	$( document ).on( 'click', '.move-up', event => {
		jQuery.blockUI.defaults.applyPlatformOpacityRules = false;
		jQuery.blockUI( { message: jQuery( '#loader.chapter' ) } );
		let row = getRowFromAction( event.target );
		let table = $( row )
			.parent()
			.parent();
		if (
			$( row ).is( 'tr:first-of-type' ) &&
			table.is( '[id^=part]' ) &&
			table.prev( '[id^=part]' ).length
		) {
			let targetTable = getAdjacentContainer( table, 'prev' );
			targetTable.append( row );
			updateParent( row, targetTable );
			updateIndex( table );
			updateIndex( targetTable );
			updateControls( table );
			updateControls( targetTable );
		} else {
			row.prev().before( row );
			updateIndex( table );
			updateControls( table );
		}
		jQuery.unblockUI();
	} );

	$( document ).on( 'click', '.move-down', event => {
		jQuery.blockUI.defaults.applyPlatformOpacityRules = false;
		jQuery.blockUI( { message: jQuery( '#loader.chapter' ) } );
		let row = getRowFromAction( event.target );
		let table = $( row )
			.parent()
			.parent();
		if (
			$( row ).is( 'tr:last-of-type' ) &&
			table.is( '[id^=part]' ) &&
			table.next( '[id^=part]' ).length
		) {
			let targetTable = getAdjacentContainer( table, 'next' );
			targetTable.prepend( row );
			updateParent( row, targetTable );
			updateIndex( table );
			updateIndex( targetTable );
			updateControls( table );
			updateControls( targetTable );
		} else {
			row.next().after( row );
			updateIndex( table );
			updateControls( table );
		}
		jQuery.unblockUI();
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
