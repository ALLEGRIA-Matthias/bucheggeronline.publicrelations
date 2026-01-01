<?php
defined('TYPO3') or die();

return [
    'ctrl' => [
        'title' => 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_contactgroup',
        'label' => 'name',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'searchFields' => 'name,description',
        'iconfile' => 'EXT:publicrelations/Resources/Public/Icons/tx_publicrelations_domain_model_contactgroup.svg'
    ],
    'types' => [
        '0' => ['showitem' => 'type, name, description, logo, parent, contacts'],
    ],
    'columns' => [
        'type' => [
            'label' => 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_socialprofile.type',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    // Leerer Eintrag, damit nichts ausgewählt sein muss
                    ['LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_contactgroup.type.select', ''],

                    // Medientypen
                    ['LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_contactgroup.type.print', 'print'],
                    ['LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_contactgroup.type.tv', 'tv'],
                    ['LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_contactgroup.type.radio', 'radio'],
                    ['LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_contactgroup.type.show', 'show'],
                    ['LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_contactgroup.type.podcast', 'podcast'],
                    ['LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_contactgroup.type.blog', 'blog'],

                    // Organisationstypen
                    ['LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_contactgroup.type.corporation', 'corporation'],
                    ['LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_contactgroup.type.agency', 'agency'],
                    ['LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_contactgroup.type.marketing', 'marketing'],

                    // Fallback
                    ['LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_contactgroup.type.other', 'other'],
                ],
                'default' => '',
            ],
        ],
        'name' => [
            'label' => 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_contactgroup.name',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim,required',
            ],
        ],
        'description' => [
            'label' => 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_contactgroup.description',
            'config' => [
                'type' => 'text',
                'enableRichtext' => true,
                'cols' => 40,
                'rows' => 5,
                'eval' => 'trim'
            ],
        ],
        'logo' => [
            'label' => 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_contactgroup.logo',
            'config' => [
                'type' => 'file',
                'maxitems' => 1,
                'allowed' => 'common-image-types'
            ],
        ],
        'parent' => [
            'label' => 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_contactgroup.parent',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectTree',
                'foreign_table' => 'tx_publicrelations_domain_model_contactgroup',
                'treeConfig' => [
                    'parentField' => 'parent',
                    'appearance' => [
                        'expandAll' => true,
                        'showHeader' => true,
                    ],
                ],
                'maxitems' => 1,
            ],
        ],
        'contacts' => [
            'label' => 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_contactgroup.contacts',
            'config' => [
                'type' => 'group',
                'foreign_table' => 'tt_address',
                'allowed' => 'tt_address',
                'MM' => 'tx_publicrelations_contactgroup_ttaddress_mm',
                'maxitems' => 9999,
                'multiple' => 0,
            ],
        ],
    ],
];