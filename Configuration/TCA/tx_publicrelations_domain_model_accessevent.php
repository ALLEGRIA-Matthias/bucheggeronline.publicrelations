<?php
return [
    'ctrl' => [
        'title' => 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_accessevent',
        'label' => 'event',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'delete' => 'deleted',
        'hideTable' => true, // Wichtig! Nur inline bearbeiten
    ],
    'types' => [
        '1' => ['showitem' => 'event, accesslevel, invitation_type'],
    ],
    'columns' => [
        'access_client' => [
            'config' => ['type' => 'passthrough'],
        ],
        'event' => [
            'label' => 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_accessevent.event',
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
            ],
        ],
        'accesslevel' => [
            'label' => 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_accessevent.accesslevel',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_accessevent.accesslevel.view', 'view'],
                    ['LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_accessevent.accesslevel.edit', 'edit'],
                    ['LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_accessevent.accesslevel.manage', 'manage'],
                ],
            ],
        ],
        'invitation_type' => [
            'label' => 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_accessevent.invitation_type',
            'config' => [
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'tx_publicrelations_domain_model_invitation',
                'foreign_table' => 'tx_publicrelations_domain_model_invitation',
                'size' => 1,
                'minitems' => 1,
                'maxitems' => 1,
                'hideSuggest' => 0,
                'multiple' => 0,
                'default' => 0,
            ],
        ],
    ],
];