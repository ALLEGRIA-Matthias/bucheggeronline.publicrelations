<?php

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use BucheggerOnline\Publicrelations\Domain\Model\Dto\EmConfiguration;
use BucheggerOnline\Publicrelations\Backend\TCA;

$ll = 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_link';
$llg = 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_general';
$configuration = GeneralUtility::makeInstance(EmConfiguration::class);

return [
    'ctrl' => [
        'title' => 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_link',
        'label' => 'url',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'sortby' => 'sorting',
        'versioningWS' => true,
        'origUid' => 't3_origuid',
        'delete' => 'deleted',
        'hideAtCopy' => 1,
        'hideTable' => 1,
        'enablecolumns' => [
            'disabled' => 'hidden',
            'starttime' => 'starttime',
            'endtime' => 'endtime',
        ],
        'searchFields' => 'url,title',
        'iconfile' => 'EXT:publicrelations/Resources/Public/Icons/tx_publicrelations_domain_model_link.svg'
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
                url, title, type',
            'canNotColapse' => 1
        ],
        'paletteAccess' => TCA::getPaletteAccess(),
    ],
    'columns' => [

        'url' => [
            'exclude' => false,
            'label' => $ll . '.url',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputLink',
                'size' => 30,
                'eval' => 'required'
            ],
        ],
        'title' => [
            'exclude' => false,
            'label' => $ll . '.title',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim'
            ],
        ],
        'type' => [
            'exclude' => false,
            'label' => $ll . '.type',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectCheckBox',
                'foreign_table' => 'sys_category',
                'treeConfig' => [
                    'parentField' => 'parent',
                    'appearance' => [
                        'showHeader' => true,
                        'expandAll' => false,
                        'maxLevels' => 99
                    ],
                    'startingPoints' => $configuration->getLinkRootUid(),
                ],
                'MM' => 'sys_category_record_mm',
                'MM_match_fields' => [
                    'tablenames' => 'tx_publicrelations_domain_model_link',
                    'fieldname' => 'type',
                ],
                'MM_opposite_field' => 'items',
                'size' => 5,
                'minitems' => 0,
                'maxitems' => 1,
                'default' => 0,
            ]
        ],

        'client' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'campaign' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'news' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'event' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'mediagroup' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'slide' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'report' => [
            'config' => [
                'type' => 'passthrough',
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
                'foreign_table' => 'tx_publicrelations_domain_model_link',
                'foreign_table_where' => 'AND {#tx_publicrelations_domain_model_link}.{#pid}=###CURRENT_PID### AND {#tx_publicrelations_domain_model_link}.{#sys_language_uid} IN (-1,0)',
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
    ],
];
