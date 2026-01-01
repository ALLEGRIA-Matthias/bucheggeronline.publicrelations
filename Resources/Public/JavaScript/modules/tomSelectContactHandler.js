// js/modules/tomSelectContactHandler.js

import { TomSelect } from './../tomselect/tomselect.esm.js'; // Dein Importpfad
import AjaxRequest from '@typo3/core/ajax/ajax-request.js';

const SVG_SPRITE_PATH = '/typo3conf/ext/allegria_communications/Resources/Public/Images/glyphicons-basics.svg'; // Gemäß deinem Select2-Beispiel

/**
 * Rendert ein AUSGEWÄHLTES Item in TomSelect.
 */
function renderContactItem(data, escape) {
    // console.log('[TomSelect ContactItem] Data:', data); // Zum Debuggen der ankommenden Datenstruktur
    if (data.customOption || !data.uid || data.uid === 'new' || data.uid === 'manual') {
        return `<div>${escape(data.text || '')}</div>`;
    }

    let displayName = '';
    // Bestimme den anzuzeigenden Namen
    displayName = data.stageName || data.firstName + ' ' + data.middleName + ' ' + data.lastName;
    if (!displayName.trim()) {
        // Wenn kein Name vorhanden ist, verwende die Firma oder die E-Mail-Adresse
        displayName = data.company || data.email;
    }
    displayName = displayName.trim();
    
    if (!displayName && data.text) {
        displayName = data.text;
    }
    if (!displayName) displayName = 'Ausgewählter Kontakt';

    let htmlOutput = `<span><strong>${escape(displayName)}</strong>`;
    
    if (data.company && data.company.trim()) {
        const companyTrimmed = data.company.trim();
        if (displayName !== companyTrimmed) { // Exakter Vergleich wie im Original
             htmlOutput += ` <span class="text-muted">(${escape(companyTrimmed)})</span>`;
        }
    }
    htmlOutput += `</span>`;
    return htmlOutput;
}

/**
 * Rendert eine OPTION in der TomSelect Dropdown-Liste.
 */
function renderContactOption(data, escape) {
    if (data.customOption) {
        return `<div class="d-flex align-items-center p-2"><strong>${escape(data.text)}</strong></div>`;
    }
    // Für von TomSelect generierte Platzhalter (z.B. "Type to search") oder unsere eigenen (value="0")
    if (!data.uid || data.uid === "0" || data.$isPlaceholder) {
        return `<div class="p-2 text-muted">${escape(data.text || '')}</div>`;
    }

    let imageHtml = '';
    if (data.image) {
        imageHtml = `<img src="${escape(data.image)}" alt="Kontaktbild" style="width: 30px; height: 30px; border-radius: 50%; margin-right: 10px;">`;
    } else {
        let badgeColor = 'badge-light';
        let icon = 'user';
        switch (data.gender) {
            case 'm': badgeColor = 'badge-blue'; icon = 'user'; break;
            case 'f': badgeColor = 'badge-pink'; icon = 'user-female'; break;
            case 'v': badgeColor = 'badge-gray'; icon = 'users'; break;
            case 's': badgeColor = 'badge-gold'; icon = 'star'; break;
        }
        imageHtml = `<span class="gender-badge ${escape(badgeColor)}" style="display: inline-flex; align-items: center; justify-content: center; width: 30px; height: 30px; border-radius: 50%; margin-right: 10px; color: white;">
                        <svg class="icon icon-sm" style="height: 1em; width: 1em; fill: currentColor;"><use xlink:href="${SVG_SPRITE_PATH}#${icon}"></use></svg>
                     </span>`;
    }

    let fullName = '';
    if (data.firstName || data.middleName || data.lastName) {
        let nameParts = [];
        if (data.firstName) nameParts.push(data.firstName);
        if (data.middleName) nameParts.push(data.middleName);
        if (data.lastName) nameParts.push(data.lastName);
        fullName = nameParts.join(' ').trim();
    }
    let displayName = data.stageName || fullName || data.company || data.email || '';
    displayName = displayName.trim();

    if (!displayName && data.text) {
        displayName = data.text;
    }
    if (!displayName) displayName = 'Unbekannter Kontakt';

    let textContainerHtml = `<div class="flex-grow-1">`;
    textContainerHtml += `<div><strong>${escape(displayName)}</strong></div>`;

    const isCompanyNameDisplayedAsName = !data.stageName && !fullName && data.company && data.company.trim() === displayName;
    let companyInfoText = '';
    if (data.company && data.company.trim() && !isCompanyNameDisplayedAsName) {
        companyInfoText = data.company.trim() + (data.position && data.position.trim() ? ` (${data.position.trim()})` : '');
    } else if (data.position && data.position.trim() && isCompanyNameDisplayedAsName) {
        companyInfoText = data.position.trim();
    }
    if (companyInfoText) {
        textContainerHtml += `<div class="small text-muted">${escape(companyInfoText)}</div>`;
    }

    let infoBadgesHtml = '';
    if (data.pageTitle || (data.categories && Array.isArray(data.categories) && data.categories.length > 0)) {
        infoBadgesHtml += '<div class="mt-1">';
        if (data.contactType) {
            infoBadgesHtml += `<span class="badge badge-warning me-1">${escape(data.contactType)}</span>`;
        }
        if (data.categories && Array.isArray(data.categories)) {
            data.categories.forEach(category => {
                infoBadgesHtml += `<span class="badge badge-primary me-1">${escape(category)}</span>`;
            });
        }
        infoBadgesHtml += '</div>';
    }
    textContainerHtml += infoBadgesHtml;
    textContainerHtml += `</div>`;

    return `<div class="d-flex align-items-center p-2">
                ${imageHtml}
                ${textContainerHtml}
            </div>`;
}


export function initializeContactSelects() {
    const contactSelectElements = document.querySelectorAll('select.select-contact');

    contactSelectElements.forEach(selectElement => {
        if (selectElement.tomselect) { return; }

        const noMailingRaw = selectElement.dataset.contactsNomailing;
        const noMailing = (noMailingRaw === 'true' || noMailingRaw === '1') ? 1 : 0;
        const maxResults = selectElement.dataset.contactsMaxresults || '20';
        const clientUid = selectElement.dataset.contactsClient || '0'; 
        const includeSpecial = selectElement.dataset.includeSpecialOptions === 'true';

        // Store instance for potential later use or debugging
        selectElement.tomselectInstance = new TomSelect(selectElement, {
            placeholder: selectElement.getAttribute('placeholder') || 'Kontakt suchen...',
            valueField: 'uid',      // *** NEU: uid als Wertfeld ***
            labelField: 'text',     // Wir erstellen ein 'text' Feld für die Standardanzeige/Suche
            searchField: ['text', 'firstName', 'lastName', 'company', 'email', 'stageName'], // Originalfelder für Suche

            shouldLoad: function(query) {
                return query.length >= 3;
            },
            load: function(query, callback) {
                const ajaxUrl = (window.TYPO3?.settings?.ajaxUrls?.publicrelations_contactsearch);
                if (!ajaxUrl) {
                    console.error('TomSelect Kontakte: AJAX URL "publicrelations_contactsearch" nicht definiert.');
                    callback(); return;
                }

                new AjaxRequest(ajaxUrl)
                    .withQueryArguments({ q: query, nomailing: noMailing, maxResults: maxResults, clientUid: clientUid })
                    .get()
                    .then(async function (response) {
                        try {
                            const resolvedData = await response.resolve();
                            let sourceArray = Array.isArray(resolvedData) ? resolvedData : (resolvedData?.results || []);
                            if (!Array.isArray(sourceArray)) sourceArray = [];

                            let itemsForTomSelect = sourceArray.map(item => {
                                // Annahme: 'item' hat Felder wie firstName, stageName etc. (camelCase)
                                const mainName = item.stageName || `${item.firstName || ''} ${item.middleName || ''} ${item.lastName || ''}`.trim() || item.company || item.email || 'Unbekannt';
                                return {
                                    ...item, // Alle Originaldaten (mit camelCase) für Renderer beibehalten
                                             // uid ist hier schon Teil von ...item
                                    text: mainName // Standardtext für TomSelect
                                };
                            });

                            if (includeSpecial) {
                                // Stelle sicher, dass spezielle Optionen auch 'uid' und 'text' haben
                                // und ggf. Felder, die Renderer für customOption erwarten (z.B. firstName für Fallback)
                                itemsForTomSelect.unshift(
                                    // { uid: 'new', text: 'NEUEN KONTAKT in Datenbank aufnehmen', customOption: true, gender: 's', firstName: 'NEUEN KONTAKT...' },
                                    { uid: 'manual', text: 'NEUEN KONTAKT manuell eintragen, nicht speichern', customOption: true, gender: 's', firstName: 'KONTAKT manuell...' }
                                );
                            }
                            callback(itemsForTomSelect);
                        } catch (resolveError) {
                            console.error('TomSelect Kontakte: Fehler beim Verarbeiten der Ajax-Antwort:', resolveError);
                            callback();
                        }
                    })
                    .catch(function (error) {
                        console.error('TomSelect Kontakte: AjaxRequest .get() FEHLGESCHLAGEN:', error);
                        callback();
                    });
            },
            render: {
                option: renderContactOption,
                item: renderContactItem,
            },
            onItemAdd: function(value, $item) {
                this.setTextboxValue('');
                this.refreshOptions();
            },
            // onLoad etc. für weiteres Debugging
        });
    });
}