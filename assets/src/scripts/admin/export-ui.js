document.addEventListener('DOMContentLoaded', function () {
    console.log('Export UI script loaded');
    const exportForm = document.getElementById('pb-export-form');
    const exportButton = document.getElementById('pb-export-button');
    const exportsTable = document.querySelector('table.wp-list-table'); // Standard WP table class

    if (exportForm && exportButton && exportsTable) {
        const tableBody = exportsTable.querySelector('tbody');


        exportForm.addEventListener('submit', async function (event) {
            event.preventDefault();
            exportButton.disabled = true;
            exportButton.textContent = (PB_ExportToken && PB_ExportToken.text && PB_ExportToken.text.exporting) || 'Exporting...';

            const clientFormData = new FormData(exportForm);
            const selectedFormats = [];
            const formatDisplayNames = {};

            for (const [key, value] of clientFormData.entries()) {
                if (key.startsWith('export_formats[')) {
                    const formatKey = key.substring(key.indexOf('[') + 1, key.indexOf(']'));
                    selectedFormats.push(formatKey);
                    const labelElement = exportForm.querySelector(`label[for='${formatKey}']`);
                    formatDisplayNames[formatKey] = labelElement ? labelElement.textContent.trim() : formatKey;

                    if (tableBody) {
                        const newRow = document.createElement('tr');
                        // Add a general class and a format-specific class for easier targeting if needed
                        newRow.className = `export-job-row export-job-in-progress export-format-${formatKey}`;
                        newRow.setAttribute('data-format', formatKey);

                        const cbCell = document.createElement('td');
                        cbCell.className = 'column-cb check-column';
                        newRow.appendChild(cbCell);

                        const fileCell = document.createElement('td');
                        fileCell.className = 'column-file export-progress-cell';
                        fileCell.innerHTML = `
                            <div class="export-file-name">
                                <span class="file-name-text"><strong>${formatDisplayNames[formatKey]}</strong></span>: <i>Waiting for server...</i>
                            </div>
                            <div class="progress-bar-container">
                                <div class="progress-bar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                <div class="progress-text">0%</div>
                            </div>
                        `;
                        newRow.appendChild(fileCell);

                        ['format', 'size', 'pin','date'].forEach(colName => {
                            const cell = document.createElement('td');
                            cell.className = `column-${colName}`;
                            newRow.appendChild(cell);
                        });
                        tableBody.prepend(newRow);
                    }
                }
            }

            if (selectedFormats.length === 0) {
                alert((PB_ExportToken && PB_ExportToken.text && PB_ExportToken.text.select_format) || 'Please select at least one export format.');
                exportButton.disabled = false;
                exportButton.textContent = (PB_ExportToken && PB_ExportToken.text && PB_ExportToken.text.exportBookButton) || 'Export Your Book';
                return;
            }

            const serverFormData = new FormData();
            selectedFormats.forEach(format => {
                serverFormData.append(`export_formats[${format}]`, '1');
            });
            serverFormData.append('action', 'pb_export_book');
            serverFormData.append('pb_export_nonce', PB_ExportToken.nonce);

            try {
                const response = await fetch(PB_ExportToken.ajaxurl, {
                    method: 'POST',
                    body: serverFormData
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const data = await response.json();
                window.PB_Export_ReloadOnComplete = data.reload_on_complete === true;

                if (data.success === true && data.data && data.data.results) {
                    data.data.results.forEach(jobResult => {
                        const rowToUpdate = tableBody.querySelector(`tr.export-format-${jobResult.module_slug}:not([data-job-id])`);
                        if (rowToUpdate) {
                            if (jobResult.event_type === 'job_queued' && jobResult.job_id) {
                                rowToUpdate.setAttribute('data-job-id', jobResult.job_id);
                                const statusElement = rowToUpdate.querySelector('.column-file .export-file-name i');
                                if(statusElement) statusElement.textContent = 'Queued, waiting for progress...';
                            } else if (jobResult.event_type === 'job_queue_failed') {
                                rowToUpdate.classList.remove('export-job-in-progress');
                                rowToUpdate.classList.add('export-job-failed');
                                const statusElement = rowToUpdate.querySelector('.column-file .export-file-name i');
                                const fileNameTextElement = rowToUpdate.querySelector('.column-file .export-file-name span.file-name-text strong');
                                if(statusElement && fileNameTextElement) {
                                    statusElement.textContent = `Failed: ${jobResult.message || 'Could not queue job.'}`;
                                    statusElement.style.color = 'red';
                                }
                                const progressBar = rowToUpdate.querySelector('.progress-bar');
                                const progressText = rowToUpdate.querySelector('.progress-text');
                                if(progressBar) progressBar.style.width = '100%'; progressBar.classList.add('pb-sse-progressbar-error');
                                if(progressText) progressText.textContent = 'Error';
                            }
                        }
                    });

                    if (typeof window.PB_EnsureGlobalExportFeed === 'function') {
                        window.PB_EnsureGlobalExportFeed();
                    }

                } else { // Handles data.success !== true OR if data.data.results is missing/empty
                    console.error('Failed to submit export jobs or bad response:', data.data && data.data.message ? data.data.message : (data.message || data));
                    // Revert UI for all provisionally added rows
                    selectedFormats.forEach(formatKey => {
                        const rowToRemove = tableBody.querySelector(`tr.export-format-${formatKey}:not([data-job-id])`);
                        if(rowToRemove) rowToRemove.remove();
                    });
                    alert((data.data && data.data.message ? data.data.message : (data.message || 'Failed to submit export jobs. Check console for details.')));
                }

            } catch (error) {
                console.error('Error submitting export jobs:', error);
                alert('Error submitting export jobs: ' + error.message);
                 selectedFormats.forEach(formatKey => {
                        const rowToRemove = tableBody.querySelector(`tr.export-format-${formatKey}:not([data-job-id])`);
                        if(rowToRemove) rowToRemove.remove();
                    });
            } finally {
                exportButton.disabled = false;
                exportButton.textContent = (PB_ExportToken && PB_ExportToken.text && PB_ExportToken.text.exportBookButton) || 'Export Your Book';
            }
        });
    }
});
