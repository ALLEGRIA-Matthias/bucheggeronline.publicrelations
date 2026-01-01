<?php

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use BucheggerOnline\Publicrelations\Domain\Model\Dto\EmConfiguration;
use BucheggerOnline\Publicrelations\Backend\TCA;

$ll = 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_event';
$llg = 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_general';
$configuration = GeneralUtility::makeInstance(EmConfiguration::class);

return [
    'ctrl' => [
        'title' => $ll,
        'label' => 'date',
        'label_alt' => 'type, title',
        'label_alt_force' => 1,
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
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
        'searchFields' => 'title,location',
        'iconfile' => 'EXT:publicrelations/Resources/Public/Icons/tx_publicrelations_domain_model_event.svg'
    ],
    'types' => [
        '1' => [
            'showitem' => '
                --div--;LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_general.palettes.general,
                    --palette--;;paletteGeneral,
                --div--;LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_general.palettes.additional,
                    --palette--;;paletteAdditional,
                --div--;LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_general.tab.invitations,
                    --palette--;;paletteInvitations,
                --div--;LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_general.tab.logs,
                  logs
                --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.access,
                    --palette--;;paletteAccess,
                '
        ],
    ],
    'palettes' => [
        'paletteGeneral' => [
            'showitem' => '
                date, date_fulltime, canceled, private, --linebreak--,
                title, type, --linebreak--,
                client, --linebreak--,
                partners, campaign, --linebreak--,
                location, location_note, --linebreak--,
                online, accreditation, checkin, --linebreak--,
                duration, duration_approx, duration_with_break',
            'canNotColapse' => 1
        ],
        'paletteAdditional' => [
            'showitem' => '
                notes, notes_overwrite, --linebreak--,
                notes_manual, --linebreak--,
                additional_fields, --linebreak--,
                old_event, new_event',
            'canNotColapse' => 1
        ],
        'paletteInvitations' => [
            'showitem' => '
                overwrite_theaterevent, manual_confirmation, tickets_quota, invitation_report_stop, --linebreak--,
                invitation_notes_title, invitation_notes_description, invitation_notes_required, --linebreak--,
                invitations',
            'canNotColapse' => 1
        ],
        'paletteAccess' => TCA::getPaletteAccess(),
    ],
    'columns' => [

        'title' => [
            'exclude' => false,
            'label' => $ll . '.title',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim,required'
            ],
        ],
        'date' => [
            'exclude' => false,
            'label' => $ll . '.date',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputDateTime',
                'eval' => 'datetime,required',
                'default' => time()
            ],
        ],
        'date_fulltime' => [
            'exclude' => false,
            'label' => $ll . '.date_fulltime',
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
        'accreditation' => [
            'exclude' => false,
            'label' => $ll . '.accreditation',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxLabeledToggle',
                'items' => [
                    [
                        0 => '',
                        1 => '',
                        'labelChecked' => $ll . '.accreditation.allowed',
                        'labelUnchecked' => $ll . '.accreditation.prohibited',
                    ]
                ],
                'default' => 1
            ],
        ],
        'online' => [
            'exclude' => false,
            'label' => $ll . '.online',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxLabeledToggle',
                'items' => [
                    [
                        0 => '',
                        1 => '',
                        'labelChecked' => $ll . '.online.1',
                        'labelUnchecked' => $ll . '.online.0',
                    ]
                ],
            ]
        ],
        'private' => [
            'exclude' => true,
            'label' => $ll . '.private',
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
        'canceled' => [
            'exclude' => true,
            'label' => $ll . '.canceled',
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
        'overwrite_theaterevent' => [
            'exclude' => false,
            'label' => $ll . '.overwrite_theaterevent',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [$ll . '.overwrite_theaterevent.0', 0],
                    [$ll . '.overwrite_theaterevent.1', 1],
                    [$ll . '.overwrite_theaterevent.2', 2]
                ],
                'default' => 0,
            ]
        ],
        'duration' => [
            'exclude' => false,
            'label' => $ll . '.duration',
            'config' => [
                'type' => 'input',
                'size' => 10,
                'eval' => 'trim,int',
                'default' => 0,
            ],
        ],
        'duration_approx' => [
            'exclude' => false,
            'label' => $ll . '.duration_approx',
            'config' => [
                'type' => 'check',
                'items' => [
                    '1' => [
                        '0' => 'LLL:EXT:lang/locallang_core.xlf:labels.enabled'
                    ]
                ],
                'default' => 0,
            ]
        ],
        'duration_with_break' => [
            'exclude' => false,
            'label' => $ll . '.duration_with_break',
            'config' => [
                'type' => 'check',
                'items' => [
                    '1' => [
                        '0' => 'LLL:EXT:lang/locallang_core.xlf:labels.enabled'
                    ]
                ],
                'default' => 0,
            ]
        ],
        'opening' => [
            'exclude' => true,
            'label' => $ll . '.opening',
            'config' => [
                'type' => 'check',
                'items' => [
                    '1' => [
                        '0' => 'LLL:EXT:lang/locallang_core.xlf:labels.enabled'
                    ]
                ],
                'default' => 0,
            ]
        ],
        'checkin' => [
            'exclude' => true,
            'label' => $ll . '.checkin',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxLabeledToggle',
                'items' => [
                    [
                        0 => '',
                        1 => '',
                        'labelChecked' => $ll . '.checkin.1',
                        'labelUnchecked' => $ll . '.checkin.0',
                    ]
                ],
            ]
        ],
        'type_overwrite' => [
            'exclude' => false,
            'label' => $ll . '.type_overwrite',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim'
            ],
        ],
        'notes_overwrite' => [
            'exclude' => true,
            'label' => $ll . '.notes_overwrite',
            'description' => $llg . '.description.overwrite',
            'config' => [
                'type' => 'text',
                'renderType' => 't3editor',
                'rows' => 5,
                'eval' => 'trim'
            ]
        ],
        'notes_manual' => [
            'exclude' => true,
            'label' => $ll . '.notes_manual',
            'config' => [
                'type' => 'text',
                'enableRichtext' => true,
                'richtextConfiguration' => 'default',
                'fieldControl' => [
                    'fullScreenRichtext' => [
                        'disabled' => false,
                    ],
                ],
                'rows' => 15,
                'eval' => 'trim',
            ],

        ],
        'invitations' => [
            'exclude' => false,
            'label' => $ll . '.invitations',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_publicrelations_domain_model_invitation',
                'foreign_field' => 'event',
                'maxitems' => 9999,
                'appearance' => [
                    'collapseAll' => 1,
                    'levelLinksPosition' => 'bottom',
                    'showSynchronizationLink' => 1,
                    'showPossibleLocalizationRecords' => 1,
                    'showAllLocalizationLink' => 1
                ],
            ],
        ],
        'invitation_subject' => [
            'exclude' => false,
            'label' => $ll . '.invitation_subject',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim'
            ],
        ],
        'invitation_from' => [
            'exclude' => false,
            'label' => $ll . '.invitation_from',
            'description' => $ll . '.invitation_from.description',
            'config' => [
                'type' => 'text',
                'enableRichtext' => true,
                'richtextConfiguration' => 'mini',
                'fieldControl' => [
                    'fullScreenRichtext' => [
                        'disabled' => false,
                    ],
                ],
                'eval' => 'trim',
            ],
        ],
        'invitation_from_personally' => [
            'exclude' => false,
            'label' => $ll . '.invitation_from_personally',
            'description' => $ll . '.invitation_from_personally.description',
            'config' => [
                'type' => 'text',
                'enableRichtext' => true,
                'richtextConfiguration' => 'mini',
                'fieldControl' => [
                    'fullScreenRichtext' => [
                        'disabled' => false,
                    ],
                ],
                'eval' => 'trim',
            ],
        ],
        'invitation_text' => [
            'exclude' => false,
            'label' => $ll . '.invitation_text',
            'config' => [
                'type' => 'text',
                'enableRichtext' => true,
                'richtextConfiguration' => 'min',
                'fieldControl' => [
                    'fullScreenRichtext' => [
                        'disabled' => false,
                    ],
                ],
                'eval' => 'trim',
            ],
        ],
        'invitation_text_personally' => [
            'exclude' => false,
            'label' => $ll . '.invitation_text_personally',
            'config' => [
                'type' => 'text',
                'enableRichtext' => true,
                'richtextConfiguration' => 'min',
                'fieldControl' => [
                    'fullScreenRichtext' => [
                        'disabled' => false,
                    ],
                ],
                'eval' => 'trim',
            ],
        ],
        'invitation_image' => [
            'exclude' => false,
            'label' => $ll . '.invitation_image',
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
        'invitation_logo' => [
            'exclude' => false,
            'label' => $ll . '.invitation_logo',
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
        'invitation_report_stop' => [
            'exclude' => false,
            'label' => $ll . '.invitation_report_stop',
            'description' => $ll . '.invitation_report_stop.description',
            'config' => [
                'type' => 'input',
                'size' => 10,
                'min' => -24,
                'eval' => 'trim,int',
                'default' => 0,
            ],
        ],
        'invitation_notes_required' => [
            'exclude' => false,
            'label' => $ll . '.invitation_notes_required',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'items' => [
                    [
                        'label' => '',
                        'value' => 1,
                    ]
                ],
                'default' => 1
            ],
        ],
        'invitation_notes_title' => [
            'exclude' => false,
            'label' => $ll . '.invitation_notes_title',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim'
            ],
        ],
        'invitation_notes_description' => [
            'exclude' => false,
            'label' => $ll . '.invitation_notes_description',
            'config' => [
                'type' => 'text',
                'rows' => 5
            ],
        ],
        'location_note' => [
            'exclude' => false,
            'label' => $ll . '.location_note',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim'
            ],
        ],
        'location' => [
            'exclude' => false,
            'label' => $llg . '.field.location',
            'config' => [
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'tx_publicrelations_domain_model_location',
                'foreign_table' => 'tx_publicrelations_domain_model_location',
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
        'notes' => [
            'exclude' => true,
            'label' => $ll . '.notes',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'foreign_table' => 'sys_category',
                'foreign_table_where' => ' AND (sys_category.parent = ' . $configuration->getEventNoteRootUid() . ') ORDER BY sys_category.title',
                'MM' => 'sys_category_record_mm',
                'MM_match_fields' => [
                    'tablenames' => 'tx_publicrelations_domain_model_event',
                    'fieldname' => 'notes',
                ],
                'MM_opposite_field' => 'items',
                'size' => 5,
                'minitems' => 0,
                'maxitems' => 99,
                'default' => 0,
            ]
        ],
        'type' => [
            'exclude' => false,
            'label' => $ll . '.type',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'sys_category',
                'foreign_table_where' => ' AND (sys_category.parent = ' . $configuration->getEventRootUid() . ') ORDER BY sys_category.title',
                'items' => [
                    [$llg . '.field.select', 0]
                ],
                'default' => 0,
                'minitems' => 1,
                'maxitems' => 1,
            ],
        ],
        'manual_confirmation' => [
            'exclude' => false,
            'label' => $ll . '.manual_confirmation',
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
        'tickets_quota' => [
            'exclude' => false,
            'label' => $ll . '.tickets_quota',
            'config' => [
                'type' => 'input',
                'size' => 4,
                'eval' => 'int,required',
                'default' => 0
            ]
        ],
        'waiting_quota' => [
            'exclude' => false,
            'label' => $ll . '.waiting_quota',
            'config' => [
                'type' => 'input',
                'size' => 4,
                'eval' => 'int,required',
                'default' => 0
            ]
        ],
        'links' => [
            'exclude' => false,
            'label' => $llg . '.field.links',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_publicrelations_domain_model_link',
                'foreign_field' => 'event',
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
        'accreditations' => [
            'exclude' => false,
            'label' => $ll . '.accreditations',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_publicrelations_domain_model_accreditation',
                'foreign_field' => 'event',
                'maxitems' => 9999,
                'appearance' => [
                    'collapseAll' => 1,
                    'levelLinksPosition' => 'both',
                    'showSynchronizationLink' => 1,
                    'showPossibleLocalizationRecords' => 1,
                    'showAllLocalizationLink' => 1
                ],
            ],
        ],
        'old_event' => [
            'exclude' => true,
            'label' => $ll . '.old_event',
            'config' => [
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'tx_publicrelations_domain_model_event',
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
        'new_event' => [
            'exclude' => true,
            'label' => $ll . '.new_event',
            'config' => [
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'tx_publicrelations_domain_model_event',
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
        'additional_fields' => [
            'exclude' => false,
            'label' => $ll . '.additional_fields',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_publicrelations_domain_model_additionalfield',
                'foreign_field' => 'event',
                'foreign_sortby' => 'sorting',
                'maxitems' => 9999,
                'appearance' => [
                    'collapseAll' => 1,
                    'levelLinksPosition' => 'bottom',
                    'showSynchronizationLink' => 1,
                    'showPossibleLocalizationRecords' => 1,
                    'showAllLocalizationLink' => 1
                ],
            ],
        ],
        'logs' => [
            'exclude' => false,
            'label' => $llg . '.field.logs',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_publicrelations_domain_model_log',
                'foreign_field' => 'event',
                'maxitems' => 9999,
                'appearance' => [
                    'collapseAll' => 1,
                    'showSynchronizationLink' => 1,
                    'showPossibleLocalizationRecords' => 1,
                    'showAllLocalizationLink' => 1,
                    'enabledControls' => [
                        'delete' => false,
                        'new' => false
                    ]
                ],
            ],
        ],
        'client' => [
            'exclude' => false,
            'label' => $llg . '.field.client',
            'config' => [
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'tx_publicrelations_domain_model_client',
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
        'partners' => [
            'exclude' => false,
            'label' => $ll . '.partners',
            'config' => [
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'tx_publicrelations_domain_model_client',
                'foreign_table' => 'tx_publicrelations_domain_model_client',
                'MM' => 'tx_publicrelations_event_client_mm',
                'size' => 3,
                'autoSizeMax' => 10,
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
        'campaign' => [
            'exclude' => false,
            'label' => $llg . '.field.campaign',
            'config' => [
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'tx_publicrelations_domain_model_campaign',
                'foreign_table' => 'tx_publicrelations_domain_model_campaign',
                // 'foreign_table_where' => 'AND {#tx_publicrelations_domain_model_campaign}.{#client} = ###REC_FIELD_client### AND ###REC_FIELD_client### > 0 ORDER BY {#tx_publicrelations_domain_model_campaign}.{#title} ASC',
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
                'foreign_table' => 'tx_publicrelations_domain_model_event',
                'foreign_table_where' => 'AND {#tx_publicrelations_domain_model_event}.{#pid}=###CURRENT_PID### AND {#tx_publicrelations_domain_model_event}.{#sys_language_uid} IN (-1,0)',
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
