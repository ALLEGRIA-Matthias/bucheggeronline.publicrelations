<?php
namespace BucheggerOnline\Publicrelations\Controller;

use Psr\Http\Message\ResponseInterface;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\History\RecordHistory;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Core\Pagination\SimplePagination;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Pagination\QueryResultPaginator;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;

use Throwable;

use BucheggerOnline\Publicrelations\Domain\Model\Dto\EmConfiguration;
use BucheggerOnline\Publicrelations\Utility\LogGenerator;
use BucheggerOnline\Publicrelations\Utility\MailGenerator;
use BucheggerOnline\Publicrelations\Utility\GeneralFunctions;

use BucheggerOnline\Publicrelations\Domain\Model\Mailing;
use BucheggerOnline\Publicrelations\Domain\Repository\MailingRepository;

use BucheggerOnline\Publicrelations\Domain\Model\Mail;
use BucheggerOnline\Publicrelations\Domain\Repository\MailRepository;

use BucheggerOnline\Publicrelations\Domain\Repository\SysCategoryRepository;

use BucheggerOnline\Publicrelations\Domain\Repository\TtAddressRepository;
use BucheggerOnline\Publicrelations\Domain\Model\TtAddress;

use BucheggerOnline\Publicrelations\Domain\Repository\EventRepository;

use BucheggerOnline\Publicrelations\Domain\Repository\AccreditationRepository;


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
 * MailingController
 */
class MailingController extends AbstractPublicrelationsController
{
  public function __construct(
    private readonly PersistenceManager $persistenceManager,
    private readonly MailingRepository $mailingRepository,
    private readonly MailRepository $mailRepository,
    private readonly SysCategoryRepository $sysCategoryRepository,
    private readonly TtAddressRepository $ttAddressRepository,
    private readonly EventRepository $eventRepository,
    private readonly AccreditationRepository $accreditationRepository,
    private readonly EmConfiguration $emConfiguration,
    private readonly MailGenerator $mailGenerator,
    private readonly LogGenerator $logGenerator
  ) {
  }

  /**
   * Entfernt Duplikate aus einem mehrdimensionalen Array nach einem bestimmten Key.
   */
  private function unique_multidim_array(array $array, string $property): array
  {
    $seen = [];
    $result = [];

    foreach ($array as $item) {
      $value = null;

      if (is_array($item) && isset($item[$property])) {
        $value = $item[$property];
      } elseif (is_object($item) && method_exists($item, 'get' . ucfirst($property))) {
        $getter = 'get' . ucfirst($property);
        $value = $item->$getter();
      }

      if ($value !== null && !in_array($value, $seen, true)) {
        $seen[] = $value;
        $result[] = $item;
      }
    }

    return $result;
  }

  /**
   * action show
   */
  public function showAction(Mailing $mailing): ResponseInterface
  {

    $recordHistory = GeneralUtility::makeInstance(RecordHistory::class);
    $creationInformationForRecord = $recordHistory->getCreationInformationForRecord('tx_publicrelations_domain_model_mailing', ['uid' => $mailing->getUid()]);
    $cruser = null;
    if ($creationInformationForRecord) {
      $cruserId = $creationInformationForRecord['userid'];
      $cruser = ($cruserId) ? GeneralFunctions::getBackendUserDataByUid($cruserId) : null;
    }

    // Mails nach Status
    $uid = $mailing->getUid();
    $this->view->assignMultiple([
      'mailing' => $mailing,
      'cruser' => $cruser,
      'mailsToSend' => $this->mailRepository->findMailsToSend($uid),
      'sentMails' => $this->mailRepository->findSentMails($uid),
      'errors' => $this->mailRepository->findErrors($uid),
    ]);

    // --- HIER DIE BUTTONS ZUM DOCHEADER DEINES MODULS HINZUFÜGEN ---
    if ($this->moduleTemplate && $mailing instanceof Mailing && $mailing->getUid() > 0) {
      $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();
      $shortCutButton = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar()->makeShortcutButton();
      $shortCutButton
        ->setRouteIdentifier('allegria_mailer')
        ->setDisplayName('Mailing: ' . $mailing->getSubject())
        ->setArguments(['action' => 'show', 'controller' => 'Mailing', 'mailing' => $mailing->getUid()]);
      $buttonBar->addButton($shortCutButton, ButtonBar::BUTTON_POSITION_RIGHT, 1);
    }

    $this->setModuleTitle($mailing->getSubject() . ' – Mailing');
    return $this->backendResponse();
  }

  /**
   * action receiverList
   */
  public function receiverListAction(Mailing $mailing): ResponseInterface
  {
    $this->view->assign('mailing', $mailing);

    return $this->backendResponse();
  }

  /**
   * action preview
   */
  public function previewAction(Mailing $mailing): ResponseInterface
  {
    $this->view->assign('mailing', $mailing);

    $guestOutput = [
      'gender' => 1,
      'title' => 'Dr.',
      'firstName' => 'Marianne',
      'lastName' => 'Musterfrau',
      'fullName' => 'Dr. Marianne Musterfrau',
    ];

    $this->view->assignMultiple([
      'guestOutput' => $guestOutput,
      'staff' => ($this->getCurrentBackendUserName() ?? ''),
      'template' => $mailing->getAltTemplate(),
    ]);

    $this->setModuleTitle($mailing->getSubject() . ' – Vorschau');

    return $this->backendResponse();
  }

  /**
   * action list
   */
  public function listAction(): ResponseInterface
  {
    $mailings = $this->mailingRepository->findByStatus(-1, true);

    // Extract UIDs from the fetched mailings
    $mailingUids = [];
    foreach ($mailings as $mailing) {
      $mailingUids[] = $mailing->getUid();
    }

    // Get the mail counts for all mailings at once
    $mailCounts = $this->mailRepository->countByMailingUids($mailingUids);

    $this->view->assignMultiple([
      'mailings' => $mailings,
      'mailCounts' => $mailCounts, // Pass the new count array to the view
    ]);

    $this->setModuleTitle('Vorbereitete Mailings');

    return $this->backendResponse();
  }


  /**
   * action archive
   *
   * @return void
   */
  public function archiveAction(): ResponseInterface
  {
    // Alle Mailings mit Status -1 holen
    $sent = $this->mailingRepository->findByStatus(-1);

    // UIDs aus den abgerufenen Mailings extrahieren
    $mailingUids = [];
    foreach ($sent as $mailing) {
      $mailingUids[] = $mailing->getUid();
    }

    // Die Anzahl der Mails für alle Mailings auf einmal abrufen
    $mailCounts = $this->mailRepository->countByMailingUids($mailingUids);

    // Pagination-Konfiguration aus den Settings (falls vorhanden)
    $paginateConfig = $this->settings['list']['paginate'] ?? [];
    $itemsPerPage = (int) ($paginateConfig['itemsPerPage'] ?? 100);
    $maximumNumberOfLinks = (int) ($paginateConfig['maximumNumberOfLinks'] ?? 0);

    // Aktuelle Seite ermitteln (Fallback: 1)
    $currentPage = $this->request->hasArgument('currentPage')
      ? (int) $this->request->getArgument('currentPage')
      : 1;

    // Paginator erzeugen
    $paginator = GeneralUtility::makeInstance(
      QueryResultPaginator::class,
      $sent,
      $currentPage,
      $itemsPerPage
    );

    // Welche Pagination-Klasse benutzen?
    $paginationClass = $paginateConfig['class'] ?? SimplePagination::class;
    $pagination = match (true) {
      // Nummerierte Links, wenn Klasse existiert und Links > 0
      $paginationClass === NumberedPagination::class
      && $maximumNumberOfLinks > 0
      && class_exists(NumberedPagination::class)
      => GeneralUtility::makeInstance(NumberedPagination::class, $paginator, $maximumNumberOfLinks),

      // Custom-Pagination, falls die Klasse existiert
      class_exists($paginationClass)
      => GeneralUtility::makeInstance($paginationClass, $paginator),

      // Sonst Default SimplePagination
      default
      => GeneralUtility::makeInstance(SimplePagination::class, $paginator),
    };

    // View-Variablen zuweisen
    $this->view->assignMultiple([
      'mailings' => $sent,
      'mailCounts' => $mailCounts, // Das neue Count-Array zuweisen
      'settings' => $this->settings,
      'pagination' => [
        'currentPage' => $currentPage,
        'paginator' => $paginator,
        'instance' => $pagination,
      ],
    ]);

    $this->setModuleTitle('Archivierte Mailings');

    return $this->backendResponse();
  }


  /**
   * action receiverManager
   *
   * @param Mailing    $mailing
   * @param array|null $receiver
   * @return void
   */
  public function receiverManagerAction(Mailing $mailing, ?array $receiver = []): ResponseInterface
  {
    // Alle Werte auf einmal zuweisen
    $this->view->assignMultiple([
      'mailing' => $mailing,
      'receiver' => $receiver,
    ]);

    $this->setModuleTitle('1/3 Empfängermanager – ' . $mailing->getSubject());

    return $this->backendResponse();
  }


  /**
   * action receiverManagerCategories
   *
   * @param Mailing $mailing
   * @param array   $receiver
   * @return void
   */
  public function receiverManagerCategoriesAction(Mailing $mailing, array $receiver): ResponseInterface
  {

    // Holen Sie sich den Listentyp (oder null, wenn nicht gesetzt)
    $listType = $receiver['listType'] ?? '';
    $mailingLists = [];
    $clientUid = 0;

    // Mit match die möglichen Konfigurationen abbilden
    switch ($listType) {
      case 'internal':
        $mailingLists = $this->sysCategoryRepository->findByParentUid($this->emConfiguration->getContactRootUid());
        break;
      case 'client':
        $clientUid = $mailing->getClient()?->getUid() ?? 0;
        if ($clientUid > 0) {
          $mailingLists = $this->sysCategoryRepository->findByProperty('client', $clientUid);
        }
        break;
      default:
        // Standardfall
        $mailingLists = [];
        break;
    }

    $this->view->assignMultiple([
      'mailing' => $mailing,
      'mailingLists' => $mailingLists,
      'client' => $clientUid,
      'receiver' => $receiver,
      'listType' => $listType
    ]);

    $this->setModuleTitle('2/3 Empfängermanager – ' . $mailing->getSubject());

    return $this->backendResponse();
  }

  public function receiverManagerSummaryAction(Mailing $mailing, array $receiver = []): ResponseInterface
  {
    // Konfiguration laden (falls nötig)
    \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\BucheggerOnline\Publicrelations\Domain\Model\Dto\EmConfiguration::class);

    // 1. UIDs aus Mailing-Listen sammeln
    $checkedMailingListUids = [];
    if (!empty($receiver['mailingLists']) && is_iterable($receiver['mailingLists'])) {
      foreach ($receiver['mailingLists'] as $uid => $c) {
        if ((string) $c === '1') {
          $checkedMailingListUids[] = (int) $uid;
        }
      }
    }

    $allReceiverUids = [];
    if (!empty($checkedMailingListUids)) {
      // Annahme: findUidsByCategoryUids holt alle verknüpften tt_address UIDs in einer Abfrage
      $contactUidsFromMailingLists = $this->ttAddressRepository->findUidsByCategoryUids($checkedMailingListUids);
      $allReceiverUids = array_merge($allReceiverUids, $contactUidsFromMailingLists);
    }

    // 2. UIDs aus manueller Auswahl hinzufügen
    if (!empty($receiver['manualReceiver']) && is_iterable($receiver['manualReceiver'])) {
      $manualReceiverUids = array_map('intval', $receiver['manualReceiver']);
      $allReceiverUids = array_merge($allReceiverUids, $manualReceiverUids);
    }

    // 3) Event-basiert (Akkreditierungen) hinzufügen
    if (isset($receiver['event'])) {
      $eventFilter = [
        'event' => $receiver['event'],
        'status' => '1',
        'guestType' => $receiver['guestType'] ?? '',
        'facie' => $receiver['facie'] ?? '',
        'tickets' => $receiver['tickets'] ?? '',
      ];

      $accreditations = $this->accreditationRepository->findFiltered($eventFilter);

      // Sammle die UIDs aus den Akkreditierungen
      $accreditationUids = [];
      foreach ($accreditations as $accreditation) {
        if ($accreditation->getGuest() !== null) {
          $accreditationUids[] = $accreditation->getGuest()->getUid();
        }
      }

      // Füge die gesammelten UIDs zum Haupt-Array hinzu
      $allReceiverUids = array_merge($allReceiverUids, $accreditationUids);
    }

    $allReceiverUids = array_unique($allReceiverUids);

    // 3. Fehlende Auswahl abfangen
    if (empty($allReceiverUids)) {
      $this->addModuleFlashMessage(
        'Es wurden keine Verteiler oder Kontakte ausgewählt.',
        'AUSWAHL LEER!',
        'WARNING'
      );
      return $this->redirect('receiverManager', 'Mailing', null, ['mailing' => $mailing]);
    }

    // 4. Alle Kontaktdaten in einem einzigen Aufruf holen
    // Annahme: findForSummaryByUids gibt nur die benötigten Felder als Array zurück
    $receivers = $this->ttAddressRepository->findForSummaryByUids($allReceiverUids);

    // 5. View-Variablen setzen
    $this->view->assignMultiple([
      'mailing' => $mailing,
      'receiver' => array_merge(
        $receiver,
        ['contacts' => $receivers]
      ),
    ]);

    $this->setModuleTitle('3/3 Zusammenfassung – Einladungen ' . (isset($event) ? ('zu ' . $event->getTitle()) : '') . 'zuordnen');
    return $this->backendResponse();
  }


  // /**
  //  * action receiverManagerSummary
  //  *
  //  * @param Mailing $mailing
  //  * @param array   $receiver
  //  * @return void
  //  */
  // public function receiverManagerSummaryAction(Mailing $mailing, array $receiver): ResponseInterface
  // {
  //   $this->view->assign('mailing', $mailing);

  //   // Initialisierung
  //   $checkedMailingLists = [];
  //   $receivers = [];
  //   $manualReceiver = [];

  //   // Prüfen, ob Mailing-Listen ausgewählt wurden
  //   foreach ($receiver['mailingLists'] ?? [] as $uid => $check) {
  //     if ($check === '1') {
  //       $checkedMailingLists[] = (int) $uid;
  //     }
  //   }

  //   // Prüfen, ob überhaupt etwas zum Zusammenstellen da ist
  //   if (
  //     !empty($checkedMailingLists)
  //     || !empty($receiver['manualReceiver'] ?? [])
  //     || isset($receiver['event'])
  //   ) {
  //     // 1) Kontakte aus Kategorien sammeln
  //     $receivers = [];
  //     if (!empty($checkedMailingLists)) {
  //       $lists = array_map(
  //         fn(int $uid) => \TYPO3\CMS\Frontend\Category\Collection\CategoryCollection::load(
  //           $uid,
  //           true,
  //           'tt_address',
  //           'categories'
  //         ),
  //         $checkedMailingLists
  //       );

  //       foreach ($lists as $col) {
  //         if (is_iterable($col)) {
  //           foreach ($col as $c) {
  //             $receivers[] = $c;
  //           }
  //         }
  //       }
  //     }

  //     // 2) Manuelle Kontakte hinzufügen
  //     if (!empty($receiver['manualReceiver']) && is_iterable($receiver['manualReceiver'])) {
  //       foreach ($receiver['manualReceiver'] as $m) {
  //         $receivers[] = $this->ttAddressRepository->findByProperty('uid', (int) $m, true);
  //       }
  //     }

  //     // 3) Event-basiert (Akkreditierungen) hinzufügen
  //     if (isset($receiver['event'])) {
  //       $eventFilter = [
  //         'event' => $receiver['event'],
  //         'status' => '1',
  //         'guestType' => $receiver['guestType'] ?? '',
  //         'facie' => $receiver['facie'] ?? '',
  //         'tickets' => $receiver['tickets'] ?? '',
  //       ];
  //       $accreditations = $this->accreditationRepository->findFiltered($eventFilter);
  //       foreach ($accreditations as $accreditation) {
  //         if ($accreditation->getGuest() !== null) {
  //           $manualReceiver[] = $accreditation->getGuest();
  //         } else {
  //           $manualReceiver[] = $accreditation->getGuestOutput();
  //         }
  //       }
  //     }

  //     // Alles zusammenführen
  //     $receivers = array_merge($receivers, $manualReceiver);

  //     // Duplikate entfernen
  //     $listType = $receiver['listType'] ?? '';

  //     if (($receiver['guestType'] ?? '') === '1') {
  //       // bei Gast-Typ 1 nach UID deduplizieren
  //       $receivers = $this->unique_multidim_array($receivers, 'uid');
  //     } elseif (!in_array($listType, ['4', '5'], true)) {
  //       // sonst (außer bei Listentyp 4 oder 5) nach E-Mail deduplizieren
  //       $receivers = $this->unique_multidim_array($receivers, 'email');
  //     }

  //     // Ergebnis zurückgeben
  //     $receiver['contacts'] = $receivers;

  //   } else {
  //     // keine Auswahl → Warnung und zurück
  //     $this->addModuleFlashMessage(
  //       'Nochmals von vorne: Es wurden keine Verteiler ausgewählt.',
  //       'VERTEILERAUSWAHL LEER!',
  //       'WARNING'
  //     );
  //     return $this->redirect(
  //       'receiverManager',
  //       'Mailing',
  //       null,
  //       ['mailing' => $mailing]
  //     );
  //   }

  //   $this->view->assign('receiver', $receiver);

  //   $this->setModuleTitle('3/3 Übersicht Empfänger-Auswahl – ' . $mailing->getSubject());

  //   return $this->backendResponse();
  // }

  public function createMailsAction(Mailing $mailing, array $receiver): ResponseInterface
  {
    $selectedReceiverUidsString = $receiver['selectedReceiverUids'] ?? '';
    $selectedReceiverUids = GeneralUtility::intExplode(',', $selectedReceiverUidsString, true);

    if (empty($selectedReceiverUids)) {
      $this->addModuleFlashMessage('Keine Kontakte ausgewählt.', 'KEINE AKTION', 'INFO');
      return $this->redirect('show', 'Mailing', null, ['mailing' => $mailing]);
    }

    // 1. Alle benötigten Kontaktdaten in einem Rutsch abrufen (ohne Hydration)
    $receiverData = $this->ttAddressRepository->findGuestsForBulkProcessing($selectedReceiverUids);
    $receiverById = array_column($receiverData, null, 'uid');

    // 2. Duplikate (bestehende Mails) gesammelt prüfen
    $existingMailReceiverUids = $this->mailRepository->findReceiverUidsByMailing($selectedReceiverUids, $mailing->getUid());
    $isAlreadyAssigned = array_flip($existingMailReceiverUids);

    $tcaUpdates = [];
    $createdCount = 0;
    $duplicateNames = [];
    $excludedNames = [];

    foreach ($selectedReceiverUids as $receiverUid) {
      $receiverRecord = $receiverById[$receiverUid] ?? null;

      if (!$receiverRecord) {
        continue; // Kontakt wurde nicht gefunden, ignorieren
      }

      $receiverName = $this->formatReceiverName($receiverRecord);

      // Prüfung 1: Mailing-Ausschluss
      if ((int) $receiverRecord['mailing_exclude'] === 1) {
        $excludedNames[] = $receiverName;
        continue;
      }

      // Prüfung 2: Duplikat im Mailing (UID-basiert)
      if (isset($isAlreadyAssigned[$receiverUid])) {
        $duplicateNames[] = $receiverName;
        continue;
      }

      // Gast ist nicht ausgeschlossen und kein Duplikat -> DataHandler-Array erstellen
      $newMailData = [
        'pid' => $mailing->getPid(),
        'mailing' => $mailing->getUid(),
        'receiver' => $receiverUid,
        'type' => 0,
      ];
      $tcaUpdates['tx_publicrelations_domain_model_mail']['NEW_' . $receiverUid] = $newMailData;
      $createdCount++;
    }

    // Alle Änderungen auf einmal persistieren
    if ($createdCount > 0) {
      $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
      $dataHandler->start($tcaUpdates, []);
      $dataHandler->process_datamap();

      // if (!empty($dataHandler->errorLog)) {
      //   $errorMessage = sprintf(
      //     'Fehler beim Erstellen der Akkreditierungen: %s',
      //     implode('<br>', $dataHandler->errorLog)
      //   );
      //   \TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump($errorMessage);
      // }
    }

    // Statusaktualisierung nach der Schleife
    if ($mailing->getStatus() === 0 && $createdCount > 0) {
      $mailing->setStatus(1);
      $this->mailingRepository->update($mailing);
    } elseif ($mailing->getStatus() === -1 && $createdCount > 0) {
      $mailing->setStatus(3);
      $this->mailingRepository->update($mailing);
    }

    $this->persistenceManager->persistAll();

    // Feedback
    if (count($excludedNames) > 0) {
      $message = sprintf('%d Kontakte wollten keine Mailings erhalten und wurden nicht zugeordnet: <br> %s', count($excludedNames), implode(', ', $excludedNames));
      $this->addModuleFlashMessage($message, 'EINIGE KONTAKTE AUSGESCHLOSSEN', 'WARNING');
    }
    if (count($duplicateNames) > 0) {
      $message = sprintf('Nicht alle Kontakte konnten zugewiesen werden. %d Duplikat(e) gefunden: <br> %s', count($duplicateNames), implode(', ', $duplicateNames));
      $this->addModuleFlashMessage($message, 'DUPLIKATE NICHT ERSTELLT', 'WARNING');
    }
    if ($createdCount > 0) {
      $message = sprintf("Es wurden %d Kontakte erfolgreich zugewiesen. Der Versand muss noch angestoßen werden.", $createdCount);
      $this->addModuleFlashMessage($message, 'EINLADUNGEN ERSTELLT!', 'OK');
    } elseif ($createdCount === 0 && count($duplicateNames) === 0 && count($excludedNames) === 0) {
      $this->addModuleFlashMessage('Es wurden keine Kontakte ausgewählt oder es gab ein Problem mit den Eingabedaten.', 'KEINE AKTION', 'INFO');
    }

    return $this->redirect('show', 'Mailing', null, ['mailing' => $mailing]);
    // return $this->redirect('list', 'Mailing');
  }

  /**
   * Hilfsmethode zur Formatierung des Anzeigenamens aus einem Daten-Array.
   */
  private function formatReceiverName(array $receiverData): string
  {
    $name = trim($receiverData['first_name'] . ' ' . $receiverData['middle_name'] . ' ' . $receiverData['last_name']);
    if (empty($name) && !empty($receiverData['company'])) {
      return $receiverData['company'];
    }
    return $name;
  }


  // /**
  //  * action createMails
  //  *
  //  * @param Mailing $mailing
  //  * @param array   $receiver
  //  * @return void
  //  */
  // public function createMailsAction(Mailing $mailing, array $receiver): ResponseInterface
  // {
  //   $created = 0;
  //   $duplicate = 0;

  //   // Nur dann iterieren, wenn contacts existiert und ein Array ist
  //   $contacts = $receiver['contacts'] ?? [];
  //   $listType = $receiver['listType'] ?? '';

  //   foreach ($contacts as $contactId => $selected) {
  //     // Nur „1“ und gültige ID > 0 verarbeiten
  //     if ($selected === '1' && $contactId !== 0) {
  //       $ttAddress = null;
  //       $searchDuplicate = false;

  //       if ($listType === '5') {
  //         // Liste Typ 5: nur UID
  //         $searchDuplicate = $this->mailRepository->findMailsByReceiver(
  //           $mailing->getUid(),
  //           $contactId
  //         );
  //         if (!$searchDuplicate) {
  //           $ttAddress = $this->ttAddressRepository->findByUid($contactId);
  //         }
  //       } else {
  //         // Sonst: Adresse laden, dann nach E-Mail & UID prüfen
  //         $ttAddress = $this->ttAddressRepository->findByUid($contactId);
  //         if ($ttAddress !== null) {
  //           $searchDuplicate = $this->mailRepository->findMailsByReceiver(
  //             $mailing->getUid(),
  //             $ttAddress->getUid(),
  //             $ttAddress->getEmail()
  //           );
  //         }
  //       }

  //       // Mailing-Ausschluss?
  //       if ($ttAddress instanceof TtAddress && $ttAddress->isMailingExclude()) {
  //         $this->addModuleFlashMessage(
  //           sprintf(
  //             '<strong>%s</strong> will keine Mailings mehr erhalten und wurde daher nicht in die Liste aufgenommen!',
  //             htmlspecialchars($ttAddress->getName(), ENT_QUOTES)
  //           ),
  //           'KONTAKT WILL KEINE MAILINGS ERHALTEN!',
  //           'WARNING'
  //         );

  //         // Kein Duplikat und gültige Adresse → anlegen
  //       } elseif (!$searchDuplicate && $ttAddress !== null) {
  //         $newMail = new Mail();
  //         $newMail->setMailing($mailing);
  //         $newMail->setCruserId($this->getCurrentBackendUserUid());
  //         $newMail->setCrdate(new \DateTimeImmutable());
  //         $newMail->setTstamp(new \DateTimeImmutable());
  //         $newMail->setReceiver($ttAddress);

  //         $mailing->addMail($newMail);
  //         $this->mailingRepository->update($mailing);
  //         $this->persistenceManager->persistAll();

  //         $created++;
  //       } else {
  //         // hier landen echte Duplikate
  //         $duplicate++;
  //       }
  //     }
  //   }

  //   // Status aktualisieren
  //   if ($mailing->getStatus() === 0) {
  //     $mailing->setStatus(1);
  //   }
  //   if ($mailing->getStatus() === -1) {
  //     $mailing->setStatus(3);
  //   }
  //   $this->mailingRepository->update($mailing);

  //   // Feedback
  //   if ($created > 0) {
  //     $this->addModuleFlashMessage(
  //       sprintf('Es wurden %d Empfänger angelegt.', $created),
  //       'FABELHAFT • EMPFÄNGER ANGELEGT!',
  //       'OK'
  //     );
  //   }
  //   if ($duplicate > 0) {
  //     $this->addModuleFlashMessage(
  //       sprintf('Nicht alle Empfänger konnten angelegt werden. %d Duplikat(e).', $duplicate),
  //       'DUPLIKATE WURDEN NICHT ANGELEGT!',
  //       'WARNING'
  //     );
  //   }

  //   return $this->redirect('show', 'Mailing', null, ['mailing' => $mailing]);
  // }


  /**
   * action sendMails
   *
   * @param Mailing $mailing
   * @return void
   * @throws \Throwable
   */
  public function sendMailsAction(Mailing $mailing): ResponseInterface
  {
    // Test-Mail versenden?
    if ($this->request->hasArgument('testmail')) {
      $testData = [
        'mailing' => $mailing,
        'receiver' => [
          'name' => 'Mario Reiner',
          'fullName' => 'Mag. Mario Reiner, MAS',
          'firstName' => 'Mario',
          'lastName' => 'Reiner',
          'gender' => 2,
          'email' => 'mario.reiner@allegria.at',
        ],
      ];
      $this->mailGenerator
        ->createMailerTestmail('Mailer-Test', $testData, $this->settings);

      $mailing->setTest(1);
      $this->mailingRepository->update($mailing);

      $this->addModuleFlashMessage(
        'Das hat geklappt. ☺',
        'FABELHAFT • TESTMAIL VERSANDT!',
        'OK'
      );

      // richtigen Versand starten?
    } elseif ($this->request->hasArgument('sendMailing')) {
      $sent = 0;
      $actuallyNotSentDueToError = 0; // Neuer Zähler für echte Fehler
      $limitReachedCount = 0;       // Umbenannt von $limitReached für Klarheit
      $skippedDueToTypeOrReceiver = 0; // Neuer Zähler für übersprungene Mails
      $limit = null;

      // Versanddatum für das Mailing setzen, falls es das erste Mal ist, dass Mails gesendet werden
      // und es überhaupt Mails zum Senden gibt.
      $mailsToSend = $this->mailRepository->findMailsToSend($mailing->getUid()); // Query-Objekt

      if (count($mailsToSend) > 0 && $mailing->getStarted() === null) {
        $mailing->setStarted(new \DateTimeImmutable());
        // Wichtig: Diese Änderung am $mailing Objekt muss auch persistiert werden.
        // Wir machen das zusammen mit anderen Änderungen am Ende oder wenn $mailsToSend leer ist.
      }

      if ($this->request->hasArgument('mailingLimit')) {
        $limit = (int) $this->request->getArgument('mailingLimit');
      }

      if (count($mailsToSend) === 0) {
        $this->addModuleFlashMessage(
          'Es gibt aktuell keine vorbereiteten E-Mails für dieses Mailing zum Versenden.',
          'Keine Mails zu senden',
          'INFO' // String-Severity verwenden, wie in addModuleFlashMessage definiert
        );
        // Trotzdem den Status des Mailings aktualisieren, falls es z.B. vorher auf "senden" stand
      } else {
        foreach ($mailsToSend as $mail) { // Iteriere nur über Mails, die gesendet werden sollen
          // Die Prüfung auf type === 0 und Receiver ist jetzt in findMailsToSend() idealerweise schon enthalten.
          // Falls nicht, hier zur Sicherheit beibehalten, aber findMailsToSend sollte das leisten.
          if ($mail->getReceiver() === null) { // Sollte durch findMailsToSend eigentlich nicht passieren
            $skippedDueToTypeOrReceiver++;
            continue;
          }

          if ($limit !== null && $sent >= $limit) {
            $limitReachedCount++;
            // Nicht $skippedDueToTypeOrReceiver erhöhen, da dies ein Limit ist, kein Fehler/Typ-Problem
            continue; // Gehe zur nächsten Mail, aber zähle sie nicht als "error"
          }

          try {
            // $mail wird hier potenziell modifiziert (z.B. sent-Datum im MailGenerator)
            $updatedMail = $this->mailGenerator->createMailerMail($mail, $this->settings);
            // createMailerMail sollte das $mail Objekt direkt modifizieren oder das modifizierte zurückgeben.
            // Die Zuweisung $mail = $updatedMail; ist gut, falls ein neues Objekt zurückkommt.
            // Wenn $mail per Referenz geändert wird, ist sie optional.
            // Für Klarheit:
            $mail = $updatedMail;

            // Die folgenden Zeilen sind jetzt wahrscheinlich Teil von createMailerMail,
            // aber zur Sicherheit hier oder dort sicherstellen:
            $mail->setType(1); // Status "gesendet"
            $mail->setTstamp(new \DateTimeImmutable()); // Aktualisierungszeitpunkt
            $sent++;

          } catch (\Throwable $throwable) {
            // Logge den spezifischen Fehler
            $logMessage = 'Fehler: ' . $throwable->getMessage() .
              ' (Datei: ' . $throwable->getFile() .
              ', Zeile: ' . $throwable->getLine() . ')';
            $log = $this->logGenerator->createMailLog('M-Error', $mail, $logMessage);
            $mail->addLog($log);
            $mail->setType(-1); // Status "Fehler"
            $mail->setTstamp(new \DateTimeImmutable());
            $actuallyNotSentDueToError++;

            // Wichtig: Wenn ein Fehler hier den gesamten Prozess abbricht, müssen wir das genauer untersuchen.
            // Der try-catch sollte den Fehler abfangen und die Schleife weitermachen lassen.
            // Wenn es trotzdem abbricht, könnte der Fehler im Logging oder im mailRepository->update() liegen.
            // Oder die Exception ist so gravierend, dass sie nicht von Throwable abgefangen wird (sehr selten).
          }

          $this->mailRepository->update($mail); // Update jede Mail einzeln nach dem Versuch

          // Optionale Batch-Persistierung für Performance bei sehr vielen Mails
          // if (($sent + $actuallyNotSentDueToError) % 50 === 0) { // Alle 50 Mails persistieren
          //     $this->persistenceManager->persistAll();
          // }
        }
      }

      // Versand-Endzeitpunkt setzen (nur wenn tatsächlich etwas versucht wurde)
      if ($sent > 0 || $actuallyNotSentDueToError > 0 || count($mailsToSend) > 0) { // Nur wenn Mails vorhanden waren
        $mailing->setSent(new \DateTimeImmutable());
      }

      // // Status neu berechnen und Mailing aktualisieren
      // // Wir brauchen die aktuelle Anzahl der *noch* zu sendenden Mails für den neuen Status
      // $remainingToSendCount = $this->mailRepository->findMailsToSend($mailing->getUid())->count();
      // if (count($mailsToSend) > 0 || $mailing->getStatus() !== -1) { // Nur Status ändern, wenn vorher nicht schon "fertig" (oder Fehler)
      //   if ($remainingToSendCount > 0) {
      //     // Wenn Mails gesendet wurden, aber noch welche übrig sind, oder wenn Fehler auftraten
      //     // und noch welche übrig sind.
      //     $mailing->setStatus(3); // "Teilweise gesendet" oder "Wird gesendet"
      //   } else if ($actuallyNotSentDueToError > 0 && $sent === 0) {
      //     $mailing->setStatus(1); // Alle fehlgeschlagen
      //   } else {
      //     $mailing->setStatus(-1); // "Versand abgeschlossen" (oder alle erfolgreich / fehlgeschlagen und keine mehr übrig)
      //   }
      // }
      $this->mailingRepository->update($mailing); // Mailing-Objekt (mit neuem Status, started, sent Datum) aktualisieren

      // Persist aller Änderungen (Mails & Mailing) am Ende
      $this->persistenceManager->persistAll();

      // Feedback an den Redakteur (angepasste Zähler verwenden)
      if ($sent > 0) {
        $this->addModuleFlashMessage(
          sprintf('Es wurden %d Mails erfolgreich versandt!', $sent),
          'MAILS VERSANDT!', // Titel kürzer und prägnanter
          'OK'
        );
      }
      if ($limitReachedCount > 0) {
        $this->addModuleFlashMessage(
          sprintf(
            '%d weitere Mails wurden nicht versandt, da das Versand-Limit von %d erreicht wurde.',
            $limitReachedCount,
            $limit ?? 0 // Zeige das Limit an, falls gesetzt
          ),
          'LIMIT ERREICHT',
          'INFO'
        );
      }
      if ($actuallyNotSentDueToError > 0) {
        $this->addModuleFlashMessage(
          sprintf(
            '%d Mails konnten aufgrund von Fehlern nicht versandt werden! Bitte Logs prüfen.',
            $actuallyNotSentDueToError
          ),
          'VERSANDFEHLER AUFGETRETEN',
          'ERROR'
        );
      }
      if ($skippedDueToTypeOrReceiver > 0) { // Info über formal übersprungene Mails
        $this->addModuleFlashMessage(
          sprintf(
            '%d Mails wurden übersprungen (z.B. kein Empfänger oder falscher Typ vorab).',
            $skippedDueToTypeOrReceiver
          ),
          'MAILS ÜBERSPRUNGEN',
          'NOTICE'
        );
      }
      if ($sent === 0 && $actuallyNotSentDueToError === 0 && $limitReachedCount === 0 && count($mailsToSend) > 0) {
        // Fall abdecken, dass Mails da waren, aber keine gesendet/fehlerhaft/limitiert wurden
        // (sollte durch die obigen Bedingungen eigentlich nicht passieren, aber als Sicherheitsnetz)
        $this->addModuleFlashMessage(
          'Es wurden keine Mails versandt, obwohl Mails zum Senden vorhanden waren. (Limit: ' . ($limit ?? 'kein') . ')',
          'VERSAND INFO',
          'WARNING'
        );
      }

      return $this->redirect('statusUpdate', 'Mailing', null, ['mailing' => $mailing]);

    } elseif ($this->request->hasArgument('resend')) {
      $mailUid = (int) $this->request->getArgument('mail');
      $mail = $this->mailRepository->findByUid($mailUid);

      if ($mail !== null && in_array($mail->getType(), [1, -1], true)) {
        $mail = $this->mailGenerator
          ->createMailerMail($mail, $this->settings);
        $mail->setTstamp(new \DateTimeImmutable());
        $this->mailRepository->update($mail);

        $this->addModuleFlashMessage(
          'Die Email wurde erneut zugestellt!',
          'FABELHAFT • MAIL-KOPIE VERSANDT!',
          'OK'
        );

      } else {
        $this->addModuleFlashMessage(
          'Kopie nur möglich, wenn die Mail bereits einmal verschickt wurde.',
          'MAIL NICHT VORHANDEN!',
          'ERROR'
        );
      }

      // kein gültiger Vorgang
    } else {
      $this->addModuleFlashMessage(
        'Es wurden nicht genügend Daten übergeben. Es wurde nichts ausgeführt.',
        'DAS SOLLTE NICHT PASSIEREN!',
        'ERROR'
      );
    }

    // Zurück zur Mailing-Übersicht
    return $this->redirect('show', 'Mailing', null, ['mailing' => $mailing]);
  }

  /**
   * Kopiert ein bestehendes Mailing.
   *
   * @param Mailing $sourceMailing Das zu kopierende Mailing-Objekt
   * @TYPO3\CMS\Extbase\Annotation\IgnoreValidation("sourceMailing") // Nötig, wenn Objekt direkt per Link übergeben wird
   */
  public function copyMailingAction(Mailing $sourceMailing): ResponseInterface
  {
    $newMailing = new Mailing(); // Erstelle ein brandneues Mailing-Objekt

    // 1. Skalare und einfache Eigenschaften kopieren
    // Titel und Betreff leicht anpassen, um die Kopie zu kennzeichnen
    $newMailing->setSubject($sourceMailing->getSubject() . ' (Kopie)');

    if ($sourceMailing->getClient()) {
      $newMailing->setClient($sourceMailing->getClient()); // Annahme: Client ist eine To-One-Relation
    }
    $newMailing->setPreview($sourceMailing->getPreview());
    $newMailing->setType($sourceMailing->getType());
    $newMailing->setTitle($sourceMailing->getTitle());
    $newMailing->setAltSender($sourceMailing->getAltSender());
    $newMailing->setReplyName($sourceMailing->getReplyName());
    $newMailing->setReplyEmail($sourceMailing->getReplyEmail());
    $newMailing->setNoHeader($sourceMailing->isNoHeader());
    $newMailing->setNoSalutation($sourceMailing->isNoSalutation());
    $newMailing->setNoSignature($sourceMailing->isNoSignature());
    $newMailing->setPersonally($sourceMailing->isPersonally());
    $newMailing->setAltTemplate($sourceMailing->getAltTemplate());

    // 2. 'contents' (ObjectStorage) kopieren/klonen
    // Innerhalb deiner copyAction, für 'contents':
    if ($sourceMailing->getContents()) {
      $newContentsCollection = new ObjectStorage();
      foreach ($sourceMailing->getContents() as $originalContentItem) {
        /** @var \TYPO3\CMS\Extbase\DomainObject\AbstractEntity $originalContentItem */
        $clonedContentItem = clone $originalContentItem; // UID/PID werden durch __clone von AbstractEntity zurückgesetzt

        $newContentsCollection->attach($clonedContentItem);
      }
      $newMailing->setContents($newContentsCollection);
    }

    // 3. 'attachment' und 'header' kopieren/klonen (oft FileReferences)
    // Für FileReference-Objekte (FAL) bedeutet klonen, dass eine neue sys_file_reference
    // erstellt wird, die auf dieselbe sys_file (Datei) zeigt.

    // Attachment
    $sourceAttachment = $sourceMailing->getAttachment(); // Annahme: Getter existiert
    if ($sourceAttachment instanceof FileReference) { // Wenn es ein einzelner FileReference ist
      /** @var FileReference $clonedAttachment */
      $clonedAttachment = clone $sourceAttachment;
      $newMailing->setAttachment($clonedAttachment); // Annahme: Setter existiert
    } elseif ($sourceAttachment instanceof ObjectStorage) { // Wenn es eine ObjectStorage von FileReferences ist
      $newAttachmentCollection = new ObjectStorage();
      /** @var FileReference $originalRef */
      foreach ($sourceAttachment as $originalRef) {
        $clonedRef = clone $originalRef;
        $newAttachmentCollection->attach($clonedRef);
      }
      $newMailing->setAttachment($newAttachmentCollection);
    }

    // Header (analog zum Attachment)
    $sourceHeader = $sourceMailing->getHeader(); // Annahme: Getter existiert
    if ($sourceHeader instanceof FileReference) {
      /** @var FileReference $clonedHeader */
      $clonedHeader = clone $sourceHeader;
      $newMailing->setHeader($clonedHeader); // Annahme: Setter existiert
    } elseif ($sourceHeader instanceof ObjectStorage) {
      $newHeaderCollection = new ObjectStorage();
      /** @var FileReference $originalRef */
      foreach ($sourceHeader as $originalRef) {
        $clonedRef = clone $originalRef;
        $newHeaderCollection->attach($clonedRef);
      }
      $newMailing->setHeader($newHeaderCollection);
    }

    // 4. Statusfelder zurücksetzen (nicht kopieren)
    $newMailing->setStatus(0);


    // 5. Neues Mailing persistieren
    $this->mailingRepository->add($newMailing);
    $this->persistenceManager->persistAll();


    $this->addModuleFlashMessage(
      'Das Mailing "' . htmlspecialchars($sourceMailing->getSubject() ?? '') .
      '" wurde erfolgreich als "' . htmlspecialchars($newMailing->getSubject() ?? '') . '" kopiert.',
      'Mailing kopiert',
      'OK' // String-basierte Severity deiner Helfermethode
    );

    // Weiterleiten zur Liste oder zur Bearbeitungsansicht der Kopie
    // return $this->redirect('list'); // Oder 'edit', 'show' mit ['mailing' => $newMailing]
  }


  /**
   * action delete
   *
   * @param Mailing $mailing
   * @return void
   */
  public function deleteAction(Mailing $mailing): ResponseInterface
  {
    // Anzahl bereits versandter Mails ermitteln
    $sentCount = $this->mailRepository->findSentMails($mailing->getUid())->count();
    // Wurde der Versand bereits gestartet? (NULL = noch nicht gestartet)
    $started = $mailing->getStarted();

    if ($sentCount === 0 && $started === null) {
      // Keine versandten Mails und Versand noch nicht gestartet → löschen erlaubt
      $this->mailingRepository->remove($mailing);

      $this->addModuleFlashMessage(
        'Das Mailing konnte gelöscht werden!',
        'MAILING GELÖSCHT!',
        'OK'
      );

      // Zur Übersichtsliste zurückkehren
      return $this->redirect('list', 'Mailing');
    }

    // Ansonsten: löschen nicht möglich
    $this->addModuleFlashMessage(
      'Sobald der Versand angestoßen wurde kann ein Mailing nicht mehr gelöscht werden.',
      'LÖSCHUNG NICHT MÖGLICH!',
      'ERROR'
    );

    // Zur Detailansicht zurückkehren
    return $this->redirect('show', 'Mailing', null, ['mailing' => $mailing]);
  }




  /**
   * action deleteMails
   *
   * @param Mailing $mailing
   * @return void
   */
  public function deleteMailsAction(Mailing $mailing): ResponseInterface
  {
    // Alle noch nicht versandten Mails holen
    $mailsToSend = $this->mailRepository->findMailsToSend($mailing->getUid());
    $countToSend = $mailsToSend->count();

    if ($countToSend > 0) {
      $deleted = 0;
      foreach ($mailsToSend as $mail) {
        if (!$mail->getSent()) {
          $this->mailRepository->remove($mail);
        }
        $deleted++;
      }

      // Rückmeldung an den Redakteur
      $this->addModuleFlashMessage(
        sprintf('%d Empfänger wurden gelöscht!', $deleted),
        'EMPFÄNGER GELÖSCHT!'
      );

      // Status des Mailings aktualisieren
      $remainingSent = $this->mailRepository->findSentMails($mailing->getUid())->count();
      if ($remainingSent > 0) {
        // Es gibt bereits verschickte Mails → Archivierer‐Status
        $mailing->setStatus(-1);
      } else {
        // Keine Empfänger mehr → wieder "vorbereitet"
        $mailing->setStatus(0);
      }
      $this->mailingRepository->update($mailing);

    } else {
      // Keine Empfänger zum Löschen
      $this->addModuleFlashMessage(
        'Es sind keine Empfänger vorbereitet. Bereits versandte Mails können nicht gelöscht werden.',
        'LÖSCHUNG NICHT MÖGLICH!',
        'ERROR'
      );
    }

    // Zurück zur Detail‐Ansicht
    return $this->redirect('show', 'Mailing', null, ['mailing' => $mailing]);
  }


  /**
   * action statusUpdate
   *
   * @param Mailing $mailing
   * @return void
   */
  public function statusUpdateAction(Mailing $mailing): ResponseInterface
  {
    // vorbereitete und bereits versandte Mails holen
    $mailsToSend = $this->mailRepository->findMailsToSend($mailing->getUid());
    $sentMails = $this->mailRepository->findSentMails($mailing->getUid());

    $countToSend = $mailsToSend->count();
    $countSent = $sentMails->count();
    $started = $mailing->getStarted() !== null;

    // Status‐Logik
    if ($countToSend > 0 && !$started && $countSent === 0) {
      // noch nicht gestartet, aber Empfänger vorbereitet
      $mailing->setStatus(1);
    } elseif ($countToSend > 0 && $started && $countSent > 0) {
      // Versand läuft gerade
      $mailing->setStatus(3);
    } elseif ($started && $countSent > 0 && $countToSend === 0) {
      // Versand abgeschlossen
      $mailing->setStatus(-1);
    } else {
      // kein Empfänger, kein Versand
      $mailing->setStatus(0);
    }

    // in Repository speichern
    $this->mailingRepository->update($mailing);

    // Feedback an den Redakteur
    $this->addModuleFlashMessage(
      'Der Status wurde erfolgreich upgedated!',
      'STATUS UPGEDATED!'
    );

    // zurück zur Detail‐Ansicht
    return $this->redirect('show', 'Mailing', null, ['mailing' => $mailing]);
  }


}
