/* global PB_ExportToken */
/* global _pb_export_formats_map */
/* global _pb_export_pins_inventory */

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

	// Update progress bar and percentage
	if ( progressBar && progressText ) {
		progressBar.classList.remove( 'pb-sse-progressbar-success', 'pb-sse-progressbar-error' );
		progressBar.style.width = ( jobData.progress_percentage || 0 ) + '%';
		progressBar.setAttribute( 'aria-valuenow', jobData.progress_percentage || 0 );
		progressText.textContent = ( jobData.progress_percentage || 0 ) + '%';
	}

	// Update status message
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
				jobStatus.textContent = ( PB_ExportToken.text && PB_ExportToken.text.completed ) || 'Completed';
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

			// Add visual indicator
			const label = exportForm.querySelector( `label[for="${ formatSlug }"]` );
			if ( label ) {
				label.classList.add( 'export-format-active' );
				label.title = 'This export is currently running';
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
                    <span class="job-status">${ jobData.progress_message || 'Starting...' }</span>
                </div>
                <div class="export-progress">
                    <div class="progress-bar-container">
                        <div class="progress-bar" style="width: ${ jobData.progress_percentage || 0 }%" aria-valuenow="${ jobData.progress_percentage || 0 }"></div>
                    </div>
                    <span class="progress-text">${ jobData.progress_percentage || 0 }%</span>
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
	if ( activeJobs.size === 0 && ( completedJobs.size > 0 || failedJobs.size > 0 ) ) {
		if ( window.PB_Export_ReloadOnComplete ) {
			setTimeout( () => {
				window.location.reload();
			}, 100 ); // Optional delay
		}
	}
}

/**
 * Initializes the single global SSE stream for all user export jobs.
 */
function initializeGlobalExportFeed() {

	if ( ! PB_ExportToken || ! PB_ExportToken.ajaxurl || ! PB_ExportToken.userExportFeedNonce || ! PB_ExportToken.bookId ) {
		console.error( 'SSE Feed: Missing required PB_ExportToken properties (ajaxurl, userExportFeedNonce, bookId).' );
		return;
	}

	globalExportSSENonce = PB_ExportToken.userExportFeedNonce;
	globalExportSSEBookId = PB_ExportToken.bookId;

	if ( globalExportSSE && globalExportSSE.readyState !== EventSource.CLOSED ) {
		return;
	}

	const sseUrl = `${ PB_ExportToken.ajaxurl }?action=pb_sse_exports&_wpnonce=${ globalExportSSENonce }&book_id=${ globalExportSSEBookId }`;
	globalExportSSE = new EventSource( sseUrl );

	globalExportSSE.addEventListener( 'export_job_updates', function ( event ) {
		try {
			const jobsData = JSON.parse( event.data );
			jobsData.forEach( job => {
				updateJobRowUI( job );
				// Pre-select format checkboxes
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
}

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

	/**
	 *
	 */
	window.PB_EnsureGlobalExportFeed = function () {
		initializeGlobalExportFeed();
	};

} );
