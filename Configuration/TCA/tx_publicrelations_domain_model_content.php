<?php

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use BucheggerOnline\Publicrelations\Domain\Model\Dto\EmConfiguration;
use BucheggerOnline\Publicrelations\Backend\TCA;

$ll = 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_content';
$llg = 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_general';
$configuration = GeneralUtility::makeInstance(EmConfiguration::class);

return [
    'ctrl' => [
        'title' => $ll,
        'label' => 'type',
        'label_alt' => 'event',
        'label_alt_force' => true,
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'versioningWS' => true,
        'origUid' => 't3_origuid',
        'delete' => 'deleted',
        'sortby' => 'sorting',
        'hideAtCopy' => 1,
        'hideTable' => 1,
        'type' => 'type',
        'enablecolumns' => [
            'disabled' => 'hidden',
            'starttime' => 'starttime',
            'endtime' => 'endtime',
        ],
        'searchFields' => '',
        'iconfile' => 'EXT:publicrelations/Resources/Public/Icons/tx_publicrelations_domain_model_content.svg'
    ],
    'types' => [
        '0' => [
            'showitem' => 'type, content,--div--;' . $llg . '.tab.layout,--palette--;;paletteLayout,--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.access,--palette--;;paletteAccess'
        ],
        '100' => [
            'showitem' => 'type, content,--div--;' . $llg . '.tab.layout,--palette--;;paletteLayout,--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.access,--palette--;;paletteAccess'
        ],
        '1' => [
            'showitem' => 'type, content,--div--;' . $llg . '.tab.layout,--palette--;;paletteLayout, --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.access,--palette--;;paletteAccess',
            'columnsOverrides' => [
                'content' => [
                    'config' => [
                        'enableRichtext' => false,
                        'renderType' => 't3editor',
                    ],
                ],
            ],
        ],
        '2' => ['showitem' => 'type, news, news_media,--div--;' . $llg . '.tab.layout,--palette--;;paletteLayout, --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.access,--palette--;;paletteAccess'],
        '3' => ['showitem' => 'type, event, event_title, event_link,--div--;' . $llg . '.tab.layout,--palette--;;paletteLayout, --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.access,--palette--;;paletteAccess'],
        '4' => ['showitem' => 'type, media,--div--;' . $llg . '.tab.layout,--palette--;;paletteLayout, --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.access,--palette--;;paletteAccess'],
        '5' => ['showitem' => 'type, listtype,--div--;' . $llg . '.tab.layout,--palette--;;paletteLayout, --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.access,--palette--;;paletteAccess'],
        '6' => ['showitem' => 'type, event,--div--;' . $llg . '.tab.layout,--palette--;;paletteLayout, --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.access,--palette--;;paletteAccess'],
        '7' => ['showitem' => 'type, --palette--;;paletteImage,--div--;' . $llg . '.tab.layout,--palette--;;paletteLayout, --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.access,--palette--;;paletteAccess'],
        '8' => ['showitem' => 'type, event,--div--;' . $llg . '.tab.layout,--palette--;;paletteLayout, --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.access,--palette--;;paletteAccess'],
        '9' => ['showitem' => 'type, event, --palette--;;paletteEventOverwrites,--div--;' . $llg . '.tab.layout,--palette--;;paletteLayout, --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.access,--palette--;;paletteAccess'],
        '10' => ['showitem' => 'type,--div--;' . $llg . '.tab.layout,--palette--;;paletteLayout, --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.access,--palette--;;paletteAccess'],
        '11' => [
            'showitem' => 'type, content,--div--;' . $llg . '.tab.layout,--palette--;;paletteLayout, --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.access,--palette--;;paletteAccess',
            'columnsOverrides' => [
                'content' => [
                    'label' => 'align',
                    'config' => [
                        'type' => 'input',
                        'valuePicker' => [
                            'items' => [
                                ['Linksbündig', 'left'],
                                ['Rechtsbündig', 'right'],
                                ['Mittig', 'center']
                            ]
                        ],
                        'size' => 30,
                        'eval' => 'trim'
                    ],
                ],
            ],
        ],
        '12' => [
            'showitem' => 'type, content,--div--;' . $llg . '.tab.layout,--palette--;;paletteLayout, --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.access,--palette--;;paletteAccess',
            'columnsOverrides' => [
                'content' => [
                    'config' => [
                        'enableRichtext' => false,
                        'renderType' => 't3editor',
                    ],
                ],
            ],
        ],
    ],
    'palettes' => [
        'paletteGeneral' => [
            'showitem' => '
                type, subject',
            'canNotColapse' => 1
        ],
        'paletteLayout' => [
            'showitem' => '
                padding, --linebreak--,
                color, bgcolor',
            'canNotColapse' => 1
        ],
        'paletteEventOverwrites' => [
            'showitem' => '
                event_title, --linebreak--,
                event_date, event_location, --linebreak--,
                event_description',
            'canNotColapse' => 1
        ],
        'paletteImage' => [
            'showitem' => 'image, image_full_width',
            'canNotColapse' => 1
        ],
        'paletteAccess' => TCA::getPaletteAccess(),
    ],
    'columns' => [

        'type' => [
            'exclude' => false,
            'label' => $ll . '.type',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [$ll . '.type.0', 100],
                    [$ll . '.type.1', 1],
                    [$ll . '.type.2', 2],
                    [$ll . '.type.3', 3],
                    [$ll . '.type.6', 6],
                    [$ll . '.type.7', 7],
                    [$ll . '.type.4', 4],
                    [$ll . '.type.5', 5],
                    [$ll . '.type.8', 8],
                    [$ll . '.type.9', 9],
                    [$ll . '.type.10', 10],
                    [$ll . '.type.11', 11],
                    ['HTML mit Fluid', 12],
                    [$ll . '.type.0', 0]
                ],
                'size' => 1,
                'maxitems' => 1,
                'default' => 100,
            ],
        ],
        'listtype' => [
            'exclude' => false,
            'label' => $ll . '.listtype',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [$ll . '.listtype.0', 0],
                    [$ll . '.listtype.1', 1],
                    [$ll . '.listtype.2', 2],
                    [$ll . '.listtype.3', 3],
                    [$ll . '.listtype.4', 4],
                    [$ll . '.listtype.5', 5],
                ],
                'size' => 1,
                'maxitems' => 1,
                'eval' => '',
                'default' => 0,
            ],
        ],
        'content' => [
            'exclude' => false,
            'label' => $ll . '.content',
            'config' => [
                'type' => 'text',
                'enableRichtext' => true,
                'richtextConfiguration' => 'email',
                'fieldControl' => [
                    'fullScreenRichtext' => [
                        'disabled' => false,
                    ],
                ],
                'eval' => 'trim',
            ],
        ],
        'padding' => [
            'exclude' => false,
            'label' => $ll . '.padding',
            'config' => [
                'type' => 'input',
                'valuePicker' => [
                    'items' => [
                        ['Standard auf allen Seiten', '40px'],
                        ['Keine Ränder', '0'],
                        ['Oben & Unten 0', '0px 40px'],
                        ['Links & Rechts 0', '40px 0px'],
                        ['Oben 0', '0 40px 40px 40px'],
                        ['Unten 0', '40px 40px 0 40px'],
                        ['Rechts 0', '40px 0 40px 40px'],
                        ['Links 0', '40px 40px 40px 0'],
                    ]
                ],
                'size' => 30,
                'eval' => 'trim'
            ],
        ],
        'color' => [
            'exclude' => false,
            'label' => $ll . '.color',
            'config' => [
                'type' => 'input',
                'renderType' => 'colorpicker',
                'valuePicker' => [
                    'items' => [
                        ['Dunkel (#030305)', '#030305'],
                        ['Weiss (#FFFFFF)', '#FFFFFF'],
                        ['Gold (#EFB72E)', '#EFB72E'],
                        ['Blau (#C2E2F1)', '#C2E2F1'],
                    ]
                ],
                'size' => 10,
                'eval' => 'trim'
            ],
        ],
        'bgcolor' => [
            'exclude' => false,
            'label' => $ll . '.bgcolor',
            'config' => [
                'type' => 'input',
                'renderType' => 'colorpicker',
                'valuePicker' => [
                    'items' => [
                        ['Dunkel (#030305)', '#030305'],
                        ['Weiss (#FFFFFF)', '#FFFFFF'],
                        ['Gold (#EFB72E)', '#EFB72E'],
                        ['Blau (#C2E2F1)', '#C2E2F1'],
                    ]
                ],
                'size' => 10,
                'eval' => 'trim'
            ],
        ],
        'event_title' => [
            'exclude' => false,
            'label' => $ll . '.event_title',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim'
            ],
        ],
        'event_date' => [
            'exclude' => false,
            'label' => $ll . '.event_date',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim'
            ],
        ],
        'event_location' => [
            'exclude' => false,
            'label' => $ll . '.event_location',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim'
            ],
        ],
        'event_description' => [
            'exclude' => false,
            'label' => $ll . '.event_description',
            'config' => [
                'type' => 'text',
                'enableRichtext' => true,
                'richtextConfiguration' => 'default',
                'fieldControl' => [
                    'fullScreenRichtext' => [
                        'disabled' => false,
                    ],
                ],
                'eval' => 'trim',
            ],
        ],
        'content_element' => [
            'exclude' => true,
            'label' => $ll . '.content_element',
            'config' => [
                'type' => 'inline',
                'allowed' => 'tt_content',
                'foreign_table' => 'tt_content',
                'foreign_field' => 'publicrelations_content',
                'minitems' => 1,
                'maxitems' => 1,
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
        'image' => [
            'exclude' => false,
            'label' => $ll . '.image',
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
        'image_full_width' => [
            'exclude' => false,
            'label' => $ll . '.image_full_width',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'items' => [
                    [
                        'label' => '',
                        'value' => 1,
                    ]
                ],
                'default' => 0
            ],
        ],
        'media' => [
            'exclude' => false,
            'label' => $llg . '.field.media',
            'config' => [
                'type' => 'file',
                'maxitems' => 10,
                'appearance' => [
                    'createNewRelationLinkTitle' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:media.addFileReference'
                ],
                'allowed' => ['jpg', 'jpeg', 'png', 'svg', 'mp3', 'mp4', 'pdf', 'txt'],
                'overrideChildTca' => [
                    'types' => [
                        '0' => [
                            'showitem' => '--palette--;LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,--palette--;;filePalette',
                        ],
                        \TYPO3\CMS\Core\Resource\File::FILETYPE_TEXT => [
                            'showitem' => '--palette--;LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,--palette--;;filePalette',
                        ],
                        \TYPO3\CMS\Core\Resource\File::FILETYPE_IMAGE => [
                            'showitem' => '--palette--;LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,--palette--;;filePalette',
                        ],
                        \TYPO3\CMS\Core\Resource\File::FILETYPE_AUDIO => [
                            'showitem' => '--palette--;LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,--palette--;;filePalette',
                        ],
                        \TYPO3\CMS\Core\Resource\File::FILETYPE_VIDEO => [
                            'showitem' => '--palette--;LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,--palette--;;filePalette',
                        ],
                        \TYPO3\CMS\Core\Resource\File::FILETYPE_APPLICATION => [
                            'showitem' => '--palette--;LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,--palette--;;filePalette',
                        ],
                    ],
                    'columns' => [
                        'crop' => [
                            'config' => [
                                'type' => 'imageManipulation',
                                'cropVariants' => [
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
        'news' => [
            'exclude' => false,
            'label' => $llg . '.field.news',
            'config' => [
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'tx_publicrelations_domain_model_news',
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
        'news_media' => [
            'exclude' => false,
            'label' => $ll . '.news_media',
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
        'event' => [
            'exclude' => false,
            'label' => $llg . '.field.event',
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
        'event_link' => [
            'exclude' => false,
            'label' => $ll . '.event_link',
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

        'invitation' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'role' => [
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
                'foreign_table' => 'tx_publicrelations_domain_model_slide',
                'foreign_table_where' => 'AND {#tx_publicrelations_domain_model_slide}.{#pid}=###CURRENT_PID### AND {#tx_publicrelations_domain_model_slide}.{#sys_language_uid} IN (-1,0)',
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
