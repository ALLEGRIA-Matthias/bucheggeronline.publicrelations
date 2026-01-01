// js/modules/tomSelectGenericHandler.js

import { TomSelect } from './../tomselect/tomselect.esm.js'; // Dein Importpfad

export function initializeGenericTomSelects() {
    const genericSelects = document.querySelectorAll('select.tomselect-generic');

    if (genericSelects.length === 0) {
        return;
    }

    genericSelects.forEach(selectElement => {
        if (selectElement.tomselect) { // Bereits initialisiert? Überspringen.
            return;
        }

        const isMultiple = selectElement.hasAttribute('multiple');

        const tomSelectOptions = {
            placeholder: selectElement.getAttribute('placeholder') || 'Bitte auswählen...',
            allowEmptyOption: true,
            dropdownParent: 'body', // Diese Option hattest du als hilfreich empfunden
            // closeAfterSelect: true, // Ist Standard für Single-Select, false für Multi-Select.
                                    // TomSelect handhabt das meist automatisch korrekt.
        };

        // Spezifisches Verhalten für Single-Select-Dropdowns:
        if (!isMultiple) {
            tomSelectOptions.onItemAdd = function() {
                // Nachdem ein Item ausgewählt wurde (in Single-Select-Modus),
                // entferne den Fokus vom TomSelect-Eingabefeld.
                // Das Dropdown sollte sich durch TomSelects Standardverhalten bereits schließen.
                this.blur(); // 'this' bezieht sich hier auf die TomSelect-Instanz
            };
        }
        // Für Multi-Selects ist das Standardverhalten (Fokus behalten) erwünscht.

        try {
            new TomSelect(selectElement, tomSelectOptions);
        } catch (error) {
            console.error('Fehler bei der Initialisierung eines generischen TomSelect für Element:', selectElement, error);
        }
    });
}