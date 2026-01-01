<?php

use BucheggerOnline\Publicrelations\Controller\MailingController;
use BucheggerOnline\Publicrelations\Controller\MailController;
use BucheggerOnline\Publicrelations\Controller\ContactController;
use BucheggerOnline\Publicrelations\Controller\PressroomController;
use BucheggerOnline\Publicrelations\Controller\ClientController;
use BucheggerOnline\Publicrelations\Controller\CampaignController;
use BucheggerOnline\Publicrelations\Controller\NewsController;
use BucheggerOnline\Publicrelations\Controller\EventController;
use BucheggerOnline\Publicrelations\Controller\AccreditationController;
use BucheggerOnline\Publicrelations\Controller\CheckinController;
use BucheggerOnline\Publicrelations\Controller\ReportController;

return [
    'allegria' => [
        'labels' => 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_allegria.xlf',
        'iconIdentifier' => 'modulegroup-allegria',
        'position' => ['top'],
        'access' => 'user',
    ],
    'allegria_contacts' => [
        'parent' => 'allegria',
        'access' => 'user',
        'workspaces' => 'live',
        'path' => '/module/allegria/contacts',
        'labels' => 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_contacts.xlf',
        'iconIdentifier' => 'module-allegria-contacts',
        'extensionName' => 'Publicrelations',
        'controllerActions' => [
            ContactController::class => [
                'index',
                'new',
                'create',
                'list',
                'pressList',
                'promiList',
                'clientList',
                'mailingList',
                'show',
                'edit',
                'import',
                'uploadAndMap',
                'importer',
                'imported',
                'finisher',
                'export',
                'moveCategories'
            ],
        ],
    ],
    'allegria_eventcenter' => [
        'parent' => 'allegria',
        'access' => 'user',
        'workspaces' => 'live',
        'path' => '/module/allegria/eventcenter',
        'labels' => 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_eventcenter.xlf',
        'iconIdentifier' => 'module-allegria-eventcenter',
        'extensionName' => 'Publicrelations',
        'controllerActions' => [
            PressroomController::class => ['overview', 'test'],
            ClientController::class => ['backendList'],
            CampaignController::class => ['list'],
            NewsController::class => ['list'],
            EventController::class => ['list', 'show', 'printLabels', 'new', 'create', 'edit', 'editCollection', 'update', 'updateCollection', 'delete', 'deleteCollection', 'postponeCollection', 'undoPostpone', 'archive', 'export'],
            AccreditationController::class => ['list', 'show', 'print', 'preview', 'new', 'newWizzard', 'invitationManager', 'invitationManagerCategories', 'invitationManagerSummary', 'invitationPreview', 'statusUpdate', 'create', 'editCollection', 'updateCollection', 'approve', 'edit', 'update', 'delete', 'checkDuplicates', 'removeFromGroup', 'swapMaster', 'mailPreview'],
        ],
    ],
    'allegria_checkin' => [
        'parent' => 'allegria',
        'access' => 'user',
        'workspaces' => 'live',
        'path' => '/module/allegria/checkin',
        'labels' => 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_checkin.xlf',
        'iconIdentifier' => 'module-allegria-checkin',
        'extensionName' => 'Publicrelations',
        'controllerActions' => [
            CheckinController::class => ['list', 'show', 'checkin', 'showDetailsForModal'],
            AccreditationController::class => ['checkin'],
        ],
    ],
    'allegria_reports' => [
        'parent' => 'allegria',
        'access' => 'user',
        'workspaces' => 'live',
        'path' => '/module/allegria/reports',
        'labels' => 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_reports.xlf',
        'iconIdentifier' => 'module-allegria-reports',
        'extensionName' => 'Publicrelations',
        'controllerActions' => [
            ReportController::class => ['clippingRoutes', 'reports'],
        ],
    ],
    'allegria_reports_clippingroutes' => [
        'parent' => 'allegria_reports',
        'access' => 'user',
        'visible' => false,
        'path' => '/module/allegria/reports/clippingroutes',
        'extensionName' => 'Publicrelations',
        'controllerActions' => [
            ReportController::class => ['clippingRoutes'],
        ],
    ],
    'allegria_reports_list' => [
        'parent' => 'allegria_reports',
        'access' => 'user',
        'visible' => false,
        'path' => '/module/allegria/reports/list',
        'extensionName' => 'Publicrelations',
        'controllerActions' => [
            ReportController::class => ['reports'],
        ],
    ],
    'allegria_mailer' => [
        'parent' => 'allegria',
        'access' => 'user',
        'workspaces' => 'live',
        'path' => '/module/allegria/mailer',
        'navigationComponent' => '',
        'labels' => 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_mailer.xlf',
        'iconIdentifier' => 'module-allegria-mailer',
        'extensionName' => 'Publicrelations',
        'controllerActions' => [
            MailingController::class => [
                'archive',
                'list',
                'show',
                'preview',
                // 'receiverList',
                // 'receiverManager',
                // 'receiverManagerCategories',
                // 'receiverManagerSummary',
                // 'createMails',
                // 'sendMails',
                // 'copyMailing',
                // 'delete',
                // 'deleteMails',
                // 'statusUpdate'
            ],
            MailController::class => ['list', 'show', 'delete'],
        ],
    ],
];
