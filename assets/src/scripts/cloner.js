/* global PB_ClonerToken */

import displayNotice from './utils/displayNotice';

jQuery( function ( $ ) {
	const clonerForm = $( '#pb-cloner-form' );
	const button = $( '#pb-cloner-button' );
	const bar = $( '#pb-sse-progressbar' );
	const info = $( '#pb-sse-info' );
	const status = $( '#pb-sse-status' );
	const result = $( '#pb-clone-result' );

	const POLL_INTERVAL = 2500;
	const MAX_POLL_FAILURES = 5;

	let pollTimer = null;
	let pollFailures = 0;

	/**
	 *
	 * @param data
	 */
	function showProgress( data ) {
		bar.show();
		if ( data.status === 'pending' ) {
			bar.removeAttr( 'value' );
			info.text( PB_ClonerToken.text.queued );
		} else {
			bar.val( data.progress_percentage );
			status.text( `${ data.progress_percentage }%` );
			info.text( data.progress_message || '' );
		}
	}

	/**
	 *
	 */
	function stopPolling() {
		if ( pollTimer ) {
			clearInterval( pollTimer );
			pollTimer = null;
		}
	}

	/**
	 *
	 * @param data
	 */
	function renderCompleted( data ) {
		stopPolling();
		bar.val( 100 );
		status.text( '100%' );
		info.text( '' );

		const summary = data.cloned_items || {};
		const counts = summary.counts || {};
		const labels = PB_ClonerToken.text.labels;

		const list = $( '<ul></ul>' );
		Object.keys( labels ).forEach( key => {
			list.append(
				$( '<li></li>' ).text( `${ labels[ key ] }: ${ counts[ key ] || 0 }` )
			);
		} );

		result.empty()
			.append( $( '<h2></h2>' ).text( PB_ClonerToken.text.completed ) )
			.append( $( '<h3></h3>' ).text( PB_ClonerToken.text.summaryHeading ) )
			.append( list )
			.append(
				$( '<p></p>' ).text(
					summary.theme_applied
						? PB_ClonerToken.text.themeApplied
						: PB_ClonerToken.text.themeNotApplied
				)
			);

		if ( summary.target_book_url ) {
			result.append(
				$( '<a class="button button-hero button-primary"></a>' )
					.attr( 'href', `${ summary.target_book_url.replace( /\/$/, '' ) }/wp-admin/` )
					.text( PB_ClonerToken.text.goToBook )
			);
		}

		result.removeAttr( 'hidden' ).trigger( 'focus' );
	}

	/**
	 *
	 * @param data
	 */
	function renderFailed( data ) {
		stopPolling();
		bar.val( 0 ).hide();
		info.text( '' );
		displayNotice( 'error', data.progress_message || PB_ClonerToken.text.failed, true );
		button.attr( 'disabled', false ).show();
	}

	/**
	 *
	 * @param response
	 */
	function handleStatusResponse( response ) {
		pollFailures = 0;
		if ( ! response.success ) {
			stopPolling();
			return;
		}
		const data = response.data;
		if ( data.status === 'completed' ) {
			renderCompleted( data );
		} else if ( data.status === 'failed' ) {
			renderFailed( data );
		} else {
			showProgress( data );
		}
	}

	/**
	 *
	 * @param jobId
	 */
	function watch( jobId ) {
		button.attr( 'disabled', true ).hide();
		result.attr( 'hidden', true ).empty();
		stopPolling();
		pollFailures = 0;
		pollTimer = setInterval( () => {
			$.getJSON( PB_ClonerToken.ajaxUrl, {
				action: 'pb_clone_job_status',
				job_id: jobId || '',
				_wpnonce: PB_ClonerToken.nonce,
			} )
				.done( handleStatusResponse )
				.fail( () => {
					pollFailures++;
					if ( pollFailures >= MAX_POLL_FAILURES ) {
						stopPolling();
						info.html( PB_ClonerToken.text.connectionLost + ' ' + PB_ClonerToken.reloadSnippet );
					}
				} );
		}, POLL_INTERVAL );
	}

	// Resume watching an active job when returning to the page.
	if ( clonerForm.length ) {
		$.getJSON( PB_ClonerToken.ajaxUrl, {
			action: 'pb_clone_job_status',
			_wpnonce: PB_ClonerToken.nonce,
		} ).done( response => {
			if ( response.success && response.data ) {
				showProgress( response.data );
				watch( response.data.job_id );
			}
		} );
	}

	clonerForm.on( 'submit', function ( e ) {
		e.preventDefault();
		$( '.notice' ).remove();
		button.attr( 'disabled', true );

		$.post( PB_ClonerToken.ajaxUrl, {
			action: 'pb_queue_clone',
			_wpnonce: PB_ClonerToken.nonce,
			source_book_url: $( '#source-book-url' ).val(),
			target_book_url: $( '#target-book-url' ).val(),
		} )
			.done( response => {
				bar.val( 0 ).show();
				watch( response.data.job_id );
			} )
			.fail( jqXHR => {
				const message =
					jqXHR.responseJSON && jqXHR.responseJSON.data && jqXHR.responseJSON.data.message
						? jqXHR.responseJSON.data.message
						: PB_ClonerToken.text.failed;
				displayNotice( 'error', message, true );
				button.attr( 'disabled', false );
			} );
	} );
} );
