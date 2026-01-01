<?php

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use BucheggerOnline\Publicrelations\Domain\Model\Dto\EmConfiguration;
use BucheggerOnline\Publicrelations\Backend\TCA;

$ll = 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_accreditation';
$llg = 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_general';
$configuration = GeneralUtility::makeInstance(EmConfiguration::class);

return [
    'ctrl' => [
        'title' => $ll,
        'label' => 'title',
        'label_userFunc' => \BucheggerOnline\Publicrelations\Backend\Labels::class . '->generateAccreditation',
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
        'searchFields' => 'title,first_name,middle_name,last_name,email,phone,medium,notes',
        'iconfile' => 'EXT:publicrelations/Resources/Public/Icons/tx_publicrelations_domain_model_accreditation.svg'
    ],
    'types' => [
        '1' => [
            'showitem' => '
                --div--;LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_general.tab.general,
                    --palette--;LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_accreditation.palettes.accreditationData;paletteAccreditationData,
                    --palette--;LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_accreditation.palettes.journalist;paletteJournalist,
                    --div--;LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_general.tab.checkin,
                    --palette--;;paletteCheckIn,
                    --div--;LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_general.tab.internal,
                      internal_notes,
                    --div--;LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_general.tab.logs,
                      logs,
                --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.access,
                    --palette--;;paletteAccess,
                '
        ],
    ],
    'palettes' => [
        'paletteJournalist' => [
            'showitem' => '
                guest, facie, --linebreak--,
                medium, medium_type, --linebreak--,
                gender, title, --linebreak--,
                first_name, middle_name, last_name, --linebreak--,
                email, phone',
            'canNotColapse' => 1
        ],
        'paletteAccreditationData' => [
            'showitem' => '
                event, --linebreak--,
                status, type, guest_type, invitation_status, --linebreak--,
                tickets_wish, present, --linebreak--,
                photographer, camerateam, request_note',
            'canNotColapse' => 1
        ],
        'paletteCheckIn' => [
            'showitem' => '
                tickets_approved, program, pass, seats, tickets, --linebreak--,
                notes, notes_select, --linebreak--,
                tickets_received, notes_received',
            'canNotColapse' => 1
        ],
        'paletteSlug' => TCA::getPaletteSlug(),
        'paletteSEO' => TCA::getPaletteSEO(),
        'paletteAccess' => TCA::getPaletteAccess(),
    ],
    'columns' => [

        'status' => [
            'exclude' => false,
            'label' => $ll . '.status',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [$ll . '.status.pending', 0, 'EXT:publicrelations/Resources/Public/Icons/pending.svg'],
                    [$ll . '.status.approved', 1, 'EXT:publicrelations/Resources/Public/Icons/approved.svg'],
                    [$ll . '.status.checkedin', 2, 'EXT:publicrelations/Resources/Public/Icons/checkedin.svg'],
                    [$ll . '.status.rejected', -1, 'EXT:publicrelations/Resources/Public/Icons/rejected.svg'],
                    [$ll . '.status.waiting', -2, 'EXT:publicrelations/Resources/Public/Icons/pending.svg'],
                    ['E-Mail-Fehler', 99, 'EXT:publicrelations/Resources/Public/Icons/error.svg'],
                    ['Duplikat', 9, 'EXT:publicrelations/Resources/Public/Icons/error.svg'],
                ],
                'default' => 0,
            ],
        ],
        'invitation_status' => [
            'exclude' => false,
            'label' => $ll . '.invitation_status',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [$ll . '.invitation_status.prepared', 0],
                    [$ll . '.invitation_status.invited', 1],
                    [$ll . '.invitation_status.reminded', 2],
                    [$ll . '.invitation_status.pushed', 3],
                    [$ll . '.invitation_status.reported', -1],
                ],
                'default' => 0,
            ],
        ],
        'type' => [
            'exclude' => false,
            'label' => $ll . '.type',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [$ll . '.type.manual', 0],
                    [$ll . '.type.request', 1],
                    [$ll . '.type.invitation', 2],
                ],
                'default' => 0,
            ],
        ],
        'guest_type' => [
            'exclude' => false,
            'label' => $ll . '.guest_type',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [$llg . '.field.select', 0],
                    [$ll . '.guest_type.vip', 1],
                    [$ll . '.guest_type.press', 2],
                    [$ll . '.guest_type.talent', 6],
                    [$ll . '.guest_type.winner', 3],
                    [$ll . '.guest_type.filler', 4],
                    [$ll . '.guest_type.staff', 5]
                ],
                'default' => 0,
            ],
        ],
        'facie' => [
            'exclude' => false,
            'label' => $ll . '.facie',
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
        'gender' => [
            'exclude' => false,
            'label' => $ll . '.gender',
            'config' => [
                'type' => 'radio',
                'default' => '',
                'items' => [
                    [$ll . '.gender.x', 'v'],
                    [$ll . '.gender.f', 'f'],
                    [$ll . '.gender.m', 'm']
                ]
            ]
        ],
        'title' => [
            'exclude' => false,
            'label' => $ll . '.title',
            'config' => [
                'type' => 'input',
                'size' => 10,
                'eval' => 'trim'
            ],
        ],
        'first_name' => [
            'exclude' => false,
            'label' => $ll . '.first_name',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim'
            ],
        ],
        'middle_name' => [
            'exclude' => false,
            'label' => $ll . '.middle_name',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim'
            ],
        ],
        'last_name' => [
            'exclude' => false,
            'label' => $ll . '.last_name',
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
        'phone' => [
            'exclude' => false,
            'label' => $ll . '.phone',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim'
            ],
        ],
        'request_note' => [
            'exclude' => false,
            'label' => $ll . '.request_note',
            'config' => [
                'type' => 'text',
                'cols' => 40,
                'rows' => 5,
                'eval' => 'trim'
            ]
        ],
        'dsgvo' => [
            'exclude' => false,
            'label' => $ll . '.dsgvo',
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
        'ip' => [
            'exclude' => false,
            'label' => $ll . '.ip',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim'
            ],
        ],
        'medium' => [
            'exclude' => false,
            'label' => $ll . '.medium',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim'
            ],
        ],
        'tickets_wish' => [
            'exclude' => false,
            'label' => $ll . '.tickets_wish',
            'config' => [
                'type' => 'input',
                'size' => 4,
                'eval' => 'int'
            ]
        ],
        'tickets_approved' => [
            'exclude' => false,
            'label' => $ll . '.tickets_approved',
            'config' => [
                'type' => 'input',
                'size' => 4,
                'eval' => 'int',
                'default' => 0
            ]
        ],
        'tickets_received' => [
            'exclude' => false,
            'label' => $ll . '.tickets_received',
            'config' => [
                'type' => 'input',
                'size' => 4,
                'eval' => 'int',
                'default' => 0
            ]
        ],
        'notes_received' => [
            'exclude' => false,
            'label' => $ll . '.notes_received',
            'config' => [
                'type' => 'text',
                'cols' => 40,
                'rows' => 5,
                'eval' => 'trim'
            ]
        ],
        'locking_be_user_uid' => [
            'exclude' => false,
            'config' => [
                'type' => 'passthrough'
            ],
        ],
        'locking_tstamp' => [
            'exclude' => false,
            'config' => [
                'type' => 'passthrough'
            ],
        ],
        'program' => [
            'exclude' => false,
            'label' => $ll . '.program',
            'config' => [
                'type' => 'input',
                'size' => 4,
                'eval' => 'int',
                'default' => 0
            ]
        ],
        'pass' => [
            'exclude' => false,
            'label' => $ll . '.pass',
            'config' => [
                'type' => 'input',
                'size' => 4,
                'eval' => 'int',
                'default' => 0
            ]
        ],
        'seats' => [
            'exclude' => false,
            'label' => $ll . '.seats',
            'config' => [
                'type' => 'text',
                'cols' => 40,
                'rows' => 5,
                'eval' => 'trim'
            ]
        ],
        'tickets' => [
            'exclude' => false,
            'label' => $ll . '.tickets',
            'config' => [
                'type' => 'text',
                'cols' => 40,
                'rows' => 5,
                'eval' => 'trim'
            ]
        ],
        'notes' => [
            'exclude' => false,
            'label' => $ll . '.notes',
            'config' => [
                'type' => 'text',
                'cols' => 40,
                'rows' => 5,
                'eval' => 'trim'
            ]
        ],
        'notes_select' => [
            'exclude' => false,
            'label' => $ll . '.notes_select',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'foreign_table' => 'sys_category',
                'foreign_table_where' => ' AND (sys_category.parent = ' . $configuration->getAccreditationNotesRootUid() . ') ORDER BY sys_category.title',
                'MM' => 'sys_category_record_mm',
                'MM_match_fields' => [
                    'tablenames' => 'tx_publicrelations_domain_model_accreditation',
                    'fieldname' => 'notes_select',
                ],
                'MM_opposite_field' => 'items',
                'size' => 5,
                'autoSizeMax' => 5,
                'maxitems' => 99,
                'minitems' => 0,
                'default' => 0,
            ],
        ],
        'photographer' => [
            'exclude' => false,
            'label' => $ll . '.photographer',
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
        'camerateam' => [
            'exclude' => false,
            'label' => $ll . '.camerateam',
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
        'event' => [
            'exclude' => false,
            'label' => $ll . '.event',
            'config' => [
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'tx_publicrelations_domain_model_event',
                'foreign_table' => 'tx_publicrelations_domain_model_event',
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
        'guest' => [
            'exclude' => false,
            'label' => $ll . '.guest',
            'config' => [
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'tt_address',
                'foreign_table' => 'tt_address',
                'size' => 1,
                'minitems' => 0,
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
        'medium_type' => [
            'exclude' => false,
            'label' => $ll . '.medium_type',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'sys_category',
                'foreign_table_where' => ' AND (sys_category.parent = ' . $configuration->getMediumRootUid() . ') ORDER BY sys_category.title',
                'items' => [
                    [$llg . '.field.select', 0],
                ],
                'size' => 1,
                'maxitems' => 1,
                'minitems' => 1,
                'default' => 0,
                'eval' => ''
            ],
        ],
        'logs' => [
            'exclude' => false,
            'label' => $llg . '.field.logs',
            'config' => [
                'type' => 'passthrough',
                'foreign_table' => 'tx_publicrelations_domain_model_log',
                'foreign_field' => 'accreditation'
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
        'additional_answers' => [
            'exclude' => false,
            'label' => $ll . '.additional_answers',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_publicrelations_domain_model_additionalanswer',
                'foreign_field' => 'accreditation',
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
        'invitation_type' => [
            'config' => [
                'type' => 'passthrough',
                'foreign_table' => 'tx_publicrelations_domain_model_invitation',
            ],
        ],
        'opened' => [
            'config' => [
                'type' => 'passthrough'
            ]
        ],
        'duplicate_of' => [
            'exclude' => false,
            'label' => $ll . '.duplicate_of',
            'config' => [
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'tx_publicrelations_domain_model_accreditation',
                'foreign_table' => 'tx_publicrelations_domain_model_accreditation',
                'readOnly' => true,
                'maxitems' => 1,
                'minitems' => 0,
                'size' => 1,
            ]
        ],
        'is_master' => [
            'exclude' => false,
            'label' => $ll . '.is_master',
            'config' => [
                'type' => 'check',
                'default' => 0,
                'readOnly' => true,
            ]
        ],
        'ignored_duplicates' => [
            'exclude' => false,
            'label' => $ll . '.ignored_duplicates',
            'config' => [
                'type' => 'text',
                'cols' => 20,
                'rows' => 2,
                'eval' => 'trim',
                'readOnly' => true,
            ]
        ],
        'distribution_job' => [
            'exclude' => false,
            'label' => 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_accreditation.distribution_job',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_acdistribution_domain_model_job',
                // 'foreign_table_where' => 'AND {#tx_acdistribution_domain_model_job}.{#pid}=###CURRENT_PID###', // oder eine andere Einschränkung
                'minitems' => 0,
                'maxitems' => 1, // Wichtig für n:1 (oder 1:1)
                'default' => 0,
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
