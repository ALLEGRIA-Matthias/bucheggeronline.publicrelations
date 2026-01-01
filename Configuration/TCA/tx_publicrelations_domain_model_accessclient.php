<?php
return [
    'ctrl' => [
        'title' => 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_accessclient',
        'label' => 'client',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'searchFields' => 'client,fe_users,fe_usergroups',
        'iconfile' => 'EXT:publicrelations/Resources/Public/Icons/access.svg'
    ],
    'types' => [
        '1' => [
            'showitem' => '
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
                client, fe_users, fe_groups,
            --div--;LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tabs.permissions,
                --palette--;;permissions,
            --div--;LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tabs.advanced_events,
                advanced_events,
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                hidden,
        '
        ],
    ],
    'palettes' => [
        'permissions' => [
            'showitem' => '
                view_clippings, view_media, view_news, view_events, --linebreak--,
                view_contacts, edit_contacts, delete_contacts
            ',
        ],
    ],
    'columns' => [
        'hidden' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.hidden',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'items' => [['', '']],
            ],
        ],
        'client' => [
            'label' => 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_accessclient.client',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_publicrelations_domain_model_client', // Annahme
                'minitems' => 1,
                'maxitems' => 1,
            ],
        ],
        'fe_users' => [
            'label' => 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_accessclient.fe_users',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'foreign_table' => 'fe_users',
                'MM' => 'tx_publicrelations_accessclient_feuser_mm',
                'size' => 10,
                'minitems' => 0,
                'maxitems' => 999,
            ],
        ],
        'fe_groups' => [
            'label' => 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_accessclient.fe_usergroups',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'foreign_table' => 'fe_groups',
                'MM' => 'tx_publicrelations_accessclient_fegroup_mm',
                'size' => 10,
                'minitems' => 0,
                'maxitems' => 999,
            ],
        ],
        'view_clippings' => [
            'label' => 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_accessclient.view_clippings',
            'config' => ['type' => 'check', 'renderType' => 'checkboxToggle']
        ],
        'view_contacts' => [
            'label' => 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_accessclient.view_contacts',
            'config' => ['type' => 'check', 'renderType' => 'checkboxToggle']
        ],
        'edit_contacts' => [
            'label' => 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_accessclient.edit_contacts',
            'config' => ['type' => 'check', 'renderType' => 'checkboxToggle']
        ],
        'delete_contacts' => [
            'label' => 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_accessclient.delete_contacts',
            'config' => ['type' => 'check', 'renderType' => 'checkboxToggle']
        ],
        'view_media' => [
            'label' => 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_accessclient.view_media',
            'config' => ['type' => 'check', 'renderType' => 'checkboxToggle']
        ],
        'view_news' => [
            'label' => 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_accessclient.view_news',
            'config' => ['type' => 'check', 'renderType' => 'checkboxToggle']
        ],
        'view_events' => [
            'label' => 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_accessclient.view_events',
            'config' => ['type' => 'check', 'renderType' => 'checkboxToggle']
        ],
        'advanced_events' => [
            'label' => 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_accessclient.advanced_events',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_publicrelations_domain_model_accessevent',
                'foreign_field' => 'access_client',
                'appearance' => [
                    'collapseAll' => 1,
                    'expandSingle' => 1,
                ],
            ],
        ],
    ],
];