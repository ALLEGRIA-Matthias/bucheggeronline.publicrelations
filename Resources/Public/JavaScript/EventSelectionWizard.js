import Notification from '@typo3/backend/notification.js';

/**
 * EventSelectionWizard
 * Spezifische Validierung für den PublicRelations Event Wizard.
 */
class EventSelectionWizard {
    constructor(element) {
        this.element = element;
        this.form = document.getElementById('selection-submit-form');
        this.bindSubmit();
    }

    bindSubmit() {
        if (!this.form) return;

        this.form.addEventListener('submit', (e) => {
            const formData = new FormData(this.form);
            let hasCriteria = false;
            
            // Wir prüfen, ob IRGENDEIN Filter gesetzt ist.
            // Zumindest ein Event ODER ein Suchbegriff sollte da sein, 
            // sonst laden wir alle Akkreditierungen der Welt.
            
            // 1. Event ID Check
            const eventId = formData.get('filters[event]');
            if (eventId && eventId !== '') {
                hasCriteria = true;
            }

            // 2. Facie Check (wenn explizit gesetzt)
            const facie = formData.get('filters[facie]');
            if (facie !== '') {
                hasCriteria = true;
            }
            
            // 3. Tickets Text Check
            const tickets = formData.get('filters[tickets]');
            if (tickets && tickets.trim() !== '') {
                hasCriteria = true;
            }

            // Wenn gar nichts gewählt ist -> Warnung
            if (!hasCriteria) {
                // Optional: Man kann es auch durchlassen, wenn man "Alle" erlauben will.
                // Aber meistens ist das ein User-Fehler.
                e.preventDefault();
                Notification.warning('Keine Kriterien', 'Bitte wähle mindestens ein Event oder einen Filterstatus aus.');
            }
        });
    }
}

// Auto-Init
const wizardContainer = document.querySelector('[data-contact-selection-wizard]');
if (wizardContainer) {
    new EventSelectionWizard(wizardContainer);
}