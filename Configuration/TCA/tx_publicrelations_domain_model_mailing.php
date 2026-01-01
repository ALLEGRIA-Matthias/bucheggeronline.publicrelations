<?php

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use BucheggerOnline\Publicrelations\Domain\Model\Dto\EmConfiguration;
use BucheggerOnline\Publicrelations\Backend\TCA;

$ll = 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_mailing';
$llg = 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_general';
$configuration = GeneralUtility::makeInstance(EmConfiguration::class);

return [
    'ctrl' => [
        'title' => $ll,
        'label' => 'type',
        'label_alt' => 'title,subject,sent',
        'label_alt_force' => 1,
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'versioningWS' => true,
        'origUid' => 't3_origuid',
        'delete' => 'deleted',
        'hideTable' => 0,
        'rootLevel' => 0,
        'searchFields' => '',
        'iconfile' => 'EXT:publicrelations/Resources/Public/Icons/tx_publicrelations_domain_model_mailing.svg',
    ],
    'types' => [
        '0' => [
            'showitem' => '
              --div--;LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_general.tab.general,
                  --palette--;LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_general.palettes.general;paletteGeneral,
                  contents,
                  attachment,
              --div--;LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_general.tab.additional,
              --palette--;LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_general.palettes.header;paletteHeader,
              --palette--;LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_general.palettes.additional;paletteAdditional,
              --div--;LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_general.tab.logs,
                  logs,
              --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.access,
                  --palette--;;paletteAccess,
              '
        ],
    ],
    'palettes' => [
        'paletteGeneral' => [
            'showitem' => '
                client, preview, --linebreak--,
                subject, type, title, --linebreak--,
                alt_sender, reply_name, reply_email',
            'canNotColapse' => 1
        ],
        'paletteHeader' => [
            'showitem' => '
                header, no_header,--linebreak--,
                no_salutation, no_signature',
            'canNotColapse' => 1
        ],
        'paletteAdditional' => [
            'showitem' => '
               personally, alt_template, --linebreak--,
                approved, --linebreak--,
                planed, started, sent',
            'canNotColapse' => 1
        ],
        'paletteAccess' => TCA::getPaletteAccess(),
    ],
    'columns' => [
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
        'type' => [
            'exclude' => false,
            'label' => $ll . '.type',
            'config' => [
                'type' => 'input',
                'valuePicker' => [
                    'items' => $configuration->getMailingtypes(),
                ],
                'default' => 'Presseinformation',
                'size' => 30,
                'eval' => 'trim'
            ],
        ],
        'title' => [
            'exclude' => false,
            'label' => $ll . '.title',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'max' => 35,
                'eval' => 'trim'
            ],
        ],
        'subject' => [
            'exclude' => false,
            'label' => $ll . '.subject',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim,required'
            ],
        ],
        'preview' => [
            'exclude' => false,
            'label' => $ll . '.preview',
            'config' => [
                'type' => 'text',
                'cols' => 50,
                'rows' => 3
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
        'blank' => [
            'exclude' => false,
            'label' => $ll . '.blank',
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
        'no_salutation' => [
            'exclude' => false,
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
        'no_logo' => [
            'exclude' => false,
            'label' => $ll . '.no_logo',
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
        'no_header' => [
            'exclude' => false,
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
                                            'NaN' => ['title' => 'Freie Größe', 'value' => 0.0],
                                            '1:1' => ['title' => 'Quadratisch', 'value' => 1],
                                            '5:7' => ['title' => 'DIN-A Hochformat', 'value' => 5 / 7],
                                            '2:3' => ['title' => 'Foto Hochformat', 'value' => 2 / 3],
                                            '3:2' => ['title' => 'Foto Querformat', 'value' => 3 / 2],
                                        ],
                                        'selectedRatio' => 'NaN',
                                    ],
                                ],
                                'fieldWizard' => [
                                    'localizationStateSelector' => ['disabled' => true],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
        'personally' => [
            'exclude' => false,
            'label' => $ll . '.personally',
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
                'foreign_field' => 'mailing',
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
        'attachment' => [
            'exclude' => true,
            'label' => $ll . '.attachment',
            'config' => [
                'type' => 'file',
                'maxitems' => 1,
                'appearance' => [
                    'createNewRelationLinkTitle' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:media.addFileReference',
                ],
                'allowed' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'png', 'jpg', 'mp3', 'mp4'],
                'overrideChildTca' => [
                    'types' => [
                        '0' => ['showitem' => '--palette--;LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,--palette--;;filePalette'],
                        \TYPO3\CMS\Core\Resource\File::FILETYPE_TEXT => ['showitem' => '--palette--;LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,--palette--;;filePalette'],
                        \TYPO3\CMS\Core\Resource\File::FILETYPE_IMAGE => ['showitem' => '--palette--;LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,--palette--;;filePalette'],
                        \TYPO3\CMS\Core\Resource\File::FILETYPE_AUDIO => ['showitem' => '--palette--;LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,--palette--;;filePalette'],
                        \TYPO3\CMS\Core\Resource\File::FILETYPE_VIDEO => ['showitem' => '--palette--;LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,--palette--;;filePalette'],
                        \TYPO3\CMS\Core\Resource\File::FILETYPE_APPLICATION => ['showitem' => '--palette--;LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,--palette--;;filePalette'],
                    ],
                ],
            ],
        ],
        'status' => [
            'exclude' => true,
            'label' => $ll . '.status',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [$ll . '.status.0', 0],
                    [$ll . '.status.1', 1],
                    [$ll . '.status.2', 2],
                    [$ll . '.status.3', 3],
                    [$ll . '.status.-1', -1],
                ],
                'size' => 1,
                'maxitems' => 1,
                'minitems' => 1,
                'eval' => 'required',
                'default' => 0,
            ],
        ],
        'test' => [
            'exclude' => true,
            'label' => $ll . '.test',
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
        'planed' => [
            'exclude' => false,
            'label' => $ll . '.planed',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputDateTime',
                'size' => 10,
                'eval' => 'datetime',
                'default' => 0
            ],
        ],
        'started' => [
            'exclude' => false,
            'label' => $ll . '.started',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputDateTime',
                'size' => 10,
                'eval' => 'datetime',
                'default' => 0,
                'readOnly' => 1
            ],
        ],
        'sent' => [
            'exclude' => false,
            'label' => $ll . '.sent',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputDateTime',
                'size' => 10,
                'eval' => 'datetime',
                'default' => 0,
                'readOnly' => 1
            ],
        ],
        'logs' => [
            'exclude' => false,
            'label' => $llg . '.field.logs',
            'config' => [
                'type' => 'passthrough',
                'foreign_table' => 'tx_publicrelations_domain_model_log',
                'foreign_field' => 'mailing',
            ],
        ],


        'mails' => [
            'exclude' => false,
            'label' => $ll . '.mails',
            'config' => [
                'type' => 'passthrough',
                'foreign_table' => 'tx_publicrelations_domain_model_mail',
                'foreign_field' => 'mailing',
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
