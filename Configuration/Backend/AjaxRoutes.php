<?php
return [
    'publicrelations_contactsearch' => [
        'path' => '/publicrelations/contact-search',
        'target' => \BucheggerOnline\Publicrelations\Backend\Ajax\ContactAjaxController::class . '::findContacts',
    ],
    'publicrelations_contactindex' => [
        'path' => '/accontacts/contact-index/',
        'target' => \BucheggerOnline\Publicrelations\Backend\Ajax\ContactAjaxController::class . '::newSearchContacts',
    ],
    'publicrelations_contactemailvalidation' => [
        'path' => '/accontacts/contact-email-validation/',
        'target' => \BucheggerOnline\Publicrelations\Backend\Ajax\ContactAjaxController::class . '::validateEmailAction',
    ],
    'publicrelations_contactimportvalidation' => [
        'path' => '/accontacts/contact-import-validation/',
        'target' => \BucheggerOnline\Publicrelations\Backend\Ajax\ContactAjaxController::class . '::validateImportAction',
    ],
    'publicrelations_contactimportexecute' => [
        'path' => '/accontacts/contact-import-execute/',
        'target' => \BucheggerOnline\Publicrelations\Backend\Ajax\ContactAjaxController::class . '::executeImportAction',
    ],
    'publicrelations_eventsearch' => [
        'path' => '/publicrelations/event-search',
        'target' => \BucheggerOnline\Publicrelations\Backend\Ajax\EventAjaxController::class . '::findEvents',
    ],
    'publicrelations_campaignsearch' => [
        'path' => '/publicrelations/campaign-search',
        'target' => \BucheggerOnline\Publicrelations\Backend\Ajax\CampaignAjaxController::class . '::searchByClient',
    ],
    'publicrelations_checkinstatus' => [
        'path' => '/publicrelations/checkin-status',
        'target' => \BucheggerOnline\Publicrelations\Backend\Ajax\CheckinAjaxController::class . '::refreshEventStatusAction',
    ],
    'publicrelations_accreditationslist' => [
        'path' => '/publicrelations/accreditations-list',
        'target' => \BucheggerOnline\Publicrelations\Backend\Ajax\CheckinAjaxController::class . '::listAccreditationsAction',
    ],
    'publicrelations_accreditationdetails' => [
        'path' => '/publicrelations/accreditation-details',
        'target' => \BucheggerOnline\Publicrelations\Backend\Ajax\CheckinAjaxController::class . '::showAccreditationDetailsAction',
    ],
    'publicrelations_accreditationcheckin' => [
        'path' => '/publicrelations/accreditation-checkin',
        'target' => \BucheggerOnline\Publicrelations\Backend\Ajax\CheckinAjaxController::class . '::processCheckinAction',
    ],
    'publicrelations_accreditationreleaselock' => [
        'path' => '/publicrelations/accreditation-releaselock',
        'target' => \BucheggerOnline\Publicrelations\Backend\Ajax\CheckinAjaxController::class . '::releaseAccreditationLockAction',
    ],
    'publicrelations_accreditation_test' => [
        'path' => '/publicrelations/accreditation/test',
        'target' => \BucheggerOnline\Publicrelations\Controller\AccreditationController::class . '::sendTestMailAction',
    ],
    'publicrelations_categoriesselect' => [
        'path' => '/publicrelations/categories-select/',
        'target' => \BucheggerOnline\Publicrelations\Backend\Ajax\CategoriesAjaxController::class . '::selectAction',
    ],
    'publicrelations_clippingroutes_list' => [
        'path' => '/publicrelations/clippingroutes/list/',
        'target' => \BucheggerOnline\Publicrelations\Backend\Ajax\ClippingRouteAjaxController::class . '::listClippingRoutes',
    ],
    'publicrelations_clippingroutes_send' => [
        'path' => '/publicrelations/clippingroutes/send',
        'target' => \BucheggerOnline\Publicrelations\Backend\Ajax\ClippingRouteAjaxController::class . '::sendClippingRoute',
    ],
    'publicrelations_reports_list' => [
        'path' => '/publicrelations/reports/list/',
        'target' => \BucheggerOnline\Publicrelations\Backend\Ajax\ReportAjaxController::class . '::listReports',
    ]
];