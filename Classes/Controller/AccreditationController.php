<?php

declare(strict_types=1);

namespace BucheggerOnline\Publicrelations\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\JsonResponse;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;

use BucheggerOnline\Publicrelations\Domain\Model\Dto\EmConfiguration;
use BucheggerOnline\Publicrelations\Utility\GeneralFunctions;
use BucheggerOnline\Publicrelations\Utility\LogGenerator;
use BucheggerOnline\Publicrelations\Utility\MailGenerator;

use BucheggerOnline\Publicrelations\Service\AccreditationService;
use BucheggerOnline\Publicrelations\DataResolver\AccreditationDataResolver;
use BucheggerOnline\Publicrelations\Domain\Repository\AccreditationRepository;
use BucheggerOnline\Publicrelations\Domain\Model\Accreditation;

use Allegria\AcDistribution\Service\DistributionService;
use Allegria\AcDistribution\Service\MailBuildService;
use Allegria\AcDistribution\Service\SmtpService;

use BucheggerOnline\Publicrelations\Domain\Repository\EventRepository;
use BucheggerOnline\Publicrelations\Domain\Model\Event;
use BucheggerOnline\Publicrelations\Domain\Repository\InvitationRepository;
use BucheggerOnline\Publicrelations\Domain\Model\Invitation;
use BucheggerOnline\Publicrelations\Domain\Repository\SysCategoryRepository;
use BucheggerOnline\Publicrelations\Domain\Model\SysCategory;
use BucheggerOnline\Publicrelations\Domain\Repository\TtAddressRepository;
use BucheggerOnline\Publicrelations\Domain\Model\TtAddress;
use BucheggerOnline\Publicrelations\Domain\Repository\AdditionalfieldRepository;
use BucheggerOnline\Publicrelations\Domain\Model\Additionalfield;
use BucheggerOnline\Publicrelations\Domain\Repository\AdditionalanswerRepository;
use BucheggerOnline\Publicrelations\Domain\Model\Additionalanswer;

use BucheggerOnline\Publicrelations\Domain\Model\Dto\PreviewSelector;

/**
 * AccreditationController
 */
class AccreditationController extends AbstractPublicrelationsController
{
  public function __construct(
    private readonly PersistenceManager $persistenceManager,
    private readonly AccreditationRepository $accreditationRepository,
    private readonly EventRepository $eventRepository,
    private readonly InvitationRepository $invitationRepository,
    private readonly AdditionalfieldRepository $additionalfieldRepository,
    private readonly AdditionalanswerRepository $additionalanswerRepository,
    private readonly SysCategoryRepository $sysCategoryRepository,
    private readonly TtAddressRepository $ttAddressRepository,
    private readonly EmConfiguration $emConfiguration,
    private readonly MailGenerator $mailGenerator,
    private readonly LogGenerator $logGenerator,
    private readonly GeneralFunctions $generalFunctions,
    private readonly AccreditationService $accreditationService,
    private readonly DistributionService $distributionService,
    private readonly AccreditationDataResolver $accreditationDataResolver,
    private readonly MailBuildService $mailBuildService,
    private readonly SmtpService $smtpService
  ) {
  }

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

  public function listAction(): ResponseInterface
  {
    $accreditations = $this->accreditationRepository->findAll();
    $this->view->assign('accreditations', $accreditations);

    $this->setModuleTitle('Akkreditierungen');
    return $this->backendResponse();
  }

  public function showAction(Accreditation $accreditation): ResponseInterface
  {
    $this->view->assign('accreditation', $accreditation);

    $this->setModuleTitle('Akkreditierung zu ' . (isset($event) ? (' – ' . $event->getTitle()) : ''));
    return $this->backendResponse();
  }

  public function printAction(Accreditation $accreditation, string $content): ResponseInterface
  {
    $this->view->assignMultiple([
      'accreditation' => $accreditation,
      'content' => $content,
    ]);
    if ($alt = $accreditation->getInvitationType()?->getFromName()) {
      $this->view->assign('altSender', $alt);

      $this->setModuleTitle('Druckbare Liste' . (isset($event) ? (' – ' . $event->getTitle()) : ''));
      return $this->backendResponse();
    }
  }

  public function previewAction(Event $event, string $template = 'Accreditation', string $content = 'approve'): ResponseInterface
  {
    $guestOutput = [
      'gender' => 1,
      'title' => 'Dr.',
      'firstName' => 'Marianne',
      'lastName' => 'Musterfrau',
      'fullName' => 'Dr. Marianne Musterfrau'
    ];
    $invType = [
      'noHeader' => false,
      'header' => $event->getClient()->getLogo()
    ];
    $this->view->assignMultiple([
      'event' => $event,
      'guestOutput' => $guestOutput,
      'accreditation' => [
        'uid' => 'MUSTER',
        'event' => $event,
        'invitationType' => $invType,
        'guestOutput' => $guestOutput,
        'noHeader' => false,
        'header' => $event->getClient()->getLogo()
      ],
      'content' => $content
    ]);

    $this->setModuleTitle('Einladungsvorschau' . (isset($event) ? (' – ' . $event->getTitle()) : ''));
    return $this->backendResponse();
  }

  public function newAction(Event $event, int $invitation = 0, ?Accreditation $newAccreditation = null): ResponseInterface
  {

    $notesItems = $this->sysCategoryRepository->findByParentUid(
      $this->emConfiguration->getAccreditationNotesRootUid()
    );
    $this->view->assignMultiple([
      'notesItems' => $notesItems,
      'event' => $event,
      'invitation' => $invitation,
      'newAccreditation' => $newAccreditation
    ]);

    $this->setModuleTitle('Neue Akkreditierung' . (isset($event) ? (' – ' . $event->getTitle()) : ''));
    return $this->backendResponse();
  }

  public function newWizzardAction(Event $event): ResponseInterface
  {

    $notesItems = $this->sysCategoryRepository->findByParentUid(
      $this->emConfiguration->getAccreditationNotesRootUid()
    );
    $this->view->assignMultiple([
      'notesItems' => $notesItems,
      'event' => $event,
      'loadNewAccr' => range(1, 10)
    ]);

    $this->setModuleTitle('Akkr.-Wizzard' . (isset($event) ? (' – ' . $event->getTitle()) : ''));
    return $this->backendResponse();
  }

  public function invitationManagerAction(Event $event, array $invitations = []): ResponseInterface
  {
    $this->view->assignMultiple([
      'event' => $event,
      'invitations' => $invitations
    ]);

    $this->setModuleTitle('1/3 Einladungsmanager – Einladungen ' . (isset($event) ? ('zu ' . $event->getTitle()) : '') . 'zuordnen');
    return $this->backendResponse();
  }

  public function invitationManagerCategoriesAction(Event $event, array $invitations): ResponseInterface
  {
    $listType = $invitations['listType'] ?? '';
    $mailingLists = [];
    $clientUidList = '';

    // Bestimme die Daten basierend auf dem neuen listType
    switch ($listType) {
      case 'internal':
        $mailingLists = $this->sysCategoryRepository->findByParentUid($this->emConfiguration->getContactRootUid());
        break;
      case 'client':
        $clientUids = [];

        // 1. UID des Haupt-Clients hinzufügen
        $mainClient = $event->getClient();
        if ($mainClient) {
          $clientUids[] = $mainClient->getUid();
        }

        // 2. UIDs der zugewiesenen Partner hinzufügen
        // Wir gehen davon aus, dass getPartners() eine Sammlung von Objekten zurückgibt
        $partners = $event->getPartners();
        if (!empty($partners)) {
          foreach ($partners as $partner) {
            // Füge die UID des Partners hinzu.
            // Jeder Partner wird hier als potenzieller Client behandelt.
            $clientUids[] = $partner->getUid();
          }
        }

        // 3. UIDs bereinigen: Duplikate und ungültige Werte (z.B. 0) entfernen
        $uniqueUids = array_filter(array_unique($clientUids));

        // Die Variable für die finale, komma-separierte Liste
        $clientUidList = implode(',', $uniqueUids);

        // Optional: Wenn du die $mailingLists basierend auf ALLEN diesen Clients laden willst
        if (!empty($uniqueUids)) {
          // Annahme: Dein Repository hat eine Methode, die mit einem Array von UIDs umgehen kann.
          // Falls nicht, müsstest du eine eigene Repository-Methode erstellen.
          // Beispiel: findByClientUids(array $uids)
          $mailingLists = $this->sysCategoryRepository->findByProperty('client', $uniqueUids);

        } else {
          $mailingLists = [];
        }
        break;
      default:
        // Standardfall
        $mailingLists = [];
        break;
    }

    // Ergebnis an das Template übergeben
    $this->view->assignMultiple([
      'event' => $event,
      'mailingLists' => $mailingLists,
      'client' => $clientUidList,
      'invitations' => $invitations,
      'listType' => $listType,
    ]);

    $this->setModuleTitle('2/3 Kontaktauswahl – Einladungen ' . (isset($event) ? ('zu ' . $event->getTitle()) : '') . 'zuordnen');
    return $this->backendResponse();
  }

  // public function invitationManagerCategoriesAction(Event $event, array $invitations): ResponseInterface
  // {

  //   $listType = $invitations['listType'] ?? '';

  //   // match liefert ein assoziatives Array mit mailingLists, contacts und pid
  //   $result = match ($listType) {
  //     '1' => [
  //       'mailingLists' => $this->sysCategoryRepository->findByParentUid($this->emConfiguration->getCelebRootUid()),
  //       'contacts' => $this->ttAddressRepository->findByPage($this->emConfiguration->getCelebPid()),
  //       'pid' => $this->emConfiguration->getCelebPid(),
  //     ],
  //     '2' => [
  //       'mailingLists' => $this->sysCategoryRepository->findByParentUid($this->emConfiguration->getJournalistsRootUid()),
  //       'contacts' => $this->ttAddressRepository->findByPage($this->emConfiguration->getJournalistsPid()),
  //       'pid' => $this->emConfiguration->getJournalistsPid(),
  //     ],
  //     '3' => [
  //       'mailingLists' => $this->sysCategoryRepository->findByParentUid($this->emConfiguration->getMailingListsRootUid()),
  //       'contacts' => $this->ttAddressRepository->findByPage($this->emConfiguration->getMailingListsPid()),
  //       'pid' => $this->emConfiguration->getMailingListsPid(),
  //     ],
  //     '4' => [
  //       'mailingLists' => array_values(array_filter(
  //         $this->eventRepository->findAll(),
  //         fn(Event $e): bool => $e->getAccreditations()->count() > 0
  //       )),
  //       'contacts' => null,
  //       'pid' => null,
  //     ],
  //     '5' => [
  //       'mailingLists' => $this->sysCategoryRepository->findByProperty('client', 0, false),
  //       'contacts' => null,
  //       'pid' => null,
  //     ],
  //     default => [
  //       'mailingLists' => [],
  //       'contacts' => null,
  //       'pid' => null,
  //     ],
  //   };

  //   // Ergebnis an das Template übergeben
  //   $this->view->assignMultiple([
  //     'event' => $event,
  //     'mailingLists' => $result['mailingLists'],
  //     'contacts' => $result['contacts'],
  //     'pid' => $result['pid'],
  //     'invitations' => $invitations,
  //   ]);

  //   $this->setModuleTitle('2/3 Kontaktauswahl – Einladungen ' . (isset($event) ? ('zu ' . $event->getTitle()) : '') . 'zuordnen');
  //   return $this->backendResponse();
  // }


  public function invitationManagerSummaryAction(Event $event, array $invitations = []): ResponseInterface
  {
    // Konfiguration laden (falls nötig)
    \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\BucheggerOnline\Publicrelations\Domain\Model\Dto\EmConfiguration::class);

    // 1. UIDs aus Mailing-Listen sammeln
    $checkedMailingListUids = [];
    if (!empty($invitations['mailingLists']) && is_iterable($invitations['mailingLists'])) {
      foreach ($invitations['mailingLists'] as $uid => $c) {
        if ((string) $c === '1') {
          $checkedMailingListUids[] = (int) $uid;
        }
      }
    }

    $allGuestUids = [];
    if (!empty($checkedMailingListUids)) {
      // Annahme: findUidsByCategoryUids holt alle verknüpften tt_address UIDs in einer Abfrage
      $contactUidsFromMailingLists = $this->ttAddressRepository->findUidsByCategoryUids($checkedMailingListUids);
      $allGuestUids = array_merge($allGuestUids, $contactUidsFromMailingLists);
    }

    // 2. UIDs aus manueller Auswahl hinzufügen
    if (!empty($invitations['manualReceiver']) && is_iterable($invitations['manualReceiver'])) {
      $manualReceiverUids = array_map('intval', $invitations['manualReceiver']);
      $allGuestUids = array_merge($allGuestUids, $manualReceiverUids);
    }

    // 3) Event-basiert (Akkreditierungen) hinzufügen
    if (isset($invitations['event'])) {
      $eventFilter = [
        'event' => $invitations['event'],
        'status' => '1',
        'guestType' => $invitations['guestType'] ?? '',
        'facie' => $invitations['facie'] ?? '',
        'tickets' => $invitations['tickets'] ?? '',
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
      $allGuestUids = array_merge($allGuestUids, $accreditationUids);
    }

    $allGuestUids = array_unique($allGuestUids);

    // 3. Fehlende Auswahl abfangen
    if (empty($allGuestUids)) {
      $this->addModuleFlashMessage(
        'Es wurden keine Verteiler oder Kontakte ausgewählt.',
        'AUSWAHL LEER!',
        'WARNING'
      );
      return $this->redirect('invitationManager', 'Accreditation', null, ['event' => $event]);
    }

    // 4. Alle Kontaktdaten in einem einzigen Aufruf holen
    // Annahme: findForSummaryByUids gibt nur die benötigten Felder als Array zurück
    $receivers = $this->ttAddressRepository->findForSummaryByUids($allGuestUids);

    // // 5. Duplikate (nach E-Mail) entfernen
    // $guestType = $invitations['guestType'] ?? '';
    // if ($guestType === '1') {
    //   // Bei Gast-Typ 1 nach UID deduplizieren (schon durch array_unique erledigt)
    // } else {
    //   // Bei anderen Typen nach E-Mail deduplizieren
    //   $receivers = $this->unique_multidim_array($receivers, 'email');
    // }

    // 6. View-Variablen setzen
    $this->view->assignMultiple([
      'event' => $event,
      'invitations' => array_merge(
        $invitations,
        ['receiver' => $receivers]
      ),
    ]);

    $this->setModuleTitle('3/3 Zusammenfassung – Einladungen ' . (isset($event) ? ('zu ' . $event->getTitle()) : '') . 'zuordnen');
    return $this->backendResponse();
  }

  // public function invitationPreviewAction(Event $event): ResponseInterface
  // {
  //   if (!$event->isInvitationAllowed()) {
  //     $this->addModuleFlashMessage(
  //       'Anhand der aktuellen Einstellungen kann keine Einladung versandt werden.',
  //       'TEST NICHT MÖGLICH!',
  //       'ERROR'
  //     );
  //     return $this->redirect('show', 'Event', ['event' => $event]);
  //   }
  //   foreach ($event->getInvitations() as $inv) {
  //     $acr = new Accreditation();
  //     $acr->setGuest($this->ttAddressRepository->findByUid(1));
  //     $acr->setEvent($event);
  //     $acr->setInvitationType($inv);
  //     $this->mailGenerator
  //       ->createAccreditationMail('AI-Email-PV', $acr, $this->settings);
  //     $this->addModuleFlashMessage(
  //       'Test-Einladungen verschickt.',
  //       'TEST-EINLADUNG VERSANDT!'
  //     );
  //   }
  //   return $this->redirect('show', 'Event', null, ['event' => $event]);
  // }

  public function createAction(Event $event, ?Accreditation $newAccreditation = null): ResponseInterface
  {
    // Welcher Formular-Typ wurde übergeben?
    $formType = $this->request->hasArgument('formType')
      ? (string) $this->request->getArgument('formType')
      : '';

    if ($formType === '') {
      // kein Formular → zurück zur Homepage
      $this->redirectToUri('https://www.allegria.at/');
    }

    // dispatch nach Typ
    return match ($formType) {
      'singleBackend' => $this->handleSingleBackend($event, $newAccreditation),
      'wizzardBackend' => $this->handleWizardBackend($event),
      'invitationManager' => $this->handleInvitationManager($event),
      'manualFrontend' => $this->handleManualFrontend($event),
      default => $this->redirectToUri('https://www.allegria.at/'),
    };
  }

  /**
   * Fall 1: Einzelakkreditierung im Backend
   */
  protected function handleSingleBackend(Event $event, Accreditation $newAccreditation): ResponseInterface
  {
    // Gast neu anlegen?
    $guestArg = $this->request->getArgument('guest') ?? '';
    if ($guestArg === 'new') {
      // Daten zusammenstellen
      $firstName = trim(ucwords($this->request->getArgument('newContact.firstName')));
      $middleName = trim(ucwords($this->request->getArgument('newContact.middleName')));
      $lastName = trim(ucwords($this->request->getArgument('newContact.lastName')));
      $contactData = [
        'pid' => $this->emConfiguration->getContactPid(),
        'cruserId' => $this->getCurrentBackendUserUid(),
        'company' => $this->request->getArgument('newContact.company'),
        'gender' => $this->request->getArgument('newContact.gender'),
        'title' => trim($this->request->getArgument('newContact.title')),
        'firstName' => $firstName,
        'middleName' => $middleName,
        'lastName' => $lastName,
        'name' => preg_replace('/\s+/', ' ', "$firstName $middleName $lastName"),
        'email' => trim(strtolower($this->request->getArgument('newContact.email'))),
        'phone' => trim($this->request->getArgument('newContact.phone')),
        'mobile' => trim($this->request->getArgument('newContact.mobile')),
      ];
      $guest = $this->generalUtility->createContact($contactData);
      $newAccreditation->setGuest($guest);
    }
    // bestehender Gast?
    elseif ($guestArg !== 'manual' && $guestArg !== '0') {
      $guest = $this->ttAddressRepository->findByUid((int) $guestArg);
      $duplicate = $this->accreditationRepository->findGuestByEvent($guest->getUid(), $event->getUid());
      if ($duplicate) {
        $this->addModuleFlashMessage(
          'Der Gast ist bereits akkreditiert!',
          'ES DÜRFEN KEINE DUPLIKATE ERSTELLT WERDEN!',
          'ERROR'
        );
        return $this->redirect('show', 'Event', null, ['event' => $event]);
      }
      $newAccreditation->setGuest($guest);
    }

    // Zusätzliche Felder verarbeiten
    if ($this->request->hasArgument('additionalAnswers'))
      foreach ($this->request->getArgument('additionalAnswers') ?? [] as $fieldUid => $value) {
        $content = '';
        $additionalField = $this->additionalfieldRepository->findByUid((int) $fieldUid);
        $type = $additionalField->getType();

        if ($type === 6 && is_array($value)) {
          foreach ($value as $k => $v) {
            $content .= "$k|$v\n";
          }
        } elseif ($type === 4 && is_array($value)) {
          foreach ($value as $v) {
            $content .= "$v\n";
          }
        } else {
          $content = (string) $value;
        }

        $answer = new Additionalanswer();
        $answer->setType($type);
        $answer->setValue($content);
        $answer->setField($additionalField);
        $answer->setAccreditation($newAccreditation);
        $newAccreditation->addAdditionalanswer($answer);
      }

    // Log erstellen
    $log = $this->logGenerator
      ->createAccreditationLog('A-CR1', $newAccreditation);
    $newAccreditation->addLog($log);

    // Mail versenden?
    $guestEmail = $newAccreditation->getGuestOutput()['email'] ?? '';
    if ($guestEmail !== '' && $this->request->getArgument('mails') === '1') {
      $mailOk = $this->sendDistributionMail($newAccreditation, 'approve');
      if ($mailOk)
        $this->addModuleFlashMessage('Eine Akkreditierungsbestätigung ging an den Gast.', 'EINLADUNG BESTÄTIGT!');
    } else {
      $this->addModuleFlashMessage(
        'Der Gast wurde nicht verständigt! Bitte manuell erledigen.',
        'GAST WURDE NICHT VERSTÄNDIGT!',
        'ERROR'
      );
    }

    // Datensatz speichern
    if (!$newAccreditation->getGuest()?->isMailingExclude()) {
      $this->accreditationRepository->add($newAccreditation);
      if ($this->request->hasArgument('invitation')) {
        $this->addModuleFlashMessage(
          'Der Gast wurde erfolgreich auf die Einladungsliste gesetzt, ' .
          $newAccreditation->getTicketsWish() . ' Tickets wurden vorgemerkt!',
          'EINLADUNG ERSTELLT!'
        );
      } else {
        $this->addModuleFlashMessage(
          'Der Gast wurde erfolgreich akkreditiert, ' .
          $newAccreditation->getTicketsApproved() . ' Tickets wurden eingetragen!',
          'AKKREDITIERUNG ERSTELLT!'
        );
      }
    } else {
      $this->addModuleFlashMessage(
        'Der Gast ' . $newAccreditation->getGuestOutput()['name'] .
        ' will keine Mailings mehr erhalten, es wurde keine Akkreditierung/Einladung erstellt!',
        'AKKREDITIERUNG KONNTE NICHT ERSTELLT WERDEN!',
        'ERROR'
      );
    }

    // $this->handleDuplicateCheck($event);

    return $this->redirect('show', 'Event', null, ['event' => $event]);
  }

  /**
   * Fall 2: Wizard-Backend
   *
   * @param Event $event
   * @return void
   */
  protected function handleWizardBackend(Event $event): ResponseInterface
  {
    // 1. Rohdaten holen und als Array sicherstellen
    $rawSettings = $this->request->getArgument('settings') ?? [];
    $settings = is_array($rawSettings) ? $rawSettings : [];

    // 2. Gruppen-Defaults (falls im einzelnen Datensatz nichts übergeben wird)
    $defaultGuestType = isset($settings['guestType']) && $settings['guestType'] !== '' ? (int) $settings['guestType'] : 0;
    $defaultTicketsApproved = isset($settings['ticketsApproved']) && $settings['ticketsApproved'] !== '' ? (int) $settings['ticketsApproved'] : 0;
    $defaultProgram = isset($settings['program']) && $settings['program'] !== '' ? (int) $settings['program'] : 0;
    $defaultPass = isset($settings['pass']) && $settings['pass'] !== '' ? (int) $settings['pass'] : 0;
    $defaultNotes = $settings['notes'] ?? '';
    $defaultInvitationType = isset($settings['invitationType']) && $settings['invitationType'] !== '' ? (int) $settings['invitationType'] : null;
    $defaultSeats = $settings['seats'] ?? '';
    $defaultTickets = $settings['tickets'] ?? '';

    // 3. Checkbox: sollen Bestätigungsmails versandt werden?
    $sendMails = $this->request->hasArgument('mails') && $this->request->getArgument('mails') === '1';

    // 4. Notizen-Kategorien sammeln (nur wenn Array)
    $notesSelect = [];
    if (isset($settings['notesSelect']) && is_array($settings['notesSelect'])) {
      foreach ($settings['notesSelect'] as $catId) {
        $notesSelect[] = $this->sysCategoryRepository->findByUid((int) $catId);
      }
    }

    // 5. Nur valide neue Akkreditierungen sammeln
    $toGenerate = [];
    foreach ($settings['newAccreditations'] ?? [] as $entry) {
      if (
        !empty($entry['guest'] ?? '')
        || !empty($entry['company'] ?? '')
        || !empty($entry['firstName'] ?? '')
        || !empty($entry['lastName'] ?? '')
      ) {
        $toGenerate[] = $entry;
      }
    }

    // 6. Verarbeiten
    $created = 0;
    $ticketsTotal = 0;
    foreach ($toGenerate as $v) {
      $akk = new Accreditation();

      // Basis
      $akk->setEvent($event);
      $akk->setStatus(1);

      // a) Gast-Fall
      if (!empty($v['guest'] ?? '')) {
        $guest = $this->ttAddressRepository->findByUid((int) $v['guest']);
        $dup = $this->accreditationRepository->findGuestByEvent(
          $guest->getUid(),
          $event->getUid()
        );
        if ($dup) {
          $this->addModuleFlashMessage(
            $guest->getName() . ' ist bereits akkreditiert!',
            'ES DÜRFEN KEINE DUPLIKATE ERSTELLT WERDEN!',
            'ERROR'
          );
          continue;
        }
        $akk->setGuest($guest);

        // guestType
        if (isset($v['guestType']) && $v['guestType'] !== '') {
          $akk->setGuestType((int) $v['guestType']);
        } else {
          $akk->setGuestType($defaultGuestType);
        }

        // ticketsApproved
        if (isset($v['ticketsApproved']) && $v['ticketsApproved'] !== '') {
          $akk->setTicketsApproved((int) $v['ticketsApproved']);
        } else {
          $akk->setTicketsApproved($defaultTicketsApproved);
        }

        // b) Manueller-Fall
      } else {
        $akk->setGuestType($defaultGuestType);
        $akk->setTicketsApproved($defaultTicketsApproved);
        $akk->setGender($v['gender'] ?? '');
        $akk->setTitle(trim($v['title'] ?? ''));
        $akk->setMedium(trim($v['company'] ?? ''));
        $akk->setFirstName(trim(ucwords($v['firstName'] ?? '')));
        $akk->setLastName(trim(ucwords($v['lastName'] ?? '')));
      }

      // 7. Gruppen-Defaults ergänzen
      $akk->setProgram($defaultProgram);
      $akk->setPass($defaultPass);
      $akk->setNotes($defaultNotes);

      if ($defaultInvitationType !== null) {
        $invTypeObj = $this->invitationRepository->findByUid($defaultInvitationType);
        $akk->setInvitationType($invTypeObj);
      }
      $akk->setSeats($defaultSeats);
      $akk->setTickets($defaultTickets);

      // 8. Notizen-Kategorien
      foreach ($notesSelect as $noteCat) {
        $akk->addNotesSelect($noteCat);
      }

      // 9. Log und Speichern
      $log = $this->logGenerator
        ->createAccreditationLog('A-CR2', $akk);
      $akk->addLog($log);

      $this->accreditationRepository->add($akk);

      // 10. Bestätigungsmail, falls gewünscht
      if ($sendMails && $akk->getGuestOutput()['email'] ?? '' !== '' && $akk->getGuestOutput()['email'] !== 'noreply@allegria.at') {
        $this->sendDistributionMail($akk, 'approve');
      }

      $created++;
      $ticketsTotal += $akk->getTicketsApproved();
    }

    // 11. Feedback
    if ($created > 0) {
      $this->addModuleFlashMessage(
        sprintf('Es wurden %d Gäste mit %d Tickets in die Gästeliste eingetragen.', $created, $ticketsTotal),
        'FABELHAFT • AKKREDITIERUNGEN ERSTELLT!'
      );
    }

    // $this->handleDuplicateCheck($event);

    // 12. Zurück zur Event-Ansicht
    return $this->redirect('show', 'Event', null, ['event' => $event]);
  }

  /**
   * Fall 3: Invitation-Manager
   */
  protected function handleInvitationManager(Event $event): ResponseInterface
  {
    $invitationsInput = $this->request->getArgument('invitations') ?? [];
    $selectedGuestUidsString = $invitationsInput['selectedGuestUids'] ?? '';

    // UIDs aus dem komma-separierten String extrahieren
    $selectedGuestUids = GeneralUtility::intExplode(',', $selectedGuestUidsString, true);

    if (empty($selectedGuestUids)) {
      $this->addModuleFlashMessage('Keine Gäste ausgewählt.', 'KEINE AKTION', 'INFO');
      return $this->redirect('show', 'Event', null, ['event' => $event]);
    }

    // 1. Alle benötigten Gast-Daten in einem Rutsch abrufen (nur die notwendigen Felder)
    // findGuestsForBulkProcessing holt nur die benötigten Felder als Array zurück, ohne Hydration.
    $guestsData = $this->ttAddressRepository->findGuestsForBulkProcessing($selectedGuestUids);
    $guestsById = array_column($guestsData, null, 'uid');

    // 2. Bestehende Akkreditierungen für diese Gäste und dieses Event auf einmal prüfen
    $existingAccreditationGuestUids = $this->accreditationRepository->findGuestUidsByEvent($selectedGuestUids, $event->getUid());
    $isAlreadyAccredited = array_flip($existingAccreditationGuestUids);

    // 3. InvitationType einmalig laden (als UID, um Hydration zu vermeiden)
    $invitationTypeUid = (int) ($invitationsInput['invitationType'] ?? 0);

    $tcaUpdates = [];
    $createdCount = 0;
    $duplicateNames = [];
    $excludedNames = [];
    $logData = [];

    foreach ($selectedGuestUids as $guestUid) {
      $guestData = $guestsById[$guestUid] ?? null;

      if (!$guestData) {
        continue; // Gast wurde nicht gefunden, ignorieren
      }

      // Hilfsfunktion zur Erstellung des Anzeigenamens
      $guestName = $this->formatGuestName($guestData);

      // Prüfung 1: Ist der Gast von Mailings ausgeschlossen?
      if ((int) $guestData['mailing_exclude'] === 1) {
        $excludedNames[] = $guestName;
        continue;
      }

      // Prüfung 2: Gibt es bereits eine Akkreditierung für dieses Event?
      if (isset($isAlreadyAccredited[$guestUid])) {
        $duplicateNames[] = $guestName;
        continue;
      }

      // Gast ist nicht ausgeschlossen und kein Duplikat -> DataHandler-Array erstellen
      $newAccreditationData = [
        'pid' => $event->getPid(),
        'event' => $event->getUid(),
        'status' => 0,
        'invitation_status' => 0,
        'type' => 2,
        'guest_type' => (int) ($invitationsInput['guestType'] ?? 0),
        'tickets_wish' => (int) ($invitationsInput['ticketsWish'] ?? 0),
        'guest' => $guestUid,
        'invitation_type' => $invitationTypeUid,
      ];

      $tempId = 'NEW_' . $guestUid;
      $tcaUpdates['tx_publicrelations_domain_model_accreditation'][$tempId] = $newAccreditationData;
      $logData[$tempId] = ['logCode' => 'AI-CR2'];
      $createdCount++;
    }

    // Alle Änderungen auf einmal persistieren
    if ($createdCount > 0) {
      $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
      $dataHandler->start($tcaUpdates, []);
      $dataHandler->process_datamap();

      // // NEU: Fehler des ersten DataHandler-Laufs prüfen und ausgeben
      // if (!empty($dataHandler->errorLog)) {
      //   $this->addModuleFlashMessage(
      //     'Fehler beim Erstellen der Akkreditierungen. Bitte prüfen Sie die Eingaben.',
      //     'DATENBANKFEHLER',
      //     'ERROR'
      //   );
      //   // Optional: Spezifische Fehlerdetails loggen
      //   \TYPO3\CMS\Core\Utility\DebugUtility::debug($dataHandler->errorLog);
      // }

      // Logs erstellen, da wir jetzt die echten UIDs haben
      $newlyCreatedUids = $dataHandler->substNEWwithIDs;
      $logTcaUpdates = [];
      $logCounter = 0;

      foreach ($newlyCreatedUids as $tempId => $newUid) {
        if (isset($logData[$tempId])) {
          // Holen der tt_address UID aus der ursprünglichen newAccreditationData-Struktur
          $guestUid = $tcaUpdates['tx_publicrelations_domain_model_accreditation'][$tempId]['guest'];

          // Log-Daten-Array erstellen
          $logEntryData = [
            'pid' => 2,
            'cruser_id' => $GLOBALS['BE_USER']->user['uid'] ?? 0,
            'function' => 'Akkreditierung',
            'code' => $logData[$tempId]['logCode'],
            'subject' => 'Erstellt - Wizzard',
            'accreditation' => (int) $newUid,
            'tt_address' => (int) $guestUid,
            'event' => (int) $event->getUid(),
          ];

          // Log-Daten dem gesammelten Array für den DataHandler hinzufügen
          $logTcaUpdates['tx_publicrelations_domain_model_log']['NEW_' . $logCounter] = $logEntryData;
          $logCounter++;
        }
      }

      // 2. Log-Einträge mit EINEM DataHandler-Aufruf erstellen
      if (!empty($logTcaUpdates)) {
        $dataHandler->start($logTcaUpdates, []);
        $dataHandler->process_datamap();

        // NEU: Fehler des zweiten DataHandler-Laufs prüfen und ausgeben
        if (!empty($dataHandler->errorLog)) {
          $this->addModuleFlashMessage(
            'Fehler beim Erstellen der Log-Einträge.',
            'LOG-FEHLER',
            'WARNING'
          );
        }
      }
    }

    // Flash Messages
    if (count($excludedNames) > 0) {
      $message = sprintf('%d Gäste wollten keine Mailings erhalten und wurden nicht akkreditiert: <br> %s', count($excludedNames), implode(', ', $excludedNames));
      $this->addModuleFlashMessage($message, 'EINIGE GÄSTE AUSGESCHLOSSEN', 'WARNING');
    }
    if (count($duplicateNames) > 0) {
      $message = sprintf('Nicht alle Einladungen konnten erstellt werden. %d Duplikat(e) gefunden: <br> %s', count($duplicateNames), implode(', ', $duplicateNames));
      $this->addModuleFlashMessage($message, 'DUPLIKATE NICHT ERSTELLT', 'WARNING');
    }
    if ($createdCount > 0) {
      $message = sprintf("Es wurden %d Einladungen erfolgreich erstellt. Der Versand muss noch angestoßen werden.", $createdCount);
      $this->addModuleFlashMessage($message, 'EINLADUNGEN ERSTELLT!', 'OK');
    } elseif ($createdCount === 0 && count($duplicateNames) === 0 && count($excludedNames) === 0) {
      $this->addModuleFlashMessage('Es wurden keine Gäste ausgewählt oder es gab ein Problem mit den Eingabedaten.', 'KEINE AKTION', 'INFO');
    }

    return $this->redirect('show', 'Event', null, ['event' => $event]);
  }

  /**
   * Hilfsmethode zur Formatierung des Anzeigenamens aus einem Daten-Array.
   */
  private function formatGuestName(array $guestData): string
  {
    $name = trim($guestData['first_name'] . ' ' . $guestData['middle_name'] . ' ' . $guestData['last_name']);
    if (empty($name) && !empty($guestData['company'])) {
      return $guestData['company'];
    }
    return $name;
  }




  // /**
  //  * Fall 3: Invitation-Manager
  //  */
  // protected function handleInvitationManager(Event $event): ResponseInterface
  // {

  //   $invitationsInput = $this->request->getArgument('invitations') ?? [];
  //   if (empty($invitationsInput['guest'])) {
  //     $this->addModuleFlashMessage( // Verwenden Sie addFlashMessage aus dem ActionController
  //       'Das sollte eigentlich nicht passieren. Es kamen keine zu generierenden Daten an.',
  //       'KEINE DATEN ÜBERGEBEN!',
  //       'ERROR' // Verwenden Sie die Konstanten
  //     );
  //     return $this->redirect('show', 'Event', null, ['event' => $event]);
  //   }

  //   $selectedGuestUids = [];
  //   foreach ($invitationsInput['guest'] as $uid => $selected) {
  //     if ($selected === '1') {
  //       $selectedGuestUids[] = (int) $uid;
  //     }
  //   }

  //   if (empty($selectedGuestUids)) {
  //     $this->addModuleFlashMessage(
  //       'Keine Gäste ausgewählt.',
  //       'INFORMATION',
  //       'INFO'
  //     );
  //     return $this->redirect('show', 'Event', null, ['event' => $event]);
  //   }

  //   // 1. Alle benötigten Gast-Objekte (TtAddress) auf einmal laden
  //   $guestsById = [];
  //   $ttAddressObjects = $this->ttAddressRepository->findByUids($selectedGuestUids);
  //   foreach ($ttAddressObjects as $guest) {
  //     /** @var TtAddress $guest */
  //     $guestsById[$guest->getUid()] = $guest;
  //   }

  //   // 2. Bestehende Akkreditierungen für diese Gäste und dieses Event auf einmal prüfen
  //   // Annahme: findGuestUidsByEvent gibt ein Array der UIDs von Gästen zurück, die bereits akkreditiert sind.
  //   $existingAccreditationGuestUids = $this->accreditationRepository->findGuestUidsByEvent(
  //     $selectedGuestUids,
  //     $event->getUid()
  //   );
  //   // Umwandeln in ein assoziatives Array für schnellen Lookup
  //   $isAlreadyAccredited = array_flip($existingAccreditationGuestUids);

  //   // 3. InvitationType einmalig laden, wenn vorhanden
  //   $invitationTypeObject = null;
  //   if (!empty($invitationsInput['invitationType'])) {
  //     $invitationTypeObject = $this->invitationRepository->findByUid((int) $invitationsInput['invitationType']);
  //     if (!$invitationTypeObject) {
  //       $this->addModuleFlashMessage(
  //         sprintf('Der angegebene Einladungstyp mit UID %d konnte nicht gefunden werden.', (int) $invitationsInput['invitationType']),
  //         'FEHLERHAFTER EINLADUNGSTYP',
  //         'ERROR'
  //       );
  //       // Eventuell hier abbrechen, wenn ein gültiger InvitationType zwingend ist
  //       return $this->redirect('show', 'Event', null, ['event' => $event]);
  //     }
  //   }

  //   $createdCount = 0;
  //   $duplicateCount = 0;
  //   $excludedCount = 0;
  //   $accreditationsToPersist = [];

  //   foreach ($selectedGuestUids as $guestUid) {
  //     $guest = $guestsById[$guestUid] ?? null;

  //     if (!$guest) {
  //       // Sollte nicht passieren, wenn findByUids korrekt funktioniert und alle UIDs gültig waren
  //       $duplicateCount++; // Zählen als nicht verarbeitet
  //       continue;
  //     }

  //     /** @var TtAddress $guest */
  //     if ($guest->isMailingExclude()) {
  //       $this->addModuleFlashMessage(
  //         sprintf(
  //           "Der Gast %s (%s) will keine Mailings mehr erhalten, es wurde keine Akkreditierung/Einladung erstellt!",
  //           htmlspecialchars($guest->getFullName() ?: 'Unbekannt', ENT_QUOTES),
  //           htmlspecialchars($guest->getEmail() ?: 'Keine E-Mail', ENT_QUOTES) // Zusätzliche Info
  //         ),
  //         'GAST AUSGESCHLOSSEN', // Titel angepasst
  //         'WARNING' // Eventuell WARNING statt ERROR, da es eine Gast-Eigenschaft ist
  //       );
  //       $excludedCount++;
  //       continue;
  //     }

  //     if (isset($isAlreadyAccredited[$guestUid])) {
  //       $duplicateCount++;
  //       continue;
  //     }

  //     // Gast ist nicht ausgeschlossen und kein Duplikat -> Akkreditierung erstellen
  //     $newAccreditation = new Accreditation();
  //     $newAccreditation->setEvent($event);
  //     $newAccreditation->setStatus(0); // Default Status
  //     $newAccreditation->setInvitationStatus(0); // Default Invitation Status
  //     $newAccreditation->setType(2); // Typ 2 für Invitation-Manager?
  //     $newAccreditation->setGuestType((int) ($invitationsInput['guestType'] ?? 0));
  //     $newAccreditation->setTicketsWish((int) ($invitationsInput['ticketsWish'] ?? 0));
  //     $newAccreditation->setGuest($guest);

  //     if ($invitationTypeObject) {
  //       $newAccreditation->setInvitationType($invitationTypeObject);
  //     }

  //     // Log erstellen und hinzufügen
  //     // Annahme: $this->logGenerator->createAccreditationLog gibt ein persistierbares Log-Objekt zurück
  //     // und $newAccreditation->addLog() ist korrekt konfiguriert (z.B. mit cascade persist)
  //     $logEntry = $this->logGenerator->createAccreditationLog('AI-S-C', $newAccreditation); // Typ 'Accreditation created from Invitation System - Controller'
  //     $newAccreditation->addLog($logEntry);

  //     $this->accreditationRepository->add($newAccreditation); // Dem Repository hinzufügen (noch nicht persistiert)
  //     // Wenn LogEntry ein eigenes persistierbares Objekt ist und nicht via Kaskadierung von Accreditation persistiert wird:
  //     // $this->logEntryRepository->add($logEntry);

  //     $createdCount++;
  //   }

  //   // 4. Alle Änderungen auf einmal persistieren, wenn etwas erstellt wurde
  //   if ($createdCount > 0) {
  //     // Da Sie $this->accreditationRepository->add() verwenden, sollte ein
  //     // $this->persistenceManager->persistAll() am Ende ausreichen,
  //     // wenn es nicht automatisch am Ende des Action-Controller-Zyklus passiert.
  //     // Um sicherzugehen, explizit aufrufen:
  //     $this->persistenceManager->persistAll();
  //   }

  //   // Flash Messages
  //   if ($excludedCount > 0) {
  //     $this->addModuleFlashMessage(
  //       sprintf('%d Gäste wollten keine Mailings erhalten und wurden nicht akkreditiert.', $excludedCount),
  //       'EINIGE GÄSTE AUSGESCHLOSSEN',
  //       'WARNING'
  //     );
  //   }
  //   if ($duplicateCount > 0) {
  //     $this->addModuleFlashMessage(
  //       sprintf('Nicht alle Einladungen konnten erstellt werden. %d Duplikat(e) oder Fehler beim Laden der Gastdaten.', $duplicateCount),
  //       'DUPLIKATE NICHT ERSTELLT',
  //       'WARNING'
  //     );
  //   }
  //   if ($createdCount > 0) {
  //     $this->addModuleFlashMessage(
  //       sprintf(
  //         "Es wurden %d Einladungen erfolgreich erstellt. Bitte beachte, dass dies nur intern erstellt wurde, bisher hat noch niemand eine Einladung via E-Mail erhalten. Der Versand muss noch angestoßen werden.",
  //         $createdCount
  //       ),
  //       'EINLADUNGEN ERSTELLT!',
  //       'OK'
  //     );
  //   } elseif ($createdCount === 0 && $duplicateCount === 0 && $excludedCount === 0 && !empty($selectedGuestUids)) {
  //     $this->addModuleFlashMessage(
  //       'Es wurden keine Gäste ausgewählt oder es gab ein Problem mit den Eingabedaten.',
  //       'KEINE AKTION',
  //       'INFO'
  //     );
  //   }

  //   // $this->handleDuplicateCheck($event);

  //   return $this->redirect('show', 'Event', null, ['event' => $event]);
  // }


  // /**
  //  * Fall 3: Invitation-Manager
  //  */
  // protected function handleInvitationManager(Event $event): ResponseInterface
  // {
  //   $inv = $this->request->getArgument('invitations') ?? [];
  //   if (empty($inv['guest'])) {
  //     $this->addModuleFlashMessage(
  //       'Das sollte eigentlich nicht passieren. Es kamen keine zu generierenden Daten an.',
  //       'KEINE DATEN ÜBERGEBEN!',
  //       'ERROR'
  //     );
  //     return $this->redirect('show', 'Event', null, ['event' => $event]);
  //   }

  //   $created = $dup = 0;
  //   foreach ($inv['guest'] as $uid => $sel) {
  //     if ($sel !== '1') {
  //       continue;
  //     }
  //     $guest = $this->ttAddressRepository->findByUid((int) $uid);
  //     $duplicate = $this->accreditationRepository->findGuestByEvent($guest->getUid(), $event->getUid());
  //     if ($guest->isMailingExclude()) {
  //       $this->addModuleFlashMessage(
  //         "Der Gast {$guest->getFullName()} will keine Mailings mehr erhalten, es wurde keine Akkreditierung/Einladung erstellt!",
  //         'AKKREDITIERUNG KONNTE NICHT ERSTELLT WERDEN!',
  //         'ERROR'
  //       );
  //     } elseif ($duplicate) {
  //       $dup++;
  //     } else {
  //       $newAk = new Accreditation();
  //       $newAk->setEvent($event);
  //       $newAk->setStatus(0);
  //       $newAk->setInvitationStatus(0);
  //       $newAk->setType(2);
  //       $newAk->setGuestType((int) ($inv['guestType'] ?? 0));
  //       $newAk->setTicketsWish((int) ($inv['ticketsWish'] ?? 0));
  //       $newAk->setGuest($guest);

  //       if (!empty($inv['invitationType'])) {
  //         $newAk->setInvitationType(
  //           $this->invitationRepository->findByUid((int) $inv['invitationType'])
  //         );
  //       }
  //       $log = $this->logGenerator
  //         ->createAccreditationLog('AI-S-C', $newAk);
  //       $newAk->addLog($log);
  //       $this->accreditationRepository->add($newAk);
  //       $created++;
  //     }
  //   }

  //   if ($dup > 0) {
  //     $this->addModuleFlashMessage(
  //       "Nicht alle Einladungen konnten erstellt werden. {$dup} Duplikat(e).",
  //       'DUPLIKATE WURDEN NICHT ERSTELLT!',
  //       'WARNING'
  //     );
  //   }
  //   $this->addModuleFlashMessage(
  //     "Es wurden {$created} Einladungen erfolgreich erstellt. Bitte beachte, dass dies nur intern erstellt wurde, bisher hat noch niemand eine Einladung via E-Mail erhalten. Der Versand muss noch angestoßen werden.",
  //     'EINLADUNGEN ERSTELLT!'
  //   );

  //   return $this->redirect('show', 'Event', null, ['event' => $event]);
  // }

  /**
   * Fall 4: Manuelle Frontend-Anfrage
   */
  protected function handleManualFrontend(Event $event): ResponseInterface
  {
    $data = $this->request->getArgument('newAccreditation') ?? [];
    $email = trim(strtolower($data['email'] ?? ''));
    $guest = $this->ttAddressRepository->findByProperty('email', $email);

    if ($guest !== null) {
      $dup = $this->accreditationRepository->findGuestByEvent($guest->getUid(), $event->getUid());
    }
    if (!empty($dup)) {
      $this->addModuleFlashMessage(
        'Eine Akkreditierungsanfrage wurde bereits übermittelt. Sie erhalten Ihre Rückmeldung zeitgerecht via E-Mail. Wenden Sie sich im Falle einer gewünschten Änderung bitte direkt an office@allegria.at.',
        'AKKREDITIERUNGSANFRAGE BEREITS ÜBERMITTELT!',
        'WARNING'
      );
      return $this->redirect('show', 'Client', null, ['client' => $event->getClient()]);
    }

    $newAk = new Accreditation();
    $newAk->setEvent($event);
    $newAk->setStatus((int) ($data['status'] ?? -2));
    $newAk->setGuestType((int) ($data['guestType'] ?? 0));
    $newAk->setType((int) ($data['type'] ?? 0));
    $newAk->setDsgvo((int) ($data['dsgvo'] ?? 0));
    $newAk->setIp(trim($data['ip'] ?? ''));
    $newAk->setMedium(trim($data['medium'] ?? ''));
    $newAk->setMediumType(
      $this->sysCategoryRepository->findByUid((int) ($data['mediumType'] ?? 0))
    );
    $newAk->setTicketsWish((int) ($data['ticketsWish'] ?? 0));
    $newAk->setGender($data['gender'] ?? '');
    $newAk->setTitle(trim($data['title'] ?? ''));
    $newAk->setFirstName(trim(ucwords($data['firstName'] ?? '')));
    $newAk->setLastName(trim(ucwords($data['lastName'] ?? '')));
    $newAk->setEmail($email);
    $newAk->setPhone(trim($data['phone'] ?? ''));
    $newAk->setRequestNote(trim($data['requestNote'] ?? ''));

    if ($guest !== null) {
      $newAk->setGuest($guest);
    }

    // Log & speichern
    $log = $this->logGenerator
      ->createAccreditationLog('A-CRW', $newAk);
    $newAk->addLog($log);
    $this->accreditationRepository->add($newAk);

    $this->addModuleFlashMessage(
      'Ihre Akkreditierungsanfrage wurde erfolgreich übermittelt. Sie erhalten Ihre Rückmeldung zeitgerecht via E-Mail.',
      'AKKREDITIERUNGSANFRAGE ÜBERMITTELT!'
    );
    $this->persistenceManager->persistAll();

    // Test- und Bestätigungs-Mails
    if (($newAk->getGuestOutput()['email'] ?? '') !== 'noreply@allegria.at') {
      $this->mailGenerator
        ->createAccreditationMail('A-Email-S', $newAk, $this->settings);
      $this->mailGenerator
        ->createAccreditationMail('A-Email-P', $newAk, $this->settings);
      $this->accreditationRepository->update($newAk);
    }

    // // Test- und Bestätigungs-Mails
    // if (($newAk->getGuestOutput()['email'] ?? '') !== 'noreply@allegria.at') {

    //   // --- ERSETZT (Mail an Gast) ---
    //   // $this->mailGenerator->createAccreditationMail('A-Email-S', $newAk, $this->settings);
    //   // 'A-Email-S' (Submit) -> 'pending'
    //   $this->sendDistributionMail($newAk, 'pending');
    //   // --- ENDE ---

    //   // --- BEHALTEN (Mail an Office) ---
    //   // ⚠️ Wir behalten den MailGenerator für die interne Benachrichtigung,
    //   // da der AccreditationDataResolver dies nicht abbilden kann.
    //   $this->mailGenerator
    //     ->createAccreditationMail('A-Email-P', $newAk, $this->settings);
    //   // --- ENDE BEHALTEN ---

    //   $this->accreditationRepository->update($newAk);
    // }

    return $this->redirect('show', 'Client', null, ['client' => $event->getClient()]);
  }

  /**
   * action editCollection
   *
   * @param Event|null $event
   * @param bool $noMail
   * @param array|null $settings
   * @param string $function
   * @return void
   */
  public function editCollectionAction(
    ?Event $event = null,
    bool $noMail = false,
    ?array $settings = null,
    string $function = ''
  ): ResponseInterface {

    // 1. Prüfung ob Accreditations übergeben
    $selectedAccreditationUids = [];
    if (!empty($settings['accreditations']) && is_array($settings['accreditations'])) {
      foreach ($settings['accreditations'] as $uid => $flag) {
        if ($flag === '1') {
          $selectedAccreditationUids[] = (int) $uid;
        }
      }
    }
    if (empty($selectedAccreditationUids)) {
      $this->addModuleFlashMessage('Es wurden keine Akkreditierungen ausgewählt.', 'Keine Auswahl', 'WARNING');
      return $event ? $this->redirect('show', 'Event', null, ['event' => $event]) : $this->redirect('list');
    }

    // 2. Prüfung ob Funktion übergeben - sonst Formular rendern
    if (empty($function)) {
      // --- Formular-Ansicht vorbereiten (bestehende Logik) ---
      $accreditations = $this->accreditationRepository->findByUidsArray($selectedAccreditationUids); // Lade Objekte für die Ansicht
      $notesItems = $this->sysCategoryRepository->findByParentUid($this->emConfiguration->getAccreditationNotesRootUid());
      $this->view->assignMultiple([
        'accreditations' => $accreditations,
        'event' => $event,
        'notesItems' => $notesItems,
        'noMail' => $noMail, // Pass this flag to the view if needed
      ]);
      $this->setModuleTitle('Massenänderung Akkreditierungen' . ($event ? (' – ' . $event->getTitle()) : ''));
      return $this->backendResponse();
    }

    $function = $settings['function'] ?? '';
    if (empty($function)) {
      $this->addModuleFlashMessage('Es wurde keine Funktion ausgewählt bzw. nicht korrekt übergeben.', 'Keine Auswahl', 'WARNING');
      return $event ? $this->redirect('show', 'Event', null, ['event' => $event]) : $this->redirect('list');
    }

    // --- Funktions-Verarbeitung ---
    $accreditations = $this->accreditationRepository->findByUidsArray($selectedAccreditationUids);

    $validAccreditations = [];
    $invalidTransitionAccreditations = [];
    $deleteAccreditationUids = []; // UIDs für cmdMap['delete']

    // 3. Akkreditierungen durchschleifen und prüfen
    foreach ($accreditations as $accreditation) {
      if (!$this->accreditationService->isValidStatusTransition($accreditation, $function)) {
        $invalidTransitionAccreditations[] = $accreditation;
      } elseif ($function === 'delete') {
        $deleteAccreditationUids[] = $accreditation->getUid();
      } else {
        $validAccreditations[] = $accreditation;
      }
    }

    // 4. Aufteilung in Mail / NoMail (Refaktorisiert) ---
    $mailAccreditations = [];
    $noMailAccreditations = [];
    $mailInvalidAccreditations = [];

    $mailServiceIsActive = !empty($settings['mails']) && $settings['mails'] === '1';
    $mailFunctionsWhitelist = [
      'invite',
      'remind',
      'push',
      'resend',
      'approve',
      'confirm',
      'reject',
    ];
    $functionShouldSendMail = in_array($function, $mailFunctionsWhitelist, true);

    foreach ($validAccreditations as $accreditation) {
      // Prüft, ob Mails (A) global aktiv sind, (B) die Funktion eine Mail vorsieht
      // UND (C) der Gast Mails empfangen darf (kein Opt-Out, E-Mail vorhanden).
      $canSendMail = $mailServiceIsActive
        && $functionShouldSendMail
        && $this->accreditationService->isValidForSending($accreditation);

      if ($canSendMail) {
        $mailAccreditations[] = $accreditation;
      } else {
        $noMailAccreditations[] = $accreditation;
        // Wenn Mailversand gewollt war, aber (C) fehlschlug -> Feedback
        if ($mailServiceIsActive && $functionShouldSendMail) {
          $mailInvalidAccreditations[] = $accreditation;
        }
      }
    }

    // --- 5. Job erstellen (falls nötig) ---
    $jobAccreditations = [];
    $lockedAccreditations = [];
    $newJobUid = null;

    if (!empty($mailAccreditations)) {
      $mailAccreditationUids = array_map(fn($acc) => $acc->getUid(), $mailAccreditations);
      $lockedUids = $this->accreditationRepository->findUidsWithActiveDistributionJob($mailAccreditationUids);

      // Trenne gesperrte von job-fähigen
      foreach ($mailAccreditations as $accreditation) {
        if (in_array($accreditation->getUid(), $lockedUids)) {
          $lockedAccreditations[] = $accreditation;
        } else {
          $jobAccreditations[] = $accreditation;
        }
      }

      // Erstelle den Job, wenn es freigeschaltete Akkreditierungen gibt
      if (!empty($jobAccreditations)) {
        try {
          $context = [
            'dataSource' => [
              'dataResolverClass' => AccreditationDataResolver::class,
              'function' => $function,
              'uids' => array_map(fn($acc) => $acc->getUid(), $jobAccreditations)
            ],
            'context' => 'Massenversand von Typ ' . ($function ?? 'Unbekannt') . ' zu ' . ($event?->getTitle() ?? 'Unbekanntes Event'),
            'sender_profile' => 1, // ⚠️ TODO: Sender-Profil muss dynamisch sein
            'report' => [
              'no_report' => false,
              'job_title' => 'Massenänderung \'' . $function . '\' zu Event: ' . ($event?->getTitle() ?? 'Unbekanntes Event')
            ]
          ];

          // (Gleiche Logik wie vorher für 'scheduled_at')
          $scheduledAtString = $settings['scheduled_at'] ?? null;
          if (!empty($scheduledAtString)) {
            try {
              $userTimeZoneString = $GLOBALS['BE_USER']->uc['tz'] ?? 'Europe/Vienna';
              $userTimeZone = new \DateTimeZone($userTimeZoneString);
              $localDateTimeString = str_replace(['T', 'Z'], [' ', ''], $scheduledAtString);
              $dateTime = new \DateTimeImmutable($localDateTimeString, $userTimeZone);
              $context['scheduled_at'] = $dateTime->getTimestamp();
            } catch (\Exception $e) {
              $this->addModuleFlashMessage(
                'Das geplante Datum (' . htmlspecialchars($scheduledAtString) . ') war ungültig und wurde ignoriert.',
                'Warnung',
                'WARNING'
              );
            }
          }

          // Übergabe an DistributionService
          $dispatchResult = $this->distributionService->send($context);

          if ($dispatchResult['status'] === 'queued') {
            $newJobUid = $dispatchResult['job_uid'];
          } elseif ($dispatchResult['status'] === 'sent') {
            $newJobUid = null; // Wurde direkt versandt (bei nur 1 UID)
            $this->addModuleFlashMessage('Eine Akkreditierung wurde direkt versandt (Job-Erstellung übersprungen).', 'Direktversand');
          } else {
            throw new \RuntimeException('Job dispatch failed: ' . ($dispatchResult['message'] ?? 'Unknown error'));
          }

        } catch (\Exception $e) {
          $this->addModuleFlashMessage('Fehler beim Erstellen des Versandjobs: ' . $e->getMessage(), 'Job Fehler', 'ERROR');
          // Wenn die Job-Erstellung fehlschlägt, behandle diese als "noMail"
          $noMailAccreditations = array_merge($noMailAccreditations, $jobAccreditations);
          $jobAccreditations = []; // Reset
          $newJobUid = null;
        }
      }
    }

    // --- 6. Prepare Main DataHandler Map (Updates, Logs, Locks) ---
    $mainDataMap = [];
    $mainCmdMap = [];

    // 6a. Lösch-Befehle
    if (!empty($deleteAccreditationUids)) {
      $mainCmdMap['tx_publicrelations_domain_model_accreditation'] = array_fill_keys($deleteAccreditationUids, ['delete' => 1]);
      // Logs für Löschung
      foreach ($deleteAccreditationUids as $uid) {
        $deletedAcc = $this->accreditationRepository->findByUid($uid);
        if ($deletedAcc) {
          $logData = $this->accreditationService->prepareLogData($deletedAcc, 'A-delete');
          $mainDataMap['tx_publicrelations_domain_model_log']['NEW_LOG_DELETE_' . $uid] = $logData;
        }
      }
    }

    // 6b. Job-Locks setzen (für die, die erfolgreich in den Job gingen)
    if ($newJobUid !== null && !empty($jobAccreditations)) {
      foreach ($jobAccreditations as $accreditation) {
        $uid = $accreditation->getUid();
        // Job-Lock setzen
        $mainDataMap['tx_publicrelations_domain_model_accreditation'][$uid]['distribution_job'] = $newJobUid;
        // Log für Job-Erstellung
        $logData = $this->accreditationService->prepareLogData($accreditation, 'A-job-created', ['jobUid' => $newJobUid]);
        $mainDataMap['tx_publicrelations_domain_model_log']['NEW_LOG_JOB_' . $uid] = $logData;
      }
    }

    // 6c. Status-Updates (für 'noMail' und 'locked')
    // Alle, die *nicht* in einen Job kamen, aber valide waren.
    $processList = array_merge($noMailAccreditations, $lockedAccreditations);

    foreach ($processList as $accreditation) {
      $uid = $accreditation->getUid();

      // Änderungen holen (z.B. status => 0, invitation_status => 0)
      $targetChanges = $this->accreditationService->getDefaultChangesForAction($accreditation, $function);

      // Log-Code holen (NUR Status-Änderung, da Mail ja nicht stattfindet)
      $logCode = $this->accreditationService->getLogCodeForStatusAction($function);

      // Änderungen zur DataMap hinzufügen
      if (!empty($targetChanges)) {
        $mainDataMap['tx_publicrelations_domain_model_accreditation'][$uid] =
          array_merge($mainDataMap['tx_publicrelations_domain_model_accreditation'][$uid] ?? [], $targetChanges);
      }

      // Log zur DataMap hinzufügen
      if ($logCode !== null) {
        // WICHTIG: Wir müssen die Änderungen am Objekt simulieren,
        // damit der Log-Service den (zukünftigen) Zustand loggt, falls nötig.
        foreach ($targetChanges ?? [] as $field => $value) {
          $setter = 'set' . GeneralUtility::underscoredToUpperCamelCase($field);
          if (method_exists($accreditation, $setter)) {
            $accreditation->$setter($value);
          }
        }
        $logData = $this->accreditationService->prepareLogData($accreditation, $logCode);
        $mainDataMap['tx_publicrelations_domain_model_log']['NEW_LOG_MAIN_' . $uid] = $logData;
      }
    }

    // --- 7. DataHandler ausführen (Updates, Deletes, Logs, Locks) ---
    if (!empty($mainDataMap) || !empty($mainCmdMap)) {
      $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
      $dataHandler->bypassAccessCheckForRecords = true;
      $dataHandler->start($mainDataMap, $mainCmdMap);
      $dataHandler->process_datamap();
      $dataHandler->process_cmdmap();
      $this->persistenceManager->persistAll();

      if (!empty($dataHandler->errorLog)) {
        $this->addModuleFlashMessage('Einige Datenbankoperationen sind fehlgeschlagen (Hauptlauf).', 'DataHandler Fehler', 'ERROR');
        // TODO: Log $dataHandler->errorLog
      }
    }

    // --- 8. Feedback generieren ---
    $this->generateFeedbackMessages(
      $invalidTransitionAccreditations,
      $mailInvalidAccreditations,
      $lockedAccreditations,
      $newJobUid !== null ? count($jobAccreditations) : 0, // Job erstellt
      count($noMailAccreditations) - count($mailInvalidAccreditations), // Echte "No-Mails"
      count($deleteAccreditationUids)
    );

    return $event ? $this->redirect('show', 'Event', null, ['event' => $event]) : $this->redirect('list');
  }

  /**
   * Bestimmt den Mail-Code für den MailGenerator.
   */
  private function determineMailCode(string $function, int $currentInvitationStatus): ?string
  {
    return match (strtolower($function)) {
      'invite' => 'AI-Email-I1',
      'remind' => 'AI-Email-I2',
      'push' => 'AI-Email-I3',
      'approve', 'confirm' => 'AI-Email-A', // Nimmt A-Email-A für beide?
      'reject' => 'AI-Email-R',
      'resend' => match ($currentInvitationStatus) { // Speziell für resend
          1 => 'AI-Email-I1',
          2 => 'AI-Email-I2',
          3 => 'AI-Email-I3',
          default => null, // Kein Resend für andere Stati
        },
      default => null, // Keine Mail für andere Aktionen wie reset
    };
  }

  /**
   * Generiert die finalen FlashMessages.
   */
  private function generateFeedbackMessages(
    array $invalidTransitionAccreditations,
    array $mailInvalidAccreditations,
    array $lockedAccreditations,
    int $jobCreatedCount,
    int $noMailProcessedCount,
    int $deletedCount
  ): void {
    if (!empty($invalidTransitionAccreditations)) {
      $names = array_map(fn($acc) => $acc->getGuestOutput()['name'] ?? $acc->getUid(), $invalidTransitionAccreditations);
      $this->addModuleFlashMessage(sprintf('Die Aktion war für %d Akkreditierung(en) nicht zulässig: %s', count($names), implode(', ', $names)), 'Aktion nicht möglich', 'ERROR');
    }
    if (!empty($mailInvalidAccreditations)) {
      $names = array_map(fn($acc) => $acc->getGuestOutput()['name'] ?? $acc->getUid(), $mailInvalidAccreditations);
      $this->addModuleFlashMessage(sprintf('Für %d Akkreditierung(en) konnte keine E-Mail gesendet werden (ungültige Adresse, Opt-Out o.ä.): %s', count($names), implode(', ', $names)), 'Mailversand nicht möglich', 'WARNING');
    }
    if (!empty($lockedAccreditations)) {
      $names = array_map(fn($acc) => $acc->getGuestOutput()['name'] ?? $acc->getUid(), $lockedAccreditations);
      $this->addModuleFlashMessage(sprintf('%d Akkreditierung(en) sind bereits Teil eines aktiven Versandjobs und wurden übersprungen (nur Statusänderung): %s', count($names), implode(', ', $names)), 'Akkreditierungen gesperrt', 'WARNING');
    }
    if ($jobCreatedCount > 0) {
      $this->addModuleFlashMessage(sprintf('Ein Versandjob für %d Akkreditierung(en) wurde erstellt und wird wie geplant verarbeitet.', $jobCreatedCount), 'Job erstellt');
    }
    if ($noMailProcessedCount > 0) {
      $this->addModuleFlashMessage(sprintf('%d Akkreditierung(en) wurden wie gewünscht ohne Mailversand verarbeitet (z.B. Status-Reset oder Mail-Checkbox deaktiviert).', $noMailProcessedCount), 'Aktion ohne Mail ausgeführt', 'INFO');
    }
    if ($deletedCount > 0) {
      $this->addModuleFlashMessage(sprintf('%d Akkreditierung(en) wurden gelöscht.', $deletedCount), 'Gelöscht');
    }
    if (empty($invalidTransitionAccreditations) && empty($mailInvalidAccreditations) && empty($lockedAccreditations) && $jobCreatedCount === 0 && $noMailProcessedCount === 0 && $deletedCount === 0) {
      $this->addModuleFlashMessage('Es wurden keine gültigen Aktionen für die ausgewählten Akkreditierungen gefunden.', 'Keine Aktion durchgeführt', 'INFO');
    }
  }


  /**
   * action approve
   */
  public function approveAction(Accreditation $accreditation): ResponseInterface
  {
    // Extension-Konfiguration laden


    // Kategorien für Notizen holen
    $notesItems = $this->sysCategoryRepository->findByParentUid(
      $this->emConfiguration->getAccreditationNotesRootUid()
    );

    // Journalisten-Kontakte holen
    $press = $this->ttAddressRepository->findByPage(
      $this->emConfiguration->getJournalistsPid()
    );

    // Alle View-Variablen auf einmal zuweisen
    $this->view->assignMultiple([
      'notesItems' => $notesItems,
      'press' => $press,
      'accreditation' => $accreditation,
    ]);

    $this->setModuleTitle('Akkreditierung für ' . $accreditation->getGuestOutput()['fullName'] . ' bestätigen');
    return $this->backendResponse();
  }

  /**
   * action edit
   */
  public function editAction(Accreditation $accreditation, bool $noMail = false): ResponseInterface
  {
    // Extension-Konfiguration laden


    // Notiz-Kategorien holen
    $notesItems = $this->sysCategoryRepository
      ->findByParentUid($this->emConfiguration->getAccreditationNotesRootUid());

    // Alle View-Variablen auf einmal zuweisen
    $this->view->assignMultiple([
      'notesItems' => $notesItems,
      'accreditation' => $accreditation,
      'noMail' => $noMail,
      'event' => $accreditation->getEvent(),
    ]);

    $this->setModuleTitle('Akkreditierung für ' . $accreditation->getGuestOutput()['fullName'] . ' ändern');
    return $this->backendResponse();
  }

  /**
   * Initialize action for update form
   */
  public function initializeUpdateAction(): void
  {
    if ($this->request->hasArgument('accreditation')) {
      $submitted = $this->request->getArgument('accreditation');
      // Nur wenn accreditation ein Array ist, der Key 'guest' existiert und dessen Wert == 0
      if (
        is_array($submitted)
        && array_key_exists('guest', $submitted)
        && (int) $submitted['guest'] === 0
      ) {
        $this->arguments
          ->getArgument('accreditation')
          ->getPropertyMappingConfiguration()
          ->skipProperties(['guest']);
      }
    }
  }



  /**
   * action update
   *
   * @return void
   */
  public function updateAction(Accreditation $accreditation): ResponseInterface
  {

    if ($this->request->hasArgument('reject')) {

      $accreditation->setTicketsApproved(0);
      $accreditation->setProgram(0);
      $accreditation->setPass(0);

      if ($accreditation->getType() == 2) {

        $logRejected = $this->logGenerator->createAccreditationLog('AI-S-R', $accreditation);
        $accreditation->addLog($logRejected);

        if ($this->request->getArgument('mails') == '1') {

          if ($accreditation->getStatus() == -2) {
            // 'AI-Email-AW-NO' (Reject Waitinglist) -> 'reject_after_waiting'
            $this->sendDistributionMail($accreditation, 'reject_after_waiting');
          } else {
            // 'AI-Email-R' (Reject) -> 'reject'
            $this->sendDistributionMail($accreditation, 'reject');
          }

          $this->addModuleFlashMessage(
            'Die Einladung wurde abgelehnt und der Gast informiert.',
            'EINLADUNG ABGELEHNT!'
          );

        } else {

          $this->addModuleFlashMessage(
            'Der Gast wurde nicht verständigt! Bitte manuell erledigen.',
            'GAST WURDE NICHT VERSTÄNDIGT!',
            'ERROR'
          );

          $this->addModuleFlashMessage(
            'Die Einladung wurde abgelehnt.',
            'EINLADUNG ABGELEHNT!'
          );

        }

      } else {

        $logRejected = $this->logGenerator->createAccreditationLog('A-R', $accreditation);
        $accreditation->addLog($logRejected);

        if ($this->request->getArgument('mails') == '1') {

          if ($accreditation->getGuestOutput() != '' && $accreditation->getGuestOutput() != 'noreply@allegria.at')
            $this->sendDistributionMail($accreditation, 'reject');

          $this->addModuleFlashMessage(
            'Die Akkrediterung wurde abgelehnt und der Gast informiert.',
            'AKKREDITIERUNG ABGELEHNT!'
          );

        } else {

          $this->addModuleFlashMessage(
            'Der Gast wurde nicht verständigt! Bitte manuell erledigen.',
            'GAST WURDE NICHT VERSTÄNDIGT!',
            'ERROR'
          );

          $this->addModuleFlashMessage(
            'Die Akkreditierung wurde abgelehnt.',
            'AKKREDITIERUNG ABGELEHNT!'
          );

        }

      }

      $accreditation->setStatus(-1);
      $accreditation->setInvitationStatus(-1);
      $this->accreditationRepository->update($accreditation);

    } elseif ($this->request->hasArgument('approve')) {

      if ($accreditation->getType() == 2) {

        $logApproved = $this->logGenerator->createAccreditationLog('AI-S-A', $accreditation);
        $accreditation->addLog($logApproved);

        if ($this->request->getArgument('mails') == '1') {
          if ($accreditation->getGuestOutput() != '' && $accreditation->getGuestOutput() != 'noreply@allegria.at') {

            if ($accreditation->getStatus() === -2) {
              // 'AI-Email-AW-OK' (Approve Waitinglist) -> 'approve_after_waiting'
              $this->sendDistributionMail($accreditation, 'approve_after_waiting');
            } else {
              // 'AI-Email-A' (Approve) -> 'approve'
              $this->sendDistributionMail($accreditation, 'approve');
            }

          }

          $this->addModuleFlashMessage(
            'Die Einladung wurde bestätigt und der Gast informiert.',
            'EINLADUNG BESTÄTIGT!'
          );

        } else {

          $this->addModuleFlashMessage(
            'Der Gast wurde nicht verständigt! Bitte manuell erledigen.',
            'GAST WURDE NICHT VERSTÄNDIGT!',
            'ERROR'
          );

          $this->addModuleFlashMessage(
            'Die Einladung wurde bestätigt.',
            'EINLADUNG BESTÄTIGT!'
          );

        }

        $accreditation->setStatus(1);

      } else {

        $logApproved = $this->logGenerator->createAccreditationLog('A-A', $accreditation);
        $accreditation->addLog($logApproved);

        if ($this->request->getArgument('mails') == '1') {

          if ($accreditation->getGuestOutput() != '' && $accreditation->getGuestOutput() != 'noreply@allegria.at')
            $this->sendDistributionMail($accreditation, 'approve');

          $this->addModuleFlashMessage(
            'Die Akkrediterung wurde bestätigt und der Gast informiert.',
            'AKKREDITIERUNG BESTÄTIGT!'
          );

        } else {

          $this->addModuleFlashMessage(
            'Der Gast wurde nicht verständigt! Bitte manuell erledigen.',
            'GAST WURDE NICHT VERSTÄNDIGT!',
            'ERROR'
          );

          $this->addModuleFlashMessage(
            'Die Akkreditierung wurde bestätigt.',
            'AKKREDITIERUNG BESTÄTIGT!'
          );

        }

      }

      $this->accreditationRepository->update($accreditation);

    } elseif ($this->request->hasArgument('createContact')) {

      $guest = $this->ttAddressRepository->findByProperty('email', trim(strtolower($accreditation->getEmail())));

      if ($guest)
        $accreditation->setGuest($guest);
      else {

        switch ($accreditation->getGuestType()) {
          case 1:
            $contactPid = $this->emConfiguration->getCelebPid();
            break;
          case 2:
            $contactPid = $this->emConfiguration->getJournalistsPid();
            break;
        }
        ;

        $contactData = [
          'pid' => $contactPid,
          'cruserId' => $this->getCurrentBackendUserUid(),
          'company' => $accreditation->getMedium(),
          'gender' => $accreditation->getGender(),
          'title' => $accreditation->getTitle(),
          'firstName' => $accreditation->getFirstName(),
          'middleName' => $accreditation->getMiddleName(),
          'lastName' => $accreditation->getLastName(),
          'name' => preg_replace('/\s+/', ' ', $accreditation->getFirstName() . ' ' . $accreditation->getMiddleName() . ' ' . $accreditation->getLastName()),
          'email' => $accreditation->getEmail(),
          'phone' => '',
          'mobile' => $accreditation->getPhone()
        ];

        $newContact = $this->generalUtility->createContact($contactData);
        $accreditation->setGuest($newContact);

      }
      ;

      $this->accreditationRepository->update($accreditation);
      return $this->redirect('edit', 'Accreditation', NULL, ['accreditation' => $accreditation]);

    } elseif ($this->request->hasArgument('invitationReset')) {

      $logStatusUpdate = $this->logGenerator->createAccreditationLog('AI-Reset', $accreditation);
      $accreditation->addLog($logStatusUpdate);

      $accreditation->setInvitationStatus(0);
      $accreditation->setStatus(0);

      $this->accreditationRepository->update($accreditation);

      // MESSAGE
      $this->addModuleFlashMessage(
        'Die Einladung wurde zurückgesetzt.',
        'EINLADUNG ZURÜCKGESETZT!'
      );

    } else {

      $logUpdated = $this->logGenerator->createAccreditationLog('A-E', $accreditation);
      $accreditation->addLog($logUpdated);

      $accreditation->setStatus(1);

      if ($this->request->hasArgument('additionalAnswers')) {

        foreach ($this->request->getArgument('additionalAnswers') as $fieldUid => $value) {
          $content = '';
          $additionalField = $this->additionalfieldRepository->findByUid($fieldUid);
          $additionalAnswer = $this->additionalanswerRepository->findByFieldInAccreditation($accreditation->getUid(), $fieldUid);

          if ($additionalField->getType() == 6)
            foreach ($value as $key => $val) {
              $content .= $key . '|' . $val . "\n";
            } elseif ($value && $additionalField->getType() == 4)
            foreach ($value as $val) {
              $content .= $val . "\n";
            } else {
            $content = $value;
          }


          if ($additionalAnswer) {

            $additionalAnswer->setValue($content);
            $this->additionalanswerRepository->update($additionalAnswer);

          } else {

            $newAnswer = new Additionalanswer();
            $newAnswer->setType($additionalField->getType());
            $newAnswer->setValue($content);
            $newAnswer->setField($additionalField);
            $newAnswer->setAccreditation($accreditation);

            $accreditation->addAdditionalanswer($newAnswer);

          }

        }

      }

      $this->accreditationRepository->update($accreditation);

      if ($this->request->getArgument('mails') == '1') {
        if ($accreditation->getGuestOutput() != '' && $accreditation->getGuestOutput() != 'noreply@allegria.at') {
          $this->sendDistributionMail($accreditation, 'approve');
        }

        $this->addModuleFlashMessage(
          'Die Akkrediterung wurde geändert und dem Gast wurde eine aktuelle Akkreditierungsbestätigung zugestellt.',
          'AKKREDITIERUNG GEÄNDERT!'
        );

      } else {

        $this->addModuleFlashMessage(
          'Die Akkreditierung wurde erfolgreich geändert.',
          'AKKREDITIERUNGEN GEÄNDERT!'
        );

      }

    }

    return $this->redirect('show', 'Event', NULL, ['event' => $accreditation->getEvent()]);
  }

  /**
   * action statusUpdate
   *
   * @return void
   */
  public function statusUpdateAction(Event $event)
  {

    if ($this->request->hasArgument('settings')) {
      $settings = $this->request->getArgument('settings');

      foreach ($settings['accreditations'] as $accr => $value) {
        if ($value == '1')
          $accreditations[] = $this->accreditationRepository->findByUid(intval($accr));
      }
    }

    if ($accreditations) {
      $done = 0;
      $error = 0;
      $mailCode = null;
      $logCode = null;
      $targetInvitationStatus = null;

      // 1. Logik-Weiche basierend auf der $function
      //    Wir ermitteln $mailCode (für den Versand) und $logCode (für die DB)

      $function = $settings['function'];
      $sendMails = $settings['mails'] == '1';

      // HINWEIS: $function ist hier bereits 'invite', 'remind', 'resend', etc.
      // Das passt perfekt zu unseren neuen Codes.

      foreach ($accreditations as $accreditation) {
        // A. Prüfen, ob die Aktion gültig ist
        if (!$this->accreditationService->isValidStatusTransition($accreditation, $function)) {
          $error++;
          continue;
        }

        // B. DB-Änderungen und Log holen
        $targetChanges = $this->accreditationService->getDefaultChangesForAction($accreditation, $function);
        $logCode = $this->accreditationService->getLogCodeForMailAction($function, $accreditation->getInvitationStatus()); // (oder getLogCodeForStatusAction)

        // C. Änderungen anwenden (direkt am Objekt)
        foreach ($targetChanges as $field => $value) {
          $setter = 'set' . GeneralUtility::underscoredToUpperCamelCase($field);
          if (method_exists($accreditation, $setter)) {
            $accreditation->$setter($value);
          }
        }

        // D. Log erstellen
        if ($logCode) {
          $logStatusUpdate = $this->logGenerator->createAccreditationLog($logCode, $accreditation);
          $accreditation->addLog($logStatusUpdate);
        }

        // E. Mail senden (falls gewünscht und Mail-Funktion)
        if ($sendMails) {
          // Die $function (z.B. 'invite') ist direkt unser neuer Mail-Code.
          // Wir müssen nur 'delete' und 'reset' ausschließen, die keine Mails senden.
          if ($function !== 'delete' && $function !== 'reset') {
            if ($accreditation->getGuestOutput() != '' && $accreditation->getGuestOutput() != 'noreply@allegria.at') {

              // --- HIER IST DER NEUE AUFRUF ---
              $this->sendDistributionMail($accreditation, $function);
              // --- ENDE ---

            }
          }
        }

        // F. DB-Update
        $this->accreditationRepository->update($accreditation);
        $done++;
      }

      if ($done > 0) {
        $this . addModuleFlashMessage($done . ' Akkreditierungen erfolgreich verarbeitet für Funktion: ' . $function, 'Aktion ausgeführt', 'OK');
      }
      if ($error > 0) {
        $this->addModuleFlashMessage($error . ' Akkreditierungen konnten nicht verarbeitet werden (Status-Übergang ungültig).', 'Aktion teilweise fehlgeschlagen', 'ERROR');
      }




    } else {
      // MESSAGE
      $this->addModuleFlashMessage(
        'Das sollte nicht passieren, es wurde keine deklarierte Funktion übergeben.',
        'FUNKTION GÄNZLICH UNBEKANNT!',
        'ERROR'
      );
    }

    // NACH der Schleife, aber nur wenn auch Datensätze geändert wurden
    if ($done > 0 || $error > 0) {
      $this->persistenceManager->persistAll();
    }

    if (!$settings['mails'] == '1') {
      // MESSAGE
      $this->addModuleFlashMessage(
        'Beachte, dass auf Wunsch keine E-Mail versandt wurde!',
        'KEINE E-MAIL VERSANDT!',
        'WARNING'
      );
    }

    return $this->redirect('show', 'Event', NULL, ['event' => $event]);

  }

  /**
   * action checkin
   *
   * @return void
   */
  public function checkinAction(Accreditation $accreditation)
  {

    $ticketsToCheckin = intval($this->request->getArgument('ticketsReceived'));
    $accreditation->setTicketsReceived($accreditation->getTicketsReceived() + $ticketsToCheckin);

    if ($accreditation->getTicketsPrepared() < $accreditation->getTicketsApproved())
      $accreditation->setStatus(2);
    else
      $accreditation->setStatus(1);

    // LOG
    $report = '<strong>Tickets:</strong> ' . $ticketsToCheckin . ' von ' . $accreditation->getTicketsApproved();
    $report .= '<br><strong>Ausstehende Tickets:</strong> ' . $accreditation->getTicketsPrepared();
    if ($accreditation->getNotesReceived())
      $report .= '<br><strong>Anmerkungen:</strong> ' . $accreditation->getNotesReceived();
    $log = $this->logGenerator->createAccreditationLog('A-CheckIn', $accreditation, $report);
    $accreditation->addLog($log);

    $this->accreditationRepository->update($accreditation);

    // MESSAGE
    $this->addModuleFlashMessage(
      $accreditation->getGuestOutput()['name'] . ' wurde erfolgreich eingecheckt.',
      'CHECK-IN ERFOLGREICH!',
      'OK'
    );

    return $this->redirect('show', 'Checkin', NULL, ['event' => $accreditation->getEvent()]);

  }

  /**
   * action report
   *
   * @return void
   */
  public function reportAction(Accreditation $accreditation = null, string $lang = '')
  {
    if (isset($accreditation)) {
      if (!$this->getCurrentBackendUserUid()) {
        $log = $this->logGenerator->createAccreditationLog('AI-L-O', $accreditation);
        $accreditation->addLog($log);
        $accreditation->setOpened(1);
        $this->accreditationRepository->update($accreditation);
      }

      if ($lang === '')
        $lang = $this->request->getParsedBody()['lang'] ?? $this->request->getQueryParams()['lang'] ?? null;
      if ($lang)
        $this->view->assign('language', $lang);

      $this->view->assign('accreditation', $accreditation);
    } else {
      return $this->redirectToUri('https://www.allegria.at/');
    }

    return $this->frontendResponse();

  }

  /**
   * action update
   *
   * @return void
   */
  public function updateFrontendAction(?Accreditation $accreditation = null)
  {
    if (isset($accreditation)) {

      if ($this->request->hasArgument('report') && $this->request->getArgument('report') == 'reject') {

        $accreditation->setStatus(-1);
        $accreditation->setTicketsApproved(0);
        $accreditation->setProgram(0);
        $accreditation->setPass(0);
        $accreditation->setInvitationStatus(-1);

        $logRejected = $this->logGenerator->createAccreditationLog('AI-L-R', $accreditation);
        $accreditation->addLog($logRejected);

        $this->accreditationRepository->update($accreditation);

        if ($accreditation->getGuestOutput() != '' && $accreditation->getGuestOutput() != 'noreply@allegria.at') {
          $this->sendDistributionMail($accreditation, 'reject', true);
        }

        // if ($accreditation->getGuestOutput() != '' && $accreditation->getGuestOutput() != 'noreply@allegria.at')
        //   $accreditation = $this->mailGenerator->createAccreditationMail('AI-Email-R', $accreditation, $this->settings);
        // $this->accreditationRepository->update($accreditation);

        // $this->addModuleFlashMessage(
        //   'Danke für die Rückmeldung. Eine Bestätigung erfolgt via E-Mail.',
        //   'RÜCKMELDUNG ERHALTEN!'
        // );

      } elseif ($this->request->hasArgument('report') && $this->request->getArgument('report') == 'approve') {

        if ($this->request->hasArgument('additionalAnswers')) {

          foreach ($this->request->getArgument('additionalAnswers') as $fieldUid => $value) {

            $additionalField = $this->additionalfieldRepository->findByUid($fieldUid);
            $additionalAnswer = $this->additionalanswerRepository->findByFieldInAccreditation($accreditation->getUid(), $fieldUid);
            $content = '';

            if ($additionalField->getType() == 6)
              foreach ($value as $key => $val) {
                $content .= $key . '|' . $val . "\n";
              } elseif ($additionalField->getType() == 4 && $value)
              foreach ($value as $val) {
                $content .= $val . "\n";
              } else {
              $content = $value;
            }


            if ($additionalAnswer) {

              $additionalAnswer->setValue($content);
              $this->additionalanswerRepository->update($additionalAnswer);

            } else {

              $newAnswer = new Additionalanswer();
              $newAnswer->setType($additionalField->getType());
              $newAnswer->setValue($content);
              $newAnswer->setField($additionalField);
              $newAnswer->setAccreditation($accreditation);

              $accreditation->addAdditionalanswer($newAnswer);

            }

          }

        }

        if ($accreditation->getEvent()->isManualConfirmation() || ($accreditation->getEvent()->getTicketsQuota() > 0 && (($accreditation->getEvent()->getTicketsApproved()) > $accreditation->getEvent()->getTicketsQuota()))) {
          $accreditation->setStatus(-2);
          $accreditation->setTicketsWish($accreditation->getTicketsApproved());
          $accreditation->setTicketsApproved(0);

          $logApproved = $this->logGenerator->createAccreditationLog('AI-L-AW', $accreditation);
          $accreditation->addLog($logApproved);
          $this->persistenceManager->persistAll();

          // if ($accreditation->getGuestOutput() != '' && $accreditation->getGuestOutput() != 'noreply@allegria.at')
          //   $accreditation = $this->mailGenerator->createAccreditationMail('AI-Email-AW', $accreditation, $this->settings);
          // $this->persistenceManager->persistAll();

          // if ($accreditation->getGuestOutput() != '' && $accreditation->getGuestOutput() != 'noreply@allegria.at')
          //   $accreditation = $this->mailGenerator->createAccreditationMail('AI-Email-P', $accreditation, $this->settings);
          // $this->accreditationRepository->update($accreditation);

          if ($accreditation->getGuestOutput() != '' && $accreditation->getGuestOutput() != 'noreply@allegria.at') {
            $this->sendDistributionMail($accreditation, 'waiting', true);
          }
          $this->persistenceManager->persistAll();

          if ($accreditation->getGuestOutput() != '' && $accreditation->getGuestOutput() != 'noreply@allegria.at')
            $accreditation = $this->mailGenerator->createAccreditationMail('AI-Email-P', $accreditation, $this->settings);
          $this->accreditationRepository->update($accreditation);

          $this->addModuleFlashMessage(
            'Danke für die Rückmeldung. Die Veranstaltung hat einen so großen Andrang, dass Sie auf die Warteliste gerutscht sind. Wir prüfen das nochmals manuell und melden uns schnellstmöglich bei Ihnen via Email.',
            'SIE SIND AUF DER WARTELISTE!',
            'WARNING'
          );

        } else {
          $logApproved = $this->logGenerator->createAccreditationLog('AI-L-A', $accreditation);
          $accreditation->addLog($logApproved);
          $this->persistenceManager->persistAll();

          if ($accreditation->getGuestOutput() != '' && $accreditation->getGuestOutput() != 'noreply@allegria.at') {
            $this->sendDistributionMail($accreditation, 'approve', true);
          }
          $this->accreditationRepository->update($accreditation);

          // $this->addModuleFlashMessage(
          //   'Danke für die Rückmeldung. Eine Bestätigung erfolgt via E-Mail.',
          //   'RÜCKMELDUNG ERHALTEN!'
          // );
        }

      } else {

        $this->addModuleFlashMessage(
          'Leider konnte der Vorgang nicht erfolgreich abgeschlossen werden. Bitte prüfen Sie die Eingaben und kontaktieren Sie uns bei Fragen via Email unter office@allegria.at oder telefonisch.',
          'FEHLER BEI RÜCKMELDUNG!',
          'ERROR'
        );

      }

      return $this->redirect('report', 'Accreditation', NULL, ['accreditation' => $accreditation]);
    } else {
      return $this->redirectToUri('https://www.allegria.at/');
    }

  }

  /**
   * action expressApprove
   *
   * @return void
   */
  public function expressApproveAction(Accreditation $accreditation)
  {
    GeneralUtility::makeInstance(EmConfiguration::class);

    $accreditation->setStatus(1);
    $accreditation->setTicketsApproved($accreditation->getTicketsWish());

    $logApproved = $this->logGenerator->createAccreditationLog('A-AE', $accreditation);
    $accreditation->addLog($logApproved);
    $this->persistenceManager->persistAll();

    // MAIL
    if ($accreditation->getGuestOutput() != '' && $accreditation->getGuestOutput() != 'noreply@allegria.at') {
      $this->sendDistributionMail($accreditation, 'approve');
    }

    $this->accreditationRepository->update($accreditation);

    $this->addModuleFlashMessage(
      'Die Akkreditierung wurde erfolgreich bestätigt.',
      'AKKREDITIERUNG BESTÄTIGT!'
    );

    return $this->redirect('overview', 'Presscenter');

  }

  /**
   * action updateCollection
   *
   * @return void
   */
  public function updateCollectionAction(
    Accreditation $accreditation = null,
    Event $event = null
  ) {

    if ($this->request->hasArgument('accreditationsUpdate')) {

      // ACCREDITATIONS TO UPDATE
      foreach ($this->request->getArgument('accreditations') as $accreditation) {
        $accreditations[] = $this->accreditationRepository->findByUid($accreditation);
      }
      ;

      // VARIABLES
      $updates = $this->request->getArgument('accreditationsUpdate');

      // FIELDS TO UPDATE
      $fields = [];
      foreach ($this->request->getArgument('accreditationsUpdate') as $key => $innerArray) {
        if ($key == 'changes') {
          foreach ($innerArray as $field => $value) {
            if ($value && $field != 'additionalfields') {
              $fields[] = $field;
            }
          }
        }
      }


      $additionalfields = [];
      if (!empty($this->request->getArgument('accreditationsUpdate')['changes']['additionalfields'])) {
        foreach ($this->request->getArgument('accreditationsUpdate')['changes']['additionalfields'] as $fieldUid => $value) {
          if ($value) {
            $additionalfields[] = $fieldUid;
          }
        }
      }




      $edited = 0;

      // UPDATE ACCREDITATIONS
      foreach ($accreditations as $accreditation) {
        foreach ($fields as $field) {
          if ($field == 'guestType')
            $accreditation->setGuestType(intval($updates['guestType']));
          if ($field == 'facie')
            $accreditation->setFacie(intval($updates['facie']));
          if ($field == 'ticketsApproved')
            $accreditation->setTicketsApproved(intval($updates['ticketsApproved']));
          if ($field == 'program')
            $accreditation->setProgram(intval($updates['program']));
          if ($field == 'pass')
            $accreditation->setPass(intval($updates['pass']));
          if ($field == 'invitationType')
            $accreditation->setInvitationType($this->invitationRepository->findByUid(intval($updates['invitationType'])));
          if ($updates['notesSelectOverwrite']) {
            foreach ($accreditation->getNotesSelect() as $note)
              $accreditation->removeNotesSelect($note);
          }
          if ($field == 'notesSelect' && $updates['notesSelect']) {
            foreach ($updates['notesSelect'] as $note) {
              $accreditation->addNotesSelect($this->sysCategoryRepository->findByUid(intval($note)));
            }
            ;
          }
          ;
          if ($field == 'notes')
            $accreditation->setNotes($updates['notes']);
        }

        foreach ($additionalfields as $field) {
          if (!empty($updates['additionalAnswers'])) {
            foreach ($updates['additionalAnswers'] as $fieldUid => $tempField) {
              if ($field == $fieldUid) {
                $content = '';
                foreach ($tempField as $key => $val) {
                  $content .= $key . '|' . $val . "\n";
                }

                $additionalField = $this->additionalfieldRepository->findByUid($fieldUid);
                $additionalAnswer = $this->additionalanswerRepository->findByFieldInAccreditation($accreditation->getUid(), $fieldUid);

                if ($additionalAnswer) {

                  $additionalAnswer->setValue($content);
                  $this->additionalanswerRepository->update($additionalAnswer);

                } else {

                  $newAnswer = new Additionalanswer();
                  $newAnswer->setType($additionalField->getType());
                  $newAnswer->setValue($content);
                  $newAnswer->setField($additionalField);
                  $newAnswer->setAccreditation($accreditation);

                  $accreditation->addAdditionalanswer($newAnswer);

                }
              }
            }
          }
        }

        $logUpdated = $this->logGenerator->createAccreditationLog('A-EC', $accreditation, $fields);
        $accreditation->addLog($logUpdated);

        $this->accreditationRepository->update($accreditation);
        $edited++;

      }
      ;

      // PLACE MESSAGE
      $this->addModuleFlashMessage(
        'Es wurden erfolgreich ' . $edited . ' Akkreditierungen geändert.',
        'AKKREDITIERUNGEN GEÄNDERT!'
      );

      return $this->redirect('show', 'Event', NULL, ['event' => $event]);

    }
  }



  /**
   * action delete
   *
   * @return void
   */
  public function deleteAction(Event $event = null, Accreditation $accreditation = null)
  {
    GeneralUtility::makeInstance(EmConfiguration::class);

    if ($this->request->hasArgument('accreditations')) {

      // ACCREDITATIONS TO DELETE
      foreach ($this->request->getArgument('accreditations') as $accreditation) {
        $accreditations[] = $this->accreditationRepository->findByUid($accreditation);
      }
      ;

      $removed = 0;

      foreach ($accreditations as $accreditation) {

        $logDelete = $this->logGenerator->createAccreditationLog('A-D', $accreditation);
        $accreditation->addLog($logDelete);
        $this->accreditationRepository->update($accreditation);

        $this->accreditationRepository->remove($accreditation);
        $removed++;
      }

      // PLACE MESSAGE
      $this->addModuleFlashMessage(
        'Es wurden ' . $removed . ' Akkreditierungen erfolgreich gelöscht.',
        'AKKREDITIERUNGEN GELÖSCHT!'
      );

      return $this->redirect('show', 'Event', NULL, ['event' => $event]);


    } elseif ($accreditation) {

      $logDelete = $this->logGenerator->createAccreditationLog('A-D', $accreditation);
      $accreditation->addLog($logDelete);
      $this->accreditationRepository->update($accreditation);

      $this->accreditationRepository->remove($accreditation);

      // PLACE MESSAGE
      $this->addModuleFlashMessage(
        'Die Akkreditierung wurde erfolgreich gelöscht.',
        'AKKREDITIERUNG GELÖSCHT!'
      );

      return $this->redirect('show', 'Event', NULL, ['event' => $event]);

    } else {


      // PLACE MESSAGE
      $this->addModuleFlashMessage(
        'Es wurden keine Akkreditierungen übergeben.',
        'AKKREDITIERUNGEN NICHT GELÖSCHT!',
        'ERROR'
      );

      return $this->redirect('show', 'Event', NULL, ['event' => $event]);

    }
    ;

  }

  /**
   * Führt eine vollständige Duplikatsprüfung für ein Event aus und aktualisiert die Datensätze.
   *
   * @param Event $event
   * @return void
   */
  private function handleDuplicateCheck(Event $event): void
  {
    // 1. Alle Akkreditierungsdaten in einem Rutsch abrufen
    $accreditationData = $this->accreditationRepository->findForDuplicateCheck($event);
    $accreditationDataMap = [];
    foreach ($accreditationData as $record) {
      $accreditationDataMap[(int) $record['uid']] = $record;
    }

    // 2. Duplikatsgruppen bilden (kombinierte Logik)
    $groups = [];
    $processedUids = [];
    $fuzzyThreshold = 0.4;

    foreach ($accreditationDataMap as $recordUid => $record) {
      if (in_array($recordUid, $processedUids)) {
        continue;
      }

      $currentGroup = [$recordUid];
      $processedUids[] = $recordUid;

      // Finden aller direkten und indirekten Duplikate
      $queue = [$recordUid];
      while (!empty($queue)) {
        $uid = array_shift($queue);
        $compareRecord = $accreditationDataMap[$uid];
        $compareEmail = trim(strtolower($compareRecord['email'] ?: $compareRecord['guest_email'] ?? ''));
        $compareFirstName = trim(strtolower($compareRecord['first_name'] ?: $compareRecord['guest_first_name'] ?? ''));
        $compareLastName = trim(strtolower($compareRecord['last_name'] ?: $compareRecord['guest_last_name'] ?? ''));
        $compareFullName = ($compareFirstName !== '' && $compareLastName !== '') ? ($compareFirstName . '-' . $compareLastName) : '';
        $compareIgnoredUids = array_map('intval', GeneralUtility::trimExplode(',', (string) ($compareRecord['ignored_duplicates'] ?? ''), true));

        foreach (array_keys($accreditationDataMap) as $potentialDuplicateUid) {
          if (in_array($potentialDuplicateUid, $processedUids)) {
            continue;
          }

          $potentialDuplicate = $accreditationDataMap[$potentialDuplicateUid];
          $potentialEmail = trim(strtolower($potentialDuplicate['email'] ?: $potentialDuplicate['guest_email'] ?? ''));
          $potentialFirstName = trim(strtolower($potentialDuplicate['first_name'] ?: $potentialDuplicate['guest_first_name'] ?? ''));
          $potentialLastName = trim(strtolower($potentialDuplicate['last_name'] ?: $potentialDuplicate['guest_last_name'] ?? ''));
          $potentialFullName = ($potentialFirstName !== '' && $potentialLastName !== '') ? ($potentialFirstName . '-' . $potentialLastName) : '';
          $potentialIgnoredUids = array_map('intval', GeneralUtility::trimExplode(',', (string) ($potentialDuplicate['ignored_duplicates'] ?? ''), true));

          // Überprüfe, ob die beiden UIDs in den gegenseitigen Ignorier-Listen stehen
          if (in_array($potentialDuplicateUid, $compareIgnoredUids) || in_array($uid, $potentialIgnoredUids)) {
            continue; // Diese Duplikat-Beziehung wird ignoriert.
          }

          $isDuplicate = false;
          if ($compareEmail !== '' && $potentialEmail !== '' && $compareEmail === $potentialEmail) {
            $isDuplicate = true;
          } elseif ($compareFullName !== '' && $potentialFullName !== '' && $compareFullName === $potentialFullName) {
            $isDuplicate = true;
          } elseif ($compareEmail !== '' && $potentialEmail !== '') {
            $recordLocalPart = substr($compareEmail, 0, strrpos($compareEmail, '@'));
            $potentialLocalPart = substr($potentialEmail, 0, strrpos($potentialEmail, '@'));
            $recordDomain = substr(strrchr($compareEmail, '@'), 1);
            $potentialDomain = substr(strrchr($potentialEmail, '@'), 1);
            if ($recordDomain === $potentialDomain && levenshtein($recordLocalPart, $potentialLocalPart) <= (min(strlen($recordLocalPart), strlen($potentialLocalPart)) * $fuzzyThreshold)) {
              $isDuplicate = true;
            }
          } elseif ($compareFullName !== '' && $potentialFullName !== '') {
            $firstNameMatch = (levenshtein($compareFirstName, $potentialFirstName) <= (min(strlen($compareFirstName), strlen($potentialFirstName)) * $fuzzyThreshold));
            $lastNameMatch = (levenshtein($compareLastName, $potentialLastName) <= (min(strlen($compareLastName), strlen($potentialLastName)) * $fuzzyThreshold));
            $firstNameContains = (str_contains($compareFirstName, $potentialFirstName) || str_contains($potentialFirstName, $compareFirstName));
            $lastNameContains = (str_contains($compareLastName, $potentialLastName) || str_contains($potentialLastName, $compareLastName));

            if (($firstNameMatch && $lastNameMatch) || ($firstNameContains && $lastNameContains)) {
              $isDuplicate = true;
            }
          }

          if ($isDuplicate) {
            $currentGroup[] = $potentialDuplicateUid;
            $processedUids[] = $potentialDuplicateUid;
            $queue[] = $potentialDuplicateUid;
          }
        }
      }

      if (count($currentGroup) > 1) {
        $groups[] = $currentGroup;
      }
    }

    // 3. Duplikatgruppen verarbeiten und Updates festlegen
    $updates = [];
    $duplicatesCount = 0;
    foreach ($groups as $group) {
      $this->processDuplicateGroup($group, $accreditationDataMap, $updates, $duplicatesCount);
    }

    // 4. Updates mit dem DataHandler ausführen
    if (!empty($updates)) {
      $tcaUpdates = [];
      foreach ($updates as $uid => $fields) {
        $tcaUpdates['tx_publicrelations_domain_model_accreditation'][$uid] = $fields;
      }

      if (!empty($tcaUpdates)) {
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start($tcaUpdates, []);
        $dataHandler->process_datamap();
      }

      if ($duplicatesCount > 0) {
        $this->addModuleFlashMessage(
          sprintf('Es wurden %d Duplikate identifiziert und markiert.', $duplicatesCount),
          'DUPLIKATE GEFUNDEN!',
          'WARNING'
        );
      }
    }
  }


  /**
   * Verarbeitet eine Gruppe von Duplikaten, bestimmt den Master und legt die Updates fest.
   *
   * @param array<int> $groupUids
   * @param array<int, array<string, mixed>> $accreditationDataMap
   * @param array<int, array<string, mixed>> &$updates
   * @param int &$duplicatesCount
   * @return void
   */
  private function processDuplicateGroup(array $groupUids, array $accreditationDataMap, array &$updates, int &$duplicatesCount): void
  {
    // Master bestimmen
    $masterUid = 0;
    $foundProcessedMaster = false;

    // Erste Priorität: Suche nach einem bereits bearbeiteten Datensatz
    foreach ($groupUids as $uid) {
      $record = $accreditationDataMap[$uid];
      if ((int) $record['status'] !== 0 || (int) $record['invitation_status'] !== 0) {
        $masterUid = $uid;
        $foundProcessedMaster = true;
        break;
      }
    }

    // Zweite Priorität (nur wenn kein bearbeiteter Datensatz gefunden wurde):
    // Client-Priorität, dann kleinste UID
    if (!$foundProcessedMaster) {
      foreach ($groupUids as $uid) {
        $record = $accreditationDataMap[$uid];
        if ((int) $record['guest'] > 0 && (int) $record['guest_client'] > 0) {
          $masterUid = $uid;
          break;
        }
        if ($masterUid === 0 || $uid < $masterUid) {
          $masterUid = $uid;
        }
      }
    }

    // Updates für die Gruppe festlegen
    foreach ($groupUids as $uid) {
      $record = $accreditationDataMap[$uid];
      $isMaster = ($uid === $masterUid);

      $shouldUpdate = false;
      $recordUpdates = [];

      if ($isMaster) {
        if ((int) $record['is_master'] !== 1) {
          $recordUpdates['is_master'] = 1;
          $shouldUpdate = true;
        }
        if ((int) $record['duplicate_of'] !== 0) {
          $recordUpdates['duplicate_of'] = 0;
          $shouldUpdate = true;
        }
      } else {
        if ((int) $record['is_master'] !== 0) {
          $recordUpdates['is_master'] = 0;
          $shouldUpdate = true;
        }
        if ((int) $record['duplicate_of'] !== $masterUid) {
          $recordUpdates['duplicate_of'] = $masterUid;
          $shouldUpdate = true;
        }

        // Bugfix: Nur Status 9 setzen, wenn status UND invitation_status 0 sind
        if ((int) $record['status'] === 0 && (int) $record['invitation_status'] === 0) {
          if ((int) $record['status'] !== 9) {
            $recordUpdates['status'] = 9;
            $shouldUpdate = true;
          }
        }
        $duplicatesCount++;
      }

      if ($shouldUpdate) {
        $updates[$uid] = $recordUpdates;
      }
    }
  }

  /**
   * Manuell eine Duplikatsprüfung für ein Event auslösen
   *
   * @param Event $event
   * @return ResponseInterface
   */
  public function checkDuplicatesAction(Event $event): ResponseInterface
  {
    $this->handleDuplicateCheck($event);

    // Flash Message anzeigen, falls Duplikate gefunden wurden
    $message = 'Duplikatsprüfung erfolgreich abgeschlossen.';
    $this->addModuleFlashMessage(
      $message,
      'PRÜFUNG ABGESCHLOSSEN!'
    );

    // Redirect zurück zur Event-Ansicht
    return $this->redirect('show', 'Event', null, ['event' => $event]);
  }

  /**
   * Wechselt den Master innerhalb einer Duplikatgruppe.
   *
   * @param Accreditation $newMaster
   * @param array $filter Optionaler Filter-Array für den Redirect.
   * @return \Psr\Http\Message\ResponseInterface
   */
  public function swapMasterAction(Accreditation $newMaster, array $filter = []): \Psr\Http\Message\ResponseInterface
  {
    $currentMaster = $this->accreditationRepository->findByUid($newMaster->getDuplicateOf());

    if (!$currentMaster) {
      $this->addModuleFlashMessage('Der Master konnte nicht gefunden werden.', 'Fehler', 'ERROR');
      return $this->redirect('show', 'Event', null, ['event' => $newMaster->getEvent(), 'filter' => $filter]);
    }

    // Zusätzliche Prüfung: status und invitation_status müssen beide 0 sein
    if ((int) $currentMaster->getInvitationStatus() !== 0 || (int) $currentMaster->getStatus() !== 0) {
      $this->addModuleFlashMessage('Der aktuelle Master wurde bereits bearbeitet, eingeladen oder bestätigt und kann daher nicht geändert werden.', 'Aktion fehlgeschlagen', 'ERROR');
      return $this->redirect('show', 'Event', null, ['event' => $newMaster->getEvent(), 'filter' => $filter]);
    }

    $updates = [];

    // Alten Master zu Duplikat machen
    $updates[$currentMaster->getUid()] = [
      'is_master' => 0,
      'duplicate_of' => $newMaster->getUid(),
      'status' => 9 // Setze alten Master auf Duplikatstatus
    ];

    // Neuen Master setzen
    $updates[$newMaster->getUid()] = [
      'is_master' => 1,
      'duplicate_of' => 0,
      'status' => 0 // Setze neuen Master zurück auf Status 0
    ];

    // Alle Duplikate des alten Masters auf den neuen Master umleiten
    $duplicates = $this->accreditationRepository->findByDuplicateOf($currentMaster->getUid());
    foreach ($duplicates as $duplicate) {
      if ($duplicate->getUid() === $newMaster->getUid())
        continue;
      $updates[$duplicate->getUid()] = [
        'duplicate_of' => $newMaster->getUid()
      ];
    }

    // DataHandler-Updates ausführen
    $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
    $dataHandler->start(['tx_publicrelations_domain_model_accreditation' => $updates], []);
    $dataHandler->process_datamap();

    $this->addModuleFlashMessage('Master erfolgreich gewechselt.', 'Aktion erfolgreich');
    return $this->redirect('show', 'Event', null, ['event' => $newMaster->getEvent(), 'filter' => $filter]);
  }

  /**
   * Entfernt eine Akkreditierung aus einer Duplikatgruppe.
   *
   * @param Accreditation $accreditation
   * @param array $filter Optionaler Filter-Array für den Redirect.
   * @return \Psr\Http\Message\ResponseInterface
   */
  public function removeFromGroupAction(Accreditation $accreditation, array $filter = []): \Psr\Http\Message\ResponseInterface
  {
    $updates = [];
    $masterUid = $accreditation->getDuplicateOf()->getUid(); // Master-UID holen
    $master = null;

    if ($masterUid) {
      $master = $this->accreditationRepository->findByUid($masterUid);
    }

    // Entfernung aus der Gruppe
    $updates[$accreditation->getUid()] = [
      'status' => 0,
      'is_master' => 0,
      'duplicate_of' => 0 // Hier wird der Integer 0 direkt übergeben
    ];

    // Alle Mitglieder der Gruppe finden, einschließlich des Masters
    $groupMembers = [];
    if ($master) {
      $groupMembers[] = $master;
    }
    $duplicates = $this->accreditationRepository->findByDuplicateOf($masterUid);
    foreach ($duplicates as $duplicate) {
      $groupMembers[] = $duplicate;
    }

    // UIDs des zu entfernenden Datensatzes und aller Gruppenmitglieder sammeln
    $accreditationUid = $accreditation->getUid();
    $groupUids = array_map(function ($member) {
      return $member->getUid();
    }, $groupMembers);

    // UIDs der Gruppe in ignored_duplicates des zu entfernenden Datensatzes eintragen
    $accreditation->addIgnoredDuplicates($groupUids);
    $this->accreditationRepository->update($accreditation);

    // UID des zu entfernenden Datensatzes bei allen Gruppenmitgliedern eintragen
    foreach ($groupMembers as $member) {
      if ($member->getUid() !== $accreditationUid) {
        $member->addIgnoredDuplicates($accreditationUid);
        $this->accreditationRepository->update($member);
      }
    }

    // Prüfung, ob der Master nun allein übrig ist
    $remainingDuplicates = $this->accreditationRepository->findByDuplicateOf($masterUid);
    $remainingGroupMembersCount = count($remainingDuplicates);

    if ($master && $remainingGroupMembersCount <= 1) {
      $updates[$master->getUid()] = [
        'is_master' => 0,
        'duplicate_of' => 0 // Hier wird der Integer 0 direkt übergeben
      ];
    }

    // DataHandler-Updates ausführen
    $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
    $dataHandler->start(['tx_publicrelations_domain_model_accreditation' => $updates], []);
    $dataHandler->process_datamap();

    $this->persistenceManager->persistAll();

    $this->addModuleFlashMessage('Akkreditierung erfolgreich aus der Duplikatgruppe entfernt.', 'Aktion erfolgreich');
    return $this->redirect('show', 'Event', null, ['event' => $accreditation->getEvent(), 'filter' => $filter]);
  }

  /**
   * Zeigt eine Live-Vorschau.
   * - Wenn $accreditation übergeben wird, werden deren Daten genutzt.
   * - Wenn nur $event übergeben wird, werden Musterdaten genutzt.
   * - Nutzt das MailView-Layout und deaktiviert Tracking.
   */
  public function mailPreviewAction(
    ?Event $event = null,
    ?Accreditation $accreditation = null,
    ?Invitation $invitation = null,
    ?string $variantCode = 'invite'
  ): ResponseInterface {

    $sampleAccreditation = $accreditation;
    $previewEvent = $event;

    if ($sampleAccreditation === null) {
      // --- Fallback: Generische Vorschau (alter "Test"-Button) ---
      if ($previewEvent === null) {
        return new HtmlResponse('Fehler: Es wurde weder ein Event noch eine Akkreditierung übergeben.', 400);
      }

      // Finde die zu prüfende Einladung
      if ($invitation === null) {
        // Nimm die erste Einladung des Events oder erstelle eine Fallback-Invitation
        $invitation = $previewEvent->getInvitations()->first() ?: null;
        if ($invitation === null) {
          // TODO: Fallback-Invitation-Objekt erstellen, falls keines existiert
          throw new \Exception('Für dieses Event existiert keine Einladung zum Testen.');
        }
      }

      // Erstelle "Musterfrau"-Akkreditierung (Logik aus alter previewAction)
      $sampleAccreditation = new Accreditation();
      $sampleAccreditation->setEvent($previewEvent);
      $sampleAccreditation->setInvitationType($invitation);

      // Fake Guest-Daten setzen (damit getGuestOutput() funktioniert)
      // Wir nutzen die Setter, die wir aus anderen Actions (z.B. handleManualFrontend) kennen.
      $sampleAccreditation->setFirstName('Margarete');
      $sampleAccreditation->setLastName('Musterfrau');
      $sampleAccreditation->setTitle('Dr.');
      $sampleAccreditation->setGender('w'); // Annahme 1=w
      $sampleAccreditation->setEmail('m.musterfrau@beispiel.com');
      $sampleAccreditation->setMedium('Muster-Redaktion');
      $sampleAccreditation->setTicketsWish(2); // Default für Vorschau

    } else {
      // --- Echte Vorschau (alter "Print"-Button) ---
      $previewEvent = $sampleAccreditation->getEvent();
      $invitation = $sampleAccreditation->getInvitationType();
    }

    $resolvedData = null;
    $renderedHtml = '';
    try {
      // --- START: Rendering (Logik aus MailViewController) ---

      // 1. Kontext simulieren
      $simulatedContext = [
        'dataSource' => [
          'function' => $variantCode,
          'dataResolverClass' => AccreditationDataResolver::class
        ],
        'sender_profile' => 1 // Default-Profil für Vorschau
      ];

      // 2. Resolver aufrufen -> Holt LIVE-Daten
      $this->accreditationDataResolver->setJobContext($simulatedContext);

      if ($sampleAccreditation->getUid() === NULL) {
        $this->accreditationDataResolver->setPreviewAccreditation($sampleAccreditation);
      }

      $liveResolvedData = $this->accreditationDataResolver->resolve($sampleAccreditation->getUid() ?: 0);

      // 3. Sender-Profil holen
      $senderProfile = $this->smtpService->getSenderProfile($liveResolvedData, null);
      if ($senderProfile === null) {
        throw new \RuntimeException('Sender-Profil (aus Live-Daten) konnte nicht geladen werden.');
      }

      // 4. MessageRow simulieren
      $simulatedMessageRow = [
        'uid' => 0,
        'open_hash' => 'PREVIEW_HASH_' . md5(microtime())
      ];

      // 5. Mail bauen (OHNE TRACKING)
      $buildResult = $this->mailBuildService->buildMailMessage(
        $liveResolvedData,
        $senderProfile,
        $simulatedMessageRow,
        ['track_links' => false, 'track_opens' => false] // <-- WICHTIG: Tracking aus
      );

      $renderedHtml = $buildResult['mailMessage']->getHtmlBody();

      // Auflösen von Subject/Preheader (für die Debug-Anzeige)
      $resolvedData = $liveResolvedData;
      $resolvedData['subject'] = $buildResult['resolvedContent']['subject'];
      $resolvedData['preheader'] = $buildResult['resolvedContent']['preheader'];

      // --- ENDE: Rendering ---

    } catch (\Exception $e) {
      $renderedHtml = '<div class="alert alert-danger">Fehler beim Rendern der Vorschau: ' . $e->getMessage() . '</div>';
      // Optional: $this->logger->error(...)
    }

    // Alle Einladungen des Events für den Dropdown
    $allInvitations = $previewEvent->getInvitations();

    // Finde die existierenden Variant-Codes für die UI
    $existingVariantCodes = [];

    if ($invitation !== null) {
      foreach ($invitation->getVariants() as $variant) {
        $existingVariantCodes[$variant->getCode()] = true;
      }
    }

    $allVariantCodesWithStatus = [];
    $allVariantCodes = $this->accreditationService->getAllVariantCodes(); // (Array of arrays)
    foreach ($allVariantCodes as $variantInfo) {
      $code = $variantInfo['code'];
      $variantInfo['exists'] = isset($existingVariantCodes[$code]); // Füge 'exists'-Flag hinzu
      $allVariantCodesWithStatus[] = $variantInfo;
    }

    $this->view->assignMultiple([
      'accreditation' => $sampleAccreditation,
      'event' => $previewEvent,
      'resolvedData' => $resolvedData,
      'renderedHtml' => $renderedHtml,
      'allInvitations' => $allInvitations,
      'currentInvitation' => $invitation,
      'allVariantCodes' => $allVariantCodesWithStatus,
      'currentVariantCode' => $variantCode,
      'beUserEmail' => $GLOBALS['BE_USER']->user['email'] ?? ''
    ]);

    // Nutze das Layout von ac_distribution
    $this->setModuleTitle('Mail-Vorschau: ' . $sampleAccreditation->getGuestOutput()['name']);
    return $this->backendResponse();
  }

  /**
   * NEU: AJAX Action für den Test-Versand (liest Payload)
   * Ersetzt die alte Extbase-Action.
   */
  public function sendTestMailAction(ServerRequestInterface $request): ResponseInterface
  {
    // 1. Lese Payload (POST-Daten)
    $body = $request->getParsedBody();
    $variantCodes = GeneralUtility::trimExplode(',', (string) ($body['variantCodes'] ?? ''), true);
    $emails = GeneralUtility::trimExplode(',', (string) ($body['emailList'] ?? ''), true);

    $accreditationUid = (int) ($body['accreditation'] ?? 0);
    $eventUid = (int) ($body['event'] ?? 0);
    $invitationUid = (int) ($body['invitation'] ?? 0);

    if (empty($emails) || empty($variantCodes)) {
      return new JsonResponse(['success' => false, 'message' => 'Fehler: Keine E-Mail-Adressen oder Varianten ausgewählt.'], 400);
    }

    // 3. Hole oder erstelle die Akkreditierung (Fake "Margarete")
    $accreditation = null;
    if ($accreditationUid > 0) {
      $accreditation = $this->accreditationRepository->findByUid($accreditationUid);
    } else {
      // "Fake"-Modus (Margarete Mustermann)
      $event = $this->eventRepository->findByUid($eventUid);
      $invitation = $this->invitationRepository->findByUid($invitationUid);

      if (!$event || !$invitation) {
        return new JsonResponse(['success' => false, 'message' => 'Fehler: Event oder Einladung für Fake-Vorschau nicht gefunden.'], 400);
      }

      $accreditation = new Accreditation();
      $accreditation->setEvent($event);
      $accreditation->setInvitationType($invitation);
      $accreditation->setFirstName('Margarete');
      $accreditation->setLastName('Mustermann');
      $accreditation->setTitle('Dr.');
      $accreditation->setGender('w');
      $accreditation->setEmail('m.mustermann@beispiel.com');
      $accreditation->setMedium('Muster-Redaktion');
      $accreditation->setTicketsWish(2);
    }

    if ($accreditation === null) {
      return new JsonResponse(['success' => false, 'message' => 'Akkreditierung konnte nicht geladen werden.'], 404);
    }

    // 4. Sende-Schleife (ruft DistributionService)
    $errors = [];
    $successCount = 0;

    foreach ($emails as $email) {
      foreach ($variantCodes as $code) {
        $dispatchResult = null;
        try {
          $context = [
            'dataSource' => [
              'function' => $code,
              'dataResolverClass' => AccreditationDataResolver::class,
              'uids' => [$accreditation->getUid() ?: 0]
            ],
            'context' => 'Testversand von Typ ' . ($code ?? 'Unbekannt') . ' zu ' . ($accreditation?->getEvent()?->getTitle() ?? 'Unbekanntes Event'),
            'sender_profile' => 1,
            'report' => ['no_report' => true],
            'test_send' => true,
            'test_recipient_email' => $email
          ];

          if ($accreditation->getUid() === 0) {
            $this->accreditationDataResolver->setPreviewAccreditation($accreditation);
          }

          // Führe den Sendeversuch aus
          $dispatchResult = $this->distributionService->send($context);

          // Prüfe das Ergebnis von send()
          if ($dispatchResult['status'] === 'sent') {
            $successCount++;
          } else {
            // send() hat den Fehler abgefangen und als Array zurückgegeben
            $errors[] = "Fehler bei '$code' an '$email': " . ($dispatchResult['message'] ?? 'Unbekannter Fehler');
          }

        } catch (\Exception $e) {
          $errors[] = "Fehler bei '$code' an '$email': " . $e->getMessage();
        }
      }
    }

    if (!empty($errors)) {
      return new JsonResponse([
        'success' => false,
        'message' => "Teilweise erfolgreich ($successCount gesendet).", // Kurze Hauptnachricht
        'errors' => $errors // <-- Sende die Fehler als Array
      ], 500);
    }
    return new JsonResponse(['success' => true, 'message' => ($successCount . ' Test-Mails erfolgreich versandt.')]);
  }

  /**
   * NEUER HELPER: Sendet eine E-Mail über den acDistribution Service
   * und fängt alle Fehler ab, um sie als FlashMessage auszugeben.
   *
   * @param Accreditation $accreditation Das Akkreditierungs-Objekt
   * @param string $functionCode Der NEUE Funktions-Code (z.B. 'invite', 'approve')
   * @return bool True bei Erfolg, False bei Fehler
   */
  private function sendDistributionMail(Accreditation $accreditation, string $functionCode, bool $jobContext = false): bool
  {
    try {
      // 1. Kontext für den Versand-Service erstellen
      $context = [
        'dataSource' => [
          'function' => $functionCode,
          'dataResolverClass' => AccreditationDataResolver::class,
          'uids' => [$accreditation->getUid() ?: 0] // 0 bei "Fake"-Akkr. (sollte hier nicht passieren)
        ],
        'context' => 'Einzelversand von Typ ' . ($functionCode ?? 'Unbekannt') . ' zu ' . ($accreditation?->getEvent()?->getTitle() ?? 'Unbekanntes Event'),
        'contact' => $accreditation?->getGuest()?->getUid() ?: 0,
        'sender_profile' => 1, // ⚠️ Fallback: Immer Profil 1
        'report' => ['no_report' => true], // Keinen Job-Bericht
      ];

      // 2. Sende über die "Eine Logik"
      // Da UID-Count = 1, wird automatisch der DirectSendService (BE)
      // oder queueJob (FE/CLI) genutzt.
      $dispatchResult = $this->distributionService->send($context);
      $status = $dispatchResult['status'] ?? 'error';

      // 3. Ergebnis prüfen (ANGEPASSTE LOGIK)

      // Fall 1: Erfolg im Frontend-Kontext (Job wurde erstellt)
      if ($status === 'queued' && $jobContext) {
        // Wie gewünscht: Gib die "Job"-Erfolgsmeldung aus
        $this->addModuleFlashMessage(
          'Danke für die Rückmeldung. Eine Bestätigung wird in den nächsten Minuten via E-Mail bei Ihnen eintreffen.',
          'RÜCKMELDUNG WIRD VERARBEITET!'
        );
        return true;
      }

      // Fall 2: Erfolg im Backend-Kontext (Direktversand)
      if ($status === 'sent' && !$jobContext) {
        // Gib keine Message aus, die aufrufende BE-Funktion (z.B. updateAction)
        // kümmert sich um die "Erfolg!"-Meldung.
        return true;
      }

      // Fall 3: Alle anderen Fälle sind Fehler
      // (z.B. 'error', 'empty', oder ein Mismatch wie 'sent' im jobContext)
      $errorMessage = 'Mail-Fehler (Code ' . $functionCode . '): ';
      $errorMessage .= $dispatchResult['message'] ?? $status;

      $this->addModuleFlashMessage($errorMessage, 'Mail-Fehler', 'ERROR');
      return false;

    } catch (\Exception $e) {
      // "Harter" Fehler (z.B. SMTP-Server down, Resolver-Klasse fehlt)
      $this->addModuleFlashMessage(
        'Mail-Fehler (Code ' . $functionCode . '): ' . $e->getMessage(),
        'Kritischer Mail-Fehler',
        'ERROR'
      );
      return false;
    }
  }

}
