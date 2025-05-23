document.addEventListener('DOMContentLoaded', function () {
    console.log('Export UI script loaded');
    const exportForm = document.getElementById('pb-export-form');
    const exportButton = document.getElementById('pb-export-button');
    // Assuming the table is the first one with class 'wp-list-table' on the page.
    // If there are multiple, a more specific selector might be needed.
    const exportsTable = document.querySelector('table.wp-list-table');

    console.log('exportForm', exportForm);
    console.log('exportButton', exportButton);
    console.log('exportsTable', exportsTable);

    if (exportForm && exportButton && exportsTable) {
        const tableBody = exportsTable.querySelector('tbody');

        console.log('tableBody', tableBody);

        exportForm.addEventListener('submit', async function (event) {
            event.preventDefault();

            const clientFormData = new FormData(exportForm);
            const selectedFormats = [];
            const formatDisplayNames = {};

            for (const [key, value] of clientFormData.entries()) {
                if (key.startsWith('export_formats[')) {
                    const formatKey = key.substring(key.indexOf('[') + 1, key.indexOf(']'));
                    selectedFormats.push(formatKey);
                    const labelElement = document.querySelector(`label[for='${formatKey}']`);
                    formatDisplayNames[formatKey] = labelElement ? labelElement.textContent.trim() : formatKey.toUpperCase();
                }
            }

            if (selectedFormats.length === 0) {
                console.warn('No export formats selected.');
                if (window.PB_ExportToken && PB_ExportToken.text && PB_ExportToken.text.select_format) {
                    alert(PB_ExportToken.text.select_format); // Simple alert for now
                }
                return;
            }

            tableBody.querySelectorAll('tr.export-job-in-progress').forEach(row => row.remove());

            selectedFormats.forEach(formatKey => {
                const displayName = formatDisplayNames[formatKey];
                const jobRow = document.createElement('tr');
                jobRow.classList.add('export-job-row', 'export-job-in-progress');
                jobRow.setAttribute('data-format', formatKey);

                const cbCell = document.createElement('td');
                cbCell.classList.add('column-cb');

                const fileCell = document.createElement('td');
                fileCell.classList.add('column-file', 'has-row-actions');
                fileCell.innerHTML = `<div class="export-file">
                                        <div class="export-file-icon large ${formatKey}"></div>
                                        <div class="export-file-name">${displayName} - <i>Waiting to queue...</i></div>
                                      </div>`;

                const formatCell = document.createElement('td');
                formatCell.classList.add('column-format');
                formatCell.textContent = displayName;

                const sizeCell = document.createElement('td');
                sizeCell.classList.add('column-size');
                sizeCell.textContent = '...';

                const pinCell = document.createElement('td');
                pinCell.classList.add('column-pin');

                const exportedCell = document.createElement('td');
                exportedCell.classList.add('column-exported', 'export-progress-cell');
                
                const progressBarContainer = document.createElement('div');
                progressBarContainer.classList.add('progress-bar-container');

                const progressBar = document.createElement('div');
                progressBar.classList.add('progress-bar');
                progressBar.style.width = '0%';

                const progressText = document.createElement('span');
                progressText.classList.add('progress-text');
                progressText.textContent = '0%';

                progressBarContainer.appendChild(progressBar);
                progressBarContainer.appendChild(progressText);
                exportedCell.appendChild(progressBarContainer);

                jobRow.appendChild(cbCell);
                jobRow.appendChild(fileCell);
                jobRow.appendChild(formatCell);
                jobRow.appendChild(sizeCell);
                jobRow.appendChild(pinCell);
                jobRow.appendChild(exportedCell);

                if (tableBody.firstChild) {
                    tableBody.insertBefore(jobRow, tableBody.firstChild);
                } else {
                    tableBody.appendChild(jobRow);
                }
            });

            // Disable button during AJAX
            exportButton.disabled = true;
            const originalButtonText = exportButton.textContent;
            if (window.PB_ExportToken && PB_ExportToken.text && PB_ExportToken.text.exporting) {
                exportButton.textContent = PB_ExportToken.text.exporting;
            }

            // Prepare FormData for the AJAX request
            const ajaxFormData = new FormData();
            ajaxFormData.append('action', 'pb_export_book');
            if (window.PB_ExportToken && PB_ExportToken.nonce) {
                ajaxFormData.append('pb_export_nonce', PB_ExportToken.nonce);
            } else {
                console.error('PB_ExportToken.nonce is not available. Cannot submit export job.');
                exportButton.disabled = false;
                exportButton.textContent = originalButtonText;
                return;
            }

            selectedFormats.forEach(format => {
                ajaxFormData.append(`export_formats[${format}]`, '1');
            });
            
            // If there are other general export options in the form, append them too.
            // Example: clientFormData.getAll('export_options[some_option]').forEach(val => ajaxFormData.append('export_options[some_option]', val));


            try {
                const response = await fetch(PB_ExportToken.ajaxurl, {
                    method: 'POST',
                    body: ajaxFormData,
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const responseData = await response.json();

                if (responseData.success && responseData.data && responseData.data.results) {
                    const jobsToListen = [];
                    responseData.data.results.forEach(result => {
                        const row = tableBody.querySelector(`tr.export-job-in-progress[data-format='${result.module_slug}']`);
                        if (row) {
                            if (result.event_type === 'job_queued') {
                                row.setAttribute('data-job-id', result.job_id);
                                row.setAttribute('data-sse-nonce', result.sse_nonce);
                                const statusElement = row.querySelector('.column-file .export-file-name i');
                                if(statusElement) statusElement.textContent = 'Queued, waiting for progress...';
                                jobsToListen.push({
                                    book_id: result.book_id, // Ensure book_id is part of the result
                                    job_id: result.job_id,
                                    module_slug: result.module_slug,
                                    sse_nonce: result.sse_nonce,
                                    format_name: result.format_name || result.module_slug // Pass format_name for display
                                });
                            } else if (result.event_type === 'job_queue_failed') {
                                const statusElement = row.querySelector('.column-file .export-file-name i');
                                if(statusElement) statusElement.textContent = `Queueing Failed: ${result.message}`;
                                row.classList.add('export-job-failed');
                                const progressBar = row.querySelector('.progress-bar');
                                const progressText = row.querySelector('.progress-text');
                                if(progressBar) progressBar.style.width = '100%'; progressBar.classList.add('pb-sse-progressbar-error');
                                if(progressText) progressText.textContent = 'Error';
                            } // Handle other event_types like sync_export_skipped if necessary
                        }
                    });

                    if (jobsToListen.length > 0 && typeof window.PB_Export_AttachSSEListeners === 'function') {
                        window.PB_Export_AttachSSEListeners(jobsToListen);
                    } else if (jobsToListen.length > 0) {
                        console.error('PB_Export_AttachSSEListeners function not found on window object.');
                    }

                    if(responseData.data.message && !jobsToListen.length) { // General message if no jobs queued
                         // displayNotice('info', responseData.data.message); // Using alert for now
                         alert(responseData.data.message);
                    }

                } else if (!responseData.success && responseData.data && responseData.data.message) {
                    console.error('Export job submission failed:', responseData.data.message);
                    alert(`Error: ${responseData.data.message}`); // Simple alert
                } else {
                    console.error('Export job submission failed with unknown error structure:', responseData);
                    alert('An unknown error occurred while submitting export jobs.');
                }

            } catch (error) {
                console.error('Fetch error for export job submission:', error);
                alert('Failed to submit export jobs. Check console for details.');
            } finally {
                exportButton.disabled = false;
                exportButton.textContent = originalButtonText;
            }
        });
    }
}); 