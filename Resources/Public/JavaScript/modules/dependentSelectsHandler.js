// js/modules/dependentSelectsHandler.js

import { TomSelect } from './../tomselect/tomselect.esm.js'; // Dein Importpfad
import AjaxRequest from '@typo3/core/ajax/ajax-request.js';   // ES-Modul-Import

async function fetchCampaignsForClient(clientId, campaignSelectElement) {
    const initialPlaceholderText = campaignSelectElement.getAttribute('placeholder') || "Produkt auswählen...";
    const noResultsPlaceholderText = campaignSelectElement.dataset.placeholderNoResults || "Keine Produkte für diesen Kunden";
    const errorLoadingText = "Fehler beim Laden der Produkte";
    // const loadingPlaceholderText = campaignSelectElement.dataset.placeholderLoading || "Lade Produkte..."; // Wird im onChange/onInitialize gesetzt

    const ajaxUrl = TYPO3.settings.ajaxUrls.publicrelations_campaignsearch;
    if (!ajaxUrl) {
        console.error('DependentSelects: AJAX URL für Kampagnensuche nicht definiert.');
        return [{ value: "0", text: "Fehler: URL nicht konfiguriert", title: "Fehler: URL nicht konfiguriert", $isPlaceholder: true }];
    }

    try {
        const response = await new AjaxRequest(ajaxUrl)
            .withQueryArguments({ clientUid: clientId })
            .get();
        const campaignsData = await response.resolve();

        if (campaignsData && Array.isArray(campaignsData) && campaignsData.length > 0) {
            let preparedOptions = campaignsData.map(campaign => ({
                ...campaign,
                value: campaign.uid,
                text: campaign.title, // Haupt-Titel für labelField und Fallbacks
                // subtitle ist bereits in ...campaign enthalten
            }));
            preparedOptions.unshift({ value: "0", text: initialPlaceholderText, title: initialPlaceholderText, $isPlaceholder: true });
            return preparedOptions;
        } else {
            return [{ value: "0", text: noResultsPlaceholderText, title: noResultsPlaceholderText, $isPlaceholder: true }];
        }
    } catch (error) {
        console.error('DependentSelects: Fehler beim Laden der Kampagnen:', error);
        return [{ value: "0", text: errorLoadingText, title: errorLoadingText, $isPlaceholder: true }];
    }
}

/**
 * Rendert ein ausgewähltes Kampagnen-Item (was im Feld steht).
 * NEU: Subtitel in eigener Zeile.
 */
function renderCampaignItem(data, escape) {
    if (!data.value || data.value === "0" || data.$isPlaceholder) {
        return `<div>${escape(data.text)}</div>`;
    }
    // data.title ist der Titel der Kampagne, data.subtitle der Untertitel
    let html = `<div><strong>${escape(data.title || data.text)}</strong>`; // Titel fett
    if (data.subtitle) {
        html += `<div class="small text-muted">${escape(data.subtitle)}</div>`; // Subtitel normal, kleiner, in neuer Zeile
    }
    html += `</div>`;
    return html;
}

/**
 * Rendert eine Kampagnen-Option im Dropdown.
 * NEU: Subtitel in eigener Zeile.
 */
function renderCampaignOption(data, escape) {
    if (!data.value || data.value === "0" || data.$isPlaceholder) {
        return `<div class="p-2 text-muted">${escape(data.text)}</div>`; // Placeholder oder Statusnachrichten
    }
    // data.title ist der Titel der Kampagne, data.subtitle der Untertitel
    let html = `<div class="p-2">`;
    html += `<div><strong>${escape(data.title || data.text)}</strong></div>`; // Titel fett
    if (data.subtitle) {
        html += `<div class="small text-muted">${escape(data.subtitle)}</div>`; // Subtitel normal, kleiner, in neuer Zeile
    }
    html += `</div>`;
    return html;
}

export function initializeDependentClientCampaignSelects() {
    const clientSelectElements = document.querySelectorAll('select.js-client-select');

    clientSelectElements.forEach(clientSelectElement => {
        const campaignSelectTargetSelector = clientSelectElement.dataset.campaignSelectTarget;
        if (!campaignSelectTargetSelector) { return; }
        const campaignSelectElement = document.querySelector(campaignSelectTargetSelector);
        if (!campaignSelectElement) { return; }

        const initialCampaignPlaceholder = campaignSelectElement.dataset.placeholderInitial || "Bitte zuerst Kunden wählen...";
        const loadingCampaignPlaceholder = campaignSelectElement.dataset.placeholderLoading || "Lade Produkte...";

        if (campaignSelectElement.tomselect) campaignSelectElement.tomselect.destroy();
        const campaignTomSelect = new TomSelect(campaignSelectElement, {
            placeholder: initialCampaignPlaceholder,
            valueField: 'value',
            labelField: 'text',
            searchField: ['text', 'subtitle'],
            render: {
                option: renderCampaignOption,
                item: renderCampaignItem,
            },
            // NEU: Fokus entfernen nach Auswahl (da es ein Single-Select ist)
            onItemAdd: function() {
                this.blur();
            }
        });

        campaignTomSelect.clear();
        campaignTomSelect.clearOptions();
        campaignTomSelect.addOption({ value: "0", text: initialCampaignPlaceholder, title: initialCampaignPlaceholder, $isPlaceholder: true });
        campaignTomSelect.setValue("0", true);
        campaignTomSelect.disable();

        if (clientSelectElement.tomselect) clientSelectElement.tomselect.destroy();
        const clientTomSelect = new TomSelect(clientSelectElement, {
            placeholder: clientSelectElement.getAttribute('placeholder') || 'Bitte Kunden auswählen...',
            allowEmptyOption: true,
            onInitialize: async function() {
                const initialClientId = this.getValue();
                if (initialClientId && initialClientId !== "0" && initialClientId !== "") {
                    campaignTomSelect.clearOptions();
                    campaignTomSelect.addOption({ value: "0", text: loadingCampaignPlaceholder, title: loadingCampaignPlaceholder, $isPlaceholder: true });
                    campaignTomSelect.setValue("0", true);
                    campaignTomSelect.disable();

                    const newCampaignOptions = await fetchCampaignsForClient(initialClientId, campaignSelectElement);
                    
                    campaignTomSelect.clearOptions();
                    campaignTomSelect.addOption(newCampaignOptions);
                    campaignTomSelect.enable();
                    campaignTomSelect.setValue("0", true);
                    // if (newCampaignOptions.length > 1) campaignTomSelect.open(); // Optional öffnen
                }
            },
            onChange: async function(value) {
                campaignTomSelect.clear();
                campaignTomSelect.clearOptions();
                campaignTomSelect.disable();

                if (value && value !== "0" && value !== "") {
                    campaignTomSelect.addOption({ value: "0", text: loadingCampaignPlaceholder, title: loadingCampaignPlaceholder, $isPlaceholder: true });
                    campaignTomSelect.setValue("0", true);

                    const newCampaignOptions = await fetchCampaignsForClient(value, campaignSelectElement);
                    
                    campaignTomSelect.clearOptions();
                    campaignTomSelect.addOption(newCampaignOptions);
                    campaignTomSelect.enable();
                    campaignTomSelect.setValue("0", true);
                    campaignTomSelect.open(); 
                } else {
                    campaignTomSelect.addOption({ value: "0", text: initialCampaignPlaceholder, title: initialCampaignPlaceholder, $isPlaceholder: true });
                    campaignTomSelect.setValue("0", true);
                }
            }
        });
    });
}