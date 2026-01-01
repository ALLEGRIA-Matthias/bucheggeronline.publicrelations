<?php

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use BucheggerOnline\Publicrelations\Domain\Model\Dto\EmConfiguration;

defined('TYPO3') || die();

$GLOBALS['TCA']['tt_address']['ctrl']['sortby'] = '';
$GLOBALS['TCA']['tt_address']['ctrl']['default_sortby'] = 'ORDER BY last_name ASC, first_name ASC, middle_name ASC, company ASC';

unset(
    $GLOBALS['TCA']['tt_address']['ctrl']['languageField'],
    $GLOBALS['TCA']['tt_address']['ctrl']['transOrigPointerField'],
    $GLOBALS['TCA']['tt_address']['ctrl']['transOrigDiffSourceField'],
    $GLOBALS['TCA']['tt_address']['ctrl']['translationSource']
);

// Optional: Entferne auch die Spalten-Konfiguration, falls vorhanden
unset($GLOBALS['TCA']['tt_address']['columns']['sys_language_uid']);
unset($GLOBALS['TCA']['tt_address']['columns']['l10n_parent']);
unset($GLOBALS['TCA']['tt_address']['columns']['l10n_diffsource']);

$llg = 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_general';
$ll = 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tt_address';
$generalLanguageFilePrefix = 'LLL:EXT:lang/Resources/Private/Language/';
$configuration = GeneralUtility::makeInstance(EmConfiguration::class);

$tmp_publicrelations_columns = [
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
            'readOnly' => true,
        ],
    ],
    'image' => [
        'exclude' => true,
        'label' => 'Foto',
        'config' => [
            'type' => 'file',
            'maxitems' => 1,
            'minitems' => 0,
            'appearance' => [
                'collapseAll' => true,
                'createNewRelationLinkTitle' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:images.addFileReference',
                'showPossibleLocalizationRecords' => true,
                'showRemovedLocalizationRecords' => true,
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
                                        '1:1' => [
                                            'title' => 'Square',
                                            'value' => 1
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
        ]
    ],
    'special_title' => [
        'exclude' => false,
        'label' => $ll . '.special_title',
        'config' => [
            'type' => 'input',
            'size' => 30,
            'eval' => 'trim'
        ],
    ],

    'mailing_exclude' => [
        'exclude' => false,
        'label' => $ll . '.mailing_exclude',
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

    'mailing_no_html' => [
        'exclude' => false,
        'label' => $ll . '.mailing_no_html',
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
    'pid' => [
        'config' => [
            'type' => 'passthrough',
        ],
    ],
    'logs' => [
        'exclude' => false,
        'label' => $llg . '.field.logs',
        'config' => [
            'type' => 'passthrough',
            'foreign_table' => 'tx_publicrelations_domain_model_log',
            'foreign_field' => 'address'
        ],
    ],
    'duplicates' => [
        'exclude' => false,
        'label' => $ll . '.duplicates',
        'config' => [
            'type' => 'group',
            'internal_type' => 'db',
            'allowed' => 'tt_address',
            'foreign_table' => 'tt_address',
            'foreign_table_where' => ' AND (tt_address.deleted = 0 OR tt_address.deleted = 1)',
            'MM' => 'tt_address_mm',
            'size' => 5,
            'minitems' => 0,
            'maxitems' => 100,
            'hideSuggest' => 0,
            'multiple' => 0,
            'default' => 0,
            'fieldControl' => [
                'addRecord' => [
                    'disabled' => true,
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
    'duplicate_of' => [
        'exclude' => false,
        'label' => 'Duplikat von',
        'config' => [
            'type' => 'group',
            'internal_type' => 'db',
            'allowed' => 'tt_address',
            'size' => 1,
            'minitems' => 0,
            'maxitems' => 1,
            'hideSuggest' => 0,
            'multiple' => 0,
            'default' => 0,
            'readOnly' => true,
        ],
    ],
    'valid' => [
        'exclude' => false,
        'label' => $ll . '.valid',
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
    'copy_to_pid' => [
        'config' => [
            'type' => 'passthrough',
        ],
    ],
    'contact_types' => [
        'exclude' => false,
        'label' => $ll . '.contact_types',
        'config' => [
            'type' => 'select',
            'renderType' => 'selectCheckBox',
            'foreign_table' => 'sys_category',
            'foreign_table_where' => ' AND sys_category.parent = 602',
            'MM' => 'sys_category_record_mm',
            'MM_match_fields' => [
                'fieldname' => 'contact_types',
                'tablenames' => 'tt_address',
            ],
            'MM_opposite_field' => 'items',
            'default' => 0
        ]
    ],
    'working_for' => [
        'exclude' => true,
        'label' => 'Arbeitet für (eins pro Zeile)',
        'config' => [
            'type' => 'text',
            'cols' => 40,
            'rows' => 5,
            'eval' => 'trim',
            'description' => 'Bitte tragen Sie pro Zeile genau ein Magazin, ein Format, eine Sendung oder eine Marke ein. Dies ermöglicht eine spätere automatische Zuordnung.'
        ],
    ],
    'original_address' => [
        'exclude' => false,
        'label' => 'Originaler Kontakt',
        'config' => [
            'type' => 'group',
            'internal_type' => 'db',
            'allowed' => 'tt_address',
            'size' => 1,
            'minitems' => 0,
            'maxitems' => 1,
            'default' => 0,
            'readOnly' => true,
        ],
    ],
    'social_profiles' => [
        'label' => 'Social Media Profiles',
        'config' => [
            'type' => 'inline',
            'foreign_table' => 'tx_publicrelations_domain_model_socialprofile',
            'foreign_field' => 'contact',
            'foreign_sortby' => 'sorting',
            'maxitems' => 99,
            'appearance' => [
                'collapseAll' => 1,
                'levelLinksPosition' => 'top',
                'showSynchronizationLink' => 1,
                'showPossibleLocalizationRecords' => 1,
                'showAllLocalizationLink' => 1,
                'useSortable' => 1,
            ],
        ],
    ],
    'groups' => [
        'label' => 'Gruppen',
        'config' => [
            'type' => 'select',
            'renderType' => 'selectMultipleSideBySide',
            'foreign_table' => 'tx_publicrelations_domain_model_contactgroup',
            'MM' => 'tx_publicrelations_contactgroup_ttaddress_mm',
            'MM_opposite_field' => 'contacts',
            'size' => 10,
            'autoSizeMax' => 30,
            'maxitems' => 9999,
            'multiple' => 0,
        ],
    ],
    'tags' => [
        'exclude' => true,
        'config' => [
            'type' => 'tag'
        ]
    ],
    'email_cc' => [
        'exclude' => 1,
        'label' => 'Standard CC-Empfänger (eine pro Zeile)',
        'config' => [
            'type' => 'text',
            'cols' => 40,
            'rows' => 3,
            'eval' => 'trim,lower',
        ],
    ],
    'email_bcc' => [
        'exclude' => 1,
        'label' => 'Standard BCC-Empfänger (eine pro Zeile)',
        'config' => [
            'type' => 'text',
            'cols' => 40,
            'rows' => 3,
            'eval' => 'trim,lower',
        ],
    ],
];

ExtensionManagementUtility::addTCAcolumns('tt_address', $tmp_publicrelations_columns);


// Palette für "Verknüpfungen"
$GLOBALS['TCA']['tt_address']['palettes']['relations']['showitem'] = 'client, original_address';

// Palette für "Name"
$GLOBALS['TCA']['tt_address']['palettes']['name']['showitem'] = 'gender, title, title_suffix, birthday, --linebreak--, first_name, middle_name, last_name, --linebreak--, name, special_title';

// Palette für "Organisation"
$GLOBALS['TCA']['tt_address']['palettes']['organisation']['showitem'] = 'company, position, --linebreak--, contact_types, groups, --linebreak--, working_for';

// Palette für "Kontakt"
$GLOBALS['TCA']['tt_address']['palettes']['contact']['showitem'] = 'email, --linebreak--, email_cc, email_bcc, --linebreak--, mobile, phone, --linebreak-- , www';

// Palette für "Social Media"
$GLOBALS['TCA']['tt_address']['palettes']['social_backup']['showitem'] = 'facebook, instagram, --linebreak-- , tiktok, linkedin, --linebreak-- , twitter';

// Palette für "Adresse" (wird in Tab "Anschrift" verwendet)
$GLOBALS['TCA']['tt_address']['palettes']['address']['showitem'] = 'address, --linebreak--, zip, city, region, --linebreak--, country';

// Palette für "Mailings" (umbenannt zu "Einstellungen") 
$GLOBALS['TCA']['tt_address']['palettes']['mailingPalette']['showitem'] = 'mailing_exclude, personally, mailing_no_html';

// Palette für "Kategorien"
$GLOBALS['TCA']['tt_address']['palettes']['categoriesPalette']['showitem'] = 'categories';

// Palette für "Duplikate"
$GLOBALS['TCA']['tt_address']['palettes']['duplicatesPalette']['showitem'] = 'duplicates, duplicate_of';


// ### SCHRITT 2: Spezifische Feld-Eigenschaften anpassen ###

// (Änderung: Feld 'name' wird schreibgeschützt)
$GLOBALS['TCA']['tt_address']['columns']['name']['config']['readOnly'] = true;

$GLOBALS['TCA']['tt_address']['columns']['image']['config']['appearance']['fileByUrlAllowed'] = true;
$GLOBALS['TCA']['tt_address']['columns']['image']['config']['allowed'] = 'common-image-types';

// (Änderung: Felder 'personally' und 'mailing_no_html' werden schreibgeschützt)
$GLOBALS['TCA']['tt_address']['columns']['personally']['config']['readOnly'] = true;
$GLOBALS['TCA']['tt_address']['columns']['mailing_no_html']['config']['readOnly'] = true;
$GLOBALS['TCA']['tt_address']['columns']['birthday']['config']['format'] = 'date';

$GLOBALS['TCA']['tt_address']['columns']['email']['config']['eval'] = 'lower';

$GLOBALS['TCA']['tt_address']['columns']['categories']['config'] = [
    'type' => 'select',
    'renderType' => 'selectTree',
    'foreign_table' => 'sys_category',
    'foreign_table_where' => ' AND sys_category.pid = 91',
    'MM' => 'sys_category_record_mm',
    'MM_match_fields' => [
        'fieldname' => 'categories',
        'tablenames' => 'tt_address',
    ],
    'MM_opposite_field' => 'items',
    'default' => 0,
    'size' => 20,
    'treeConfig' => [
        'parentField' => 'parent',
        'appearance' => [
            'expandAll' => false,
            'showHeader' => true,
            'maxLevels' => 99,
            'nonSelectableLevels' => '0,1',
        ],
    ]
];

// Füge die 'importable'-Eigenschaft zu den gewünschten Spalten hinzu
$GLOBALS['TCA']['tt_address']['columns']['gender']['config']['importable'] = true;

$GLOBALS['TCA']['tt_address']['columns']['title']['config']['importable'] = true;
$GLOBALS['TCA']['tt_address']['columns']['first_name']['config']['importable'] = true;
$GLOBALS['TCA']['tt_address']['columns']['middle_name']['config']['importable'] = true;
$GLOBALS['TCA']['tt_address']['columns']['last_name']['config']['importable'] = true;
$GLOBALS['TCA']['tt_address']['columns']['title_suffix']['config']['importable'] = true;

$GLOBALS['TCA']['tt_address']['columns']['special_title']['config']['importable'] = true;

$GLOBALS['TCA']['tt_address']['columns']['email']['config']['importable'] = true;
$GLOBALS['TCA']['tt_address']['columns']['phone']['config']['importable'] = true;
$GLOBALS['TCA']['tt_address']['columns']['mobile']['config']['importable'] = true;
$GLOBALS['TCA']['tt_address']['columns']['fax']['config']['importable'] = true;
$GLOBALS['TCA']['tt_address']['columns']['www']['config']['importable'] = true;

$GLOBALS['TCA']['tt_address']['columns']['company']['config']['importable'] = true;
$GLOBALS['TCA']['tt_address']['columns']['position']['config']['importable'] = true;
$GLOBALS['TCA']['tt_address']['columns']['working_for']['config']['importable'] = true;

$GLOBALS['TCA']['tt_address']['columns']['address']['config']['importable'] = true;
$GLOBALS['TCA']['tt_address']['columns']['zip']['config']['importable'] = true;
$GLOBALS['TCA']['tt_address']['columns']['city']['config']['importable'] = true;
$GLOBALS['TCA']['tt_address']['columns']['region']['config']['importable'] = true;
$GLOBALS['TCA']['tt_address']['columns']['country']['config']['importable'] = true;

$GLOBALS['TCA']['tt_address']['columns']['building']['config']['importable'] = true;
$GLOBALS['TCA']['tt_address']['columns']['room']['config']['importable'] = true;

$GLOBALS['TCA']['tt_address']['columns']['birthday']['config']['importable'] = true;

$GLOBALS['TCA']['tt_address']['columns']['client']['config']['importable'] = true;

$GLOBALS['TCA']['tt_address']['columns']['categories']['config']['importable'] = true;
$GLOBALS['TCA']['tt_address']['columns']['contact_types']['config']['importable'] = true;

$GLOBALS['TCA']['tt_address']['columns']['description']['config']['importable'] = true;


// ### SCHRITT 3: Das komplette Layout über 'showitem' neu definieren ###
// Hier wird die gesamte Struktur mit Tabs und Paletten aufgebaut.
// Alte 'addToAllTCAtypes' Aufrufe werden dadurch überflüssig.

$GLOBALS['TCA']['tt_address']['types'][0]['showitem'] = '
    --div--;Grunddaten,
        --palette--;Verknüpfungen;relations,
        --palette--;Name;name,
        --palette--;Firma/Organisation;organisation,
        --palette--;Kontaktdaten;contact,
        // image,

    --div--;Social & Interessen,
        tags,
        social_profiles,
        image,
        description,
        --palette--;ALT!! Social Media;social_backup,

    --div--;Anschrift,
        --palette--;LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.address;address,

    --div--;Mailings & Internes,
        --palette--;Einstellungen;mailingPalette,
        --palette--;Zuordnungen;categoriesPalette,
        --linebreak--,
        // description,
        --palette--;Duplikate;duplicatesPalette
';

$GLOBALS['TCA']['tt_address']['ctrl']['title'] = 'Kontakt';

