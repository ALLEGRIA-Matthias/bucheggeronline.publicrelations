// js/modules/customFormElements.js
import DateTimePickerModule from '@typo3/backend/date-time-picker.js';

/**
 * Überprüft ein Datumsbereichspaar und setzt entsprechende Validierungsklassen.
 * @param {HTMLElement} fromElement Das "Von"-Input-Element.
 * @param {HTMLElement} toElement Das "Bis"-Input-Element.
 */
function updateRangeValidationStyles(fromElement, toElement) {
    const fromPicker = fromElement._flatpickr;
    const toPicker = toElement._flatpickr;

    // Nur fortfahren, wenn beide Picker-Instanzen existieren
    if (!fromPicker || !toPicker) {
        // Falls eine Instanz fehlt, vorherige Validierungsstyles entfernen, da keine Range-Prüfung möglich
        manageValidationClasses(fromElement, 'clear');
        manageValidationClasses(toElement, 'clear');
        return;
    }

    const fromDate = fromPicker.selectedDates[0]; // Das erste ausgewählte Datum (Flatpickr kann mehrere)
    const toDate = toPicker.selectedDates[0];

    if (fromDate && toDate) { // Beide Daten sind ausgewählt
        if (fromDate > toDate) {
            // Ungültiger Bereich: "Von"-Datum ist nach dem "Bis"-Datum
            manageValidationClasses(fromElement, 'error');
            manageValidationClasses(toElement, 'error');
        } else {
            // Gültiger Bereich
            manageValidationClasses(fromElement, 'success');
            manageValidationClasses(toElement, 'success');
        }
    } else {
        // Ein oder beide Daten sind nicht ausgewählt.
        // In diesem Fall entfernen wir die spezifischen Range-Validierungs-Klassen.
        // Andere Validierungen (z.B. "Pflichtfeld") könnten separat greifen.
        manageValidationClasses(fromElement, 'clear');
        manageValidationClasses(toElement, 'clear');
    }
}

export function initializeCustomDatePickers() {
    const allDateTimePickerInputs = document.querySelectorAll('input[data-input-type="datetimepicker"]');
    // console.log(`[DatePicker Initializer] Found ${allDateTimePickerInputs.length} potential datepickers.`);

    allDateTimePickerInputs.forEach(inputElement => {
        const inputId = inputElement.id || inputElement.name || 'Unbenanntes Input';
        if (!inputElement.dataset.datepickerInitialized && DateTimePickerModule?.initialize) {
            DateTimePickerModule.initialize(inputElement);
        }

        const pickerInstance = inputElement._flatpickr;
        if (!pickerInstance) {
            // console.warn(`[DatePicker Initializer] No Flatpickr instance for ${inputId}.`);
            return;
        }

        if (inputElement.classList.contains('js-is-daterange')) {
            const linkedToSelector = inputElement.dataset.daterangeTo;
            const linkedFromSelector = inputElement.dataset.daterangeFrom;

            if (linkedToSelector && linkedFromSelector) {
                console.error(`[DateRange] FEHLER bei "${inputId}": Kann nicht gleichzeitig "data-daterange-to" UND "data-daterange-from" haben.`);
                return;
            }

            if (linkedToSelector) { // Aktuelles Element ist ein "VON"-Feld
                const targetToElement = document.querySelector(linkedToSelector);
                if (!targetToElement) {
                    console.error(`[DateRange] "BIS"-Feld (Selektor: "${linkedToSelector}") nicht gefunden für "VON"-Feld "${inputId}".`);
                } else if (!targetToElement._flatpickr && DateTimePickerModule?.initialize) {
                     // Versuche, das Partnerfeld zu initialisieren, falls es noch keine Instanz hat
                    if (!targetToElement.dataset.datepickerInitialized) DateTimePickerModule.initialize(targetToElement);
                    if (!targetToElement._flatpickr) console.warn(`[DateRange] Flatpickr für "BIS"-Partnerfeld "${targetToElement.id || targetToElement.name}" konnte nicht initialisiert/gefunden werden.`);
                }
                
                if (targetToElement?._flatpickr) {
                    const targetToPickerInstance = targetToElement._flatpickr;
                    pickerInstance.config.onChange.push((selectedDates) => {
                        console.log('test');
                        const currentDate = selectedDates[0];
                        targetToPickerInstance.set('minDate', currentDate || null); // null, um Einschränkung aufzuheben
                        if (currentDate && targetToPickerInstance.selectedDates[0] && currentDate > targetToPickerInstance.selectedDates[0]) {
                            targetToPickerInstance.clear();
                        }
                        updateRangeValidationStyles(inputElement, targetToElement); // Validierung aufrufen
                    });
                }
            } else if (linkedFromSelector) { // Aktuelles Element ist ein "BIS"-Feld
                const targetFromElement = document.querySelector(linkedFromSelector);
                if (!targetFromElement) {
                    console.error(`[DateRange] "VON"-Feld (Selektor: "${linkedFromSelector}") nicht gefunden für "BIS"-Feld "${inputId}".`);
                } else if (!targetFromElement._flatpickr && DateTimePickerModule?.initialize) {
                    if(!targetFromElement.dataset.datepickerInitialized) DateTimePickerModule.initialize(targetFromElement);
                    if (!targetFromElement._flatpickr) console.warn(`[DateRange] Flatpickr für "VON"-Partnerfeld "${targetFromElement.id || targetFromElement.name}" konnte nicht initialisiert/gefunden werden.`);
                }

                if (targetFromElement?._flatpickr) {
                    const targetFromPickerInstance = targetFromElement._flatpickr;
                    pickerInstance.config.onChange.push((selectedDates) => {
                        const currentDate = selectedDates[0];
                        targetFromPickerInstance.set('maxDate', currentDate || null); // null, um Einschränkung aufzuheben
                        if (currentDate && targetFromPickerInstance.selectedDates[0] && currentDate < targetFromPickerInstance.selectedDates[0]) {
                            targetFromPickerInstance.clear();
                        }
                        updateRangeValidationStyles(targetFromElement, inputElement); // Validierung aufrufen
                    });
                }
            } else {
                console.warn(`[DateRange] Element "${inputId}" hat Klasse "js-is-daterange", aber weder "data-daterange-to" noch "data-daterange-from".`);
            }
        }
    });

    // Initiale Validierung für alle Range-Paare nach der ersten Initialisierungsrunde
    // Dies stellt sicher, dass bereits gesetzte Werte beim Laden der Seite validiert werden.
    const rangeStarters = document.querySelectorAll('input.js-is-daterange[data-daterange-to]');
    rangeStarters.forEach(fromElement => {
        if (fromElement._flatpickr) { // Nur wenn das "Von"-Feld einen Picker hat
            const toFieldSelector = fromElement.dataset.daterangeTo;
            const toElement = document.querySelector(toFieldSelector);
            if (toElement && toElement._flatpickr) { // Und das "Bis"-Feld auch
                updateRangeValidationStyles(fromElement, toElement);
            }
        }
    });
}