// js/modules/bulkActionSelectHandler.js

export function initializeBulkActionSelects() {
    // ALTE ZEILE:
    // const masterSelects = document.querySelectorAll('select.select-toggler[data-toggle-id]');

    // NEUE ZEILE:
    const masterSelects = document.querySelectorAll('select.select-table-toggler[data-toggle-id]');

    // Der Rest der Funktion bleibt gleich...
    masterSelects.forEach(masterSelect => {
        const toggleGroupId = masterSelect.dataset.toggleId;

        if (!toggleGroupId) {
            console.warn('[BulkActionSelect] Master-Select (jetzt .select-table-toggler) fehlt data-toggle-id.', masterSelect);
            return;
        }

        masterSelect.addEventListener('change', function() {
            // ... die Logik innerhalb des Event-Listeners bleibt unverändert ...
            const selectedValueInMaster = this.value;
            if (selectedValueInMaster === "" || selectedValueInMaster === "0") {
                return;
            }
            const targetSelects = document.querySelectorAll(`select[data-toggle-target="${toggleGroupId}"]`);
            targetSelects.forEach(targetSelect => {
                if (targetSelect.disabled) { return; }
                const row = targetSelect.closest('tr');
                if (!row || window.getComputedStyle(row).display === 'none') { return; }

                let optionExistsInTarget = false;
                for (let i = 0; i < targetSelect.options.length; i++) {
                    if (targetSelect.options[i].value === selectedValueInMaster) {
                        optionExistsInTarget = true;
                        break;
                    }
                }

                if (optionExistsInTarget) {
                    targetSelect.value = selectedValueInMaster;
                    if (targetSelect.tomselect) {
                        targetSelect.tomselect.setValue(selectedValueInMaster, true);
                    }
                }
            });
        });
    });
}