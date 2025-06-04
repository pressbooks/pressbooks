document.addEventListener('DOMContentLoaded', function () {
	console.log('Export UI script loaded');
	const exportForm = document.getElementById('pb-export-form');
	const exportButton = document.getElementById('pb-export-button');

	if (exportForm && exportButton) {
		exportForm.addEventListener('submit', async function (event) {
			event.preventDefault();

			// Disable button and show loading state
			exportButton.disabled = true;
			const originalButtonText = exportButton.textContent;
			exportButton.textContent = (PB_ExportToken?.text?.exporting) || 'Exporting...';

			try {
				// Get selected formats
				const formData = new FormData(exportForm);
				const selectedFormats = getSelectedFormats(formData);

				if (selectedFormats.length === 0) {
					showError((PB_ExportToken?.text?.select_format) || 'Please select at least one export format.');
					return;
				}

				const response = await submitExportJobs(selectedFormats);

				if (response.success && response.data?.results) {
					handleSubmissionSuccess(response.data);
				} else {
					handleSubmissionError(response);
				}

			} catch (error) {
				console.error('Error submitting export jobs:', error);
				showError('Error submitting export jobs: ' + error.message);
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

		const response = await fetch(PB_ExportToken.ajaxurl, {
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

		// Log job queue results
		data.results.forEach(jobResult => {
			if (jobResult.event_type === 'job_queued') {
				console.log(`Job queued: ${jobResult.module_slug} (ID: ${jobResult.job_id})`);
			} else if (jobResult.event_type === 'job_queue_failed') {
				console.error(`Failed to queue job: ${jobResult.module_slug} - ${jobResult.message}`);
			}
		});

		if (typeof window.PB_EnsureGlobalExportFeed === 'function') {
			window.PB_EnsureGlobalExportFeed();
		}

		// Show success message
		showSuccess((PB_ExportToken?.text?.jobs_submitted) || 'Export jobs submitted successfully. Watch for progress updates below.');
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
                aria-label="Dismiss this notice"
                onclick="this.parentElement.remove()">
            <span class="screen-reader-text">Dismiss this notice.</span>
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
