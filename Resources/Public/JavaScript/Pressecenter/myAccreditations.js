import * as mdb from '/typo3conf/ext/ac_base/Resources/Public/MDBootstrap/js/mdb.es.min.js';
import {Chart} from '/typo3conf/ext/ac_base/Resources/Public/MDBootstrap/js/chart.es.min.js';

document.addEventListener('DOMContentLoaded', () => {
    /**
     * Initializes MDBootstrap components (Ripple, Tooltip) within a specific container.
     * @param {HTMLElement} container - The parent element to search within. Defaults to the whole document.
     */
    const initializeMdbComponents = (container = document) => {
        // Initialize Ripple effects
        container.querySelectorAll('[data-mdb-ripple-init]').forEach((element) => {
            new mdb.Ripple(element);
        });

        // Initialize Tooltips
        container.querySelectorAll('[data-mdb-tooltip-init]').forEach((element) => {
            new mdb.Tooltip(element);
        });

        // Initialize Dropdowns for action menus
        container.querySelectorAll('[data-mdb-dropdown-init]').forEach((element) => {
            new mdb.Dropdown(element);
        });

        // Initialize Popovers for contact info
        container.querySelectorAll('[data-mdb-popover-init]').forEach((element) => {
            new mdb.Popover(element);
        });
    };

    const container = document.getElementById('accreditations-container');
    if (!container) return;

    const eventUid = container.dataset.eventUid;
    const datatableElement = document.getElementById('datatable-accreditations');
    const toastContainer = document.getElementById('toast-container');
    
    const searchInput = document.getElementById('accreditation-search-input');
    const createButton = document.getElementById('create-new-accreditation-button');
    
    const statusFilterWrapper = document.getElementById('status-filter-wrapper');
    const statusFilterSelect = document.getElementById('accreditation-status-filter');
    const statsGuestsTickets = document.getElementById('stats-guests-tickets');
    const statsFiltered = document.getElementById('stats-filtered');
    const chartCanvas = document.getElementById('accreditation-chart');

    const accreditationModalElement = document.getElementById('accreditationModal');
    const accreditationModal = new mdb.Modal(accreditationModalElement);
    const accreditationForm = document.getElementById('accreditation-form');
    const accreditationFormStatus = accreditationForm.querySelector('[name="status"]');
    const accreditationFormTickets = accreditationForm.querySelector('[name="tickets_approved"]');
    const saveAccreditationButton = document.getElementById('save-accreditation-button');
    const guestInfoElement = document.getElementById('accreditation-guest-info');
    const confirmationCheckboxWrapper = document.getElementById('confirmation-checkbox-wrapper');
    const confirmationCheckbox = document.getElementById('sendConfirmationCheck');
    
    let currentStatusFilter = 'accredited'; // Default-Filter
    let accreditationChart = null;
    let datatableInstance = null;
    let currentData = null;
    let isSelectInitialized = false;

    // Elemente für das "Erstellen"-Modal ---
    const createAccreditationModalElement = document.getElementById('createAccreditationModal');
    const createAccreditationModal = new mdb.Modal(createAccreditationModalElement);
    const createAccreditationModalLabel = document.getElementById('createAccreditationModalLabel');
    
    const createAccStep1Wrapper = document.getElementById('create-acc-step-1');
    const createAccStep2Wrapper = document.getElementById('create-acc-step-2');
    
    const createContactForm = document.getElementById('create-contact-form');
    const createAccreditationForm = document.getElementById('create-accreditation-form');
    
    const createAccContactPreview = document.getElementById('create-acc-contact-preview');
    const createAccNextButton = document.getElementById('create-acc-next-button');

    const createDuplicateCheckArea = document.getElementById('create-duplicate-check-area');
    const createDuplicateListContainer = document.getElementById('create-duplicate-list');
    const createConfirmNoDuplicateCheckbox = document.getElementById('create-confirm-no-duplicate');
    
    let createDuplicateTableInstance = null;
    let isCreateDuplicateCheckShown = false;
    let selectedDuplicateContactUid = null;
    let selectedDuplicateContactName = null;
    let currentDuplicateRows = [];
    let currentNewContactData = null;

    const buildUrl = (action, params = {}) => {
        const url = new URL(window.location.origin);
        url.searchParams.set('type', '2024');
        url.searchParams.set('tx_publicrelations_presscenterajax[controller]', 'Pressecenter\\Ajax');
        url.searchParams.set('tx_publicrelations_presscenterajax[action]', action);
        // Wichtig: 'event' als Argument übergeben
        url.searchParams.set('tx_publicrelations_presscenterajax[event]', eventUid);
        for (const key in params) {
            url.searchParams.set(`tx_publicrelations_presscenterajax[${key}]`, params[key]);
        }
        return url.toString();
    };

    const loadData = async (search = '', statusFilter = 'accredited') => {
        try {
            const url = buildUrl('listAccreditations', { search, statusFilter });
            const response = await fetch(url);
            const data = await response.json();
            currentData = data;
            updateUI(data);
        } catch (error) {
            console.error("Could not fetch accreditation data:", error);
        }
    };

    /**
     * NEUE HILFSFUNKTION (Korrektur)
     * Steuert die Sichtbarkeit und Werte der Ticket-Felder basierend auf dem Status.
     * @param {HTMLFormElement} form - Das Formular (entweder create oder edit)
     */
    const updateAccFormUI = (form) => {
        const status = form.querySelector('[name="status"]').value;
        
        // Finde die Wrapper-Elemente (die .col-md-6 divs)
        // Wir nutzen die IDs, die wir im HTML (Schritt 1) hinzugefügt haben, ODER die IDs des Create-Modals
        const ticketsWishWrapper = form.querySelector('#edit-acc-tickets-wish-wrapper, #create-acc-tickets-wish-wrapper');
        const ticketsApprovedWrapper = form.querySelector('#edit-acc-tickets-approved-wrapper, #create-acc-tickets-approved-wrapper');
        
        const ticketsWishInput = form.querySelector('[name="tickets_wish"]');
        const ticketsApprovedInput = form.querySelector('[name="tickets_approved"]');
        
        // MDB Instanzen holen (nur falls sie schon initialisiert wurden)
        const wishInputInstance = mdb.Input.getInstance(ticketsWishInput.closest('.form-outline'));
        const approvedInputInstance = mdb.Input.getInstance(ticketsApprovedInput.closest('.form-outline'));

        if (status === '0') { // Regel 1: Ausstehend
            ticketsWishWrapper.classList.remove('d-none');
            ticketsApprovedWrapper.classList.add('d-none');
            
            // Setze Werte
            if (parseInt(ticketsWishInput.value, 10) < 1 || ticketsWishInput.value === '') {
                ticketsWishInput.value = 1;
            }
            ticketsApprovedInput.value = 0;

        } else { // Regel 2 & 3: Zugesagt (1) oder Abgesagt (-1)
            ticketsWishWrapper.classList.add('d-none');
            ticketsApprovedWrapper.classList.remove('d-none');
        }
        
        // MDB UI aktualisieren, damit die Labels korrekt schweben
        if (wishInputInstance) wishInputInstance.update();
        if (approvedInputInstance) approvedInputInstance.update();

        // Spezifische Logik für "Bestätigung senden" Checkbox (nur im Create-Modal)
        if (form.id === 'create-accreditation-form') {
            const sendMailLabel = form.querySelector('label[for="sendInvitationCheck"]');
            if (status === '0') {
                sendMailLabel.textContent = 'Einladung direkt nach dem Speichern versenden?';
            } else if (status === '1') {
                sendMailLabel.textContent = 'Bestätigung direkt nach dem Speichern versenden?';
            } else { // -1
                sendMailLabel.textContent = 'Absage direkt nach dem Speichern versenden?';
            }
        }
    };

    /**
     * NEUE HILFSFUNKTION (Korrektur)
     * Wendet die gekoppelte Logik (Status <-> Tickets Approved) an.
     * @param {HTMLFormElement} form - Das Formular (entweder create oder edit)
     * @param {'status' | 'tickets'} changedField - Welches Feld die Änderung ausgelöst hat
     */
    const sanitizeAccFormValues = (form, changedField) => {
        const statusSelect = form.querySelector('[name="status"]');
        const ticketsApprovedInput = form.querySelector('[name="tickets_approved"]');
        const statusSelectInstance = mdb.Select.getInstance(statusSelect);
        const ticketsInputInstance = mdb.Input.getInstance(ticketsApprovedInput.closest('.form-outline'));

        if (changedField === 'tickets') {
            const currentTickets = parseInt(ticketsApprovedInput.value, 10);
            
            // Regel 4: tickets_approved wird auf 0 gesetzt -> status auf -1
            if (currentTickets === 0 && statusSelect.value !== '-1') {
                statusSelectInstance.setValue('-1'); // Ändert auch UI
                if (statusSelectInstance) statusSelectInstance.update();
            } 
            // Regel 5: tickets_approved wird von 0 auf >0 gesetzt -> status auf 1
            else if (currentTickets > 0 && statusSelect.value === '-1') {
                statusSelectInstance.setValue('1');
                if (statusSelectInstance) statusSelectInstance.update();
            }
        }
        
        if (changedField === 'status') {
            const currentStatus = statusSelect.value;
            
            // Regel 3: status auf -1 gesetzt -> tickets_approved auf 0
            if (currentStatus === '-1' && ticketsApprovedInput.value !== '0') {
                ticketsApprovedInput.value = 0;
                if (ticketsInputInstance) ticketsInputInstance.update();
            } 
            // Regel 2: status auf 1 gesetzt -> tickets_approved auf 1 (falls 0)
            else if (currentStatus === '1' && (ticketsApprovedInput.value === '0' || ticketsApprovedInput.value === '')) {
                ticketsApprovedInput.value = 1;
                if (ticketsInputInstance) {
                    ticketsInputInstance.update();
                }
            }
        }

        // Immer die UI aktualisieren, um Felder ein/auszublenden (Regel 1)
        updateAccFormUI(form);
    };

    /**
     * Generiert das passende Status-Badge basierend auf acc.status und acc.invitation_status.
     * @param {object} acc - Das Akkreditierungs-Objekt.
     * @returns {string} - Der HTML-String für das Badge.
     */
    const getStatusBadge = (acc) => {
        switch (acc.status) {
            case 1:
                return '<span class="badge badge-success">akkreditiert</span>';
            case 2:
                return '<span class="badge bg-success text-white"><i class="fas fa-square-check"></i> checked-in</span>';
            case -1:
                return '<span class="badge badge-danger">abgesagt</span>';
            case 99:
                return '<span class="badge bg-danger text-white">fehlerhaft</span>';
            case 9:
                return '<span class="badge badge-info">duplikat</span>';
            case 0:
                switch (acc.invitation_status) {
                    case 0:
                        return '<span class="badge badge-light">vorbereitet</span>';
                    case 1:
                        return '<span class="badge badge-warning">eingeladen</span>';
                    case 2:
                        return '<span class="badge badge-warning">erinnert</span>';
                    case 3:
                        return '<span class="badge badge-warning">ermahnt</span>';
                    default:
                        return '<span class="badge badge-secondary">unbekannt</span>';
                }
            default:
                return '<span class="badge badge-secondary">unbekannt</span>';
        }
    };

    const updateUI = (data) => {
        
        // 1. Statistik-Karten befüllen
        statsGuestsTickets.innerHTML = `
            <div><i class="fas fa-user-group fa-fw me-2"></i>${data.stats.guests} Gäste</div>
            <div><i class="fas fa-ticket fa-fw me-2"></i>${data.stats.tickets} Tickets</div>
        `;
        statsFiltered.innerHTML = `
            <div><i class="fas fa-filter fa-fw me-2"></i>${data.accreditations.length}</div>
        `;

        // 2. Tortendiagramm erstellen oder aktualisieren
        const chartData = {
            type: 'pie',
            data: {
                labels: [data.stats.guests + ' Zusagen', data.stats.pending + ' Ausstehend', data.stats.rejected + ' Absagen'],
                datasets: [{
                    label: 'Akkreditierungs-Status',
                    data: [data.stats.guests, data.stats.pending, data.stats.rejected],
                    backgroundColor: [
                        'rgba(26, 188, 156, 0.5)', // Success
                        'rgba(241, 196, 15, 0.5)',  // Warning
                        'rgba(231, 76, 60, 0.5)'    // Danger
                    ]
                }],
            }
        };

        const chartOptions = {
            options: {
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            boxWidth: 10,
                            font: {
                                size: 12
                            }
                        }
                    }
                },
                // --- HIER IST DIE NEUE LOGIK ---
                onClick: (event, elements) => {
                    // Prüfen, ob ein Segment geklickt wurde UND ob der Filter überhaupt sichtbar ist
                    if (elements.length > 0 && !statusFilterWrapper.classList.contains('d-none')) {
                        const clickedIndex = elements[0].index;
                        
                        // Den Index auf den passenden Wert des Select-Feldes mappen
                        const filterMap = ['accredited', 'pending', 'reject'];
                        const filterValue = filterMap[clickedIndex] ?? null;

                        if (filterValue) {
                            // Den Wert im globalen State speichern
                            currentStatusFilter = filterValue;
                            
                            // Den Wert im MDB-Select-Feld programmatisch setzen
                            const selectInstance = mdb.Select.getInstance(statusFilterSelect);
                            if (selectInstance) {
                                selectInstance.setValue(filterValue);
                            }
                            
                            // Die Daten mit dem neuen Filter neu laden
                            loadData(searchInput.value, currentStatusFilter);
                        }
                    }
                }
            }
        };

        if (accreditationChart) {
            accreditationChart.data = chartData.data;
            accreditationChart.update();
        } else {
            accreditationChart = new Chart(chartCanvas, chartData, chartOptions);
        }

        // 3. Tabellendaten erstellen

        const accessLevel = data.accessLevel;

        // UI-Elemente basierend auf Berechtigungen ein-/ausblenden
        if (accessLevel === 'manage') {
            createButton.classList.remove('d-none');
        }
        if (accessLevel === 'view' || accessLevel === 'edit' || accessLevel === 'manage') {
            statusFilterWrapper.classList.remove('d-none');
    
            // Initialisiere das Select-Feld nur, wenn es noch nicht initialisiert wurde
            if (!isSelectInitialized) {
                new mdb.Select(statusFilterSelect);
                isSelectInitialized = true; // Setze die Flag, damit es nicht nochmal passiert
            }
        }

        // Spalten-Definition dynamisch erstellen ---
        const columns = [
            { label: 'Status', field: 'status', sort: true, width: 130 },
            { label: 'Pax', field: 'tickets', sort: true, width: 60 },
            { label: 'Gast', field: 'guest', sort: true, width: 300 },
            // { label: 'Special', field: 'type', sort: true, width: 100 },
            { label: 'Platzierung', field: 'seats', sort: true, width: 150 },
            { label: 'Hinweise', field: 'notes', sort: true }
        ];
        // Füge die "Funktionen"-Spalte nur bei edit/manage hinzu
        if (accessLevel === 'edit' || accessLevel === 'manage') {
            columns.push({ label: 'Funktionen', field: 'actions', sort: false, width: 200 });
        }
        
        // zuerst sortieren...
        data.accreditations.sort((a, b) => {
            // localeCompare ist die Standard-Methode in JS, um Strings alphabetisch zu vergleichen.
            return a.guestOutput.sortingName.localeCompare(b.guestOutput.sortingName);
        });

        const formattedAccreditations = data.accreditations.map(acc => {
            
            const guest = acc.guestOutput;
            let guestHtmlOutput = '';

            // Prüfe, ob ein vollständiger Name vorhanden ist.
            if (guest.fullName) {
                // Fall 1: Name ist vorhanden und wird die fette erste Zeile.
                guestHtmlOutput = `<div class="fw-bold">${guest.fullName}</div>`;
                
                // Firma und Position als nachfolgende Zeilen hinzufügen, falls vorhanden.
                if (guest.company) {
                    guestHtmlOutput += guest.company;
                }
                if (guest.company && guest.position) {
                    guestHtmlOutput += `<br>`;
                }
                if (guest.position) {
                    guestHtmlOutput += `<small class="text-muted">${guest.position}</small>`;
                }
            } else if (guest.company) {
                // Fall 2: Kein Name, aber eine Firma ist vorhanden. Diese wird zur fetten ersten Zeile.
                guestHtmlOutput = `<div class="fw-bold">${guest.company}</div>`;
                
                // Nur noch die Position als zweite Zeile hinzufügen, falls vorhanden.
                if (guest.position) {
                    guestHtmlOutput += `<br><small class="text-muted">${guest.position}</small>`;
                }
            } else if (guest.position) {
                // Fall 3: Weder Name noch Firma, nur eine Position (unwahrscheinlich, aber sicher ist sicher).
                guestHtmlOutput = `<small class="text-muted">${guest.position}</small>`;
            }

            let notesHtml = '';
            if (acc.notes_select && acc.notes_select.length > 0) {
                acc.notes_select.forEach(note => {
                    notesHtml += `<span class="badge badge-secondary me-1">${note.title}</span>`;
                });
                notesHtml += '<br>';
            }

            if (acc.notes) {
                notesHtml += `${acc.notes}`;
            }

            let seatsHtml = '';
            if (acc.seats) {
                const formattedSeats = acc.seats.replace(/\r?\n/g, '<br>');
                seatsHtml += `<span class="badge badge-info text-start" data-mdb-ripple-init data-mdb-tooltip-init title="Platzierung"><i class="fas fa-chair"></i> ${formattedSeats}</span>`;
            }

            const statusHtml = getStatusBadge(acc);

            const actionsHtml = `
                <div class="btn-group shadow-0" role="group">
                    
                    <button 
                        type="button" 
                        class="btn btn-link px-2" 
                        data-mdb-ripple-init 
                        data-mdb-popover-init 
                        data-mdb-placement="left"
                        data-mdb-trigger="hover click"
                        title="${acc.infoButtonTitle}" 
                        data-mdb-content="${acc.popoverContent}" 
                        data-mdb-html="true" 
                        ${acc.infoButtonDisabled ? 'disabled' : ''}>
                        <i class="fas fa-info-circle fa-lg"></i>
                    </button>

                    <button 
                        type="button" 
                        class="btn btn-link px-2" 
                        data-edit-accreditation-uid="${acc.uid}" 
                        data-mdb-ripple-init 
                        title="Akkreditierung bearbeiten">
                        <i class="fas fa-edit fa-lg"></i>
                    </button>

                    <div class="btn-group" role="group">
                        <button 
                            type="button" 
                            class="btn btn-link px-2" 
                            data-mdb-dropdown-init 
                            data-mdb-ripple-init 
                            data-mdb-container="body" 
                            aria-expanded="false">
                            <i class="fas fa-paper-plane fa-lg"></i>
                        </button>
                        <ul class="dropdown-menu">
                            <li class="${acc.status === 1 ? '' : 'd-none'}"><a class="dropdown-item" href="#" data-mail-action="confirm" data-acc-uid="${acc.uid}"><i class="fas fa-paper-plane fa-fw me-2"></i>Bestätigung versenden</a></li>
                            <li class="${acc.status === 1 ? '' : 'd-none'}"><a class="dropdown-item d-flex justify-content-between align-items-center" href="${acc.links.view_confirmation}" target="_blank"><i class="fas fa-eye fa-fw me-2"></i>Bestätigung ansehen<i class="far fa-copy ms-2" data-copy-link="${acc.links.view_confirmation}"></i></a></li>
                            <li class="${acc.invitation_status === 2 ? '' : 'd-none'}"><a class="dropdown-item" href="#" data-mail-action="push" data-acc-uid="${acc.uid}"><i class="fas fa-paper-plane fa-fw me-2"></i>Pusher senden</a></li>
                            <li class="${acc.invitation_status === 1 ? '' : 'd-none'}"><a class="dropdown-item" href="#" data-mail-action="remind" data-acc-uid="${acc.uid}"><i class="fas fa-paper-plane fa-fw me-2"></i>Erinnerung senden</a></li>
                            <li class="${acc.status === 0 ? '' : 'd-none'}"><a class="dropdown-item d-flex justify-content-between align-items-center" href="${acc.links.view_invitation}" target="_blank"><i class="fas fa-eye fa-fw me-2"></i>Einladung ansehen<i class="far fa-copy ms-2" data-copy-link="${acc.links.view_invitation}"></i></a></li>
                            <li class="${(acc.status === 0 && acc.invitation_status === 0) ? '' : 'd-none'}"><a class="dropdown-item" href="#" data-mail-action="invite" data-acc-uid="${acc.uid}"><i class="fas fa-paper-plane fa-fw me-2"></i>Einladung versenden</a></li>
                            <li class="${acc.status === 0 ? '' : 'd-none'}"><a class="dropdown-item" href="#" data-mail-action="resend" data-acc-uid="${acc.uid}"><i class="fas fa-copy fa-fw me-2"></i>Einladungskopie</a></li>
                        </ul>
                    </div>
                    
                </div>`;

            return {
                status: statusHtml,
                tickets: acc.tickets_approved,
                guest: guestHtmlOutput,
                // type: acc.guestTypeOutput,
                seats: seatsHtml,
                notes: notesHtml,
                actions: actionsHtml
            };
        });

        if (!datatableInstance) {
            datatableInstance = new mdb.Datatable(datatableElement, {
                columns: columns,
                rows: formattedAccreditations
            },
            {
                sm: true,
                striped: true,
                entries: 50,
                pagination: false
            });
        } else {
            datatableInstance.update(
                {
                    rows: formattedAccreditations,
                },
                { forceRerender: true }
            );
        }

        initializeMdbComponents(datatableElement);
    };

    // Behandelt das Senden von Mails
    const handleMailAction = async (uid, mailCode) => {
        try {
            const url = buildUrl('sendAccreditationMail', { accreditation: uid, mailCode: mailCode });
            const response = await fetch(url, { method: 'POST' });
            const result = await response.json();
            if (result.success) {
                showToast(result.message || 'E-Mail erfolgreich versandt.', 'success');
                loadData(searchInput.value, currentStatusFilter); // Neu laden, um Status-Änderungen anzuzeigen
            } else {
                showToast(result.error || 'Fehler beim Mailversand.', 'danger');
            }
        } catch(e) { console.error(e); }
    };

    const openEditAccreditationModal = async (uid) => {
        try {
            // Finde das vollständige Akkreditierungs-Objekt aus den zwischengespeicherten Daten
            const acc = currentData.accreditations.find(a => a.uid === parseInt(uid, 10));
            if (!acc) {
                showToast('Fehler: Akkreditierungsdaten nicht gefunden.', 'danger');
                return;
            }

            // --- HIER IST DIE NEUE LOGIK (Teil 1: Infos anzeigen) ---
            // 1. Info-Bereich im Modal mit den drei Spalten befüllen
    
            // Spalte a: Status-Badge
            document.getElementById('info-status-badge').innerHTML = getStatusBadge(acc);

            // Spalte b: Gast-Daten
            const guest = acc.guestOutput;
            let guestInfoHtml = `<div class="fw-bold">${guest.fullName || guest.company}</div>`;
            if (guest.fullName && guest.company) {
                guestInfoHtml += `<div><small class="text-muted">${guest.company}</small></div>`;
            }
            document.getElementById('info-guest-details').innerHTML = guestInfoHtml;

            // Spalte c: Ticket-Anzahl (nur anzeigen, wenn Tickets > 0)
            const ticketCountElement = document.getElementById('info-ticket-count');
            if (acc.tickets_approved > 0) {
                ticketCountElement.innerHTML = `<span class="badge badge-success">${acc.tickets_approved} Ticket(s)</span>`;
            } else {
                ticketCountElement.innerHTML = ''; // Ansonsten leer lassen
            }

            // 2. Original-Werte für den späteren Vergleich speichern
            accreditationForm.dataset.originalStatus = acc.status;
            accreditationForm.dataset.originalTickets = acc.tickets_approved;

            // 3. Formularfelder befüllen (wie bisher)
            accreditationForm.querySelector('[name="uid"]').value = acc.uid;
            accreditationForm.querySelector('[name="tickets_approved"]').value = acc.tickets_approved;
            accreditationForm.querySelector('[name="tickets_wish"]').value = acc.tickets_wish;
            accreditationForm.querySelector('[name="notes"]').value = acc.notes;
            accreditationForm.querySelector('[name="seats"]').value = acc.seats;
            
            const statusSelect = mdb.Select.getInstance(accreditationForm.querySelector('[name="status"]'));
            statusSelect.setValue(acc.status.toString());
            
            accreditationForm.querySelectorAll('.form-outline').forEach(formOutline => new mdb.Input(formOutline).update());
            
            // 4. Checkbox initial verstecken und zurücksetzen
            confirmationCheckboxWrapper.classList.add('d-none');
            confirmationCheckbox.checked = false;

            // 5. UI-Logik (Felder ein/ausblenden) basierend auf dem geladenen Status ausführen
            updateAccFormUI(accreditationForm);
            
            accreditationModal.show();
        } catch (error) { console.error('Error loading accreditation data:', error); }
    };

    /**
     * Prüft, ob die Bestätigungs-Checkbox angezeigt werden soll.
     */
    const checkConfirmationVisibility = () => {
        const originalStatus = accreditationForm.dataset.originalStatus;
        const currentStatus = accreditationForm.querySelector('[name="status"]').value;
        const originalTickets = accreditationForm.dataset.originalTickets;
        const currentTickets = accreditationForm.querySelector('[name="tickets_approved"]').value;

        // Bedingung 1: Status ändert sich auf "Akkreditiert" (1) oder "Abgesagt" (-1)
        const statusChanged = currentStatus !== originalStatus && (currentStatus === '1' || currentStatus === '-1');
        // Bedingung 2: Ticketanzahl ändert sich
        const ticketsChanged = currentTickets !== originalTickets;

        if (statusChanged || ticketsChanged) {
            confirmationCheckboxWrapper.classList.remove('d-none');
            confirmationCheckbox.checked = true; // Standardmäßig aktivieren
        } else {
            confirmationCheckboxWrapper.classList.add('d-none');
            confirmationCheckbox.checked = false; // Zur Sicherheit deaktivieren
        }
    };

    /**
     * Wird aufgerufen, wenn sich die Ticketanzahl ändert.
     */
    const handleTicketChange = () => {
        // Rufe die neue Sanitize-Funktion auf
        sanitizeAccFormValues(accreditationForm, 'tickets');
        // Rufe immer die Sichtbarkeitsprüfung für die Mail-Checkbox auf
        checkConfirmationVisibility();
    };

    /**
     * Wird aufgerufen, wenn sich der Status ändert.
     */
    const handleStatusChange = () => {
        // Rufe die neue Sanitize-Funktion auf
        sanitizeAccFormValues(accreditationForm, 'status');
        // Rufe immer die Sichtbarkeitsprüfung für die Mail-Checkbox auf
        checkConfirmationVisibility();
    };

    /**
     * NEU: Entfernt alle Validierungsfehler vom "Erstellen"-Formular.
     * @param {HTMLElement} form - Das Formular (Schritt 1 oder 2).
     */
    const createModal_clearValidationErrors = (form) => {
        form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        form.querySelectorAll('.invalid-feedback').forEach(el => el.remove());
    };

    /**
     * NEU: Zeigt Validierungsfehler im "Erstellen"-Formular an.
     * @param {object} errors - Das Fehlerobjekt vom Server.
     * @param {HTMLElement} form - Das Formular (Schritt 1 oder 2).
     */
    const createModal_showValidationErrors = (errors, form) => {
        for (const fieldName in errors) {
            const field = form.querySelector(`[name="${fieldName}"]`);
            if (field) {
                field.classList.add('is-invalid');
                const errorHtml = `<div class="invalid-feedback">${errors[fieldName]}</div>`;
                field.closest('.form-outline, .form-select-wrapper').insertAdjacentHTML('afterend', errorHtml);
            }
        }
    };

    /**
     * NEU: Zeigt die gefundenen Duplikate im "Erstellen"-Modal an.
     */
    const createModal_displayDuplicates = (duplicates) => {
        const allDuplicates = (duplicates.definite || []).concat(duplicates.possible || []);
        const formattedData = allDuplicates.map(dup => ({
            uid: dup.uid,
            name: dup.name || '-',
            email: dup.email || '-',
            company: dup.company || '-',
            type: (duplicates.definite && duplicates.definite.some(d => d.uid === dup.uid)) ? 'Sicher' : 'Möglich'
        }));

        currentDuplicateRows = formattedData;

        if (createDuplicateTableInstance) {
            createDuplicateTableInstance.dispose();
            createDuplicateListContainer.innerHTML = ''; 
        }

        createDuplicateTableInstance = new mdb.Datatable(createDuplicateListContainer, {
            columns: [
                { label: 'ID', field: 'uid', sort: false },
                { label: 'Name', field: 'name', sort: false },
                { label: 'E-Mail', field: 'email', sort: false },
                { label: 'Firma', field: 'company', sort: false },
                { label: 'Typ', field: 'type', sort: false, width: 100 }
            ],
            rows: formattedData
            }, {
                sm: true,
                pagination: false,
                noFoundMessage: 'Keine Duplikate gefunden',
                selectable: true,
                multi: false
        });

        createDuplicateCheckArea.classList.remove('d-none');
        createConfirmNoDuplicateCheckbox.checked = false;
        createAccNextButton.disabled = true;
        isCreateDuplicateCheckShown = true;
    };

    /**
     * NEU: Versteckt den Duplikatsbereich im "Erstellen"-Modal.
     */
    const createModal_hideDuplicates = () => {
        createDuplicateCheckArea.classList.add('d-none');
        if (createDuplicateTableInstance) {
            createDuplicateTableInstance.dispose();
            createDuplicateTableInstance = null;
            createDuplicateListContainer.innerHTML = '';
        }
        isCreateDuplicateCheckShown = false;
        createAccNextButton.disabled = false;
    };

    const handleAccreditationFormSubmit = async () => {
        const formData = new FormData(accreditationForm);
        const accData = Object.fromEntries(formData.entries());
        
        try {
            const updateResponse = await fetch(buildUrl('updateAccreditation'), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ accData })
            });
            const updateResult = await updateResponse.json();

        if (updateResponse.ok && updateResult.success) {
            accreditationModal.hide();
            showToast('Akkreditierung erfolgreich gespeichert.', 'success');
            
            // --- SCHRITT 2: MAIL VERSENDEN (falls nötig) ---
            if (accData.send_confirmation === '1') {
                const newStatus = parseInt(accData.status, 10);
                let mailCode = null;
                
                if (newStatus === 1) mailCode = 'confirm';
                if (newStatus === -1) mailCode = 'reject';

                if (mailCode) {
                    // Starte den zweiten, asynchronen Aufruf
                    fetch(buildUrl('sendAccreditationMail', { accreditation: accData.uid, mailCode: mailCode }), { method: 'POST' })
                        .then(response => response.json())
                        .then(mailResult => {
                            if (mailResult.success) {
                                showToast(mailResult.message, 'info');
                            } else {
                                showToast(mailResult.error, 'danger');
                            }
                        });
                }
            }

            // Lade die Tabelle neu, unabhängig vom Mailversand
            loadData(searchInput.value, currentStatusFilter);

        } else {
                showToast('Fehler beim Speichern.', 'danger');
            }
        } catch(e) { console.error(e); }
    };

    /**
     * NEU: Öffnet das "Neue Akkreditierung" Modal und setzt es auf Schritt 1.
     */
    const openCreateAccreditationModal = () => {
        // 1. Formulare und Status zurücksetzen
        createModal_clearValidationErrors(createContactForm);
        createModal_clearValidationErrors(createAccreditationForm);
        createModal_hideDuplicates();
        currentNewContactData = null;
        
        createContactForm.reset();
        createAccreditationForm.reset();
        
        // 2. Felder in Schritt 1 entsperren
        createContactForm.querySelectorAll('input, select, textarea').forEach(el => el.disabled = false);

        // 3. MDB-Komponenten initialisieren/aktualisieren
        createContactForm.querySelectorAll('.form-outline').forEach(formOutline => new mdb.Input(formOutline).update());
        createAccreditationForm.querySelectorAll('.form-outline').forEach(formOutline => new mdb.Input(formOutline).update());
        
        // MDB-Selects im "Erstellen"-Modal
        const genderSelect = mdb.Select.getInstance(document.getElementById('create-contact-gender'));
        if (genderSelect) genderSelect.setValue(''); else new mdb.Select(document.getElementById('create-contact-gender'));
        
        const statusSelect = mdb.Select.getInstance(document.getElementById('create-accreditation-form').querySelector('[name="status"]'));
        if (statusSelect) statusSelect.setValue('0'); else new mdb.Select(document.getElementById('create-accreditation-form').querySelector('[name="status"]'));
        
        // 4. UI auf Schritt 1 setzen
        createAccStep1Wrapper.classList.remove('d-none');
        createAccStep2Wrapper.classList.add('d-none');
        
        createAccreditationModalLabel.textContent = 'Neue Akkreditierung (Schritt 1/2: Kontakt)';
        createAccNextButton.textContent = 'Weiter / Kontakt prüfen';
        createAccNextButton.disabled = false;
        
        createAccreditationModal.show();
    };

    /**
     * Wechselt das "Erstellen"-Modal zu Schritt 2.
     * @param {boolean} isExistingContact - Zeigt an, ob ein Duplikat ausgewählt wurde.
     */
    const switchToStep2 = (isExistingContact) => {
        // 1. UI wechseln
        createAccStep1Wrapper.classList.add('d-none');
        createAccStep2Wrapper.classList.remove('d-none');
        createModal_hideDuplicates();

        // 2. Formular 1 sperren
        createContactForm.querySelectorAll('input, select, textarea').forEach(el => el.disabled = true);
        
        // 3. MDB-Komponenten in Formular 1 aktualisieren (um "disabled" anzuzeigen)        
        createContactForm.querySelectorAll('.form-outline').forEach(formOutline => {
            const instance = mdb.Input.getInstance(formOutline);
            if(instance) instance.update();
        });

        // 4. MDB-Komponenten in Formular 2 (Step 2) JETZT initialisieren/updaten, da sie sichtbar sind
        createAccreditationForm.querySelectorAll('.form-outline').forEach(formOutline => {
            let instance = mdb.Input.getInstance(formOutline);
            if (instance) {
                instance.update();
            } else {
                new mdb.Input(formOutline); // Initialisieren, falls es noch nicht geschehen ist
            }
        });
        
        // 4. Kontakt-Vorschau füllen
        // if (isExistingContact) {
        //     // Fall A: Vorschau mit dem ausgewählten Duplikat
        //     createAccContactPreview.textContent = selectedDuplicateContactName;
        // } else {
        //     // Fall B: Vorschau mit den Formulardaten (wie bisher)
        //     const firstName = createContactForm.querySelector('[name="first_name"]').value;
        //     const lastName = createContactForm.querySelector('[name="last_name"]').value;
        //     const email = createContactForm.querySelector('[name="email"]').value;
        //     createAccContactPreview.textContent = `${firstName} ${lastName} (${email})`;
        // }

        // 5. Modal-Status aktualisieren
        createAccreditationModalLabel.textContent = 'Neue Akkreditierung (Schritt 2/2: Daten)';
        createAccNextButton.textContent = 'Speichern & Einladen';
        createAccNextButton.disabled = false;
        
        // Checkbox für Einladung standardmäßig aktivieren
        document.getElementById('sendInvitationCheck').checked = true;
    };

    /**
     * NEU: Behandelt den Klick auf den "Weiter / Speichern"-Button.
     * Unterscheidet, ob wir in Schritt 1 oder 2 sind.
     */
    const handleCreateModalSubmit = async () => {
        console.log(selectedDuplicateContactUid);
        // Prüfen, ob Schritt 1 sichtbar (d.h. aktiv) ist
        if (!createAccStep1Wrapper.classList.contains('d-none')) {
            if (selectedDuplicateContactUid > 0) {
                // Fall A: User hat Duplikat ausgewählt -> Direkt zu Schritt 2
                switchToStep2(true); // true = "isExistingContact"
            } else {
                // Fall B: User will neu erstellen -> Check-Logik starten
                await handleCreateStep1Submit();
            }
        } else {
            await handleCreateStep2Submit();
        }
    };
    
    /**
     * NEU: Logik für Schritt 1: Kontakt validieren und Duplikate prüfen.
     */
    const handleCreateStep1Submit = async () => {
        createModal_clearValidationErrors(createContactForm);
        createAccNextButton.disabled = true;

        const formData = new FormData(createContactForm);
        const contactData = Object.fromEntries(formData.entries());
        
        // WICHTIG: Wir brauchen die "tickets_wish" aus Schritt 2 für die checkAccreditationAction
        const accFormData = new FormData(createAccreditationForm);
        const accData = Object.fromEntries(accFormData.entries());

        const forceCreate = createConfirmNoDuplicateCheckbox.checked;

        // Nur wenn Duplikat-Check nicht sichtbar ist oder Checkbox gesetzt ist
        if (!isCreateDuplicateCheckShown || forceCreate) {
            try {
                // HIER: `checkAccreditationAction` wird genutzt (nicht checkContactAction)
                const checkResponse = await fetch(buildUrl('checkAccreditation'), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ contactData, accData, forceCreate }) // Beide Datensätze senden
                });
                
                const checkResult = await checkResponse.json();

                if (checkResult.success === true) {
                    currentNewContactData = contactData;
                    switchToStep2(false);
                    return; // Wichtig: Hier abbrechen
                }

                // Fehlerbehandlung
                if (checkResult.step === 'contact_validation') {
                    createModal_showValidationErrors(checkResult.errors, createContactForm);
                    showToast('Bitte korrigieren Sie die Kontaktfelder.', 'danger');
                } else if (checkResult.step === 'accreditation_validation') {
                    // Validierungsfehler für Akkreditierungsdaten (z.B. Tickets)
                    switchToStep2(false); // Zu Schritt 2 wechseln, um Fehler anzuzeigen
                    createModal_showValidationErrors(checkResult.errors, createAccreditationForm);
                    showToast('Bitte korrigieren Sie die Akkreditierungsdaten.', 'danger');
                } else if (checkResult.step === 'contact_duplicate') {
                    createModal_displayDuplicates(checkResult.duplicates);
                    showToast('Mögliche Duplikate gefunden. Bitte prüfen.', 'warning');
                } else if (checkResult.step === 'already_accredited') {
                    showToast(checkResult.error || 'Dieser Kontakt ist bereits akkreditiert.', 'danger');
                }

            } catch (error) {
                console.error('Fehler bei der Kontaktprüfung:', error);
                showToast('Kontaktprüfung fehlgeschlagen.', 'danger');
            }
        }
        
        createAccNextButton.disabled = false; // Button bei Fehlern wieder freigeben
    };
    
    /**
     * NEU: Logik für Schritt 2: Akkreditierung final erstellen.
     */
    const handleCreateStep2Submit = async () => {
        createAccNextButton.disabled = true;

        // 1. Daten aus beiden Formularen sammeln
        const accFormData = new FormData(createAccreditationForm);
        const accData = Object.fromEntries(accFormData.entries());
        
        // 2. Status und Duplikat-Flag holen
        const forceCreate = createConfirmNoDuplicateCheckbox.checked;
        const sendMail = document.getElementById('sendInvitationCheck').checked;
        
        // 2. Payload intelligent zusammenbauen
        let payload = {
            accData,
            forceCreate
        };
        
        if (selectedDuplicateContactUid) {
            // Fall A: Wir senden NUR die UID des bestehenden Kontakts
            payload.existingContactUid = selectedDuplicateContactUid;
        } else {
            // Fall B: (KORRIGIERT) Wir verwenden die gespeicherten Daten aus Schritt 1
            payload.contactData = currentNewContactData;
        }

        try {
            const saveResponse = await fetch(buildUrl('createAccreditation'), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify(payload)
            });
            
            const saveResult = await saveResponse.json();

            if (saveResponse.ok && saveResult.success) {
                createAccreditationModal.hide();
                showToast('Akkreditierung erfolgreich erstellt.', 'success');
                
                if (sendMail && saveResult.newUid) {
                    const newStatus = parseInt(accData.status, 10);
                    let mailCode = 'invite'; // Default-Wert für Status 0
                    
                    if (newStatus === 1) mailCode = 'confirm'; // <- HIER IST DIE KORREKTUR
                    if (newStatus === -1) mailCode = 'reject';
                    try {
                        const mailResponse = await fetch(buildUrl('sendAccreditationMail', { accreditation: saveResult.newUid, mailCode: mailCode }), { method: 'POST' });
                        const mailResult = await mailResponse.json();
                        if (mailResult.success) {
                            showToast('Email (' + mailCode + ') wurde versandt.', 'info');
                        } else {
                            showToast(mailResult.error || 'Mailversand fehlgeschlagen.', 'danger');
                        }
                    } catch (e) {
                        showToast('Fehler beim Starten des Mailversands.', 'danger');
                    }
                } else if (sendMail) {
                    showToast('Mailversand nicht möglich (Backend lieferte keine UID).', 'warning');
                }

                // Lade die Tabelle neu
                loadData(searchInput.value, currentStatusFilter);

            } else {
                showToast(saveResult.error || 'Speichern fehlgeschlagen.', 'danger');
                if (saveResult.errors) {
                    // Fehler können in Schritt 1 oder 2 auftreten
                    createModal_showValidationErrors(saveResult.errors, createContactForm);
                    createModal_showValidationErrors(saveResult.errors, createAccreditationForm);
                }
            }
            
        } catch (error) {
            console.error('Fehler beim Speichern der Akkreditierung:', error);
            showToast('Verbindung zum Server fehlgeschlagen.', 'danger');
        } finally {
            createAccNextButton.disabled = false;
        }
    };

    /**
     * Zeigt eine Toast-Nachricht an.
     * @param {string} message - Die anzuzeigende Nachricht.
     * @param {string} color - 'success', 'danger', 'warning', 'info'.
     */
    const showToast = (message, color = 'success') => {
        const toastId = `toast-${Date.now()}`;
        const toastHtml = `
            <div class="toast fade" id="${toastId}" role="alert" aria-live="assertive" aria-atomic="true" data-mdb-autohide="true" data-mdb-delay="5000">
                <div class="toast-header text-white bg-${color}">
                    <strong class="me-auto">Hinweis</strong>
                    <button type="button" class="btn-close btn-close-white" data-mdb-dismiss="toast" aria-label="Close"></button>
                </div>
                <div class="toast-body">${message}</div>
            </div>`;
        toastContainer.insertAdjacentHTML('beforeend', toastHtml);
        const toastElement = document.getElementById(toastId);
        const toast = new mdb.Toast(toastElement);
        toast.show();
    };

    accreditationForm.querySelector('[name="status"]').addEventListener('change', checkConfirmationVisibility);
    accreditationForm.querySelector('[name="tickets_approved"]').addEventListener('input', checkConfirmationVisibility);

    // Listener für das Status-Dropdown im Create-Modal
    createAccreditationForm.querySelector('[name="status"]').addEventListener('change', (e) => {
        // Ruft die exakt gleiche Logik auf wie das Edit-Modal
        sanitizeAccFormValues(createAccreditationForm, 'status');
    });

    // Listener für das "Bestätigte Tickets"-Feld im Create-Modal
    createAccreditationForm.querySelector('[name="tickets_approved"]').addEventListener('input', (e) => {
        // Ruft die exakt gleiche Logik auf wie das Edit-Modal
        sanitizeAccFormValues(createAccreditationForm, 'tickets');
    });

    // Listener für das "Gewünschte Tickets"-Feld (nur um <1 zu verhindern)
    createAccreditationForm.querySelector('[name="tickets_wish"]').addEventListener('input', (e) => {
        if (e.target.value === '' || parseInt(e.target.value, 10) < 1) {
            e.target.value = 1;
        }
    });

    // Event Listener erweitern
    datatableElement.addEventListener('click', (e) => {
        const editButton = e.target.closest('[data-edit-accreditation-uid]');
        const mailButton = e.target.closest('[data-mail-action]');
        const copyButton = e.target.closest('[data-copy-link]');

        if (copyButton) {
            e.preventDefault(); // Verhindert, dass der Link aufgerufen wird
            e.stopPropagation(); // Verhindert, dass andere Klick-Events auf dem Link ausgelöst werden
            
            const linkToCopy = copyButton.dataset.copyLink;
            navigator.clipboard.writeText(linkToCopy).then(() => {
                showToast('Link in die Zwischenablage kopiert!', 'success');
            }, (err) => {
                showToast('Kopieren fehlgeschlagen.', 'danger');
                console.error('Could not copy text: ', err);
            });
        } else if (editButton) {
            e.preventDefault();
            openEditAccreditationModal(editButton.dataset.editAccreditationUid);
        } else if (mailButton) {
            e.preventDefault();
            handleMailAction(mailButton.dataset.accUid, mailButton.dataset.mailAction);
        }
    });
    
    accreditationFormTickets.addEventListener('input', handleTicketChange);
    accreditationFormStatus.addEventListener('change', handleStatusChange);
    saveAccreditationButton.addEventListener('click', handleAccreditationFormSubmit);

    // Search input event listener with debounce
    let searchTimeout;
    searchInput.addEventListener('keyup', () => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            loadData(searchInput.value, currentStatusFilter);
        }, 300); // Wait 300ms after user stops typing
    });

    // Click listener for the create button
    createButton.addEventListener('click', () => {
        openCreateAccreditationModal();
    });

    // Haupt-Button (Weiter / Speichern)
    createAccNextButton.addEventListener('click', handleCreateModalSubmit);
    
    // NEU: Listener für die Tabellenauswahl
    createDuplicateListContainer.addEventListener('rowSelected.mdb.datatable', (e) => {

        console.log('selection klappt mal');
        console.log(e.selectedRows);
        if (e.selectedRows.length > 0) {
            // Auswahl getroffen
            selectedDuplicateContactUid = e.selectedRows[0].uid;
            selectedDuplicateContactName = e.selectedRows[0].name;
            
            // "Neu erstellen" deaktivieren und "Weiter" aktivieren
            createConfirmNoDuplicateCheckbox.checked = false;
            createConfirmNoDuplicateCheckbox.disabled = true;
            createAccNextButton.disabled = false;
        } else {
            // Auswahl aufgehoben
            selectedDuplicateContactUid = null;
            selectedDuplicateContactName = null;
            
            // "Neu erstellen" aktivieren und "Weiter" deaktivieren
            createConfirmNoDuplicateCheckbox.disabled = false;
            createAccNextButton.disabled = true;
        }
    });

    // Duplikat-Checkbox
    createConfirmNoDuplicateCheckbox.addEventListener('change', () => {
        if (createConfirmNoDuplicateCheckbox.checked) {
            // "Neu erstellen" ist aktiv
            createAccNextButton.disabled = false;
            // Auswahl in Tabelle aufheben (falls Instanz existiert)
            if (createDuplicateTableInstance) {
                // MDB Datatable hat keine 'deselect' Methode.
                // Wir simulieren es, indem wir die Daten neu laden (was die Auswahl aufhebt)
                // oder (einfacher) wir setzen einfach unsere Status-Variablen.
                selectedDuplicateContactUid = null;
                selectedDuplicateContactName = null;
                // Workaround, um MDB-Auswahl zu löschen: Tabelle neu rendern
                createDuplicateTableInstance.update(
                    { rows: currentDuplicateRows },
                    { forceRerender: true }
                );
            }
        } else {
            // Nichts ist ausgewählt
            createAccNextButton.disabled = true;
        }
    });

    // Modal beim Schließen zurücksetzen
    createAccreditationModalElement.addEventListener('hidden.mdb.modal', () => {
        createModal_hideDuplicates();
        selectedDuplicateContactUid = null;
        selectedDuplicateContactName = null;
        createContactForm.reset();
        createAccreditationForm.reset();
        // Alle Felder in Schritt 1 wieder entsperren
        createContactForm.querySelectorAll('input, select, textarea').forEach(el => el.disabled = false);
    });

    // Eingabefelder überwachen, um Duplikats-Check zurückzusetzen
    const fieldsToMonitor = ['first_name', 'last_name', 'email'];
    fieldsToMonitor.forEach(fieldName => {
        const field = createContactForm.querySelector(`[name="${fieldName}"]`);
        if (field) {
            field.addEventListener('input', () => {
                if (isCreateDuplicateCheckShown) {
                    createModal_hideDuplicates();
                }
            });
        }
    });

    // Event Listener für den Status-Filter
    statusFilterSelect.addEventListener('change', (e) => {
        currentStatusFilter = e.target.value;
        loadData(searchInput.value, currentStatusFilter);
    });

    // Initial data load
    loadData(searchInput.value, currentStatusFilter);
});