// js/modules/eventStatusRefresher.js

import AjaxRequest from '@typo3/core/ajax/ajax-request.js';

let refreshIntervalId = null;
let isAutoRefreshActive = true;
let lastRefreshedTimestampElement = null;

async function fetchAndUpdateStatus(containerElement, contentElement, eventUid) {
    if (!isAutoRefreshActive || !document.body.contains(containerElement)) {
        if (refreshIntervalId) clearInterval(refreshIntervalId);
        return;
    }

    const ajaxUrl = TYPO3.settings.ajaxUrls.publicrelations_checkinstatus;
    if (!ajaxUrl) {
        console.error('EventStatusRefresher: AJAX URL "publicrelations_checkinstatus" ist nicht definiert.');
        if (refreshIntervalId) clearInterval(refreshIntervalId);
        return;
    }

    try {
        const request = new AjaxRequest(ajaxUrl)
            .withQueryArguments({
                'tx_publicrelations_eventcenter[event]': eventUid,
            });
        
        const response = await request.get(); // Führt den GET-Request aus

        // Da wir jetzt JSON erwarten, verwenden wir .resolve()
        const jsonData = await response.resolve(); 

        if (jsonData.error) { // Fehlerbehandlung für serverseitige Fehler im JSON
            throw new Error(jsonData.error);
        }

        if (jsonData.html && document.body.contains(contentElement)) {
            contentElement.innerHTML = jsonData.html; // HTML aus dem JSON extrahieren
            if (lastRefreshedTimestampElement) {
                const now = new Date();
                lastRefreshedTimestampElement.textContent = `Zuletzt aktualisiert: ${now.toLocaleTimeString()}`;
            }
        } else if (refreshIntervalId) {
            clearInterval(refreshIntervalId);
            refreshIntervalId = null;
        }
    } catch (error) { // Fängt sowohl Netzwerkfehler als auch jsonData.error
        console.error('EventStatusRefresher: Fehler beim Aktualisieren des Event-Status:', error);
        let errorMessage = 'Fehler beim Update.';
        if (error.message) { // Standard JavaScript Fehlerobjekt
            errorMessage += ` (${error.message})`;
        }
        // Wenn error ein AjaxRequest.Error ist, könnte es eine 'response' Eigenschaft haben
        // aber da wir jsonData.error schon prüfen, ist das hier vielleicht nicht mehr nötig.

        if (lastRefreshedTimestampElement) {
            lastRefreshedTimestampElement.textContent = errorMessage;
        }
    }
}

// Der Rest der Datei (initializeEventStatusRefresher) bleibt gleich:
export function initializeEventStatusRefresher() {
    const statusContainer = document.getElementById('event-status-container');
    if (!statusContainer) return;

    const eventUid = statusContainer.dataset.eventUid; 
    const contentElement = document.getElementById('event-status-content');
    const toggleCheckbox = document.getElementById('toggleAutoRefresh');
    
    // Finde oder erstelle ein Element für die "Zuletzt aktualisiert" Info
    lastRefreshedTimestampElement = statusContainer.querySelector('.js-last-refreshed-timestamp-wrapper'); // Suche nach dem Wrapper-Div

    if (!lastRefreshedTimestampElement) {
        // Erstelle das Wrapper-Div
        const timestampWrapperDiv = document.createElement('div');
        timestampWrapperDiv.className = 'text-end js-last-refreshed-timestamp-wrapper'; // Bootstrap Klassen: margin-bottom und text-end (oder text-right für BS4)

        // Erstelle das <small>-Element für den Text
        const timestampTextElement = document.createElement('small');
        timestampTextElement.className = 'text-muted'; // Nur noch text-muted, d-block etc. nicht mehr nötig
        timestampTextElement.style.minHeight = '1.2em'; // Verhindert Springen

        // Füge das Text-Element in das Wrapper-Div ein
        timestampWrapperDiv.appendChild(timestampTextElement);
        
        // Weise das innere <small>-Element der globalen Variable zu, damit es aktualisiert wird
        lastRefreshedTimestampElement = timestampTextElement; 

        // Füge das Wrapper-Div an einer passenden Stelle ein
        // Ideal: Zwischen dem Toggle-Container und dem event-status-content
        const toggleContainer = statusContainer.querySelector('.d-flex.justify-content-end.mb-2');
        if (toggleContainer && toggleContainer.parentNode === statusContainer) {
            // Füge es NACH dem Toggle-Container ein
            toggleContainer.parentNode.insertBefore(timestampWrapperDiv, toggleContainer.nextSibling);
        } else {
            // Fallback: Füge es VOR dem contentElement ein, wenn die Toggle-Struktur nicht gefunden wird
            contentElement.parentNode.insertBefore(timestampWrapperDiv, contentElement);
        }
    } else {
        // Wenn der Wrapper schon da ist, finde das innere <small>-Element
        const existingTextElement = lastRefreshedTimestampElement.querySelector('small.text-muted');
        if (existingTextElement) {
            lastRefreshedTimestampElement = existingTextElement;
        } else {
            // Fallback, falls die Struktur anders ist als erwartet oder das <small> fehlt
            console.warn('Timestamp-Wrapper gefunden, aber inneres Text-Element nicht. Text wird direkt im Wrapper aktualisiert.');
        }
    }


    if (!contentElement || !eventUid) {
        console.warn('EventStatusRefresher: Benötigte Elemente oder Event-UID nicht gefunden.');
        return;
    }

    if (toggleCheckbox) {
        isAutoRefreshActive = toggleCheckbox.checked;
        toggleCheckbox.addEventListener('change', function() {
            isAutoRefreshActive = this.checked;
            if (isAutoRefreshActive && !refreshIntervalId) {
                fetchAndUpdateStatus(statusContainer, contentElement, eventUid); 
                refreshIntervalId = setInterval(() => fetchAndUpdateStatus(statusContainer, contentElement, eventUid), 30000);
                 if (lastRefreshedTimestampElement) lastRefreshedTimestampElement.style.display = 'block';
            } else if (!isAutoRefreshActive && refreshIntervalId) {
                clearInterval(refreshIntervalId);
                refreshIntervalId = null;
                if (lastRefreshedTimestampElement) lastRefreshedTimestampElement.style.display = 'none';
            }
        });
         if (!isAutoRefreshActive && lastRefreshedTimestampElement) { 
            lastRefreshedTimestampElement.style.display = 'none';
        }
    }

    if (isAutoRefreshActive) {
        fetchAndUpdateStatus(statusContainer, contentElement, eventUid); 
        refreshIntervalId = setInterval(() => fetchAndUpdateStatus(statusContainer, contentElement, eventUid), 30000);
    }
}