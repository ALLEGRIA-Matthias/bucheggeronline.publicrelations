<?php
namespace BucheggerOnline\Publicrelations\Controller;

use Psr\Http\Message\ResponseInterface;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Pagination\SimplePagination;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Extbase\Pagination\QueryResultPaginator;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Backend\Template\Components\Buttons\Action\ShortcutButton;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Core\Imaging\IconFactory;

use BucheggerOnline\Publicrelations\Domain\Model\Dto\EmConfiguration;
use BucheggerOnline\Publicrelations\Utility\GeneralFunctions;
use BucheggerOnline\Publicrelations\Utility\LogGenerator;
use BucheggerOnline\Publicrelations\Helper\HiddenObjectHelper;

use BucheggerOnline\Publicrelations\Domain\Repository\ClientRepository;
use BucheggerOnline\Publicrelations\Domain\Model\Client;

use BucheggerOnline\Publicrelations\Domain\Repository\CampaignRepository;
use BucheggerOnline\Publicrelations\Domain\Model\Campaign;

use BucheggerOnline\Publicrelations\Domain\Repository\EventRepository;
use BucheggerOnline\Publicrelations\Domain\Model\Event;

use BucheggerOnline\Publicrelations\Domain\Repository\LocationRepository;
use BucheggerOnline\Publicrelations\Domain\Model\Location;

use BucheggerOnline\Publicrelations\Domain\Repository\AccreditationRepository;
use BucheggerOnline\Publicrelations\Domain\Model\Accreditation;

use BucheggerOnline\Publicrelations\Domain\Repository\SysCategoryRepository;
use BucheggerOnline\Publicrelations\Domain\Model\SysCategory;

use BucheggerOnline\Publicrelations\Domain\Repository\StaticInfoCountryRepository;
use BucheggerOnline\Publicrelations\Domain\Model\StaticInfoCountry;

use BucheggerOnline\Publicrelations\Domain\Repository\AdditionalfieldRepository;
use BucheggerOnline\Publicrelations\Domain\Model\Additionalfield;

use BucheggerOnline\Publicrelations\Domain\Repository\AdditionalanswerRepository;
use BucheggerOnline\Publicrelations\Domain\Model\Additionalanswer;

use BucheggerOnline\Publicrelations\Icalcreator\Vcalendar;
use function intval;

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
 * EventController
 */
class EventController extends AbstractPublicrelationsController
{
  public function __construct(
    private readonly PersistenceManager $persistenceManager,
    private readonly EventRepository $eventRepository,
    private readonly ClientRepository $clientRepository,
    private readonly CampaignRepository $campaignRepository,
    private readonly LocationRepository $locationRepository,
    private readonly AccreditationRepository $accreditationRepository,
    private readonly SysCategoryRepository $sysCategoryRepository,
    private readonly StaticInfoCountryRepository $staticInfoCountryRepository,
    private readonly AdditionalfieldRepository $additionalfieldRepository,
    private readonly AdditionalanswerRepository $additionalanswerRepository,
    private readonly EmConfiguration $emConfiguration,
    private readonly LogGenerator $logGenerator,
    private readonly GeneralFunctions $generalFunctions
  ) {
  }


  /**
   * Prüft, ob ein Datum mit dem gegebenen Format gültig ist.
   *
   * @param string $date   Datum als String
   * @param string $format erwartetes Format, z.B. 'd.m.Y H:i'
   * @return bool          true, wenn $date exakt dem Format entspricht
   */
  protected function validateDate(string $date, string $format = 'd.m.Y H:i'): bool
  {
    $dt = \DateTime::createFromFormat($format, $date);
    return ($dt instanceof \DateTime)
      && $dt->format($format) === $date;
  }



  /**
   * Erzeugt Zeitpunkte zwischen Beginn und Ende nach wiederkehrenden Modifikatoren.
   *
   * @param string[]              $schedule Array von Datums-Modifiern, z.B. ['+1 day', '+1 week']
   * @param \DateTimeInterface    $start    Beginn der Periode
   * @param \DateTimeInterface    $end      Ende der Periode
   * @return \DateTimeInterface[] Array aller folgenden Termine (ohne das End-Datum)
   */
  protected function schedulePeriod(array $schedule, \DateTimeInterface $start, \DateTimeInterface $end): array
  {
    $period = [];
    // Wir brauchen eine mutable Kopie
    $current = \DateTime::createFromFormat(
      \DateTime::ATOM,
      $start->format(\DateTime::ATOM),
      $start->getTimezone()
    );
    if (!$current) {
      // Fallback: Klonen
      $current = clone $start;
    }

    while (true) {
      // Als Obergrenze nehmen wir immer das End-Datum als mutable Kopie
      $nextClosest = \DateTime::createFromFormat(
        \DateTime::ATOM,
        $end->format(\DateTime::ATOM),
        $end->getTimezone()
      ) ?: clone $end;

      foreach ($schedule as $modifier) {
        // Modifier auf Kopie von $current anwenden
        $candidate = (clone $current)->modify($modifier);
        if ($candidate > $current && $candidate < $nextClosest) {
          $nextClosest = $candidate;
        }
      }

      // Ist der nächste Termin bereits außerhalb?
      if ($nextClosest >= $end) {
        break;
      }

      $period[] = $nextClosest;
      $current = $nextClosest;
    }

    return $period;
  }


  /**
   * Listet Events im Backend (Filter, Client, Campaign oder alle).
   *
   * @param Client|null   $client   Client-Filter
   * @param Campaign|null $campaign Campaign-Filter
   * @param array|null    $filter   Zusätzliche Filter (findFiltered)
   * @return void
   */
  public function listAction(Client $client = null, Campaign $campaign = null, array $filter = null): ResponseInterface
  {


    // Event-Typen und Clients für das Filter-Formular
    $eventTypes = $this->sysCategoryRepository->findByParentUid(
      $this->emConfiguration->getEventRootUid()
    );
    $clients = $this->clientRepository->findAll();

    // 1) Events je nach Eingabe holen
    $events = match (true) {
      is_array($filter) && !empty($filter) =>
      $this->eventRepository->findFiltered($filter),
      $client !== null =>
      $this->eventRepository->findByProperty('client', $client->getUid(), 'upcoming'),
      $campaign !== null =>
      $this->eventRepository->findByProperty('campaign', $campaign->getUid(), 'upcoming'),
      default =>
      $this->eventRepository->findAllBackend('upcoming'),
    };

    // Pagination-Einstellungen aus TypoScript-Settings
    $paginationConfig = $this->settings['list']['paginate'] ?? [];
    $maxLinks = (int) ($paginationConfig['maximumNumberOfLinks'] ?? 0);
    $requestedItemsPerPage = (int) ($paginationConfig['itemsPerPage'] ?? 0);
    $filterMax = (int) ($filter['max'] ?? 0);

    // 2) itemsPerPage bestimmen (Setting > Filter.max > 25)
    $itemsPerPage = $requestedItemsPerPage ?: $filterMax;
    if ($itemsPerPage <= 0) {
      $itemsPerPage = 25;
    }

    // 3) aktuelle Seite (Argument oder 1)
    $currentPage = $this->request->hasArgument('currentPage')
      ? (int) $this->request->getArgument('currentPage')
      : 1;

    // 4) Paginator instanziieren
    $paginator = GeneralUtility::makeInstance(
      QueryResultPaginator::class,
      $events,
      $currentPage,
      $itemsPerPage
    );

    // 5) Pagination-Klasse wählen via match
    $paginationClass = $paginationConfig['class'] ?? SimplePagination::class;
    $pagination = match (true) {
      $paginationClass === NumberedPagination::class
      && $maxLinks > 0
      && class_exists(NumberedPagination::class)
      => GeneralUtility::makeInstance(NumberedPagination::class, $paginator, $maxLinks),

      class_exists($paginationClass)
      => GeneralUtility::makeInstance($paginationClass, $paginator),

      default
      => GeneralUtility::makeInstance(SimplePagination::class, $paginator),
    };

    // 6) View-Variablen zuweisen
    $this->view->assignMultiple([
      'filter' => $filter,
      'eventTypes' => $eventTypes,
      'clients' => $clients,
      'events' => $events,
      'settings' => $this->settings,
      'pagination' => [
        'currentPage' => $currentPage,
        'paginator' => $paginator,
        'instance' => $pagination,
      ],
    ]);

    $this->setModuleTitle('Terminverwaltung');
    return $this->backendResponse();
  }

  public function iCalAction(): ResponseInterface
  {
    // 1) Events optimiert holen
    $events = $this->eventRepository->findICalForRendering(); // Deine optimierte Methode

    // 2) Zeitzone
    $tz = new \DateTimeZone('UTC');

    // 3) Vcalendar initialisieren
    $vcalendar = Vcalendar::factory([
      Vcalendar::UNIQUE_ID => 'allegria.at', // Oder deine Domain
    ])
      ->setMethod(Vcalendar::PUBLISH)
      ->setXprop(Vcalendar::X_WR_CALNAME, 'allegria.at Eventkalender')
      ->setXprop(Vcalendar::X_WR_CALDESC, 'Veranstaltungskalender von allegria.at')
      ->setXprop(Vcalendar::X_WR_TIMEZONE, 'UTC');

    $sequence = 0;
    if ($events !== null) {
      foreach ($events as $event) {
        /** @var \BucheggerOnline\Publicrelations\Domain\Model\Event $event */

        $durationValue = $event->getDuration();
        $duration = ($durationValue > 0)
          ? new \DateInterval('PT' . (int) $durationValue . 'M')
          : new \DateInterval('PT120M'); // Standard 2 Stunden

        $eventDate = $event->getDate();
        if (!$eventDate instanceof \DateTimeInterface) {
          continue; // Ungültiges Datum, Event überspringen
        }
        // Stelle sicher, dass das Datum als UTC interpretiert oder nach UTC konvertiert wird
        // Wenn $event->getDate() bereits ein DateTime-Objekt mit korrekter Zeitzone liefert:
        $start = (clone $eventDate)->setTimezone($tz); // Klonen, um das Original nicht zu verändern

        $status = ($event->isCanceled() || $event->getNewEvent()) // Annahme: isNewEvent() für getNewEvent()
          ? Vcalendar::CANCELLED
          : Vcalendar::CONFIRMED;

        $locationName = 'Online';
        $locationObject = $event->getLocation(); // Sollte dank Eager Loading direkt verfügbar sein
        if ($locationObject && method_exists($locationObject, 'getName') && $locationObject->getName()) {
          $locationName = $locationObject->getName();
        }

        $eventTitle = $event->getTitle() ?? 'Unbenanntes Event';

        $vcalendar->newVevent()
          ->setDtstart($start)
          ->setDuration($duration)
          ->setUid(sprintf('event-id-%d@allegria.at', $event->getUid()))
          ->setStatus($status)
          // Die Beschreibung sollte idealerweise Text sein, keine HTML-Referenz via CID für ICS.
          // Wenn du eine URL zur Eventdetailseite hast, wäre das besser:
          // ->setDescription($event->getSomeDescriptionAsText() . "\n\nMehr Infos: " . $eventUrl)
          // Fürs Erste lasse ich deine CID-Logik, aber prüfe, ob das gewollt ist.
          // ->setDescription($event->getDescription() ?: '', $event->getDescription() ? [Vcalendar::ALTREP => 'CID:<office@allegria.at>'] : [])
          ->setLocation($locationName)
          ->setSequence($sequence++)
          ->setSummary(html_entity_decode($eventTitle, ENT_QUOTES | ENT_HTML5, 'UTF-8'))
          ->setTransp(Vcalendar::OPAQUE)
          ->setClass(Vcalendar::P_BLIC) // Korrigiert von P_BLIC, falls das ein Tippfehler war
          ->setOrganizer('office@allegria.at', [Vcalendar::CN => 'Allegria Communications']);
      }
    }

    $calendarString = $vcalendar->vtimezonePopulate()->createCalendar();

    // Erstelle eine PSR-7 Response direkt
    $response = $this->responseFactory->createResponse()
      ->withHeader('Content-Type', 'text/calendar; charset=utf-8')
      ->withHeader('Content-Disposition', 'inline; filename="calendar.ics"');

    $response->getBody()->write($calendarString);
    return $response;
  }


  /**
   * action archive
   *
   * @return void
   */
  public function archiveAction(): ResponseInterface
  {
    // 1) Alle archivierten Events holen
    $archivedEvents = $this->eventRepository->findAllBackend('archived');

    // 2) Pagination-Konfiguration aus TypoScript-Settings
    $paginationConfig = $this->settings['list']['paginate'] ?? [];

    // 3) Items per page (Default 25)
    $itemsPerPage = (int) ($paginationConfig['itemsPerPage'] ?? 25);
    if ($itemsPerPage < 1) {
      $itemsPerPage = 25;
    }

    // 4) Aktuelle Seite ermitteln
    $currentPage = $this->request->hasArgument('currentPage')
      ? (int) $this->request->getArgument('currentPage')
      : 1;

    // 5) Maximale Link-Anzahl für NumberedPagination
    $maximumNumberOfLinks = (int) ($paginationConfig['maximumNumberOfLinks'] ?? 0);

    // 6) Paginator erzeugen
    $paginator = GeneralUtility::makeInstance(
      QueryResultPaginator::class,
      $archivedEvents,
      $currentPage,
      $itemsPerPage
    );

    // 7) Pagination-Klasse wählen
    $paginationClass = $paginationConfig['class'] ?? SimplePagination::class;
    if (
      $paginationClass === NumberedPagination::class &&
      $maximumNumberOfLinks > 0 &&
      class_exists(NumberedPagination::class)
    ) {
      $pagination = GeneralUtility::makeInstance(
        NumberedPagination::class,
        $paginator,
        $maximumNumberOfLinks
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

    // 8) Variablen ans Template übergeben
    $this->view->assignMultiple([
      'archivedEvents' => $archivedEvents,
      'settings' => $this->settings,
      'pagination' => [
        'currentPage' => $currentPage,
        'paginator' => $paginator,
        'instance' => $pagination,
      ],
    ]);

    $this->setModuleTitle('Terminarchiv');
    return $this->backendResponse();
  }


  /**
   * action new
   *
   * @return void
   */
  public function newAction(): ResponseInterface
  {
    $events = $this->eventRepository->findICalForRendering();

    // Daten aus den Repositories holen
    $clients = $this->clientRepository->findAll();
    $campaigns = $this->campaignRepository->findAllBackend();
    $eventTypes = $this->sysCategoryRepository->findByParentUid(
      $this->emConfiguration->getEventRootUid()
    );
    $locations = $this->locationRepository->findAll();
    $notes = $this->sysCategoryRepository->findByParentUid(
      $this->emConfiguration->getEventNoteRootUid()
    );

    // Alle Werte auf einmal ans View übergeben
    $this->view->assignMultiple([
      'clients' => $clients,
      'campaigns' => $campaigns,
      'eventTypes' => $eventTypes,
      'locations' => $locations,
      'notes' => $notes,
    ]);


    $this->setModuleTitle('Termingenerator');
    return $this->backendResponse();
  }

  /**
   * action create
   *
   * @return void
   */
  public function createAction(): ResponseInterface
  {

    // 2) Alle neuen Event-Daten auf einmal holen
    $data = $this->request->getArgument('newEvents');

    // 3) Type, Client, Campaign
    $type = $this->sysCategoryRepository->findByUid((int) $data['type']);
    $client = $this->clientRepository->findByUid((int) $data['client'], false);
    $campaign = !empty($data['campaign'])
      ? $this->campaignRepository->findByUid((int) $data['campaign'], false)
      : null;

    // 4) Location (entweder neu anlegen oder aus Repository holen)
    if (($data['location'] ?? '') === 'new') {
      $locData = $data['newLocation'];
      $location = GeneralUtility::makeInstance(\BucheggerOnline\Publicrelations\Domain\Model\Location::class);
      $location->setName($locData['name']);
      $location->setAdditional($locData['additional']);
      $location->setStreet($locData['street']);
      $location->setZip($locData['zip']);
      $location->setCity($locData['city']);
      $location->setCountry(
        $this->staticInfoCountryRepository->findByUid((int) $locData['country'])
      );
    } else {
      $location = $this->locationRepository->findByUid((int) $data['location']);
    }

    // 5) Notes (Kategorie-UIDs)
    $noteIds = [];
    if (!empty($data['notes']) && is_array($data['notes'])) {
      foreach ($data['notes'] as $noteId) {
        $noteIds[] = (int) $noteId;
      }
    }

    // 6) Dates je nach Generator-Typ sammeln
    $generator = $this->request->getArgument('generator');
    $dates = [];
    $failed = 0;

    if ($generator === 'single') {
      $raw = $this->request->getArgument('singledate');

      // Entferne das 'Z', falls vorhanden, um den reinen Zeitstring zu bekommen
      $localTimeString = str_replace('Z', '', $raw); // Ergibt '2025-05-12T20:15:00'

      // Definiere die Zeitzone, in der die Eingabe gemacht wurde (deine User-Zeitzone)
      // Diese Information müsstest du kennen oder vom User-Profil bekommen.
      // Für dich in Wien wäre es z.B. 'Europe/Vienna'.
      $userLocalTimeZone = new \DateTimeZone('Europe/Vienna'); // Wichtig: PHP muss diese Zeitzone kennen!

      try {
        // Erstelle das DateTime-Objekt mit der Annahme, dass $localTimeString in $userLocalTimeZone ist
        $dateTimeInUserLocalZone = new \DateTimeImmutable($localTimeString, $userLocalTimeZone);

        // $dateTimeInUserLocalZone ist jetzt z.B. 12. Mai 2025, 20:15 Uhr CEST

        // Für die Speicherung oder Weiterverarbeitung ist es oft am besten, es in UTC zu haben:
        $utcTimeZone = new \DateTimeZone('UTC');
        $dateTimeInUtc = $dateTimeInUserLocalZone->setTimezone($utcTimeZone);

        // $dateTimeInUtc wäre dann z.B. 12. Mai 2025, 18:15 Uhr UTC (wenn CEST = UTC+2)
        $dates[] = $dateTimeInUtc; // Speichere die UTC-Version

      } catch (\Exception $e) {
        $failed++;
        // Fehlerbehandlung
      }
    } elseif ($generator === 'list') {
      $format = $this->request->getArgument('dateformat');
      $lines = explode("\n", $this->request->getArgument('dates'));
      foreach ($lines as $line) {
        $raw = trim($line);
        if (!$raw) {
          continue;
        }
        if (
          $this->generalFunctions
            ->validateDate($raw, $format)
        ) {
          $dates[] = \DateTime::createFromFormat($format, $raw);
        } else {
          $failed++;
        }
      }
    } elseif ($generator === 'period') {
      $range = $this->request->getArgument('daterange');
      $start = new \DateTime(trim($range['start']));
      $end = (new \DateTime(trim($range['end'])))->modify('+1 day');
      $schedule = $this->request->getArgument('schedule');
      $dates = $this->generalFunctions
        ->createDatesFromSchedule($schedule, ['start' => $start, 'end' => $end]);
    }

    // 7) Events anlegen
    $created = 0;
    if (!empty($dates)) {
      foreach ($dates as $date) {
        $event = new Event();
        $event->setPid(2);
        $event->setDate($date);
        $event->setType($type);
        $event->setTitle($data['title']);
        $event->setClient($client);
        if ($campaign !== null) {
          $event->setCampaign($campaign);
        }
        $event->setLocation($location);
        $event->setLocationNote($data['locationNote']);
        $event->setAccreditation((int) ($data['accreditation'] ?? 0));
        $event->setDuration((int) ($data['duration'] ?? 0));
        $event->setDurationApprox(((int) ($data['durationApprox'] ?? 0)) === 1);
        $event->setDurationWithBreak(((int) ($data['durationWithBreak'] ?? 0)) === 1);
        $event->setOnline(((int) ($data['online'] ?? 0)) === 1);

        // 7a) Notes hinzufügen
        foreach ($noteIds as $noteUid) {
          $category = $this->sysCategoryRepository->findByUid($noteUid);
          $event->addNote($category);
        }

        // 7b) Log je nach Generator
        switch ($generator) {
          case 'list':
            $log = $this->logGenerator
              ->createEventLog('E-CR0', $event);
            break;
          case 'period':
            $log = $this->logGenerator
              ->createEventLog('E-CR2', $event);
            break;
          default:
            $log = $this->logGenerator
              ->createEventLog('E-CR1', $event);
        }
        $event->addLog($log);

        // 7c) Speichern
        $this->eventRepository->add($event);
        $created++;
      }

      $this->addModuleFlashMessage(
        "Es wurden erfolgreich {$created} Termine erstellt.",
        'TERMINE ERSTELLT!'
      );
    } else {
      $this->addModuleFlashMessage(
        'Es wurden keine Termine erstellt, da die Daten unzureichend waren.',
        'KEINE TERMINE ERSTELLT!',
        'WARNING'
      );
    }

    if ($failed > 0) {
      $this->addModuleFlashMessage(
        "Es konnten {$failed} Termine nicht hinzugefügt werden (ungültiges Datum).",
        'FEHLER BEI DER ERSTELLUNG',
        'ERROR'
      );
    }

    // 8) Zurück zur Liste
    return $this->redirect('list', 'Event');
  }


  /**
   * action editCollection
   *
   * @return void
   */
  public function editCollectionAction(): ResponseInterface
  {
    // 1) Extension-Konfiguration laden


    // 2) Aus dem Request holen, welche IDs angeklickt wurden
    $submitted = $this->request->getArgument('events') ?? [];

    // 3) Validierte Event-Objekte sammeln
    $events = [];
    foreach ($submitted as $uid => $selected) {
      if ($selected) {
        $eventObj = $this->eventRepository->findByUid((int) $uid);
        if ($eventObj !== null) {
          $events[] = $eventObj;
        }
      }
    }

    // 4) Keine Auswahl? → Warnung + Redirect
    if (empty($events)) {
      $this->addModuleFlashMessage(
        'Du hast leider keine Termine ausgewählt, die bearbeitet werden könnten.',
        'BITTE WÄHLE ZUERST TERMINE AUS',
        'WARNING'
      );
      return $this->redirect('list', 'Event');
    }

    // 5) Falls Postpone gewünscht, direkt dorthin weiterleiten
    if ($this->request->hasArgument('postponeCollection')) {
      // Array von Event-Objekten in eine UID-Liste umwandeln:
      $uids = array_map(fn(Event $e) => $e->getUid(), $events);

      return $this->redirect(
        'postponeCollection',
        'Event',
        null,
        ['events' => $uids]
      );
    }

    // 6) Alle weiteren Daten für das Template sammeln
    $eventTypes = $this->sysCategoryRepository->findByParentUid(
      $this->emConfiguration->getEventRootUid()
    );
    $locations = $this->locationRepository->findAll();
    $notes = $this->sysCategoryRepository->findByParentUid(
      $this->emConfiguration->getEventNoteRootUid()
    );

    // 7) An View übergeben
    $this->view->assignMultiple([
      'events' => $events,
      'eventTypes' => $eventTypes,
      'locations' => $locations,
      'notes' => $notes,
    ]);

    $this->setModuleTitle('Terminauswahl ändern');
    return $this->backendResponse();
  }

  /**
   * action postponeCollection
   *
   * @return void
   */
  public function postponeCollectionAction(): ResponseInterface
  {

    // 2) UIDs aus dem Request holen (kann ein String oder Array sein)
    $raw = $this->request->getArgument('events') ?? [];
    $uids = is_array($raw) ? $raw : [$raw];

    // 3) In Event-Objekte umwandeln
    $eventObjects = [];
    foreach ($uids as $uid) {
      $event = $this->eventRepository->findByUid((int) $uid);
      if ($event instanceof Event) {
        $eventObjects[] = $event;
      }
    }

    // 4) Locations für das Template
    $locations = $this->locationRepository->findAll();

    // 5) An View übergeben
    $this->view->assignMultiple([
      'events' => $eventObjects,
      'locations' => $locations,
    ]);

    $this->setModuleTitle('Terminauswahl verschieben');
    return $this->backendResponse();
  }

  /**
   * action updateCollection
   *
   * @return void
   */
  public function updateCollectionAction(): ResponseInterface
  {
    // 1) Prüfen, ob bulk‐Update angefordert wurde
    if ($this->request->hasArgument('eventsUpdate')) {
      $eventUids = $this->request->getArgument('events') ?? [];
      $updates = $this->request->getArgument('eventsUpdate');
      $changes = $updates['changes'] ?? [];
      $fieldsToUpd = array_keys(array_filter($changes, function ($v) {
        return (bool) $v;
      }));

      $edited = 0;
      foreach ($eventUids as $uid) {
        $event = $this->eventRepository->findByUid((int) $uid);
        if (!$event) {
          continue;
        }
        // 2) Einzelne Setter aufrufen
        if (in_array('title', $fieldsToUpd, true)) {
          $event->setTitle((string) ($updates['title'] ?? ''));
        }
        if (in_array('type', $fieldsToUpd, true)) {
          $type = $this->sysCategoryRepository->findByUid((int) ($updates['type'] ?? 0));
          if ($type) {
            $event->setType($type);
          }
        }
        if (in_array('accreditation', $fieldsToUpd, true)) {
          $event->setAccreditation((int) ($updates['accreditation'] ?? 0));
        }
        // Notizen löschen, falls gewünscht
        if (!empty($updates['notesOverwrite'])) {
          foreach ($event->getNotes() as $note) {
            $event->removeNote($note);
          }
        }
        // Notizen neu setzen
        if (in_array('notes', $fieldsToUpd, true) && !empty($updates['notes'])) {
          foreach ((array) $updates['notes'] as $noteUid) {
            $note = $this->sysCategoryRepository->findByUid((int) $noteUid);
            if ($note) {
              $event->addNote($note);
            }
          }
        }
        if (in_array('location', $fieldsToUpd, true)) {
          if (($updates['location'] ?? '') === 'new' && !empty($updates['newlocation'])) {
            $locData = $updates['newlocation'];
            $location = new \BucheggerOnline\Publicrelations\Domain\Model\Location();
            $location->setName((string) ($locData['name'] ?? ''));
            $location->setAdditional((string) ($locData['additional'] ?? ''));
            $location->setStreet((string) ($locData['street'] ?? ''));
            $location->setZip((string) ($locData['zip'] ?? ''));
            $location->setCity((string) ($locData['city'] ?? ''));
            $country = $this->staticInfoCountryRepository->findByUid((int) ($locData['country'] ?? 0));
            if ($country) {
              $location->setCountry($country);
            }
            // hier ggf. $this->locationRepository->add($location); wenn benötigt
          } else {
            $location = $this->locationRepository->findByUid((int) ($updates['location'] ?? 0));
          }
          if (!empty($location)) {
            $event->setLocation($location);
          }
        }
        if (in_array('locationNote', $fieldsToUpd, true)) {
          $event->setLocationNote((string) ($updates['locationNote'] ?? ''));
        }
        if (in_array('online', $fieldsToUpd, true)) {
          $event->setOnline((int) ($updates['online'] ?? 0));
        }
        if (in_array('duration', $fieldsToUpd, true)) {
          $event->setDuration((int) ($updates['duration'] ?? 0));
        }
        if (in_array('durationApprox', $fieldsToUpd, true)) {
          $event->setDurationApprox(!empty($updates['durationApprox']) ? 1 : 0);
        }
        if (in_array('durationWithBreak', $fieldsToUpd, true)) {
          $event->setDurationWithBreak(!empty($updates['durationWithBreak']) ? 1 : 0);
        }

        // 3) Log anlegen und speichern
        $log = $this->logGenerator
          ->createEventLog('E-E2', $event, $fieldsToUpd);
        $event->addLog($log);

        $this->eventRepository->update($event);
        $edited++;
      }

      // 4) Feedback & Redirect
      $this->addModuleFlashMessage(
        sprintf('Es wurden erfolgreich %d Termine geändert.', $edited),
        'TERMINE GEÄNDERT!'
      );
      return $this->redirect('list', 'Event');
    }

    // 5) Sonst: keine gültige Bulk-Operation → Fehlermeldung
    $this->addModuleFlashMessage(
      'Es wurden keine gültigen Daten zum Aktualisieren übergeben.',
      'AKTUALISIERUNG NICHT MÖGLICH!',
      'ERROR'
    );
    return $this->redirect('list', 'Event');
  }


  /**
   * action update
   *
   * @param Event $event
   * @return void
   */
  public function updateAction(Event $event): ResponseInterface
  {
    // Änderungen persistieren
    $this->eventRepository->update($event);

    // Feedback für den Redakteur
    $this->addModuleFlashMessage(
      'Die Änderungen wurden erfolgreich gespeichert.',
      'EVENT AKTUALISIERT!',
      'OK'
    );

    // Zurück zur Detailansicht
    return $this->redirect('show', 'Event', null, ['event' => $event]);
  }



  /**
   * action undoPostpone
   *
   * @param Event $event
   * @return void
   */
  public function undoPostponeAction(Event $event): ResponseInterface
  {
    // Einfach das Event an das Template übergeben
    $this->view->assign('event', $event);

    return $this->backendResponse();
  }

  /**
   * action deleteCollection
   *
   * @return void
   */
  public function deleteCollectionAction(): ResponseInterface
  {

    // UIDs aus dem Request auslesen (kann ein String oder Array sein)
    $uids = (array) $this->request->getArgument('events');
    $deleted = 0;
    $forbidden = 0;

    foreach ($uids as $uid) {
      $event = $this->eventRepository->findByUid((int) $uid);
      if (!$event) {
        continue;
      }

      if ($event->getAccreditations()->count() === 0) {
        // Log für löschbare Termine
        $log = $this->logGenerator
          ->createEventLog('E-D', $event);
        $event->addLog($log);
        $this->eventRepository->update($event);

        // Termin wirklich löschen
        $this->eventRepository->remove($event);
        $deleted++;
      } else {
        // Log für Termine mit Akkreditierungen
        $log = $this->logGenerator
          ->createEventLog('E-DI', $event);
        $event->addLog($log);
        $this->eventRepository->update($event);

        $forbidden++;
      }
    }

    // Feedback an den Redakteur
    if ($deleted > 0) {
      $this->addModuleFlashMessage(
        sprintf('Es wurden %d Termin(e) gelöscht.', $deleted),
        'TERMINE GELÖSCHT!',
        'OK'
      );
    }
    if ($forbidden > 0) {
      $this->addModuleFlashMessage(
        sprintf(
          'Bei %d Termin(en) sind Akkreditierungen vorhanden. Diese können daher nicht gelöscht werden!',
          $forbidden
        ),
        'LÖSCHEN NICHT MÖGLICH!',
        'ERROR'
      );
    }

    // Zurück zur Übersicht
    return $this->redirect('list', 'Event');
  }


  /**
   * action show
   *
   * @param Event       $event
   * @param string      $function
   * @param array|null  $filter
   * @return void
   */
  public function showAction(Event $event, string $function = '', ?array $filter = null): ResponseInterface
  {
    // 1) Basis-View-Assignments
    $this->view->assign('event', $event);
    $this->view->assign('function', $function);

    // 2) Haupt-Liste (gefiltert oder alle Gäste)
    if ($filter) {
      $accreditations = $this->accreditationRepository->findFiltered($filter);
      $this->view->assign('filter', $filter);
    } else {
      $accreditations = $this->accreditationRepository->findGuestsByEvent($event->getUid());
    }
    $this->view->assign('accreditations', $accreditations);

    // 3) Alternative Status-Listen
    $this->view->assignMultiple([
      'guests' => $this->accreditationRepository->findGuestsByEvent($event->getUid()),
      'pending' => $this->accreditationRepository->findPendingByEvent($event->getUid()),
      'waiting' => $this->accreditationRepository->findWaitingByEvent($event->getUid()),
      'rejected' => $this->accreditationRepository->findRejectedByEvent($event->getUid()),
      'errors' => $this->accreditationRepository->findErrorsByEvent($event->getUid()),
      'duplicates' => $this->accreditationRepository->findDuplicatesByEvent($event->getUid()),
    ]);

    // 4) Summaries für zusätzliche Felder
    $fields = $event->getAdditionalFieldsWithSum() ?? [];
    if (
      (is_array($fields) && count($fields) > 0)
      || ($fields instanceof \Countable && $fields->count() > 0)
    ) {
      $summaries = [];
      foreach ($fields as $field) {
        $answers = $this->additionalanswerRepository->findByField($field->getUid());
        switch ($field->getType()) {
          case 6:
            // Multiple-Optionen → jeweils einzeln aufsummieren
            foreach ($field->getOptions() as $opt) {
              $sum = 0;
              foreach ($answers as $answer) {
                $status = $answer->getAccreditation()->getStatus();
                if ($status >= 1) {
                  foreach ($answer->getValue() as $v) {
                    if ($v['key'] === $opt['value'] && is_numeric($v['value'])) {
                      $sum += $v['value'];
                    }
                  }
                }
              }
              $summaries[] = [
                'key' => $opt['value'],
                'label' => $opt['label'],
                'icon' => $opt['icon'],
                'sum' => $sum
              ];
            }
            break;

          case 1:
            // Numerisches Feld → alle Akkreditierungen mit Status >=1 aufsummieren
            $sum = 0;
            foreach ($answers as $answer) {
              $status = $answer->getAccreditation()->getStatus();
              if ($status >= 1 && is_numeric($answer->getValue())) {
                $sum += $answer->getValue();
              }
            }
            $summaries[] = [
              'key' => $field->getUid(),
              'label' => $field->getLabel(),
              'icon' => $field->getIcon(),
              'sum' => $sum
            ];
            break;

          default:
            // Alle anderen Felder: count der TicketsApproved für Status >=1
            $sum = 0;
            foreach ($answers as $answer) {
              if ($answer->getAccreditation()->getStatus() >= 1) {
                $sum += $answer->getAccreditation()->getTicketsApproved();
              }
            }
            $summaries[] = [
              'key' => $field->getUid(),
              'label' => $field->getLabel(),
              'icon' => $field->getIcon(),
              'sum' => $sum
            ];
            break;
        }
      }
      $this->view->assign('summaries', $summaries);
    }

    // --- HIER DIE BUTTONS ZUM DOCHEADER DEINES MODULS HINZUFÜGEN ---
    if ($this->moduleTemplate && $event instanceof Event && $event->getUid() > 0) {
      $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();
      $shortCutButton = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar()->makeShortcutButton();
      $shortCutButton
        ->setRouteIdentifier('allegria_eventcenter')
        ->setDisplayName('Event: ' . $event->getTitle())
        ->setArguments(['action' => 'show', 'controller' => 'Event', 'event' => $event->getUid()]);
      $buttonBar->addButton($shortCutButton, ButtonBar::BUTTON_POSITION_RIGHT, 1);
    }

    $this->setModuleTitle($event->getTitle() . ' – Terminübersicht');
    return $this->backendResponse();


  }


  /**
   * action printLabels
   *
   * @param Event         $event
   * @param array<string> $selection Optional, nicht in diesem Beispiel implementiert
   * @return void
   */
  public function printLabelsAction(Event $event, array $selection = null): ResponseInterface
  {
    // 1) Nur Backend-User dürfen hier rein
    if (!$this->getCurrentBackendUserUid()) {
      $this->addModuleFlashMessage(
        'Zugriff verweigert. Sie müssen im Backend eingeloggt sein, um diese Funktion zu nutzen.',
        'ZUGRIFF VERWEIGERT',
        'ERROR'
      );
      return $this->redirect('list');
    }

    // 2) Gästeliste für das Event holen
    $guests = $this->accreditationRepository->findGuestsByEvent($event->getUid());

    // 3) In die View packen
    $this->view->assignMultiple([
      'event' => $event,
      'guests' => $guests,
      'selection' => $selection
    ]);

    return $this->frontendResponse();
  }


  /**
   * action export
   *
   * @param Event        $event
   * @param array|null   $filter
   * @param string       $function
   * @return void
   */
  public function exportAction(Event $event, ?array $filter = null, string $function = 'guests'): ResponseInterface
  {
    // 1) Determine which records to export
    if (!empty($filter)) {
      $accreditationsToExport = $this->accreditationRepository->findFiltered($filter);
      $filenameTitle = 'gefiltert';
    } else {
      switch ($function) {
        case 'pending':
          $accreditationsToExport = $this->accreditationRepository->findPendingByEvent($event->getUid());
          $filenameTitle = 'eingeladene';
          break;
        case 'waiting':
          $accreditationsToExport = $this->accreditationRepository->findWaitingByEvent($event->getUid());
          $filenameTitle = 'wartende';
          break;
        case 'reject':
          $accreditationsToExport = $this->accreditationRepository->findRejectedByEvent($event->getUid());
          $filenameTitle = 'abgesagte';
          break;
        default:
          $accreditationsToExport = $this->accreditationRepository->findGuestsByEvent($event->getUid());
          $filenameTitle = 'gaeste';
          break;
      }
    }

    // 2) Build a safe filename
    $clientName = $event->getClient() ? preg_replace('/\s+/', '-', $event->getClient()->getName()) : '';
    $eventTitle = preg_replace('/\s+/', '-', $event->getTitle());
    $timestamp = date('Y-m-d_H-i-s');
    $filename = sprintf('%s_%s_%s_%s.csv', $clientName, $eventTitle, $filenameTitle, $timestamp);

    // 3) Collect any additional column headers
    $additionalColumns = [];
    foreach ($event->getAdditionalColumns() ?? [] as $column) {
      if ($column->getOptions() && (int) $column->getType() === 6) {
        foreach ($column->getOptions() as $option) {
          $additionalColumns[] = $option['label'];
        }
      } else {
        $additionalColumns[] = $column->getLabel();
      }
    }

    // 4) Prepare CSV output
    $f = fopen('php://memory', 'w');
    $baseHeaders = [
      'UID',
      'Gästetyp',
      'Facie',
      'Vorname',
      'Nachname',
      'Firma',
      'E-Mail',
      'Telefon',
      'Status',
      'Invitation Status',
      'Tix',
      'Programm',
      'Fotopass'
    ];
    $footerHeaders = ['Hinweis', 'Einladungstyp', 'Sitzplatzinfos'];
    $allHeaders = array_merge(
      $baseHeaders,
      $additionalColumns,
      $footerHeaders
    );
    fputcsv($f, $allHeaders, ',');

    // 5) Write each row
    foreach ($accreditationsToExport as $accreditation) {
      // contact data or GDPR mask
      $incl = (bool) $this->request->getArgument('inclContactData');
      $guestOut = $accreditation->getGuestOutput();
      $email = $incl ? ($guestOut['email'] ?? '') : 'DSGVO';
      $phone = $incl ? ($guestOut['phone'] ?? '') : 'DSGVO';
      $mobile = $incl ? ($guestOut['mobile'] ?? '') : 'DSGVO';

      $facie = $accreditation->isFacie() ? 'JA' : 'NEIN';

      // Additional answers per column
      $answersByField = [];
      foreach ($event->getAdditionalColumns() ?? [] as $column) {
        $found = '';
        foreach ($accreditation->getAdditionalAnswers() as $answer) {
          if ($answer->getField()->getUid() === $column->getUid()) {
            switch ((int) $column->getType()) {
              case 6:
                foreach ($answer->getValue() as $val) {
                  $answersByField[] = $val['value'];
                }
                break;
              case 0:
                $answersByField[] = $answer->getValue() ? 'Ja' : 'Nein';
                break;
              default:
                $answersByField[] = $answer->getValue() ?? '';
                break;
            }
            $found = true;
            break;
          }
        }
        if (!$found) {
          $answersByField[] = '';
        }
      }

      $row = [
        $accreditation->getUid(),
        $accreditation->getGuestTypeOutput(),
        $facie,
        trim(($guestOut['firstName'] ?? '') . ' ' . ($guestOut['middleName'] ?? '')),
        ($guestOut['lastName'] ?? ''),
        ($guestOut['company'] ?? ''),
        $email,
        $phone . "\n" . $mobile,
        $accreditation->getStatus(),
        $accreditation->getInvitationStatus(),
        $accreditation->getTicketsApproved(),
        $accreditation->getProgram(),
        $accreditation->getPass(),
      ];
      if (!empty($answersByField)) {
        $row = array_merge($row, $answersByField);
      }
      $row = array_merge(
        $row,
        [
          $accreditation->getNotes(),
          $accreditation->getInvitationType()?->getTitle() ?? '',
          $accreditation->getSeats(),
        ]
      );

      fputcsv($f, $row, ',');
    }

    // 6) Stream to browser
    fseek($f, 0);
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '";');
    fpassthru($f);
    exit();
  }

}
