// js/modules/tomSelectEventHandler.js

import { TomSelect } from './../tomselect/tomselect.esm.js'; // Dein Importpfad für TomSelect
import AjaxRequest from '@typo3/core/ajax/ajax-request.js';   // ES-Modul-Import für AjaxRequest

/**
 * Hilfsfunktion zum Laden der Events.
 * Verwendet jetzt den importierten AjaxRequest und async/await.
 */
async function fetchEvents(query) {
    const ajaxUrl = (window.TYPO3?.settings?.ajaxUrls?.publicrelations_eventsearch);
    if (!ajaxUrl) {
        console.error('TomSelect Events: AJAX URL "publicrelations_eventsearch" nicht definiert.');
        // Wir werfen einen Fehler, damit das catch im aufrufenden load greift
        throw new Error('Event search URL nicht definiert.');
    }

    try {
        const response = await new AjaxRequest(ajaxUrl)
            .withQueryArguments({ q: query })
            .get(); // .get() gibt ein Promise<ResponseInterface> zurück

        const serverData = await response.resolve(); // response.resolve() gibt ebenfalls ein Promise zurück
        let flatEvents = [];

        if (Array.isArray(serverData)) {
            serverData.forEach(group => {
                if (group && Array.isArray(group.events)) {
                    group.events.forEach(event => {
                        flatEvents.push({
                            // Wichtige Felder für TomSelect (Defaults oder wie in valueField/labelField konfiguriert)
                            id: event.id,
                            text: event.title, // Wird für labelField='text' und ggf. searchField verwendet

                            // Alle ursprünglichen Event-Daten für die Render-Funktionen beibehalten
                            ...event,

                            // Gruppendaten für Optgroups und Rendering
                            optgroupName: group.groupName,
                            groupLogo: group.groupLogo
                        });
                    });
                }
            });
        }
        return flatEvents; // Das Array mit den aufbereiteten Event-Daten
    } catch (error) {
        console.error('TomSelect Events: Fehler beim Laden oder Verarbeiten der Events:', error);
        throw error; // Fehler weiterwerfen, damit das catch im aufrufenden load greift
    }
}

// --- Deine renderEventItem, renderEventOption und renderOptgroupHeader Funktionen ---
// --- bleiben unverändert von der vorherigen Version. Hier zur Vollständigkeit: ---

function renderEventItem(data, escape) {
    if (!data.id) { return `<div>${escape(data.text)}</div>`; }
    const eventDate = new Date(data.date * 1000);
    const day = eventDate.toLocaleDateString("de-DE", { year: 'numeric', month: '2-digit', day: '2-digit' });
    return `<div><strong>${escape(data.text)}</strong> am ${escape(day)}</div>`;
}

function renderEventOption(data, escape) {
    if (!data.id) { return `<div class="p-2 text-muted">${escape(data.text)}</div>`; }

    const eventDate = new Date(data.date * 1000);
    const day = eventDate.toLocaleDateString("de-DE", { year: 'numeric', month: '2-digit', day: '2-digit' });
    const time = eventDate.toLocaleTimeString("de-DE", { hour: '2-digit', minute: '2-digit' });
    const dayOfWeek = eventDate.toLocaleDateString("de-DE", { weekday: 'short' });

    const logoHtml = data.groupLogo 
        ? `<div class="event-logo me-3 d-flex align-items-center"><img src="${escape(data.groupLogo)}" alt="Logo" style="width: 50px; height: auto; max-height: 50px;"></div>` 
        : '<div class="event-logo me-3 d-flex align-items-center" style="width: 50px; height: 50px;"></div>';

    const guestCountHtml = data.guestCount ? `<span class="badge badge-primary mt-1">${escape(String(data.guestCount))} Gäste</span>` : '';

    return `<div class="d-flex align-items-center p-2">
                ${logoHtml}
                <div class="me-3 text-center" style="min-width:80px;">
                    <div>${escape(day)}</div>
                    <div><small class="text-muted">${escape(dayOfWeek)}, ${escape(time)} Uhr</small></div>
                </div>
                <div class="flex-grow-1">
                    <strong>${escape(data.text)}</strong>
                    <div><small class="text-muted">${escape(data.location || '')}, ${escape(data.city || '')}</small></div>
                    ${guestCountHtml}
                </div>
            </div>`;
}

function renderOptgroupHeader(data, escape) {
    return `<div class="optgroup-header p-2 bg-light"><strong>${escape(data.label)}</strong></div>`;
}


export function initializeEventSelects() {
    const selectElements = document.querySelectorAll('select.select-event');

    selectElements.forEach(selectElement => {
        if (selectElement.tomselect) { return; }

        const isMenu = selectElement.dataset.menu === 'true';
        const menuTarget = selectElement.dataset.menuTarget || '_self';

        new TomSelect(selectElement, {
            placeholder: selectElement.getAttribute('placeholder') || 'Nach Event suchen...',
            valueField: 'id',
            labelField: 'text',
            searchField: ['text', 'location', 'city'],

            optgroupField: 'optgroupName',
            optgroupLabelField: 'optgroupName',
            optgroupValueField: 'optgroupName',
            lockOptgroupOrder: true,

            shouldLoad: function(query) {
                return query.length >= 3;
            },
            load: async function(query, callback) {
                // console.log(`[TomSelect Events] load: Query "${query}"`);
                try {
                    const events = await fetchEvents(query); // Ruft die überarbeitete fetchEvents auf
                    // console.log('[TomSelect Events] Rufe Callback auf mit:', events.length, 'Events.');
                    callback(events);
                } catch (error) {
                    // Fehler wurde bereits in fetchEvents geloggt (oder sollte es zumindest)
                    console.error('TomSelect Events: Fehler im load-Callback beim Abrufen von Events:', error);
                    callback([]); // Wichtig: TomSelect leere Ergebnisse bei Fehler geben
                }
            },
            render: {
                option: renderEventOption,
                item: renderEventItem,
                optgroup_header: renderOptgroupHeader
            },
            onChange: function(value) {
                if (isMenu && value) {
                    const selectedEventData = this.options[value]; // TomSelect speichert die Datenobjekte hier
                    if (selectedEventData && selectedEventData.url) {
                        window.open(selectedEventData.url, menuTarget);
                        // Optional: this.clear(); this.blur();
                    }
                }
            }
            // onInitialize, onFocus, onDropdownOpen etc. können bei Bedarf hinzugefügt werden.
        });
    });
}