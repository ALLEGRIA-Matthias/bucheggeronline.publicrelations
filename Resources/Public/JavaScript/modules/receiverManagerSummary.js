// js/modules/invitationManagerSummary.js

/**
 * Initialisiert die Logik für die Einladungsmanager-Übersicht.
 * Sammelt UIDs von Checkboxen und schreibt sie in ein verstecktes Feld.
 */
export function initializeReceiverManagerSummary() {
    const form = document.querySelector('form.js-receiver-form');
    if (!form) {
        return; // Kein Formular gefunden, abbrechen
    }

    const uidField = form.querySelector('input[name="receiver[selectedReceiverUids]"]');
    if (!uidField) {
        console.error("Fehler: Verstecktes Feld 'selectedReceiverUids' nicht gefunden.");
        return;
    }

    // Beim Absenden des Formulars die UIDs sammeln
    form.addEventListener('submit', (event) => {
        const checkedCheckboxes = form.querySelectorAll('.receiver-checkbox:checked');
        const selectedUids = [];

        checkedCheckboxes.forEach(checkbox => {
            selectedUids.push(checkbox.dataset.uid);
        });

        // Die UIDs als komma-getrennten String in das versteckte Feld schreiben
        uidField.value = selectedUids.join(',');

        // Optional: Debugging
        // console.log("Gesammelte UIDs:", selectedUids);
        // console.log("Verstecktes Feld-Wert:", uidField.value);

        // Die Formularaktion fortsetzen (wird standardmäßig fortgesetzt)
    });

    // Optional: Logik für den "Alles auswählen / Alles abwählen"-Button
    const toggleAllCheckbox = form.querySelector('.checkbox-toggle-all');
    if (toggleAllCheckbox) {
        toggleAllCheckbox.addEventListener('change', () => {
            const receiverCheckboxes = form.querySelectorAll('.receiver-checkbox');
            const isChecked = toggleAllCheckbox.checked;
            receiverCheckboxes.forEach(checkbox => {
                checkbox.checked = isChecked;
            });
        });
    }
}