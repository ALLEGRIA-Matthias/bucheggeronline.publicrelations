<?php
defined('TYPO3') or die();

return [
    'ctrl' => [
        'title' => 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_socialprofile',
        'label' => 'handle',
        'label_alt' => 'type',
        'label_alt_force' => 1,
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'delete' => 'deleted',
        'hideTable' => true, // Wichtig für IRRE
        'sortby' => 'sorting',
        'versioningWS' => true,
        'origUid' => 't3_origuid',
        'searchFields' => 'type,handle',
        'iconfile' => 'EXT:publicrelations/Resources/Public/Icons/tx_publicrelations_domain_model_socialprofile.svg'
    ],
    'types' => [
        '0' => [
            'showitem' => '
                --div--;Grunddaten,
                    --palette--;;follower_details,
                --div--;Notizen,
                    notes,
            ',
        ],
    ],
    'palettes' => [
        'follower_details' => [
            'showitem' => 'type, handle, follower, follower_updated',
        ],
    ],
    'columns' => [
        'type' => [
            'label' => 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_socialprofile.type',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['Facebook', 'facebook'],
                    ['Instagram', 'instagram'],
                    ['Threads', 'threads'],
                    ['TikTok', 'tiktok'],
                    ['Snapchat', 'snapchat'],
                    ['YouTube', 'youtube'],
                    ['LinkedIn', 'linkedin'],
                    ['Xing', 'xing'],
                    ['X (Twitter)', 'x'],
                ],
                'default' => 'instagram',
            ],
        ],
        'handle' => [
            'label' => 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_socialprofile.handle',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim,required',
            ],
        ],
        'follower' => [
            'label' => 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_socialprofile.follower',
            'config' => [
                'type' => 'input',
                'size' => 10,
                'eval' => 'integer',
                'default' => 0
            ],
        ],
        'follower_updated' => [
            'label' => 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_socialprofile.follower_updated',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputDateTime',
                'eval' => 'datetime',
                'default' => 0,
            ],
        ],
        'notes' => [
            'label' => 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_contactgroup.notes',
            'config' => [
                'type' => 'text',
                'enableRichtext' => true,
                'cols' => 40,
                'rows' => 5,
                'eval' => 'trim'
            ],
        ],
        'contact' => [
            'config' => [
                'type' => 'passthrough',
                'foreign_table' => 'tt_address',
            ],
        ],
    ],
];