<?php
namespace BucheggerOnline\Publicrelations\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Http\UploadedFile;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Pagination\SimplePagination;
use TYPO3\CMS\Core\Pagination\ArrayPaginator;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Extbase\Pagination\QueryResultPaginator;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder;

use BucheggerOnline\Publicrelations\Domain\Model\Dto\EmConfiguration;

use BucheggerOnline\Publicrelations\Utility\GeneralFunctions;
use BucheggerOnline\Publicrelations\Utility\LogGenerator;
use BucheggerOnline\Publicrelations\Utility\ContactHelper;

use BucheggerOnline\Publicrelations\Domain\Repository\SysCategoryRepository;
use BucheggerOnline\Publicrelations\Domain\Model\SysCategory;

use BucheggerOnline\Publicrelations\Domain\Repository\TtAddressRepository;
use BucheggerOnline\Publicrelations\Domain\Model\TtAddress;

use BucheggerOnline\Publicrelations\Domain\Repository\AccreditationRepository;
use BucheggerOnline\Publicrelations\Domain\Model\Accreditation;

use BucheggerOnline\Publicrelations\Domain\Repository\MailRepository;
use BucheggerOnline\Publicrelations\Domain\Model\Mail;

use BucheggerOnline\Publicrelations\Domain\Repository\ClientRepository;

/***
 *
 * This file is part of the "Public Relations" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2020 Matthias Buchegger <matthias@buchegger.online>, Multimediaagentur Matthias Buchegger
 *
 ***/
/**
 * ContactController
 */
class ContactController extends AbstractPublicrelationsController
{
  public function __construct(
    private readonly PersistenceManager $persistenceManager,
    private readonly TtAddressRepository $ttAddressRepository,
    private readonly AccreditationRepository $accreditationRepository,
    private readonly MailRepository $mailRepository,
    private readonly ClientRepository $clientRepository,
    private readonly SysCategoryRepository $sysCategoryRepository,
    private readonly DataMapper $dataMapper,
    private readonly EmConfiguration $emConfiguration,
    private readonly GeneralFunctions $generalFunctions
  ) {
  }

  public function indexAction(): ResponseInterface
  {
    // 1. Assets laden
    $this->addCustomAsset('https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css?v=1.0', 'css');
    $this->addCustomAsset('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css', 'css');
    $this->addCustomAsset('EXT:ac_base/Resources/Public/Libs/grid-js/gridjs.css', 'css');

    $this->addCustomAsset('@allegria/publicrelations/contact-main.js', 'module');
    $this->addCustomAsset('@ac/libs/grid-js/gridjs.js', 'module');


    $this->setModuleTitle('Kontakt-Datenbank');


    // $this->view->assignMultiple([
    //   'test' => 'test',
    // ]);

    return $this->backendResponse();
  }

  public function newAction(): ResponseInterface
  {
    // Hole alle Datensätze, die als "Kunde" fungieren
    $clients = $this->clientRepository->findCurrent(); // Annahme: Du hast ein Client-Repository
    $this->view->assign('clients', $clients);

    $this->setModuleTitle('Kontakt erstellen');
    return $this->backendResponse();
  }

  public function createAction(array $newContact): ResponseInterface
  {
    // Hier nutzt du den DataHandler, um den Kontakt zu erstellen.
    // Das ist sicherer, da alle Hooks und TCA-Regeln beachtet werden.
    $categoryList = '';
    if (!empty($newContact['categories']) && is_array($newContact['categories'])) {
      $categoryList = implode(',', $newContact['categories']);
    }

    $dataMap = [];
    $tempId = 'NEW_CONTACT';
    $dataMap['tt_address'][$tempId] = [
      'pid' => 4, // Die PID, wo der Kontakt gespeichert werden soll
      'email' => $newContact['email'],
      'gender' => $newContact['gender'],
      'first_name' => $newContact['firstName'],
      'last_name' => $newContact['lastName'],
      'company' => $newContact['company'],
      'position' => $newContact['position'],
      'client' => ($newContact['contactType'] === 'client') ? $newContact['client'] : 0,
      'categories' => $categoryList,
    ];

    $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
    $dataHandler->start($dataMap, []);
    $dataHandler->process_datamap();

    \TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump($dataMap);

    // UID des neuen Kontakts holen
    $newUid = $dataHandler->substNEWwithIDs[$tempId] ?? null;

    \TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump($newUid);

    if ($newUid) {
      $editUrl = ContactHelper::generateEditLink($newUid);
      return $this->redirectToUri((string) $editUrl);
    }
  }

  /**
   * action show
   *
   * @param TtAddress $contact
   * @return void
   */
  public function showAction(TtAddress $contact): ResponseInterface
  {

    $this->addCustomAsset('https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css?v=1.0', 'css');
    $this->addCustomAsset('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css', 'css');

    $uid = $contact->getUid();

    // Letzte 3 Mails zum Kontakt, absteigend nach Sendedatum
    $mails = $this->mailRepository->findBy(
      ['receiver' => $uid],
      ['sent' => QueryInterface::ORDER_DESCENDING],
      3
    );

    // Letzte 3 Akkreditierungen zum Kontakt, absteigend nach Event-Datum
    $accreditations = $this->accreditationRepository->findBy(
      ['guest' => $uid],
      ['event.date' => QueryInterface::ORDER_DESCENDING],
      3
    );

    // Gesamtzahl Mails und Akkreditierungen
    $countedMails = $this->mailRepository->count(['receiver' => $uid]);
    $countedAccreditations = $this->accreditationRepository->count(['guest' => $uid]);

    $this->view->assignMultiple([
      'contact' => $contact,
      'countedMails' => $countedMails,
      'mails' => $mails,
      'accreditations' => $accreditations,
      'countedAccreditations' => $countedAccreditations,
    ]);

    $this->setModuleTitle($contact->getFullName() . ' Kontaktübersicht');
    return $this->backendResponse();
  }

  /**
   * action list
   *
   * @param SysCategory|null $mailingList
   * @param array|null       $filter
   * @return void
   */
  public function listAction(SysCategory $mailingList = null, array $filter = null): ResponseInterface
  {
    // 1) Kategorien-Baum (UID 256)
    $categories = $this->sysCategoryRepository->findByParentUid(256);

    // 2) Pagination-Settings
    $paginationConfig = $this->settings['list']['paginate'] ?? [];
    $defaultItemsPerPageFromSettings = (int) ($paginationConfig['itemsPerPage'] ?? 100);
    $maxLinks = (int) ($paginationConfig['maximumNumberOfLinks'] ?? 7); // Ein sinnvollerer Standard für maxLinks

    $itemsPerPage = $defaultItemsPerPageFromSettings; // Starte mit dem Wert aus den Settings

    // Wenn ein 'max'-Wert im Filter übergeben wurde und dieser gültig ist, hat er Vorrang
    if (isset($filter['max']) && (int) $filter['max'] > 0) {
      $itemsPerPage = (int) $filter['max'];
    }

    // Letzter Fallback, falls alles vorher zu 0 oder weniger geführt hat
    if ($itemsPerPage <= 0) {
      $itemsPerPage = 25; // Oder 100, je nach deinem bevorzugten Minimal-Standard
    }

    $currentPage = $this->request->hasArgument('currentPage')
      ? (int) $this->request->getArgument('currentPage')
      : 1;

    if ($mailingList !== null) {
      // 1) Kontakte aus Kategorie sammeln
      $collection = \TYPO3\CMS\Frontend\Category\Collection\CategoryCollection::load(
        $mailingList->getUid(),
        true,
        'tt_address',
        'categories'
      );
      $selectedContacts = [];
      foreach ($collection as $contactUid => $values) {
        if ($contact = $this->ttAddressRepository->findByUid((int) $contactUid)) {
          $selectedContacts[] = $contact;
        }
      }

      // 3) ArrayPaginator bauen
      $paginator = GeneralUtility::makeInstance(
        ArrayPaginator::class,
        $selectedContacts,
        $currentPage,
        $itemsPerPage
      );

      // 4) Pagination-Objekt
      $pagination = $this->buildPagination(
        $paginator,
        $paginationConfig,
        $maxLinks
      );

      // 5) View-Übergabe
      $this->view->assignMultiple([
        'filter' => $filter,
        'categories' => $categories,
        'selectedContacts' => $selectedContacts,
        'mailingList' => $mailingList,
        'settings' => $this->settings,
        'pagination' => [
          'currentPage' => $currentPage,
          'paginator' => $paginator,
          'pagination' => $pagination,
        ],
      ]);

    } else {

      // --- B) Ohne Kategorie, komplette oder gefilterte Liste ---
      $contacts = $filter !== null
        ? $this->ttAddressRepository->findFiltered($filter)
        : $this->ttAddressRepository->findAll();

      // 3) QueryResultPaginator bauen
      $paginator = GeneralUtility::makeInstance(
        QueryResultPaginator::class,
        $contacts,
        $currentPage,
        $itemsPerPage
      );

      // 4) Pagination-Objekt
      $pagination = $this->buildPagination(
        $paginator,
        $paginationConfig,
        $maxLinks
      );

      // 5) View-Übergabe
      $this->view->assignMultiple([
        'filter' => $filter,
        'categories' => $categories,
        'contacts' => $contacts,
        'settings' => $this->settings,
        'pagination' => [
          'currentPage' => $currentPage,
          'paginator' => $paginator,
          'pagination' => $pagination,
        ],
      ]);

    }

    $this->setModuleTitle('Kontaktübersicht');
    return $this->backendResponse();
  }

  /**
   * action pressList
   *
   * @return void
   */
  public function pressListAction(): ResponseInterface
  {
    // 1) Mailing-Listen-Kategorien holen (Parent UID = 79)
    $mailingLists = $this->sysCategoryRepository->findByParentUid(79);

    // 2) Pagination-Konfiguration
    $paginationConfig = $this->settings['list']['paginate'] ?? [];
    $itemsPerPage = isset($paginationConfig['itemsPerPage'])
      ? (int) $paginationConfig['itemsPerPage']
      : 100;
    if ($itemsPerPage <= 0) {
      $itemsPerPage = 100;
    }
    $maxLinks = (int) ($paginationConfig['maximumNumberOfLinks'] ?? 0);
    $currentPage = $this->request->hasArgument('currentPage')
      ? (int) $this->request->getArgument('currentPage')
      : 1;

    // 3) QueryResultPaginator anlegen
    $paginator = GeneralUtility::makeInstance(
      QueryResultPaginator::class,
      $mailingLists,
      $currentPage,
      $itemsPerPage
    );

    // 4) Pagination-Klasse wählen
    $paginationClass = $paginationConfig['class'] ?? SimplePagination::class;
    if (
      $paginationClass === NumberedPagination::class
      && $maxLinks > 0
      && class_exists(NumberedPagination::class)
    ) {
      $pagination = GeneralUtility::makeInstance(
        NumberedPagination::class,
        $paginator,
        $maxLinks
      );
    } elseif (class_exists($paginationClass)) {
      $pagination = GeneralUtility::makeInstance(
        $paginationClass,
        $paginator
      );
    } else {
      $pagination = GeneralUtility::makeInstance(
        SimplePagination::class,
        $paginator
      );
    }

    // 5) View übergeben
    $this->view->assignMultiple([
      'mailingLists' => $mailingLists,
      'settings' => $this->settings,
      'pagination' => [
        'currentPage' => $currentPage,
        'paginator' => $paginator,
        'pagination' => $pagination,
      ],
    ]);

    $this->setModuleTitle('Presse-Verteiler');
    return $this->backendResponse();
  }

  /**
   * action promiList
   *
   * @return void
   */
  public function promiListAction(): ResponseInterface
  {
    // 1) Kategorien für Promis holen (Parent UID = 205)
    $mailingLists = $this->sysCategoryRepository->findByParentUid(205);

    // 2) Pagination-Konfiguration aus den Settings
    $paginationConfig = $this->settings['list']['paginate'] ?? [];
    $itemsPerPage = isset($paginationConfig['itemsPerPage'])
      ? (int) $paginationConfig['itemsPerPage']
      : 100;
    if ($itemsPerPage <= 0) {
      $itemsPerPage = 100;
    }
    $maxLinks = (int) ($paginationConfig['maximumNumberOfLinks'] ?? 0);
    $currentPage = $this->request->hasArgument('currentPage')
      ? (int) $this->request->getArgument('currentPage')
      : 1;

    // 3) QueryResultPaginator anlegen
    $paginator = GeneralUtility::makeInstance(
      QueryResultPaginator::class,
      $mailingLists,
      $currentPage,
      $itemsPerPage
    );

    // 4) Gewünschte Pagination-Klasse wählen
    $paginationClass = $paginationConfig['class'] ?? SimplePagination::class;
    if (
      $paginationClass === NumberedPagination::class
      && $maxLinks > 0
      && class_exists(NumberedPagination::class)
    ) {
      $pagination = GeneralUtility::makeInstance(
        NumberedPagination::class,
        $paginator,
        $maxLinks
      );
    } elseif (class_exists($paginationClass)) {
      $pagination = GeneralUtility::makeInstance(
        $paginationClass,
        $paginator
      );
    } else {
      $pagination = GeneralUtility::makeInstance(
        SimplePagination::class,
        $paginator
      );
    }

    // 5) Werte an das Template übergeben
    $this->view->assignMultiple([
      'mailingLists' => $mailingLists,
      'settings' => $this->settings,
      'pagination' => [
        'currentPage' => $currentPage,
        'paginator' => $paginator,
        'pagination' => $pagination,
      ],
    ]);

    $this->setModuleTitle(title: 'Promi-Verteiler');
    return $this->backendResponse();
  }


  /**
   * action mailingList
   *
   * @return void
   */
  public function mailingListAction(): ResponseInterface
  {
    // 1) Alle Mailing-Listen (SysCategory mit Parent UID 254) laden
    $mailingLists = $this->sysCategoryRepository->findByParentUid(254);

    // 2) Pagination-Settings
    $paginationConfig = $this->settings['list']['paginate'] ?? [];
    $itemsPerPage = isset($paginationConfig['itemsPerPage'])
      ? (int) $paginationConfig['itemsPerPage']
      : 100;
    if ($itemsPerPage <= 0) {
      $itemsPerPage = 100;
    }
    $maxLinks = (int) ($paginationConfig['maximumNumberOfLinks'] ?? 0);
    $currentPage = $this->request->hasArgument('currentPage')
      ? (int) $this->request->getArgument('currentPage')
      : 1;

    // 3) Paginator erstellen
    $paginator = GeneralUtility::makeInstance(
      QueryResultPaginator::class,
      $mailingLists,
      $currentPage,
      $itemsPerPage
    );

    // 4) Gewünschte Pagination-Klasse wählen
    $paginationClass = $paginationConfig['class'] ?? SimplePagination::class;
    if (
      $paginationClass === NumberedPagination::class
      && $maxLinks > 0
      && class_exists(NumberedPagination::class)
    ) {
      $pagination = GeneralUtility::makeInstance(
        NumberedPagination::class,
        $paginator,
        $maxLinks
      );
    } elseif (class_exists($paginationClass)) {
      $pagination = GeneralUtility::makeInstance(
        $paginationClass,
        $paginator
      );
    } else {
      $pagination = GeneralUtility::makeInstance(
        SimplePagination::class,
        $paginator
      );
    }

    // 5) Alle Werte an das Fluid-Template übergeben
    $this->view->assignMultiple([
      'mailingLists' => $mailingLists,
      'settings' => $this->settings,
      'pagination' => [
        'currentPage' => $currentPage,
        'paginator' => $paginator,
        'pagination' => $pagination,
      ],
    ]);

    $this->setModuleTitle(title: 'Sonstige-Verteiler');
    return $this->backendResponse();
  }


  /**
   * action clientList
   *
   * @return void
   */
  public function clientListAction(): ResponseInterface
  {
    // 1) Alle Kategorien ohne zugeordneten Client laden
    $mailingLists = $this->sysCategoryRepository->findByProperty('client', 0, false);

    // 2) Pagination-Konfiguration aus TS einstellen
    $paginationConfig = $this->settings['list']['paginate'] ?? [];
    $itemsPerPage = isset($paginationConfig['itemsPerPage'])
      ? (int) $paginationConfig['itemsPerPage']
      : 100;
    if ($itemsPerPage <= 0) {
      $itemsPerPage = 100;
    }
    $maxLinks = (int) ($paginationConfig['maximumNumberOfLinks'] ?? 0);
    $currentPage = $this->request->hasArgument('currentPage')
      ? (int) $this->request->getArgument('currentPage')
      : 1;

    // 3) Paginator auf Basis des QueryResults bauen
    $paginator = GeneralUtility::makeInstance(
      QueryResultPaginator::class,
      $mailingLists,
      $currentPage,
      $itemsPerPage
    );

    // 4) Gewünschte Pagination-Klasse wählen (Numbered o. Simple)
    $paginationClass = $paginationConfig['class'] ?? SimplePagination::class;
    if (
      $paginationClass === NumberedPagination::class
      && $maxLinks > 0
      && class_exists(NumberedPagination::class)
    ) {
      $pagination = GeneralUtility::makeInstance(
        NumberedPagination::class,
        $paginator,
        $maxLinks
      );
    } elseif (class_exists($paginationClass)) {
      $pagination = GeneralUtility::makeInstance(
        $paginationClass,
        $paginator
      );
    } else {
      $pagination = GeneralUtility::makeInstance(
        SimplePagination::class,
        $paginator
      );
    }

    // 5) Werte an Fluid übergeben
    $this->view->assignMultiple([
      'mailingLists' => $mailingLists,
      'settings' => $this->settings,
      'pagination' => [
        'currentPage' => $currentPage,
        'paginator' => $paginator,
        'pagination' => $pagination,
      ],
    ]);

    $this->setModuleTitle(title: 'Kunden-Verteiler');
    return $this->backendResponse();
  }


  /**
   * action import
   *
   * @return void
   */
  public function importAction(): ResponseInterface
  {
    // 1) Extension-Konfiguration

    // 2) Bereits importierte Kontakte und Spalten
    $importedContacts = $this->ttAddressRepository->findByPid($this->emConfiguration->getImporterPid());
    $importableColumns = $this->emConfiguration->getImportableColumns();

    // 3) Verteilerlisten
    $pressLists = $this->sysCategoryRepository->findByParentUid(79);
    $promiLists = $this->sysCategoryRepository->findByParentUid(205);
    $otherLists = $this->sysCategoryRepository->findByParentUid(254);
    $clientsLists = $this->sysCategoryRepository->findByProperty('client', 0, false);

    // Hole alle Datensätze, die als "Kunde" fungieren
    $clients = $this->clientRepository->findCurrent(); // Annahme: Du hast ein Client-Repository

    // 4) Alles an Fluid übergeben
    $this->view->assignMultiple([
      'clients' => $clients,
      'importedContacts' => $importedContacts,
      'importableColumns' => $importableColumns,
      'pressLists' => $pressLists,
      'promiLists' => $promiLists,
      'otherLists' => $otherLists,
      'clientsLists' => $clientsLists,
    ]);

    $this->setModuleTitle(title: 'Kontakt-Importer');
    return $this->backendResponse();
  }

  /**
   * action importer
   *
   * @return void
   */
  public function importerAction(): ResponseInterface
  {

    // 1) CSV-Upload prüfen
    $uploadedFile = $this->request->getArgument('csv') ?? [];

    // 1) CSV-Upload prüfen
    if (!$uploadedFile instanceof UploadedFileInterface) {
      $this->addModuleFlashMessage(
        'Keine Datei hochgeladen oder das Argument ist ungültig.',
        'Import fehlgeschlagen',
        'ERROR' // Dies wird durch deine addModuleFlashMessage Methode zu Severity::ERROR
      );
      return $this->redirect('import'); // WICHTIG: redirect zurückgeben!
    }

    if ($uploadedFile->getError() !== \UPLOAD_ERR_OK) {
      // Du könntest hier spezifischere Fehlermeldungen basierend auf dem Fehlercode ausgeben
      $this->addModuleFlashMessage(
        'Fehler beim Hochladen der Datei (Fehlercode: ' . $uploadedFile->getError() . '). Bitte versuchen Sie es erneut oder prüfen Sie die Dateigröße und -rechte.',
        'Import fehlgeschlagen',
        'ERROR'
      );
      return $this->redirect('import'); // WICHTIG: redirect zurückgeben!
    }

    // Korrekter Zugriff auf Datei-Eigenschaften über Methoden
    $tmpPath = $uploadedFile->getStream()->getMetadata('uri'); // Pfad zur temporären Datei
    $clientMime = $uploadedFile->getClientMediaType();       // Vom Client gesendeter MIME-Typ
    $fileSize = $uploadedFile->getSize();                   // Dateigröße

    $allowedMimes = [
      'text/csv',
      'text/plain', // Oft für CSV-Dateien verwendet
      'application/vnd.ms-excel', // Manchmal von älteren Excel-Versionen
      'application/csv',
      'application/x-csv',
      'text/x-csv',
      'text/comma-separated-values',
      'text/x-comma-separated-values',
    ];

    // is_uploaded_file() ist eine wichtige Sicherheitsprüfung
    if (
      empty($tmpPath) // tmpPath sollte nicht leer sein
      || !in_array($clientMime, $allowedMimes, true)
      || !is_uploaded_file($tmpPath) // Prüft, ob die Datei wirklich ein HTTP-Upload ist
      || $fileSize <= 0
    ) {
      $this->addModuleFlashMessage(
        'Die hochgeladene Datei ist kein gültiges CSV, ist leer oder der MIME-Typ "' . htmlspecialchars($clientMime ?? 'unbekannt') . '" ist nicht erlaubt.',
        'Import fehlgeschlagen',
        'ERROR'
      );
      return $this->redirect('import'); // WICHTIG: redirect zurückgeben!
    }

    // 2) CSV öffnen und Kopfzeile einlesen
    $fileHandle = @fopen($tmpPath, 'r'); // @ unterdrückt Warning, wenn fopen fehlschlägt, wir prüfen danach
    if ($fileHandle === false) {
      $this->addModuleFlashMessage(
        'Die hochgeladene Datei konnte nicht zum Lesen geöffnet werden.',
        'Import fehlgeschlagen',
        'ERROR'
      );
      return $this->redirect('import'); // WICHTIG: redirect zurückgeben!
    }

    $rawColumns = fgetcsv($fileHandle); // Verwende $fileHandle
    if ($rawColumns === false || empty($rawColumns)) { // Prüfe auch auf leere Header-Zeile
      fclose($fileHandle);
      $this->addModuleFlashMessage(
        'Die CSV-Kopfzeile konnte nicht gelesen werden oder die Datei scheint leer zu sein.',
        'Import fehlgeschlagen',
        'ERROR'
      );
      return $this->redirect('import'); // WICHTIG: redirect zurückgeben!
    }

    // 3) Spalten-Sanitisierung
    $columnsSanitized = [];
    $rules = [
      ['field' => 'firstName', 'patterns' => ['/\bvorname\b/', '/\bfirst name\b/', '/\bfirstname\b/']],
      ['field' => 'secondName', 'patterns' => ['/\bzweiter vorname\b/', '/\bmiddle name\b/', '/\bmiddlename\b/']],
      ['field' => 'lastName', 'patterns' => ['/\bnachname\b/', '/\blastname\b/']],
      ['field' => 'email', 'patterns' => ['/\bemail\b/', '/\bmail\b/']],
      ['field' => 'phone', 'patterns' => ['/\btelefon\b/', '/\btelefonnummer\b/']],
      ['field' => 'mobile', 'patterns' => ['/\bmobil\b/', '/\bmobiltelefon\b/']],
      ['field' => 'company', 'patterns' => ['/\bfirma\b/']],
      ['field' => 'categories', 'patterns' => ['/\bverteiler\b/', '/\bkategorie\b/']],
      ['field' => 'gender', 'patterns' => ['/\bgeschlecht\b/', '/\banrede\b/']],
      ['field' => 'personally', 'patterns' => ['/\bper du\b/', '/\bdu\b/']],
      ['field' => 'www', 'patterns' => ['/\bwebsite\b/', '/\bdomain\b/']],
      ['field' => 'address', 'patterns' => ['/\bstraße\b/', '/\bstreet\b/']],
      ['field' => 'city', 'patterns' => ['/\bstadt\b/', '/\btown\b/']],
      ['field' => 'zip', 'patterns' => ['/\bplz\b/']],
      ['field' => 'title', 'patterns' => ['/\btitel\b/']],
      ['field' => 'titleSuffix', 'patterns' => ['/\bnachgestellter titel\b/']],
      ['field' => 'position', 'patterns' => ['/\bposition\b/', '/\bjob\b/']],
    ];
    foreach ($rawColumns as $idx => $col) {
      $val = mb_strtolower(trim((string) $col)); // (string) cast für Sicherheit
      foreach ($rules as $r) {
        $val = preg_replace($r['patterns'], $r['field'], $val);
      }
      $columnsSanitized[$idx] = $val;
    }

    // 4) Datenzeilen einlesen (dein Code bleibt hier gleich, aber verwende $fileHandle)
    $rawContacts = [];
    while (($row = fgetcsv($fileHandle)) !== false) { // Verwende $fileHandle
      if (count($row) === count($columnsSanitized)) {
        // Stelle sicher, dass Keys und Values nicht leer sind, um Fehler bei array_combine zu vermeiden
        if (!in_array(null, $columnsSanitized, true) && !in_array(null, $row, true)) {
          $rawContacts[] = array_combine($columnsSanitized, $row);
        } else {
          // Zeile überspringen oder Fehler loggen, wenn Spaltenanzahl nicht passt oder leere Werte kritisch sind
        }
      } else {
        // Zeile überspringen oder Fehler loggen, wenn Spaltenanzahl nicht passt
        // z.B. $this->addModuleFlashMessage('Zeile übersprungen: Spaltenanzahl stimmt nicht.', 'Import-Warnung', 'WARNING', false); // false, da kein redirect
      }
    }
    fclose($fileHandle); // Wichtig: Datei schließen

    // 5) Jede Zeile aufbereiten
    $prepared = [];
    foreach ($rawContacts as $data) {
      // a) Duplikat-Prüfung per E-Mail
      $exists = [];
      if (!empty($data['email'])) {
        $found = $this->ttAddressRepository->findByProperty('email', trim(strtolower($data['email'])), true);
        if ($found) {
          $exists[] = $found->getUid();
        }
      }
      $data['duplicates'] = $exists;

      // b) Gender normalisieren
      if (isset($data['gender'])) {
        $g = strtolower(trim($data['gender']));
        if (preg_match('/\b(frau|female|f|w|weiblich)\b/', $g)) {
          $data['gender'] = 'f';
        } elseif (preg_match('/\b(herr|mann|male|m|männlich)\b/', $g)) {
          $data['gender'] = 'm';
        } else {
          $data['gender'] = 'v';
        }
      }

      // c) Kategorien-Uids extrahieren
      if (!empty($data['categories'])) {
        $cats = array_map('trim', explode(',', $data['categories']));
        $mapped = [];
        foreach ($cats as $t) {
          if (preg_match('/\[(\d+)\]/', $t, $m)) {
            $catObj = $this->sysCategoryRepository->findByUid((int) $m[1]);
            if ($catObj) {
              $mapped[] = $catObj;
            }
          }
        }
        $data['categories'] = $mapped;
      }

      // d) persönlich-Flag
      $data['personally'] = !empty($data['personally']);

      // e) URL-Validierung
      if (!empty($data['www']) && !filter_var(gethostbyname($data['www']), FILTER_VALIDATE_IP)) {
        $data['description'] = trim(($data['description'] ?? '') . ' Nicht verifizierte Domain: ' . $data['www']);
        $data['www'] = '';
      }

      // f) System-Felder
      $data['pid'] = $this->emConfiguration->getImporterPid();
      $data['cruserId'] = $this->getCurrentBackendUserUid();
      $data['copyToPid'] = (int) ($this->request->getArgument('copyToPid') ?? 0);

      // g) E-Mail aufbereiten & validieren
      $email = trim(strtolower($data['email'] ?? ''));
      if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $data['email'] = $email;
        $data['valid'] = true;
      } else {
        $data['valid'] = false;
      }

      // h) alle sonstigen Spalten in description anhängen
      $importCols = array_keys($data);
      $allowedCols = $this->emConfiguration->getImportableColumns();   // am besten: explode(',', …)
      $extraCols = array_diff($importCols, $allowedCols);

      foreach ($extraCols as $col) {
        if (!empty($data[$col])) {
          $value = $data[$col];
          if (is_array($value)) {
            // wenn's ein Array von Category-Objekten ist, nach Titeln mappen:
            if (isset($value[0]) && is_object($value[0]) && method_exists($value[0], 'getTitle')) {
              $value = implode(', ', array_map(fn($cat) => $cat->getTitle(), $value));
            } else {
              // sonst einfach joinen
              $value = implode(', ', $value);
            }
          }
          // jetzt ist $value garantiert ein String
          $data['description'] = trim(
            ($data['description'] ?? '') .
            "<br>{$col}: {$value}"
          );
        }
      }


      $prepared[] = $data;
    }

    // 6) Tatsächliches Anlegen
    $created = 0;
    foreach ($prepared as $contactData) {
      $this->generalFunctions->createContact($contactData, 'C-CR2');
      $created++;
    }

    // 7) Rückmeldung und Weiterleitung
    $this->addModuleFlashMessage(
      "{$created} Kontakt(e) erfolgreich importiert und für die Überprüfung vorbereitet.", // Angepasste Nachricht
      'Import erfolgreich',
      'OK'
    );
    return $this->redirect('imported', 'Contact');

  }


  /**
   * action imported
   *
   * @return void
   */
  public function importedAction(): ResponseInterface
  {
    $pid = $this->emConfiguration->getImporterPid();
    $importedContacts = $this->ttAddressRepository->findByPid($pid);

    // Keine importierten Kontakte → zurück zum Import-Formular
    if ($importedContacts->count() === 0) {
      $this->addModuleFlashMessage(
        'Aktuell wurden keine Kontakte importiert. Lade vorerst neue Kontakte als CSV hoch.',
        'Keine importierten Kontakte',
        'WARNING'
      );
      $this->redirect('import', 'Contact');
    }

    // Anzahl der Kontakte ohne Duplikate, aber mit mindestens einer Kategorie
    $confirmable = 0;
    foreach ($importedContacts as $contact) {
      if (
        $contact->getCategories()->count() > 0
        && $contact->getDuplicates()->count() === 0
      ) {
        $confirmable++;
      }
    }

    $this->view->assignMultiple([
      'importedContacts' => $importedContacts,
      'confirmable' => $confirmable,
    ]);

    $this->setModuleTitle(title: 'Importierte Kontakte');
    return $this->backendResponse();
  }

  /**
   * action finisher
   *
   * @return void
   */
  public function finisherAction(): ResponseInterface
  {
    $pid = $this->emConfiguration->getImporterPid();

    $function = $this->request->getArgument('function') ?? '';
    $submitted = $this->request->getArgument('importedContacts') ?? [];

    $confirmed = 0;
    $deleted = 0;
    $edited = 0;

    // 1) Bestätigen/Löschen/Mergen/Ersetzen einzelner Datensätze
    foreach ($submitted as $entry) {
      $uid = (int) ($entry['uid'] ?? 0);
      if ($uid <= 0) {
        continue;
      }
      $contact = $this->ttAddressRepository->findByUid($uid);
      if (!$contact) {
        continue;
      }

      if ($function === 'confirm' && !empty($entry['confirmed'])) {
        // in Hauptarchiv verschieben
        $contact->setPid($pid);
        $contact->setCopyToPid(0);
        $this->ttAddressRepository->update($contact);
        $confirmed++;

      } elseif ($function === 'delete' && !empty($entry['confirmed'])) {
        // löschen
        $this->ttAddressRepository->remove($contact);
        $deleted++;

      } elseif ($function === 'edit' && !empty($entry['edit'])) {
        $mode = $entry['edit'];
        // DELETE
        if ($mode === 'delete') {
          $this->ttAddressRepository->remove($contact);
          $deleted++;
        }
        // MERGE
        elseif ($mode === 'merge' && !empty($entry['duplicateUid'])) {
          $duplicateUid = (int) $entry['duplicateUid'];
          $this->generalFunctions->editContact(
            $this->ttAddressRepository->findByUid($duplicateUid),
            $contact,
            'C-M1'
          );
          $this->ttAddressRepository->remove($contact); // Das Quell-Kontaktobjekt wird entfernt
          $edited++;
        }
        // REPLACE
        elseif ($mode === 'replace' && !empty($entry['duplicateUid'])) {
          $duplicateUid = (int) $entry['duplicateUid'];
          $target = $this->ttAddressRepository->findByUid($duplicateUid);
          // alle Kategorien des Zieltakts löschen
          foreach ($target->getCategories() as $cat) {
            $target->removeCategory($cat);
          }
          $this->ttAddressRepository->update($target);
          $this->generalFunctions->editContact($target, $contact, 'C-O');
          $this->ttAddressRepository->remove($contact);
          $edited++;
        }
      }
    }

    // 2) Feedback-Meldungen
    if ($confirmed > 0) {
      $this->addModuleFlashMessage(
        "{$confirmed} Kontakt(e) erfolgreich bestätigt.",
        'Import abgeschlossen',
        'OK'
      );
    }
    if ($edited > 0) {
      $this->addModuleFlashMessage(
        "{$edited} Kontakt(e) geändert/zusammengeführt.",
        'Import abgeschlossen',
        'OK'
      );
    }
    if ($deleted > 0) {
      $this->addModuleFlashMessage(
        "{$deleted} Kontakt(e) gelöscht.",
        'Einträge entfernt',
        'WARNING'
      );
    }
    if ($confirmed + $edited + $deleted === 0) {
      $this->addModuleFlashMessage(
        'Es wurden keine Änderungen vorgenommen. Hast du etwas ausgewählt?',
        'Keine Aktion',
        'WARNING'
      );
    }

    // 3) Persistieren und weiterleiten
    $this->persistenceManager->persistAll();
    $remaining = $this->ttAddressRepository->findByPid($pid)->count();
    if ($remaining > 0) {
      return $this->redirect('imported', 'Contact');
    } else {
      return $this->redirect('import', 'Contact');
    }
  }

  /**
   * action export
   *
   * @return void
   */
  public function exportAction(): ResponseInterface
  {
    // 1) Roh-Argumente auslesen
    $rawList = $this->request->hasArgument('mailingList') ? $this->request->getArgument('mailingList') : null;
    $rawInclude = $this->request->hasArgument('inclContactData') ? $this->request->getArgument('inclContactData') : false;

    $mailingList = (int) $rawList;
    $inclContactData = (bool) $rawInclude;

    // 2) Prüfen, ob eine gültige Liste übergeben wurde
    if (!$mailingList) {
      $this->addModuleFlashMessage(
        'Es wurde kein Verteiler ausgewählt.',
        'Export abgebrochen',
        'ERROR'
      );
      $this->redirect('list');
    }

    // 3) Kategorie laden
    $category = $this->sysCategoryRepository->findByUid($mailingList);
    if ($category === null) {
      $this->addModuleFlashMessage(
        'Der ausgewählte Verteiler wurde nicht gefunden.',
        'Export abgebrochen',
        'ERROR'
      );
      $this->redirect('list');
    }

    // 4) Kontakte sammeln
    $selectedContacts = [];
    $collection = \TYPO3\CMS\Frontend\Category\Collection\CategoryCollection::load(
      $mailingList,
      true,
      'tt_address',
      'categories'
    );
    foreach ($collection as $uid => $_) {
      if ($contact = $this->ttAddressRepository->findByUid((int) $uid)) {
        $selectedContacts[] = $contact;
      }
    }
    if (empty($selectedContacts)) {
      $this->addModuleFlashMessage(
        'Im Verteiler befinden sich keine Kontakte.',
        'Export abgebrochen',
        'WARNING'
      );
      $this->redirect('list');
    }

    // 5) Dateiname bauen
    $prefix = $category->getClient()
      ? preg_replace('/\s+/', '-', $category->getClient()->getName()) . '_'
      : '';
    $titleClean = preg_replace('/\s+/', '-', $category->getTitle());
    $filename = sprintf(
      'kontaktliste_%s%s_%s.csv',
      $prefix,
      $titleClean,
      date('Y-m-d_H-i-s')
    );

    // 6) CSV schreiben
    $fp = fopen('php://memory', 'w');
    $headers = [
      'KontaktID',
      'Geschlecht',
      'Titel',
      'Vorname',
      'Zweiter Name',
      'Nachname',
      'Unternehmen',
      'E-Mail',
      'Telefon',
      'Mobile',
      'Social Media',
      'Strasse',
      'PLZ',
      'Ort',
      'Land'
    ];
    fputcsv($fp, $headers, ',');

    foreach ($selectedContacts as $contact) {
      $email = $inclContactData ? $contact->getEmail() : 'DSGVO';
      $phone = $inclContactData ? $contact->getPhone() : 'DSGVO';
      $mobile = $inclContactData ? $contact->getMobile() : 'DSGVO';
      $address = $inclContactData ? $contact->getAddress() : 'DSGVO';
      $zip = $inclContactData ? $contact->getZip() : 'DSGVO';

      $socialProfilesData = [];
      foreach ($contact->getSocialProfiles() as $profile) {
        $socialProfilesData[] = $profile->getType() . ': ' . $profile->getHandle();
      }
      // Echter Zeilenumbruch für CSV statt <br>
      $socialMediaOutput = implode("\n", $socialProfilesData);

      switch ($contact->getGender()) {
        case 'm':
          $gender = 'Mann';
          break;
        case 'f':
          $gender = 'Frau';
          break;
        case 'v':
          $gender = 'Sonstig';
          break;
        default:
          $gender = 'Unbekannt';
      }

      $row = [
        $contact->getUid(),
        $gender,
        $contact->getTitle(),
        $contact->getFirstName(),
        $contact->getMiddleName(),
        $contact->getLastName(),
        $contact->getCompany(),
        $email,
        $phone,
        $mobile,
        $socialMediaOutput,
        $address,
        $zip,
        $contact->getCity(),
        $contact->getCountry()
      ];
      fputcsv($fp, $row, ',');
    }

    // 7) Ausgeben
    rewind($fp);
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    fpassthru($fp);
    exit;
  }


  /**
   * Baut aus einem Paginator und den Settings das richtige Pagination-Objekt.
   *
   * @param object $paginator   Ein Extbase-Paginator (z.B. QueryResultPaginator oder ArrayPaginator).
   * @param array  $config      Der Teil aus $this->settings['list']['paginate'].
   * @param int    $maxLinks    Die maximale Anzahl an Links für NumberedPagination.
   * @return object             Das Pagination-Objekt (NumberedPagination, SimplePagination, o.Ä.).
   */
  protected function buildPagination($paginator, array $config, int $maxLinks)
  {
    $paginationClass = $config['class'] ?? null;

    if (
      $paginationClass === NumberedPagination::class
      && $maxLinks > 0
      && class_exists(NumberedPagination::class)
    ) {
      return GeneralUtility::makeInstance(
        NumberedPagination::class,
        $paginator,
        $maxLinks
      );
    }

    if (
      $paginationClass !== null
      && class_exists($paginationClass)
    ) {
      return GeneralUtility::makeInstance(
        $paginationClass,
        $paginator
      );
    }

    return GeneralUtility::makeInstance(
      SimplePagination::class,
      $paginator
    );
  }

  /**
   * Empfängt die hochgeladene CSV-Datei, liest den Header aus
   * und zeigt die Mapping-Ansicht an.
   */
  public function uploadAndMapAction(): ResponseInterface
  {
    // KORREKTUR: Zuerst mit hasArgument() prüfen, ob die Argumente überhaupt da sind.
    if (!$this->request->hasArgument('importFile') || !$this->request->hasArgument('newContact')) {
      $this->addModuleFlashMessage(
        'Keine Datei oder Einstellungen übermittelt.',
        'Fehler',
        'ERROR'
      );
      return $this->redirect('import');
    }

    $uploadedFile = $this->request->getArgument('importFile') ?? null;
    $contactData = $this->request->getArgument('newContact') ?? null;

    $additionalCategories = $contactData['categories'] ?? [];
    $contactType = $contactData['contactType'] ?? 'internal';
    $client = (int) ($contactData['client'] ?? 0);

    // 1. Datei prüfen
    if (!$uploadedFile || $uploadedFile->getError() !== UPLOAD_ERR_OK) {
      $this->addModuleFlashMessage(
        'Fehler beim Upload der Datei.',
        '',
        'ERROR'
      );
      return $this->redirect('import');
    }

    // 2. Datei temporär speichern
    $tempPath = Environment::getVarPath() . '/transient/';
    GeneralUtility::mkdir_deep($tempPath);
    $newFileName = 'import_' . uniqid() . '_' . $uploadedFile->getClientFilename();
    $tempFilePath = $tempPath . $newFileName;
    $uploadedFile->moveTo($tempFilePath);

    // 3. CSV-Header auslesen
    $fileHandle = fopen($tempFilePath, 'r');
    $fileHeaders = fgetcsv($fileHandle, 0, ','); // Annahme: Komma als Trennzeichen
    fclose($fileHandle);

    if ($fileHeaders === false) {
      $this->addModuleFlashMessage(
        'Die CSV-Datei konnte nicht gelesen werden oder ist leer.',
        '',
        'ERROR'
      );
      return $this->redirect('import');
    }

    // 4. Automatisches Mapping
    $mappedHeaders = $this->getAutoMappedHeaders($fileHeaders);
    $availableDbFields = $this->getAvailableContactFields();

    // 5. Daten an die Ansicht übergeben
    $this->view->assignMultiple([
      'tempFilePath' => $tempFilePath,
      'fileHeaders' => $fileHeaders,
      'mappedHeaders' => $mappedHeaders,
      'availableDbFields' => $availableDbFields,
      'contactType' => $contactType,
      'client' => $client,
      'additionalCategories' => implode(',', $additionalCategories)
    ]);

    $this->setModuleTitle(title: 'Upload verfeinern');
    return $this->backendResponse();
  }

  /**
   * Versucht, die Spaltenüberschriften aus der Datei automatisch zuzuordnen.
   */
  private function getAutoMappedHeaders(array $fileHeaders): array
  {
    $dictionary = [
      'gender' => ['gender', 'anrede', 'geschlecht'],
      'title' => ['title', 'titel'],
      'first_name' => ['firstname', 'first name', 'vorname', 'erster name'],
      'middle_name' => ['middlename', 'middle name', 'second name', 'zweiter vorname', 'zweiter name', 'weiterer name'],
      'title_suffix' => ['titlesuffix', 'nachstehender titel', 'suffix titel'],
      'last_name' => ['lastname', 'last name', 'nachname', 'familienname'],
      'company' => ['company', 'firma', 'organisation'],
      'position' => ['position', 'funktion'],
      'address' => ['address', 'adresse', 'straße', 'strasse'],
      'zip' => ['zip', 'plz', 'postleitzahl'],
      'city' => ['city', 'ort', 'stadt'],
      'country' => ['country', 'land'],
      'email' => ['email', 'e-mail'],
      'phone' => ['phone', 'telefon', 'telefonnummer'],
      'mobile' => ['mobile', 'mobil', 'mobiltelefon', 'handy'],
      'categories' => ['categories', 'verteiler'],
    ];

    $mapping = [];
    foreach ($fileHeaders as $header) {
      $foundDbField = '';
      $normalizedHeader = strtolower(trim($header));
      foreach ($dictionary as $dbField => $aliases) {
        if (in_array($normalizedHeader, $aliases, true)) {
          $foundDbField = $dbField;
          break;
        }
      }
      $mapping[$header] = $foundDbField;
    }
    return $mapping;
  }

  /**
   * Holt eine Liste der verfügbaren Felder für das Mapping,
   * indem es die TCA auf eine benutzerdefinierte Eigenschaft 'importable' prüft.
   */
  private function getAvailableContactFields(): array
  {
    $tcaColumns = $GLOBALS['TCA']['tt_address']['columns'];

    // Beginne mit den speziellen, nicht-Datenbank-Optionen
    $fields = [
      '' => 'Nicht importieren',
      'description' => 'Zur Beschreibung hinzufügen'
    ];

    // Gehe alle in der TCA definierten Spalten für tt_address durch
    foreach ($tcaColumns as $fieldName => $config) {
      // Prüfe, ob unsere benutzerdefinierte Eigenschaft gesetzt und 'true' ist
      if (isset($config['config']['importable']) && $config['config']['importable'] === true) {
        $label = $GLOBALS['LANG']->sL($config['label'] ?? '');
        $fields[$fieldName] = $label ?: $fieldName;
      }
    }

    // Sortiere die Felder alphabetisch nach ihrem Label für eine bessere Übersicht im Dropdown
    asort($fields);

    return $fields;
  }

  /**
   * Verschiebt die Kategorie 256 und alle ihre Unterkategorien in einen neuen Ordner.
   */
  public function moveCategoriesAction(): ResponseInterface
  {
    // ### 1. Konfiguration ###
    $startCategoryUid = 256;
    $targetPid = 92; // <-- BITTE ANPASSEN: Setze hier die ID des ZIELORDNERS ein!

    // ### 2. Alle UIDs sammeln ###
    $descendantUids = $this->findAllCategoryDescendants($startCategoryUid);
    $allUidsToMove = array_merge([$startCategoryUid], $descendantUids);

    // ### 3. DataHandler-Anweisung erstellen ###
    $cmdMap = [];
    foreach ($allUidsToMove as $uid) {
      // Die Anweisung lautet: "Verschiebe die sys_category mit dieser UID auf die Ziel-PID."
      $cmdMap['sys_category'][$uid]['move'] = $targetPid;
    }

    // ### 4. DataHandler ausführen ###
    if (!empty($cmdMap)) {
      /** @var DataHandler $dataHandler */
      $dataHandler = GeneralUtility::makeInstance(DataHandler::class);

      $dataHandler->start([], $cmdMap);
      $dataHandler->process_cmdmap(); // Wichtig: process_cmdmap() statt process_datamap()

      $this->persistenceManager->persistAll();

      if (!empty($dataHandler->errorLog)) {
        $this->addModuleFlashMessage(
          'Es sind Fehler beim Verschieben aufgetreten.',
          'Fehler',
          'ERROR'
        );
      } else {
        $this->addModuleFlashMessage(
          sprintf('%d Kategorien wurden erfolgreich in den neuen Ordner verschoben.', count($allUidsToMove)),
          'Aktion erfolgreich',
          'OK'
        );
      }
    } else {
      $this->addModuleFlashMessage(
        'Keine untergeordneten Kategorien zum Verschieben gefunden.',
        'Hinweis',
        'INFO'
      );
    }

    // ### 5. Zurück zur Index-Ansicht ###
    return $this->redirect('index');
  }

  /**
   * Private Hilfsfunktion: Findet rekursiv alle untergeordneten Kategorie-UIDs.
   */
  private function findAllCategoryDescendants(int $parentUid): array
  {
    $allDescendantUids = [];
    $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
      ->getQueryBuilderForTable('sys_category');

    $directChildren = $queryBuilder
      ->select('uid')
      ->from('sys_category')
      ->where($queryBuilder->expr()->eq('client', $queryBuilder->createNamedParameter($parentUid, Connection::PARAM_INT)))
      ->andWhere($queryBuilder->expr()->eq('deleted', 0))
      ->executeQuery()
      ->fetchAllAssociative();

    foreach ($directChildren as $child) {
      $childUid = (int) $child['uid'];
      $allDescendantUids[] = $childUid;
      $grandChildrenUids = $this->findAllCategoryDescendants($childUid);
      if (!empty($grandChildrenUids)) {
        $allDescendantUids = array_merge($allDescendantUids, $grandChildrenUids);
      }
    }

    return $allDescendantUids;
  }


}
