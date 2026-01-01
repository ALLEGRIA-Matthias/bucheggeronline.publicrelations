// js/modules/selectToggler.js

export function initializeSelectTogglers() {
    const selectToggleElements = document.querySelectorAll('select.select-toggle');

    selectToggleElements.forEach(selectElement => {
        // Lese die ID des zugehörigen Containers aus dem data-Attribut des Select-Elements
        const containerId = selectElement.dataset.toggleContainerId;
        if (!containerId) {
            console.warn('Select-Toggle: Das Attribut "data-toggle-container-id" fehlt am select-Element:', selectElement);
            return;
        }

        // Finde das Container-Element anhand seines data-toggle-container Attributs
        const containerElement = document.querySelector(`[data-toggle-container="${containerId}"]`);
        if (!containerElement) {
            console.warn(`Select-Toggle: Container-Element mit "data-toggle-container='${containerId}'" wurde nicht gefunden für Select:`, selectElement);
            return;
        }

        // Funktion, die die Sichtbarkeit der Sektionen aktualisiert
        const updateVisibleSections = () => {
            const selectedValue = selectElement.value;
            // console.log(`[Select-Toggle Debug] Wert für "${containerId}" geändert auf: "${selectedValue}"`);

            // Gehe alle direkten Kind-Elemente des Containers durch
            Array.from(containerElement.children).forEach(childElement => {
                // Prüfe, ob das Kind ein 'data-toggle-value' Attribut hat
                if (childElement.dataset.toggleValue !== undefined) {
                    if (childElement.dataset.toggleValue === selectedValue) {
                        childElement.style.display = ''; // Entferne 'display: none', um es anzuzeigen
                                                        // Bootstrap-Klassen (.col-*) sollten dann das Layout übernehmen
                    } else {
                        childElement.style.display = 'none'; // Verstecke andere Sektionen
                    }
                }
            });
        };

        // Event-Listener für das 'change'-Event am Select-Element hinzufügen
        selectElement.addEventListener('change', updateVisibleSections);

        // Initialisiere den korrekten Zustand beim Laden der Seite
        // (falls das Select-Feld bereits einen vorausgewählten Wert hat)
        updateVisibleSections();
    });
}