import AjaxRequest from '@typo3/core/ajax/ajax-request.js';

export function initializeEmailValidation() {
    const emailInput = document.querySelector('.js-email-validation');
    if (!emailInput) {
        return;
    }

    // KORREKTUR: Die URL wird jetzt aus dem globalen TYPO3-Objekt geholt
    const validationUrl = TYPO3.settings.ajaxUrls.publicrelations_contactemailvalidation;
    const resultContainer = document.getElementById('validation-result-container');
    const furtherFieldsWrapper = document.getElementById('further-fields-wrapper');

    if (!validationUrl) {
        console.error('EmailValidation: AJAX URL (publicrelations_contactemailvalidation) ist nicht definiert.');
        return;
    }

    const handleValidation = async () => {
        const email = emailInput.value;
        const contactType = document.querySelector('input[name="newContact[contactType]"]:checked').value;
        const clientSelect = document.getElementById('client-select');
        const clientId = (contactType === 'client' && clientSelect) ? clientSelect.value : 0;

        resultContainer.innerHTML = '<div class="spinner-border spinner-border-sm" role="status"><span class="visually-hidden">Loading...</span></div>';
        furtherFieldsWrapper.classList.add('d-none');

        try {
            const request = new AjaxRequest(validationUrl);

            // KORREKTUR: Die Daten in einem Namespace senden.
            // Das ist der Standardweg und passt zum Controller.
            const body = {
                'publicrelations_newcontact': {
                    'email': email,
                    'contactType': contactType,
                    'client': clientId
                }
            };
            
            const response = await request.post(body);
            const data = await response.resolve();

            resultContainer.innerHTML = '';
            renderValidationResult(data);

        } catch (error) {
            resultContainer.innerHTML = '<div class="alert alert-danger">Ein Fehler ist aufgetreten.</div>';
            console.error('Validation AjaxRequest error:', error);
        }
    };

    // Die Logik zum Rendern der Ergebnisse bleibt unverändert
    const renderValidationResult = (data) => {
        if (!data.isValidFormat) {
            resultContainer.innerHTML = '<div class="alert alert-warning">Bitte geben Sie eine gültige E-Mail-Adresse ein.</div>';
            return;
        }

        if (data.isDuplicate) {
            let html = '<div class="alert alert-danger"><strong>Duplikat gefunden!</strong> Diese E-Mail-Adresse existiert bereits in diesem Kontext.</div>';
            html += '<ul class="list-group mb-3">';
            data.duplicates.forEach(contact => {
                html += `<a href="${contact.editLink}"><li class="list-group-item">${contact.name} [${contact.company || 'Keine Firma'}]</li></a>`;
            });
            html += '</ul>';
            html += '<div class="form-check"><input type="checkbox" id="create-anyway" class="form-check-input"><label for="create-anyway" class="form-check-label">Dennoch neuen Kontakt erstellen</label></div>';
            resultContainer.innerHTML = html;

            document.getElementById('create-anyway').addEventListener('change', (e) => {
                if(e.target.checked) {
                    furtherFieldsWrapper.classList.remove('d-none');
                } else {
                    furtherFieldsWrapper.classList.add('d-none');
                }
            });

        } else {
            resultContainer.innerHTML = '<div class="alert alert-success">E-Mail-Adresse ist korrekt.</div>';
            furtherFieldsWrapper.classList.remove('d-none');
        }
    };

    emailInput.addEventListener('blur', handleValidation);
    
    emailInput.addEventListener('keydown', (event) => {
        // Prüfen, ob die gedrückte Taste "Enter" war
        if (event.key === 'Enter') {
            // 1. Das Standard-Verhalten (Formular absenden) unterbinden
            event.preventDefault();

            // 2. Die AJAX-Validierung manuell auslösen
            handleValidation();
        }
    });
}