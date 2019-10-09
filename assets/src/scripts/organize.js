/* global PB_OrganizeToken */

import { CountUp } from 'countup.js';

let $ = window.jQuery;

let pb = {
	organize: {
		bulkToggle: [],
		oldParent: null,
		newParent: null,
		oldOrder: null,
		newOrder: null,
		sortableOptions: {
			revert: true,
			helper: 'clone',
			zIndex: 2700,
			distance: 3,
			opacity: 0.6,
			placeholder: 'ui-state-highlight',
			dropOnEmpty: true,
			cursor: 'crosshair',
			items: 'tbody > tr',
			start: ( event, ui ) => {
				pb.organize.oldParent = $( ui.item )
					.parents( 'table' )
					.attr( 'id' );
			},
			stop: ( event, ui ) => {
				pb.organize.newParent = $( ui.item )
					.parents( 'table' )
					.attr( 'id' );
				reorder( $( ui.item ) );
			},
		},
	},
};

/**
 * Clear a modal using jQuery.unBlockUI()
 *
 * @param {string | object} item
 */
function showModal( item ) {
	$.blockUI.defaults.applyPlatformOpacityRules = false;
	let alert = $( '[role="alert"]' );
	let alertMessage;
	if ( item === 'book' ) {
		alertMessage = PB_OrganizeToken.updating.book;
	} else {
		let postType = item.post_type.replace( '-', '' );
		alertMessage = PB_OrganizeToken.updating[postType];
	}
	alert.children( 'p' ).text( alertMessage );
	alert.addClass( 'loading-content' ).removeClass( 'visually-hidden' );
	$.blockUI( {
		message: $( alert ),
		baseZ: 100000,
	} );
}

/**
 * Clear a modal using jQuery.unBlockUI()
 *
 * @param {string | object} item
 * @param {string} status
 */
function removeModal( item, status ) {
	let alert = $( '[role="alert"]' );
	let alertMessage;

	if ( item === 'book' ) {
		alertMessage = PB_OrganizeToken[status].book;
	} else {
		let postType = item.post_type.replace( '-', '' );
		alertMessage = PB_OrganizeToken[status][postType];
	}

	$.unblockUI( {
		onUnblock: () => {
			alert.removeClass( 'loading-content' ).addClass( 'visually-hidden' );
			alert.children( 'p' ).text( alertMessage );
		},
	} );
}

/**
 * Update word count for exportable content.
 */
function updateWordCountForExport() {
	const data = {
		action: 'pb_update_word_count_for_export',
		_ajax_nonce: PB_OrganizeToken.wordCountNonce,
	};
	$.post( ajaxurl, data, function ( response ) {
		const current_count = parseInt( $( '#wc-selected-for-export' ).text(), 10 );
		const count_up_options = {
			startVal: current_count,
			separator: '',
		};
		let count_up = new CountUp(
			'wc-selected-for-export',
			response,
			count_up_options
		);
		count_up.start();
	} );
}

/**
 * Get the table before or after the current table.
 *
 * @param {jQuery object} table
 * @param {string} relationship
 * @returns {jQuery object}
 */
function getAdjacentContainer( table, relationship ) {
	if ( relationship === 'prev' ) {
		return $( table ).prev( '[id^=part]' );
	} else if ( relationship === 'next' ) {
		return $( table ).next( '[id^=part]' );
	}
}

/**
 * Get data for a table row.
 *
 * @param {jQuery object} row
 * @returns {object}
 */
function getRowData( row ) {
	row = $( row )
		.attr( 'id' )
		.split( '_' );
	const rowData = {
		id: row[row.length - 1],
		post_type: row[0],
	};
	return rowData;
}

/**
 * Get an array object of IDs in a table.
 *
 * @param {jQuery object} table
 * @returns {array} ids
 */
function getIdsInTable( table ) {
	let ids = [];
	table
		.children( 'tbody' )
		.children( 'tr' )
		.each( ( i, el ) => {
			let row = getRowData( $( el ) );
			ids.push( row.id );
		} );

	return ids;
}

/**
 * Adjust the reorder controls throughout a table as part of a reorder operation.
 *
 * @param {jQuery object} table
 */
function updateControls( table ) {
	table
		.children( 'tbody' )
		.children( 'tr' )
		.each( ( i, el ) => {
			let controls = '';
			let up = '<button class="move-up">Move Up</button>';
			let down = '<button class="move-down">Move Down</button>';

			if ( $( el ).is( 'tr:only-of-type' ) ) {
				if (
					table.is( '[id^=part]' ) &&
					table.prev( '[id^=part]' ).length &&
					table.next( '[id^=part]' ).length
				) {
					controls = ` | ${up} | ${down}`;
				} else if ( table.is( '[id^=part]' ) && table.next( '[id^=part]' ).length ) {
					controls = ` | ${down}`;
				} else if ( table.is( '[id^=part]' ) && table.prev( '[id^=part]' ).length ) {
					controls = ` | ${up}`;
				}
			} else if ( $( el ).is( 'tr:first-of-type' ) ) {
				if ( table.is( '[id^=part]' ) && table.prev( '[id^=part]' ).length ) {
					controls = ` | ${up} | ${down}`;
				} else {
					controls = ` | ${down}`;
				}
			} else if ( $( el ).is( 'tr:last-of-type' ) ) {
				if ( table.is( '[id^=part]' ) && table.next( '[id^=part]' ).length ) {
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

/**
 * Reorder the contents of a table, optionally moving the target row to a new table.
 *
 * @param {jQuery object} row
 * @param {jQuery object} source
 * @param {jQuery object} destination
 */
function reorder( row ) {
	let item = getRowData( row );

	$.ajax( {
		url: ajaxurl,
		type: 'POST',
		data: {
			action: 'pb_reorder',
			id: item.id,
			old_order: $( `#${pb.organize.oldParent}` ).sortable( 'serialize' ),
			new_order: $( `#${pb.organize.newParent}` ).sortable( 'serialize' ),
			old_parent: pb.organize.oldParent.replace( /^part_([0-9]+)$/i, '$1' ),
			new_parent: pb.organize.newParent.replace( /^part_([0-9]+)$/i, '$1' ),
			_ajax_nonce: PB_OrganizeToken.reorderNonce,
		},
		beforeSend: () => {
			showModal( item );
			if ( pb.organize.oldParent !== pb.organize.newParent ) {
				updateControls( $( `#${pb.organize.oldParent}` ) );
			}
			updateControls( $( `#${pb.organize.newParent}` ) );
		},
		success: () => {
			removeModal( item, 'success' );
		},
		error: () => {
			removeModal( item, 'failure' );
		},
	} );
}

/**
 * Update post status for individual or multiple posts.
 *
 * @param {string} post_id
 */
function updateVisibility( ids, postType, output, visibility ) {
	let data = {
		action: 'pb_update_post_visibility',
		post_ids: ids,
		_ajax_nonce: PB_OrganizeToken.postVisibilityNonce,
	};

	$.ajax( {
		url: ajaxurl,
		type: 'POST',
		data: Object.assign( data, { [output]: visibility } ),
		beforeSend: () => {
			showModal( { post_type: postType } );
		},
		success: response => {
			removeModal( { post_type: postType }, 'success' );
			updateWordCountForExport();
		},
		error: () => {
			removeModal( { post_type: postType }, 'failure' );
		},
	} );
}

/**
 * Update title visibility for individual or multiple posts.
 *
 * @param {string} ids Comma separated post IDs.
 * @param {string} postType
 * @param {bool} showTitle
 */
function updateTitleVisibility( ids, postType, showTitle ) {
	$.ajax( {
		url: ajaxurl,
		type: 'POST',
		data: {
			action: 'pb_update_post_title_visibility',
			post_ids: ids,
			show_title: showTitle,
			_ajax_nonce: PB_OrganizeToken.showTitleNonce,
		},
		beforeSend: () => {
			showModal( { post_type: postType } );
		},
		success: response => {
			removeModal( { post_type: postType }, 'success' );
		},
		error: () => {
			removeModal( { post_type: postType }, 'failure' );
		},
	} );
}

$( document ).ready( () => {
	// Initialize jQuery.sortable()
	$( '.allow-bulk-operations #front-matter' )
		.sortable( pb.organize.sortableOptions )
		.disableSelection();
	$( '.allow-bulk-operations table#back-matter' )
		.sortable( pb.organize.sortableOptions )
		.disableSelection();
	$( '.allow-bulk-operations table.chapters' )
		.sortable(
			Object.assign( pb.organize.sortableOptions, { connectWith: '.chapters' } )
		)
		.disableSelection();

	// Handle Global Privacy form changes.
	$( 'input[name=blog_public]' ).change( event => {
		const publicizeAlert = $( '.publicize-alert' );
		const publicizeAlertText = $( '.publicize-alert > span' );
		let blogPublic;
		if ( parseInt( event.currentTarget.value, 10 ) === 1 ) {
			blogPublic = 1;
		} else {
			blogPublic = 0;
		}

		$.ajax( {
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'pb_update_global_privacy_options',
				blog_public: blogPublic,
				_ajax_nonce: PB_OrganizeToken.privacyNonce,
			},
			beforeSend: () => {
				showModal( 'book' );
			},
			success: () => {
				if ( blogPublic === 0 ) {
					publicizeAlert.removeClass( 'public' ).addClass( 'private' );
					publicizeAlertText.text( PB_OrganizeToken.bookPrivate );
				} else if ( blogPublic === 1 ) {
					publicizeAlert.removeClass( 'private' ).addClass( 'public' );
					publicizeAlertText.text( PB_OrganizeToken.bookPublic );
				}
				removeModal( 'book', 'success' );
			},
			error: () => {
				removeModal( 'book', 'failure' );
			},
		} );
	} );

	// Handle visibility changes.
	$( '.web_visibility, .export_visibility' ).change( function () {
		let row = $( this ).parents( 'tr' );
		let item = getRowData( row );
		let output;
		let visibility = 0;

		if ( $( this ).is( ':checked' ) ) {
			visibility = 1;
		}

		if ( $( this ).is( '[id^="export_visibility"]' ) ) {
			output = 'export';
		} else if ( $( this ).is( '[id^="web_visibility"]' ) ) {
			output = 'web';
		}

		updateVisibility( item.id, item.post_type, output, visibility );
	} );

	// Handle title visibility changes.
	$( '.show_title' ).change( event => {
		let row = $( event.target ).parents( 'tr' );
		let item = getRowData( row );

		let showTitle = '';

		if ( $( event.currentTarget ).is( ':checked' ) ) {
			showTitle = 'on';
		}

		updateTitleVisibility( item.id, item.post_type, showTitle );
	} );

	// Handle "move up".
	$( document ).on( 'click', '.move-up', event => {
		let row = $( event.target ).parents( 'tr' );
		let table = $( event.target ).parents( 'table' );
		pb.organize.oldParent = table.attr( 'id' );
		if (
			row.is( 'tr:first-of-type' ) &&
			table.is( '[id^=part]' ) &&
			table.prev( '[id^=part]' ).length
		) {
			let targetTable = getAdjacentContainer( table, 'prev' );
			pb.organize.newParent = targetTable.attr( 'id' );
			targetTable.append( row );
			reorder( row );
		} else {
			pb.organize.newParent = table.attr( 'id' );
			row.prev().before( row );
			reorder( row );
		}
	} );

	// Handle "move down".
	$( document ).on( 'click', '.move-down', event => {
		let row = $( event.target ).parents( 'tr' );
		let table = $( event.target ).parents( 'table' );
		pb.organize.oldParent = table.attr( 'id' );
		if (
			row.is( 'tr:last-of-type' ) &&
			table.is( '[id^=part]' ) &&
			table.next( '[id^=part]' ).length
		) {
			let targetTable = getAdjacentContainer( table, 'next' );
			pb.organize.newParent = targetTable.attr( 'id' );
			targetTable.prepend( row );
			reorder( row );
		} else {
			pb.organize.newParent = table.attr( 'id' );
			row.next().after( row );
			reorder( row );
		}
	} );

	$( '.allow-bulk-operations table thead th span[id$="show_title"]' ).on(
		'click',
		event => {
			let id = $( event.target ).attr( 'id' );
			id = id.replace( '-', '' );
			let table = $( event.target ).parents( 'table' );
			let postType = table.attr( 'id' ).split( '_' )[0];
			if ( postType === 'part' ) {
				postType = 'chapter';
			}
			let ids = getIdsInTable( table );
			if ( pb.organize.bulkToggle[id] ) {
				table
					.find( 'tr td.column-showtitle input[type="checkbox"]' )
					.prop( 'checked', false );
				pb.organize.bulkToggle[id] = false;
				updateTitleVisibility( ids.join(), postType, '' );
			} else {
				table
					.find( 'tr td.column-showtitle input[type="checkbox"]' )
					.prop( 'checked', true );
				pb.organize.bulkToggle[id] = true;
				updateTitleVisibility( ids.join(), postType, 'on' );
			}
		}
	);

	$( '.allow-bulk-operations table thead th span[id$="visibility"]' ).on(
		'click',
		event => {
			let id = $( event.target ).attr( 'id' );
			id = id.replace( '-', '' );
			let format = id.split( '_' );
			format = format[format.length - 2];
			let table = $( event.target ).parents( 'table' );
			let postType = table.attr( 'id' ).split( '_' )[0];
			if ( postType === 'part' ) {
				postType = 'chapter';
			}
			let ids = getIdsInTable( table );
			if ( pb.organize.bulkToggle[id] ) {
				table
					.find( `tr td.column-${format} input[type=checkbox]` )
					.prop( 'checked', false );
				pb.organize.bulkToggle[id] = false;
				updateVisibility( ids.join(), postType, format, 0 );
			} else {
				table
					.find( `tr td.column-${format} input[type="checkbox"]` )
					.prop( 'checked', true );
				pb.organize.bulkToggle[id] = true;
				updateVisibility( ids.join(), postType, format, 1 );
			}
		}
	);

	// Warn of incomplete AJAX
	$( window ).on( 'beforeunload', function () {
		if ( $.active > 0 ) {
			return 'Changes you made may not be saved...';
		}
	} );
} );
