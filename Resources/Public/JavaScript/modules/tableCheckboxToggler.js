// js/modules/tableCheckboxToggler.js

export function initializeCheckboxToggleAll() {
    const masterCheckboxes = document.querySelectorAll('th input.checkbox-toggle-all');

    masterCheckboxes.forEach(masterCheckbox => {
        masterCheckbox.addEventListener('change', function() {
            const isChecked = this.checked;
            const table = this.closest('table');

            if (!table) {
                console.error('CheckboxToggleAll: Konnte keine übergeordnete Tabelle finden.', this);
                return;
            }

            const th = this.closest('th');
            if (!th) {
                console.error('CheckboxToggleAll: Konnte keinen übergeordneten Tabellenkopf (th) finden.', this);
                return;
            }

            // Ermittle den Spaltenindex des Master-Kontrollkästchens
            // (Array.from, um indexOf auf der HTMLCollection/NodeList verwenden zu können)
            const columnIndex = Array.from(th.parentElement.children).indexOf(th);

            if (columnIndex === -1) {
                console.error('CheckboxToggleAll: Spaltenindex konnte nicht bestimmt werden.', th);
                return;
            }

            const tbody = table.querySelector('tbody');
            if (!tbody) {
                console.warn('CheckboxToggleAll: Kein tbody-Element in der Tabelle gefunden.', table);
                return;
            }

            const rows = tbody.querySelectorAll('tr');
            rows.forEach(row => {
                // Prüfe, ob die Zeile sichtbar ist.
                // window.getComputedStyle ist robuster als row.style.display,
                // da es auch CSS-Klassen berücksichtigt.
                const rowStyle = window.getComputedStyle(row);
                if (rowStyle.display !== 'none') {
                    const cellInColumn = row.cells[columnIndex]; // Hole die Zelle im selben Spaltenindex
                    if (cellInColumn) {
                        // Finde das (erste) Kontrollkästchen in dieser Zelle
                        const itemCheckbox = cellInColumn.querySelector('input[type="checkbox"]');
                        if (itemCheckbox && !itemCheckbox.disabled) {
                            itemCheckbox.checked = isChecked;
                            // Optional: Ein 'change'-Event auf den geänderten Checkboxen auslösen,
                            // falls anderer JS-Code darauf reagieren muss.
                            // itemCheckbox.dispatchEvent(new Event('change', { bubbles: true }));
                        }
                    }
                }
            });
        });
    });

    // Optional: Initial den Zustand der Master-Checkboxes setzen,
    // falls einige Items schon vorausgewählt sind (nicht Teil der ursprünglichen Anfrage, aber oft nützlich)
    // Dies würde erfordern, auch die Item-Checkboxes zu beobachten.
}