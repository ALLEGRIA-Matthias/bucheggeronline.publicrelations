import { Grid, h } from '@ac/libs/grid-js/gridjs.js';
import { deDE } from '@ac/libs/grid-js/l10n/l10n.mjs';
import Notification from '@typo3/backend/notification.js';
import AjaxRequest from '@typo3/core/ajax/ajax-request.js';

class ReportsDashboard {
    constructor(element) {
        this.element = element;
        this.grid = null;

        // *** HIER: Den neuen AJAX-Routen-Namen eintragen ***
        this.baseUrl = TYPO3?.settings?.ajaxUrls?.publicrelations_reports_list; 

        if (!this.baseUrl) {
             console.error('AJAX URL for route "publicrelations_reports_list" not found.');
             Notification.error('Fehler', 'AJAX-Route (publicrelations_reports_list) nicht konfiguriert.');
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
                    id: 'type',
                    name: 'Typ',
                    width: '100px',
                    formatter: (cell) => {
                        let badgeClass = 'badge-light';
                        if (cell === 'clipping') badgeClass = 'badge-info';
                        if (cell === 'pr') badgeClass = 'badge-primary';
                        if (cell === 'social_media') badgeClass = 'badge-warning';
                        return h('span', { className: `badge ${badgeClass}` }, cell);
                    }
                },
                { 
                    id: 'date', 
                    name: 'Datum',
                    width: '150px',
                    sort: {
                        // --- NEU: Sortier-Logik ---
                        compare: (a, b) => {
                            // a und b sind das {string, timestamp} Objekt
                            if (a.timestamp > b.timestamp) {
                                return 1;
                            } else if (a.timestamp < b.timestamp) {
                                return -1;
                            }
                            return 0;
                        }
                    },
                    formatter: (cell) => {
                        // cell ist das {string, timestamp} Objekt
                        return cell.string; // Zeigt den vorformatierten String
                    }
                },
                { 
                    id: 'status',
                    name: 'Status', 
                    width: '120px',
                    formatter: (cell, row) => {
                        const report = row.cells[0].data;
                        const status = report.status || 'N/A';
                        const reported = report.reported == 1;

                        // --- STATUS-ICON (wie gewünscht) ---
                        const iconClass = reported ? 'bi-send-fill text-success' : 'bi-send text-secondary small';
                        const iconTitle = reported ? 'Gesendet' : 'Nicht gesendet';
                        
                        let statusClass = 'badge-secondary';
                        if (status === 'clipped') statusClass = 'badge-success text-dark';
                        if (status === 'clipping_reported') statusClass = 'badge-warning';
                        
                        return h('div', {className: 'd-flex align-items-center'}, [
                             h('span', { className: `badge ${statusClass}` }, status),
                             h('i', { className: `bi ${iconClass} ms-2`, title: iconTitle, style: 'font-size: 0.8 rem;' })
                        ]);
                    }
                },
                {
                    id: 'zuordnung',
                    name: 'Zuordnung',
                    width: '200px',
                    sort: false,
                    formatter: (cell, row) => {
                        const report = row.cells[0].data;
                        const client = report.client;
                        const clientName = client ? (client.short_name || client.name) : 'N/A';
                        const campaign = report.campaign;
                        const campaignName = campaign ? campaign.title : 'N/A';
                        const clippingRoute = report.clippingroute;
                        const clippingRouteName = clippingRoute ? clippingRoute.keyword : 'N/A';
                        
                        return h('div', {}, [
                            h('a', {
                                href: report.keyword_filter_url,
                                title: clippingRouteName,
                                class: 'small'
                            }, report.keyword_string),
                            h('br', {}),
                            h('strong', {}, clientName),
                            h('br', {}),
                            h('small', {}, campaignName)
                        ]);
                    }
                },
                { 
                    id: 'title', 
                    name: 'Infos', 
                    width: '350px', 
                    sort: false,
                    formatter: (cell, row) => {
                        const report = row.cells[0].data;
                        
                        // Formatiere Reichweite und Werbewert
                        const reach = new Intl.NumberFormat('de-DE').format(report.reach || 0);
                        const adValue = new Intl.NumberFormat('de-DE', { style: 'currency', currency: 'EUR', minimumFractionDigits: 0 }).format(report.ad_value || 0);

                        return h('div', {}, [
                            h('div', { class: 'small' }, [
                                h('span', {}, report.medium || 'N/A'),
                                report.department ? h('span', {}, ` | ${report.department}`) : ''
                            ]),
                            h('strong', {}, report.title || 'N/A'),
                            h('div', { class: 'small text-muted' }, [
                                h('span', {}, `Reichweite: ${reach}`),
                                h('span', {}, ` | Werbewert: ${adValue}`)
                            ])
                        ]);
                    }
                },
                {
                    id: 'files_links',
                    name: 'Dateien / Links',
                    width: '300px',
                    sort: false,
                    formatter: (cell, row) => {
                        const report = row.cells[0].data;
                        const files = report.files_data || [];
                        const links = report.links_data || [];
                        const elements = [];

                        // 1. Echte Dateien (FAL)
                        files.forEach(file => {
                            const title = file.title || file.name; // Titel ODER Dateiname
                            elements.push(h('div', {className: 'mb-1'}, 
                                h('a', {
                                    href: file.public_url, // <-- public_url aus Query
                                    target: '_blank',
                                    title: title
                                }, 
                                h('i', {className: 'bi bi-file-earmark-arrow-down me-1'}),
                                title + ` (${file.extension.toUpperCase()})`
                            )));
                        });
                        
                        // 2. AV-Links (IRRE)
                        links.forEach(link => {
                            elements.push(h('div', {className: 'mb-1'}, 
                                h('a', {
                                    href: link.url,
                                    target: '_blank',
                                    title: link.title
                                }, 
                                h('i', {className: 'bi bi-link-45deg me-1'}),
                                link.title
                            )));
                        });

                        return h('div', {}, elements);
                    }
                },
                {
                    id: 'actions', name: 'Aktion', width: '80px', sort: false,
                    formatter: (cell, row) => {
                        const report = row.cells[0].data;
                        
                        // Array für alle Buttons
                        const buttons = [];

                        // 1. Edit-Button (wie bisher)
                        buttons.push(
                             h('a', {
                                 className: 'btn btn-default btn-sm', // btn-sm hinzugefügt
                                 title: 'Report bearbeiten',
                                 href: report.edit_url
                             }, h('i', { className: 'bi bi-pencil' }))
                        );

                        // --- HIER IST DEIN NEUER BUTTON ---
                        // 2. APA-Link Button
                        if (report.apa_link) {
                            buttons.push(
                                h('a', {
                                    className: 'btn btn-default btn-sm',
                                    title: report.apa_guid, // <-- Tooltip mit GUID
                                    href: report.apa_link,  // <-- Link
                                    target: '_blank'        // <-- Neues Fenster
                                }, h('img', {
                                    src: 'https://www.allegria.at/fileadmin/marketing/APA_Logo.png',
                                    height: '16', // Höhe anpassen, damit es in den Button passt
                                    alt: 'APA-Link'
                                }))
                            );
                        }
                        // --- ENDE NEUER BUTTON ---

                        return h('div', { className: 'btn-group btn-group-sm' }, buttons);
                    }
                }
            ],
            data: [],
            sort: true,
            className: { table: 'table table-striped table-hover' },
            search: false,
            pagination: { enabled: true, limit: 100 },
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
                    data: results.data.map(report => [
                        report, // Full raw object
                        report.uid,
                        report.type,
                        report.date,
                        report, // Für Status
                        report, // Für Zuordnung
                        report, // Für Titel/Medium
                        report, // Für Files/Links
                        report // Für Aktionen
                    ]),
                }).forceRender();
            } else {
                 Notification.warning('Keine Daten', 'Die Anfrage lieferte keine Daten zurück.');
            }

        } catch (error) {
            console.error('Fehler beim Laden der Report-Daten:', error);
            Notification.error('Fehler', `Daten konnten nicht geladen werden: ${error.message}`);
            this.grid.updateConfig({ data: [] }).forceRender();
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
const dashboardElement = document.querySelector('[data-module="ac-pr-reports-dashboard"]');
if (dashboardElement) {
    new ReportsDashboard(dashboardElement);
}