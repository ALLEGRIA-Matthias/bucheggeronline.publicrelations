/**
 * Steuerung für das Akkreditierungs-Formular (Neu/Manuell Toggle)
 */
class NewAction {
    constructor() {
        this.container = document.querySelector('form[name="newAccreditation"]');
        if (!this.container) return;

        this.selectWrapper = this.container.querySelector('.js-guest-select-wrapper');
        this.manualWrapper = this.container.querySelector('[data-toggle-value="manual"]'); // Container für manuelle Felder
        
        // Inputs
        this.selectInput = this.container.querySelector('#contactSelect'); // Das TomSelect
        this.hiddenGuestInput = this.container.querySelector('#guest-mode-input'); // Das versteckte Feld für 'manual'
        
        // Buttons
        this.btnSwitchManual = this.container.querySelector('#btn-switch-manual');
        this.btnSwitchSelect = this.container.querySelector('#btn-switch-select');

        this.init();
    }

    init() {
        if (this.btnSwitchManual) {
            this.btnSwitchManual.addEventListener('click', (e) => {
                e.preventDefault();
                this.enableManualMode();
            });
        }

        if (this.btnSwitchSelect) {
            this.btnSwitchSelect.addEventListener('click', (e) => {
                e.preventDefault();
                this.enableSelectMode();
            });
        }
        
        // Initialer State Check (falls nach Validierungsfehler neu geladen)
        if (this.hiddenGuestInput && this.hiddenGuestInput.value === 'manual' && !this.hiddenGuestInput.disabled) {
            this.enableManualMode();
        }
    }

    enableManualMode() {
        // 1. UI Umschalten
        if (this.selectWrapper) this.selectWrapper.classList.add('d-none');
        
        // Alle manuellen Felder anzeigen (alle div's mit data-toggle-value="manual")
        const manualFields = this.container.querySelectorAll('[data-toggle-value="manual"]');
        manualFields.forEach(el => el.style.display = 'block');

        // Buttons tauschen
        if (this.btnSwitchManual) this.btnSwitchManual.classList.add('d-none');
        if (this.btnSwitchSelect) this.btnSwitchSelect.classList.remove('d-none');

        // 2. Daten-Logik
        // Select deaktivieren, damit kein Wert gesendet wird (oder leerer Wert)
        if (this.selectInput) {
            this.selectInput.disabled = true;
            // Falls TomSelect Instanz existiert, clearen
            if (this.selectInput.tomselect) {
                this.selectInput.tomselect.clear();
            }
        }

        // Hidden Input aktivieren und auf 'manual' setzen
        if (this.hiddenGuestInput) {
            this.hiddenGuestInput.disabled = false;
            this.hiddenGuestInput.value = 'manual';
        }
    }

    enableSelectMode() {
        // 1. UI Umschalten
        if (this.selectWrapper) this.selectWrapper.classList.remove('d-none');
        
        const manualFields = this.container.querySelectorAll('[data-toggle-value="manual"]');
        manualFields.forEach(el => el.style.display = 'none');

        // Buttons tauschen
        if (this.btnSwitchManual) this.btnSwitchManual.classList.remove('d-none');
        if (this.btnSwitchSelect) this.btnSwitchSelect.classList.add('d-none');

        // 2. Daten-Logik
        // Select aktivieren
        if (this.selectInput) {
            this.selectInput.disabled = false;
        }

        // Hidden Input deaktivieren
        if (this.hiddenGuestInput) {
            this.hiddenGuestInput.disabled = true;
        }
    }
}

// Init
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => new NewAction());
} else {
    new NewAction();
}