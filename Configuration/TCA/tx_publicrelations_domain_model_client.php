<?php

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use BucheggerOnline\Publicrelations\Domain\Model\Dto\EmConfiguration;
use BucheggerOnline\Publicrelations\Backend\TCA;

$ll = 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_client';
$llg = 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_general';
$configuration = GeneralUtility::makeInstance(EmConfiguration::class);

return [
    'ctrl' => [
        'title' => $ll,
        'label' => 'name',
        'label_alt' => 'short_name',
        'label_alt_force' => 1,
        'default_sortby' => 'name ASC',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'versioningWS' => true,
        'origUid' => 't3_origuid',
        'delete' => 'deleted',
        'hideAtCopy' => 1,
        'hideTable' => 0,
        'rootLevel' => 0,
        'thumbnail' => 'logo',
        'enablecolumns' => [
            'disabled' => 'hidden',
            'starttime' => 'starttime',
            'endtime' => 'endtime',
        ],
        'searchFields' => 'name,short_name,also_known_as,shortinfo,description,phone,email,slug, slug_locked,seo_title,seo_description',
        'iconfile' => 'EXT:publicrelations/Resources/Public/Icons/tx_publicrelations_domain_model_client.svg'
    ],
    'types' => [
        '1' => [
            'showitem' => '
                --div--;LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_general.tab.general,
                    --palette--;LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_general.palettes.general;paletteGeneral,
                    --palette--;LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_general.palettes.additional;paletteAdditional,
                --div--;LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_general.tab.internal,
                    --palette--;;paletteInternal,
                --div--;LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_general.tab.campaigns,
                    campaigns,
                --div--;LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_general.tab.news,
                    news,
                --div--;LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_general.tab.media,
                    mediagroups,
                --div--;LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_general.tab.seo,
                    --palette--;;paletteSlug,
                    --palette--;;paletteSEO,
                --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.access,
                    access_rights,
                    --palette--;;paletteAccess,
                '
        ],
    ],
    'palettes' => [
        'paletteGeneral' => [
            'showitem' => '
                name, short_name, --linebreak--,
                also_known_as, types, --linebreak--,
                logo, location, --linebreak--,
                description',
            'canNotColapse' => 1
        ],
        'paletteAdditional' => [
            'showitem' => '
                shortinfo, sort, top, --linebreak--,
                since, archive, until, --linebreak--,
                phone, email, --linebreak--,
                links, --linebreak--,
                covers',
            'canNotColapse' => 1
        ],
        'paletteInternal' => [
            'showitem' => '
                contacts, --linebreak--,
                internal_notes, activities',
            'canNotColapse' => 1
        ],
        'paletteSlug' => TCA::getPaletteSlug(),
        'paletteSEO' => TCA::getPaletteSEO(),
        'paletteAccess' => TCA::getPaletteAccess(),
    ],
    'columns' => [

        'name' => [
            'exclude' => false,
            'label' => $ll . '.name',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim,required'
            ],
        ],
        'short_name' => [
            'exclude' => false,
            'label' => $ll . '.short_name',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim'
            ],
        ],
        'also_known_as' => [
            'exclude' => false,
            'label' => $ll . '.also_known_as',
            'config' => [
                'type' => 'text',
                'cols' => 40,
                'rows' => 5,
                'eval' => 'trim'
            ]
        ],
        'shortinfo' => [
            'exclude' => false,
            'label' => $ll . '.shortinfo',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim'
            ],
        ],
        'top' => [
            'exclude' => false,
            'label' => $ll . '.top',
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
        'sort' => [
            'exclude' => false,
            'label' => $ll . '.sort',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [$ll . '.sort.0', 0],
                    [$ll . '.sort.1', 1],
                    [$ll . '.sort.2', 2]
                ],
                'default' => 0,
            ]
        ],
        'archive' => [
            'exclude' => false,
            'label' => $ll . '.archive',
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
        'description' => [
            'exclude' => false,
            'label' => $ll . '.description',
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
                'rows' => 5,
                'eval' => 'trim,required',
            ],

        ],
        'logo' => [
            'exclude' => false,
            'label' => $llg . '.field.logo',
            'config' => [
                'type' => 'file',
                'maxitems' => 1,
                'minitems' => 1,
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
                    ],
                    'columns' => [
                        'crop' => [
                            'config' => [
                                'type' => 'imageManipulation',
                                'cropVariants' => [
                                    'default' => [
                                        'title' => $llg . '.field.logo',
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
                                                'value' => 1
                                            ],
                                        ],
                                        'selectedRatio' => '1:1',
                                    ],
                                    'email' => [
                                        'title' => $llg . '.field.logo_email',
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
                                            '1:1' => [
                                                'title' => 'Quadratisch',
                                                'value' => 1
                                            ],
                                            '5:7' => [
                                                'title' => 'DIN-A Hochformat',
                                                'value' => 5 / 7
                                            ],
                                            '2:3' => [
                                                'title' => 'Foto Hochformat',
                                                'value' => 2 / 3
                                            ],
                                            '3:2' => [
                                                'title' => 'Foto Querformat',
                                                'value' => 3 / 2
                                            ],
                                        ],
                                        'selectedRatio' => 'NaN',
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
        'covers' => [
            'exclude' => false,
            'label' => $llg . '.field.covers',
            'config' => [
                'type' => 'file',
                'maxitems' => 10,
                'minitems' => 0,
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
                                            '7:2' => [
                                                'title' => 'Slider-Image',
                                                'value' => 7 / 2
                                            ],
                                        ],
                                        'selectedRatio' => '7:2',
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
        'since' => [
            'exclude' => false,
            'label' => $ll . '.since',
            'config' => [
                'dbType' => 'date',
                'type' => 'input',
                'renderType' => 'inputDateTime',
                'size' => 7,
                'eval' => 'date',
                'default' => null,
            ],
        ],
        'until' => [
            'exclude' => false,
            'label' => $ll . '.until',
            'config' => [
                'dbType' => 'date',
                'type' => 'input',
                'renderType' => 'inputDateTime',
                'size' => 7,
                'eval' => 'date',
                'default' => null,
            ],
        ],
        'phone' => [
            'exclude' => false,
            'label' => $ll . '.phone',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim'
            ],
        ],
        'email' => [
            'exclude' => false,
            'label' => $ll . '.email',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim,email'
            ],
        ],
        'slug' => [
            'exclude' => true,
            'label' => $llg . '.field.slug',
            'config' => [
                'type' => 'slug',
                'size' => 50,
                'generatorOptions' => [
                    'fields' => ['name'],
                    'fieldSeparator' => '-',
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
        'location' => [
            'exclude' => false,
            'label' => $llg . '.field.location',
            'config' => [
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'tx_publicrelations_domain_model_location',
                'size' => 1,
                'minitems' => 0,
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
        'links' => [
            'exclude' => false,
            'label' => $llg . '.field.links',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_publicrelations_domain_model_link',
                'foreign_field' => 'client',
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
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_publicrelations_domain_model_contact',
                'foreign_field' => 'client',
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
                'type' => 'inline',
                'foreign_table' => 'tx_publicrelations_domain_model_campaign',
                'foreign_field' => 'client',
                'maxitems' => 9999,
                'appearance' => [
                    'collapseAll' => 1,
                    'useSortable' => 1,
                    'showSynchronizationLink' => 1,
                    'showPossibleLocalizationRecords' => 1,
                    'showAllLocalizationLink' => 1,
                    'enabledControls' => [
                        'new' => false
                    ]
                ],
            ],
        ],
        'news' => [
            'exclude' => false,
            'label' => $llg . '.field.news',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_publicrelations_domain_model_news',
                'foreign_field' => 'client',
                'maxitems' => 9999,
                'appearance' => [
                    'collapseAll' => 1,
                    'showSynchronizationLink' => 1,
                    'showPossibleLocalizationRecords' => 1,
                    'showAllLocalizationLink' => 1,
                    'enabledControls' => [
                        'new' => false,
                    ],
                ],
            ],
        ],
        'mediagroups' => [
            'exclude' => false,
            'label' => $llg . '.field.mediagroups',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_publicrelations_domain_model_mediagroup',
                'foreign_field' => 'client',
                'foreign_sortby' => 'sorting',
                'maxitems' => 9999,
                'appearance' => [
                    'collapseAll' => 1,
                    'levelLinksPosition' => 'both',
                    'showSynchronizationLink' => 1,
                    'showPossibleLocalizationRecords' => 1,
                    'useSortable' => 1,
                    'showAllLocalizationLink' => 1
                ],
            ],

        ],
        'events' => [
            'exclude' => false,
            'label' => $llg . '.field.events',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_publicrelations_domain_model_event',
                'foreign_field' => 'client',
                'foreign_default_sortby' => 'date',
                'maxitems' => 9999,
                'appearance' => [
                    'collapseAll' => 1,
                    'showSynchronizationLink' => 1,
                    'showPossibleLocalizationRecords' => 1,
                    'showAllLocalizationLink' => 1,
                    'enabledControls' => [
                        'new' => false
                    ]
                ],
            ],

        ],
        'types' => [
            'exclude' => false,
            'label' => $ll . '.types',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'foreign_table' => 'sys_category',
                'foreign_table_where' => ' AND (sys_category.parent = ' . $configuration->getClientRootUid() . ') ORDER BY sys_category.title',
                'MM' => 'sys_category_record_mm',
                'MM_match_fields' => [
                    'tablenames' => 'tx_publicrelations_domain_model_client',
                    'fieldname' => 'types',
                ],
                'MM_opposite_field' => 'items',
                'size' => 5,
                'autoSizeMax' => 5,
                'minitems' => 1,
                'maxitems' => 99,
                'default' => 0,
            ]
        ],
        'activities' => [
            'exclude' => false,
            'label' => $ll . '.activities',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'foreign_table' => 'sys_category',
                'foreign_table_where' => ' AND (sys_category.parent = ' . $configuration->getActivitiesRootUid() . ') ORDER BY sys_category.title',
                'MM' => 'sys_category_record_mm',
                'MM_match_fields' => [
                    'tablenames' => 'tx_publicrelations_domain_model_client',
                    'fieldname' => 'activities',
                ],
                'MM_opposite_field' => 'items',
                'size' => 5,
                'autoSizeMax' => 5,
                'minitems' => 1,
                'maxitems' => 99,
                'default' => 0,
            ]
        ],
        'access_rights' => [
            'exclude' => false,
            'label' => 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_client.access_rights',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_publicrelations_domain_model_accessclient',
                'foreign_field' => 'client',
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
                'foreign_table' => 'tx_publicrelations_domain_model_client',
                'foreign_table_where' => 'AND {#tx_publicrelations_domain_model_client}.{#pid}=###CURRENT_PID### AND {#tx_publicrelations_domain_model_client}.{#sys_language_uid} IN (-1,0)',
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
