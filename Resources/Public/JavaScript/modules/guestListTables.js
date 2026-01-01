// js/modules/guestListTable.js

import * as mdb from '/typo3conf/ext/ac_base/Resources/Public/MDBootstrap/js/mdb.es.min.js'; // Dein MDB Import
import AjaxRequest from '@typo3/core/ajax/ajax-request.js';
import Notification from '@typo3/backend/notification.js';

// Globale (Modul-Scope) Variablen
let guestListDataTableInstance = null; // Wird die MDB Datatable Instanz halten
let currentSortBy = 'name';
let currentSearchTerm = '';
let eventUid = null; // Wird aus dem DOM gelesen

// Die AJAX-URL aus den TYPO3-Settings
const ajaxUrl = TYPO3.settings.ajaxUrls.publicrelations_accreditationslist;

// Hilfsfunktion für Status-Badges (wie gehabt)
function getStatusBadge(statusKey) {
    switch (statusKey) {
        case 'akkreditiert':
            return '<span class="badge badge-info">akkreditiert</span>';
        case 'teilweise_eingecheckt':
            return '<span class="badge badge-warning text-dark">Teil-Checkin</span>';
        case 'voll_eingecheckt':
            return '<span class="badge badge-success">Eingecheckt</span>';
        default:
            return '<span class="badge badge-danger">Unbekannt</span>';
    }
}

// Funktion zum Laden der Daten und Aktualisieren der MDB Datatable
async function performTableUpdate() {
    if (!eventUid) {
        console.error("performTableUpdate: Event-UID nicht gesetzt. Abbruch.");
        return;
    }
    if (!ajaxUrl) {
        console.error("performTableUpdate: AJAX-URL (publicrelations_accreditationslist) nicht definiert. Abbruch.");
        return;
    }
    if (!guestListDataTableInstance) {
        console.error("performTableUpdate: MDB Datatable Instanz nicht initialisiert. Abbruch.");
        // Hier könntest du versuchen, sie neu zu initialisieren, falls das gewünscht ist,
        // aber idealerweise ist sie schon da.
        return;
    }

    // Ladeindikator der Datatable anzeigen (falls MDB das so unterstützt)
    // Beispiel: guestListDataTableInstance.update({loading: true}); // Syntax prüfen!
    // Oft gibt es keine explizite Loading-Option bei manuellem Update,
    // du könntest einen eigenen Lade-Spinner über die Tabelle legen.

    console.log(`Lade Daten für Event ${eventUid}, Suche: "${currentSearchTerm}", Sortierung: "${currentSortBy}"`);

    try {
        const request = new AjaxRequest(ajaxUrl)
            .withQueryArguments({
                'tx_publicrelations_eventcenter[event]': eventUid,
                'searchTerm': currentSearchTerm,
                'sortBy': currentSortBy
            });

        const response = await request.get();
        const jsonData = await response.resolve();

        console.log("JSON-Antwort vom Server:", jsonData);

        if (jsonData.error) {
            console.error("Fehler vom Server:", jsonData.error);
            TYPO3.Notification.error('Fehler beim Laden', jsonData.error); // TYPO3 Backend Notification
            // Leere Tabelle oder Fehlermeldung in Tabelle anzeigen
            guestListDataTableInstance.update({ rows: [] }); // Leert die Tabelle
            document.getElementById('guestlist-count').textContent = '0';
            return;
        }

        if (jsonData.data) {
            // Transformiere die Serverdaten in das Format, das MDB Datatable für `rows` erwartet.
            // Dein PHP-Controller sollte `jsonData.data` als Array von Objekten liefern.
            // Jedes Objekt repräsentiert eine Zeile.
            const mappedRows = jsonData.data.map(item => ({
                // Die Schlüssel hier müssen den `field`-Namen entsprechen,
                // die du beim Initialisieren der Datatable in `columns` definierst (falls du das tust),
                // ODER MDB Datatable erwartet ein Array von Werten in der richtigen Spaltenreihenfolge.
                // Basierend auf deinem v11-Beispiel: Wir erstellen die HTML-Strings direkt.
                // MDB Datatable erlaubt oft, dass `rows` ein Array von Objekten ist,
                // und die `columns` Definition sagt dann, welche `field` (Eigenschaft des Objekts)
                // in welche Spalte kommt und wie sie formatiert wird.
                // Alternative: `rows` ist ein Array von Arrays (jedes innere Array ist eine Zeile mit Spaltenwerten).

                // Ansatz 1: Datenobjekt für jede Zeile (flexibler mit `columns`-Definition)
                uid: item.uid, // Reine Daten, um sie später zu nutzen
                aktion_html: `<button class="btn btn-sm btn-outline-primary guestlist-open-modal-btn" data-uid="${item.uid}" data-mdb-toggle="modal" data-mdb-target="#accreditationDetailModal">Öffnen</button>`,
                status_html: getStatusBadge(item.checkin_status_key),
                tickets_display: `<div class="text-center">${item.tickets_prepared}</div>`,
                name_firma_html: `<strong>${item.name_primary}</strong><br><small class="text-muted">${item.name_secondary || ''}</small>`,
                notizen_html: item.notes_html
                // Du könntest hier auch die Rohdaten behalten und das Rendering in der `columns` Def. machen
            }));

            console.log("Aktualisiere MDB Datatable mit", mappedRows.length, "Zeilen.");
            guestListDataTableInstance.update(
                { rows: mappedRows },
                // { loading: false } // Ladeindikator ausblenden (Syntax prüfen!)
            );
            document.getElementById('guestlist-count').textContent = jsonData.recordsFiltered || '0';
        } else {
            console.warn("Keine 'data' Eigenschaft in der JSON-Antwort gefunden.");
            guestListDataTableInstance.update({ rows: [] });
            document.getElementById('guestlist-count').textContent = '0';
        }

    } catch (error) {
        console.error("Catch-Block: Fehler beim AJAX-Request oder Verarbeiten der Antwort:", error);
        TYPO3.Notification.error('Schwerer Fehler', 'Daten konnten nicht geladen werden. Details siehe Konsole.');
        if (guestListDataTableInstance) {
            guestListDataTableInstance.update({ rows: [] }); // Bei Fehler Tabelle leeren
        }
        document.getElementById('guestlist-count').textContent = '0';
    }
}

// Diese Funktion initialisiert die MDB Datatable Instanz
function initializeMDBInstance(tableElement) {
    // Definiere die Spalten so, dass sie zu den Feldern passen, die du in `mappedRows` erstellst
    const columns = [
        // { label: 'Aktion', field: 'aktion_html', sort: false, width: 80 }, // `field` matcht Schlüssel in mappedRows
        { label: 'Status', field: 'status_html', width: 120 },
        { label: 'Pax', field: 'tickets_display', class: 'text-center', width: 30 }, // `class` für CSS-Klasse der Zelle/Spalte
        { label: 'Gästedaten', field: 'name_firma_html' },
        { label: 'Notizen', field: 'notizen_html' },
        { label: 'UID', field: 'uid', hidden: true } // UID versteckt, aber im Datenmodell vorhanden
    ];

    // MDB Datatable Optionen (siehe Doku für deine MDB Version!)
    const datatableOptions = {
        // Ladeoptionen (loading, loadingMessage etc. wie in deinem v11 Beispiel)
        loading: true,
        loadingMessage: 'Lade Akkreditierungen...',
        hover: true,
        pagination: false, // Du wolltest keine Paginierung
        fixedHeader: true, // Kann nützlich sein
        noFoundMessage: 'Keine Akkreditierungen gefunden.',
        sm: true, // Kleinere Tabelle
        striped: true,
        clickableRows: true
    };

    try {
        if (guestListDataTableInstance && typeof guestListDataTableInstance.dispose === 'function') {
            guestListDataTableInstance.dispose(); // Alte Instanz sicher entfernen
        }
        guestListDataTableInstance = new mdb.Datatable(tableElement, { columns }, datatableOptions);
        console.log("MDB Datatable Instanz erfolgreich erstellt/aktualisiert:", guestListDataTableInstance);
    } catch (e) {
        console.error("Fehler bei der Initialisierung von MDB Datatable:", e);
        TYPO3.Notification.error('MDB Fehler', 'Datatable konnte nicht initialisiert werden.');
    }
}


export function initializeGuestListTable() {
    console.log('initializeGuestListTable CALLED');
    const tableElement = document.getElementById('guestlist-table');
    const searchInput = document.getElementById('guestlist-search');
    const sortToggle = document.getElementById('guestlist-sort-toggle');
    const statusContainer = document.getElementById('event-status-container');

    if (!tableElement || !statusContainer) {
        console.warn("Elemente für Gästeliste (#guestlist-table oder #event-status-container) nicht gefunden.");
        return;
    }

    eventUid = statusContainer.dataset.eventUid;
    if (!eventUid) {
        console.warn("Event-UID nicht im #event-status-container gefunden.");
        return;
    }
    if (!ajaxUrl) {
        console.error("AJAX URL (publicrelations_accreditationslist) nicht global definiert!");
        return;
    }
    console.log(`Initialisiere Gästeliste für Event-UID: ${eventUid}`);

    // MDB Datatable Instanz initialisieren
    initializeMDBInstance(tableElement);

    // Event Listeners für Suche und Sortierung
    if (searchInput) {
        let debounceTimer;
        searchInput.addEventListener('input', (e) => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                currentSearchTerm = e.target.value;
                console.log('Suche geändert:', currentSearchTerm);
                performTableUpdate(); // Lade Daten basierend auf neuer Suche
            }, 300);
        });
    }

    if (sortToggle) {
        sortToggle.addEventListener('change', (e) => {
            currentSortBy = e.target.checked ? 'status' : 'name';
            console.log('Sortierung geändert:', currentSortBy);
            performTableUpdate(); // Lade Daten basierend auf neuer Sortierung
        });
    }

    // Initiales Laden der Daten für die Tabelle
    if (guestListDataTableInstance) { // Nur wenn Instanz erfolgreich erstellt wurde
        console.log('Starte initiales Laden der Tabellendaten...');
        performTableUpdate();
    } else {
        console.error("Initiales Laden übersprungen: MDB Datatable Instanz konnte nicht erstellt werden.");
    }


    // Event Delegation für Modal-Buttons (bleibt gleich)
    tableElement.addEventListener('click', function(event) {
        const button = event.target.closest('.guestlist-open-modal-btn');
        if (button) {
            const accreditationUid = button.dataset.uid;
            console.log("Modal öffnen für UID (Platzhalter):", accreditationUid);
            // TODO: Logik zum Laden und Anzeigen des Modals hier
            // const modalBody = document.getElementById('accreditationDetailModalBody');
            // modalBody.innerHTML = 'Lade Details für Akkreditierung ' + accreditationUid + '...';
            // const modalInstance = mdb.Modal.getInstance(document.getElementById('accreditationDetailModal')) || new mdb.Modal(document.getElementById('accreditationDetailModal'));
            // modalInstance.show();
        }
    });
}