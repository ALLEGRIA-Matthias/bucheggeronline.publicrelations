import AjaxRequest from '@typo3/core/ajax/ajax-request.js';
import Notification from '@typo3/backend/notification.js';

/**
 * Logik für das MailPreview.html Template
 */
class MailPreview {
    constructor(container) {
        this.container = container;

        this.iframe = this.container.querySelector('#mail-content-iframe');
        this.htmlTemplate = this.container.querySelector('#mail-html-content');

        this.loadIframeContent();
        this.initializeTestSendButton();
        this.initializeResizeButtons(); 
        this.initializeCheckboxToggles();
    }

    /**
     * NEU: Lädt das HTML aus dem <template> in das <iframe>
     */
    loadIframeContent() {
        if (!this.iframe || !this.htmlTemplate) {
            console.warn('Iframe oder HTML-Template für Vorschau nicht gefunden.');
            return;
        }
        
        const htmlContent = this.htmlTemplate.innerHTML;
        
        try {
            const iframeDoc = this.iframe.contentWindow.document;
            iframeDoc.open();
            iframeDoc.write(htmlContent); // HTML in das Iframe schreiben
            iframeDoc.close();
        } catch (e) {
            console.error('Fehler beim Beschreiben des Iframe-Inhalts:', e);
        }
    }

    /**
     * KORRIGIERT: Zielt jetzt auf das <iframe>
     */
    initializeResizeButtons() {
        const resizeButtons = this.container.querySelectorAll('[data-action="resize-preview"]');
        const pane = this.iframe; // <-- KORREKTUR
        
        if (!pane || resizeButtons.length === 0) {
            return;
        }

        resizeButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                const width = button.dataset.width;

                if (width === '100%') {
                    pane.style.width = '100%';
                    pane.style.maxWidth = '100%';
                } else {
                    pane.style.width = width + 'px';
                    pane.style.maxWidth = '100%';
                }
            });
        });
    }

    /**
     * NEU: Fügt Klick-Listener zu den Checkbox-Toggles hinzu.
     */
    initializeCheckboxToggles() {
        const toggleAll = this.container.querySelector('[data-action="toggle-checkboxes-all"]');
        const toggleNone = this.container.querySelector('[data-action="toggle-checkboxes-none"]');
        const checkboxes = this.container.querySelectorAll('#test-send-panel input[type="checkbox"]');

        if (toggleAll) {
            toggleAll.addEventListener('click', (e) => {
                e.preventDefault();
                checkboxes.forEach(cb => { cb.checked = true; });
            });
        }
        if (toggleNone) {
            toggleNone.addEventListener('click', (e) => {
                e.preventDefault();
                checkboxes.forEach(cb => { cb.checked = false; });
            });
        }
    }

    /**
     * Initialisiert den "Test-Mails senden"-Button
     */
    initializeTestSendButton() {
        const sendButton = this.container.querySelector('#testSendButton');
        if (!sendButton) {
            // Panel ist nicht da (z.B. bei Fake-Accreditation), nichts zu tun.
            return;
        }
        
        const ajaxUrl = TYPO3?.settings?.ajaxUrls?.publicrelations_accreditation_test; // URL aus data-Attribut

        sendButton.addEventListener('click', (e) => {
            e.preventDefault();

            sendButton.disabled = true;
            sendButton.innerHTML = '<i class="bi bi-send"></i> Test-Mails werden verschickt...';

            const form = this.container.querySelector('#testSendForm');
            
            // 1. Hole die Werte aus den Feldern
            const emailList = form.querySelector('input[name="emailList"]').value;
            const accreditationUid = form.querySelector('input[name="accreditation"]').value;
            const eventUid = form.querySelector('input[name="event"]').value;
            const invitationUid = form.querySelector('input[name="invitation"]').value;
            
            // 2. Wandle die Checkboxen in einen Komma-String um
            const checkedCheckboxes = form.querySelectorAll('input[name="variantCodes"]:checked');
            const variantCodes = Array.from(checkedCheckboxes).map(cb => cb.value).join(',');

            // 3. Validierung
            if (!emailList || !variantCodes) {
                Notification.error('Fehler', 'Bitte E-Mail(s) und mindestens eine Variante auswählen.');
                return;
            }

            // 4. FormData-Objekt manuell befüllen
            const formData = new FormData();
            formData.append('emailList', emailList);
            formData.append('variantCodes', variantCodes); // (Jetzt als String)
            formData.append('accreditation', accreditationUid);
            formData.append('event', eventUid);
            formData.append('invitation', invitationUid);
            
            // TYPO3 Core AjaxRequest (für CSRF-Token)
            new AjaxRequest(ajaxUrl)
                .post(formData)
                .then(async (response) => {
                    // Fall 1: Server antwortet mit 200 OK
                    const data = await response.resolve();
                    if (data.success) {
                        Notification.success('Erfolg', data.message);
                    } else {
                        Notification.error('Fehler', data.message, 10); // Längere Anzeige
                    }
                })
                .catch(async (error) => {
                    // Fall 2: Server antwortet mit 4xx oder 5xx
                    let title = 'Fehler';
                    let errorMessages = [];

                    if (error.response) {
                        try {
                            // 1. JSON aus der Fehler-Antwort lesen
                            const errorData = await error.response.json();
                            
                            // 2. Titel setzen (z.B. "Teilweise erfolgreich (1 gesendet).")
                            if (errorData.message) {
                                title = errorData.message;
                            }
                            
                            // 3. Fehler-Array holen
                            if (errorData.errors && Array.isArray(errorData.errors)) {
                                errorMessages = errorData.errors;
                            } else {
                                errorMessages.push(errorData.message || 'Unbekannter Server-Fehler');
                            }
                        } catch (e) {
                            errorMessages.push(error.message || 'Kommunikationsfehler');
                        }
                    } else {
                        errorMessages.push(error.message || 'Unbekannter Client-Fehler');
                    }

                    // --- START KORREKTUR: Benachrichtigungen aufteilen ---

                    // 1. Die "Erfolgs"-Meldung (als Info, 5 Sek.)
                    Notification.success(title, '', 5); // z.B. "Teilweise erfolgreich..."

                    // 2. JEDEN Fehler als eigene, permanente Notification
                    errorMessages.forEach(errText => {
                        Notification.error(
                            'Fehlerdetails', // Eigener Titel pro Fehler
                            errText,         // z.B. "Fehler bei 'push' an '...'"
                            0                // Dauer 0 = Bleibt, bis man klickt
                        );
                    });
                    
                })
                .finally(() => {
                    sendButton.disabled = false;
                    sendButton.innerHTML = '<i class="bi bi-send"></i> Test-Mails senden';
                });
        });
    }
}


// --- Initialisierung ---
// Initialisiert das Modul, wenn das data-Attribut gefunden wird.
const previewElement = document.querySelector('[data-module="ac-pr-mail-preview"]');
if (previewElement) {
    new MailPreview(previewElement);
}