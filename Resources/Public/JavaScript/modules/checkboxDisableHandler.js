// js/modules/checkboxDisableHandler.js

export function initializeCheckboxDisablers() {
    const masterCheckboxes = document.querySelectorAll('input.checkbox-disable[data-disable-id]');

    masterCheckboxes.forEach(masterCheckbox => {
        const disableId = masterCheckbox.dataset.disableId;
        if (!disableId) {
            // console.warn('[CheckboxDisabler] Master-Checkbox hat kein data-disable-id.', masterCheckbox);
            return;
        }

        const targetElements = document.querySelectorAll(`[data-disable-target="${disableId}"]`);

        // Funktion, um den disabled-Status der Ziel-Elemente zu aktualisieren
        const updateTargetsState = () => {
            const isMasterChecked = masterCheckbox.checked;

            targetElements.forEach(targetElement => {
                // Prüfe, ob das data-disable-reverse Attribut am Ziel-Element vorhanden ist.
                // Die reine Existenz des Attributs reicht, oder du prüfst auf den Wert 'true'.
                const isReversed = targetElement.hasAttribute('data-disable-reverse') ||
                                   targetElement.dataset.disableReverse === 'true';

                let shouldTargetBeDisabled;

                if (isReversed) {
                    // Umgekehrte Logik:
                    // Master ist gecheckt -> Ziel soll disabled sein.
                    // Master ist NICHT gecheckt -> Ziel soll NICHT disabled sein (enabled).
                    shouldTargetBeDisabled = isMasterChecked;
                } else {
                    // Normale Logik:
                    // Master ist gecheckt -> Ziel soll NICHT disabled sein (enabled).
                    // Master ist NICHT gecheckt -> Ziel soll disabled sein.
                    shouldTargetBeDisabled = !isMasterChecked;
                }
                
                // Setze den disabled-Status am nativen HTML-Element
                targetElement.disabled = shouldTargetBeDisabled;

                // Wenn das Ziel-Element eine TomSelect-Instanz hat,
                // rufe auch dessen enable()/disable() Methode auf.
                if (targetElement.tomselect) {
                    if (shouldTargetBeDisabled) {
                        targetElement.tomselect.disable();
                    } else {
                        targetElement.tomselect.enable();
                    }
                    // console.log(`[CheckboxDisabler] TomSelect für ${targetElement.id || targetElement.name} disabled: ${shouldTargetBeDisabled}`);
                }
                // console.log(`[CheckboxDisabler] Ziel ${targetElement.id || targetElement.name} disabled: ${targetElement.disabled}`);
            });
        };

        masterCheckbox.addEventListener('change', updateTargetsState);
        updateTargetsState(); // Initialen Zustand setzen
    });
}