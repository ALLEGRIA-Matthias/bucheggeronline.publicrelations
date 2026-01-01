$(document).ready(function() {
    var $contactSelects = $('#contactSelect, .contactSelect');

    if ($contactSelects.length > 0) {
        $('#contactSelect, .contactSelect').select2({
            minimumInputLength: 3,
            ajax: {
                data: function(params) {
                    var nomailing = $('#contactSelect, .contactSelect').data('contacts-nomailing');
                    var maxResults = $('#contactSelect, .contactSelect').data('contacts-maxresults');
                    var pid = $('#contactSelect, .contactSelect').data('contacts-pid');
                    return {
                        q: params.term,
                        nomailing: nomailing ? 1 : 0, // Fehlendes Komma hinzugefügt
                        maxResults: maxResults,
                        pid: pid
                    };
                },
                transport: function(params, success, failure) {
                    require(['TYPO3/CMS/Core/Ajax/AjaxRequest'], function (AjaxRequest) {
                        const url = TYPO3.settings.ajaxUrls.publicrelations_contactsearch; // Stelle sicher, dass diese Einstellung existiert
                        new AjaxRequest(url)
                        .withQueryArguments({
                            q: params.data.q,
                            nomailing: params.data.nomailing,
                            maxResults: params.data.maxResults,
                            pid: params.data.pid
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
                    // Prüfe, ob das <select> Element eine bestimmte Klasse oder ein data-Attribut hat
                    var includeSpecialOptions = $('#contactSelect').data('include-special-options');
                
                    if (includeSpecialOptions) {
                        // Füge die Optionen "New" und "Manual" zu den Ergebnissen hinzu
                        data.unshift(
                            { id: 'new', text: 'NEUEN KONTAKT in Datenbank aufnehmen', gender: 's', customOption: true },
                            { id: 'manual', text: 'KONTAKT manuell eintragen, nicht speichern', gender: 's', customOption: true }
                        );
                    }

                    return {
                        results: data
                    };
                }
            },
            placeholder: 'Kontakt suchen',
            language: "de",
            templateSelection: function(data) {
                // Falls es sich um eine spezielle Option handelt oder keine ID vorhanden ist, zeige den Text direkt an
                if (data.customOption || !data.id) {
                    return data.text;
                }
                // Erstelle ein <span>-Element für die gesamte Anzeige
                var $display = $('<span></span>');
            
                // Bestimme den anzuzeigenden Namen
                var displayName = data.stageName || data.firstName + ' ' + data.middleName + ' ' + data.lastName;
                if (!displayName.trim()) {
                    // Wenn kein Name vorhanden ist, verwende die Firma oder die E-Mail-Adresse
                    displayName = data.company || data.email;
                }
            
                // Füge den Namen in einem <strong>-Element hinzu, um ihn fett zu machen
                var $name = $('<strong></strong>').text(displayName.trim());
                $display.append($name);
            
                // Wenn die Firma vorhanden ist und nicht als Hauptname verwendet wird, füge sie in Klammern hinzu
                if (data.company && displayName.trim() !== data.company.trim()) {
                    var $company = $('<span></span>').text(' (' + data.company + ')');
                    $display.append($company);
                }
            
                return $display;
            },
            templateResult: function(data) {

                // Prüfung, ob es sich um eine der speziellen Optionen handelt
                if (data.customOption) {
                    // Für spezielle Optionen nur den Text in einem einfachen <strong> Element anzeigen
                    var $specialOption = $('<strong></strong>').text(data.text);
                    return $('<div class="d-flex align-items-center"></div>').append($specialOption);
                }

                if (!data.id) {
                    return data.text; // Behandle Platzhaltertexte
                }
                
                // Erstelle das äußere Div, das die Gesamtstruktur enthält
                var $result = $('<div class="d-flex align-items-center"></div>');

                // Überprüfe, ob ein Bildpfad vorhanden ist
                if (data.image) {
                    // Erstelle ein <img>-Element für das Bild
                    var $image = $('<img src="' + data.image + '" alt="Kontaktbild" style="width: 30px; height: 30px; border-radius: 50%; margin-right: 10px;">');
                    $result.prepend($image); // Füge das Bild hinzu
                } else {
            
                    // Bestimme die Badge-Farbe und das Icon basierend auf dem Geschlecht
                    var badgeColor = '', icon = '';
                    switch (data.gender) {
                        case 'm': badgeColor = 'bg-blue'; icon = 'user'; break;
                        case 'f': badgeColor = 'bg-pink'; icon = 'user-female'; break;
                        case 'v': badgeColor = 'bg-gray'; icon = 'users'; break;
                        case 's': badgeColor = 'bg-gold'; icon = 'star'; break;
                        default: badgeColor = 'bg-default'; icon = 'user';
                    }
                
                    // Füge das Icon hinzu, das über alle Zeilen sichtbar sein soll
                    var $icon = $('<span class="gender-badge ' + badgeColor + '"><svg class="icon fill-white"><use xlink:href="/typo3conf/ext/allegria_communications/Resources/Public/Images/glyphicons-basics.svg#' + icon + '"></use></svg></span>');
                    $result.append($icon);

                }
            
                // Erstelle ein Div für die Textinhalte neben dem Icon
                var $textContainer = $('<div class="flex-grow-1"></div>');
            
                // Bestimme den anzuzeigenden Namen
                var fullName = data.firstName + ' ' + data.middleName + ' ' + data.lastName;
                var displayName = data.stageName || fullName.trim() || data.company || data.email; // Verwende E-Mail, wenn kein Name/Firma vorhanden
            
                // Füge den Namen in der ersten Zeile hinzu
                var $name = $('<strong></strong>').text(displayName);
                $textContainer.append($name);
            
                // Zweite Zeile: Firmendaten und Position
                var isCompanyNameDisplayedAsName = !data.stageName && !fullName.trim() && data.company;
                var companyInfo = '';
                if (data.company && !isCompanyNameDisplayedAsName) {
                    // Firma vorhanden und nicht als Name verwendet
                    companyInfo = data.company + (data.position ? ' (' + data.position + ')' : '');
                } else if (data.position && isCompanyNameDisplayedAsName) {
                    // Nur Position anzeigen, wenn Firma als Name verwendet wird
                    companyInfo = data.position;
                }

                if (companyInfo) {
                    var $companyInfo = $('<div></div>').text(companyInfo);
                    $textContainer.append($companyInfo);
                }
            
                // Dritte Zeile: Seitentitel und Kategorien, falls vorhanden
                var $info = $('<div></div>');
                if (data.pageTitle) {
                    var $pageTitleBadge = $('<span class="badge bg-gray mr-1"></span>').text(data.pageTitle);
                    $info.append($pageTitleBadge);
                }
                if (data.categories) {
                    data.categories.forEach(function(category) {
                        var $categoryBadge = $('<span class="badge bg-primary text-dark mr-1"></span>').text(category);
                        $info.append($categoryBadge);
                    });
                }
                $textContainer.append($info);
            
                // Füge den Text-Container zum äußeren Div hinzu
                $result.append($textContainer);
            
                return $result;
            }
        });
    }
});