<?php

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use BucheggerOnline\Publicrelations\Domain\Model\Dto\EmConfiguration;
use BucheggerOnline\Publicrelations\Backend\TCA;

$ll = 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_news';
$llg = 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_general';
$configuration = GeneralUtility::makeInstance(EmConfiguration::class);

return [
    'ctrl' => [
        'title' => $ll,
        'label' => 'title',
        'default_sortby' => 'date DESC',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'versioningWS' => true,
        'origUid' => 't3_origuid',
        'delete' => 'deleted',
        'hideAtCopy' => 1,
        'hideTable' => 0,
        'rootLevel' => 0,
        'thumbnail' => 'media',
        'enablecolumns' => [
            'disabled' => 'hidden',
            'starttime' => 'starttime',
            'endtime' => 'endtime',
        ],
        'searchFields' => 'title,text,slug,seo_title,seo_description',
        'iconfile' => 'EXT:publicrelations/Resources/Public/Icons/tx_publicrelations_domain_model_news.svg'
    ],
    'types' => [
        '1' => [
            'showitem' => '
                --div--;LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_general.tab.general,
                    --palette--;LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_general.palettes.general;paletteGeneral,
                    --palette--;LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_general.palettes.additional;paletteAdditional,
                --div--;LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_general.tab.internal,
                    contacts,
                    internal_notes,
                --div--;LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_general.tab.media,
                    media,
                --div--;LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_general.tab.seo,
                    --palette--;;paletteSlug,
                    --palette--;;paletteSEO,
                --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.access,
                    --palette--;;paletteAccess,
                '
        ],
    ],
    'palettes' => [
        'paletteGeneral' => [
            'showitem' => '
                client, campaigns, --linebreak--,
                title, date, --linebreak--,
                text, --linebreak--,
                content_elements',
            'canNotColapse' => 1
        ],
        'paletteAdditional' => [
            'showitem' => '
                retention_date, retention_info, --linebreak--,
                links',
            'canNotColapse' => 1
        ],
        'paletteSlug' => TCA::getPaletteSlug(),
        'paletteSEO' => TCA::getPaletteSEO(),
        'paletteAccess' => TCA::getPaletteAccess(),
    ],
    'columns' => [

        'date' => [
            'exclude' => false,
            'label' => $ll . '.date',
            'config' => [
                'dbType' => 'date',
                'type' => 'input',
                'renderType' => 'inputDateTime',
                'size' => 7,
                'eval' => 'date,required',
                'default' => null,
            ],
        ],
        'retention_date' => [
            'exclude' => false,
            'label' => $ll . '.retention_date',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputDateTime',
                'size' => 7,
                'eval' => 'datetime',
                'default' => 0
            ],
        ],
        'retention_info' => [
            'exclude' => false,
            'label' => $ll . '.retention_info',
            'config' => [
                'type' => 'text',
                'cols' => 40,
                'rows' => 5,
                'eval' => 'trim'
            ]
        ],
        'title' => [
            'exclude' => false,
            'label' => $ll . '.title',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim,required'
            ],
        ],
        'text' => [
            'exclude' => false,
            'label' => $ll . '.text',
            'config' => [
                'type' => 'text',
                'enableRichtext' => true,
                'richtextConfiguration' => 'default',
                'fieldControl' => [
                    'fullScreenRichtext' => [
                        'disabled' => false,
                    ],
                ],
                'cols' => 40,
                'rows' => 15,
                'eval' => 'trim,required',
            ],

        ],
        'slug' => [
            'exclude' => true,
            'label' => $llg . '.field.slug',
            'config' => [
                'type' => 'slug',
                'size' => 50,
                'generatorOptions' => [
                    'fields' => ['client', 'title'],
                    'fieldSeparator' => '/',
                    'replacements' => [
                        '/' => '-'
                    ],
                ],
                'fallbackCharacter' => '-',
                'eval' => 'uniqueInPid',
                'default' => NULL
            ]
        ],
        'slug_locked' => [
            'exclude' => true,
            'label' => $llg . '.field.slug_locked',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'items' => [
                    [
                        'label' => '',
                        'value' => 1,
                    ]
                ],
                'default' => 0,
            ]
        ],
        'seo_title' => [
            'exclude' => false,
            'label' => $llg . '.field.seo_title',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim'
            ],
        ],
        'seo_description' => [
            'exclude' => false,
            'label' => $llg . '.field.seo_description',
            'config' => [
                'type' => 'text',
                'cols' => 40,
                'rows' => 5,
                'eval' => 'trim'
            ]
        ],
        'client' => [
            'exclude' => false,
            'label' => $llg . '.field.client',
            'config' => [
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'tx_publicrelations_domain_model_client',
                'size' => 1,
                'minitems' => 1,
                'maxitems' => 1,
                'hideSuggest' => 0,
                'multiple' => 0,
                'default' => 0,
                'fieldControl' => [
                    'addRecord' => [
                        'disabled' => false,
                    ],
                    'editPopup' => [
                        'disabled' => false,
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
        'content_elements' => [
            'exclude' => true,
            'label' => $ll . '.content_elements',
            'config' => [
                'type' => 'inline',
                'allowed' => 'tt_content',
                'foreign_table' => 'tt_content',
                'foreign_sortby' => 'sorting',
                'foreign_field' => 'publicrelations_news',
                'minitems' => 0,
                'maxitems' => 9999,
                'appearance' => [
                    'collapseAll' => 1,
                    'levelLinksPosition' => 'bottom',
                    'useSortable' => 1,
                    'showSynchronizationLink' => 1,
                    'showPossibleLocalizationRecords' => 1,
                    'showAllLocalizationLink' => 1
                ],
            ],
        ],
        'media' => [
            'exclude' => false,
            'label' => $llg . '.field.media',
            'config' => [
                'type' => 'file',
                'minitems' => 1,
                'maxitems' => 3,
                'appearance' => [
                    'collapseAll' => true,
                    'createNewRelationLinkTitle' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:images.addFileReference',
                    'showPossibleLocalizationRecords' => true,
                    'showAllLocalizationLink' => true,
                    'showSynchronizationLink' => true
                ],
                'behaviour' => [
                    'allowLanguageSynchronization' => true,
                ],
                'allowed' => $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'],
                'overrideChildTca' => [
                    'types' => [
                        \TYPO3\CMS\Core\Resource\File::FILETYPE_IMAGE => [
                            'showitem' => 'crop,--palette--;;filePalette',
                        ],
                        \TYPO3\CMS\Core\Resource\File::FILETYPE_VIDEO => [
                            'showitem' => '
                                --palette--;LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,
                                --palette--;;filePalette',
                        ],
                    ],
                    'columns' => [
                        'crop' => [
                            'config' => [
                                'type' => 'imageManipulation',
                                'cropVariants' => [
                                    'default' => [
                                        'title' => 'Default',
                                        'coverAreas' => [],
                                        'cropArea' => [
                                            'x' => '0.0',
                                            'y' => '0.0',
                                            'width' => '1.0',
                                            'height' => '1.0'
                                        ],
                                        'allowedAspectRatios' => [
                                            'NaN' => [
                                                'title' => 'Freie Größe',
                                                'value' => 0.0
                                            ],
                                        ],
                                        'selectedRatio' => 'NaN',
                                    ],
                                    'thumb' => [
                                        'title' => 'Thumbnail',
                                        'coverAreas' => [],
                                        'cropArea' => [
                                            'x' => '0.0',
                                            'y' => '0.0',
                                            'width' => '1.0',
                                            'height' => '1.0'
                                        ],
                                        'allowedAspectRatios' => [
                                            '1:1' => [
                                                'title' => 'Quadratisch',
                                                'value' => 1.0
                                            ],
                                        ],
                                        'selectedRatio' => '1:1',
                                    ],
                                ],
                                'fieldWizard' => [
                                    'localizationStateSelector' => [
                                        'disabled' => true,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
        'links' => [
            'exclude' => false,
            'label' => $llg . '.field.links',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_publicrelations_domain_model_link',
                'foreign_field' => 'news',
                'foreign_sortby' => 'sorting',
                'maxitems' => 9999,
                'appearance' => [
                    'collapseAll' => 1,
                    'levelLinksPosition' => 'top',
                    'showSynchronizationLink' => 1,
                    'showPossibleLocalizationRecords' => 1,
                    'useSortable' => 1,
                    'showAllLocalizationLink' => 1
                ],
            ],

        ],
        'contacts' => [
            'exclude' => false,
            'label' => $llg . '.field.contacts',
            'description' => $ll . '.contacts.description',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_publicrelations_domain_model_contact',
                'foreign_field' => 'news',
                'foreign_sortby' => 'sorting',
                'maxitems' => 9999,
                'appearance' => [
                    'collapseAll' => 1,
                    'levelLinksPosition' => 'bottom',
                    'showSynchronizationLink' => 1,
                    'showPossibleLocalizationRecords' => 1,
                    'useSortable' => 1,
                    'showAllLocalizationLink' => 1
                ],
            ],
        ],
        'internal_notes' => [
            'exclude' => false,
            'label' => $llg . '.field.internal_notes',
            'config' => [
                'type' => 'text',
                'enableRichtext' => true,
                'richtextConfiguration' => 'default',
                'fieldControl' => [
                    'fullScreenRichtext' => [
                        'disabled' => false,
                    ],
                ],
                'cols' => 40,
                'rows' => 15,
                'eval' => 'trim',
            ],
        ],
        'campaigns' => [
            'exclude' => false,
            'label' => $llg . '.field.campaigns',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'foreign_table' => 'tx_publicrelations_domain_model_campaign',
                'foreign_table_where' => 'AND (tx_publicrelations_domain_model_campaign.client = ###REC_FIELD_client###)',
                'MM' => 'tx_publicrelations_news_campaign_mm',
                'size' => 10,
                'autoSizeMax' => 30,
                'maxitems' => 9999,
                'multiple' => 0,
                'fieldControl' => [
                    'editPopup' => [
                        'disabled' => true,
                    ],
                    'addRecord' => [
                        'disabled' => true,
                    ],
                    'listModule' => [
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
                'foreign_table' => 'tx_publicrelations_domain_model_news',
                'foreign_table_where' => 'AND {#tx_publicrelations_domain_model_news}.{#pid}=###CURRENT_PID### AND {#tx_publicrelations_domain_model_news}.{#sys_language_uid} IN (-1,0)',
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
                'default' => 1
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
