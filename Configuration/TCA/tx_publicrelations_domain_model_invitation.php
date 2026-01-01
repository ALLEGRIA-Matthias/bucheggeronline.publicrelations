<?php

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use BucheggerOnline\Publicrelations\Domain\Model\Dto\EmConfiguration;
use BucheggerOnline\Publicrelations\Backend\TCA;

$ll = 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_invitation';
$llg = 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_general';
$configuration = GeneralUtility::makeInstance(EmConfiguration::class);

return [
    'ctrl' => [
        'title' => $ll,
        'label' => 'title',
        'label_alt' => 'type',
        'label_alt_force' => true,
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'versioningWS' => true,
        'origUid' => 't3_origuid',
        'delete' => 'deleted',
        'hideAtCopy' => 1,
        'hideTable' => 1,
        'searchFields' => 'title,location,date',
        'iconfile' => 'EXT:publicrelations/Resources/Public/Icons/tx_publicrelations_domain_model_invitation.svg',
        'type' => 'type',
    ],
    'types' => [
        '0' => [
            'showitem' => '
                --div--;LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_general.palettes.general,
                    --palette--;;paletteType,
                    contents,
                    --palette--;LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:palette.senderinfo;paletteSender,
                    variants,
                --div--;LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_general.palettes.layout,
                    --palette--;LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_general.palettes.layout;paletteVariables,
                    --palette--;LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_general.palettes.header;paletteHeader,
                    --palette--;;paletteAdditional
                '
        ],
        // Fall 3: type ist 'html' - NEUE Ansicht
        'html' => [
            'showitem' => '
                --div--;LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_general.palettes.general,
                    --palette--;;paletteType,
                    --palette--;LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:palette.sender;paletteSender,
                    variants,
                --div--;LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_general.palettes.layout,
                    --palette--;LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_general.palettes.layout;paletteVariables,
                    --palette--;;paletteAdditional
            '
        ],
    ],
    'palettes' => [
        'paletteType' => [
            'showitem' => '
                type, title, alt_template',
            'canNotColapse' => 1
        ],
        'paletteSender' => [
            'showitem' => 'from_name, reply_email, reply_name',
            'canNotColapse' => 1
        ],
        'paletteOptions' => [
            'showitem' => '
                blank, no_event_overview',
            'canNotColapse' => 1
        ],
        'paletteGeneral' => [
            'showitem' => '
                contents',
            'canNotColapse' => 1
        ],
        'paletteGeneralBlank' => [
            'showitem' => '
                title, subject, alt_template, --linebreak--,
                alt_sender, reply_name, reply_email, --linebreak--,
                contents, contents_personally, --linebreak--,
                invitation_title_overwrite, invitation_subtitle_overwrite, invitation_header_overwrite',
            'canNotColapse' => 1
        ],
        'paletteVariables' => [
            'showitem' => '
                feedback_date, no_salutation, no_signature',
            'canNotColapse' => 1
        ],
        'paletteAdditional' => [
            'showitem' => 'image, logo',
            'canNotColapse' => 1
        ],
        'paletteHeader' => [
            'showitem' => '
                no_header, header',
            'canNotColapse' => 1
        ],
        'paletteAccess' => TCA::getPaletteAccess(),
    ],
    'columns' => [
        'type' => [
            'exclude' => false,
            'label' => 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_invitation.type',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['HTML', 'html'],
                    ['Fluid', 'fluid'],
                ],
                'default' => 'fluid',
                'onChange' => 'reload'
            ],
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
        'subject' => [
            'exclude' => false,
            'label' => $ll . '.subject',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim'
            ],
        ],
        'from' => [
            'exclude' => false,
            'label' => $ll . '.from',
            'description' => $ll . '.from.description',
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
        'from_personally' => [
            'exclude' => false,
            'label' => $ll . '.from_personally',
            'description' => $ll . '.from_personally.description',
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
        'blank' => [
            'exclude' => false,
            'label' => $ll . '.blank',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'items' => [
                    [
                        'label' => '',
                        'value' => 1
                    ],
                ],
                'default' => 0,
            ],
        ],
        'no_event_overview' => [
            'exclude' => false,
            'label' => $ll . '.no_event_overview',
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
            ],
        ],
        'contents' => [
            'exclude' => false,
            'label' => $ll . '.contents',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_publicrelations_domain_model_content',
                'foreign_field' => 'invitation',
                'foreign_sortby' => 'sorting',
                'foreign_match_fields' => [
                    'role' => 'default',
                ],
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
        'contents_personally' => [
            'exclude' => false,
            'label' => $ll . '.contents_personally',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_publicrelations_domain_model_content',
                'foreign_field' => 'invitation',
                'foreign_sortby' => 'sorting',
                'foreign_match_fields' => [
                    'role' => 'personally',
                ],
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
        'image' => [
            'exclude' => false,
            'label' => $ll . '.image',
            'config' => [
                'type' => 'file',
                'maxitems' => 1,
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
        'header' => [
            'exclude' => false,
            'label' => $ll . '.header',
            'config' => [
                'type' => 'file',
                'maxitems' => 1,
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
        'no_header' => [
            'exclude' => false,
            'displayCond' => 'FIELD:alt_template:REQ:false',
            'label' => $ll . '.no_header',
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
                'default' => 0,
            ],
        ],
        'no_salutation' => [
            'exclude' => false,
            'displayCond' => [
                'AND' => [
                    'FIELD:type:=:fluid',
                    'FIELD:template_layout:REQ:false',
                ]
            ],
            'label' => $ll . '.no_salutation',
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
                'default' => 0,
            ],
        ],
        'no_signature' => [
            'exclude' => false,
            'displayCond' => [
                'AND' => [
                    'FIELD:type:=:fluid',
                    'FIELD:template_layout:REQ:false',
                ]
            ],
            'label' => $ll . '.no_signature',
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
                'default' => 0,
            ],
        ],
        'feedback_date' => [
            'exclude' => false,
            'label' => $ll . '.feedback_date',
            'description' => $ll . '.description.feedback_date',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputDateTime',
                'size' => 10,
                'eval' => 'date',
                'default' => null
            ],
        ],
        'logo' => [
            'exclude' => false,
            'displayCond' => [
                'AND' => [
                    'FIELD:type:=:fluid',
                    'FIELD:template_layout:REQ:false',
                ]
            ],
            'label' => $ll . '.logo',
            'config' => [
                'type' => 'file',
                'maxitems' => 1,
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
        'event' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'alt_sender' => [
            'exclude' => false,
            'label' => $ll . '.alt_sender',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim'
            ],
        ],
        'alt_template' => [
            'exclude' => false,
            'displayCond' => 'FIELD:type:=:fluid',
            'label' => $ll . '.alt_template',
            'config' => [
                'type' => 'input',
                'valuePicker' => [
                    'items' => $configuration->getEmailTemplates(),
                ],
                'size' => 30,
                'eval' => 'trim'
            ],
        ],
        'from_name' => [
            'exclude' => false,
            'label' => $ll . '.from_name',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim'
            ],
        ],
        'reply_name' => [
            'exclude' => false,
            'label' => $ll . '.reply_name',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim'
            ],
        ],
        'reply_email' => [
            'exclude' => false,
            'label' => $ll . '.reply_email',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim'
            ],
        ],
        'invitation_title_overwrite' => [
            'exclude' => false,
            'label' => $ll . '.invitation_title_overwrite',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim'
            ],
        ],
        'invitation_subtitle_overwrite' => [
            'exclude' => false,
            'label' => $ll . '.invitation_subtitle_overwrite',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim'
            ],
        ],
        'invitation_header_overwrite' => [
            'exclude' => false,
            'label' => $ll . '.invitation_header_overwrite',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim'
            ],
        ],
        'variants' => [
            'exclude' => false,
            'label' => 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_invitation.variants',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_publicrelations_domain_model_invitationvariant',
                'foreign_field' => 'invitation',
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
                    'enableCascadingDelete' => true,
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
                'foreign_table' => 'tx_publicrelations_domain_model_invitation',
                'foreign_table_where' => 'AND {#tx_publicrelations_domain_model_invitation}.{#pid}=###CURRENT_PID### AND {#tx_publicrelations_domain_model_invitation}.{#sys_language_uid} IN (-1,0)',
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
