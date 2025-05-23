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

	// return; // This was likely for debugging, removing it.
	// console.log('Document ready. export.js executing.'); // DEBUG: Document ready

	const exportForm = $( '#pb-export-form' );

	if (!exportForm.length) {
		// console.error('CRITICAL: Export form #pb-export-form NOT FOUND.'); // DEBUG: Form not found
		return; // Stop if form isn't found
	}
	// console.log('Export form #pb-export-form found.'); // DEBUG: Form found

	const mainButton = $( '#pb-export-button' );
	// const noticesContainer = $( '.notice-container' ); // Define if you have a specific notices container

	// DEBUG: Add a direct click listener to the export button
	// if (mainButton.length) {
	// 	console.log('Export button #pb-export-button found. Attaching click listener.');
	// 	mainButton.on('click', function(e) {
	// 		console.log('#pb-export-button CLICKED! Attempting to submit form manually for debug...');
	// 		// e.preventDefault(); // Optional: uncomment if you want to prevent default initially
	// 		exportForm.submit(); // TRY THIS: Programmatically submit the form
	// 	});
	// } else {
	// 	console.error('Export button #pb-export-button NOT FOUND.');
	// }

	// Track active jobs
	let activeJobs = new Set();
	let completedJobs = new Set();
	let failedJobs = new Set(); // Keep this for tracking failed jobs if needed.

	// Function to check if all jobs are complete and reload if needed
	// This might need adjustment based on how export-ui.js signals completion
	function checkAllJobsComplete() {
		if (activeJobs.size === 0 && (completedJobs.size > 0 || failedJobs.size > 0)) { // Consider failed jobs too
			// console.log('All jobs processed, considering page reload...');
			// Potentially get reload flag from initial AJAX in export-ui.js
			// For now, defer reload logic, might be handled by export-ui.js based on its AJAX response.
			// setTimeout(() => {
			// 	window.location.reload();
			// }, 2000);
		}
	}

	// Function to check for existing jobs and reconnect to their progress
	// This will need to be adapted to find rows created by export-ui.js
	function checkExistingJobs() {
		$.ajax({
			url: PB_ExportToken.ajaxurl,
			type: 'POST',
			data: {
				action: 'pressbooks_check_existing_jobs',
				_wpnonce: PB_ExportToken.nonce // Assuming PB_ExportToken.nonce is the correct general nonce for this
			},
			success: function(response) {
				if (response.success && response.data && response.data.jobs) {
					response.data.jobs.forEach(function(job) {
						if (job.status !== 'completed' && job.status !== 'failed' && job.status !== 'cancelled') {
							// TODO: Find the row in the table created by export-ui.js using job.module_slug or job.job_id
							// const tableRow = $(`tr[data-format='${job.module_slug}'][data-job-id='${job.job_id}']`); // Or similar selector
							// if (tableRow.length) {
							// Update UI in tableRow with job.progress_message, job.progress_percentage
							// console.log(`Reconnecting to job: ${job.job_id}, format: ${job.module_slug}`);
							// const progressBar = tableRow.find('.progress-bar');
							// const progressText = tableRow.find('.progress-text');
							// const statusCell = tableRow.find('.column-file .export-file-name i'); // Example

							// progressBar.css('width', job.progress_percentage + '%').text(job.progress_percentage + '%');
							// progressText.text(job.progress_percentage + '%');
							// if (statusCell) statusCell.text(job.progress_message || 'Reconnecting...');

							activeJobs.add(job.job_id);
							// listenForJobProgress(job.book_id, job.job_id, job.module_slug, job.sse_nonce, { bar: progressBar, info: progressText, statusElement: statusCell /* pass other relevant row elements */ });
							// }
						}
					});
				}
			}
		});
	}

	// Call checkExistingJobs when the page loads
	// checkExistingJobs(); // Temporarily disable until it's adapted for the new UI

	// The exportForm.on('submit', ...) handler has been removed as export-ui.js now handles this.

	/**
	 * Listens for job progress using Server-Sent Events (SSE).
	 * @param {string} bookId - The book ID.
	 * @param {string|number} jobId - The job ID.
	 * @param {string} moduleSlug - Slug for the export module.
	 * @param {string} sseNonce - Nonce for the SSE connection.
	 * @param {string} formatName - Display name for the format.
	 * @param {object} uiElements - Object containing jQuery elements for the job's UI (.bar, .info, .statusElement, .rowElement).
	 */
	function listenForJobProgress( bookId, jobId, moduleSlug, sseNonce, formatName, uiElements ) {
		// console.log(`SSE: Initializing for Job ID: ${jobId}, Format: ${moduleSlug}, Display: ${formatName}`); // DEBUG

		// Close existing EventSource for this job ID if any
		if (jobEventSources[jobId]) {
			jobEventSources[jobId].close();
			// console.log(`SSE: Closed existing EventSource for Job ID: ${jobId}`); // DEBUG
		}

		const source = new EventSource( PB_ExportToken.ajaxurl + `?action=pressbooks_export_status&job_id=${jobId}&_wpnonce=${sseNonce}&book_id=${bookId}` );
		jobEventSources[jobId] = source; // Store the new EventSource

		source.onopen = function ( event ) {
			// console.log( `SSE: Connection opened for Job ID: ${jobId}, Format: ${moduleSlug}`, event ); // DEBUG
			// Update UI to show "Connected" or similar
			if (uiElements.statusElement) uiElements.statusElement.text( 'Connected, waiting for progress...' );
		};

		source.onmessage = function ( event ) {
			// console.log( `SSE: Message for Job ID: ${jobId}, Format: ${moduleSlug}`, event.data ); // DEBUG
			try {
				const eventData = JSON.parse( event.data );

				if (uiElements.bar && uiElements.info) {
					uiElements.bar.removeClass( 'pb-sse-progressbar-success pb-sse-progressbar-error' );
					uiElements.bar.css( 'width', eventData.progress_percentage + '%' )
						.attr( 'aria-valuenow', eventData.progress_percentage );
					
					uiElements.info.text(eventData.progress_percentage + '%');

					if(uiElements.statusElement) { // For new UI file name status
						uiElements.statusElement.text( eventData.progress_message || (eventData.progress_percentage + '%') );
					} else if (uiElements.info) { // Fallback for old UI (will be removed) - though .info is now progressText
						// This fallback might not be very relevant anymore given the new structure.
						uiElements.info.html(`<strong>${eventData.format_name || formatName || moduleSlug}</strong>: ${eventData.progress_message || (eventData.progress_percentage + '%')}`);
					}
				}

				if ( eventData.status === 'completed' ) {
					// console.log( `SSE: Job ID ${jobId} (${moduleSlug}) COMPLETED.` ); // DEBUG
					if (uiElements.bar) {
						uiElements.bar.addClass( 'pb-sse-progressbar-success' ).css('width', '100%');
					}
					if(uiElements.info) { // This is the progressTextElement
						uiElements.info.text( (PB_ExportToken.text && PB_ExportToken.text.completed) || 'Completed');
					}

					if(uiElements.rowElement) {
						const fileNameElement = uiElements.rowElement.find('.column-file .export-file-name');
						if(fileNameElement.length) {
							let finalMessage = `<strong>${eventData.format_name || formatName || moduleSlug}</strong>: Completed.`;
							if (eventData.file_name && eventData.download_url) {
								finalMessage = `<a href="${eventData.download_url}" target="_blank">${eventData.file_name}</a>`;
							} else if (eventData.file_name) {
								finalMessage = eventData.file_name;
							}
							fileNameElement.html(finalMessage);
						}
						const sizeCell = uiElements.rowElement.find('.column-size');
						if(sizeCell.length && eventData.file_size) {
							sizeCell.text(eventData.file_size); // Assuming file_size is pre-formatted
						}
						uiElements.rowElement.removeClass('export-job-in-progress').addClass('export-job-completed');
					}
					
					source.close();
					delete jobEventSources[jobId];
					activeJobs.delete(jobId);
					completedJobs.add(jobId);
					checkAllJobsComplete();
				} else if ( eventData.status === 'failed' ) {
					// console.error( `SSE: Job ID ${jobId} (${moduleSlug}) FAILED. Error: ${eventData.error_message}` ); // DEBUG
					if (uiElements.bar) {
						uiElements.bar.addClass( 'pb-sse-progressbar-error' ).css('width', '100%');
						// uiElements.bar.text( PB_ExportToken.text.error || 'Error' );
					}
					if(uiElements.rowElement) {
						const progressTextElement = uiElements.rowElement.find('.progress-text');
						if(progressTextElement.length) progressTextElement.text(PB_ExportToken.text.error || 'Error');

						const statusCell = uiElements.rowElement.find('.column-file .export-file-name i');
						if(statusCell.length) statusCell.text( `Failed: ${eventData.error_message || 'Unknown error'}` ).css('color', 'red');
						uiElements.rowElement.removeClass('export-job-in-progress').addClass('export-job-failed');
					}

					source.close();
					delete jobEventSources[jobId];
					activeJobs.delete(jobId);
					failedJobs.add(jobId); // Track failed job
					checkAllJobsComplete();
				} else if (eventData.log_message) { // Handle general log messages if needed
					// console.log(`SSE Log (Job ${jobId}): ${eventData.log_message}`);
				}

			} catch ( e ) {
				console.error( `SSE: Error parsing message for Job ID ${jobId}:`, e, event.data ); // DEBUG
				// Potentially close source on parse error if messages become unrecoverable
				// source.close();
				// activeJobs.delete(jobId);
				// checkAllJobsComplete();
			}
		};

		source.onerror = function ( event ) {
			console.error( `SSE: Error for Job ID: ${jobId}, Format: ${moduleSlug}`, event ); // DEBUG
			if (uiElements.bar) {
				uiElements.bar.addClass( 'pb-sse-progressbar-error' ).css('width', '100%');
				// uiElements.bar.text( PB_ExportToken.text.error || 'Error: Connection Lost' );
			}
			if(uiElements.rowElement) {
				const progressTextElement = uiElements.rowElement.find('.progress-text');
				if(progressTextElement.length) progressTextElement.text(PB_ExportToken.text.error || 'Error');
				const statusCell = uiElements.rowElement.find('.column-file .export-file-name i');
				if(statusCell.length) statusCell.text( 'Error: Connection Lost' ).css('color', 'red');
				uiElements.rowElement.removeClass('export-job-in-progress').addClass('export-job-failed'); // Mark as failed on connection error
			}

			source.close();
			delete jobEventSources[jobId];
			activeJobs.delete(jobId);
			failedJobs.add(jobId); // Track as failed on SSE error
			checkAllJobsComplete();
		};
	}


	// Pinning functionality (remains largely unchanged for now, but ensure selectors are still valid)
	let pins = _pb_export_pins_inventory; // initial value from inline script

	// ... (rest of the original pinning logic, check selectors if they were tied to old progress bar area)
	// This part seems to interact with the table directly, so it might be okay.

	/**
	 * Save pins to user meta (via transient)
	 */
	function savePins() {
		// Disable all pin checkboxes during save
		$( 'input[name^="pin"]' ).prop( 'disabled', true );

		$.post( PB_ExportToken.ajaxurl, {
			action: 'pb_export_pins',
			pins: pins,
			_ajax_nonce: PB_ExportToken.pinsNonce, // Assuming pinsNonce is localized
		} ).always( function () {
			// Re-enable all pin checkboxes after save attempt
			$( 'input[name^="pin"]' ).prop( 'disabled', false );
		} );
	}

	// Event delegation for pin checkboxes within the table
	$( 'table.wp-list-table' ).on( 'change', 'input[name^="pin"]', function () {
		const postId = $( this ).attr( 'name' );
		const format = $( this ).val();
		if ( $( this ).prop( 'checked' ) ) {
			pins[postId] = format;
		} else {
			delete pins[postId];
		}

		// Validate pins
		const count = Object.keys( pins ).length;
		let types = {};
		for ( let k in pins ) {
			if ( pins.hasOwnProperty( k ) ) {
				if ( ! types[pins[k]] ) {
					types[pins[k]] = 0;
				}
				++types[pins[k]];
			}
		}

		let error = false;
		if ( count > 5 ) {
			$( this ).prop( 'checked', false );
			delete pins[postId];
			alert( PB_ExportToken.text.maximumFilesWarning );
			error = true;
		} else {
			for ( let k in types ) {
				if ( types.hasOwnProperty( k ) ) {
					if ( types[k] > 3 ) {
						$( this ).prop( 'checked', false );
						delete pins[postId];
						alert( PB_ExportToken.text.maximumFileTypeWarning );
						error = true;
						break;
					}
				}
			}
		}

		if ( ! error ) {
			savePins();
		}
	} );

	// Populate initial pin states (This should already be handled by server-side rendering of checkboxes)
	// for ( let k in pins ) {
	// 	if ( pins.hasOwnProperty( k ) ) {
	// 		$( 'input[name="' + k + '"]' ).prop( 'checked', true );
	// 	}
	// }


	// Delete button handler (if any outside the table rows, otherwise handled by WP_List_Table row actions)
	// $( '.delete-button-class' ).on( 'click', function() { ... } );

	// Unload warning if jobs are active
	$( window ).on( 'beforeunload', function () {
		if ( activeJobs.size > 0 ) {
			return PB_ExportToken.unloadWarning;
		}
	} );

} );

// Global function that could be called by export-ui.js after it has created rows and received job details.
// This is one way to bridge the two scripts.
window.PB_Export_AttachSSEListeners = function(jobsData) {
	// console.log('PB_Export_AttachSSEListeners called with:', jobsData);
	jobsData.forEach(job => {
		if (job.job_id && job.sse_nonce && job.module_slug) {
			const tableRow = jQuery(`tr.export-job-in-progress[data-format='${job.module_slug}'][data-job-id='${job.job_id}']`);
			if (tableRow.length) {
				// Ensure sse_nonce is set if not already (it should be by export-ui.js)
				if (!tableRow.attr('data-sse-nonce')) {
				    tableRow.attr('data-sse-nonce', job.sse_nonce);
				}

				const progressBar = tableRow.find('.progress-bar');
				const progressText = tableRow.find('.progress-text'); // The one inside the bar
				const statusElement = tableRow.find('.column-file .export-file-name i'); // The italic text for status

				// Add to active jobs tracked by this script (export.js)
				// jQuery needed here as activeJobs is defined within jQuery ready scope
				jQuery(function($) {
					activeJobs.add(job.job_id);
				});
				
				listenForJobProgress(job.book_id, job.job_id, job.module_slug, job.sse_nonce, job.format_name, {
					bar: progressBar,
					info: progressText, 
					statusElement: statusElement, 
					rowElement: tableRow
				});
			} else {
				console.warn(`PB_Export_AttachSSEListeners: Could not find table row for format: ${job.module_slug}, job ID: ${job.job_id} to attach SSE listener.`);
			}
		}
	});
};
