import { Grid, h } from '@ac/libs/grid-js/gridjs.js';
import { deDE } from '@ac/libs/grid-js/l10n/l10n.mjs';
import Notification from '@typo3/backend/notification.js';
import TomSelect from '@ac/libs/tom-select/tom-select.js';
// ... weitere Imports, die wir später für die Filter brauchen

class ContactsIndex {
    /**
     * @param {HTMLElement} element Das Wurzelelement mit dem data-module Attribut
     */
    constructor(element) {
        this.element = element;
        this.grid = null;
        this.baseUrl = TYPO3.settings.ajaxUrls.accontacts_contactsearch;

        this.initializeDatatable();
        this.initializeSearch();
        this.fetchData(); // Initialer Ladevorgang
        
        // Hier werden wir später die Filter initialisieren
        // this.initializeFilters();
    }

    initializeDatatable() {
        const datatableElement = this.element.querySelector('#datatable');
        if (!datatableElement) return;

        this.grid = new Grid({
            // Wir definieren die Spalten. Die 'field' Namen müssen mit den Keys
            // aus Ihrem AJAX-Response übereinstimmen.
            columns: [
                { id: 'image', name: 'Foto', width: '70px', sort: false, formatter: (cell, row) => h('span', { dangerouslySetInnerHTML: { __html: this.formatContactImage(row.cells[10].data) } }) },
                { id: 'contact_type', name: 'Typ', width: '110px', formatter: (cell, row) => h('span', { dangerouslySetInnerHTML: { __html: this.formatContactTypeBadge(row.cells[1].data) } }) },
                { id: 'client', name: 'Kunde', width: '100px', formatter: (cell, row) => h('span', { dangerouslySetInnerHTML: { __html: this.formatClient(row.cells[10].data) } }) },
                {
                    id: 'actions', name: 'Aktionen', width: '100px', sort: false,
                    formatter: (cell, row) => {
                        const contact = row.cells[10].data; // Das volle Kontakt-Objekt
                        return h('a', {
                            href: contact.show_link,
                            title: `${contact.name} öffnen`,
                            className: 'show-contact-button text-muted ms-2',
                        }, h('i', { className: 'bi bi-eye-fill' }));
                    }
                },
                { id: 'formated_name', name: 'Name', formatter: (cell, row) => h('span', { dangerouslySetInnerHTML: { __html: this.formatName(row.cells[10].data) } }) },
                { id: 'formated_company', name: 'Firma', formatter: (cell, row) => h('span', { dangerouslySetInnerHTML: { __html: this.formatCompany(row.cells[10].data) } }) },
                { id: 'formated_contact', name: 'Kontakt', formatter: (cell, row) => h('span', { dangerouslySetInnerHTML: { __html: this.formatContactDetails(row.cells[10].data) } }) },
                { id: 'tags', name: 'Tags', formatter: (cell, row) => h('span', { dangerouslySetInnerHTML: { __html: this.formatTags(row.cells[10].data.tags) } }) },
                { id: 'groups', name: 'Gruppen', formatter: (cell, row) => h('span', { dangerouslySetInnerHTML: { __html: this.formatGroups(row.cells[10].data.groups) } }) },
                { id: 'categories', name: 'Verteiler', formatter: (cell, row) => h('span', { dangerouslySetInnerHTML: { __html: this.formatCategories(row.cells[10].data.categories) } }) },
                { id: 'checkbox', name: h('input', { type: 'checkbox' }), width: '50px', sort: false },
                { id: 'raw_data', name: 'Raw', hidden: true } // Versteckte Spalte für das ganze Objekt
            ],
            data: [],
            // pagination: {
            //     enabled: true,
            //     limit: 15,
            // },
            // fixedHeader: true,
            // height: '700px',
            language: deDE, // Deutsche Übersetzungen
            sort: true,
            style: {
                table: { 'table-layout': 'auto' },
                th: { 'white-space': 'nowrap' }
            }
        }).render(datatableElement);
    }

    initializeSearch() {
        const searchInput = this.element.querySelector('#contactSearch');
        if (!searchInput) return;

        searchInput.addEventListener(
            'input',
            this.debounce(() => this.fetchData(),
            500)
        );
    }

    /**
     * Dies ist unsere neue, zentrale Funktion.
     * Sie sammelt alle Such- & Filterwerte, baut die URL und lädt die Tabelle neu.
     */
    async fetchData() {
        this.grid.updateConfig({ data: [] }).forceRender();

        const searchTerm = this.element.querySelector('#contactSearch').value;
        const params = new URLSearchParams();

        if (searchTerm.length >= 3 || searchTerm.length === 0) {
            params.append('query', searchTerm);
        }


        // Später für die Filter:
        // const tags = Array.from(this.element.querySelector('#filterTags').options).filter(o => o.selected).map(o => o.value);

        // Später für die Filter:
        // if (tags.length > 0) {
        //     params.append('tags', tags.join(','));
        // }

        const separator = this.baseUrl.includes('?') ? '&' : '?';
        const finalUrl = params.toString() ? `${this.baseUrl}${separator}${params.toString()}` : this.baseUrl;
        
        try {
            const response = await fetch(finalUrl);
            const results = await response.json();

            if (results.data) {
                // Wir übergeben die Daten und die Gesamtzahl an die Tabelle
                this.grid.updateConfig({
                    data: results.data.map(contact => ([
                        contact,
                        contact.contact_type,
                        contact,
                        contact,
                        contact,
                        contact,
                        contact,
                        contact.tags,
                        contact.groups,
                        contact.categories,
                        h('input', { type: 'checkbox', 'data-uid': contact.uid }),
                        contact
                    ])),
                    total: results.total
                }).forceRender();
            }

        } catch (error) {
            console.error('Fehler beim Laden der Tabellendaten:', error);
            Notification.error('Fehler', 'Daten konnten nicht geladen werden.');
        }
    }

    // Ihre Debounce-Funktion - perfekt, bleibt unverändert
    debounce(func, delay) {
        let timer;
        return (...args) => {
            clearTimeout(timer);
            timer = setTimeout(() => func.apply(this, args), delay);
        };
    }

    // ALLE IHRE FORMAT-FUNKTIONEN BLEIBEN EXAKT GLEICH.
    // Sie sind das Herzstück Ihrer Logik und werden 1:1 übernommen.
    formatName(contact) {
        return `<strong>${[
            contact.title,
            contact.first_name,
            contact.middle_name,
            contact.last_name,
        ]
            .filter(Boolean)
            .join(' ') + (contact.title_suffix ? `, ${contact.title_suffix}` : '')}</strong>`;
    }
    
    formatCompany(contact) {
        const company = contact.company ? `<strong>${contact.company}</strong>` : '';
        const position = contact.position ? `<br>${contact.position}` : '';
        return company + position;
    }
    
    formatContactDetails(contact) {
        const email = contact.email ? `<a href="mailto:${contact.email}" target="_blank">${contact.email}</a>` : '';
        const phone = this.formatFullPhone(contact);
        return [email, phone].filter(Boolean).join('<br>');
    }
    
    formatFullPhone(contact) {
        const numbers = [];
        if (contact.mobile) numbers.push(this.formatPhone(contact.mobile));
        if (contact.phone) numbers.push(this.formatPhone(contact.phone));
        return numbers.join('<br>');
    }
    
    formatPhone(number) {
        const cleanNumber = number.replace(/[^\d+]/g, '');
        const linkNumber = cleanNumber.startsWith('+') ? `00${cleanNumber.slice(1)}` : cleanNumber;
        return `<a href="tel:${linkNumber}" target="_blank">${cleanNumber}</a>`;
    }
    
    formatCategories(categories) {
        if (!categories || !categories.length) return '';
        return categories
            .map(category => `<span class="badge badge-secondary">${category.title}</span>`)
            .join(' ');
    }
    
    formatTags(tags) {
        if (!tags || !tags.length) return '';
        return tags
            .map(tag => `<span class="badge text-white" style="background-color: var(${tag.color})"><i class="${tag.icon} me-1"></i>${tag.title}</span>`)
            .join(' ');
    }
    
    formatGroups(groups) {
        if (!groups || !groups.length) return '';
        return groups
            .map(group => {
                const hierarchy = group.hierarchy_name ? `${group.hierarchy_name} / ` : '';
                return `<span class="badge badge-primary">${hierarchy}${group.name}</span>`;
            })
            .join(' ');
    }
    
    formatContactTypeBadge(type) {
        const badgeClasses = {
            Presse: 'badge-success',
            Promi: 'badge-primary',
            Kundenkontakt: 'badge-danger',
            Verteiler: 'badge-info',
            Undefiniert: 'badge-pink',
        };
        const badgeClass = badgeClasses[type] || 'badge-secondary';
        return `<span class="badge ${badgeClass}">${type}</span>`;
    }
    
    formatContactImage(contact) {
        if (contact.image) {
            return `<img src="${contact.image}" alt="Kontaktbild" style="width: 30px; height: 30px; border-radius: 50%; margin-right: 10px;">`;
        } else {
            let badgeColor = '', icon = '';
            switch (contact.gender) {
                case 'm': badgeColor = 'badge-blue'; icon = 'user'; break;
                case 'f': badgeColor = 'badge-pink'; icon = 'user-female'; break;
                default: badgeColor = 'badge-gray'; icon = 'user';
            }
            return `<span class="gender-badge ${badgeColor} text-white"><i class="bi bi-person-fill"></i></span>`;
        }
    }
}

// Der Export, damit unser main.js dieses Modul starten kann
export default function initialize(element) {
    new ContactsIndex(element);
}