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

	// Wrap main progress bar in container if not already wrapped
	if (mainProgressBar.length && !mainProgressBar.parent().hasClass('pb-sse-progressbar-container')) {
		mainProgressBar.wrap('<div class="pb-sse-progressbar-container"></div>');
	}

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
	 * Updates the UI elements for a specific job's progress.
	 * @param {string} moduleSlug - Slug for the export module (e.g., 'prince-pdf').
	 * @param {string|number} jobId - The job ID.
	 * @returns {object} Contains .bar (progressbar) and .info (text area) jQuery objects.
	 */
	function getOrCreateJobProgressUI( moduleSlug, jobId ) {
		const mainProgressBar = $( '#pb-sse-progressbar' );
		const mainInfoText = $( '#pb-sse-info' );
		
		// Update the main progress bar and info text with job-specific information
		const friendlyName = _pb_export_formats_map && _pb_export_formats_map[moduleSlug] ? _pb_export_formats_map[moduleSlug].name : moduleSlug.toUpperCase();
		mainInfoText.html( `<strong>${friendlyName}</strong> (Job ID: ${jobId})` );
		
		return {
			bar: mainProgressBar,
			info: mainInfoText,
			container: mainProgressBar.parent()
		};
	}

	// Track active jobs
	let activeJobs = new Set();
	let completedJobs = new Set();

	// Function to check if all jobs are complete and reload if needed
	function checkAllJobsComplete() {
		if (activeJobs.size === 0 && completedJobs.size > 0) {
			console.log('All jobs completed, reloading page...');
			setTimeout(() => {
				window.location.reload();
			}, 2000); // Wait 2 seconds before reloading to show completion message
		}
	}

	// Function to check for existing jobs and reconnect to their progress
	function checkExistingJobs() {
		$.ajax({
			url: PB_ExportToken.ajaxurl,
			type: 'POST',
			data: {
				action: 'pressbooks_check_existing_jobs',
				_wpnonce: PB_ExportToken.nonce
			},
			success: function(response) {
				if (response.success && response.data && response.data.jobs) {
					response.data.jobs.forEach(function(job) {
						if (job.status !== 'completed' && job.status !== 'failed' && job.status !== 'cancelled') {
							// Create UI for this job if it's still in progress
							const jobUI = getOrCreateJobProgressUI(job.module_slug, job.job_id);
							jobUI.info.html(`<strong>${job.format_name || job.module_slug}</strong>: ${job.progress_message || 'Reconnecting to export progress...'}`);
							jobUI.bar.css('width', job.progress_percentage + '%')
								.attr('aria-valuenow', job.progress_percentage)
								.text(job.progress_percentage + '%');
							
							// Add to active jobs
							activeJobs.add(job.job_id);
							
							// Reconnect to the SSE stream
							listenForJobProgress(job.book_id, job.job_id, job.module_slug, job.sse_nonce, jobUI);
							$('.pb-sse-progressbar-container').show();
						}
					});
				}
			}
		});
	}

	// Call checkExistingJobs when the page loads
	checkExistingJobs();

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

		const bar = $('.pb-sse-progressbar');
		bar.removeClass('pb-sse-progressbar-success pb-sse-progressbar-error');
		bar.css('width', '0%').attr('aria-valuenow', 0).text('0%');

		mainButton.prop( 'disabled', true ).val( PB_ExportToken.text.exporting );
		$('.pb-sse-progressbar-container').show();
		mainProgressBar.show();
		mainProgressBar.css('width', '5%').attr( 'aria-valuenow', 5 ).text( '5%' );
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
					mainProgressBar.css('width', '100%').addClass( 'pb-sse-progressbar-error' ).text( 'Error' );
					console.error('Empty response from server during job submission.'); // DEBUG
					return;
				}

				if ( response.success && response.data && response.data.results ) {
					mainInfoText.html( 'Processing export request...' );
					mainProgressBar.show()
						.css('width', '10%')
						.attr('aria-valuenow', 10)
						.text('10%');

					// Clear previous job tracking
					activeJobs.clear();
					completedJobs.clear();

					response.data.results.forEach(function(eventData) {
						console.log('Processing event from initial AJAX response:', eventData); // DEBUG
						let jobUI;

						if ( eventData.event_type === 'job_queued' ) {
							jobUI = getOrCreateJobProgressUI( eventData.module_slug, eventData.job_id );
							jobUI.info.html( `<strong>${eventData.format_name || eventData.module_slug}</strong>: ${eventData.message}` );
							jobUI.bar.css('width', '5%')
								.attr('aria-valuenow', 5)
								.text('5%');
							
							// Add to active jobs
							activeJobs.add(eventData.job_id);
							
							listenForJobProgress( eventData.book_id, eventData.job_id, eventData.module_slug, eventData.sse_nonce, jobUI );
						} else if ( eventData.event_type === 'job_queue_failed' ) {
							jobUI = getOrCreateJobProgressUI( eventData.module_slug, 'failed-' + Date.now() );
							jobUI.info.html( `<span style="color: red;"><strong>${eventData.format_name || eventData.module_slug}</strong>: Queueing Failed - ${eventData.message}</span>` );
							jobUI.bar.css('width', '100%')
								.addClass('pb-sse-progressbar-error')
								.text('Error');
						} else if ( eventData.event_type === 'validation_error' ) {
							displayNotice('error', eventData.message);
							mainInfoText.html( `<strong>Validation Error:</strong> ${eventData.message}` );
							mainProgressBar.css('width', '100%')
								.addClass( 'pb-sse-progressbar-error' )
								.text('Error');
						} else if (eventData.event_type === 'sync_export_completed') {
							mainInfoText.html(`<strong>${eventData.format_name || 'Export'}</strong>: ${eventData.message}`);
							if (eventData.download_url) {
								window.location.href = eventData.download_url;
							}
							mainProgressBar.css('width', '100%')
								.text('Completed');
						}
					});

					// If reload_on_complete is true, we'll reload when all jobs finish
					if (response.data.reload_on_complete) {
						console.log(`Will reload page when ${response.data.total_jobs} jobs complete`);
					}

				} else if (response.success && response.data && response.data.message) {
					mainInfoText.html( response.data.message );
					if (response.data.redirect) {
						window.location.href = response.data.redirect;
					} else if (response.data.download_url) {
						// This case might be for non-SSE direct downloads
						window.location.href = response.data.download_url;
						mainProgressBar.css('width', '100%').text( 'Completed' );
					}
				} else if ( ! response.success && response.data && response.data.message ) {
					mainInfoText.html( response.data.message );
					mainProgressBar.css('width', '100%').addClass( 'pb-sse-progressbar-error' ).text( 'Error' );
					displayNotice( 'error', response.data.message );
					console.error('Server returned error during job submission:', response.data.message); // DEBUG
				} else {
					mainInfoText.html( 'Error: Unexpected response from server.' );
					mainProgressBar.css('width', '100%').addClass( 'pb-sse-progressbar-error' ).text( 'Error' );
					console.error('Unexpected response from server:', response); // DEBUG
				}
			},
			error: function ( jqXHR, textStatus, errorThrown ) {
				console.error('AJAX error during job submission:', textStatus, errorThrown, jqXHR.responseText); // DEBUG
				mainButton.prop( 'disabled', false ).val( PB_ExportToken.text.export );
				mainProgressBar.show().css('width', '100%').addClass( 'pb-sse-progressbar-error' ).text( 'Error' );
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
			console.log(`SSE Progress for job ${jobId}:`, eventData); // DEBUG

			if (eventData.progress) {
				jobUI.bar.css('width', eventData.progress + '%')
					.attr('aria-valuenow', eventData.progress)
					.text(eventData.progress + '%');
			}

			if (eventData.message) {
				jobUI.info.html(`<strong>${eventData.format_name || moduleSlug}</strong>: ${eventData.message}`);
			}

			if (eventData.event_type === 'job_completed') {
				jobUI.bar.css('width', '100%')
					.attr('aria-valuenow', 100)
					.text('100%')
					.addClass('pb-sse-progressbar-success');
				
				// Move from active to completed
				activeJobs.delete(jobId);
				completedJobs.add(jobId);
				
				// Check if all jobs are done
				checkAllJobsComplete();
				
				// Close the SSE connection
				jobEventSources[jobId].close();
				delete jobEventSources[jobId];
			} else if (eventData.event_type === 'job_failed') {
				jobUI.bar.css('width', '100%')
					.addClass('pb-sse-progressbar-error')
					.text('Error');
				jobUI.info.html(`<span style="color: red;"><strong>${eventData.format_name || moduleSlug}</strong>: ${eventData.message}</span>`);
				
				// Move from active to completed (even though it failed)
				activeJobs.delete(jobId);
				completedJobs.add(jobId);
				
				// Check if all jobs are done
				checkAllJobsComplete();
				
				// Close the SSE connection
				jobEventSources[jobId].close();
				delete jobEventSources[jobId];
			}
		});

		jobEventSources[jobId].addEventListener('error', function ( event ) {
			console.error(`SSE Error for job ${jobId}:`, event); // DEBUG
			jobUI.info.html(`<span style="color: red;"><strong>${moduleSlug}</strong>: Connection error. Please refresh the page.</span>`);
			jobUI.bar.css('width', '100%')
				.addClass('pb-sse-progressbar-error')
				.text('Error');
			
			// Close the SSE connection
			jobEventSources[jobId].close();
			delete jobEventSources[jobId];
			
			// Move from active to completed (due to error)
			activeJobs.delete(jobId);
			completedJobs.add(jobId);
			
			// Check if all jobs are done
			checkAllJobsComplete();
		});
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
