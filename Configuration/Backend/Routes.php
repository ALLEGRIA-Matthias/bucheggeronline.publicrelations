<?php

return [
    'allegria_mailer' => [
        'path' => '/module/allegria/mailer',
        'target' => \TYPO3\CMS\Extbase\Core\Bootstrap::class . '::handleRequest',
        'defaults' => [
            'extensionName' => 'Publicrelations',
            'pluginName' => 'Mailer', // oder 'Mailer' – je nachdem, wie du es in ext_localconf.php / Plugin.php registriert hast
        ],
    ],
    'allegria_contacts' => [
        'path' => '/module/allegria/contacts',
        'target' => \TYPO3\CMS\Extbase\Core\Bootstrap::class . '::handleRequest',
        'defaults' => [
            'extensionName' => 'Publicrelations',
            'pluginName' => 'Contacts', // oder 'Mailer' – je nachdem, wie du es in ext_localconf.php / Plugin.php registriert hast
        ],
    ],
    'allegria_eventcenter' => [
        'path' => '/module/allegria/eventcenter',
        'target' => \TYPO3\CMS\Extbase\Core\Bootstrap::class . '::handleRequest',
        'defaults' => [
            'extensionName' => 'Publicrelations',
            'pluginName' => 'Eventcenter', // oder 'Mailer' – je nachdem, wie du es in ext_localconf.php / Plugin.php registriert hast
        ],
    ],
    'allegria_checkin' => [
        'path' => '/module/allegria/checkin',
        'target' => \TYPO3\CMS\Extbase\Core\Bootstrap::class . '::handleRequest',
        'defaults' => [
            'extensionName' => 'Publicrelations',
            'pluginName' => 'Checkin', // oder 'Mailer' – je nachdem, wie du es in ext_localconf.php / Plugin.php registriert hast
        ],
    ],
];