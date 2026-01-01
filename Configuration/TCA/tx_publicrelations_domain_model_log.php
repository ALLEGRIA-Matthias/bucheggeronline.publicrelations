<?php

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use BucheggerOnline\Publicrelations\Domain\Model\Dto\EmConfiguration;
use BucheggerOnline\Publicrelations\Backend\TCA;

$ll = 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_log';
$llg = 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_general';
$configuration = GeneralUtility::makeInstance(EmConfiguration::class);

return [
    'ctrl' => [
        'title' => $ll,
        'label' => 'code',
        'label_alt' => 'function, subject',
        'label_alt_force' => true,
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'versioningWS' => true,
        'origUid' => 't3_origuid',
        'delete' => 'deleted',
        // 'rootLevel' => 1,
        'hideTable' => 1,
        'searchFields' => 'function,code,subject,notes',
        'iconfile' => 'EXT:publicrelations/Resources/Public/Icons/tx_publicrelations_domain_model_log.svg'
    ],
    'types' => [
        '1' => [
            'showitem' => '
                --div--;LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_general.tab.general,
                    --palette--;;paletteGeneral,
                    cruser_id,
                    notes,
                --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.access,
                    --palette--;;paletteAccess,
                '
        ],
    ],
    'palettes' => [
        'paletteGeneral' => [
            'showitem' => '
                code, function, subject',
            'canNotColapse' => 1
        ],
    ],
    'columns' => [
        'function' => [
            'exclude' => false,
            'label' => $ll . '.function',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim,required',
                'readOnly' => true
            ],
        ],
        'code' => [
            'exclude' => false,
            'label' => $ll . '.code',
            'config' => [
                'type' => 'input',
                'size' => 10,
                'eval' => 'trim,required',
                'readOnly' => true
            ],
        ],
        'subject' => [
            'exclude' => false,
            'label' => $ll . '.subject',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim,required',
                'readOnly' => true
            ],
        ],
        'notes' => [
            'exclude' => false,
            'label' => $ll . '.notes',
            'config' => [
                'type' => 'text',
                'cols' => 40,
                'rows' => 5,
                'eval' => 'trim',
                'readOnly' => true
            ],

        ],

        'accreditation' => [
            'exclude' => false,
            'label' => $llg . '.field.accreditation',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_publicrelations_domain_model_accreditation',
                'maxitems' => 1,
                'readOnly' => true,
            ],
        ],

        'event' => [
            'exclude' => false,
            'label' => $llg . '.field.event',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_publicrelations_domain_model_event',
                'maxitems' => 1,
                'readOnly' => true,
            ],
        ],

        'address' => [
            'exclude' => false,
            'label' => $llg . '.field.address',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tt_address',
                'maxitems' => 1,
                'readOnly' => true,
            ],
        ],

        'sys_language_uid' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.language',
            'config' => [
                'type' => 'language'
            ]
        ],
        'l10n_parent' => [
            'displayCond' => 'FIELD:sys_language_uid:>:0',
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.l18n_parent',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'default' => 0,
                'items' => [
                    ['', 0],
                ],
                'foreign_table' => 'tx_publicrelations_domain_model_log',
                'foreign_table_where' => 'AND {#tx_publicrelations_domain_model_log}.{#pid}=###CURRENT_PID### AND {#tx_publicrelations_domain_model_log}.{#sys_language_uid} IN (-1,0)',
            ],
        ],
        'l10n_diffsource' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        't3ver_label' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.versionLabel',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'max' => 255,
            ],
        ],
        'crdate' => [
            'label' => 'crdate',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputDateTime',
                'eval' => 'datetime',
            ]
        ],
        'tstamp' => [
            'label' => 'tstamp',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputDateTime',
                'eval' => 'datetime',
            ]
        ],
        'cruser_id' => [
            'label' => 'User',
            'config' => [
                'type' => 'passthrough',
            ],
        ],
    ],
];
