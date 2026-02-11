<?php
declare(strict_types=1);

namespace BucheggerOnline\Publicrelations\Controller\Pressecenter;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Core\Database\ConnectionPool;

use BucheggerOnline\Publicrelations\Domain\Repository\AccessClientRepository;
use BucheggerOnline\Publicrelations\Domain\Repository\SysCategoryRepository;
use BucheggerOnline\Publicrelations\Domain\Repository\LogRepository;
use BucheggerOnline\Publicrelations\Domain\Repository\ClientRepository;
use BucheggerOnline\Publicrelations\Domain\Repository\EventRepository;
use BucheggerOnline\Publicrelations\Domain\Repository\AccreditationRepository;
use BucheggerOnline\Publicrelations\Domain\Repository\InvitationRepository;
use Allegria\AcDistribution\Domain\Repository\JobRepository;

use BucheggerOnline\Publicrelations\Domain\Model\Client;
use BucheggerOnline\Publicrelations\Domain\Model\Event;
use BucheggerOnline\Publicrelations\Domain\Model\Log;
use BucheggerOnline\Publicrelations\Domain\Model\Accreditation;
use Allegria\AcContacts\Domain\Model\Contact;

// Neue Services
use BucheggerOnline\Publicrelations\Service\AccreditationService;
use BucheggerOnline\Publicrelations\DataResolver\AccreditationDataResolver;
use Allegria\AcDistribution\Service\DistributionService;
use Allegria\AcContacts\Domain\Repository\ContactRepository;
use Allegria\AcContacts\Domain\Repository\MailingListRepository;
use Allegria\AcContacts\Service\ContactValidationService;
use Allegria\AcContacts\Service\ContactDuplicateService;

/**
 * AjaxController
 */
class AjaxController extends ActionController
{
    private AccessClientRepository $accessClientRepository;
    private ContactRepository $contactRepository;
    private MailingListRepository $mailingListRepository;
    private LogRepository $logRepository;
    private ClientRepository $clientRepository;
    private EventRepository $eventRepository;
    private AccreditationRepository $accreditationRepository;
    private InvitationRepository $invitationRepository;
    private JobRepository $jobRepository;
    private AccreditationService $accreditationService;
    private DistributionService $distributionService;
    private PersistenceManager $persistenceManager;
    private ContactValidationService $contactValidationService;
    private ContactDuplicateService $contactDuplicateService;

    public function __construct(
        AccessClientRepository $accessClientRepository,
        ContactRepository $contactRepository,
        MailingListRepository $mailingListRepository,
        LogRepository $logRepository,
        ClientRepository $clientRepository,
        EventRepository $eventRepository,
        AccreditationRepository $accreditationRepository,
        JobRepository $jobRepository,
        InvitationRepository $invitationRepository,
        AccreditationService $accreditationService,
        DistributionService $distributionService,
        PersistenceManager $persistenceManager,
        ContactValidationService $contactValidationService,
        ContactDuplicateService $contactDuplicateService
    ) {
        $this->accessClientRepository = $accessClientRepository;
        $this->contactRepository = $contactRepository;
        $this->mailingListRepository = $mailingListRepository;
        $this->logRepository = $logRepository;
        $this->clientRepository = $clientRepository;
        $this->eventRepository = $eventRepository;
        $this->accreditationRepository = $accreditationRepository;
        $this->jobRepository = $jobRepository;
        $this->invitationRepository = $invitationRepository;
        $this->accreditationService = $accreditationService;
        $this->distributionService = $distributionService;
        $this->persistenceManager = $persistenceManager;
        $this->contactValidationService = $contactValidationService;
        $this->contactDuplicateService = $contactDuplicateService;
    }

    /**
     * Liefert Kontakte und zugehörige Daten für das Pressecenter als JSON
     *
     * @param Client $client
     * @param string $search (optional) Suchbegriff
     * @param int $mailinglist (optional) UID der Mailingliste zum Filtern
     * @return ResponseInterface
     */
    public function listContactsAction(Client $client, string $search = '', int $mailinglist = 0): ResponseInterface
    {
        $clientUid = $client->getUid();

        // 2. Daten abrufen
        $contacts = $this->contactRepository->feFindByClient($clientUid, $search, $mailinglist);
        $mailinglists = $this->mailingListRepository->feFindByClient($clientUid);

        // Mailinglisten formatieren
        $mailinglistData = [];
        foreach ($mailinglists as $list) {
            $mailinglistData[] = [
                'uid' => $list->getUid(),
                'name' => $list->getTitle()
            ];
        }

        $mailinglistMap = [];
        foreach ($mailinglists as $list) {
            // Annahme: $list ist ein Objekt mit getUid() und getTitle()
            $mailinglistMap[$list->getUid()] = $list->getTitle();
        }

        $processedContacts = [];
        foreach ($contacts as $contact) {
            $categoriesOfContact = [];
            if (!empty($contact['mailing_list_uids'])) {
                // Den String in einzelne UIDs zerlegen
                $uids = GeneralUtility::intExplode(',', $contact['mailing_list_uids']);

                // Jede UID in unserer "Landkarte" nachschlagen
                foreach ($uids as $uid) {
                    if (isset($mailinglistMap[$uid])) {
                        $categoriesOfContact[] = [
                            'uid' => $uid,
                            'title' => $mailinglistMap[$uid]
                        ];
                    }
                }
            }

            // Das 'mailing_list_uids'-Feld durch das neue 'categories'-Array ersetzen
            $contact['categories'] = $categoriesOfContact;
            unset($contact['mailing_list_uids']);

            $processedContacts[] = $contact;
        }

        // 3. JSON-Antwort erstellen
        $response = [
            'totalContacts' => $this->contactRepository->countByClient($clientUid),
            'filteredContacts' => count($contacts),
            'contacts' => $processedContacts,
            'mailinglists' => $mailinglistData,
            'mailinglistsCount' => count($mailinglistData)
        ];

        return new JsonResponse($response);
    }

    /**
     * Holt die Daten eines Kontakts zum Bearbeiten, nach deinem Plan.
     */
    public function editContactAction(int $contact, int $client): ResponseInterface
    {
        // 1. Berechtigungen prüfen (wir prüfen auf 'edit_contacts', da wir bearbeiten wollen)
        if (!$this->hasClientAccess($client, 'edit_contacts')) {
            return new JsonResponse(['error' => 'Access denied.'], 403);
        }

        // 2. Kontakt-Objekt laden
        /** @var \Allegria\AcContacts\Domain\Model\Contact|null $contactObject */
        $contactObject = $this->contactRepository->findByUid($contact);

        // 3. Prüfen, ob der Kontakt existiert UND zum richtigen Client gehört
        if ($contactObject && $contactObject->getClient() && $contactObject->getClient()->getUid() === $client) {

            // 4. Wenn ja, Daten für das Formular zusammenstellen und zurückgeben
            $contactData = [
                'uid' => $contactObject->getUid(),
                'gender' => $contactObject->getGender(),
                'title' => $contactObject->getTitle(),
                'title_suffix' => $contactObject->getTitleSuffix(),
                'first_name' => $contactObject->getFirstName(),
                'middle_name' => $contactObject->getMiddleName(),
                'last_name' => $contactObject->getLastName(),
                'company' => $contactObject->getCompany(),
                'position' => $contactObject->getPosition(),
                'email' => $contactObject->getEmail(),
                'phone' => $contactObject->getPhone(),
                'mobile' => $contactObject->getMobile(),
            ];

            return new JsonResponse($contactData);
        }

        // Wenn eine der Prüfungen fehlschlägt, geben wir einen Fehler zurück
        return new JsonResponse(['error' => 'Contact not found or does not belong to the specified client.'], 404);
    }

    public function updateContactAction(int $client): ResponseInterface
    {
        $jsonPayload = json_decode($this->request->getBody()->getContents(), true);
        $contactData = $jsonPayload['contactData'] ?? [];

        // 1. Erneute Sicherheitsprüfung
        if (!$this->hasClientAccess($client, 'edit_contacts')) {
            return new JsonResponse(['error' => 'Access denied'], 403);
        }

        // Prüfen, ob es sich um einen reinen Toggle-Aufruf handelt
        $isToggleOnly = (isset($contactData['no_mailing']) && count($contactData) <= 2); // uid + no_mailing

        // 2. Daten validieren (nur wenn es KEIN reiner Toggle ist)
        if (!$isToggleOnly) {
            $errors = $this->validateContactData($contactData);
            if (!empty($errors)) {
                return new JsonResponse(['success' => false, 'errors' => $errors], 422);
            }
        }

        // 3. Model-basiertes Update durchführen
        $uid = (int) ($contactData['uid'] ?? 0);

        // 3a. Bestehendes Kontakt-Objekt laden
        $contact = $this->contactRepository->findByUid($uid);
        if ($contact === null) {
            return new JsonResponse(['success' => false, 'error' => 'Kontakt nicht gefunden oder kein Zugriff gewährt.'], 404);
        }
        if ($contact->getClient()->getUid() != $client) {
            return new JsonResponse(['success' => false, 'error' => 'Auf diesen Kontakt kann kein Zugriff gewährt werden.'], 404);
        }

        // 3b. Änderungsprotokoll erstellen (Vergleich von NEUEN Daten mit ALTEM Objekt)
        $notes = $this->generateChangeLogFromArray($contactData, $contact);

        // 3c. Formulardaten automatisiert auf das Model mappen
        $this->mapArrayToModel($contactData, $contact);

        // 3d. Nur ein Log erstellen, wenn es auch Änderungen gab
        if (!empty($notes)) {
            $context = GeneralUtility::makeInstance(Context::class);
            $feUsername = $context->getPropertyFromAspect('frontend.user', 'username');

            // GEÄNDERT: Prefix zu den Notes hinzufügen
            $finalNotes = "Geändert durch " . $feUsername . ":\n" . $notes;

            $log = new Log();
            $log->setCrdate(new \DateTime());
            $log->setTstamp(new \DateTime());
            $log->setCode('FE_edit');
            $log->setFunction('edit');
            $log->setSubject('Kontaktänderung durch ' . $feUsername);
            $log->setNotes($finalNotes);
            $log->setAddress($contact);

            $this->logRepository->add($log);
        }

        // 4. Geändertes Objekt (ggf. inkl. neuem Log) persistieren
        $this->contactRepository->update($contact);

        return new JsonResponse(['success' => true]);
    }

    // Fügen Sie diese neue Methode in Ihren AjaxController.php ein

    public function createContactAction(int $client): ResponseInterface
    {
        $jsonPayload = json_decode($this->request->getBody()->getContents(), true);
        $contactData = $jsonPayload['contactData'] ?? [];
        if (empty($contactData)) {
            return new JsonResponse(['success' => false, 'error' => 'No data received.'], 400);
        }

        if (!$this->hasClientAccess($client, 'edit_contacts')) {
            return new JsonResponse(['success' => false, 'error' => 'Access denied'], 403);
        }

        // 1. Daten validieren
        $errors = $this->validateContactData($contactData);
        if (!empty($errors)) {
            return new JsonResponse(['success' => false, 'errors' => $errors], 422);
        }

        // 2. Client-Objekt holen
        $clientObject = $this->clientRepository->findByUid($client);
        if (!$clientObject) {
            return new JsonResponse(['success' => false, 'error' => 'Client not found.'], 404);
        }

        // 3. Model manuell erstellen und befüllen
        try {
            $newContact = new Contact();
            $newContact->setClient($clientObject);
            // Wir setzen die PID auf die des Clients, damit der Datensatz im richtigen Ordner landet
            $newContact->setPid(4);

            // Daten mappen
            $this->mapArrayToModel($contactData, $newContact);

            // Speichern
            $this->contactRepository->add($newContact);

            // Persistieren um UID zu erhalten
            $this->persistenceManager->persistAll();

        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'error' => 'Fehler beim Speichern: ' . $e->getMessage()], 500);
        }

        return new JsonResponse(['success' => true, 'newUid' => $newContact->getUid()]);
    }

    /**
     * Prüft Kontaktdaten auf Validität und Duplikate.
     */
    public function checkContactAction(int $client): ResponseInterface
    {
        $jsonPayload = json_decode($this->request->getBody()->getContents(), true);
        $contactData = $jsonPayload['contactData'] ?? [];

        // 1. Validieren (nutzt intern den ValidationService für Email)
        $validationErrors = $this->validateContactData($contactData);
        if (!empty($validationErrors)) {
            return new JsonResponse(['success' => false, 'errors' => $validationErrors, 'step' => 'validation'], 422);
        }

        // 2. Duplikate prüfen (Mit neuem Service)
        // Wir nehmen die UID 0 an, da wir einen neuen Kontakt prüfen
        $checkResult = $this->contactDuplicateService->checkAgainstDatabase($contactData, $client, 0);

        if ($checkResult['hasPotentialDuplicates']) {
            // Mapping der Ergebnisse auf die vom Frontend erwartete Struktur (definite/possible)
            $formattedDuplicates = [
                'definite' => [],
                'possible' => []
            ];

            foreach ($checkResult['matches'] as $match) {
                // Wir mappen die flache Liste auf Kategorien basierend auf der Probability
                $category = ($match['probability'] >= 85) ? 'definite' : 'possible';

                $existing = $match['existing_record'];

                $formattedDuplicates[$category][] = [
                    'uid' => (int) $existing['uid'],
                    // Full Name zusammenbauen für Anzeige
                    'name' => trim(($existing['first_name'] ?? '') . ' ' . ($existing['last_name'] ?? '')),
                    'email' => $existing['email'] ?? '',
                    'company' => $existing['company'] ?? '',
                    'probability' => $match['probability']
                ];
            }

            // Nur zurückgeben, wenn Listen nicht leer sind
            if (!empty($formattedDuplicates['definite']) || !empty($formattedDuplicates['possible'])) {
                return new JsonResponse(['success' => false, 'duplicates' => $formattedDuplicates, 'step' => 'duplicate'], 200);
            }
        }

        // Alles in Ordnung
        return new JsonResponse(['success' => true]);
    }

    /**
     * Private Validierungsfunktion
     * @return array Ein Array von Fehlern, leer bei Erfolg
     */
    private function validateContactData(array &$contactData): array
    {
        $errors = [];

        // Regel 1: Mindestens Vor- oder Nachname
        if (empty($contactData['first_name']) && empty($contactData['last_name'])) {
            $errors['last_name'] = 'Bitte geben Sie mindestens einen Vor- oder Nachnamen an.';
        }

        // Regel 2: E-Mail-Prüfung via Service
        if (empty($contactData['email'])) {
            $errors['email'] = 'Bitte geben Sie eine E-Mail-Adresse an.';
        } else {
            // Nutze den neuen ValidationService
            $emailResult = $this->contactValidationService->validateAndNormalizeEmail($contactData['email']);

            if (!$emailResult['isValid']) {
                $errors['email'] = $emailResult['error'];
            } else {
                // Die normalisierte Email zurückschreiben (lowercase, trim)
                $contactData['email'] = $emailResult['email'];
            }
        }

        return $errors;
    }


    // EVENTS

    /**
     * Liefert Events und Zähler für das Pressecenter
     */
    public function listEventsAction(Client $client, string $filterMode = 'upcoming'): ResponseInterface
    {
        $clientUid = $client->getUid();
        $accessMap = $this->getAccessMap();
        $permissions = $accessMap[$clientUid] ?? null;

        if ($permissions === null) {
            return new JsonResponse(['events' => [], 'counts' => []]); // Kein Zugriff, leere Antwort
        }

        $allowedEventUids = null; // Standard: keine Einschränkung

        // Fall 1: Keine globale Berechtigung, aber spezifische Event-Rechte
        if ($permissions['view_events'] === false && !empty($permissions['events'])) {
            $allowedEventUids = array_keys($permissions['events']);
        }
        // Fall 2: Keine globale und keine spezifische Berechtigung
        elseif ($permissions['view_events'] === false && empty($permissions['events'])) {
            return new JsonResponse(['events' => [], 'counts' => []]); // Definitiv kein Zugriff
        }
        // Fall 3: Globale Berechtigung (allowedEventUids bleibt null, es werden alle Events des Clients geladen)
        // Die Repository-Methode mit den passenden Parametern aufrufen
        $events = $this->eventRepository->feFindByClient($clientUid, $filterMode, $allowedEventUids);
        $counts = $this->eventRepository->feCountByClient($clientUid, $allowedEventUids);

        $processedEvents = [];
        foreach ($events as $event) {
            // Wir erstellen eine bearbeitbare Kopie des Event-Arrays
            $eventData = $event;

            // Link zur "myAccreditations" Action generieren
            $eventData['link_event_view'] = $this->uriBuilder
                ->reset()
                ->setTargetPageUid((int) $this->settings['pages']['mydata'])
                ->setArguments([
                    'tx_publicrelations_pressecentermydata' => [ // Name des Plugins
                        'controller' => 'Pressecenter',
                        'action' => 'myAccreditations', // Deine Ziel-Action
                        'event' => $event['uid'], // Das Event als Argument übergeben
                        'client' => $clientUid,
                    ]
                ])
                ->build();

            $processedEvents[] = $eventData;
        }

        return new JsonResponse([
            'events' => $processedEvents,
            'counts' => $counts
        ]);
    }


    // ACCREDITATIONS

    public function listAccreditationsAction(Event $event, string $search = '', string $statusFilter = 'accredited'): ResponseInterface
    {
        $eventUid = $event->getUid();
        $clientUid = $event->getClient()->getUid();

        // 1. Berechtigungen holen und spezifisches Level für dieses Event ermitteln
        $accessMap = $this->getAccessMap();
        $eventAccess = $accessMap[$clientUid]['events'][$eventUid] ?? null;
        $accessLevel = $eventAccess['level'] ?? null;

        // 2. Daten mit dem neuen Status-Filter abrufen
        $accreditationsFromDb = $this->accreditationRepository->feFindByEvent($event->getUid(), $search, $statusFilter);

        $processedAccreditations = [];
        foreach ($accreditationsFromDb as $acc) {

            $hasAccessToContact = false;
            if (!empty($acc['guest_client'])) {
                $guestClientId = (int) $acc['guest_client'];

                // Schneller Check im Array, anstatt einer neuen DB-Abfrage
                $hasAccessToContact = isset($accessMap[$guestClientId]['view_contacts']) && $accessMap[$guestClientId]['view_contacts'] === true;
            }

            if ($hasAccessToContact) {
                $popoverParts = [];

                if (!empty($acc['guest_email'])) {
                    $popoverParts[] = sprintf('<i class="fas fa-at fa-fw me-2"></i>%s', $acc['guest_email']);
                }
                if (!empty($acc['guest_mobile'])) {
                    $popoverParts[] = sprintf('<i class="fas fa-mobile-alt fa-fw me-2"></i>%s', $acc['guest_mobile']);
                }
                if (!empty($acc['guest_phone'])) {
                    $popoverParts[] = sprintf('<i class="fas fa-phone fa-fw me-2"></i>%s', $acc['guest_phone']);
                }

                // Join the existing parts with a <br> tag
                $popoverHtml = implode(' <br>', $popoverParts);
                $acc['popoverContent'] = htmlspecialchars($popoverHtml, ENT_QUOTES, 'UTF-8');

                $acc['infoButtonDisabled'] = false;
                $acc['infoButtonTitle'] = 'Kontaktinformationen';
            } else {
                // Logik für den DSGVO-Hinweis
                $acc['popoverContent'] = 'DSGVO - Kein persönlicher Kontakt';
                $acc['infoButtonDisabled'] = true;
                $acc['infoButtonTitle'] = 'Keine Berechtigung';
            }

            // Sensible Daten entfernen
            unset($acc['guest_email'], $acc['guest_phone'], $acc['guest_mobile']);


            // Namensformatierungen
            $fullName = '';
            $company = '';
            $position = '';
            $sortingName = '';

            // Prüfen, ob ein verknüpfter Gast (ac_contact) existiert
            if (!empty($acc['guest_uid'])) {
                // Fall 1: Daten vom ac_contact-Datensatz verwenden
                $nameParts = array_filter([$acc['guest_title'], $acc['guest_first_name'], $acc['guest_middle_name'], $acc['guest_last_name']]);
                $fullName = implode(' ', $nameParts);
                if (!empty($acc['guest_title_suffix'])) {
                    $fullName .= ', ' . $acc['guest_title_suffix'];
                }
                $company = $acc['guest_company'];
                $position = $acc['guest_position'];
                $sortingName = implode(' ', array_filter([$acc['guest_last_name'], $acc['guest_first_name'], $acc['guest_middle_name'], $acc['guest_company']]));
            } else {
                // Fall 2: Fallback auf Akkreditierungsdaten
                $nameParts = array_filter([$acc['title'], $acc['first_name'], $acc['middle_name'], $acc['last_name']]);
                $fullName = implode(' ', $nameParts);
                $company = $acc['company']; // acc.medium
                $sortingName = implode(' ', array_filter([$acc['last_name'], $acc['first_name'], $acc['middle_name'], $acc['company']]));
            }

            // KORREKTUR: Daten als sauberes Objekt senden
            $acc['guestOutput'] = [
                'fullName' => $fullName,
                'company' => $company,
                'position' => $position,
                'sortingName' => $sortingName
            ];

            $acc['guestTypeOutput'] = $this->mapGuestType($acc['guest_type']);

            // HINZUGEFÜGT: Das Links-Objekt wird direkt zum $acc-Array hinzugefügt
            $acc['links'] = [
                'view_invitation' => $this->uriBuilder
                    ->reset()
                    ->setTargetPageUid((int) $this->settings['pages']['mailview'])
                    ->setArguments([
                        'tx_publicrelations_mailview' => [
                            'type' => 'accreditation',
                            'accreditation' => $acc['uid'],
                            'content' => 'invite'
                        ]
                    ])
                    ->build(),

                'view_confirmation' => $this->uriBuilder
                    ->reset()
                    ->setTargetPageUid((int) $this->settings['pages']['mailview'])
                    ->setArguments([
                        'tx_publicrelations_mailview' => [
                            'type' => 'accreditation',
                            'accreditation' => $acc['uid'],
                            'content' => 'approve'
                        ]
                    ])
                    ->build()
            ];

            $processedAccreditations[] = $acc;
        }

        $stats = $this->accreditationRepository->feGetStatsForEvent($eventUid);

        // JSON-Antwort um die Statistiken erweitern
        return new JsonResponse([
            'accreditations' => $processedAccreditations,
            'stats' => $stats,
            'accessLevel' => $accessLevel,
            'statusFilter' => $statusFilter,
            'accessMap' => $accessMap
        ]);
    }

    /**
     * Holt die Daten einer einzelnen Akkreditierung zum Bearbeiten.
     */
    public function editAccreditationAction(int $accreditation): ResponseInterface
    {
        $accreditationObject = $this->accreditationRepository->findByUid($accreditation);

        // Hier Sicherheitscheck einfügen, ob der User Zugriff auf das Event hat

        if ($accreditationObject) {
            $data = [
                'uid' => $accreditationObject->getUid(),
                'status' => $accreditationObject->getStatus(),
                'tickets_approved' => $accreditationObject->getTicketsApproved(),
                'tickets_wish' => $accreditationObject->getTicketsWish(),
                'notes' => $accreditationObject->getNotes(),
                'seats' => $accreditationObject->getSeats(),
            ];
            return new JsonResponse($data);
        }
        return new JsonResponse(['error' => 'Accreditation not found'], 404);
    }

    /**
     * Speichert Änderungen an einer Akkreditierung.
     */
    public function updateAccreditationAction(): ResponseInterface
    {
        $jsonPayload = json_decode($this->request->getBody()->getContents(), true);
        $accData = $jsonPayload['accData'] ?? [];
        $uid = (int) ($accData['uid'] ?? 0);

        if ($uid === 0) {
            return new JsonResponse(['error' => 'Invalid UID'], 400);
        }

        $accreditation = $this->accreditationRepository->findByUid($uid);
        if (!$accreditation) {
            return new JsonResponse(['error' => 'Accreditation not found'], 404);
        }

        // Sicherheitscheck für Berechtigungen
        $accreditation = $this->accreditationRepository->findByUid($uid);
        $clientUid = $accreditation->getEvent()->getClient()->getUid();
        $eventUid = $accreditation->getEvent()->getUid();
        $accessMap = $this->getAccessMap();
        $eventAccess = $accessMap[$clientUid]['events'][$eventUid] ?? null;
        $accessLevel = $eventAccess['level'] ?? null;
        if ($accessLevel !== 'edit' && $accessLevel !== 'manage') {
            return new JsonResponse(['error' => 'Access denied'], 403);
        }

        // 1. Log für Änderungen erstellen (VOR dem Mappen!)
        $notes = $this->generateChangeLogFromArray($accData, $accreditation);
        if (!empty($notes)) {
            $context = GeneralUtility::makeInstance(Context::class);
            $feUsername = $context->getPropertyFromAspect('frontend.user', 'username');

            // GEÄNDERT: Prefix zu den Notes hinzufügen
            $finalNotes = "Geändert durch " . $feUsername . ":\n" . $notes;

            $log = new Log();

            $log->setCrdate(new \DateTime());
            $log->setTstamp(new \DateTime());
            $log->setCode('FE_edit');
            $log->setFunction('edit');
            $log->setSubject('Änderung durch ' . $feUsername);
            $log->setNotes($finalNotes);
            $log->setAccreditation($accreditation);

            $this->logRepository->add($log);
        }

        // 2. Daten mappen
        $this->mapArrayToModel($accData, $accreditation);

        // 3. Geändertes Objekt inkl. neuem Log speichern
        $this->accreditationRepository->update($accreditation);

        return new JsonResponse(['success' => true]);
    }

    /**
     * Prüft Daten für eine neue Akkreditierung (Kontakt Validierung/Duplikate + bestehende Akkreditierung).
     */
    public function checkAccreditationAction(Event $event): ResponseInterface
    {
        $jsonPayload = json_decode($this->request->getBody()->getContents(), true);
        $contactData = $jsonPayload['contactData'] ?? [];
        $accData = $jsonPayload['accData'] ?? [];
        $clientUid = $event->getClient()->getUid();

        $validationErrors = $this->validateContactData($contactData);
        if (!empty($validationErrors)) {
            return new JsonResponse(['success' => false, 'errors' => $validationErrors, 'step' => 'contact_validation'], 422);
        }

        $forceCreate = (bool) ($jsonPayload['forceCreate'] ?? false);

        if (!$forceCreate) {
            // Duplikat Check via Service
            $checkResult = $this->contactDuplicateService->checkAgainstDatabase($contactData, $clientUid, 0);

            if ($checkResult['hasPotentialDuplicates']) {
                $formattedDuplicates = ['definite' => [], 'possible' => []];
                foreach ($checkResult['matches'] as $match) {
                    $cat = $match['probability'] >= 85 ? 'definite' : 'possible';
                    $existing = $match['existing_record'];
                    $formattedDuplicates[$cat][] = [
                        'uid' => $existing['uid'],
                        'name' => trim(($existing['first_name'] ?? '') . ' ' . ($existing['last_name'] ?? '')),
                        'email' => $existing['email'],
                        'company' => $existing['company']
                    ];
                }
                return new JsonResponse(['success' => false, 'duplicates' => $formattedDuplicates, 'step' => 'contact_duplicate'], 200);
            }

            // Check if email already accredited
            $existingContact = $this->contactRepository->findOneByEmail($contactData['email'], $clientUid);
            if ($existingContact) {
                $existingAccreditationUid = $this->accreditationRepository->findExistingAccreditation($existingContact->getUid(), $event->getUid());
                if ($existingAccreditationUid !== null) {
                    return new JsonResponse([
                        'success' => false,
                        'error' => 'Dieser Kontakt ist bereits für dieses Event akkreditiert.',
                        'step' => 'already_accredited',
                        'existingAccreditationUid' => $existingAccreditationUid
                    ], 409);
                }
            }
        }

        if (!isset($accData['tickets_wish']) || (int) $accData['tickets_wish'] <= 0) {
            $errors['tickets_wish'] = 'Bitte geben Sie die gewünschte Ticketanzahl an.';
            return new JsonResponse(['success' => false, 'errors' => $errors, 'step' => 'accreditation_validation'], 422);
        }

        return new JsonResponse(['success' => true]);
    }

    /**
     * Erstellt eine neue Akkreditierung (nach erfolgreichem Check).
     */
    public function createAccreditationAction(Event $event): ResponseInterface
    {
        $jsonPayload = json_decode($this->request->getBody()->getContents(), true);
        $contactData = $jsonPayload['contactData'] ?? [];
        $accData = $jsonPayload['accData'] ?? [];
        $existingContactUid = (int) ($jsonPayload['existingContactUid'] ?? 0);

        $clientUid = $event->getClient()->getUid();
        $eventUid = $event->getUid();
        $accessMap = $this->getAccessMap();
        $eventAccess = $accessMap[$clientUid]['events'][$eventUid] ?? null;
        $accessLevel = $eventAccess['level'] ?? null;
        if ($accessLevel !== 'manage') {
            return new JsonResponse(['error' => 'Keine Berechtigung zum Erstellen'], 403);
        }
        $invitationType = $eventAccess['invitationType'] ?? 0;
        $invitationTypeObject = $this->invitationRepository->findByUid($invitationType);

        $contact = null;

        if ($existingContactUid > 0) {
            $contact = $this->contactRepository->findByUid($existingContactUid);
            if (!$contact || $contact->getClient()->getUid() !== $clientUid) {
                return new JsonResponse(['success' => false, 'error' => 'Der ausgewählte Kontakt ist ungültig.'], 403);
            }
        } else {
            // Kontakt neu anlegen
            try {
                // Validate
                $errors = $this->validateContactData($contactData);
                if (!empty($errors))
                    return new JsonResponse(['success' => false, 'errors' => $errors], 422);

                $contact = new Contact();
                $contact->setClient($event->getClient());
                $contact->setPid(4);
                $this->mapArrayToModel($contactData, $contact);

                $this->contactRepository->add($contact);
                // Wichtig: Persistence wird unten global aufgerufen, aber für Accreditierung referenzieren wir das Object
                // Das sollte in Extbase funktionieren solange alles in einem Request passiert

            } catch (\Exception $e) {
                return new JsonResponse(['success' => false, 'error' => 'Der neue Kontakt konnte nicht erstellt werden: ' . $e->getMessage()], 500);
            }
        }

        if ($contact === null) {
            return new JsonResponse(['success' => false, 'error' => 'Kontakt konnte nicht ermittelt werden.'], 500);
        }

        $newAccreditation = new Accreditation();
        $newAccreditation->setEvent($event);
        $newAccreditation->setType((int) 2);
        $newAccreditation->setGuest($contact);
        $newAccreditation->setGuestType((int) $accData['guest_type']);

        $status = (int) ($accData['status'] ?? 0);
        $newAccreditation->setInvitationType($invitationTypeObject);
        $newAccreditation->setStatus($status);
        $newAccreditation->setInvitationStatus($accData['invitation_status'] ?? 0);
        $newAccreditation->setTicketsWish((int) $accData['tickets_wish']);
        $newAccreditation->setTicketsApproved((int) $accData['tickets_approved']);

        $newAccreditation->setNotes($accData['notes'] ?? '');
        $newAccreditation->setSeats($accData['seats'] ?? '');

        $logSubject = 'Akkreditierung erstellt';
        if ($status === 1)
            $logSubject = 'Akkreditierung erstellt (Zugesagt)';
        if ($status === -1)
            $logSubject = 'Akkreditierung erstellt (Abgesagt)';

        $log = $this->createAccreditationLog('FE_create', $newAccreditation, $logSubject);
        $newAccreditation->addLog($log);

        $this->accreditationRepository->add($newAccreditation);
        $this->persistenceManager->persistAll();

        return new JsonResponse(['success' => true, 'newUid' => $newAccreditation->getUid()]);
    }

    /**
     * Hilfsfunktion zum Loggen (Logik aus updateAccreditationAction).
     */
    private function createAccreditationLog(string $code, Accreditation $acc, string $subject): Log
    {
        $context = GeneralUtility::makeInstance(Context::class);
        $feUsername = $context->getPropertyFromAspect('frontend.user', 'username');

        $log = new Log();
        $log->setCrdate(new \DateTime());
        $log->setTstamp(new \DateTime());
        $log->setCode($code);
        $log->setFunction('create');
        $log->setSubject($subject . ' durch ' . $feUsername);
        $log->setAccreditation($acc);

        // Detaillierte Notiz
        $notes = sprintf(
            "Neue Akkreditierung für '%s' (Tickets: %d) erstellt.",
            $acc->getGuest() ? $acc->getGuest()->getDisplayName() : 'N/A',
            $acc->getTicketsWish()
        );
        $log->setNotes($notes);

        return $log;
    }

    /**
     * Sendet eine spezifische E-Mail für eine Akkreditierung.
     */
    public function sendAccreditationMailAction(int $accreditation, string $mailCode): ResponseInterface
    {
        /** @var Accreditation|null $accObject */
        $accObject = $this->accreditationRepository->findByUid($accreditation);
        if (!$accObject) {
            return new JsonResponse(['error' => 'Akkreditierung nicht gefunden'], 404);
        }

        // 1. Sicherheitsprüfung
        $clientUid = $accObject->getEvent()->getClient()->getUid();
        $eventUid = $accObject->getEvent()->getUid();
        $accessMap = $this->getAccessMap();
        $eventAccess = $accessMap[$clientUid]['events'][$eventUid] ?? null;
        $accessLevel = $eventAccess['level'] ?? null;

        if ($accessLevel !== 'edit' && $accessLevel !== 'manage') {
            return new JsonResponse(['error' => 'Keine Berechtigung für diesen Vorgang'], 403);
        }

        // 2. Validierung der Aktion (Ist der Status-Übergang logisch erlaubt?)
        // Wir nutzen den AccreditationService, um die Logik zentral zu halten.
        if (!$this->accreditationService->isValidStatusTransition($accObject, $mailCode)) {
            return new JsonResponse(['error' => 'Aktion für den aktuellen Status nicht erlaubt.'], 409);
        }

        // 3. Prüfung auf bereits laufende Jobs
        if ($accObject->getDistributionJob() > 0) {
            return new JsonResponse(['error' => 'Ein Versand für diesen Gast ist bereits in Vorbereitung.'], 409);
        }

        $feUsername = GeneralUtility::makeInstance(Context::class)->getPropertyFromAspect('frontend.user', 'username', 'Unbekannt');

        try {
            // 4. Kontext für den DistributionService bauen
            $context = [
                'dataSource' => [
                    'function' => $mailCode, // invite, remind, approve, etc.
                    'dataResolverClass' => AccreditationDataResolver::class,
                    'uids' => [$accObject->getUid()]
                ],
                'context' => 'Frontend-Versand durch ' . $feUsername . ': ' . $mailCode . ' zu Event – ' . $accObject->getEvent()->getTitle(),
                'sender_profile' => 1, // Standardprofil
                'report' => [
                    'no_report' => true // Im Frontend brauchen wir keinen Einzel-Report pro Klick
                ]
            ];

            // 5. Versand anstoßen
            // Da wir im Frontend (isLoggedIn FE) sind, gibt der Service automatisch 'queued' zurück
            $dispatchResult = $this->distributionService->send($context);

            if ($dispatchResult['status'] === 'queued') {
                $jobUid = (int) $dispatchResult['job_uid'];
                $distributionJob = $this->jobRepository->findByUid($jobUid);

                // 6. Lokale Akkreditierung sperren (Job-ID hinterlegen)
                // Wir ändern NICHT den Einladungs-Status, das macht der Worker nach Erfolg!
                $accObject->setDistributionJob($distributionJob);

                // 7. Log für den angestoßenen Prozess erstellen
                $logData = $this->accreditationService->prepareLogData($accObject, 'A-job-created', [
                    'jobUid' => $jobUid
                ]);

                $log = new Log();
                $log->setCrdate(new \DateTime());
                $log->setTstamp(new \DateTime());
                $log->setCode($logData['code']);
                $log->setFunction($logData['function']);
                $log->setSubject($logData['details']);
                $log->setNotes('Versand von "' . $mailCode . '" durch ' . $feUsername . ' eingeplant.');
                $log->setAccreditation($accObject);
                $this->logRepository->add($log);

                // Speichern
                $this->accreditationRepository->update($accObject);
                $this->persistenceManager->persistAll();

                return new JsonResponse([
                    'success' => true,
                    'message' => 'Der Versand der E-Mail wurde eingeplant – die Zustellung erfolgt in den nächsten Minuten.'
                ]);
            }

            return new JsonResponse(['error' => 'Job konnte nicht erstellt werden: ' . ($dispatchResult['message'] ?? 'Unbekannter Fehler')], 500);

        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Systemfehler beim Versand: ' . $e->getMessage()], 500);
        }
    }

    // Private Hilfsfunktion, um guest_type zu mappen
    private function mapGuestType(int $type): string
    {
        return match ($type) {
            1 => 'VIP',
            2 => 'Press',
            6 => 'Talent',
            3 => 'Gewinner',
            4 => '',
            5 => 'Staff',
            default => 'Unbekannt',
        };
    }


    // SONSTIGES

    /**
     * Automatisierte, wiederverwendbare Mapping-Funktion.
     * Geht ein Array durch und ruft die passenden Setter auf einem Model-Objekt auf.
     * Konvertiert dabei snake_case (aus Formular) in setCamelCase (Methodenname).
     *
     * @param array $data Das Quell-Array (z.B. ['first_name' => 'Max'])
     * @param object $model Das Ziel-Objekt
     */
    private function mapArrayToModel(array $data, object $model): void
    {
        foreach ($data as $key => $value) {
            // Konvertiert z.B. 'first_name' zu 'setFirstName'
            $setterName = 'set' . GeneralUtility::underscoredToUpperCamelCase($key);

            // Prüft, ob die Setter-Methode im Model existiert
            if (method_exists($model, $setterName)) {
                // Ruft den Setter mit dem Wert auf
                $model->$setterName($value);
            }
        }
    }

    /**
     * Vergleicht ein Daten-Array (snake_case) mit einem Model-Objekt
     * und generiert automatisch einen Log-Text im HTML-Format für alle geänderten Felder.
     *
     * @param array $newData Das Array mit den neuen Daten aus dem Formular.
     * @param object $originalModel Das ursprüngliche Model-Objekt aus der Datenbank.
     * @return string Der formatierte Log-Text als HTML.
     */
    private function generateChangeLogFromArray(array $newData, object $originalModel): string
    {
        $notes = [];

        // Definiere die HTML-Wrapper für die Werte
        $oldValueWrapper = '<span style="color: red; font-family: monospace;">%s</span>';
        $newValueWrapper = '<span style="color: green; font-family: monospace;">%s</span>';

        foreach ($newData as $key => $newValue) {
            $getterName = 'get' . GeneralUtility::underscoredToUpperCamelCase($key);

            if (method_exists($originalModel, $getterName)) {
                $oldValue = $originalModel->$getterName();

                if ($oldValue != $newValue && !(empty($oldValue) && empty($newValue))) {

                    $formattedOldValue = $this->formatLogValue($oldValue);
                    $formattedNewValue = $this->formatLogValue($newValue);

                    // sprintf-Formatierung für HTML-Ausgabe
                    $notes[] = sprintf(
                        "Feld '<strong>%s</strong>' geändert von '" . $oldValueWrapper . "' zu '" . $newValueWrapper . "'",
                        $key,
                        htmlspecialchars($formattedOldValue), // htmlspecialchars zur Sicherheit
                        htmlspecialchars($formattedNewValue)
                    );
                }
            }
        }

        // GEÄNDERT: Zeilenumbruch mit <br>
        return implode("<br>\n", $notes);
    }

    /**
     * Formatiert einen beliebigen Wert in einen lesbaren String für das Log.
     *
     * @param mixed $value
     * @return string
     */
    private function formatLogValue($value): string
    {
        if (is_bool($value)) {
            return $value ? 'Ja' : 'Nein';
        }
        if ($value === null || $value === '') {
            return 'LEER';
        }
        return (string) $value;
    }

    /**
     * Private Hilfsfunktion für die Sicherheitsprüfung
     */
    private function hasClientAccess(int $client, string $permission): bool
    {
        $context = GeneralUtility::makeInstance(Context::class);
        $userId = (int) $context->getPropertyFromAspect('frontend.user', 'id');
        if ($userId === 0) {
            return false;
        }
        $userGroupIds = $context->getPropertyFromAspect('frontend.user', 'groupIds');
        $accessPermissions = $this->accessClientRepository->findForUserAndGroups($userId, $userGroupIds);

        foreach ($accessPermissions as $p) {
            if ($p->getClient()->getUid() === $client) {
                // Je nach benötigter Berechtigung prüfen
                if ($permission === 'view_contacts' && $p->isViewContacts())
                    return true;
                if ($permission === 'edit_contacts' && $p->isEditContacts())
                    return true;
                // Hier könnten weitere Berechtigungen folgen...
            }
        }
        return false;
    }

    /**
     * Holt alle Client- und Event-Berechtigungen für den aktuellen FE-User
     * und gibt sie als einfach durchsuchbare "Map" zurück.
     *
     * @return array z.B. [ 123 => ['view_contacts' => true, 'events' => [ 789 => 'edit' ]], 456 => [...] ]
     */
    private function getAccessMap(): array
    {
        $context = GeneralUtility::makeInstance(Context::class);
        $userId = (int) $context->getPropertyFromAspect('frontend.user', 'id');
        if ($userId === 0) {
            return [];
        }

        $userGroupIds = $context->getPropertyFromAspect('frontend.user', 'groupIds');
        $accessPermissions = $this->accessClientRepository->findForUserAndGroups($userId, $userGroupIds);

        $accessMap = [];
        foreach ($accessPermissions as $permission) {
            $clientUid = $permission->getClient()->getUid();

            // SCHRITT 1: Initialisiere den Eintrag für den Kunden, falls er noch nicht existiert.
            if (!isset($accessMap[$clientUid])) {
                $accessMap[$clientUid] = [
                    'view_clippings' => false,
                    'view_media' => false,
                    'view_news' => false,
                    'view_events' => false,
                    'view_contacts' => false,
                    'edit_contacts' => false,
                    'delete_contacts' => false,
                    'events' => []
                ];
            }

            // SCHRITT 2: Führe die allgemeinen Berechtigungen mit einem logischen ODER zusammen.
            // Das Recht wird auf `true` gesetzt, wenn es im aktuellen ODER im bereits vorhandenen Eintrag `true` ist.
            $accessMap[$clientUid]['view_clippings'] = $accessMap[$clientUid]['view_clippings'] || $permission->isViewClippings();
            $accessMap[$clientUid]['view_media'] = $accessMap[$clientUid]['view_media'] || $permission->isViewMedia();
            $accessMap[$clientUid]['view_news'] = $accessMap[$clientUid]['view_news'] || $permission->isViewNews();
            $accessMap[$clientUid]['view_events'] = $accessMap[$clientUid]['view_events'] || $permission->isViewEvents();
            $accessMap[$clientUid]['view_contacts'] = $accessMap[$clientUid]['view_contacts'] || $permission->isViewContacts();
            $accessMap[$clientUid]['edit_contacts'] = $accessMap[$clientUid]['edit_contacts'] || $permission->isEditContacts();
            $accessMap[$clientUid]['delete_contacts'] = $accessMap[$clientUid]['delete_contacts'] || $permission->isDeleteContacts();


            // SCHRITT 3: Füge die spezifischen Event-Berechtigungen hinzu.
            if ($permission->getAdvancedEvents()) {
                foreach ($permission->getAdvancedEvents() as $advancedEvent) {
                    if ($advancedEvent->getEvent()) {
                        $eventUid = $advancedEvent->getEvent()->getUid();
                        $accessLevel = $advancedEvent->getAccesslevel();

                        // Hier ist ein Überschreiben okay, da man pro Event nur ein Access-Level haben kann.
                        // Im Zweifel gilt die spezifischere (meist die letzte gefundene) Berechtigung.
                        $accessMap[$clientUid]['events'][$eventUid] = [
                            'level' => $accessLevel,
                            'invitationType' => $advancedEvent->getInvitationType()
                        ];
                    }
                }
            }
        }

        return $accessMap;
    }
}