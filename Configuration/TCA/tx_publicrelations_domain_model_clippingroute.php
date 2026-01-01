<?php
defined('TYPO3') or die();

return [
    'ctrl' => [
        'title' => 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_clippingroute',
        'label' => 'keyword',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'delete' => 'deleted',
        'searchFields' => 'keyword,to_emails,cc_emails,bcc_emails',
        'iconfile' => 'EXT:publicrelations/Resources/Public/Icons/tx_publicrelations_domain_model_clippingroute.svg', // Du musst evtl. ein Icon hier ablegen
    ],
    'types' => [
        '1' => [
            'showitem' => '
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
                    keyword,
                    --palette--;;relations_palette,
                    --palette--;LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_clippingroute.palette.recipients;email_palette,
                    drive
            ',
        ],
    ],
    'palettes' => [
        'relations_palette' => [
            'showitem' => 'client, project',
        ],
        'email_palette' => [
            'showitem' => 'to_emails, send_immediate, --linebreak--, cc_emails, bcc_emails',
        ],
    ],
    'columns' => [
        'keyword' => [
            'exclude' => false,
            'label' => 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_clippingroute.keyword',
            'config' => [
                'type' => 'input',
                'size' => 40,
                'eval' => 'trim,required,unique',
            ],
        ],
        'client' => [
            'exclude' => false,
            'label' => 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_clippingroute.client',
            'config' => [
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'tx_publicrelations_domain_model_client',
                'minitems' => 1,
                'maxitems' => 1,
                'size' => 1,
                'default' => 0
            ],
        ],
        'project' => [
            'exclude' => false,
            'label' => 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_clippingroute.project',
            'config' => [
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'tx_publicrelations_domain_model_campaign',
                'minitems' => 0,
                'maxitems' => 1,
                'size' => 1,
                'default' => 0
            ],
        ],
        'drive' => [
            'exclude' => false,
            'label' => 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_clippingroute.drive',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputLink',
                'size' => 50,
                'eval' => 'trim',
                'softref' => 'typolink' // Ermöglicht die Verwendung des Link-Wizards
            ],
        ],
        'send_immediate' => [
            'exclude' => false,
            'label' => 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_clippingroute.send_immediate',
            'config' => [
                'type' => 'check',
                'default' => 1, // Standardmäßig aktiv, wie in deiner CSV
            ],
        ],
        'to_emails' => [
            'exclude' => false,
            'label' => 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_clippingroute.to_emails',
            'config' => [
                'type' => 'text',
                'rows' => 5,
                'cols' => 40,
                'eval' => 'trim',
            ],
        ],
        'cc_emails' => [
            'exclude' => false,
            'label' => 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_clippingroute.cc_emails',
            'config' => [
                'type' => 'text',
                'rows' => 5,
                'cols' => 40,
                'eval' => 'trim',
            ],
        ],
        'bcc_emails' => [
            'exclude' => false,
            'label' => 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_clippingroute.bcc_emails',
            'config' => [
                'type' => 'text',
                'rows' => 5,
                'cols' => 40,
                'eval' => 'trim',
            ],
        ],
    ],
];