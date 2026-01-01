<?php
defined('TYPO3') or die();

// Override news icon
$GLOBALS['TCA']['pages']['columns']['module']['config']['items'][] = [
    0 => 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_general.pagetree',
    1 => 'publicrelations',
    2 => 'pagetree-folder-publicrelations'
];

$GLOBALS['TCA']['pages']['ctrl']['typeicon_classes']['contains-publicrelations'] = 'pagetree-folder-publicrelations';

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::registerPageTSConfigFile(
    'publicrelations',
    'Configuration/TSconfig/Page/publicrelations_folder.tsconfig',
    'EXT:publicrelations :: Restrict pages to database records'
);
