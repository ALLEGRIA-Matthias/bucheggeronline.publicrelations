function openCheckInModal(accreditationId) {
    var fetchUrl = checkInModalUrlTemplate.replace('%23%23%23UID%23%23%23', accreditationId);
    fetch(fetchUrl)
    .then(response => {        
        // Prüfe den Content-Type der Antwort
        if (response.headers.get("content-type").includes("application/json")) {
            // Verarbeite die Antwort als JSON
            return response.json().then(data => {
                if (data.error || data.warning) {
                    // Bereitet die Nachricht vor
                    var message = data.message + "\n\nBitte zum \"Clearing Desk\" schicken.";
                    // Zeige TYPO3 Notification
                    require(['TYPO3/CMS/Backend/Notification'], function (Notification) {
                        if (data.error) {
                            Notification.error('Fehler – Akkred.-Nr.:' + accreditationId, message);
                        }
                        if (data.warning) {
                            Notification.warning('Warnung – Akkred.-Nr.:' + accreditationId, message, -1); // -1 sorgt dafür, dass die Notification stehen bleibt
                        }
                    });
                }
            });
        } else {
            // Verarbeite die Antwort als Text/HTML
            return response.text().then(html => {
                // Zeige das Modal mit dem HTML-Inhalt
                document.getElementById('modalBody').innerHTML = html;
                $('#myModal').modal('show');
            });
        }
    })
    .catch(error => {
        console.error('Fehler beim Laden der Akkreditierungsdetails:', error);
        var errorMessage = "Ein Fehler ist aufgetreten.\n\nBitte zum \"Clearing Desk\" schicken.";
        require(['TYPO3/CMS/Backend/Notification'], function (Notification) {
            Notification.error('Fehler – Akkred.-Nr.:' + accreditationId, errorMessage);
        });
    });
}


// function openCheckInModal(accreditationId) {
//     var fetchUrl = checkInModalUrlTemplate.replace('%23%23%23UID%23%23%23', accreditationId);
//     fetch(fetchUrl)
//     .then(response => {
//         if (response.headers.get("content-type").includes("application/json")) {
//             // Verarbeitet JSON-Fehlermeldung
//             TYPO3.Notification.error('Fehler 1', data.message);
//             return response.json();
//         } else {
//             // Annahme: Die Antwort ist HTML für das Modal, wenn kein Fehler vorliegt
//             require(['TYPO3/CMS/Backend/Notification'], function (Notification) {
//                 Notification.error(
//                         'Fehler 2',
//                         data.message
//                 );
//             });
//             return response.text();
//         }
//     })
//     .then(data => {
//         if (typeof data === 'object' && data.error) {
//             // Zeige TYPO3 Notification mit Fehlermeldung
//             require(['TYPO3/CMS/Backend/Notification'], function (Notification) {
//                 Notification.error(
//                         'Fehler 3',
//                         data.message
//                 );
//             });
//         } else if (typeof data === 'string') {
//             // Die Antwort ist HTML, zeige das Modal
//             document.getElementById('modalBody').innerHTML = data;
//             $('#myModal').modal('show');
//         }
//     })
//     .catch(error => console.error('Fehler beim Laden der Akkreditierungsdetails:', error));
// };

// function openCheckInModal(accreditationId) {
//     var fetchUrl = checkInModalUrlTemplate.replace('%23%23%23UID%23%23%23', accreditationId);
//     console.log(fetchUrl);

//     fetch(fetchUrl)
//         .then(response => response.text())
//         .then(html => {
//             // Dekodiere HTML-Entities im erhaltenen HTML-String
//             var parser = new DOMParser();
//             var doc = parser.parseFromString(html, 'text/html');
//             html = doc.documentElement.textContent;

//             // Verwende TYPO3 Modal API, um den dekodierten HTML-String darzustellen
//             require(['TYPO3/CMS/Backend/Modal'], function(Modal) {
//                 Modal.advanced({
//                     type: Modal.types.default,
//                     title: 'Akkreditierungsdetails',
//                     content: html,
//                     size: Modal.sizes.large,
//                     buttons: [
//                         {
//                             text: 'Schließen',
//                             btnClass: 'btn-default',
//                             name: 'close',
//                             trigger: function() {
//                                 Modal.dismiss();
//                             }
//                         }
//                     ]
//                 });
//             });
//         })
//         .catch(error => console.error('Error loading the accreditation details:', error));
// }

$(document).ready(function() {
    $(".filter-table").keyup( function() {

    var value = $(this).val().toLowerCase();
    var tableId = $(this).attr("data-filter-tbody-id");

    var counterId = $(this).attr("data-counter-id");
    var counterLabel = $(this).attr("data-counter-label");

    $(tableId + " tr").filter(function() {
      $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
    });

    $(counterId).text($(tableId + ' tr:visible').length + " von " + $(tableId + ' tr').length + " " + counterLabel);

  });

});

var table = document.getElementById("accreditations");
if (table) { new Tablesort(table) };    

document.addEventListener('DOMContentLoaded', function() {

    const searchInput = document.querySelector('.filter-table');

    searchInput.addEventListener('keypress', function(e) {        
        if (e.which === 13 || e.keyCode === 13) {
            e.preventDefault(); // Verhindert das Standardverhalten des Formularabsendens
            const uid = this.value.trim();
            if (uid.match(/^\d+$/)) {
                openCheckInModal(uid);
            } else {
                alert("Bitte geben Sie eine gültige UID ein.");
            }
        }
    });
});