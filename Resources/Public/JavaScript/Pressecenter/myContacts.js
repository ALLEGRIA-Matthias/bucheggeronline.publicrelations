import * as mdb from '/typo3conf/ext/ac_base/Resources/Public/MDBootstrap/js/mdb.es.min.js';

document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('contacts-container');
    if (!container) return;

    const clientUid = container.dataset.clientUid;
    const datatableElement = document.getElementById('datatable-contacts');
    const searchInput = document.getElementById('contact-search-input');
    const mailinglistFilter = document.getElementById('mailinglist-filter');
    const totalCountElement = document.getElementById('total-contacts-count');
    const filteredCountElement = document.getElementById('filtered-contacts-count');
    const mailinglistsCountElement = document.getElementById('total-mailinglists-count');
    const contactModalElement = document.getElementById('contactModal');
    const contactModal = new mdb.Modal(contactModalElement);
    const contactForm = document.getElementById('contact-form');
    const saveContactButton = document.getElementById('save-contact-button');
    const toastContainer = document.getElementById('toast-container');
    const createNewContactButton = document.getElementById('create-new-contact-button');
    const duplicateCheckArea = document.getElementById('duplicate-check-area');
    const duplicateListContainer = document.getElementById('duplicate-list');
    const confirmNoDuplicateCheckbox = document.getElementById('confirm-no-duplicate');
    let datatableInstance = null;
    let currentMailinglist = 0;
    let duplicateTableInstance = null;
    let isDuplicateCheckShown = false;

    /**
     * Allgemeine Funktion zum Erstellen von AJAX-URLs.
     * @param {string} action - Der Name der Controller-Action (z.B. 'listContacts').
     * @param {object} params - Zusätzliche URL-Parameter.
     */
    const buildUrl = (action, params = {}) => {
        const url = new URL(window.location.origin);
        url.searchParams.set('type', '2024'); // Unsere AJAX typeNum
        url.searchParams.set('tx_publicrelations_presscenterajax[controller]', 'Pressecenter\\Ajax');
        url.searchParams.set('tx_publicrelations_presscenterajax[action]', action);
        url.searchParams.set('tx_publicrelations_presscenterajax[client]', clientUid);
        for (const key in params) {
            url.searchParams.set(`tx_publicrelations_presscenterajax[${key}]`, params[key]);
        }
        return url.toString();
    };

    const loadData = async (search = '', mailinglist = 0) => {
        try {
            const url = buildUrl('listContacts', { search, mailinglist });
            const response = await fetch(url);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const data = await response.json();
            updateUI(data);
        } catch (error) {
            console.error("Could not fetch contact data:", error);
            datatableElement.innerHTML = `<div class="alert alert-danger">Fehler beim Laden der Kontakte.</div>`;
        }
    };

    const updateUI = (data) => {
        // Zähler aktualisieren
        totalCountElement.textContent = data.totalContacts;
        filteredCountElement.textContent = data.filteredContacts;
        mailinglistsCountElement.textContent = data.mailinglistsCount;
        
        // Mailinglisten-Filter nur beim ersten Mal aufbauen
        if (mailinglistFilter.childElementCount === 0) {
            let filterHtml = '<a href="#" class="list-group-item list-group-item-action small active" data-id="0">Alle anzeigen</a>';
            data.mailinglists.forEach(list => {
                filterHtml += `<a href="#" class="list-group-item list-group-item-action small" data-id="${list.uid}">${list.name}</a>`;
            });
            mailinglistFilter.innerHTML = filterHtml;
            // Event Listeners für die neuen Filter-Links
            mailinglistFilter.querySelectorAll('a').forEach(link => {
                link.addEventListener('click', (e) => {
                    e.preventDefault();
                    mailinglistFilter.querySelector('.active').classList.remove('active');
                    e.target.classList.add('active');
                    currentMailinglist = parseInt(e.target.dataset.id, 10);
                    loadData(searchInput.value, currentMailinglist);
                });
            });
        }
        
        // Datatable initialisieren oder aktualisieren
        const formattedContacts = data.contacts.map(contact => {
            const fullName = [contact.title, contact.first_name, contact.middle_name, contact.last_name, contact.titleSuffix].filter(Boolean).join(' ');
            const companyInfo = `${contact.company}<br><small>${contact.position || ''}</small>`;
            const contactInfo = [contact.email, contact.phone, contact.mobile].filter(Boolean).join('<br>');
            const statusBadge = contact.mailing_exclude 
                ? '<span class="badge badge-danger">Deaktiviert</span>'
                : '<span class="badge badge-success">Aktiv</span>';

            // Name, Firma und Position zusammenfügen
            let nameAndCompanyHtml = `<div>${fullName}</div>`;
            if (contact.company || contact.position) {
                nameAndCompanyHtml += `<small class="text-muted">`;
                if (contact.company) {
                    nameAndCompanyHtml += contact.company;
                }
                if (contact.position) {
                    nameAndCompanyHtml += `<br>${contact.position}`;
                }
                nameAndCompanyHtml += `</small>`;
            }
            
            // Kategorien (Mailinglisten) als Badges formatieren
            let categoriesHtml = '';
            if (contact.categories && contact.categories.length > 0) {
                contact.categories.forEach(cat => {
                    categoriesHtml += `<span class="badge badge-primary me-1">${cat.title}</span>`;
                });
            }

            let toggleButtonHtml;
            if (contact.mailing_exclude) {
                // Kontakt ist gesperrt -> "Aktivieren"-Button anzeigen
                toggleButtonHtml = `
                    <button class="btn btn-link text-success btn-sm" data-toggle-uid="${contact.uid}" data-current-status="1" title="Mailing aktivieren">
                        <i class="fas fa-user-plus text-success"></i>
                    </button>`;
            } else {
                // Kontakt ist aktiv -> "Sperren"-Button anzeigen
                toggleButtonHtml = `
                    <button class="btn btn-link text-danger btn-sm" data-toggle-uid="${contact.uid}" data-current-status="0" title="Mailing sperren">
                        <i class="fas fa-user-minus text-danger"></i>
                    </button>`;
            }

            const actionsHtml = `
                <div class="btn-group shadow-0" role="group" aria-label="Aktionen">
                    <button class="btn btn-link btn-sm" data-edit-uid="${contact.uid}" title="Kontakt bearbeiten">
                        <i class="fas fa-pen-to-square"></i>
                    </button>
                    ${toggleButtonHtml}
                </div>`;

            return {
                // Die Keys müssen mit den `field` Werten in den Spalten übereinstimmen
                status: statusBadge,
                name: nameAndCompanyHtml,
                contact: contactInfo,
                mailinglists: categoriesHtml,
                actions: actionsHtml
            };
        });

        if (datatableInstance) {
            datatableInstance.update(
                {
                    rows: formattedContacts,
                },
                { forceRerender: true }
            );
        } else {
            datatableInstance = new mdb.Datatable(datatableElement, {
                columns: [
                    { label: 'Status', field: 'status', sort: false, width: 100 },
                    { label: 'Name / Firma', field: 'name', sort: true },
                    { label: 'Kontaktdaten', field: 'contact', sort: false },
                    { label: 'Mailinglisten', field: 'mailinglists', sort: false },
                    { label: 'Aktionen', field: 'actions', sort: false, width: 150 }
                ],
                rows: formattedContacts
            },
            {
                sm: true,
                striped: true,
                entries: 25,
                entriesOptions: [10, 25, 50, 100, 250],
                noFoundMessage: 'Keine Kontakte gefunden.',
                // selectable: true,
                // multi: true
            });
        }
    };

    /**
     * Holt die Daten eines Kontakts und füllt das Modal-Formular.
     * @param {string} contactUid - Die UID des zu bearbeitenden Kontakts.
     */
    const openEditModal = async (contactUid) => {
        try {
            const url = buildUrl('editContact', { contact: contactUid });
            const response = await fetch(url);
            const contactData = await response.json();

            if (contactData && !contactData.error) {
                // Formularfelder mit den erhaltenen Daten füllen
                for (const key in contactData) {
                    const field = contactForm.querySelector(`[name="${key}"]`);
                    if (field) {
                        field.value = contactData[key];
                    }
                }
                
                // WICHTIG: MDBootstrap-Komponenten nach dem Füllen per JS manuell aktualisieren
                // 1. Das Select-Feld für die Anrede
                const genderSelect = mdb.Select.getInstance(document.getElementById('contact-gender'));
                if(genderSelect) {
                    genderSelect.setValue(contactData.gender);
                }

                // 2. Alle Text-Inputs, damit die Labels korrekt schweben
                contactForm.querySelectorAll('.form-outline').forEach(formOutline => {
                    new mdb.Input(formOutline).update();
                });

                // Modal-Titel anpassen und Modal anzeigen
                document.getElementById('contactModalLabel').textContent = 'Kontakt bearbeiten';
                contactModal.show();
            } else {
                alert(contactData.error || 'Kontakt konnte nicht geladen werden.');
            }
        } catch (error) {
            console.error('Fehler beim Laden der Kontaktdaten für das Modal:', error);
            alert('Ein Fehler ist aufgetreten.');
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

    /**
     * Entfernt alle Validierungsfehler vom Formular.
     */
    const clearValidationErrors = () => {
        contactForm.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        contactForm.querySelectorAll('.invalid-feedback').forEach(el => el.remove());
    };

    /**
     * Zeigt Validierungsfehler im Formular an.
     * @param {object} errors - Das Fehlerobjekt vom Server.
     */
    const showValidationErrors = (errors) => {
        for (const fieldName in errors) {
            const field = contactForm.querySelector(`[name="${fieldName}"]`);
            if (field) {
                field.classList.add('is-invalid');
                const errorHtml = `<div class="invalid-feedback">${errors[fieldName]}</div>`;
                field.closest('.form-outline, .form-select-wrapper').insertAdjacentHTML('afterend', errorHtml);
            }
        }
    };

    /**
     * Zeigt die gefundenen Duplikate in einer MDB Datatable an.
     */
    const displayDuplicates = (duplicates) => {
        const allDuplicates = (duplicates.definite || []).concat(duplicates.possible || []);
        // Formatierung für MDB Datatable anpassen (Objekte statt Arrays)
        const formattedData = allDuplicates.map(dup => ({
            name: dup.name || '-',
            email: dup.email || '-',
            company: dup.company || '-',
            // Aktion als HTML-String direkt hier bauen
            action: `<button class="btn btn-sm btn-link p-0" data-edit-duplicate-uid="${dup.uid}">Bearbeiten</button> (${(duplicates.definite && duplicates.definite.some(d => d.uid === dup.uid)) ? 'Sicher' : 'Möglich'})`
        }));

        // Alte Instanz zerstören, falls vorhanden
        if (duplicateTableInstance) {
            duplicateTableInstance.dispose();
            duplicateListContainer.innerHTML = ''; // Container leeren
        }

        // Neue MDB Datatable initialisieren
        duplicateTableInstance = new mdb.Datatable(duplicateListContainer, {
            columns: [
                { label: 'Name', field: 'name', sort: false },
                { label: 'E-Mail', field: 'email', sort: false },
                { label: 'Firma', field: 'company', sort: false },
                { label: 'Aktion/Typ', field: 'action', sort: false, width: 150 }
            ],
            rows: formattedData
            // Optionen für die kleine Tabelle
            }, {
                sm: true, // Kompakter
                pagination: false, // Keine Seiten
                noFoundMessage: 'Keine Duplikate gefunden' // Text falls leer
        });

        duplicateCheckArea.classList.remove('d-none');
        confirmNoDuplicateCheckbox.checked = false;
        saveContactButton.disabled = true;
        isDuplicateCheckShown = true;
    };

    /**
     * Versteckt den Duplikatsbereich und zerstört die Tabelle.
     */
    const hideDuplicates = () => {
        duplicateCheckArea.classList.add('d-none');
        if (duplicateTableInstance) {
            duplicateTableInstance.dispose();
            duplicateTableInstance = null;
            duplicateListContainer.innerHTML = '';
        }
        isDuplicateCheckShown = false;
        // Speichern nur freigeben, wenn Checkbox nicht sichtbar ist (Checkbox steuert es sonst)
        if (!confirmNoDuplicateCheckbox.checked) {
            saveContactButton.disabled = false;
        }
    };

    /**
     * Die Funktion zum Speichern des Formulars.
     */
    const handleFormSubmit = async () => {
        clearValidationErrors();
        saveContactButton.disabled = true; // Button deaktivieren, um Doppelklicks zu verhindern

        const formData = new FormData(contactForm);
        const contactData = Object.fromEntries(formData.entries());
        const isUpdate = contactData.uid && contactData.uid > 0;

        // --- NEUER ZWISCHENSCHRITT: VALIDIERUNG & DUPLIKATSPRÜFUNG ---
        // Nur bei NEUEN Kontakten UND wenn der Duplikatscheck noch nicht angezeigt wird ODER die Checkbox nicht aktiv ist
        if (!isUpdate && (!isDuplicateCheckShown || !confirmNoDuplicateCheckbox.checked)) {
            try {
                const checkResponse = await fetch(buildUrl('checkContact'), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ contactData })
                });
                const checkResult = await checkResponse.json();

                if (!checkResult.success) {
                    if (checkResult.step === 'validation') {
                        showValidationErrors(checkResult.errors);
                        const firstErrorMessage = Object.values(checkResult.errors)[0];
                        showToast(firstErrorMessage, 'danger');
                    } else if (checkResult.step === 'duplicate') {
                        displayDuplicates(checkResult.duplicates);
                        // Breche hier ab, warte auf Bestätigung durch User
                        return; // Wichtig: return hier!
                    }
                    saveContactButton.disabled = false; // Button wieder aktivieren bei Fehlern
                    return; // Wichtig: return hier!
                }
                // Wenn checkResult.success === true, fahre unten fort zum Speichern
                
            } catch (error) {
                console.error('Fehler bei der Kontaktprüfung:', error);
                showToast('Kontaktprüfung fehlgeschlagen.', 'danger');
                saveContactButton.disabled = false;
                return; // Wichtig: return hier!
            }
        }

        // Wenn wir hier ankommen, ist alles validiert und (falls nötig) bestätigt.
        // Jetzt speichern wir (wie bisher).
        const saveAction = isUpdate ? 'updateContact' : 'createContact';
        
        try {
            const saveResponse = await fetch(buildUrl(saveAction), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ contactData })
            });
            const saveResult = await saveResponse.json();

            if (saveResponse.ok && saveResult.success) {
                contactModal.hide();
                hideDuplicates();
                showToast('Kontakt erfolgreich gespeichert.', 'success');
                loadData(searchInput.value, currentMailinglist);
            } else {
                // Fehler (Validierung oder Serverfehler)
                if (saveResponse.status === 422 && saveResult.errors) {
                    showValidationErrors(saveResult.errors);
                    const firstErrorMessage = Object.values(saveResult.errors)[0] || 'Bitte korrigieren Sie die markierten Felder.';
                    showToast(firstErrorMessage, 'danger');
                } else {
                    showToast(saveResult.error || 'Ein unbekannter Fehler ist aufgetreten.', 'danger');
                }
            }
        } catch (error) {
            console.error('Fehler beim Speichern:', error);
            showToast('Verbindung zum Server fehlgeschlagen.', 'danger');
        } finally {
            saveContactButton.disabled = false; // Button wieder aktivieren
        }
    };
    
    // Event Listener für die Bestätigungs-Checkbox
    confirmNoDuplicateCheckbox.addEventListener('change', () => {
        saveContactButton.disabled = !confirmNoDuplicateCheckbox.checked;
    });
    
    // Event Listener für Klicks auf die Duplikatsliste (Bearbeiten-Button)
    duplicateListContainer.addEventListener('click', (e) => {
        const editDuplicateButton = e.target.closest('button[data-edit-duplicate-uid]');
        if (editDuplicateButton) {
            e.preventDefault();
            const uidToEdit = editDuplicateButton.dataset.editDuplicateUid;
            contactModal.hide(); // Aktuelles Modal schließen
            hideDuplicates();    // Duplikatsbereich zurücksetzen
            // Wichtig: Kurze Verzögerung, damit das Schließen abgeschlossen ist, bevor das neue Modal öffnet
            setTimeout(() => {
                openEditModal(uidToEdit); // Edit-Modal für das Duplikat öffnen
            }, 300); 
        }
    });
    
    // Funktion, die den Klick auf den Toggle-Button behandelt
    const handleToggleClick = async (contactUid, currentStatus) => {
        // Der neue Status ist das Gegenteil des aktuellen Status (0 -> 1, 1 -> 0)
        const newStatus = currentStatus === '1' ? 0 : 1; 

        const contactData = {
            uid: contactUid,
            mailing_exclude: newStatus
        };

        try {
            const response = await fetch(buildUrl('updateContact'), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ contactData }) // Wir senden genau die Struktur, die updateContactAction erwartet
            });
            const result = await response.json();

            if (response.ok && result.success) {
                showToast(newStatus === 1 ? 'Kontakt für Mailings gesperrt.' : 'Kontakt für Mailings aktiviert.', 'info');
                loadData(searchInput.value, currentMailinglist); // Daten neu laden, um die Ansicht zu aktualisieren
            } else {
                showToast(result.error || 'Aktion fehlgeschlagen.', 'danger');
            }
        } catch (error) {
            console.error('Fehler beim Umschalten des Mailing-Status:', error);
            showToast('Verbindung zum Server fehlgeschlagen.', 'danger');
        }
    };

    const openCreateModal = () => {
        clearValidationErrors();
        contactForm.reset(); // Formular komplett leeren
        contactForm.querySelector('[name="uid"]').value = '';
        
        // MDB Select für 'gender' zurücksetzen
        const genderSelect = mdb.Select.getInstance(document.getElementById('contact-gender'));
        if(genderSelect) {
            genderSelect.setValue(''); // Leeren Wert setzen
        }

        // Alle Input-Labels zurücksetzen
        contactForm.querySelectorAll('.form-outline').forEach(formOutline => {
            new mdb.Input(formOutline).update();
        });

        document.getElementById('contactModalLabel').textContent = 'Neuen Kontakt erstellen';
        contactModal.show();
    };

    // Dies nennt sich "Event Delegation" und ist sehr performant.
    datatableElement.addEventListener('click', (e) => {
        const editButton = e.target.closest('button[data-edit-uid]');
        const toggleButton = e.target.closest('button[data-toggle-uid]');
        
        if (editButton) {
            e.preventDefault();
            openEditModal(editButton.dataset.editUid);
        } else if (toggleButton) {
            e.preventDefault();
            handleToggleClick(toggleButton.dataset.toggleUid, toggleButton.dataset.currentStatus);
        }
    });

    // Event Listener für die Suche (mit debounce)
    let searchTimeout;
    searchInput.addEventListener('keyup', () => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            loadData(searchInput.value, currentMailinglist);
        }, 300); // 300ms warten nach der letzten Eingabe
    });
    
    // Event Listener für den Speicher-Button im Modal
    saveContactButton.addEventListener('click', handleFormSubmit);

    // Event Listener für den Kontakt-erstellen-Button
    createNewContactButton.addEventListener('click', openCreateModal);

    // Event Listener für das Schließen des Modals
    contactModalElement.addEventListener('hidden.mdb.modal', () => {
        // Wenn das Modal geschlossen wird (egal ob per Klick oder nach Speichern),
        // verstecke den Duplikatsbereich.
        hideDuplicates();
        contactForm.querySelector('[name="uid"]').value = '';
    });

    // Event Listener für Formularänderungen ---
    // Liste aller Felder, die den Duplikatscheck zurücksetzen sollen
    const fieldsToMonitor = ['first_name', 'last_name', 'email'];
    
    fieldsToMonitor.forEach(fieldName => {
        const field = contactForm.querySelector(`[name="${fieldName}"]`);
        if (field) {
            field.addEventListener('input', () => {
                // Wenn der Duplikatscheck gerade angezeigt wird...
                if (isDuplicateCheckShown) {
                    // ...verstecke ihn und gib den Speicherbutton wieder frei.
                    hideDuplicates();
                }
            });
        }
    });

    // Initiales Laden der Daten
    loadData();
});