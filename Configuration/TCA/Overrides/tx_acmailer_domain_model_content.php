<?php

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Resource\File;

/**
 * Registriert die TCA-Overrides für tx_acmailer_domain_model_content
 * aus der tx_publicrelations Extension (Event/News-Relationen und Overwrites).
 */
(static function () {

    $tableName = 'tx_acmailer_domain_model_content';
    $ll = 'LLL:EXT:ac_mailer/Resources/Private/Language/locallang_db.xlf:tx_acmailer_domain_model_content';
    $llg = 'LLL:EXT:ac_mailer/Resources/Private/Language/locallang_db.xlf:tx_acmailer_general';
    $llPub = 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_content';
    $llPubG = 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_general';

    // --- 1. Neue Spalten (Relationen und Overwrite-Felder) definieren ---
    $newColumns = [
        'event' => [
            'exclude' => false,
            'label' => $llPubG . '.field.event',
            'config' => [
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'tx_publicrelations_domain_model_event',
                'size' => 1,
                'maxitems' => 1,
                'minitems' => 1,
                'default' => 0,
            ],
        ],
        'event_title' => ['exclude' => false, 'label' => $llPub . '.event_title', 'config' => ['type' => 'input', 'size' => 30, 'eval' => 'trim']],
        'event_date' => ['exclude' => false, 'label' => $llPub . '.event_date', 'config' => ['type' => 'input', 'size' => 30, 'eval' => 'trim']],
        'event_location' => ['exclude' => false, 'label' => $llPub . '.event_location', 'config' => ['type' => 'input', 'size' => 30, 'eval' => 'trim']],
        'event_description' => [
            'exclude' => false,
            'label' => $llPub . '.event_description',
            'config' => [
                'type' => 'text',
                'enableRichtext' => true,
                'richtextConfiguration' => 'default',
            ],
        ],
        'event_link' => [
            'exclude' => false,
            'label' => $llPub . '.event_link',
            'config' => ['type' => 'check', 'renderType' => 'checkboxToggle', 'default' => 1],
        ],
        'news' => [
            'exclude' => false,
            'label' => $llPubG . '.field.news',
            'config' => [
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'tx_publicrelations_domain_model_news',
                'size' => 1,
                'maxitems' => 1,
                'minitems' => 1,
                'default' => 0,
            ],
        ],
        'news_media' => [
            'exclude' => false,
            'label' => $llPub . '.news_media',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'items' => [
                    [
                        'label' => '',
                        'value' => 1,
                        'invertStateDisplay' => true
                    ]
                ],
                'default' => 1
            ],
        ],
    ];

    // TCA-Felder zur Content-Tabelle hinzufügen
    ExtensionManagementUtility::addTCAcolumns($tableName, $newColumns);

    // --- 2. Paletten für die Event-Overwrites hinzufügen ---
    // Repliziert die Original-Paletten-Struktur.
    ExtensionManagementUtility::addFieldsToPalette(
        $tableName,
        'paletteEventOverwrites', // Name der Palette im Original-TCA
        'event_title, --linebreak--, event_date, event_location, --linebreak--, event_description'
    );
    ExtensionManagementUtility::addFieldsToPalette(
        $tableName,
        'paletteImage', // Name der Palette im Original-TCA
        'image, image_full_width'
    );


    // --- 3. TYPES ERWEITERN, um die Original-Showitem-Logik zu replizieren ---
    // Hier verwenden wir die neuen String-Keys, um die alten numerischen Logiken zu binden.
    $newTypes = [
        // Typ 2: News
        'publicrelations_news' => [
            'showitem' => 'type, news, news_media, --div--;' . $llg . '.tab.layout, --palette--;;paletteLayout, --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.access, --palette--;;paletteAccess'
        ],
        // Typ 3: Event
        'publicrelations_event' => [
            'showitem' => 'type, event, event_title, event_link, --div--;' . $llg . '.tab.layout, --palette--;;paletteLayout, --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.access, --palette--;;paletteAccess'
        ],
        // Typ 6: Event (Link)
        'publicrelations_eventlink' => [
            'showitem' => 'type, event, --div--;' . $llg . '.tab.layout, --palette--;;paletteLayout, --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.access, --palette--;;paletteAccess'
        ],
        // Typ 4: Mailing Image
        'publicrelations_media' => [
            'showitem' => 'type, media, --div--;' . $llg . '.tab.layout, --palette--;;paletteLayout'
        ],
        // Typ 8: Event (Facie-Liste)
        'publicrelations_facielist' => [
            'showitem' => 'type, event, --div--;' . $llg . '.tab.layout, --palette--;;paletteLayout, --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.access, --palette--;;paletteAccess'
        ],
        // Typ 9: Event (Overwrites)
        'publicrelations_eventinfo' => [
            'showitem' => 'type, event, --palette--;;paletteEventOverwrites, --div--;' . $llg . '.tab.layout, --palette--;;paletteLayout, --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.access, --palette--;;paletteAccess'
        ],
    ];

    foreach ($newTypes as $typeKey => $typeConfig) {
        // Wenn TCA für $tableName bereits geladen ist:
        if (is_array($GLOBALS['TCA'][$tableName]['types'])) {
            $GLOBALS['TCA'][$tableName]['types'][$typeKey] = $typeConfig;
        }
    }

    // 1. Array der neuen Optionen
    $newItems = [
        ['LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_content.type.2', 'publicrelations_news'],
        ['LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_content.type.3', 'publicrelations_event'],
        ['LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_content.type.6', 'publicrelations_eventlink'],
        ['LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_content.type.4', 'publicrelations_media'],
        ['LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_content.type.8', 'publicrelations_facielist'],
        ['LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_content.type.9', 'publicrelations_eventinfo'],
    ];

    // 2. TCA des Select-Feldes erweitern
    $GLOBALS['TCA'][$tableName]['columns']['type']['config']['items'] = array_merge(
        $GLOBALS['TCA'][$tableName]['columns']['type']['config']['items'],
        $newItems
    );

})();