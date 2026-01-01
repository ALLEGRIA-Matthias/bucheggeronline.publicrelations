export function initializeCloseWindowButton() {
    const closeButtons = document.querySelectorAll('.js-close-window-button');

    closeButtons.forEach(button => {
        button.addEventListener('click', function(event) {
            event.preventDefault(); // Verhindert das Navigieren zu href="#"
            
            // Versuche, zur vorherigen Seite in der Browser-Historie zurückzukehren
            window.history.back(); 
        });
    });
}