import AjaxRequest from '@typo3/core/ajax/ajax-request.js';

/**
 * Baut die HTML-Vorschau-Tabelle aus den Validierungsdaten des Servers.
 * @param {object} data - Das JSON-Objekt vom Server.
 * @param {HTMLElement} container - Das DOM-Element, in das die Tabelle eingefügt wird.
 */
function renderPreviewTable(data, container) {
    if (!data || !Array.isArray(data.rows) || data.rows.length === 0) {
        container.innerHTML = '<div class="alert alert-warning">Keine importierbaren Daten gefunden.</div>';
        return;
    }

    const resultCacheId = data.resultCacheId;

    const mappingForm = document.querySelector('#mapping-form');

    const fileHeaders = data.headers;
    let tableHtml = `
        <form id="execute-import-form">
            <input type="hidden" name="resultCacheId" value="${resultCacheId}">
            <div class="card shadow-sm">
                <div class="card-header"><strong>Schritt 3: Vorschau und Bestätigung</strong></div>
                <div class="card-body">
                    <p>Bitte prüfen Sie die validierten Daten. Nur angehakte Zeilen werden importiert.</p>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped table-hover">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width: 50px;">
                                        <div class="form-check d-flex justify-content-center">
                                            <input type="checkbox" class="form-check-input" id="check-all-valid" title="Alle validen Zeilen auswählen/abwählen">
                                        </div>
                                    </th>
                                    <th style="width: 200px;">Status</th>
                                    ${fileHeaders.map(header => `<th>${header}</th>`).join('')}
                                </tr>
                            </thead>
                            <tbody>`;

    data.rows.forEach((row, index) => {
        const statusClasses = { ok: 'table-success', duplicate: 'table-warning', error: 'table-danger' };
        const trClass = statusClasses[row.status] || '';
        const isChecked = row.status === 'ok' ? 'checked' : '';
        const isDisabled = row.status === 'error' ? 'disabled' : '';

        // Die Hauptzeile für jeden Datensatz
        tableHtml += `<tr class="${trClass}" data-row-index="${index}">
                        <td class="text-center">
                            <div class="form-check d-flex justify-content-center">
                                <input type="checkbox" name="importRows[]" value="${index}" class="form-check-input import-checkbox"
                                       title="${row.status === 'duplicate' ? 'Zum Aktualisieren/Zusammenführen auswählen' : 'Zum Importieren auswählen'}"
                                       ${isChecked} ${isDisabled}>
                            </div>
                        </td>
                        <td>${row.message}</td>
                        ${fileHeaders.map(header => `<td>${row.data[header] || ''}</td>`).join('')}
                    </tr>`;

        // Wenn es ein Duplikat mit Unterschieden ist, fügen wir eine ZWEITE, detaillierte Zeile hinzu
        if (row.status === 'duplicate' && row.diff && Object.keys(row.diff).length > 0) {
            tableHtml += `<tr class="${trClass} import-details-row" data-details-for-row="${index}">
                            <td></td>
                            <td colspan="${fileHeaders.length + 1}" class="p-3">
                                <div><strong>Gefundene Daten-Unterschiede (optional zum Aktualisieren auswählen):</strong></div>
                                <div class="ps-4 mt-2 field-update-container" data-fields-for-row="${index}">`;

            for (const field in row.diff) {
                const csvValue = row.diff[field].csv;
                const dbValue = row.diff[field].db;
                tableHtml += `
                    <div class="form-check small my-1">
                        <input type="checkbox" name="updateFields[${index}][${field}]" value="1" id="update-field-${index}-${field}" class="form-check-input update-field-checkbox" disabled>
                        <label for="update-field-${index}-${field}" class="form-check-label">
                            Feld <strong>${field}</strong> aktualisieren
                            <br>
                            <span class="text-muted">Neuer Wert:</span> <em class="text-primary">"${csvValue}"</em>
                            <span class="text-muted">(Bisher: "<em>${dbValue}</em>")</span>
                        </label>
                    </div>`;
            }

            tableHtml += `</div></td></tr>`;
        }
    });

    tableHtml += `</tbody></table></div></div>
                <div class="card-footer d-flex justify-content-between">
                        <button type="button" class="btn btn-light" id="restart-import-btn">
                            <i class="fa-solid fa-arrow-left"></i> Abbrechen und neu starten
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-database"></i> Auswahl importieren
                        </button>
                </div>
            </div>
        </form>`;
    container.innerHTML = tableHtml;
    
    // "Alle auswählen" toggelt die Haupt-Checkboxen
    document.getElementById('check-all-valid').addEventListener('change', (e) => {
        document.querySelectorAll('.import-checkbox:not(:disabled)').forEach(cb => {
            cb.checked = e.target.checked;
            cb.dispatchEvent(new Event('change')); // Löst das Change-Event manuell aus
        });
    });

    // Jede Haupt-Checkbox steuert ihre "Update"-Checkbox
    document.querySelectorAll('.import-checkbox').forEach(importCheckbox => {
        importCheckbox.addEventListener('change', () => {
            const rowIndex = importCheckbox.value;
            const fieldCheckboxes = document.querySelectorAll(`[data-fields-for-row="${rowIndex}"] .update-field-checkbox`);
            
            // Wenn es für diese Zeile überhaupt Detail-Checkboxen gibt
            if (fieldCheckboxes.length > 0) {
                const isEnabled = importCheckbox.checked;
                
                fieldCheckboxes.forEach(fieldCheckbox => {
                    fieldCheckbox.disabled = !isEnabled;
                    // Wenn die Haupt-Checkbox deaktiviert wird, auch die Unter-Checkboxen abwählen
                    if (!isEnabled) {
                        fieldCheckbox.checked = false;
                    }
                });
            }
        });
    });

    document.getElementById('restart-import-btn').addEventListener('click', () => window.location.reload());

    const executeForm = document.getElementById('execute-import-form');
    const executeUrl = TYPO3.settings.ajaxUrls.publicrelations_contactimportexecute;
    const previewContainer = document.getElementById('step-3-validation-container');

    if (executeForm && executeUrl) {
        executeForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            
            previewContainer.innerHTML = `
                <div class="text-center p-5">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-2">Import wird ausgeführt, bitte warten...</p>
                </div>`;
                
            const formData = new FormData(executeForm);

            try {
                const response = await new AjaxRequest(executeUrl).post(formData);
                const data = await response.resolve();
                if (data.success) {
                    previewContainer.innerHTML = `<div class="alert alert-success">${data.message}</div>`;
                } else {
                    previewContainer.innerHTML = `<div class="alert alert-danger">Fehler: ${data.message}</div>`;
                }
            } catch (error) {
                previewContainer.innerHTML = '<div class="alert alert-danger">Ein schwerwiegender Fehler ist aufgetreten.</div>';
                console.error('Execute Import AJAX error:', error);
            }
        });
    }
}


export function initializeImportMappingForm() {
    const mappingForm = document.querySelector('#mapping-form');
    if (!mappingForm) {
        return;
    }

    const validationUrl = TYPO3.settings.ajaxUrls.publicrelations_contactimportvalidation;
    const previewContainer = document.getElementById('step-3-validation-container');

    if (!validationUrl) {
        console.error('Import Handler: AJAX URL (publicrelations_contactimportvalidation) ist nicht definiert.');
        return;
    }

    mappingForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        
        // Verstecke das Mapping-Formular und zeige einen Spinner
        mappingForm.classList.add('d-none');
        previewContainer.innerHTML = `
            <div class="text-center p-5">
                <div class="spinner-border" role="status"></div>
                <p class="mt-2">Datei wird validiert, bitte warten...</p>
            </div>`;

        const formData = new FormData(mappingForm);

        try {
            const response = await new AjaxRequest(validationUrl).post(formData);
            const data = await response.resolve();
            renderPreviewTable(data, previewContainer);
        } catch (error) {
            previewContainer.innerHTML = '<div class="alert alert-danger">Ein schwerwiegender Fehler ist bei der Validierung aufgetreten. Bitte prüfen Sie die Server-Logs.</div>';
            console.error('Validation AJAX error:', error);
        }
    });
}