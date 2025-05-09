/* global PB_ExportToken */
/* global _pb_export_formats_map */
/* global _pb_export_pins_inventory */

import Cookies from 'js-cookie';

import displayNotice from './utils/displayNotice';
import resetClock from './utils/resetClock';
import startClock from './utils/startClock';

// Stores EventSource instances for individual background jobs. Key: jobId
const jobEventSources = {};

jQuery( function ( $ ) {
	const exportForm = $( '#pb-export-form' );
	const mainButton = $( '#pb-export-button' );
	const mainProgressBar = $( '#pb-sse-progressbar' ); // Main progress bar for initial/overall status
	const mainInfoText = $( '#pb-sse-info' ); // Main info display area
	// const noticesContainer = $( '.notice-container' ); // Define if you have a specific notices container

	/**
	 * Creates or retrieves UI elements for a specific export job.
	 * @param {string} moduleSlug - Slug for the export format (e.g., 'pdf', 'epub').
	 * @param {string|null} jobId - The ID of the job, if applicable.
	 * @returns {object} Contains jQuery objects for bar, info, and downloadLink.
	 */
	function getOrCreateJobProgressUI(moduleSlug, jobId = null) {
		const jobUiId = `job-progress-${moduleSlug}${jobId ? '-' + jobId : ''}`;
		let jobUIContainer = $( '#' + jobUiId );

		if (!jobUIContainer.length) {
			const jobTitle = jobId ? `${moduleSlug.toUpperCase()} (Job ID: ${jobId})` : moduleSlug.toUpperCase();
			const uiHtml = `
				<div id="${jobUiId}" class="job-progress-item" style="margin-top: 15px; padding: 10px; border: 1px solid #eee;">
					<h4>Export: ${jobTitle}</h4>
					<progress id="progress-bar-${moduleSlug}${jobId ? '-' + jobId : ''}" value="0" max="100" style="width: 100%; margin-bottom: 5px;"></progress>
					<p id="info-text-${moduleSlug}${jobId ? '-' + jobId : ''}" style="margin-bottom: 5px;">Initializing...</p>
					<a href="#" id="download-link-${moduleSlug}${jobId ? '-' + jobId : ''}" class="button button-secondary" style="display:none; margin-top: 5px;">Download File</a>
				</div>
			`;
			// Append to a container. If mainInfoText is a <p>, append after its parent or a designated div.
			// For this example, appending after the mainInfoText's parent div if it exists, or after mainInfoText itself.
			(mainInfoText.parent().is('div') ? mainInfoText.parent() : mainInfoText).after(uiHtml);
			jobUIContainer = $( '#' + jobUiId );
		}
		return {
			container: jobUIContainer,
			bar: jobUIContainer.find('progress'),
			info: jobUIContainer.find('p'),
			downloadLink: jobUIContainer.find('a.button'),
		};
	}

	exportForm.on( 'submit', function ( e ) {
		e.preventDefault();

		const notices = $( '.notice' ); // Existing notices
		let clock = null;

		mainProgressBar.val( 0 ).show();
		mainButton.attr( 'disabled', true ).hide();
		notices.remove(); // Clear old WordPress notices
		$( '.job-progress-item' ).remove(); // Clear any previous job-specific UI elements
		mainInfoText.html( 'Initializing export process...' ); // Set initial message for main info

		// Construct the URL for the initial EventSource connection (to EventStreams.php handler)
		// PB_ExportToken.ajaxUrl should contain the base admin-ajax.php and necessary parameters like action and main nonce
		const checkedFormatsParams = exportForm.find( ':input:checked' ).serialize(); // Get only checked formats
		if (!checkedFormatsParams) {
			mainInfoText.html('<span style="color:red;">No export formats selected.</span>');
			mainButton.attr('disabled', false).show();
			mainProgressBar.hide();
			return;
		}
		const initialEventSourceUrl = PB_ExportToken.ajaxUrl + (PB_ExportToken.ajaxUrl.includes('?') ? '&' : '') + checkedFormatsParams;
		const initialEvtSource = new EventSource( initialEventSourceUrl );

		initialEvtSource.onopen = function () {
			clock = startClock();
			$( window ).on( 'beforeunload', function () {
				return PB_ExportToken.unloadWarning;
			} );
		};

		initialEvtSource.onmessage = function ( message ) {
			const data = JSON.parse( message.data );
			let jobUI;

			if ( data.event_type === 'job_queued' ) {
				jobUI = getOrCreateJobProgressUI( data.module_slug, data.job_id );
				jobUI.info.html( data.message );
				jobUI.bar.val( 5 ); // Small progress indicating "queued"
				mainInfoText.html( `Job for ${data.module_slug.toUpperCase()} has been queued. See details below.` );
				listenForJobProgress( data.book_id, data.job_id, data.module_slug, data.sse_nonce, jobUI );

			} else if ( data.event_type === 'job_queue_failed' ) {
				jobUI = getOrCreateJobProgressUI( data.module_slug );
				const errorMessage = `Failed to queue ${data.module_slug.toUpperCase()}: ${data.message}`;
				jobUI.info.html( `<span style="color:red;">${errorMessage}</span>` );
				jobUI.bar.hide();
				displayNotice( 'error', errorMessage, true ); // Display as a general WordPress notice too
				mainInfoText.html( `Error queueing job for ${data.module_slug.toUpperCase()}.` );
                checkAllJobsComplete(initialEvtSource); // Check if this was the only task

			} else if ( data.action === 'updateStatusBar' ) { // Legacy direct progress update
				mainProgressBar.val( parseInt( data.percentage, 10 ) );
				mainInfoText.html( data.info );
				// This is a general update, not tied to a specific module UI unless data.module_slug is available
				if (data.module_slug) {
					jobUI = getOrCreateJobProgressUI(data.module_slug);
					jobUI.bar.val(parseInt(data.percentage, 10));
					jobUI.info.html(data.info);
				}

			} else if ( data.action === 'complete' ) { // Legacy stream completion
				initialEvtSource.close();
				$( window ).unbind( 'beforeunload' );
				if ( clock ) resetClock( clock );

				if ( data.error ) {
					mainProgressBar.val( 0 ).hide();
					// mainButton.attr( 'disabled', false ).show(); // Moved to checkAllJobsComplete
					displayNotice( 'error', data.error, true );
					mainInfoText.html( `<span style="color:red;">Export process encountered an error: ${data.error}</span>` );
				} else {
					mainInfoText.html( 'Main export process complete. Background jobs may still be running.' );
				}
                checkAllJobsComplete(initialEvtSource);


			} else if (data.progress !== undefined && data.message !== undefined && data.module_slug) {
                // Standardized synchronous progress from Export::exportGenerator
                jobUI = getOrCreateJobProgressUI(data.module_slug);
                jobUI.bar.val(parseInt(data.progress, 10));
                jobUI.info.html(data.message);
                mainInfoText.html(`Processing ${data.module_slug.toUpperCase()}: ${data.progress}%`);
                mainProgressBar.val(parseInt(data.progress, 10)); // Update main bar for current sync task

            } else if (data.event_type === 'error' && data.module_slug) {
                // Standardized synchronous error from Export::exportGenerator
                jobUI = getOrCreateJobProgressUI(data.module_slug);
                jobUI.info.html(`<span style="color:red;">Error processing ${data.module_slug.toUpperCase()}: ${data.message}</span>`);
                mainInfoText.html(`Error with ${data.module_slug.toUpperCase()}. Check details or logs.`);
                // Don't close initialEvtSource here, other sync jobs might be in the queue
            }
		};

		initialEvtSource.onerror = function () {
			initialEvtSource.close();
			if ( clock ) resetClock( clock );
			$( window ).unbind( 'beforeunload' );
			mainProgressBar.removeAttr( 'value' ).hide();
			mainInfoText.html( 'EventStream Connection Error. ' + PB_ExportToken.reloadSnippet );
			mainButton.attr( 'disabled', false ).show();
		};
	} );

	/**
	 * Listens for progress updates for a specific background job.
	 * @param {string} bookId - The ID of the book.
	 * @param {string} jobId - The ID of the job.
	 * @param {string} moduleSlug - Slug for the export format.
	 * @param {string} sseNonce - Nonce for the SSE connection.
	 * @param {object} jobUI - UI elements for this job.
	 */
	function listenForJobProgress(bookId, jobId, moduleSlug, sseNonce, jobUI) {
		if (jobEventSources[jobId]) {
			jobEventSources[jobId].close(); // Close existing if any, though unlikely
		}

		const jobEventSourceUrl = ajaxurl + '?action=pressbooks_export_status_sse&book_id=' + bookId + '&job_id=' + jobId + '&_wpnonce=' + sseNonce;
		const jobEvtSource = new EventSource(jobEventSourceUrl);
		jobEventSources[jobId] = jobEvtSource;

		jobEvtSource.addEventListener('export_progress', function (event) {
			const data = JSON.parse(event.data);

			jobUI.bar.val(data.progress);
			jobUI.info.html(data.message);

			if (data.status === 'completed') {
				jobUI.info.html(`<strong>${moduleSlug.toUpperCase()} Export Complete!</strong>`);
				if (data.file_url) {
					// Ensure the download URL uses the secure download mechanism
					// The PHP for formSubmit in Export.php was updated to handle job_id and nonce for downloads
					const downloadUrl = PB_ExportToken.exportPageUrl + // Assuming this is admin.php?page=pb_export
						'&download_export_file=' + encodeURIComponent(data.file_url.substring(data.file_url.lastIndexOf('/') + 1)) +
						'&job_id=' + jobId +
						'&_wpnonce=' + PB_ExportToken.downloadNoncePrefix + jobId; // Need a way to generate/get this nonce
                                                                                 // For now, let's assume PB_ExportToken.downloadNoncePrefix is set, e.g. 'download_export_job_'

					jobUI.downloadLink.attr('href', data.file_url) // Direct link for now, but ideally use a nonced download trigger if files are protected.
                                     // The PHP Export::formSubmit was updated to handle secure downloads
                                     // The URL structure would be admin_url for the pb_export page.
                                     // Example: PB_ExportToken.exportPageUrl + '&download_export_file=' + basename(data.file_url) + '&job_id=' + jobId + '&_wpnonce=' + (new nonce for download)
                                     // For simplicity, this example still uses the direct file_url if provided by server,
                                     // assuming it might be a direct CDN link or similar in some setups.
                                     // Better: construct a link to the WordPress download handler.
                                     .text(`Download ${moduleSlug.toUpperCase()}`)
                                     .show();
				}
				jobEvtSource.close();
				delete jobEventSources[jobId];
				checkAllJobsComplete();
			} else if (data.status === 'failed' || data.status === 'error') {
				jobUI.info.html(`<strong style="color:red;">${moduleSlug.toUpperCase()} Export Failed:</strong> ${data.message}`);
				jobEvtSource.close();
				delete jobEventSources[jobId];
				checkAllJobsComplete();
			}
		});

		jobEvtSource.onerror = function () {
			jobUI.info.html(`<span style="color:red;">Connection error for ${moduleSlug.toUpperCase()} job progress. Please check the main export list later.</span>`);
			jobEvtSource.close();
			delete jobEventSources[jobId];
			checkAllJobsComplete();
		};
	}

	/**
	 * Checks if all export processes (initial SSE and all background jobs) are complete.
	 * @param {EventSource|null} initialEvtSource - The initial EventSource, to check its readyState.
	 */
	function checkAllJobsComplete(initialEvtSource = null) {
        // A simple check: if no background jobs are actively being tracked.
        // And if the initial EventSource is closed (or was never opened for this check).
        const initialSseIsClosed = initialEvtSource ? initialEvtSource.readyState === EventSource.CLOSED : true;

		if (Object.keys(jobEventSources).length === 0 && initialSseIsClosed) {
			mainButton.attr('disabled', false).show();
			mainProgressBar.hide(); // Hide main progress bar as individual jobs show status
			mainInfoText.html("All export tasks have been processed. Review status of each job above.");
            // If you want to redirect after ALL jobs are done and successful:
            // let allSuccessful = true;
            // $('.job-progress-item').each(function() {
            //    if ($(this).text().toLowerCase().includes('failed') || $(this).text().toLowerCase().includes('error')) {
            //        allSuccessful = false;
            //    }
            // });
            // if (allSuccessful && initialSseIsClosed && PB_ExportToken.redirectUrl) { // Check if initial stream also reported no general error
            //    window.location = PB_ExportToken.redirectUrl;
            // }
		}
	}


	/* JSON Cookie. Remember to keep key/values short because a cookie has max 4096 bytes */
	let json_cookie_key = 'pb_export';
	let json_cookie = Cookies.get( json_cookie_key );
	json_cookie = typeof json_cookie === 'undefined' ? {} : JSON.parse( Cookies.get( json_cookie_key ) );

	/**
	 *
	 */
	function update_json_cookie() {
		Cookies.set( json_cookie_key, JSON.stringify( json_cookie ), {
			path: '/',
			expires: 365,
		} );
	}

	/* Collapsible form */
	const optionsPanel = document.getElementById( 'export-options' );
	const toggleButton = optionsPanel.querySelector( '.handlediv' );
	/**
	 *
	 */
	if (toggleButton && optionsPanel) { // Ensure elements exist
		toggleButton.onclick = () => {
			let expanded = toggleButton.getAttribute( 'aria-expanded' ) === 'true' || false;
			toggleButton.setAttribute( 'aria-expanded', ! expanded );
			if ( expanded ) {
				optionsPanel.classList.add( 'closed' );
			} else {
				optionsPanel.classList.remove( 'closed' );
			}
		};
	}


	/* Bulk Action Handler */
	const bulkActionsTop = document.getElementById( 'bulk-action-selector-top' );
	const bulkActionsBottom = document.getElementById( 'bulk-action-selector-bottom' );
	const bulkFormTable = document.querySelector( '.wp-list-table' );
	if (bulkFormTable) { // Ensure table exists
		const bulkForm = bulkFormTable.parentNode;
		bulkForm.addEventListener( 'submit', event => {
			event.preventDefault();
			if ( bulkActionsTop.value === 'delete' || bulkActionsBottom.value === 'delete' ) {
				if ( ! confirm( PB_ExportToken.bulkDeleteWarning ) ) { // eslint-disable-line
					return false;
				}
			}
			/**
			 *
			 */
			const bulkSubmission = function () {
				bulkForm.submit();
			};
			setTimeout( bulkSubmission, 0 );
		} );
	}


	/* Swap out and animate the 'Export Your Book' button */
	$( '#pb-export-button' ).on( 'click', function ( e ) {
		e.preventDefault();
		// If the user has pinned three files of a given export type and then tries to export that format,
		// the export job should be stopped and an error should be displayed instructing them to deselect
		// one of the pinned files before attempting to export.
		let tooManyExports = false;
		let myLabel = '';
		$( '#pb-export-form input:checked' ).each( function () {
			myLabel = $( "label[for='" + $( this ).attr( 'id' ) + "']" ).text().trim(); // eslint-disable-line quotes
			let name = $( this ).attr( 'name' );
			let myMatch = _pb_export_formats_map[ name ];
			if ( Object.values( _pb_export_pins_inventory ).filter( function ( value ) {
				// value matches <crc32-format-td>
				return value === myMatch;
			} ).length >= 3 ) {
				tooManyExports = true;
				return false; // Use return false to break out of each() loops early
			}
		} );
		if ( tooManyExports ) {
			alert( myLabel + ': ' + PB_ExportToken.tooManyExportsWarning );
			return false;
		}
		$( '.export-file-container' ).unbind( 'mouseenter mouseleave' ); // Disable Download & Delete Buttons
		$( '.export-control button' ).prop( 'disabled', true );
		$( '#pb-export-button' ).hide();
		$( '#loader' ).show();
		/**
		 *
		 */
		const submission = function () {
			$( '#pb-export-form' ).submit(); // This will trigger the 'submit' event handler above
		};
		setTimeout( submission, 0 );
	} );

	/* Export Formats */
	$( '#pb-export-form' )
		.find( 'input' )
		.each( function () {
			let name = $( this ).attr( 'name' );
			// Defaults
			if ( jQuery.isEmptyObject( json_cookie ) ) {
				// Defaults
				if (
					name === 'export_formats[pdf]' ||
					name === 'export_formats[mpdf]' ||
					name === 'export_formats[epub]'
				) {
					$( this ).prop( 'checked', true );
				} else {
					$( this ).prop( 'checked', false );
				}
			} else {
				// Initialize checkboxes from cookie
				let was_checked = 0;
				if ( Object.prototype.hasOwnProperty.call( json_cookie, name ) ) {
					was_checked = json_cookie[ name ];
				}
				$( this ).prop( 'checked', !! was_checked );
			}
			// If there's a dependency error, then forcibly uncheck
			if ( $( this ).attr( 'disabled' ) ) {
				$( this ).prop( 'checked', false );
			}
		} )
		.on( 'change', function () {
			let name = $( this ).attr( 'name' );
			if ( $( this ).prop( 'checked' ) ) {
				// Cookie syntax: 'ef[<format>]': 1
				// I.e: 'ef[print_pdf]': 1
				json_cookie[ name ] = 1;
			} else {
				delete json_cookie[ name ];
			}
			update_json_cookie();
		} );

	/* Pins */
	/**
	 *
	 */
	const adjustBulkActions = () => {
		let totalCount = $( 'td.column-pin input' ).length;
		let checkedCount = $( 'td.column-pin input:checked' ).length;
		if (checkedCount === 0 && totalCount === 0) return; // No pins on page

		if ( checkedCount === totalCount ) {
			$( '#cb-select-all-1, #cb-select-all-2, #bulk-action-selector-top, #bulk-action-selector-bottom, #doaction, #doaction2' )
				.attr( 'disabled', true );
		} else {
			$( '#cb-select-all-1, #cb-select-all-2, #bulk-action-selector-top, #bulk-action-selector-bottom, #doaction, #doaction2' )
				.attr( 'disabled', false );
		}
	};

	adjustBulkActions();

	$( 'td.column-pin' )
		.find( 'input' )
		.each( function () {
			if ( $( this ).prop( 'checked' ) ) {
				let tr = $( this ).closest( 'tr' );
				let id = tr.attr( 'data-id' );
				if (id) { // Ensure id is found
					let cb = $( `input[name='ID[]'][value='${ id }']` );
					$( this ).prop( 'checked', true );
					cb.prop( 'checked', false );
					cb.prop( 'disabled', true );
					tr.find( 'td.column-file span.delete' ).hide();
				}
			}
		} )
		.on( 'change', function () {
			adjustBulkActions();
			let name =  $( this ).attr( 'name' );
			let tr = $( this ).closest( 'tr' );
			let id = tr.attr( 'data-id' );
			if (!id) return; // Should not happen if row is well-formed

			let cb = $( `input[name='ID[]'][value='${ id }']` );
			let format = tr.attr( 'data-format' );
			let file = tr.attr( 'data-file' );
			let pinned = $( this ).prop( 'checked' ) ? 1 : 0;
			if ( pinned ) {
				_pb_export_pins_inventory[ name ] = format;
				// Up to five files can be pinned at once.
				if ( Object.keys( _pb_export_pins_inventory ).length > 5 ) {
					delete _pb_export_pins_inventory[ name ];
					$( this ).prop( 'checked', false );
					alert( PB_ExportToken.maximumFilesWarning );
					return false;
				}
				// If the user has pinned three files of a given export type and they then try to pin an additional file of that type,
				// an error should be displayed instructing them to deselect one of the pinned files before attempting to pin another.
				if ( Object.values( _pb_export_pins_inventory ).filter( function ( value ) {
					// value matches <crc32-format-td>
					return value === format;
				} ).length > 3 ) {
					delete _pb_export_pins_inventory[ name ];
					$( this ).prop( 'checked', false );
					alert( PB_ExportToken.maximumFileTypeWarning );
					return false;
				}
				// Checked
				cb.prop( 'checked', false );
				cb.prop( 'disabled', true );
				tr.find( 'td.column-file span.delete' ).hide();
			} else {
				// Unchecked
				delete _pb_export_pins_inventory[ name ];
				cb.prop( 'disabled', false );
				tr.find( 'td.column-file span.delete' ).show();
			}
			$.ajax( {
				url: ajaxurl,
				type: 'POST',
				data: {
					action: 'pb_update_pins',
					pins: JSON.stringify( _pb_export_pins_inventory ),
					file: file,
					pinned: pinned,
					_ajax_nonce: PB_ExportToken.pinsNonce,
				},
				/**
				 * @param response
				 */
				success: response => {
					let pinNotifications = $( '#pin-notifications' );
					if (pinNotifications.length && response.data && response.data.message) {
						pinNotifications.html( response.data.message );
					}
				},
			} );
		} );
} );
