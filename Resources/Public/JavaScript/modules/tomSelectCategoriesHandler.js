import { TomSelect } from './../tomselect/tomselect.esm.js';
import AjaxRequest from '@typo3/core/ajax/ajax-request.js';

export function initializeMailingListSelector() {
    const categorySelectElement = document.getElementById('category-select');
    if (!categorySelectElement) return;

    // Die Elemente, die das Neuladen steuern
    const contextRadios = document.querySelectorAll('input[data-toggle-group="contact-context"]');
    const clientSelect = document.getElementById('client-select');
    const ajaxUrl = TYPO3.settings.ajaxUrls.publicrelations_categoriesselect;

    const tomSelect = new TomSelect(categorySelectElement, {
        plugins: ['remove_button'],
        valueField: 'value',
        labelField: 'text',
        searchField: 'text',
        load: async (query, callback) => {
            const selectedType = document.querySelector('input[data-toggle-group="contact-context"]:checked').value;
            const params = { q: query };

            if (selectedType === 'client' && clientSelect) {
                params.clientId = clientSelect.value;
            } else {
                // Holen der "internal" Parameter direkt aus dem data-Attribut des Select-Felds
                params.parent = categorySelectElement.dataset.internalParent || 0;
                params.parentRecursive = categorySelectElement.dataset.internalRecursive || false;
            }

            try {
                const request = new AjaxRequest(ajaxUrl);
                const response = await request.post({ publicrelations_categories: params });
                const json = await response.resolve();
                callback(json);
            } catch (error) {
                console.error('Category AJAX request failed:', error);
                callback();
            }
        },
        render: {
            option: (data, escape) => {
                let clientBadge = '';
                if (data.clientName) {
                    clientBadge = `<span class="badge bg-secondary float-end">${escape(data.clientName)}</span>`;
                }
                return `<div>${escape(data.text)}${clientBadge}</div>`;
            },
            item: (data, escape) => {
                 let clientBadge = '';
                if (data.clientName) {
                    clientBadge = ` <span class="badge bg-secondary">${escape(data.clientName)}</span>`;
                }
                return `<div title="${escape(data.text)}">${escape(data.text)}${clientBadge}</div>`;
            }
        }
    });

    const reloadOptions = () => {
        tomSelect.clear();
        tomSelect.clearOptions();
    };

    contextRadios.forEach(radio => radio.addEventListener('change', reloadOptions));
    if (clientSelect) {
        clientSelect.addEventListener('change', reloadOptions);
    }
}