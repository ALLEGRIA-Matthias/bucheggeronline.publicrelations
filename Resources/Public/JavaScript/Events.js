$(document).ready(function() {
    var $selectEvents = $('#selectEvents');
    var isMenu = $selectEvents.data('menu') === true;
    var menuTarget = $selectEvents.data('menu-target') || '_self';

    $selectEvents.select2({
        minimumInputLength: 3,
        ajax: {
            data: function(params) {
                return {
                    q: params.term
                };
            },
            transport: function(params, success, failure) {
                require(['TYPO3/CMS/Core/Ajax/AjaxRequest'], function (AjaxRequest) {
                    const url = TYPO3.settings.ajaxUrls.publicrelations_eventsearch; // Stelle sicher, dass diese Einstellung existiert
                    new AjaxRequest(url)
                    .withQueryArguments({
                        q: params.data.q
                    })
                    .get()
                    .then(async function (response) {
                        const resolved = await response.resolve();
                        success(resolved);
                    })
                    .catch(function (error) {
                        failure(error);
                        console.error(error);
                    });
                });
                return {
                    abort: function() {
                        // Logik zum Abbrechen der Anfrage, falls notwendig
                    }
                };
            },
            processResults: function(data) {
                return {
                    results: data.map(group => ({
                        text: group.groupName, // Für die Gruppierung im Dropdown
                        children: group.events.map(event => ({
                            id: event.id,
                            text: event.title,
                            date: event.date,
                            guestCount: event.guestCount,
                            location: event.location,
                            city: event.city,
                            groupLogo: group.groupLogo,
                            url: event.url
                        }))
                    }))
                };
            },
        },
        placeholder: 'Nach Event suchen',
        language: "de",
        templateSelection: function(data) {
            // Prüfe, ob ein Wert ausgewählt ist (d.h. das Datenobjekt hat eine 'id')
            if (data.id) {
                var eventDate = new Date(data.date * 1000);
                var day = eventDate.toLocaleDateString("de-DE", { year: 'numeric', month: '2-digit', day: '2-digit' });

                // Erstelle ein jQuery-Objekt mit dem Markup
                var $markup = $('<span></span>');
                $markup.append('<strong>' + data.text + '</strong>');
                $markup.append(' am ' + day);

                return $markup;
            } else {
                // Für den Platzhalter oder andere Fälle ohne ausgewählten Wert, gib einfach den Text zurück
                return data.text;
            }
        },
        templateResult: function(data) {
            if (!data.id) return data.text; // Gruppierung oder Platzhalter
        
            var eventDate = new Date(data.date * 1000);
            var day = eventDate.toLocaleDateString("de-DE", { year: 'numeric', month: '2-digit', day: '2-digit' });
            var time = eventDate.toLocaleTimeString("de-DE", { hour: '2-digit', minute: '2-digit' });
            var dayOfWeek = eventDate.toLocaleDateString("de-DE", { weekday: 'short' });
        
            // Logo-HTML zusammenbauen, wenn ein Logo vorhanden ist
            var logoHtml = data.groupLogo ? '<div class="event-logo me-3 d-flex align-items-center"><img src="' + data.groupLogo + '" alt="Logo" style="width: 50px; height: 50px;"></div>' : '';
        
            // Erstelle das Markup als jQuery-Objekt
            var $markup = $(
                '<div class="d-flex align-items-center">' +
                    logoHtml +
                    '<div class="me-3">' +
                        '<div>' + day + '</div>' +
                        '<div><small>' + dayOfWeek + ', ' + time + '</small></div>' +
                    '</div>' +
                    '<div>' +
                        '<strong>' + data.text + '</strong>' +
                        '<div><small>' + data.location + ', ' + data.city + '</small></div>' +
                        (data.guestCount ? '<span class="badge bg-primary">' + data.guestCount + ' Gäste</span>' : '') +
                    '</div>' +
                '</div>'
            );
        
            return $markup;
        },
    }).on('select2:open', function(e) {
        setTimeout(function() {
            // Annahme: #selectEvents ist das einzige Select2-Element auf der Seite
            // Wenn es mehrere gibt, benötigst du möglicherweise einen spezifischeren Selektor
            $('#select2-selectEvents-results .select2-search__field').first().focus();
        }, 500);
    });



    // Wenn es als Menü funktionieren soll, füge einen Event-Listener hinzu
    if (isMenu) {
        $selectEvents.on('select2:select', function(e) {
            var data = e.params.data;
            // Überprüfe, ob eine URL vorhanden ist
            if (data.url) {
                // Verwende window.open für die Weiterleitung
                window.open(data.url, menuTarget);
            }
        });
    }
    
});
