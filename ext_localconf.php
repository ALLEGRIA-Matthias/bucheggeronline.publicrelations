<?php


use TYPO3\CMS\Extbase\Utility\ExtensionUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
// use BucheggerOnline\Publicrelations\Controller\{ClientController, AccreditationController, CampaignController, NewsController, PresscenterController, SlideController, EventController, SearchController, MailController};
use BucheggerOnline\Publicrelations\Controller\ClientController;
use BucheggerOnline\Publicrelations\Controller\AccreditationController;
use BucheggerOnline\Publicrelations\Controller\CampaignController;
use BucheggerOnline\Publicrelations\Controller\NewsController;
use BucheggerOnline\Publicrelations\Controller\PresscenterController;
use BucheggerOnline\Publicrelations\Controller\SlideController;
use BucheggerOnline\Publicrelations\Controller\EventController;
use BucheggerOnline\Publicrelations\Controller\SearchController;
use BucheggerOnline\Publicrelations\Controller\MailController;
use BucheggerOnline\Publicrelations\Controller\PressecenterController;

use Allegria\AcMailer\Domain\Model\Mailing;
use BucheggerOnline\Publicrelations\Domain\Model\AcMailerMailing;

defined('TYPO3') || die();

// Für TYPO3 v8.4 kein Composer Autoloading erforderlich
if (!\TYPO3\CMS\Core\Core\Environment::isComposerMode()) {
    require_once \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('publicrelations') . '/Resources/Private/Php/vendor/autoload.php';
}

$boot = static function (): void {

    // Immer das Setup‐TypoScript aus deiner Datei laden
    ExtensionManagementUtility::addTypoScriptSetup(
        '<INCLUDE_TYPOSCRIPT: source="FILE:EXT:publicrelations/Configuration/TypoScript/setup.typoscript">'
    );

    ExtensionUtility::configurePlugin(
        'Publicrelations',
        'ClientList',
        [
            ClientController::class => 'list',
            AccreditationController::class => 'create',
        ],
        [
            AccreditationController::class => 'create',
        ],
        ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
    );

    ExtensionUtility::configurePlugin(
        'Publicrelations',
        'ClientReferences',
        [
            ClientController::class => 'references',
        ],
        [
        ],
        ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
    );

    ExtensionUtility::configurePlugin(
        'Publicrelations',
        'ClientShow',
        [
            ClientController::class => 'show',
            CampaignController::class => 'show',
            NewsController::class => 'show',
            AccreditationController::class => 'create',
        ],
        [
            NewsController::class => 'show',
            AccreditationController::class => 'create',
        ],
        ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
    );

    ExtensionUtility::configurePlugin(
        'Publicrelations',
        'AccreditationForm',
        [
            AccreditationController::class => 'report,updateFrontend',
        ],
        [
            AccreditationController::class => 'report,updateFrontend',
        ],
        ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
    );

    ExtensionUtility::configurePlugin(
        'Publicrelations',
        'PresscenterOverview',
        [
            PresscenterController::class => 'overview',
            AccreditationController::class => 'expressApprove',
        ],
        [
            PresscenterController::class => 'overview',
            AccreditationController::class => 'expressApprove',
        ],
        ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
    );

    ExtensionUtility::configurePlugin(
        'Publicrelations',
        'PresscenterHome',
        [
            PresscenterController::class => 'home',
        ],
        [
            PresscenterController::class => 'home',
        ],
        ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
    );

    ExtensionUtility::configurePlugin(
        'Publicrelations',
        'SlideList',
        [
            SlideController::class => 'list',
        ],
        [
        ],
        ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
    );

    ExtensionUtility::configurePlugin(
        'Publicrelations',
        'EventIcal',
        [
            EventController::class => 'iCal',
        ],
        [
            EventController::class => 'iCal',
        ],
        ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
    );

    ExtensionUtility::configurePlugin(
        'Publicrelations',
        'SearchResult',
        [
            SearchController::class => 'result',
        ],
        [
            SearchController::class => 'result',
        ],
        ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
    );

    ExtensionUtility::configurePlugin(
        'Publicrelations',
        'MailView',
        [
            MailController::class => 'view',
        ],
        [
            MailController::class => 'view',
        ],
        ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
    );

    ExtensionUtility::configurePlugin(
        'Publicrelations',
        'EventPrintLabels',
        [
            EventController::class => 'printLabels',
        ],
        [
            EventController::class => 'printLabels',
        ],
        ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
    );

    ExtensionUtility::configurePlugin(
        'Publicrelations',
        'PressecenterMenu', // Plugin-Name
        [
            PressecenterController::class => 'menu', // erlaubte Actions
        ],
        // Nicht-cachebare Actions (WICHTIG für personalisierte Inhalte!)
        [
            PressecenterController::class => 'menu',
        ]
    );

    ExtensionUtility::configurePlugin(
        'Publicrelations',
        'PressecenterUserMenu', // Eindeutiger Name für das neue Plugin
        [
                // Erlaubte Action: Controller => 'action'
            PressecenterController::class => 'userMenu',
        ],
        // Nicht-cachebare Actions
        [
            PressecenterController::class => 'userMenu',
        ]
    );

    ExtensionUtility::configurePlugin(
        'Publicrelations',
        'PressecenterMyData', // Plugin-Name
        [
            PressecenterController::class => 'dashboard,myClippings,myContacts,myEvents,myAccreditations,myNews,myMedia', // erlaubte Actions
        ],
        // Nicht-cachebare Actions (WICHTIG für personalisierte Inhalte!)
        [
            PressecenterController::class => 'dashboard,myClippings,myContacts,myEvents,myAccreditations,myNews,myMedia',
        ]
    );

    ExtensionUtility::configurePlugin(
        'Publicrelations',
        'Presscenterajax',
        [
            \BucheggerOnline\Publicrelations\Controller\Pressecenter\AjaxController::class => '
                listContacts,
                editContact,
                checkContact,
                updateContact,
                createContact,
                listEvents,
                listAccreditations,
                editAccreditation,
                updateAccreditation,
                checkAccreditation,
                createAccreditation,
                sendAccreditationMail
            '
        ],
        // non-cacheable actions
        [
            \BucheggerOnline\Publicrelations\Controller\Pressecenter\AjaxController::class => '
                listContacts,
                editContact,
                checkContact,
                updateContact,
                createContact,
                listEvents,
                listAccreditations,
                editAccreditation,
                updateAccreditation,
                checkAccreditation,
                createAccreditation,
                sendAccreditationMail
            '
        ]
    );


    $icons = [
        'pagetree-folder-publicrelations' => 'page-tree-module.svg',
        'publicrelations-plugin-pressroom' => 'user_plugin_pressroom.svg',
        'publicrelations-mod-eventcenter' => 'user_mod_eventcenter.svg'
    ];
    $iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconRegistry::class);
    foreach ($icons as $identifier => $path) {
        $iconRegistry->registerIcon(
            $identifier,
            \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
            ['source' => 'EXT:publicrelations/Resources/Public/Icons/' . $path]
        );
    };

    ExtensionManagementUtility::addTypoScriptSetup(trim('
        config.pageTitleProviders {
            publicrelations {
                provider = BucheggerOnline\Publicrelations\Utility\TitleProvider
                before = record
                after = altPageTitle
            }
        }
    '));

    $GLOBALS['TYPO3_CONF_VARS']['MAIL']['layoutRootPaths'][300] = 'EXT:publicrelations/Resources/Private/Layouts';
    $GLOBALS['TYPO3_CONF_VARS']['MAIL']['templateRootPaths'][300] = 'EXT:publicrelations/Resources/Private/Templates/Email';
    $GLOBALS['TYPO3_CONF_VARS']['MAIL']['partialRootPaths'][300] = 'EXT:publicrelations/Resources/Private/Partials';

    $GLOBALS['TYPO3_CONF_VARS']['RTE']['Presets']['default'] = 'EXT:publicrelations/Configuration/RTE/Default.yaml';
    $GLOBALS['TYPO3_CONF_VARS']['RTE']['Presets']['email'] = 'EXT:publicrelations/Configuration/RTE/Email.yaml';
    $GLOBALS['TYPO3_CONF_VARS']['RTE']['Presets']['min'] = 'EXT:publicrelations/Configuration/RTE/Min.yaml';
    $GLOBALS['TYPO3_CONF_VARS']['RTE']['Presets']['mini'] = 'EXT:publicrelations/Configuration/RTE/Mini.yaml';
    $GLOBALS['TYPO3_CONF_VARS']['RTE']['Presets']['mailpreview'] = 'EXT:publicrelations/Configuration/RTE/MailPreview.yaml';

    $GLOBALS['TYPO3_CONF_VARS']['SYS']['fluid']['namespaces']['mb'] = ['BucheggerOnline\\Publicrelations\\ViewHelpers'];
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['fluid']['namespaces']['be'] = ['TYPO3\CMS\Backend\ViewHelpers'];

    // Registriert unsere neue Cleanup-Task im TYPO3 Scheduler
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][\BucheggerOnline\Publicrelations\Task\CleanupTask::class] = [
        'extension' => 'publicrelations',
        'title' => 'Kontakte | 1. Typisierung durchführen',
        'description' => 'Setzt den Kontakttyp basierend auf Kategorien/PIDs',
        'additionalFields' => \BucheggerOnline\Publicrelations\Task\CleanupTaskAdditionalFieldProvider::class
    ];

    // Registriert die Task zum Splitten von Kundenkontakten
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][\BucheggerOnline\Publicrelations\Task\SplitContactsTask::class] = [
        'extension' => 'publicrelations',
        'title' => 'Kontakte | 2. Kontakte bereinigen & aufsplitten',
        'description' => 'Kopiert Kontakte mit gemischten (intern/extern) und splittet die Kategorien.',
        'additionalFields' => \BucheggerOnline\Publicrelations\Task\SplitContactsTaskAdditionalFieldProvider::class
    ];

    // Registriert die Task zum Splitten von Kundenkontakten
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][\BucheggerOnline\Publicrelations\Task\AssignClientContactsTask::class] = [
        'extension' => 'publicrelations',
        'title' => 'Kontakte | 3. Kundenkontakte bereinigen',
        'description' => 'Splittet Kontakte die zu unterschiedlichen Kunden gehören.',
        'additionalFields' => \BucheggerOnline\Publicrelations\Task\AssignClientContactsTaskAdditionalFieldProvider::class
    ];

    // Registriert die Task zum Splitten von Kundenkontakten
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][\BucheggerOnline\Publicrelations\Task\DuplicateContactsTask::class] = [
        'extension' => 'publicrelations',
        'title' => 'Kontakte | 4. Duplikate finden (intern/pro Kunde)',
        'description' => 'Erstellt eine CSV im fileadmin mit E-Mail-Duplikaten. Trennt dabei zwischen internen Kontakten (kein Kunde) und Kontakten pro Kunde.',
        'additionalFields' => \BucheggerOnline\Publicrelations\Task\DuplicateContactsTaskAdditionalFieldProvider::class
    ];

    // RSSImportTask
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][\BucheggerOnline\Publicrelations\Task\ImportRssTask::class] = [
        'extension' => 'publicrelations',
        'title' => 'RSS Clipping Import',
        'description' => 'Importiert den APA-Feed und erstellt Report-Einträge.',
        'additionalFields' => \BucheggerOnline\Publicrelations\Task\ImportRssTaskAdditionalFieldProvider::class
    ];

    $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\Allegria\AcMailer\Domain\Model\Mailing::class] = [
        'className' => \BucheggerOnline\Publicrelations\Domain\Model\AcMailerMailing::class,
    ];

    $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\Allegria\AcMailer\Domain\Model\Content::class] = [
        'className' => \BucheggerOnline\Publicrelations\Domain\Model\AcMailerContent::class,
    ];
};

$boot();
unset($boot);