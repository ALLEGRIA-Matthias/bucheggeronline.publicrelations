// js/modules/checkinGuestListTable.js

import * as mdb from '/typo3conf/ext/ac_base/Resources/Public/MDBootstrap/js/mdb.es.min.js';
import AjaxRequest from '@typo3/core/ajax/ajax-request.js';
import Notification from '@typo3/backend/notification.js'; // Korrekter Import

// Modul-Scope Variablen
let guestListDataTableInstance = null;
let currentSortBy = 'name';
let currentSearchTerm = '';
let eventUid = null;

const ajaxUrl = TYPO3.settings.ajaxUrls.publicrelations_accreditationslist;
const modalDetailsAjaxUrl = TYPO3.settings.ajaxUrls.publicrelations_accreditationdetails;
const releaseLockAjaxUrl = TYPO3.settings.ajaxUrls.publicrelations_accreditationreleaselock;

function getStatusBadge(statusKey) {
    switch (statusKey) {
        case 'akkreditiert':
            return '<span class="badge badge-info">akkreditiert</span>'; // Geändert zu badge-info für Konsistenz mit deinem HTML
        case 'teilweise_eingecheckt':
            return '<span class="badge badge-warning text-dark">Teil-Checkin</span>';
        case 'voll_eingecheckt':
            return '<span class="badge badge-success">eingecheckt</span>';
        default:
            // Du hattest badge-danger für Unbekannt, was eher für Fehler steht.
            // Vielleicht badge-secondary oder eine spezifische Farbe für "Unbekannt/Ausstehend"?
            return '<span class="badge badge-secondary">Unbekannt</span>';
    }
}

// Diese Funktion stößt das Neuladen der Tabelle an
function triggerTableReload() {
    console.log("triggerTableReload CALLED");
    performTableUpdate();
}

async function performTableUpdate() {
    console.log(`performTableUpdate CALLED. EventUID: ${eventUid}, Search: "${currentSearchTerm}", Sort: "${currentSortBy}"`);
    if (!eventUid) {
        console.error("performTableUpdate: Event-UID ist nicht gesetzt. Abbruch.");
        Notification.error('Fehler', 'Event-Kontext für Gästeliste fehlt.');
        return;
    }
    if (!ajaxUrl) {
        console.error("performTableUpdate: AJAX-URL (publicrelations_accreditationslist) ist nicht definiert. Abbruch.");
        Notification.error('Fehler', 'Konfigurationsfehler: Daten-URL fehlt.');
        return;
    }
    if (!guestListDataTableInstance) {
        console.error("performTableUpdate: MDB Datatable Instanz ist nicht initialisiert. Abbruch.");
        // Notification.error('Fehler', 'Tabellenkomponente nicht bereit.'); // Kann nervig sein, wenn es oft passiert
        return;
    }

    // Optional: Zeige einen Ladeindikator. MDB Datatable könnte eine eingebaute Methode haben,
    // oder du könntest einen eigenen Spinner über die Tabelle legen.
    // z.B. guestListDataTableInstance.update({ loading: true }); // Prüfe MDB API

    try {
        const request = new AjaxRequest(ajaxUrl)
            .withQueryArguments({
                'tx_publicrelations_eventcenter[event]': eventUid,
                'searchTerm': currentSearchTerm,
                'sortBy': currentSortBy
            });

        console.log("Sende AJAX Request an:", ajaxUrl, "mit Parametern:", {eventUid, currentSearchTerm, currentSortBy});
        const response = await request.get();
        const jsonData = await response.resolve();
        console.log("JSON-Antwort vom Server:", jsonData);

        if (jsonData.error) {
            console.error("Server-Fehler beim Laden der Gästeliste:", jsonData.error);
            Notification.error('Fehler beim Laden', jsonData.error);
            guestListDataTableInstance.update({ rows: [] }); // Tabelle leeren
            document.getElementById('guestlist-count').textContent = '0';
            return;
        }

        if (jsonData.data) {
            const mappedRows = jsonData.data.map(item => ({
                uid: item.uid,
                aktion_html: `<button class="btn btn-sm btn-outline-primary guestlist-open-modal-btn" data-uid="${item.uid}" data-mdb-toggle="modal" data-mdb-target="#accreditationDetailModal">Öffnen</button>`,
                status_html: getStatusBadge(item.checkin_status_key),
                tickets_display: `<div class="text-center">${item.tickets_prepared}/${item.tickets_approved}</div>`,
                gaeste_typ: `<span class="badge ${(item.facie === true || item.facie === 'true' || parseInt(item.facie) === 1) ? 'badge-warning' : 'badge-default'}">${item.guest_type}</span>`,
                name_firma_html: `<strong>${item.name_primary}</strong><br><small class="text-muted">${item.name_secondary || ''}</small>`,
                notizen_html: item.notes_html
            }));

            console.log("Aktualisiere MDB Datatable mit", mappedRows.length, "Zeilen.");
            guestListDataTableInstance.update(
                { rows: mappedRows }
                // Optional: { loading: false } // Ladeindikator ausblenden
            );
            document.getElementById('guestlist-count').textContent = jsonData.recordsFiltered || '0';
        } else {
            console.warn("Keine 'data' Eigenschaft in der JSON-Antwort gefunden. Leere Tabelle.");
            guestListDataTableInstance.update({ rows: [] });
            document.getElementById('guestlist-count').textContent = '0';
        }

    } catch (error) {
        console.error("Catch-Block: Fehler beim AJAX-Request oder Verarbeiten der Antwort:", error);
        Notification.error('Systemfehler', 'Daten konnten nicht geladen werden. Details in der Konsole.');
        if (guestListDataTableInstance) {
            guestListDataTableInstance.update({ rows: [] });
        }
        document.getElementById('guestlist-count').textContent = '0';
    }
}

const checkinSubmitAjaxUrl = TYPO3.settings.ajaxUrls.publicrelations_accreditationcheckin; // NEUE ROUTE DEFINIEREN!

async function openAccreditationModal(accreditationUidToLoad, currentEventUidForContext) {
    const modalElement = document.getElementById('accreditationDetailModal');
    const modalBodyElement = document.getElementById('accreditationDetailModalBody');
    const modalTitleElement = document.getElementById('accreditationDetailModalLabel');
    const modalInstance = mdb.Modal.getInstance(modalElement) || new mdb.Modal(modalElement);

    // ... (Ladeindikator und initiales modalInstance.show() wie gehabt) ...
    modalTitleElement.textContent = 'Lade Details...';
    modalBodyElement.innerHTML = '<div class="d-flex justify-content-center p-5"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>';
    if (!modalInstance._isShown) modalInstance.show();


    try {
        const request = new AjaxRequest(modalDetailsAjaxUrl)
            .withQueryArguments({
                'tx_publicrelations_eventcenter[accreditation]': accreditationUidToLoad,
                'tx_publicrelations_eventcenter[eventContext]': currentEventUidForContext
            });
        
        const response = await request.get();
        const jsonData = await response.resolve();

        // if (jsonData.needsConfirmationToOpen) {
        //     // MDB Confirm oder Standard confirm
        //     const userConfirmation = await new Promise((resolve) => {
        //         // Hier könntest du ein schöneres MDB Confirm Modal implementieren
        //         // Für jetzt: Standard JavaScript confirm
        //         const confirmed = confirm(jsonData.message || "Konflikt. Trotzdem öffnen?");
        //         resolve(confirmed);
        //     });

        //     if (!userConfirmation) {
        //         Notification.info('Abgebrochen', 'Bearbeitung nicht fortgesetzt.');
        //         modalInstance.hide();
        //         return;
        //     }
        //     Notification.warning('Hinweis', 'Bearbeitung trotz möglicher Sperre fortgesetzt.', { duration: 5000 });
        // } else if (jsonData.error) { // Harter Fehler ohne allowOpenAnyway
        //     Notification.error('Fehler', jsonData.message || 'Akkreditierung konnte nicht geladen werden.');
        //     modalInstance.hide();
        //     return;
        // }

        modalTitleElement.textContent = jsonData.guestName || 'Akkreditierungsdetails';
        
        if (jsonData.html) {
            modalBodyElement.innerHTML = jsonData.html;
            // Binde Event-Listener für das neu geladene Formular im Modal
            initializeModalFormLogic(accreditationUidToLoad, currentEventUidForContext); 

            if (jsonData.type === 'warning' && !jsonData.needsConfirmationToOpen) {
                 Notification.warning('Hinweis', jsonData.message, {duration: 7000});
            }
        } else {
            modalBodyElement.innerHTML = '<p class="text-danger">Inhalt konnte nicht geladen werden.</p>';
        }

    } catch (error) {
        console.error("Fehler beim Laden der Modal-Details:", error);
        Notification.error('Systemfehler', 'Details konnten nicht geladen werden.');
        modalTitleElement.textContent = 'Fehler';
        modalBodyElement.innerHTML = '<p class="text-danger">Ein Fehler ist aufgetreten.</p>';
    }
}

// NEUE FUNKTION für die Logik innerhalb des Modals
function initializeModalFormLogic(accreditationUid, currentEventUid) {
    const formContainer = document.getElementById('checkinFormContainer'); // Die ID des äußeren Divs im Modal-Partial
    if (!formContainer) return;

    const ticketsInput = formContainer.querySelector('#checkinTicketsToProcess');
    const notesInput = formContainer.querySelector('#checkinNotesReceived');
    const minusBtn = formContainer.querySelector('#formNumberMinusBtn');
    const plusBtn = formContainer.querySelector('#formNumberPlusBtn');
    const submitBtn = formContainer.querySelector('#submitCheckinBtn');

    if (!ticketsInput || !notesInput || !minusBtn || !plusBtn || !submitBtn) {
        console.error("Ein oder mehrere Formularelemente im Modal nicht gefunden.");
        return;
    }

    // Logik für Plus/Minus-Buttons
    minusBtn.addEventListener('click', function() {
        let currentValue = parseInt(ticketsInput.value) || 0;
        const min = parseInt(ticketsInput.min);
        if (!isNaN(min) && currentValue > min) {
            ticketsInput.value = currentValue - 1;
        } else if (isNaN(min) && currentValue > -Infinity) { // Fallback falls min nicht gesetzt
            ticketsInput.value = currentValue - 1;
        }
    });

    plusBtn.addEventListener('click', function() {
        let currentValue = parseInt(ticketsInput.value) || 0;
        const max = parseInt(ticketsInput.max);
        if (!isNaN(max) && currentValue < max) {
            ticketsInput.value = currentValue + 1;
        } else if (isNaN(max) && currentValue < Infinity) { // Fallback falls max nicht gesetzt
             ticketsInput.value = currentValue + 1;
        }
    });

    // Submit-Logik
    submitBtn.addEventListener('click', async function() {
        const ticketsToProcessValue = parseInt(ticketsInput.value);
        const notesValue = notesInput.value.trim();

        if (ticketsToProcessValue === 0 && notesValue === '') {
            Notification.warning('Ungültige Eingabe', 'Bitte Tickets angeben oder eine Anmerkung eintragen.');
            return;
        }
        if (isNaN(ticketsToProcessValue)) {
            Notification.error('Fehler', 'Ungültige Ticketanzahl.');
            return;
        }

        // Deaktiviere Button, um doppelte Submits zu verhindern
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Speichern...';

        try {
            const payload = {
                'tx_publicrelations_eventcenter[accreditation]': accreditationUid,
                'tx_publicrelations_eventcenter[notesReceived]': notesValue,
                'tx_publicrelations_eventcenter[ticketsReceivedCount]': ticketsToProcessValue,
                'tx_publicrelations_eventcenter[eventContext]': currentEventUid // Falls im Controller benötigt
            };

            const request = new AjaxRequest(checkinSubmitAjaxUrl).post(payload); // Sendet als POST
            const response = await request; // Das Promise von post() ist das PSR-7 Response Objekt
            const jsonData = await response.resolve(); // JSON-Antwort erwarten

            if (jsonData.success) {
                Notification.success('Erfolg', jsonData.message || 'Check-In erfolgreich.');
                const modalInstance = mdb.Modal.getInstance(document.getElementById('accreditationDetailModal'));
                if (modalInstance) modalInstance.hide();
                const searchInput = document.getElementById('guestlist-search');
                if (searchInput) {
                    searchInput.value = ''; // Suchfeld leeren
                }
                currentSearchTerm = '';
                triggerTableReload(); // Tabelle neu laden
                document.getElementById('guestlist-search')?.focus(); // Fokus auf Suchfeld
            } else {
                Notification.error('Fehler', jsonData.message || 'Check-In fehlgeschlagen.');
            }
        } catch (error) {
            Notification.error('Systemfehler', 'Fehler beim Speichern des Check-Ins.');
            console.error("Fehler beim Check-In Submit:", error);
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<svg class="icon icon-sm fill-white mr-1"><use xlink:href="/typo3conf/ext/allegria_communications/Resources/Public/Images/glyphicons-basics.svg#circle-check"></use></svg>Check-In Bestätigen';
        }
    });

    if (releaseLockBtn) {
        if (!releaseLockAjaxUrl) {
            console.error("URL für Sperrfreigabe (publicrelations_releaselock) nicht definiert!");
            releaseLockBtn.disabled = true;
            releaseLockBtn.title = "Funktion nicht verfügbar (Konfigurationsfehler).";
        }

        releaseLockBtn.addEventListener('click', async function() {            
            const confirmed = confirm("Möchtest du deine Bearbeitungssperre für diese Akkreditierung aufheben, damit andere sie sofort bearbeiten können?");
            if (!confirmed) {
                return;
            }

            this.disabled = true;
            const originalButtonText = this.innerHTML;
            this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Freigeben...';

            try {
                const payload = {
                    'tx_publicrelations_eventcenter[accreditation]': accreditationUid
                };
                // Wichtig: POST Request für schreibende Operationen
                const request = new AjaxRequest(releaseLockAjaxUrl).post(payload); 
                const response = await request; // Das PSR-7 Response Objekt
                const jsonData = await response.resolve(); // Erwartet JSON

                if (jsonData.success) {
                    Notification.success('Erfolg', jsonData.message || 'Sperre wurde freigegeben.');
                    const modalInstance = mdb.Modal.getInstance(document.getElementById('accreditationDetailModal'));
                    if (modalInstance) modalInstance.hide(); // Modal schließen
                    triggerTableReload(); // Tabelle aktualisieren
                    // Fokus wird durch den 'hidden.mdb.modal' Listener gesetzt
                } else {
                    Notification.error('Fehler', jsonData.message || 'Sperre konnte nicht freigegeben werden.');
                }
            } catch (error) {
                Notification.error('Systemfehler', 'Fehler bei der Sperrfreigabe.');
                console.error("Fehler bei Sperrfreigabe AJAX:", error);
            } finally {
                this.disabled = false;
                this.innerHTML = originalButtonText;
            }
        });
    }
}

function initializeMDBInstance(tableElement) {
    const columns = [
        // Die erste Spalte für den Button wird oft durch `defaultContent` oder `render` in Datatables definiert,
        // wenn `field` nicht direkt auf ein Datenfeld zeigt. Da wir `aktion_html` im Datenobjekt haben:
        { label: 'Status', field: 'status_html', width: 150 },
        { label: 'Pax', field: 'tickets_display', class: 'text-center', width: 80 }, // Breite angepasst
        { label: 'Gästetyp', field: 'gaeste_typ', width: 150 },
        { label: 'Gästedaten', field: 'name_firma_html' },
        { label: 'Notizen', field: 'notizen_html' },
        { label: 'UID', field: 'uid', width: 100 }
    ];

    const datatableOptions = {
        loading: true,
        loadingMessage: 'Lade Akkreditierungen...',
        hover: true,
        pagination: false,
        fixedHeader: true,
        noFoundMessage: 'Keine Akkreditierungen gefunden.',
        sm: false, // Du hattest es auf false, dein HTML hat table-sm nicht
        striped: true,
        clickableRows: true, // Wenn du auf die Zeile klicken willst, um Modal zu öffnen (zusätzlich zum Button)
        // Wichtig: `rows` wird initial leer sein, da wir die Daten über `performTableUpdate` laden.
        rows: [] 
    };

    try {
        if (guestListDataTableInstance && typeof guestListDataTableInstance.dispose === 'function') {
            guestListDataTableInstance.dispose();
        }
        guestListDataTableInstance = new mdb.Datatable(tableElement, { columns: columns, rows: [] }, datatableOptions);
        console.log("MDB Datatable Instanz erfolgreich erstellt/aktualisiert:", guestListDataTableInstance);

        // NEU: Event-Listener für rowClicked.mdb.datatable
        tableElement.addEventListener('rowClicked.mdb.datatable', (e) => {
            console.log('rowClicked.mdb.datatable Event:', e); // Untersuche das Event-Objekt!
            
            let rowData;
            rowData = e.row;


            if (rowData && rowData.uid) {
                console.log(`Zeile geklickt für UID: ${rowData.uid}`);
                if (event.target.closest('.guestlist-open-modal-btn')) {
                    console.log("Klick war auf dem 'Öffnen'-Button, Modal wird schon dadurch geöffnet.");
                    return;
                }
                openAccreditationModal(rowData.uid, eventUid);
            } else {
                console.warn("Konnte UID aus rowClicked Event nicht extrahieren. Event-Detail:", e.detail, "RowData:", rowData);
            }
        }); 
    } catch (e) {
        console.error("Fehler bei der Initialisierung von MDB Datatable:", e);
        Notification.error('MDB Fehler', `Datatable konnte nicht initialisiert werden: ${e.message}`);
        guestListDataTableInstance = null; // Sicherstellen, dass es null ist bei Fehler
    }
}

export function initializeGuestListTable() {
    console.log('initializeGuestListTable CALLED');
    const tableElement = document.getElementById('guestlist-table');
    const searchInput = document.getElementById('guestlist-search');
    const sortToggle = document.getElementById('guestlist-sort-toggle');
    const statusContainer = document.getElementById('event-status-container');

    if (!tableElement || !statusContainer) {
        console.warn("Elemente für Gästeliste (#guestlist-table oder #event-status-container) nicht gefunden. Abbruch.");
        return;
    }
    eventUid = statusContainer.dataset.eventUid;
    if (!eventUid) {
        console.warn("Event-UID nicht im #event-status-container gefunden. Abbruch.");
        return;
    }
    if (!ajaxUrl) {
        console.error("AJAX URL (publicrelations_accreditationslist) nicht global definiert! Abbruch.");
        return;
    }
    if (!modalDetailsAjaxUrl) { console.warn("AJAX URL für Modal-Details nicht definiert!"); }
    if (!checkinSubmitAjaxUrl) { console.error("AJAX URL für Checkin-Submit nicht definiert!"); }
    console.log(`Initialisiere Gästeliste für Event-UID: ${eventUid}`);

    initializeMDBInstance(tableElement);

    if (searchInput) {

        searchInput.focus();
        console.log('Initialer Fokus auf Suchfeld gesetzt.');

        let debounceTimer;
        searchInput.addEventListener('input', (e) => {
            clearTimeout(debounceTimer);
            const searchTerm = e.target.value.trim();
            // Die UID-Prüfung bei 'input' ist meist nicht nötig, da QR-Scanner oft mit Enter abschließen
            // und der Nutzer bei normaler Eingabe nicht erwartet, dass nach jeder Zahl ein Modal kommt.
            // Die Enter-Logik ist dafür besser.
            currentSearchTerm = searchTerm;
            debounceTimer = setTimeout(() => {
                console.log('Suche geändert (Input):', currentSearchTerm);
                triggerTableReload(); // Verwende die neue Funktion
            }, 300); // Debounce auf 300ms erhöht
        });

        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const potentialUid = this.value.trim();
                if (/^\d+$/.test(potentialUid) && potentialUid.length > 0) {
                    console.log(`Enter im Suchfeld mit UID: ${potentialUid}. Öffne Modal...`);
                    openAccreditationModal(potentialUid, eventUid);
                    // this.value = ''; // Optional: Suchfeld leeren
                    // currentSearchTerm = ''; // Optional: Suchterm zurücksetzen
                    // triggerTableReload(); // Optional: Tabelle neu laden
                } else {
                    currentSearchTerm = potentialUid; // Normale Suche bei Enter, wenn keine UID
                    triggerTableReload();
                }
            }
        });
    }

    if (sortToggle) {
        sortToggle.addEventListener('change', (e) => {
            currentSortBy = e.target.checked ? 'status' : 'name';
            console.log('Sortierung geändert:', currentSortBy);
            triggerTableReload(); // Verwende die neue Funktion
        });
    }

    if (guestListDataTableInstance) {
        console.log('Starte initiales Laden der Tabellendaten...');
        triggerTableReload(); // Verwende die neue Funktion für initiales Laden
    } else {
        console.error("Initiales Laden übersprungen: MDB Datatable Instanz ist null.");
    }

    tableElement.addEventListener('click', function(event) {
        const button = event.target.closest('.guestlist-open-modal-btn');
        if (button) {
            event.preventDefault();
            const accreditationUid = button.dataset.uid;
            if (accreditationUid) {
                openAccreditationModal(accreditationUid, eventUid);
            }
        }
    });

    const modalElement = document.getElementById('accreditationDetailModal');
    if (modalElement && searchInput) { // searchInput hier auch prüfen
        modalElement.addEventListener('hidden.mdb.modal', function () {
            console.log('Modal geschlossen, setze Fokus auf Suchfeld.');
            searchInput.focus();
            searchInput.select(); // Markiert den gesamten Inhalt des Suchfelds
        });
    }

    console.log('Event Listener für Gästeliste initialisiert.');
}