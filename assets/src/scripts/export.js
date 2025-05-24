/* global PB_ExportToken */
/* global _pb_export_formats_map */
/* global _pb_export_pins_inventory */


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
	const tableRow = jQuery(`tr.export-job-row[data-job-id='${jobData.job_id}']`);
	if (!tableRow.length) {
		return;
	}

	const progressBar = tableRow.find('.progress-bar');
	const progressText = tableRow.find('.progress-text');
	const statusElement = tableRow.find('.column-file .export-file-name i');
	const fileNameElement = tableRow.find('.column-file .export-file-name span.file-name-text');
	const sizeCell = tableRow.find('.column-size');


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
		tableRow.removeClass('export-job-completed export-job-failed').addClass('export-job-in-progress');
		if (fileNameElement.length && !fileNameElement.find('a').length) {
			fileNameElement.html(`<strong>${jobData.format_name || jobData.module_slug}</strong>`);
		}
		if(statusElement.length) statusElement.show().css('color', '');
		activeJobs.add(jobData.job_id);
	}
	checkAllJobsComplete();
}

/**
 * Checks if all initially queued jobs have completed or failed.
 */
function checkAllJobsComplete() {
	if (activeJobs.size === 0 && (completedJobs.size > 0 || failedJobs.size > 0)) {
		if (window.PB_Export_ReloadOnComplete) {
			setTimeout(() => { window.location.reload(); }, 2000); // Optional delay
		}
	}
}

/**
 * Initializes the single global SSE stream for all user export jobs.
 */
function initializeGlobalExportFeed() {

	if (!PB_ExportToken || !PB_ExportToken.ajaxurl || !PB_ExportToken.userExportFeedNonce || !PB_ExportToken.bookId) {
		console.error('SSE Feed: Missing required PB_ExportToken properties (ajaxurl, userExportFeedNonce, bookId).');
		return;
	}

	globalExportSSENonce = PB_ExportToken.userExportFeedNonce;
	globalExportSSEBookId = PB_ExportToken.bookId;

	if (globalExportSSE && globalExportSSE.readyState !== EventSource.CLOSED) {
		console.log('SSE Feed: Connection already active.');
		return;
	}

	const sseUrl = `${PB_ExportToken.ajaxurl}?action=pb_sse_exports&_wpnonce=${globalExportSSENonce}&book_id=${globalExportSSEBookId}`;
	globalExportSSE = new EventSource(sseUrl);

	globalExportSSE.addEventListener('export_job_update', function(event) {
		try {
			const jobData = JSON.parse(event.data);
			updateJobRowUI(jobData);
		} catch (e) {
			console.error('SSE Feed: Error parsing export_job_update data:', e, event.data);
		}
	});

	globalExportSSE.onerror = function(event) {
		console.error('SSE Feed: Connection error.', event);
		if (globalExportSSE && globalExportSSE.readyState === EventSource.CLOSED) {
			console.log('SSE Feed: Connection was closed. Nullifying globalExportSSE.');
			globalExportSSE = null; // Allow re-initialization
		}
	};
}

jQuery( function ( $ ) {

	const exportForm = $( '#pb-export-form' );

	if (!exportForm.length) {
		return;
	}

	initializeGlobalExportFeed();

	// Pinning functionality (remains largely unchanged for now, but ensure selectors are still valid)
	let pins = _pb_export_pins_inventory; // initial value from inline script


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

	// Function for export-ui.js to potentially call if the SSE connection needs to be re-ensured after AJAX.
	// Or, export-ui.js could just rely on the initial load's connection.
	window.PB_EnsureGlobalExportFeed = function() {
		// console.log('PB_EnsureGlobalExportFeed called.');
		initializeGlobalExportFeed();
	};

} );
