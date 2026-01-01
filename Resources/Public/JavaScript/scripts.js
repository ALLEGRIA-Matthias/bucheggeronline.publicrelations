// js/main.js

// -----------------------------------------------------------------------------
// 1. IMPORTE: Alle deine Initialisierungsfunktionen aus ihren Modulen importieren
// -----------------------------------------------------------------------------
import { initializeTableFilters } from './modules/tableFilter.js';
import { initializeContactSelects } from './modules/tomSelectContactHandler.js';
import { initializeEventSelects } from './modules/tomSelectEventHandler.js';
import { initializeCheckboxToggleAll } from './modules/tableCheckboxToggler.js';
import { initializeGenericTomSelects } from './modules/tomSelectGenericHandler.js';
import { initializeSelectTogglers } from './modules/selectToggler.js';
import { initializeCustomDatePickers } from './modules/customFormElements.js';
import { initializeDependentClientCampaignSelects } from './modules/dependentSelectsHandler.js';
import { initializeCheckboxDisablers } from './modules/checkboxDisableHandler.js';
import { initializeCopyToClipboard } from './modules/copyToClipboardHandler.js';
import { initializePreventEnterSubmit } from './modules/preventEnterSubmitHandler.js';
import { initializeBulkActionSelects } from './modules/bulkActionSelectHandler.js';
import { initializeEventStatusRefresher } from './modules/checkinEventStatusRefresher.js';
import { initializeGuestListTable } from './modules/checkinGuestListTables.js';
import { initializePrintButton } from './modules/buttonPrintHandler.js';
import { initializeCloseWindowButton } from './modules/buttonCloseWindowHandler.js';
import { initializeTableSort } from './modules/tableSort.js';
import { initializeOptionToggler } from './modules/optionToggler.js';
import { initializeEmailValidation } from './modules/emailValidationHandler.js';
import { initializeImportMappingForm } from './modules/importHandler.js';
import { initializeMailingListSelector } from './modules/mailingListSelector.js';
import { initializeInvitationManagerSummary } from './modules/invitationManagerSummary.js';
import { initializeReceiverManagerSummary } from './modules/receiverManagerSummary.js';

// -----------------------------------------------------------------------------
// 2. INITIALIZER-REGISTRY: Definiere, welche Funktion bei welchem Selektor greift
// -----------------------------------------------------------------------------
const initializers = [
    {
        selector: 'input.filter-table', // Selektor, der das Vorhandensein des Features anzeigt
        initFunction: initializeTableFilters,   // Die Funktion, die aufgerufen werden soll
        name: 'Tabellenfilter'                  // Ein optionaler Name für Logging-Zwecke
    },
    {
        selector: 'select.select-contact', // Der Selektor für deine Kontakt-Selects
        initFunction: initializeContactSelects,
        name: 'Kontakt-Suche (TomSelect)'
    },
    {
        selector: 'select.select-event', // Dein Selektor für Event-Selects
        initFunction: initializeEventSelects,
        name: 'Event-Auswahl (TomSelect)'
    },
    {
        selector: 'th input.checkbox-toggle-all', // Der Selektor für die Master-Kontrollkästchen
        initFunction: initializeCheckboxToggleAll,
        name: 'Tabellen Checkbox Toggle-All'
    },
    {
        selector: 'select.tomselect-generic', // Der Selektor für deine generischen Selects
        initFunction: initializeGenericTomSelects,
        name: 'Generische TomSelect Dropdowns'
    },
    {
        selector: 'select.select-toggle', // Der Selektor für deine Select-Toggle Dropdowns
        initFunction: initializeSelectTogglers,
        name: 'Select-gesteuerte Sektionsanzeige'
    },
    {
        selector: 'input[data-input-type="datetimepicker"]', // Oder deine spezifische Klasse/Marker
        initFunction: initializeCustomDatePickers,
        name: 'Benutzerdefinierte Datumsfelder'
    },
    {
        selector: 'select.js-client-select', // Aktiviert, wenn ein Kunden-Select vorhanden ist
        initFunction: initializeDependentClientCampaignSelects,
        name: 'Abhängige Kunden/Kampagnen Dropdowns'
    },
    {
        selector: 'input.checkbox-disable[data-disable-id]', // Selektor für die Master-Checkboxen
        initFunction: initializeCheckboxDisablers,
        name: 'Checkbox-gesteuerte Feld (De-)Aktivierung'
    },
    {
        selector: '.js-copy-to-clipboard', // Selektor für Elemente, die das Kopieren auslösen
        initFunction: initializeCopyToClipboard,
        name: 'In Zwischenablage kopieren'
    },
    {
        selector: 'form.js-prevent-enter-submit', // Selektor für die Formulare
        initFunction: initializePreventEnterSubmit,
        name: 'Enter-Taste im Formular deaktivieren'
    },
    {
        selector: 'select.select-table-toggler[data-toggle-id]',
        initFunction: initializeBulkActionSelects,
        name: 'Bulk-Aktion für Select-Felder in Tabellen'
    },
    {
        selector: '#event-status-container', // Selektor für den Container des Statusbereichs
        initFunction: initializeEventStatusRefresher,
        name: 'Event Status Auto-Refresher'
    },
    {
        selector: '#event-status-container', // Selektor für den Container des Statusbereichs
        initFunction: initializeEventStatusRefresher,
        name: 'Event Status Auto-Refresher'
    },
    {
        selector: '#guestlist-table',         // NEUER EINTRAG: Anker-Element für die Gästeliste
        initFunction: initializeGuestListTable,
        name: 'Gästelisten Tabelle (MDB)'
    },
    {
        selector: '.js-print-button', // Selektor für den Print-Button
        initFunction: initializePrintButton,
        name: 'Druck-Button Handler'
    },
    {
        selector: '.js-close-window-button', // Selektor für den Schließen-Button
        initFunction: initializeCloseWindowButton,
        name: 'Schließen-Button Handler'
    },
    {
        selector: 'table.table-sort', // Selektor für das Sortieren von Tabellen
        initFunction: initializeTableSort,
        name: 'Tabelle sortieren'
    },
    {
        selector: 'input[type="radio"][data-toggle-group]',
        initFunction: initializeOptionToggler,
        name: 'Generischer Radio-Button Umschalter (Option Toggler)'
    },
    {
        selector: '.js-email-validation',
        initFunction: initializeEmailValidation,
        name: 'Kontakt E-Mail AJAX-Validierung'
    },
    {
        selector: '#mapping-form', // Das Formular in UploadAndMap.html
        initFunction: initializeImportMappingForm,
        name: 'Kontakt-Import Mapping-Formular'
    },
    {
        selector: '#mailing-list-select', // Hört jetzt auf die ID des Select-Felds
        initFunction: initializeMailingListSelector,
        name: 'Kontext-abhängiger Kategorie-Selektor'
    },
    {
        selector: 'form.js-invitation-form',
        initFunction: initializeInvitationManagerSummary,
        name: 'Einladungsmanager Übersicht'
    },
    {
        selector: 'form.js-receiver-form',
        initFunction: initializeReceiverManagerSummary,
        name: 'Mailempfänger Übersicht'
    },
];

// -----------------------------------------------------------------------------
// 3. DOMContentLoaded: Initialisierungslogik ausführen
// -----------------------------------------------------------------------------
document.addEventListener('DOMContentLoaded', () => {
    // console.log('DOM vollständig geladen. Starte Feature-Initialisierung...');

    initializers.forEach(initializer => {
        // Prüfen, ob mindestens ein Element für den Selektor existiert
        if (document.querySelector(initializer.selector)) {
            try {
                // console.log(`Initialisiere: ${initializer.name || initializer.selector}`);
                initializer.initFunction(); // Rufe die zugehörige Initialisierungsfunktion auf
            } catch (error) {
                // console.error(`Fehler bei der Initialisierung von "${initializer.name || initializer.selector}":`, error);
            }
        } else {
            // Optional: Loggen, wenn keine Elemente gefunden wurden (kann bei vielen Features verbose werden)
            // console.log(`Keine Elemente für "${initializer.name || initializer.selector}" gefunden. Überspringe.`);
        }
    });

    // console.log('Feature-Initialisierung abgeschlossen.');
});