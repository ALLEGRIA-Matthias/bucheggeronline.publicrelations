<?php
defined('TYPO3') || die();

$GLOBALS['TCA']['sys_category']['ctrl']['label'] = 'title';
$GLOBALS['TCA']['sys_category']['ctrl']['label_userFunc'] = \BucheggerOnline\Publicrelations\Backend\Labels::class . '->generateSysCategory';
$GLOBALS['TCA']['sys_category']['ctrl']['label_alt'] = 'client';
$GLOBALS['TCA']['sys_category']['ctrl']['label_alt_force'] = 1;

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

$llg = 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_general';
$ll = 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_syscategory';

$tmp_publicrelations_columns = [

    'icon' => [
        'exclude' => false,
        'label' => $ll . '.icon',
        'config' => [
            'type' => 'file',
            'maxitems' => 1,
            'appearance' => [
                'createNewRelationLinkTitle' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:images.addFileReference'
            ],
            'allowed' => $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'],
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
            ],
        ],
    ],
    'svg' => [
        'exclude' => false,
        'label' => $ll . '.svg',
        'description' => 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_syscategory.svg.description',
        'config' => [
            'type' => 'input',
            'size' => 30,
            'eval' => 'trim'
        ],
    ],
    'plural' => [
        'exclude' => false,
        'label' => $ll . '.plural',
        'config' => [
            'type' => 'input',
            'size' => 30,
            'eval' => 'trim'
        ],
    ],
    'css_class' => [
        'exclude' => false,
        'label' => $ll . '.css_class',
        'config' => [
            'type' => 'input',
            'size' => 30,
            'eval' => 'trim'
        ],
    ],
    'schedule' => [
        'exclude' => false,
        'label' => $ll . '.schedule',
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
    'theaterevent' => [
        'exclude' => false,
        'label' => $ll . '.theaterevent',
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

];

ExtensionManagementUtility::addTCAcolumns('sys_category', $tmp_publicrelations_columns);
ExtensionManagementUtility::addFieldsToPalette('sys_category', 'titlePalette', 'title, plural, client, --linebreak--, css_class, schedule, theaterevent');
ExtensionManagementUtility::addToAllTCAtypes('sys_category', '--palette--;Titelinfos;titlePalette', '', 'replace:title');
ExtensionManagementUtility::addFieldsToPalette('sys_category', 'iconPalette', 'icon, svg');
ExtensionManagementUtility::addToAllTCAtypes('sys_category', '--palette--;Icons;iconPalette', '', 'before:parent');
