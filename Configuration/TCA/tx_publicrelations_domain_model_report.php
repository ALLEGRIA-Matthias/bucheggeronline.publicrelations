<?php
defined('TYPO3') or die();

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

return [
    'ctrl' => [
        'title' => 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_report',
        'label' => 'title',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'delete' => 'deleted',
        'searchFields' => 'title,status,type,medium,department,media_type,publication_id,apa_guid',
        'iconfile' => 'EXT:publicrelations/Resources/Public/Icons/tx_publicrelations_domain_model_report.svg',
        'type' => 'type',
        // 'typeicon_classes' => [
        //     'clipping' => 'apps-pagetree-clipping',
        //     'pr' => 'apps-pagetree-pr',
        //     'default' => 'apps-pagetree-default',
        // ],
    ],
    'types' => [
        'clipping' => [
            'showitem' => '
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
                    --palette--;;status_palette,
                    --palette--;;relations_palette,
                    --palette--;;content_palette,
                    --palette--;;media_palette,
                    --palette--;;publication_palette,
                    --palette--;;kpi_palette,
                --div--;Dateien & Links,
                    files, links,
                --div--;Internal,
                    notes,
                    logs,
                    --palette--;;internal_palette,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                    hidden
            ',
        ],
        '0' => [
            'showitem' => '
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
                    --palette--;;status_palette,
                    --palette--;;relations_palette,
                    notes,
                --div--;Dateien & Links,
                    files, links,
                --div--;Internal,
                    logs,
                    --palette--;;internal_palette,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                    hidden
            ',
        ],
    ],
    // Paletten für aufgeräumtes Interface
    'palettes' => [
        'status_palette' => ['showitem' => 'type, date, --linebreak--, title, status, reported'],
        'relations_palette' => ['showitem' => 'client, campaign'],
        'content_palette' => ['showitem' => 'subtitle, clippingroute, --linebreak--, content'],
        'media_palette' => ['showitem' => 'medium, department, media_type'],
        'publication_palette' => ['showitem' => 'publication_frequency, publication_id, page_number'],
        'kpi_palette' => ['showitem' => 'reach, ad_value'],
        'internal_palette' => ['showitem' => 'apa_guid, apa_link, approval_token, --linebreak--, data'],
    ],
    'columns' => [
        'type' => [
            'exclude' => false,
            'label' => 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_report.type',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['Clipping', 'clipping'],
                    ['PR', 'pr'],
                    ['Social Media', 'social_media'],
                    // ...
                ],
                'default' => 'clipping',
            ],
        ],
        // Status-Auswahl (war 'status tinytext')
        'status' => [
            'exclude' => false,
            'label' => 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_report.status',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['Idee', 'idea'],
                    ['In Arbeit', 'in_progress'],
                    ['Vorbereitet', 'prepared'],
                    ['Wartet auf Freigabe', 'approval_pending'],
                    ['Abgeschlossen', 'done'],
                    ['Geclippet', 'clipped'],
                    ['Aus Verrechnung genommen', 'clipping_reported'],
                ],
                'default' => 'clipped',
            ],
        ],

        'title' => [
            'exclude' => false,
            'label' => 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_report.title',
            'config' => [
                'type' => 'input',
                'size' => 50,
                'eval' => 'trim,required',
            ],
        ],
        'subtitle' => [
            'exclude' => false,
            'label' => 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_report.subtitle',
            'config' => [
                'type' => 'text',
            ],
        ],
        'date' => [
            'exclude' => false,
            'label' => 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_report.date',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputDateTime',
                'eval' => 'datetime,required',
                'default' => 0
            ],
        ],
        'client' => [
            'exclude' => false,
            'label' => 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_report.client',
            'config' => [
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'tx_publicrelations_domain_model_client',
                'minitems' => 1,
                'maxitems' => 1,
                'size' => 1,
                'default' => 0
            ],
        ],
        'campaign' => [
            'exclude' => false,
            'label' => 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_report.campaign',
            'config' => [
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'tx_publicrelations_domain_model_campaign',
                'minitems' => 0,
                'maxitems' => 1,
                'size' => 1,
                'default' => 0
            ],
        ],
        'files' => [
            'exclude' => false,
            'label' => 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_report.files',
            'config' => [
                'type' => 'file',
                'maxitems' => 99,
                'allowed' => 'common-image-types, common-text-types, common-media-types',
            ],
        ],

        // --- KORREKTES IRRE FÜR LINKS ---
        // Nutzt 'tx_publicrelations_domain_model_link.report'
        'links' => [
            'exclude' => false,
            'label' => 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_report.links',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_publicrelations_domain_model_link',
                'foreign_field' => 'report', // Das ist das Feld in der Kind-Tabelle!
                'maxitems' => 99,
                'appearance' => [
                    'collapseAll' => 1,
                    'levelLinksPosition' => 'top',
                    'showSynchronizationLink' => 1,
                    'showPossibleLocalizationRecords' => 1,
                    'showRemovedLocalizationRecords' => 1,
                    'useSortable' => 1,
                ],
                'behaviour' => [
                    'enableCascadingDelete' => 1,
                ],
            ],
        ],

        // --- KORREKTES IRRE FÜR LOGS ---
        // Nutzt 'tx_publicrelations_domain_model_log.report'
        'logs' => [
            'exclude' => false,
            'label' => 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_report.logs',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_publicrelations_domain_model_log',
                'foreign_field' => 'report', // Das Feld in der Kind-Tabelle
                'maxitems' => 9999,
                'appearance' => [
                    'collapseAll' => 1,
                    'levelLinksPosition' => 'top',
                ],
                'readOnly' => true, // Logs sollten nur vom System geschrieben werden
            ],
        ],

        'notes' => [
            'exclude' => false,
            'label' => 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_report.notes',
            'config' => [
                'type' => 'text',
                'enableRichtext' => true,
                'richtextConfiguration' => 'full',
            ],
        ],
        'content' => [
            'exclude' => false,
            'label' => 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_report.content',
            'config' => [
                'type' => 'text',
                'enableRichtext' => true,
                'richtextConfiguration' => 'full',
                'readOnly' => false
            ],
        ],
        'data' => [
            'exclude' => true, // Verstecken wir vor Redakteuren, ist nur Backup
            'label' => 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_report.data',
            'config' => [
                'type' => 'text',
                'rows' => 10,
                'cols' => 80,
                'readOnly' => true,
            ],
        ],
        'medium' => [
            'exclude' => false,
            'label' => 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_report.medium',
            'config' => ['type' => 'input', 'size' => 30, 'eval' => 'trim,required'],
        ],
        'department' => [
            'exclude' => false,
            'label' => 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_report.department',
            'config' => ['type' => 'input', 'size' => 30],
        ],
        'media_type' => [
            'exclude' => false,
            'label' => 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_report.media_type',
            'config' => ['type' => 'input', 'size' => 30],
        ],
        'publication_frequency' => [
            'exclude' => false,
            'label' => 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_report.publication_frequency',
            'config' => ['type' => 'input', 'size' => 30],
        ],
        'publication_id' => [
            'exclude' => false,
            'label' => 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_report.publication_id',
            'config' => ['type' => 'input', 'size' => 30],
        ],
        'page_number' => [
            'exclude' => false,
            'label' => 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_report.page_number',
            'config' => ['type' => 'input', 'size' => 10],
        ],
        'reach' => [
            'exclude' => false,
            'label' => 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_report.reach',
            'config' => ['type' => 'input', 'size' => 10, 'eval' => 'int', 'default' => 0],
        ],
        'ad_value' => [
            'exclude' => false,
            'label' => 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_report.ad_value',
            'config' => ['type' => 'input', 'size' => 10, 'eval' => 'int', 'default' => 0],
        ],
        'apa_guid' => [
            'exclude' => false,
            'label' => 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_report.apa_guid',
            'config' => ['type' => 'input', 'size' => 40, 'readOnly' => true],
        ],
        'apa_link' => [
            'exclude' => false,
            'label' => 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_report.apa_link',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputLink',
                'size' => 50,
                'eval' => 'trim',
                'softref' => 'typolink',
                'readOnly' => true
            ],
        ],
        'clippingroute' => [
            'exclude' => false,
            'label' => 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_report.clippingroute',
            'config' => [
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'tx_publicrelations_domain_model_clippingroute',
                'minitems' => 1,
                'maxitems' => 1,
                'size' => 1,
                'default' => 0,
                'readOnly' => false,
            ],
        ],
        'reported' => [
            'exclude' => false,
            'label' => 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_report.reported',
            'config' => ['type' => 'check', 'default' => 0],
        ],
        'approval_token' => [
            'exclude' => false,
            'label' => 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_report.approval_token',
            'config' => ['type' => 'input', 'size' => 40, 'readOnly' => true],
        ],
    ],
];