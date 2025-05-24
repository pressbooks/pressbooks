/* global PB_ExportToken */
/* global _pb_export_formats_map */
/* global _pb_export_pins_inventory */

import Cookies from 'js-cookie';

import displayNotice from './utils/displayNotice';
import resetClock from './utils/resetClock';
import startClock from './utils/startClock';

// Stores the single global EventSource instance for all user jobs.
let globalExportSSE = null;
let globalExportSSENonce = null; // Will be populated from PB_ExportToken
let globalExportSSEBookId = null; // Will be populated from PB_ExportToken

// Define job tracking sets at a higher scope
let activeJobs = new Set();
let completedJobs = new Set();
let failedJobs = new Set();

/**
 * Updates the UI for a specific job row based on SSE data.
 * @param {object} jobData - Data received from SSE for a job.
 */
function updateJobRowUI(jobData) {
	console.log('updateJobRowUI called with:', JSON.stringify(jobData)); // DEBUG
	const tableRow = jQuery(`tr.export-job-row[data-job-id='${jobData.job_id}']`);
	console.log('updateJobRowUI: tableRow found:', tableRow.length, tableRow); // DEBUG
	if (!tableRow.length) {
		// console.warn(`UI Update: Could not find table row for job ID: ${jobData.job_id}`);
		return;
	}

	const progressBar = tableRow.find('.progress-bar');
	const progressText = tableRow.find('.progress-text');
	const statusElement = tableRow.find('.column-file .export-file-name i'); // Italicized status text
	const fileNameElement = tableRow.find('.column-file .export-file-name span.file-name-text'); // Span to hold filename/link
	const sizeCell = tableRow.find('.column-size');

	console.log('updateJobRowUI: progressBar found:', progressBar.length); // DEBUG
	console.log('updateJobRowUI: progressText found:', progressText.length); // DEBUG
	console.log('updateJobRowUI: statusElement found:', statusElement.length); // DEBUG
	console.log('updateJobRowUI: fileNameElement found:', fileNameElement.length); // DEBUG

	if (progressBar.length && progressText.length) {
		progressBar.removeClass('pb-sse-progressbar-success pb-sse-progressbar-error');
		progressBar.css('width', jobData.progress_percentage + '%').attr('aria-valuenow', jobData.progress_percentage);
		progressText.text(jobData.progress_percentage + '%');
	}

	if (statusElement.length) {
		statusElement.text(jobData.progress_message || (jobData.progress_percentage + '%') );
	}

	if (jobData.status === 'completed') {
		if (progressBar.length) progressBar.addClass('pb-sse-progressbar-success').css('width', '100%');
		if (progressText.length) progressText.text((PB_ExportToken.text && PB_ExportToken.text.completed) || 'Completed');
		if (statusElement.length) statusElement.hide(); // Hide italic status on complete
		
		if (fileNameElement.length) {
			let finalMessage = `<strong>${jobData.format_name || jobData.module_slug}</strong>: Completed.`;
			if (jobData.file_name && jobData.download_url) {
				finalMessage = `<a href="${jobData.download_url}" target="_blank">${jobData.file_name}</a>`;
			} else if (jobData.file_name) {
				finalMessage = jobData.file_name;
			}
			fileNameElement.html(finalMessage);
		}
		if (sizeCell.length && jobData.file_size) {
			sizeCell.text(jobData.file_size);
		}
		tableRow.removeClass('export-job-in-progress export-job-failed').addClass('export-job-completed');

		activeJobs.delete(jobData.job_id);
		completedJobs.add(jobData.job_id);

	} else if (jobData.status === 'failed') {
		if (progressBar.length) progressBar.addClass('pb-sse-progressbar-error').css('width', '100%');
		if (progressText.length) progressText.text((PB_ExportToken.text && PB_ExportToken.text.error) || 'Error');

		if (statusElement.length) {
			statusElement.text(`Failed: ${jobData.error_message || jobData.progress_message || 'Unknown error'}`).show().css('color', 'red');
		}
		if (fileNameElement.length && !fileNameElement.find('a').length) { // Only update if not already a link
			 fileNameElement.html(`<strong>${jobData.format_name || jobData.module_slug}</strong>`);
		}
		tableRow.removeClass('export-job-in-progress export-job-completed').addClass('export-job-failed');
		
		activeJobs.delete(jobData.job_id);
		failedJobs.add(jobData.job_id);
	} else {
		// Still in progress or pending
		tableRow.removeClass('export-job-completed export-job-failed').addClass('export-job-in-progress');
		if (fileNameElement.length && !fileNameElement.find('a').length) { 
			fileNameElement.html(`<strong>${jobData.format_name || jobData.module_slug}</strong>`);
		}
		if(statusElement.length) statusElement.show().css('color', ''); // Reset color
		activeJobs.add(jobData.job_id); // Ensure it's in active jobs
	}
	checkAllJobsComplete();
}

/**
 * Checks if all initially queued jobs have completed or failed.
 */
function checkAllJobsComplete() {
	if (activeJobs.size === 0 && (completedJobs.size > 0 || failedJobs.size > 0)) {
		// console.log('All export jobs processed.');
		// Potentially trigger a page reload if PB_ExportToken.reloadOnComplete is true,
		// which should be set by the initial AJAX response from export-ui.js
		if (window.PB_Export_ReloadOnComplete) {
			// console.log('Reloading page as all jobs are done and reload flag is set.');
			// setTimeout(() => { window.location.reload(); }, 2000); // Optional delay
		}
	}
}

/**
 * Initializes the single global Server-Sent Events stream for all user export jobs.
 */
function initializeGlobalExportFeed() {
	console.log('SSE Feed: initializeGlobalExportFeed called.'); // DEBUG
	if (!PB_ExportToken || !PB_ExportToken.ajaxurl || !PB_ExportToken.userExportFeedNonce || !PB_ExportToken.bookId) {
		console.error('SSE Feed: Missing required PB_ExportToken properties (ajaxurl, userExportFeedNonce, bookId).');
		return;
	}

	globalExportSSENonce = PB_ExportToken.userExportFeedNonce;
	globalExportSSEBookId = PB_ExportToken.bookId;

	if (globalExportSSE && globalExportSSE.readyState !== EventSource.CLOSED) {
		console.log('SSE Feed: Connection already active.'); // DEBUG
		return;
	}

	const sseUrl = `${PB_ExportToken.ajaxurl}?action=pressbooks_user_export_feed&_wpnonce=${globalExportSSENonce}&book_id=${globalExportSSEBookId}`;
	console.log('SSE Feed: Initializing connection to:', sseUrl); // DEBUG
	globalExportSSE = new EventSource(sseUrl);

	globalExportSSE.onopen = function(event) {
		console.log('SSE Feed: Connection opened.', event); // DEBUG
	};

	globalExportSSE.addEventListener('export_job_update', function(event) {
		console.log('Raw SSE event data (export_job_update):', event.data); // DEBUG: Log raw data
		try {
			const jobData = JSON.parse(event.data);
			console.log('SSE Feed (export_job_update):', jobData); // DEBUG
			updateJobRowUI(jobData);
		} catch (e) {
			console.error('SSE Feed: Error parsing export_job_update data:', e, event.data);
		}
	});

	globalExportSSE.addEventListener('heartbeat', function(event) {
		console.log('Raw SSE event data (heartbeat):', event.data); // DEBUG
		try {
			const data = JSON.parse(event.data);
			// Expect only timestamp for genuine heartbeats now
			if (data.timestamp) {
				console.log('SSE Feed (heartbeat received timestamp):', data.timestamp); // DEBUG
			} else {
				console.log('SSE Feed (heartbeat received unexpected data structure):', data); // DEBUG
			}
		} catch (e) {
			console.error('SSE Feed: Error parsing heartbeat data:', e, event.data);
		}
	});

	globalExportSSE.onerror = function(event) {
		console.error('SSE Feed: Connection error.', event); // DEBUG
		// Optional: Implement retry logic or inform user persistently.
		// For now, it will automatically try to reconnect by default unless server sends HTTP 204.
		// If readyState is CLOSED, we might want to nullify globalExportSSE to allow re-init on next action.
		if (globalExportSSE && globalExportSSE.readyState === EventSource.CLOSED) {
			console.log('SSE Feed: Connection was closed. Nullifying globalExportSSE.'); // DEBUG
			globalExportSSE = null; // Allow re-initialization
		}
	};
}

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

	// Initialize the global SSE feed when the page is ready.
	// Assumes PB_ExportToken.userExportFeedNonce and PB_ExportToken.bookId are localized.
	initializeGlobalExportFeed();
	
	// When new jobs are added by export-ui.js, they will have data-job-id.
	// The global SSE feed will pick up their updates.
	// The PB_Export_AttachSSEListeners function is no longer needed to attach individual listeners.

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

	// Unload warning if jobs are active - REMOVING THIS as it contradicts background processing benefits
	/*
	$( window ).on( 'beforeunload', function () {
		if ( activeJobs.size > 0 ) {
			return PB_ExportToken.unloadWarning;
		}
	} );
	*/

	// Function for export-ui.js to potentially call if the SSE connection needs to be re-ensured after AJAX.
	// Or, export-ui.js could just rely on the initial load's connection.
	window.PB_EnsureGlobalExportFeed = function() {
		// console.log('PB_EnsureGlobalExportFeed called.');
		initializeGlobalExportFeed(); 
	};

} );
