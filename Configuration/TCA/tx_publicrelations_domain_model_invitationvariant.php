<?php
defined('TYPO3') or die();

return [
    'ctrl' => [
        'title' => 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_invitationvariant',
        'label' => 'code',
        'label_alt' => 'subject',
        'label_alt_force' => 1,
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'delete' => 'deleted',
        'searchFields' => 'code,subject,html,from_name,reply_email,reply_name,preheader',
        'iconfile' => 'EXT:publicrelations/Resources/Public/Icons/tx_publicrelations_domain_model_invitationvariant.svg',
        'hideTable' => true,
        'versioningWS' => false,
        'origUid' => 't3_origuid',
    ],
    'types' => [
        '1' => [
            'showitem' => '
                --palette--;;base,
                --palette--;LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:palette.options;options,
                --palette--;LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:palette.sender;sender
            '
        ],
    ],
    'palettes' => [
        'base' => ['showitem' => 'code, html'],
        'options' => ['showitem' => 'subject, preheader, --linebreak--, attachments'],
        'sender' => ['showitem' => 'from_name, reply_name, reply_email'],
    ],
    'columns' => [
        'code' => [
            'exclude' => false,
            'label' => 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_invitationvariant.code',
            'config' => [
                'type' => 'input',
                'size' => 20,
                'eval' => 'trim,lower',
                'required' => true,
                'valuePicker' => [
                    'items' => [
                        [
                            'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_invitationvariant.code.invite',
                            'invite',
                        ],
                        [
                            'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_invitationvariant.code.remind',
                            'remind',
                        ],
                        [
                            'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_invitationvariant.code.push',
                            'push',
                        ],
                        [
                            'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_invitationvariant.code.approve',
                            'approve',
                        ],
                        [
                            'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_invitationvariant.code.reject',
                            'reject',
                        ],
                        [
                            'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_invitationvariant.code.waiting',
                            'waiting',
                        ],
                        [
                            'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_invitationvariant.code.pending',
                            'pending',
                        ],
                        [
                            'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_invitationvariant.code.approve_after_waiting',
                            'approve_after_waiting',
                        ],
                        [
                            'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_invitationvariant.code.reject_after_waiting',
                            'reject_after_waiting',
                        ],
                    ],
                ],
                // 'valuePicker' => [
                //     'items' => [
                //         [
                //             'label' => 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_invitationvariant.code.invite',
                //             'value' => 'invite',
                //         ],
                //         [
                //             'label' => 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_invitationvariant.code.remind',
                //             'value' => 'remind',
                //         ],
                //         [
                //             'label' => 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_invitationvariant.code.push',
                //             'value' => 'push',
                //         ],
                //         [
                //             'label' => 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_invitationvariant.code.approve',
                //             'value' => 'approve',
                //         ],
                //         [
                //             'label' => 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_invitationvariant.code.reject',
                //             'value' => 'reject',
                //         ],
                //         [
                //             'label' => 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_invitationvariant.code.waiting',
                //             'value' => 'waiting',
                //         ],
                //         [
                //             'label' => 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_invitationvariant.code.pending',
                //             'value' => 'pending',
                //         ],
                //         [
                //             'label' => 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_invitationvariant.code.approve_after_waiting',
                //             'value' => 'approve_after_waiting',
                //         ],
                //         [
                //             'label' => 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_invitationvariant.code.reject_after_waiting',
                //             'value' => 'reject_after_waiting',
                //         ],
                //     ],
                // ],
                'default' => '',
            ],
        ],
        'html' => [
            'exclude' => false,
            'displayCond' => 'USER:BucheggerOnline\\Publicrelations\\Backend\\Tca\\InvitationTypeConditionMatcher->showHtmlField',
            'label' => 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_invitationvariant.html',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputLink',
                'required' => true,
                'allowedTypes' => ['file'],
                'appearance' => [
                    'allowedOptions' => [],
                    'allowedFileExtensions' => ['html'],
                ],
                'size' => 50,
                'eval' => 'trim',
                'softref' => 'file',
            ],
        ],
        'subject' => [
            'exclude' => false,
            'label' => 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_invitationvariant.subject',
            'config' => [
                'type' => 'input',
                'size' => 50,
                'eval' => 'trim',
                'required' => false
            ],
        ],
        'from_name' => [
            'exclude' => false,
            'label' => 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_invitationvariant.from_name',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
            ],
        ],
        'reply_email' => [
            'exclude' => false,
            'label' => 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_invitationvariant.reply_email',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim,email',
            ],
        ],
        'reply_name' => [
            'exclude' => false,
            'label' => 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_invitationvariant.reply_name',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
            ],
        ],
        'preheader' => [
            'exclude' => false,
            'label' => 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_invitationvariant.preheader',
            'config' => [
                'type' => 'text',
                'cols' => 40,
                'rows' => 3,
                'eval' => 'trim',
                'max' => 70,
                'required' => false
            ],
        ],
        'attachments' => [
            'exclude' => false,
            'label' => 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_invitationvariant.attachments',
            'config' => [
                'type' => 'file',
                'maxitems' => 5,
                'max_upload_size' => 10240, // 10 MB in KB
                'appearance' => [
                    'createNewRelationLinkTitle' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:media.addFileReference',
                    'collapseAll' => 1,
                    'levelLinksPosition' => 'top',
                    'fileByUrlAllowed' => false
                ],
                // Optional: Dateitypen einschränken
                // 'allowed' => ['pdf', 'jpg', 'png'],
            ],
        ],
        'invitation' => [
            'config' => [
                'type' => 'passthrough',
                'foreign_table' => 'tx_publicrelations_domain_model_invitation',
                'foreign_field' => 'uid',
            ],
        ],
    ],
];