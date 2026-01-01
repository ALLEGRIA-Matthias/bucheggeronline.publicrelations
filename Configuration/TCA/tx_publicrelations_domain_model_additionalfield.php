<?php

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use BucheggerOnline\Publicrelations\Domain\Model\Dto\EmConfiguration;
use BucheggerOnline\Publicrelations\Backend\TCA;

$ll = 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_additionalfield';
$llg = 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_general';
$configuration = GeneralUtility::makeInstance(EmConfiguration::class);

return [
    'ctrl' => [
        'title' => $ll,
        'label' => 'label',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'versioningWS' => true,
        'origUid' => 't3_origuid',
        'delete' => 'deleted',
        'sortby' => 'sorting',
        'hideAtCopy' => 1,
        'hideTable' => 1,
        'enablecolumns' => [
            'disabled' => 'hidden',
            'starttime' => 'starttime',
            'endtime' => 'endtime',
        ],
        'searchFields' => 'label,description,position,type,options',
        'iconfile' => 'EXT:publicrelations/Resources/Public/Icons/tx_publicrelations_domain_model_additionalfield.svg'
    ],
    'types' => [
        '1' => [
            'showitem' => '
                --div--;LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_general.tab.general,
                    --palette--;;paletteGeneral,
                --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.access,
                    --palette--;;paletteAccess,
                '
        ],
    ],
    'palettes' => [
        'paletteGeneral' => [
            'showitem' => '
                type, position, required, --linebreak--,
                label, icon, --linebreak--,
                description, options, --linebreak--,
                summary, accreditation, invitation, confirmation',
            'canNotColapse' => 1
        ],
        'paletteAccess' => TCA::getPaletteAccess(),
    ],
    'columns' => [
        'position' => [
            'exclude' => false,
            'label' => $ll . '.position',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [$ll . '.position.0', 0],
                    [$ll . '.position.1', 1],
                ],
                'default' => 0,
            ],
        ],
        'label' => [
            'exclude' => false,
            'label' => $ll . '.label',
            'config' => [
                'type' => 'input',
                'size' => 10,
                'eval' => 'trim'
            ],
        ],
        'description' => [
            'exclude' => false,
            'label' => $ll . '.description',
            'config' => [
                'type' => 'text',
                'cols' => 40,
                'rows' => 5,
                'eval' => 'trim'
            ],
        ],
        'icon' => [
            'exclude' => false,
            'label' => $ll . '.icon',
            'config' => [
                'type' => 'input',
                'size' => 10,
                'eval' => 'trim'
            ],
        ],
        'type' => [
            'exclude' => false,
            'label' => $ll . '.type',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [$ll . '.type.0', 0],
                    [$ll . '.type.1', 1],
                    [$ll . '.type.2', 2],
                    [$ll . '.type.3', 3],
                    [$ll . '.type.4', 4],
                    [$ll . '.type.5', 5],
                    [$ll . '.type.6', 6],
                ],
                'default' => 0,
            ],
        ],
        'options' => [
            'exclude' => false,
            'label' => $ll . '.options',
            'config' => [
                'type' => 'text',
                'cols' => 40,
                'rows' => 5,
                'eval' => 'trim'
            ],
        ],
        'required' => [
            'exclude' => false,
            'label' => $ll . '.required',
            'config' => [
                'type' => 'check',
                'items' => [
                    [
                        0 => '',
                        1 => ''
                    ]
                ],
                'default' => 0,
            ]
        ],
        'summary' => [
            'exclude' => false,
            'label' => $ll . '.summary',
            'config' => [
                'type' => 'check',
                'items' => [
                    [
                        0 => '',
                        1 => ''
                    ]
                ],
                'default' => 0,
            ]
        ],
        'accreditation' => [
            'exclude' => false,
            'label' => $ll . '.accreditation',
            'config' => [
                'type' => 'check',
                'items' => [
                    [
                        0 => '',
                        1 => ''
                    ]
                ],
                'default' => 0,
            ]
        ],
        'invitation' => [
            'exclude' => false,
            'label' => $ll . '.invitation',
            'config' => [
                'type' => 'check',
                'items' => [
                    [
                        0 => '',
                        1 => ''
                    ]
                ],
                'default' => 0,
            ]
        ],
        'confirmation' => [
            'exclude' => false,
            'label' => $ll . '.confirmation',
            'config' => [
                'type' => 'check',
                'items' => [
                    [
                        0 => '',
                        1 => ''
                    ]
                ],
                'default' => 0,
            ]
        ],

        'event' => [
            'exclude' => false,
            'label' => $ll . '.event',
            'config' => [
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'tx_publicrelations_domain_model_event',
                'size' => 1,
                'minitems' => 1,
                'maxitems' => 1,
                'hideSuggest' => 0,
                'multiple' => 0,
                'default' => 0,
                'fieldControl' => [
                    'addRecord' => [
                        'disabled' => true,
                    ],
                    'editPopup' => [
                        'disabled' => true,
                    ],
                    'elementBrowser' => [
                        'disabled' => true,
                    ],
                    'insertClipboard' => [
                        'disabled' => true,
                    ],
                    'listModule' => [
                        'disabled' => true,
                    ]
                ],
                'fieldWizard' => [
                    'recordsOverview' => [
                        'disabled' => true,
                    ],
                ],
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
                'foreign_table' => 'tx_publicrelations_domain_model_accreditation',
                'foreign_table_where' => 'AND {#tx_publicrelations_domain_model_accreditation}.{#pid}=###CURRENT_PID### AND {#tx_publicrelations_domain_model_accreditation}.{#sys_language_uid} IN (-1,0)',
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
        'hidden' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.visible',
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
            ],
        ],
        'starttime' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.starttime',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputDateTime',
                'eval' => 'datetime,int',
                'default' => 0,
                'behaviour' => [
                    'allowLanguageSynchronization' => true
                ]
            ],
        ],
        'endtime' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.endtime',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputDateTime',
                'eval' => 'datetime,int',
                'default' => 0,
                'range' => [
                    'upper' => mktime(0, 0, 0, 1, 1, 2038)
                ],
                'behaviour' => [
                    'allowLanguageSynchronization' => true
                ]
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

    ],
];
