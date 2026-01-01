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
        this.baseUrl = TYPO3.settings.ajaxUrls.publicrelations_contactindex;

        this.initializeDatatable();
        this.initializeSearch();
        this.initializeFilters();
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
                { id: 'raw_data', name: 'Raw', hidden: true }, // Versteckte Spalte für das ganze Objekt
                { id: 'image', name: h('i', { className: 'bi bi-image' }), width: '80px', sort: false, formatter: (cell, row) => h('span', { dangerouslySetInnerHTML: { __html: this.formatContactImage(row.cells[0].data) } }) },
                { id: 'contact_type', name: 'Typ', width: '150px', className: 'info', formatter: (cell, row) => h('div', { dangerouslySetInnerHTML: { __html: this.formatContactType(row.cells[0].data) } }) },
                
                { id: 'formated_name', name: 'Name', sort: true, formatter: (cell, row) => h('span', { dangerouslySetInnerHTML: { __html: this.formatName(row.cells[0].data) } }) },
                { id: 'formated_company', name: 'Firma', sort: true, formatter: (cell, row) => h('span', { dangerouslySetInnerHTML: { __html: this.formatCompany(row.cells[0].data) } }) },
                { id: 'formated_contact', name: 'Kontakt', formatter: (cell, row) => h('span', { dangerouslySetInnerHTML: { __html: this.formatContactDetails(row.cells[0].data) } }) },
                { id: 'social_profiles', name: 'Social', formatter: (cell, row) => h('div', { dangerouslySetInnerHTML: { __html: this.formatSocialProfiles(row.cells[0].data.social_profiles) } }) },
                { id: 'tags', name: 'Themen / Tags', formatter: (cell, row) => h('span', { dangerouslySetInnerHTML: { __html: this.formatTags(row.cells[0].data.tags) } }) },
                // { id: 'groups', name: 'Gruppen', formatter: (cell, row) => h('span', { dangerouslySetInnerHTML: { __html: this.formatGroups(row.cells[0].data.groups) } }) },
                { id: 'categories', name: 'Verteiler', formatter: (cell, row) => h('span', { dangerouslySetInnerHTML: { __html: this.formatCategories(row.cells[0].data.categories) } }) },
                // { id: 'checkbox', name: h('input', { type: 'checkbox' }), width: '50px', sort: false },
                {
                    id: 'actions', name: h('i', { className: 'bi bi-play-fill' }), width: '60px', sort: false,
                    formatter: (cell, row) => {
                        const contact = row.cells[0].data; // Das volle Kontakt-Objekt

                        // Wir erstellen einen Container, der beide Links enthalten wird.
                        return h('div', {}, [
                            // 1. Der "Ansehen"-Button (show_link)
                            h('a', {
                                href: contact.show_link,
                                title: `${contact.name} ansehen`,
                                className: 'show-contact-button text-muted me-2',
                            }, h('i', { className: 'bi bi-eye-fill' })),

                            // 2. Der "Bearbeiten"-Button
                            h('a', {
                                href: contact.edit_link,
                                title: `${contact.name} bearbeiten`,
                                className: 'edit-contact-button text-muted',
                            }, h('i', { className: 'bi bi-pencil-square' }))
                        ]);
                    }
                }
            ],
            data: [],
            // pagination: {
            //     enabled: true,
            //     limit: 15,
            // },
            // fixedHeader: true,
            // height: '700px',
            language: deDE, // Deutsche Übersetzungen
            // sort: true,
            style: {
                table: { 'table-layout': 'auto' },
                th: { 'white-space': 'nowrap' },
                tr: { 'class': 'info'}
            },
        }).render(datatableElement);
    }

    initializeSearch() {
        const searchInput = this.element.querySelector('#contactSearch');
        if (!searchInput) return;

        searchInput.addEventListener(
            'input',
            this.debounce(() => this.fetchData(),
            1000)
        );
    }

    initializeFilters() {
        const showClientsToggle = this.element.querySelector('#showClients');
        if (showClientsToggle) {
            showClientsToggle.addEventListener('change', () => this.fetchData());
        }
    }

    /**
     * Dies ist unsere neue, zentrale Funktion.
     * Sie sammelt alle Such- & Filterwerte, baut die URL und lädt die Tabelle neu.
     */
    async fetchData() {
        this.grid.updateConfig({ data: [] }).forceRender();

        const searchTerm = this.element.querySelector('#contactSearch').value;
        const showClientsToggle = this.element.querySelector('#showClients');
        console.log(showClientsToggle);
        const params = new URLSearchParams();

        if (searchTerm.length >= 3 || searchTerm.length === 0) {
            params.append('query', searchTerm);
        }

        if (showClientsToggle) {
            // schickt 'true' oder 'false' als String
            params.append('showClients', showClientsToggle.checked);
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

            if (results.clientContactCount !== undefined) {
                const countSpan = this.element.querySelector('#clientContactCount');
                if (countSpan) {
                    countSpan.textContent = results.clientContactCount;
                }
            }

            if (results.data) {
                // Wir übergeben die Daten und die Gesamtzahl an die Tabelle
                this.grid.updateConfig({
                    data: results.data.map(contact => ([
                        contact,
                        contact,
                        contact.contact_type,
                        contact,
                        contact,
                        contact,
                        contact.social_profiles,
                        contact.tags,
                        // contact.groups,
                        contact.categories,
                        // h('input', { type: 'checkbox', 'data-uid': contact.uid }),
                        contact,
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

        // NEU: Logik für Ort und Land hinzufügen
        const locationParts = [];
        if (contact.city) {
            locationParts.push(contact.city);
        }
        if (contact.country) {
            locationParts.push(contact.country);
        }
        // Fügt die Teile mit ", " zusammen. Funktioniert für 2, 1 oder 0 Teile.
        const location = locationParts.join(', ');

        // NEU: Den 'location'-String zum Array hinzufügen.
        // Die filter(Boolean)-Methode entfernt leere Einträge automatisch.
        return [email, phone, location].filter(Boolean).join('<br>');
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

    /**
     * Formatiert die Follower-Zahl in eine Kurzform (z.B. 146k).
     * @param {number} followers Die exakte Follower-Zahl.
     * @returns {string} Die formatierte Zeichenkette.
     */
    formatFollowers(followers) {
        if (followers >= 1000000) {
            return (followers / 1000000).toFixed(1).replace('.0', '') + 'M';
        }
        if (followers >= 1000) {
            // Runden auf die nächste ganze Tausender-Zahl für die Anzeige, wie gefordert (145950 -> 146k)
            return Math.round(followers / 1000) + 'k';
        }
        return followers.toString();
    }

    /**
     * Erstellt die Basis-URL für ein Social-Media-Profil.
     * @param {string} type Der Typ des Profils (z.B. 'instagram').
     * @param {string} handle Der Benutzername/Handle.
     * @returns {string} Die vollständige URL.
     */
    getSocialProfileUrl(type, handle) {
        // Entfernt ein eventuelles '@' am Anfang des Handles
        const cleanHandle = handle.startsWith('@') ? handle.substring(1) : handle;
        switch (type) {
            case 'instagram':
                return `https://www.instagram.com/${cleanHandle}`;
            case 'threads':
                return `https://www.threads.com/@${cleanHandle}`;
            case 'facebook':
                return `https://www.facebook.com/${cleanHandle}`;
            case 'tiktok':
                return `https://www.tiktok.com/@${cleanHandle}`;
            case 'youtube':
                return `https://www.${type}.com/@${cleanHandle}`;
            case 'linkedin':
                // LinkedIn hat komplexere URLs, oft /in/ für Personen
                return `https://www.linkedin.com/in/${cleanHandle}`;
            case 'xing':
                return `https://www.xing.com/profile/${cleanHandle}`;
            case 'x':
                return `https://x.com/${cleanHandle}`;
            case 'snapchat':
                return `https://www.snapchat.com/add/${cleanHandle}`;
            default:
                return '#'; // Fallback
        }
    }

    /**
     * Formatiert die Social-Media-Profile für die Tabellenansicht.
     * @param {Array} profiles Ein Array von Social-Profile-Objekten.
     * @returns {string} Der generierte HTML-Code.
     */
    formatSocialProfiles(profiles) {
        if (!profiles || !profiles.length) return '';
        
        // TYPO3 v13 liefert den Pfad zu den Public-Assets nicht automatisch, daher hart codieren.
        const iconBasePath = '/typo3conf/ext/publicrelations/Resources/Public/Icons/SocialProfiles/';

        return profiles.map(profile => {
            const profileUrl = this.getSocialProfileUrl(profile.type, profile.handle);
            const iconUrl = `${iconBasePath}${profile.type}.svg`;
            
            const followerBadge = profile.follower > 0
                ? `<span class="badge rounded-pill bg-info ms-1" title="${new Intl.NumberFormat('de-DE').format(profile.follower)}">${this.formatFollowers(profile.follower)}</span>`
                : '';

            return `
                <a href="${profileUrl}" target="_blank" class="badge text-decoration-none text-dark bg-light me-1 mb-1 p-1">
                    <img src="${iconUrl}" alt="${profile.type}" style="width: 16px; height: 16px; vertical-align: middle;">
                    <span class="ms-1 my-auto">${profile.handle}</span>
                    ${followerBadge}
                </a>
            `;
        }).join('');
    }
    
    formatTags(tags) {
        if (!tags || !tags.length) return '';

        return tags.map(tag => {
            // 1. Prüfen, ob der Name der Farbvariable 'white' oder 'yellow' enthält.
            //    .toLowerCase() macht den Vergleich sicher, egal wie die Variable benannt ist.
        const isLightColor = tag.color && 
                             (tag.color.toLowerCase().includes('white') || 
                              tag.color.toLowerCase().includes('yellow'));

            // 2. Anhand des Ergebnisses die passende Textfarben-Klasse auswählen.
            const textColorClass = isLightColor ? 'badge-default' : 'badge-danger';

            // 3. Die korrekte Klasse im HTML einsetzen.
            return `<span class="badge ${textColorClass}" style="background-color: var(${tag.color});">
                        <i class="${tag.icon} me-1"></i>${tag.title}
                    </span>`;
        }).join(' ');
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
    
    /**
     * Formatiert die "Typ"-Spalte basierend auf Kundenzugehörigkeit und Kontakttypen.
     * @param {object} contact Das gesamte Kontakt-Objekt.
     * @returns {string} Der generierte HTML-Code.
     */
    formatContactType(contact) {
        // Wir sammeln die einzelnen HTML-Teile in diesem Array.
        const htmlParts = [];

        // 1. Prüfen, ob es ein Kundenkontakt ist.
        if (contact.client && contact.client > 0) {
            htmlParts.push('<span class="badge badge-danger">Kundenkontakt</span>');
            if (contact.client_name) {
                // Wir packen den Namen in ein div für den Zeilenumbruch.
                htmlParts.push(`<div class="mt-1">${contact.client_name}</div>`);
            }
        }

        // 2. Die Icons aus dem 'contact_types' Array verarbeiten.
        if (contact.contact_types && contact.contact_types.length > 0) {
            const iconsHtml = contact.contact_types
                .map(type => {
                    // Nur wenn die 'svg' Eigenschaft existiert, erstellen wir ein Icon.
                    // Der title-Attribut ist gut für die Usability (Anzeige bei Mouse-Hover).
                    return type.svg ? `<i class="${type.svg} me-1" title="${type.title}"></i>` : '';
                })
                .join(''); // Alle Icons zu einem String verbinden.

            // Wir packen die Icons in ein div, um den Abstand zu steuern.
            // Wenn bereits Kundeninfos da sind, fügen wir einen kleinen Abstand nach oben hinzu.
            const marginTopClass = htmlParts.length > 0 ? 'mt-2' : '';
            htmlParts.push(`<div class="${marginTopClass}">${iconsHtml}</div>`);
        }

        // Alle Teile zu einem finalen HTML-String zusammenfügen.
        return htmlParts.join('');
    }
    
    formatContactImage(contact) {
        const indicatorColor = contact.mailing_exclude === 0 ? 'bg-success' : 'bg-danger';
        const indicatorText = contact.mailing_exclude === 0 ? 'aktiv' : 'deaktiv';

        if (contact.image) {
            return `<span class="position-relative d-inline-block" style="margin-right: 10px;">
                        <img src="${contact.image}" alt="Kontaktbild" style="width: 30px; height: 30px; border-radius: 50%;">
                        <span class="position-absolute top-0 start-100 translate-middle p-1 ${indicatorColor} border border-light rounded-circle">
                            <span class="visually-hidden">${indicatorText}</span>
                        </span>
                    </span>`;
        } else {
            let badgeColor = '', icon = '';
            switch (contact.gender) {
                case 'm': badgeColor = 'badge-blue'; icon = 'user'; break;
                case 'f': badgeColor = 'badge-pink'; icon = 'user-female'; break;
                default: badgeColor = 'badge-gray'; icon = 'user';
            }

            return `<span class="position-relative d-inline-block" style="margin-right: 10px;">
                        <span class="d-flex align-items-center justify-content-center rounded-circle border border-dark text-white ${badgeColor}" style="width: 30px; height: 30px;">
                            <i class="bi bi-person-fill"></i>
                        </span>
                        <span class="position-absolute top-0 start-100 translate-middle p-1 ${indicatorColor} border border-light rounded-circle">
                            <span class="visually-hidden">${indicatorText}</span>
                        </span>
                    </span>`;
        }
    }
}

// Der Export, damit unser main.js dieses Modul starten kann
export default function initialize(element) {
    new ContactsIndex(element);
}