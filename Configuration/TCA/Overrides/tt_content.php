<?php

use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

defined('TYPO3') or die();

$pluginDefinitions = [
    ['Kundenliste', 'ClientList', 'Zeigt eine Liste von Kunden', 'ext-publicrelations-plugin-client-list'],
    ['Referenzliste', 'ClientReferences', 'Zeigt Kundenreferenzen', 'ext-publicrelations-plugin-client-references'],
    ['Kundenseite', 'ClientShow', 'Detailansicht eines Kunden mit Kampagnen & News', 'ext-publicrelations-plugin-client-show'],
    ['Rückmeldungsformular', 'AccreditationForm', 'Formular zur Rückmeldung von Gästen', 'ext-publicrelations-plugin-accreditation-form'],
    ['Pressecenter', 'PresscenterOverview', 'Pressebereich mit Schnellfreigabe', 'ext-publicrelations-plugin-presscenter-overview'],
    ['Homeinfos', 'PresscenterHome', 'Startseitenelement für Pressecenter', 'ext-publicrelations-plugin-presscenter-home'],
    ['Slider', 'SlideList', 'Frontend-Slider', 'ext-publicrelations-plugin-slide-list'],
    ['iCalendar-Export', 'EventIcal', 'Export von Veranstaltungen im iCal-Format', 'ext-publicrelations-plugin-event-ical'],
    ['Suche', 'SearchResult', 'Ergebnisse der Suchfunktion', 'ext-publicrelations-plugin-search-result'],
    ['Mail-Ansicht', 'MailView', 'Darstellung einer E-Mail im Frontend', 'ext-publicrelations-plugin-mail-view'],
    ['Labeldruck', 'EventPrintLabels', 'Erzeugt Namensschilder für Gäste', 'ext-publicrelations-plugin-event-printlabels'],
    ['Pressecenter | Persönliches Menü', 'PressecenterMenu', 'Gibt das Menü für das SideNav für Benutzer aus.', 'ext-publicrelations-plugin-pressecenter-menu'],
    ['Pressecenter | Meine Daten', 'PressecenterMyData', 'Gibt Daten für Benutzer im Pressecenter aus.', 'ext-publicrelations-plugin-pressecenter-mydata'],
];


foreach ($pluginDefinitions as [$label, $pluginName, $description, $iconIdentifier]) {
    $pluginIdentifier = ExtensionUtility::registerPlugin(
        'Publicrelations',
        $pluginName,
        $label,
        $iconIdentifier,
        'Allegria',
        $description
    );

    // Optional: Flexform
    // ExtensionManagementUtility::addPiFlexFormValue(...);

    $GLOBALS['TCA']['tt_content']['ctrl']['typeicon_classes'][$pluginIdentifier] = $iconIdentifier;
}
