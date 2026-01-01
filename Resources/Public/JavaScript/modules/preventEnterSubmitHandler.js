// js/modules/preventEnterSubmitHandler.js

export function initializePreventEnterSubmit() {
    const formsToPreventEnter = document.querySelectorAll('form.js-prevent-enter-submit');

    if (formsToPreventEnter.length === 0) {
        return; // Nichts zu tun
    }

    formsToPreventEnter.forEach(formElement => {
        formElement.addEventListener('keydown', function(event) {
            // Prüfen, ob die Enter-Taste gedrückt wurde
            if (event.key === 'Enter' || event.keyCode === 13) {
                const targetElement = event.target;
                const targetNodeName = targetElement.nodeName.toLowerCase();
                const targetType = targetElement.type ? targetElement.type.toLowerCase() : '';

                // Verhindere das Absenden nur für bestimmte Input-Typen.
                // In <textarea> soll Enter einen Zeilenumbruch erzeugen.
                // Buttons (besonders type="submit") sollen weiterhin auf Enter reagieren, wenn sie fokussiert sind.
                if (
                    targetNodeName === 'input' &&
                    (targetType === 'text' ||
                     targetType === 'password' ||
                     targetType === 'email' ||
                     targetType === 'search' ||
                     targetType === 'tel' ||
                     targetType === 'url' ||
                     targetType === 'number'
                     // Füge hier weitere Input-Typen hinzu, falls nötig
                    )
                ) {
                    // Wenn sich das Input-Feld in einem TomSelect-Wrapper befindet
                    // UND TomSelect das Enter bereits verarbeitet hat (um eine Option auszuwählen),
                    // dann wurde event.preventDefault() möglicherweise schon im TomSelect onKeyDown Handler gerufen.
                    // Dieser generelle Handler hier ist ein Fallback oder für andere Felder.
                    
                    // console.log(`[PreventEnterSubmit] Enter in ${targetType}-Input unterdrückt für Formular:`, formElement.name || formElement.id);
                    event.preventDefault();
                }
                // Für TomSelect-Felder: Die spezifische onKeyDown-Logik in der TomSelect-Konfiguration
                // (die wir zuvor besprochen haben) sollte das Enter für die Auswahl bereits korrekt handhaben
                // und dort event.preventDefault() aufrufen. Dieser generelle Handler hier ist eine
                // zusätzliche Absicherung für andere Felder im Formular.
            }
        });
    });
}