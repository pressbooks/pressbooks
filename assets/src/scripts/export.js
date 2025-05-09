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
	console.log('Document ready. export.js executing.'); // DEBUG: Document ready

	const exportForm = $( '#pb-export-form' );

	if (!exportForm.length) {
		console.error('CRITICAL: Export form #pb-export-form NOT FOUND.'); // DEBUG: Form not found
		return; // Stop if form isn't found
	}
	console.log('Export form #pb-export-form found.'); // DEBUG: Form found

	const mainButton = $( '#pb-export-button' );
	const mainProgressBar = $( '#pb-sse-progressbar' ); // Main progress bar for initial/overall status
	const mainInfoText = $( '#pb-sse-info' ); // Main info display area
	// const noticesContainer = $( '.notice-container' ); // Define if you have a specific notices container

	// DEBUG: Add a direct click listener to the export button
	if (mainButton.length) {
		console.log('Export button #pb-export-button found. Attaching click listener.');
		mainButton.on('click', function(e) {
			console.log('#pb-export-button CLICKED! Attempting to submit form manually for debug...');
			// e.preventDefault(); // Optional: uncomment if you want to prevent default initially
			exportForm.submit(); // TRY THIS: Programmatically submit the form
		});
	} else {
		console.error('Export button #pb-export-button NOT FOUND.');
	}

	/**
	 * Creates or retrieves the UI elements for a specific job's progress.
	 * @param {string} moduleSlug - Slug for the export module (e.g., 'prince-pdf').
	 * @param {string|number} jobId - The job ID.
	 * @returns {object} Contains .bar (progressbar) and .info (text area) jQuery objects.
	 */
	function getOrCreateJobProgressUI( moduleSlug, jobId ) {
		const containerId = `job-progress-${jobId}`;
		let jobProgressContainer = $( `#${containerId}` );

		if ( ! jobProgressContainer.length ) {
			// Ensure there's a main container for all job progress bars if not already present
			let allJobsContainer = $( '#pb-all-jobs-progress-container' );
			if ( ! allJobsContainer.length ) {
				allJobsContainer = $( '<div id="pb-all-jobs-progress-container" style="margin-top: 20px;"></div>' );
				exportForm.after( allJobsContainer );
			}

			const friendlyName = _pb_export_formats_map && _pb_export_formats_map[moduleSlug] ? _pb_export_formats_map[moduleSlug].name : moduleSlug.toUpperCase();
			jobProgressContainer = $( `
				<div id="${containerId}" class="job-progress-item" style="margin-bottom: 15px;">
					<h4>${friendlyName} (Job ID: ${jobId})</h4>
					<div class="pb-sse-progressbar-container">
						<div id="pb-sse-progressbar-${jobId}" class="pb-sse-progressbar" style="width: 0%;" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
					</div>
					<p id="pb-sse-info-${jobId}" class="pb-sse-info">Queued...</p>
				</div>
			` );
			allJobsContainer.append( jobProgressContainer );
		}

		return {
			bar: $( `#pb-sse-progressbar-${jobId}` ),
			info: $( `#pb-sse-info-${jobId}` ),
			container: jobProgressContainer,
		};
	}


	exportForm.on( 'submit', function ( e ) {
		console.log('Export form submitted.'); // DEBUG

		e.preventDefault();

		const formData = new FormData( this );

		// Append action and nonce for the AJAX request
		formData.append('action', 'pb_export_book');
		formData.append('pb_export_nonce', PB_ExportToken.nonce); // Ensure this matches the nonce localized

		// DEBUG: Log all form data entries
		console.log('FormData entries (after adding action and nonce):');
		for (const pair of formData.entries()) {
			console.log(pair[0] + ': ' + pair[1]);
		}

		const selectedFormats = formData.getAll( 'export_formats[]' );

		console.log('Selected formats:', selectedFormats);

		if ( ! selectedFormats || selectedFormats.length === 0 ) {
			displayNotice( 'error', PB_ExportToken.text.select_format );
			return;
		}

		mainButton.prop( 'disabled', true ).val( PB_ExportToken.text.exporting );
		mainProgressBar.show().width( '5%' ).attr( 'aria-valuenow', 5 ).text( '5%' );
		mainInfoText.text( PB_ExportToken.text.starting_export );
		// noticesContainer.empty(); // Clear previous notices

		// Reset UI for any individual jobs if they exist from a previous run
		$('.job-progress-item').remove();


		$.ajax( {
			url: PB_ExportToken.ajaxurl,
			type: 'POST',
			data: formData,
			processData: false,
			contentType: false,
			dataType: 'json',
			success: function ( response ) {
				console.log('AJAX response for job submission:', response); // DEBUG

				mainButton.prop( 'disabled', false ).val( PB_ExportToken.text.export ); // Re-enable main button

				if ( ! response ) {
					mainInfoText.html( 'Error: Empty response from server.' );
					mainProgressBar.width( '100%' ).addClass( 'pb-sse-progressbar-error' ).text( 'Error' );
					console.error('Empty response from server during job submission.'); // DEBUG
					return;
				}

				if ( response.success && response.data && response.data.results ) {
					mainInfoText.html( 'Processing export request...' );
					mainProgressBar.width( '10%' ).attr( 'aria-valuenow', 10 ).text( '10%' );

					response.data.results.forEach(function(eventData) {
						console.log('Processing event from initial AJAX response:', eventData); // DEBUG
						let jobUI;

						if ( eventData.event_type === 'job_queued' ) {
							mainProgressBar.hide(); // Hide main progress bar as individual ones will take over
							jobUI = getOrCreateJobProgressUI( eventData.module_slug, eventData.job_id );
							jobUI.info.html( eventData.message );
							jobUI.bar.val( 5 ).css('width', '5%').text('5%');
							mainInfoText.html( `Export for ${eventData.module_slug.toUpperCase()} has been queued. See details below or await completion.` );
							console.log(`Calling listenForJobProgress for job ${eventData.job_id}, book ${eventData.book_id}, slug ${eventData.module_slug}`); // DEBUG
							listenForJobProgress( eventData.book_id, eventData.job_id, eventData.module_slug, eventData.sse_nonce, jobUI );
						} else if ( eventData.event_type === 'job_queue_failed' ) {
							jobUI = getOrCreateJobProgressUI( eventData.module_slug, 'failed-' + Date.now() ); // Unique ID for failed queue
							jobUI.info.html( `<span style="color: red;">Queueing Failed: ${eventData.message}</span>` );
							jobUI.bar.val( 100 ).css('width', '100%').addClass('pb-sse-progressbar-error').text('Error');
							mainInfoText.append( `<br/>Failed to queue job for ${eventData.module_slug.toUpperCase()}.` );
							console.error('Job queue failed event:', eventData); // DEBUG
						} else if ( eventData.event_type === 'validation_error' ) {
							// Handle validation errors if they are part of the initial response
							displayNotice('error', eventData.message);
							mainInfoText.html( eventData.message );
							mainProgressBar.width( '100%' ).addClass( 'pb-sse-progressbar-error' ).text( 'Error' );
						} else if (eventData.event_type === 'sync_export_completed') {
							// Handle synchronous exports if any were part of the request
							mainInfoText.html(eventData.message);
							if (eventData.download_url) {
								window.location.href = eventData.download_url;
							}
							mainProgressBar.width('100%').text('Completed');
						}
					});


				} else if (response.success && response.data && response.data.message) { // General success message
					mainInfoText.html( response.data.message );
					if (response.data.redirect) {
						window.location.href = response.data.redirect;
					} else if (response.data.download_url) {
						// This case might be for non-SSE direct downloads
						window.location.href = response.data.download_url;
						mainProgressBar.width( '100%' ).text( 'Completed' );
					}
				} else if ( ! response.success && response.data && response.data.message ) { // Error message
					mainInfoText.html( response.data.message );
					mainProgressBar.width( '100%' ).addClass( 'pb-sse-progressbar-error' ).text( 'Error' );
					displayNotice( 'error', response.data.message );
					console.error('Server returned error during job submission:', response.data.message); // DEBUG
				} else {
					mainInfoText.html( 'Error: Unexpected response from server.' );
					mainProgressBar.width( '100%' ).addClass( 'pb-sse-progressbar-error' ).text( 'Error' );
					console.error('Unexpected response from server:', response); // DEBUG
				}
			},
			error: function ( jqXHR, textStatus, errorThrown ) {
				console.error('AJAX error during job submission:', textStatus, errorThrown, jqXHR.responseText); // DEBUG
				mainButton.prop( 'disabled', false ).val( PB_ExportToken.text.export );
				mainProgressBar.show().width( '100%' ).addClass( 'pb-sse-progressbar-error' ).text( 'Error' );
				mainInfoText.html( `AJAX Error: ${textStatus} - ${errorThrown}. Check console for details.` );
				displayNotice( 'error', `AJAX Error: ${textStatus} - ${errorThrown}` );
			},
		} );
	} );


	/**
	 * Listens for Server-Sent Events for a specific job.
	 * @param {string|number} bookId - The Book ID.
	 * @param {string|number} jobId - The Job ID.
	 * @param {string} moduleSlug - Slug for the export module.
	 * @param {string} sseNonce - Nonce for the SSE connection.
	 * @param {object} jobUI - UI elements for this job.
	 */
	function listenForJobProgress(bookId, jobId, moduleSlug, sseNonce, jobUI) {
		if (jobEventSources[jobId]) {
			jobEventSources[jobId].close(); // Close existing connection if any
		}

		const sseUrl = PB_ExportToken.ajaxurl + '?action=pressbooks_export_status_sse&job_id=' + jobId + '&book_id=' + bookId + '&_wpnonce=' + sseNonce;
		console.log(`SSE: Connecting to ${sseUrl} for job ${jobId}`); // DEBUG
		jobEventSources[jobId] = new EventSource(sseUrl);

		jobEventSources[jobId].addEventListener('export_progress', function ( event ) {
			const eventData = JSON.parse(event.data);
			console.log(`SSE: Received data for job ${jobId} (event: export_progress):`, eventData); // DEBUG

			jobUI.info.html(eventData.message || 'Processing...');
			jobUI.bar.css('width', eventData.progress + '%').attr('aria-valuenow', eventData.progress).text(eventData.progress + '%');

			if (eventData.status === 'completed') {
				jobUI.info.html(eventData.message || 'Export completed!');
				jobUI.bar.removeClass('pb-sse-progressbar-error').addClass('pb-sse-progressbar-success'); // Ensure success class
				if (eventData.file_url) {
					// Create a download link
					const downloadLink = $('<a></a>')
						.attr('href', eventData.file_url)
						.attr('download', '') // Suggests download
						.addClass('button button-primary')
						.text(PB_ExportToken.text.download_file || 'Download File');
					jobUI.info.append('<br/>').append(downloadLink);
				}
				jobEventSources[jobId].close();
				delete jobEventSources[jobId];
				console.log(`SSE: Closed connection for completed job ${jobId}`); // DEBUG
			} else if (eventData.status === 'failed' || eventData.status === 'error' ) {
				jobUI.info.html(`<span style="color: red;">Failed: ${eventData.message || 'An unknown error occurred.'}</span>`);
				jobUI.bar.addClass('pb-sse-progressbar-error');
				jobEventSources[jobId].close();
				delete jobEventSources[jobId];
				console.log(`SSE: Closed connection for failed job ${jobId}`); // DEBUG
			}
		});

		jobEventSources[jobId].onerror = function (error) {
			console.error(`SSE: Error for job ${jobId}:`, error, "URL:", sseUrl); // DEBUG
			jobUI.info.html('<span style="color: red;">Error connecting to progress updates. Please try exporting again. If the issue persists, check server logs.</span>');
			jobUI.bar.addClass('pb-sse-progressbar-error');
			jobEventSources[jobId].close();
			delete jobEventSources[jobId];
		};
	}


	// Handle Clock
	const clock = $( '#pb-export-clock' );
	if ( clock.length ) {
		resetClock( clock );
		const timer =Cookies.get( PB_ExportToken.cookie.timer );
		if ( timer ) {
			startClock( clock, new Date( timer ) );
		}
		exportForm.on( 'submit', function () {
			resetClock( clock );
			startClock( clock, new Date() );
		} );
	}

	// Handle pins
	const pins = $( '.pb-pinnedexport' );
	if ( pins.length ) {
		exportForm.on( 'submit', function () {
			const formats = $( this ).find( 'input[name="export_formats[]"]:checked' );
			formats.each( function () {
				const slug = $( this ).val();
				const inventory = _pb_export_pins_inventory[slug];
				if ( inventory && Cookies.get( inventory ) ) {
					Cookies.remove( inventory );
				}
			} );
		} );
	}

	// Handle select all/none
	const selectAll = $( '#pb-export-select-all' );
	const selectNone = $( '#pb-export-select-none' );
	const checkboxes = exportForm.find( 'input[type="checkbox"][name="export_formats[]"]' );

	selectAll.on('click', function(e) {
		e.preventDefault();
		checkboxes.prop('checked', true);
	});

	selectNone.on('click', function(e) {
		e.preventDefault();
		checkboxes.prop('checked', false);
	});

} );
