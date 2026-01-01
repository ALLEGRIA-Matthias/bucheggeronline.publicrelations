// js/modules/copyToClipboardHandler.js

import Notification from '@typo3/backend/notification.js'; // Für TYPO3 Backend Notifications

export function initializeCopyToClipboard() {
    const copyTriggers = document.querySelectorAll('.js-copy-to-clipboard');

    copyTriggers.forEach(triggerElement => {
        triggerElement.addEventListener('click', async function(event) {
            // Verhindere Standardaktionen, falls das Element ein Link ist etc.
            event.preventDefault();

            // NEUE PRÜFUNG: Ist das Element explizit durch die Klasse 'disabled' deaktiviert?
            if (this.classList.contains('disabled')) {
                Notification.warning( // Zeige eine TYPO3 Warn-Notification
                    'Aktion nicht möglich', // Titel der Warnung
                    'Diese Funktion ist aktuell deaktiviert.', // Nachricht der Warnung
                    5 // Anzeigedauer in Sekunden (optional)
                );
                return; // Breche die weitere Ausführung der Funktion hier ab
            }

            const valueToCopy = this.dataset.copyValue;

            // Standardtexte für die Notification, falls keine Data-Attribute gesetzt sind
            const successTitle = this.dataset.copySuccessTitle || 'Erfolgreich kopiert';
            const successMessage = this.dataset.copySuccessMessage || 'Inhalt in die Zwischenablage kopiert.';
            
            const errorTitle = this.dataset.copyErrorTitle || 'Fehler beim Kopieren';
            let errorMessage = this.dataset.copyErrorMessage || 'Inhalt konnte nicht in die Zwischenablage kopiert werden.';

            if (valueToCopy === undefined || valueToCopy === null || valueToCopy.trim() === '') {
                // console.warn('CopyToClipboard: Kein "data-copy-value" Attribut gefunden oder der Wert ist leer.', this);
                Notification.warning(
                    'Kopieren nicht möglich',
                    'Kein Inhalt zum Kopieren definiert.',
                    5
                );
                return;
            }

            if (!navigator.clipboard || !navigator.clipboard.writeText) {
                errorMessage = 'Ihr Browser unterstützt diese Kopierfunktion nicht oder blockiert sie (z.B. bei nicht-sicherer Verbindung).';
                console.error('CopyToClipboard: Clipboard API (writeText) ist nicht verfügbar.');
                Notification.error(errorTitle, errorMessage, 7);
                return;
            }

            try {
                await navigator.clipboard.writeText(valueToCopy);
                // console.log('[CopyToClipboard] Text erfolgreich kopiert:', valueToCopy);
                Notification.success(successTitle, successMessage, 3); // Titel, Nachricht, Dauer (optional)

            } catch (err) {
                console.error('[CopyToClipboard] Fehler beim Kopieren in die Zwischenablage:', err);
                Notification.error(errorTitle, errorMessage + ` (Fehler: ${err.name})`, 5);
            }
        });
    });
}