<?php

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

/**
 * Registriert die TCA-Overrides für tx_acmailer_domain_model_mailing
 * aus der tx_publicrelations Extension (Client-Relation).
 */
(static function () {

    $tableName = 'tx_acmailer_domain_model_mailing';
    $llg = 'LLL:EXT:ac_mailer/Resources/Private/Language/locallang_db.xlf:tx_acmailer_general';

    // 1. Spalte hinzufügen (Relation zum Client-Model in Publicrelations)
    $newColumns = [
        'client' => [
            'exclude' => false,
            'label' => 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_client',
            'config' => [
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'tx_publicrelations_domain_model_client',
                'foreign_table' => 'tx_publicrelations_domain_model_client',
                'size' => 1,
                'maxitems' => 1,
                'minitems' => 1, // Wir machen den Client hier obligatorisch
                'default' => 0,
            ],
        ],
        'project' => [
            'exclude' => false,
            'label' => 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_campaign',
            'config' => [
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'tx_publicrelations_domain_model_campaign',
                'foreign_table' => 'tx_publicrelations_domain_model_campaign',
                'size' => 1,
                'maxitems' => 1,
                'minitems' => 0,
                'default' => 0,
            ],
        ],
    ];

    // TCA-Felder in die Tabelle einfügen
    ExtensionManagementUtility::addTCAcolumns($tableName, $newColumns);

    // 2. Client-Feld in die General Palette des Mailings einfügen
    // Wir nutzen den TCA-Utility-Aufruf, um das Feld an die gewünschte Position zu setzen.
    // Position: Vor 'subject' in der paletteGeneral.
    ExtensionManagementUtility::addFieldsToPalette(
        $tableName,
        'paletteGeneral',
        'client, project, --linebreak--',
        'before:subject'
    );

})();