export function initializePrintButton() {
    const printButtons = document.querySelectorAll('.js-print-button');

    printButtons.forEach(button => {
        button.addEventListener('click', function(event) {
            event.preventDefault(); // Verhindert das Navigieren zu href="#"
            window.print();
        });
    });
}