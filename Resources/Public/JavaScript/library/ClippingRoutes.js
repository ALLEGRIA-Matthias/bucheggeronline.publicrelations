import { Grid, h } from '@ac/libs/grid-js/gridjs.js';
import { deDE } from '@ac/libs/grid-js/l10n/l10n.mjs';
import Notification from '@typo3/backend/notification.js';
import AjaxRequest from '@typo3/core/ajax/ajax-request.js';

class ClippingRoutesDashboard {
    constructor(element) {
        this.element = element;
        this.grid = null;

        this.baseUrl = TYPO3?.settings?.ajaxUrls?.publicrelations_clippingroutes_list;
        this.sendUrlBase = TYPO3?.settings?.ajaxUrls?.publicrelations_clippingroutes_send;

        if (!this.baseUrl || !this.sendUrlBase) {
             console.error('AJAX URLs (list oder send) nicht gefunden.', TYPO3?.settings?.ajaxUrls);
             Notification.error('Fehler', 'AJAX-Routen nicht konfiguriert.');
             return;
        }

        this.initializeGrid();
        this.initializeSearch();
        this.initializeFilterBadges();
        this.fetchData(); // Initial load
    }

    /**
     * Macht die Filter-Badges klickbar
     */
    initializeFilterBadges() {
        // Wir suchen im *ganzen* Dokument, da die Badges außerhalb 
        // des "data-module" Containers liegen könnten.
        const container = document.querySelector('[data-filter-badges]');
        if (!container) return;

        const targetInput = document.querySelector(container.dataset.filterTarget);
        if (!targetInput) return;

        const badges = container.querySelectorAll('.badge[data-filter]');

        badges.forEach(badge => {
            badge.style.cursor = 'pointer'; // Klickbar machen
            badge.addEventListener('click', () => {
                const filterKey = badge.dataset.filter;
                const currentValue = targetInput.value;
                
                let newValue = '';
                if (currentValue.trim() === '') {
                    // Input ist leer
                    newValue = filterKey;
                } else {
                    // Input hat Inhalt, Space hinzufügen
                    newValue = currentValue.trim() + ' ' + filterKey;
                }
                
                targetInput.value = newValue; // Extra Space nach dem Key
                targetInput.focus();
                
                // Bei "..."-Badge, Cursor *vor* das letzte Anführungszeichen setzen
                if (filterKey === '"..."') {
                    const pos = targetInput.value.length - 2; // Position vor dem "
                    targetInput.selectionStart = pos;
                    targetInput.selectionEnd = pos;
                }
                
                // Trigger 'input'-Event, damit das Grid.js die Suche startet
                targetInput.dispatchEvent(new Event('input', { bubbles: true }));
            });
        });
    }

    initializeGrid() {
        const container = this.element;
        if (!container) return;

        this.grid = new Grid({
            columns: [
                { id: 'raw_data', hidden: true },
                { id: 'uid', name: 'ID', width: '60px' },
                { 
                    id: 'keyword', 
                    name: 'Keyword', 
                    width: '200px', 
                    sort: true,
                    // --- NEUE FORMATIERUNG (Send-Icon) ---
                    formatter: (cell, row) => {
                        const route = row.cells[0].data;
                        const iconClass = route.send_immediate == 1 ? 'bi-send-fill text-success' : 'bi-send-slash-fill text-danger';
                        const title = route.send_immediate == 1 ? 'Sofortversand aktiv' : 'Sofortversand inaktiv';
                        
                        return h('div', {}, [
                            h('i', { className: `bi ${iconClass} me-2`, title: title }),
                            route.keyword
                        ]);
                    }
                },
                {
                    id: 'zuordnung',
                    name: 'Zuordnung',
                    width: '200px',
                    sort: false,
                    formatter: (cell, row) => {
                        // route.client und route.project sind jetzt Objekte (oder null)
                        const route = row.cells[0].data;
                        
                        // Client-Logik: short_name ODER name
                        const client = route.client;
                        const clientName = client 
                            ? (client.short_name || client.name) // Deine Logik
                            : 'N/A';
                            
                        // Campaign-Logik: title
                        const campaign = route.project;
                        const campaignName = campaign 
                            ? campaign.title // Deine Logik
                            : 'N/A';
                        
                        return h('div', {}, [
                            h('strong', {}, clientName),
                            h('br', {}),
                            h('small', {}, campaignName)
                        ]);
                    }
                },
                { 
                    id: 'recipients', // <-- ID geändert
                    name: 'Empfänger', // <-- Titel geändert
                    width: '300px',
                    // --- NEUE FORMATIERUNG (TO/CC/BCC) ---
                    formatter: (cell, row) => {
                        const route = row.cells[0].data;
                        const parts = [];
                        
                        if (route.to_emails) {
                            parts.push(h('div', { className: 'd-flex' }, [
                                h('span', { className: 'badge badge-success me-1', style: 'width: 40px;' }, 'TO'), 
                                h('pre', { style: 'font-size: 0.8rem; margin: 0; white-space: pre-wrap;' }, route.to_emails)
                            ]));
                        }
                        if (route.cc_emails) { //
                            parts.push(h('div', { className: 'd-flex mt-1' }, [
                                h('span', { className: 'badge badge-info me-1', style: 'width: 40px;' }, 'CC'), 
                                h('pre', { style: 'font-size: 0.8rem; margin: 0; white-space: pre-wrap;' }, route.cc_emails)
                            ]));
                        }
                        if (route.bcc_emails) { //
                            parts.push(h('div', { className: 'd-flex mt-1' }, [
                                h('span', { className: 'badge badge-secondary me-1', style: 'width: 40px;' }, 'BCC'), 
                                h('pre', { style: 'font-size: 0.8rem; margin: 0; white-space: pre-wrap;' }, route.bcc_emails)
                            ]));
                        }
                        return h('div', {}, parts);
                    }
                },
                { 
                    id: 'unreported_count', 
                    name: 'Neue Clippings?', 
                    width: '80px',
                    formatter: (cell) => {
                        const count = parseInt(cell, 10);
                        const badgeClass = count > 0 ? 'badge-warning' : 'badge-light';
                        return h('span', { className: `badge ${badgeClass}` }, `${count} ungesendet`);
                    }
                },
                {
                    id: 'actions', name: 'Aktion', width: '150px', sort: false,
                    formatter: (cell, row) => {
                        const route = row.cells[0].data;
                        const hasUnreported = route.unreported_count > 0;

                        const buttons = [];

                        // 1. Edit-Button
                        buttons.push(h('a', {
                            className: 'btn btn-default btn-sm',
                            title: 'Route bearbeiten',
                            href: route.edit_url,
                        }, h('i', { className: 'bi bi-pencil' })));

                        // 2. Preview-Button
                        buttons.push(h('a',{ 
                            className: 'btn btn-default btn-sm', 
                            title: 'Vorschau der Mail anzeigen', 
                            href: route.preview_url, 
                            target: '_blank'
                        }, h('i', {className: 'bi bi-eye'})));

                        // --- HIER IST DER NEUE BUTTON ---
                        // 3. Filter-Reports-Button
                        buttons.push(h('a',{ 
                            className: 'btn btn-default btn-sm', 
                            title: 'Alle Reports zu diesem Keyword anzeigen', 
                            href: route.filter_reports_url // <-- Die neue URL
                        }, h('i', {className: 'bi bi-filter-circle'})));
                        // --- ENDE NEUER BUTTON ---

                        // 4. Send-Button
                        buttons.push(h('button',{ 
                            className: 'btn btn-default btn-sm', 
                            title: 'Sendet ALLE ungesendeten Reports für diese Route',
                            disabled: !hasUnreported,
                            onClick: () => this.sendRoute(route.uid) 
                        }, h('i', {className: 'bi bi-send'})));

                        return h('div', { className: 'btn-group btn-group-sm' }, buttons);
                    }
                }
            ],
            data: [],
            sort: true,
            className: { table: 'table table-striped table-hover' },
            search: false,
            pagination: { enabled: true, limit: 50 },
            language: deDE,
        });

        this.grid.render(container);
    }

    initializeSearch() {
        const searchInput = document.querySelector('#gridSearch');
        if (!searchInput) return;

        const initialQuery = this.element.dataset.initialQuery;
        if (initialQuery) {
            searchInput.value = initialQuery;
        }
        searchInput.addEventListener(
            'input',
            this.debounce(() => this.fetchData(), 300)
        );
    }

    async fetchData() {
        if (!this.grid) return;
        this.grid.updateConfig({ data: [] }).forceRender();

        const searchTerm = this.getCurrentSearchTerm();
        const params = new URLSearchParams();
        if (searchTerm) {
            params.append('query', searchTerm);
        }

        const separator = this.baseUrl.includes('?') ? '&' : '?';
        const finalUrl = params.toString() ? `${this.baseUrl}${separator}${params.toString()}` : this.baseUrl;

        try {
            const response = await fetch(finalUrl, {
                method: 'GET',
                headers: { 'Accept': 'application/json' },
            });

            if (!response.ok) {
                 throw new Error(`HTTP error! Status: ${response.status}`);
            }

            const results = await response.json();

            if (results.data) {
                this.grid.updateConfig({
                    data: results.data.map(route => [
                        route, // Full raw object
                        route.uid,
                        route.keyword,
                        route, // Für Zuordnung (nutzt zuordnung_html)
                        route, // Für Empfänger (nutzt das ganze Objekt)
                        route.unreported_count,
                        route // Pass full object for actions
                    ]),
                }).forceRender();
            } else {
                 Notification.warning('Keine Daten', 'Die Anfrage lieferte keine Daten zurück.');
            }

        } catch (error) {
            console.error('Fehler beim Laden der Clipping-Routen:', error);
            Notification.error('Fehler', `Daten konnten nicht geladen werden: ${error.message}`);
            this.grid.updateConfig({ data: [] }).forceRender();
        }
    }

    /**
     * Löst den Sende-AJAX-Aufruf aus
     */
    async sendRoute(routeUid) {
        if (!confirm(`Sollen die ungesendeten Clippings für Route ${routeUid} jetzt versendet werden?`)) {
            return;
        }

        console.log(this.sendUrlBase);

        const finalSendUrl = this.sendUrlBase;

        const formData = new FormData();
        formData.append('routeUid', routeUid);

        try {
            const request = new AjaxRequest(finalSendUrl); 
            const response = await request.post(formData);
            const result = await response.resolve(); 

            if (result.success) {
                Notification.success('Job erstellt', `Versand-Job (ID: ${result.result.job_uid || 'N/A'}) wurde gestartet.`);
                this.fetchData();
            } else {
                Notification.error('Fehler (Senden)', result.message || 'Unbekannter Fehler');
            }
        } catch (error) {
            // --- HIER IST DER FIX (START) ---
            // Diese Logik ist robust, sie kopiert den Stil von messagePreview.js
            let title = 'AJAX Fehler';
            let message = error.message || 'Unbekannter Fehler';

            if (error.response) {
                // Wir HABEN eine Antwort vom Server (z.B. 404, 500)
                try {
                    const errorBody = await error.response.json();
                    title = errorBody.message || title;
                    message = errorBody.errors ? errorBody.errors.join(', ') : title;
                } catch (e) {
                    // Server-Antwort war kein JSON (z.B. HTML-Fehlerseite)
                    message = 'Server-Antwort konnte nicht gelesen werden.';
                }
            } else {
                // Wir haben KEINE Antwort (z.B. Netzwerkfehler oder URL falsch)
                message = `Fehler beim Senden: ${error.message}. Ist die AJAX-Route "publicrelations_clippingroutes_send" korrekt registriert?`;
            }
            
            Notification.error(title, message, 0); // 0 = Dauerhaft
        }
    }

    getCurrentSearchTerm() {
        const searchInput = document.querySelector('#gridSearch');
        return searchInput ? searchInput.value : '';
    }

    debounce(func, delay) {
        let timer;
        return (...args) => {
            clearTimeout(timer);
            timer = setTimeout(() => func.apply(this, args), delay);
        };
    }
}

// Initialisierung
const dashboardElement = document.querySelector('[data-module="ac-pr-clippingroutes-dashboard"]');
if (dashboardElement) {
    new ClippingRoutesDashboard(dashboardElement);
}