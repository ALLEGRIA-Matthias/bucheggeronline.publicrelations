// js/modules/dependentSelectsHandler.js

import { TomSelect } from './../tomselect/tomselect.esm.js'; // Dein Importpfad
import AjaxRequest from '@typo3/core/ajax/ajax-request.js';   // ES-Modul-Import

/**
 * Lädt Kampagnen für einen gegebenen Kunden via AJAX und aktualisiert das TomSelect-Feld für Kampagnen.
 */
async function loadCampaignsForClient(clientId, campaignTomSelectInstance, campaignSelectElement) {
    const initialPlaceholder = campaignSelectElement.dataset.placeholderInitial || "Bitte zuerst Kunden wählen...";
    const loadingPlaceholder = campaignSelectElement.dataset.placeholderLoading || "Lade Produkte...";
    const noResultsPlaceholder = campaignSelectElement.dataset.placeholderNoResults || "Keine Produkte für diesen Kunden";

    campaignTomSelectInstance.clear();
    campaignTomSelectInstance.clearOptions(); // Wichtig: Alle alten Optionen entfernen
    campaignTomSelectInstance.addOption({ value: "0", text: loadingPlaceholder }); // Lade-Nachricht
    campaignTomSelectInstance.setValue("0", true); // Lade-Nachricht auswählen (silent)
    campaignTomSelectInstance.disable(); // Deaktivieren während des Ladens

    // Du brauchst einen neuen AJAX-Endpunkt in TYPO3, der Kampagnen für eine clientUid zurückgibt
    const ajaxUrl = TYPO3.settings.ajaxUrls.publicrelations_campaignsearch; // Z.B. publicrelations_campaignsearch
    if (!ajaxUrl) {
        console.error('DependentSelects: AJAX URL für Kampagnensuche (publicrelations_campaignsearch) nicht definiert.');
        campaignTomSelectInstance.addOption({ value: "0", text: "Fehler: URL nicht konfiguriert" });
        campaignTomSelectInstance.enable(); // Wieder aktivieren, auch wenn mit Fehlermeldung
        return;
    }

    try {
        const response = await new AjaxRequest(ajaxUrl)
            .withQueryArguments({ clientUid: clientId }) // Sende die Kunden-UID
            .get();
        const campaignsData = await response.resolve(); // Erwartet: [{uid: 1, title: 'Kampagne A'}, ...]

        campaignTomSelectInstance.enable();
        campaignTomSelectInstance.clearOptions(); // Lade-Nachricht entfernen

        if (campaignsData && Array.isArray(campaignsData) && campaignsData.length > 0) {
            campaignTomSelectInstance.addOption({ value: "0", text: campaignSelectElement.getAttribute('placeholder') || "Produkt auswählen..." });
            campaignsData.forEach(campaign => {
                campaignTomSelectInstance.addOption({
                    value: campaign.uid,
                    text: campaign.title
                    // Hier könntest du weitere Daten für komplexeres Rendering hinzufügen:
                    // ...campaign
                });
            });
            campaignTomSelectInstance.setValue("0", true); // Placeholder wieder auswählen
        } else {
            campaignTomSelectInstance.addOption({ value: "0", text: noResultsPlaceholder });
            campaignTomSelectInstance.setValue("0", true);
        }
    } catch (error) {
        console.error('DependentSelects: Fehler beim Laden der Kampagnen:', error);
        campaignTomSelectInstance.enable();
        campaignTomSelectInstance.clearOptions();
        campaignTomSelectInstance.addOption({ value: "0", text: "Fehler beim Laden" });
        campaignTomSelectInstance.setValue("0", true);
    }
}

export function initializeDependentClientCampaignSelects() {
    const clientSelectElements = document.querySelectorAll('select.js-client-select');

    clientSelectElements.forEach(clientSelectElement => {
        const campaignSelectTargetSelector = clientSelectElement.dataset.campaignSelectTarget;
        if (!campaignSelectTargetSelector) {
            // console.warn('DependentSelects: Client-Select fehlt data-campaign-select-target.', clientSelectElement);
            return;
        }
        const campaignSelectElement = document.querySelector(campaignSelectTargetSelector);
        if (!campaignSelectElement) {
            // console.warn(`DependentSelects: Kampagnen-Select-Ziel "${campaignSelectTargetSelector}" nicht gefunden.`);
            return;
        }

        // Initialisiere das (zunächst leere und deaktivierte) Kampagnen-TomSelect
        if (campaignSelectElement.tomselect) campaignSelectElement.tomselect.destroy(); // Ggf. alte Instanz zerstören
        const campaignTomSelect = new TomSelect(campaignSelectElement, {
            placeholder: campaignSelectElement.getAttribute('placeholder') || (campaignSelectElement.dataset.placeholderInitial || "Produkt auswählen..."),
            valueField: 'uid',
            labelField: 'text', // Wir erstellen ein 'text' Feld in loadCampaignsForClient
            // render: { option: ..., item: ... } // Falls du spezielles Rendering brauchst
        });
        if (!clientSelectElement.value || clientSelectElement.value === "0" || clientSelectElement.value === "") {
            campaignTomSelect.disable(); // Initial deaktivieren
        }


        // Initialisiere das Kunden-TomSelect
        if (clientSelectElement.tomselect) clientSelectElement.tomselect.destroy();
        const clientTomSelect = new TomSelect(clientSelectElement, {
            placeholder: clientSelectElement.getAttribute('placeholder') || 'Bitte Kunden auswählen...',
            allowEmptyOption: true, // Damit "Bitte Kunden auswählen..." abwählbar ist
            onInitialize: function() { // 'this' ist die clientTomSelect Instanz
                // Beim Laden der Seite prüfen, ob schon ein Kunde ausgewählt ist
                const initialClientId = this.getValue();
                if (initialClientId && initialClientId !== "0" && initialClientId !== "") {
                    loadCampaignsForClient(initialClientId, campaignTomSelect, campaignSelectElement);
                }
            },
            onChange: function(value) { // 'value' ist die ausgewählte clientUid
                const initialPlaceholder = campaignSelectElement.dataset.placeholderInitial || "Bitte zuerst Kunden wählen...";
                campaignTomSelect.clear();
                campaignTomSelect.clearOptions();
                campaignTomSelect.addOption({ value: "0", text: initialPlaceholder });
                campaignTomSelect.setValue("0", true);

                if (value && value !== "0" && value !== "") { // Ein Kunde wurde ausgewählt
                    loadCampaignsForClient(value, campaignTomSelect, campaignSelectElement);
                } else { // Kein Kunde ausgewählt
                    campaignTomSelect.disable();
                }
            }
        });
    });
}