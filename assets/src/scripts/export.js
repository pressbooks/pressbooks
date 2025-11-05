/* global PB_ExportToken */
/* global _pb_export_formats_map */
/* global _pb_export_pins_inventory */
import "../styles/export.scss"

// Stores the single global EventSource instance for all user jobs.
let globalExportSSE = null;
let globalExportSSENonce = null;
let globalExportSSEBookId = null;
let activeJobs = new Set();
let completedJobs = new Set();
let failedJobs = new Set();

/**
 * Updates the UI for a specific job row based on SSE data.
 *
 * @param {object} jobData - Data received from SSE for a job.
 */
function updateJobRowUI( jobData ) {
	let tableRow = document.querySelector( `tr.export-job-row[data-job-id='${ jobData.job_id }']` );

	if ( ! tableRow ) {
		tableRow = createJobRow( jobData );
		const tableBody = document.querySelector( 'table.wp-list-table' );
		if ( tableBody ) {
			tableBody.prepend( tableRow );
		}
	}

	const progressBar = tableRow.querySelector( '.progress-bar' );
	const progressText = tableRow.querySelector( '.progress-text' );
	const jobStatus = tableRow.querySelector( '.job-status' );

	if ( progressBar && progressText ) {
		progressBar.classList.remove( 'pb-sse-progressbar-success', 'pb-sse-progressbar-error' );
		progressBar.style.width = ( jobData.progress_percentage || 0 ) + '%';
		progressBar.setAttribute( 'aria-valuenow', jobData.progress_percentage || 0 );
		progressText.textContent = ( jobData.progress_percentage || 0 ) + '%';
	}

	if ( jobStatus ) {
		jobStatus.textContent = jobData.progress_message || 'Processing...';
	}

	if ( jobData.status === 'completed' ) {
		if ( progressBar ) {
			progressBar.classList.add( 'pb-sse-progressbar-success' );
			progressBar.style.width = '100%';
		}
		if ( progressText ) {
			progressText.textContent = '100%';
		}
		if ( jobStatus ) {
			if ( jobData.file_name && jobData.download_url ) {
				jobStatus.innerHTML = `<a href="${ jobData.download_url }" target="_blank" class="download-link">${ jobData.file_name }</a>`;
			} else {
				jobStatus.textContent = PB_ExportToken?.text?.completed  || 'Completed';
			}
		}

		tableRow.classList.remove( 'export-job-in-progress', 'export-job-failed' );
		tableRow.classList.add( 'export-job-completed' );
		completedJobs.add( jobData.job_id );
		activeJobs.delete( jobData.job_id );
	} else if ( jobData.status === 'failed' ) {
		if ( progressBar ) {
			progressBar.classList.add( 'pb-sse-progressbar-error' );
			progressBar.style.width = '100%';
		}
		if ( progressText ) {
			progressText.textContent = 'Error';
		}
		if ( jobStatus ) {
			jobStatus.textContent = `Failed: ${ jobData.error_message || jobData.progress_message || 'Unknown error' }`;
			jobStatus.style.color = 'red';
		}

		tableRow.classList.remove( 'export-job-in-progress', 'export-job-completed' );
		tableRow.classList.add( 'export-job-failed' );

		activeJobs.delete( jobData.job_id );
		failedJobs.add( jobData.job_id );
	} else {
		// In progress
		tableRow.classList.remove( 'export-job-completed', 'export-job-failed' );
		tableRow.classList.add( 'export-job-in-progress' );
		if ( jobStatus ) {
			jobStatus.style.color = '';
		}
		activeJobs.add( jobData.job_id );
	}
	checkAllJobsComplete();
}

/**
 *
 * @param activeFormats
 */
function preselectActiveFormats( activeFormats ) {
	const exportForm = document.getElementById( 'pb-export-form' );
	if ( ! exportForm ) return;

	activeFormats.forEach( formatSlug => {
		const checkbox = exportForm.querySelector( `input[name="export_formats[${ formatSlug }]"]` );
		if ( checkbox ) {
			checkbox.checked = true;
			checkbox.disabled = true; // Prevent unchecking while job is running

			const label = exportForm.querySelector( `label[for="${ formatSlug }"]` );
			if ( label ) {
				label.classList.add( 'export-format-active' );
				label.title = PB_ExportToken?.text?.job_running || 'This export is currently running';
			}
		}
	} );
}

/**
 * Creates a new job row if it doesn't exist (for handling page refreshes)
 *
 * @param {object} jobData - Job data to create row for
 * @returns {HTMLElement} - The created table row element
 */
function createJobRow( jobData ) {
	const row = document.createElement( 'tr' );
	row.className = 'export-job-row export-job-in-progress';
	row.setAttribute( 'data-job-id', jobData.job_id );

	row.innerHTML = `
        <td colspan="6" class="export-job-cell">
            <div class="export-job-content">
                <div class="export-name">
                    <strong class="format-name">${ jobData.format_name || jobData.module_slug }</strong>
                    <span class="job-status">${ jobData.progress_message || PB_ExportToken?.text?.start_export }</span>
                </div>
                <div class="export-progress">
                    <div class="progress-bar-container">
                        <div class="progress-bar" style="width: ${ jobData.progress_percentage || 0 }%" aria-valuenow="${ jobData.progress_percentage || 0 }"></div>
                    </div>
                    <span class="progress-text">${ jobData.progress_percentage || 0 }%</span>
                </div>
                <div class="export-actions">
               	  <button class="button button-secondary cancel-job" data-job-id="${ jobData.job_id }" type="button">${PB_ExportToken?.text?.cancel_button || 'Cancel'}</button>
				</div>
            </div>
        </td>
    `;

	return row;
}

/**
 * Checks if all initially queued jobs have completed or failed.
 */
function checkAllJobsComplete() {
	window.PB_Export_ReloadOnComplete = true;
	if ( activeJobs.size === 0 && ( completedJobs.size > 0 ) ) {
		if ( window.PB_Export_ReloadOnComplete ) {
			setTimeout( () => {
				window.location.reload();
			}, 100 );
		}
	}
}

/**
 * Initializes the single global SSE stream for all user export jobs.
 */
function initializeGlobalExportFeed() {

	if ( ! PB_ExportToken || ! PB_ExportToken.ajaxUrl || ! PB_ExportToken.userExportFeedNonce || ! PB_ExportToken.bookId ) {
		return;
	}

	globalExportSSENonce = PB_ExportToken.userExportFeedNonce;
	globalExportSSEBookId = PB_ExportToken.bookId;

	if ( globalExportSSE && globalExportSSE.readyState !== EventSource.CLOSED ) {
		return;
	}

	const sseUrl = `${ PB_ExportToken.ajaxUrl }?action=pb_sse_exports&_wpnonce=${ globalExportSSENonce }&book_id=${ globalExportSSEBookId }`;
	globalExportSSE = new EventSource( sseUrl );

	globalExportSSE.addEventListener( 'export_job_updates', function ( event ) {
		try {
			const jobsData = JSON.parse( event.data );
			jobsData.forEach( job => {
				updateJobRowUI( job );
				const activeFormats = jobsData.map( job => job.module_slug );
				preselectActiveFormats( [ ...new Set( activeFormats ) ] );
			} );
		} catch ( e ) {

		}
	} );

	/**
	 *
	 * @param event
	 */
	globalExportSSE.onerror = function ( event ) {
		if ( globalExportSSE && globalExportSSE.readyState === EventSource.CLOSED ) {
			globalExportSSE = null;
		}
	};
	// Trigger cancel button functionality
	document.querySelector('table.wp-list-table').addEventListener('click', function(event) {
		const button = event.target.closest('button.cancel-job');
		if (!button) return;

		const jobId = button.getAttribute('data-job-id');
		if (!jobId) return;

		if (confirm(PB_ExportToken?.text?.cancel_confirmation || 'Are you sure you want to cancel this export job?')) {
			fetch(PB_ExportToken.ajaxUrl, {
			    method: 'POST',
			    headers: {
			        'Content-Type': 'application/x-www-form-urlencoded',
			    },
			    body: new URLSearchParams({
			        action: 'pb_cancel_job',
			        job_id: jobId,
			        pb_cancel_nonce: PB_ExportToken.nonce,
			    })
			})
			.then(response => {
			    if (!response.ok) throw new Error('Network response was not ok');
			    window.location.reload();
			})
			.catch(() => {
			    alert(PB_ExportToken?.text?.cancel_failed || 'Failed to cancel export job.');
			});
		}
	});

}

document.addEventListener('DOMContentLoaded', function () {
	const exportForm = document.getElementById('pb-export-form');
	const exportButton = document.getElementById('pb-export-button');

	if (exportForm && exportButton) {
		exportForm.addEventListener('submit', async function (event) {
			event.preventDefault();

			// Disable button and show loading state
			exportButton.disabled = true;
			const originalButtonText = exportButton.textContent;
			exportButton.textContent = PB_ExportToken?.text?.exporting || 'Exporting...';

			try {
				// Get selected formats
				const formData = new FormData(exportForm);
				const selectedFormats = getSelectedFormats(formData);

				if (selectedFormats.length === 0) {
					showError(PB_ExportToken?.text?.select_format || 'Please select at least one export format.');
					return;
				}

				const response = await submitExportJobs(selectedFormats);

				if (response.success && response.data?.results) {
					handleSubmissionSuccess(response.data);
				} else {
					handleSubmissionError(response);
				}

			} catch (error) {
				showError(PB_ExportToken?.text?.error_jobs || 'Error submitting export jobs:' + error.message);
			} finally {
				exportButton.disabled = false;
				exportButton.textContent = originalButtonText;
			}
		});
	}

	/**
	 * Extract selected export formats from form data
	 */
	function getSelectedFormats(formData) {
		const selectedFormats = [];

		for (const [key, value] of formData.entries()) {
			if (key.startsWith('export_formats[') && value) {
				const formatKey = key.substring(key.indexOf('[') + 1, key.indexOf(']'));
				selectedFormats.push(formatKey);
			}
		}

		return selectedFormats;
	}

	/**
	 * Submit export jobs to the server
	 */
	async function submitExportJobs(selectedFormats) {
		const serverFormData = new FormData();


		selectedFormats.forEach(format => {
			serverFormData.append(`export_formats[${format}]`, '1');
		});

		serverFormData.append('action', 'pb_export_book');
		serverFormData.append('pb_export_nonce', PB_ExportToken.nonce);

		const response = await fetch(PB_ExportToken.ajaxUrl, {
			method: 'POST',
			body: serverFormData
		});

		if (!response.ok) {
			throw new Error(`HTTP error! status: ${response.status}`);
		}

		return await response.json();
	}

	/**
	 * Handle successful job submission
	 */
	function handleSubmissionSuccess(data) {
		// Store reload preference
		if (data.reload_on_complete === true) {
			window.PB_Export_ReloadOnComplete = true;
		}

		data.results.forEach(jobResult => {
			if (jobResult.event_type === 'job_queued') {
			} else if (jobResult.event_type === 'job_queue_failed') {
				console.error(`Failed to queue job: ${jobResult.module_slug} - ${jobResult.message}`);
			}
		});

		if (typeof window.PB_EnsureGlobalExportFeed === 'function') {
			window.PB_EnsureGlobalExportFeed();
		}

		showSuccess((PB_ExportToken?.text?.jobs_submitted) || 'Export job(s) successfully added to the queue. Progress updates will appear below until the export process is completed. In the meantime, you can safely navigate away from this page.');
	}

	/**
	 * Handle submission errors
	 */
	function handleSubmissionError(response) {
		const errorMessage = response.data?.message || response.message || 'Failed to submit export jobs.';
		showError(errorMessage);
	}

	/**
	 * Show error message to user
	 */
	function showError(message) {
		alert(message);
	}

	/**
	 * Show success message to user
	 */
	function showSuccess(message) {
		createNotification(message, 'success');
	}

	/**
	 * Create an accessible notification banner
	 */
	function createNotification(message, type = 'info') {
		const notification = document.createElement('div');
		notification.className = `notice notice-${type} is-dismissible`;
		notification.setAttribute('role', 'alert');
		notification.setAttribute('aria-live', 'polite');
		notification.innerHTML = `
        <p>${message}</p>
        <button type="button" class="notice-dismiss"
                aria-label="${PB_ExportToken?.text?.job_notice_dismissal || 'Dismiss this notice'}"
                onclick="this.parentElement.remove()">
            <span class="screen-reader-text">${PB_ExportToken?.text?.job_notice_dismissal || 'Dismiss this notice'}.</span>
        </button>
    `;

		const insertAfter = exportForm.parentElement || document.body;
		insertAfter.insertBefore(notification, insertAfter.firstChild);

		if (type === 'error') {
			notification.focus();
			notification.setAttribute('tabindex', '-1');
		}
	}
});


jQuery( function ( $ ) {

	const exportForm = $( '#pb-export-form' );

	if ( ! exportForm.length ) {
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

		$.post( PB_ExportToken.ajaxUrl, {
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
			alert( PB_ExportToken?.text?.maximum_files_warning );
			error = true;
		} else {
			for ( let k in types ) {
				if ( types.hasOwnProperty( k ) ) {
					if ( types[k] > 3 ) {
						$( this ).prop( 'checked', false );
						delete pins[postId];
						alert( PB_ExportToken?.text?.maximum_file_type_warning );
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

	/**
	 *
	 */
	window.PB_EnsureGlobalExportFeed = function () {
		initializeGlobalExportFeed();
	};

} );
