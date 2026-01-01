<?php

declare(strict_types=1);

namespace BucheggerOnline\Publicrelations\Backend\Ajax; // Dein Namespace

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface; // Wichtig für den modernen Ansatz
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\View\ViewFactoryInterface;
use TYPO3\CMS\Core\View\ViewFactoryData;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController; // Bleibt als Basis, aber wir nutzen $request
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use BucheggerOnline\Publicrelations\Utility\GeneralFunctions;
use BucheggerOnline\Publicrelations\Utility\LogGenerator;
use BucheggerOnline\Publicrelations\Domain\Model\{Event, Accreditation};
use BucheggerOnline\Publicrelations\Domain\Repository\{EventRepository, AccreditationRepository}; // Du brauchst das EventRepository

class CheckinAjaxController extends ActionController // Oder eine schlankere Basisklasse, wenn du keine View brauchst
{
    // protected EventRepository $eventRepository;
    // protected AccreditationRepository $accreditationRepository;

    // // Dependency Injection für das Repository
    // public function __construct(EventRepository $eventRepository, AccreditationRepository $accreditationRepository)
    // {
    //     $this->eventRepository = $eventRepository;
    //     $this->accreditationRepository = $accreditationRepository;
    // }


    public function __construct(
        private readonly EventRepository $eventRepository,
        private readonly AccreditationRepository $accreditationRepository,
        private readonly GeneralFunctions $generalFunctions, // Injizieren
        private readonly LogGenerator $logGenerator, // Injizieren
        private readonly PersistenceManager $persistenceManager
    ) {
    }

    /**
     * Refreshes the event status partial.
     * Erwartet 'eventUid' (oder einen passenden Extbase-Parameter-Namen wie 'tx_publicrelations_eventcenter[event]')
     * als GET-Parameter.
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function refreshEventStatusAction(ServerRequestInterface $request): ResponseInterface
    {
        $queryParams = $request->getQueryParams();
        $eventUid = 0;

        // Versuche, die Event-UID aus den Query-Parametern zu extrahieren
        // Anpassung an die im JavaScript gesendeten Parameter (z.B. 'tx_publicrelations_eventcenter[event]')
        if (isset($queryParams['tx_publicrelations_eventcenter']['event'])) {
            $eventUid = (int) $queryParams['tx_publicrelations_eventcenter']['event'];
        } elseif (isset($queryParams['eventUid'])) { // Fallback, falls du 'eventUid' direkt sendest
            $eventUid = (int) $queryParams['eventUid'];
        }
        // Füge hier weitere Prüfungen für den Parameternamen hinzu, falls nötig

        if ($eventUid <= 0) {
            return new HtmlResponse('Fehler: Event-UID fehlt oder ist ungültig.', 400);
        }

        /** @var Event|null $event */
        $event = $this->eventRepository->findByUid($eventUid);

        if (!$event) {
            return new HtmlResponse('Fehler: Event nicht gefunden.', 404);
        }

        // Logik um faciesCheckedInCount zu berechnen:
        $faciesCheckedin = $this->accreditationRepository->findCheckedinFaciesByEvent($event);


        /** @var ViewFactoryInterface $viewFactory */
        $viewFactory = GeneralUtility::makeInstance(ViewFactoryInterface::class);

        $viewFactoryData = new ViewFactoryData(
            ['EXT:publicrelations/Resources/Private/Templates/'],
            ['EXT:publicrelations/Resources/Private/Partials/'],
            ['EXT:publicrelations/Resources/Private/Layouts/'],
            'EXT:publicrelations/Resources/Private/Partials/Checkin/StatusDetails.html'
        );
        $view = $viewFactory->create($viewFactoryData);
        $view->assignMultiple([
            'event' => $event,
            'faciesCheckedin' => $faciesCheckedin,
        ]);

        $renderedContent = $view->render();

        return new JsonResponse([
            'html' => $renderedContent
        ]);
    }

    /**
     * Listet Akkreditierungen für ein Event für die MDB Datatable.
     * Erwartet Parameter:
     * - 'eventUid' (oder 'tx_publicrelations_eventcenter[event]')
     * - Optional: 'searchTerm' für die Suche
     * - Optional: 'sortBy' ('name' oder 'status')
     * - DataTable-spezifische Parameter werden hier noch nicht berücksichtigt (Paging etc.)
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function listAccreditationsAction(ServerRequestInterface $request): ResponseInterface
    {
        $queryParams = $request->getQueryParams();
        $eventUid = 0;
        // Wichtig: `trim` beim searchTerm, um unnötige Leerzeichen zu entfernen
        $searchTerm = isset($queryParams['searchTerm']) ? trim($queryParams['searchTerm']) : null;
        $sortBy = $queryParams['sortBy'] ?? 'name';

        if (isset($queryParams['tx_publicrelations_eventcenter']['event'])) {
            $eventUid = (int) $queryParams['tx_publicrelations_eventcenter']['event'];
        } elseif (isset($queryParams['eventUid'])) {
            $eventUid = (int) $queryParams['eventUid'];
        }

        if ($eventUid <= 0) {
            return new JsonResponse(['error' => 'Event-UID fehlt.', 'data' => [], 'recordsFiltered' => 0, 'recordsTotal' => 0], 400);
        }

        /** @var Event|null $event */
        $event = $this->eventRepository->findByUid($eventUid);
        if (!$event) {
            return new JsonResponse(['error' => 'Event nicht gefunden.', 'data' => [], 'recordsFiltered' => 0, 'recordsTotal' => 0], 404);
        }

        // Akkreditierungen über die neue Repository-Methode laden (bereits gefiltert)
        $accreditationsResult = $this->accreditationRepository->findByEventAndSearchTerm($event, $searchTerm ?: null);

        // Die QueryResultInterface muss in ein Array konvertiert werden für usort
        $accreditationsArray = [];
        if ($accreditationsResult !== null) {
            foreach ($accreditationsResult as $acc) {
                $accreditationsArray[] = $acc;
            }
        }

        $recordsFiltered = count($accreditationsArray); // Anzahl nach Filterung
        $recordsTotal = $this->accreditationRepository->countByEvent($event); // Gesamtzahl für das Event

        // Sortierlogik (bleibt im PHP, da die Kriterien komplex sein können)
        usort($accreditationsArray, function ($a, $b) use ($sortBy) {
            /**
             * @var \BucheggerOnline\Publicrelations\Domain\Model\Accreditation $a
             * @var \BucheggerOnline\Publicrelations\Domain\Model\Accreditation $b
             */
            if ($sortBy === 'status') {
                $statusA = $a->getStatus();
                $statusB = $b->getStatus();
                $ticketsPreparedA = $a->getTicketsPrepared();
                $ticketsPreparedB = $b->getTicketsPrepared();
                $orderA = ($statusA == 1) ? 1 : (($ticketsPreparedA > 0) ? 2 : 3);
                $orderB = ($statusB == 1) ? 1 : (($ticketsPreparedB > 0) ? 2 : 3);
                if ($orderA !== $orderB)
                    return $orderA <=> $orderB;
                return strcasecmp($a->getGuestOutput()['sortName'] ?? '', $b->getGuestOutput()['sortName'] ?? '');
            } else {
                // Stelle sicher, dass getGuestOutput()['sortName'] einen validen String liefert
                $sortNameA = $a->getGuestOutput()['sortName'] ?? (($a->getLastName() ?? '') . ($a->getFirstName() ?? ''));
                $sortNameB = $b->getGuestOutput()['sortName'] ?? (($b->getLastName() ?? '') . ($b->getFirstName() ?? ''));
                return strcasecmp($sortNameA, $sortNameB);
            }
        });

        $dataForTable = [];
        foreach ($accreditationsArray as $accreditation) {
            $guestOutput = $accreditation->getGuestOutput();
            $notesHtml = $this->renderNotesForAccreditation($accreditation);
            $checkinStatus = 'akkreditiert';
            if ($accreditation->getStatus() === 2) {
                $checkinStatus = ($accreditation->getTicketsPrepared() > 0) ? 'teilweise_eingecheckt' : 'voll_eingecheckt';
            }

            $dataForTable[] = [
                'uid' => $accreditation->getUid(),
                'checkin_status_key' => $checkinStatus,
                'tickets_prepared' => $accreditation->getTicketsPrepared(),
                'tickets_approved' => $accreditation->getTicketsApproved(),
                'guest_type' => $accreditation->getGuestTypeOutput(),
                'facie' => $accreditation->isFacie(),
                'name_primary' => $guestOutput['name'] ?? $accreditation->getFullName(),
                'name_secondary' => $guestOutput['company'] ?? $accreditation->getMedium(),
                'name_sort_value' => $guestOutput['sortName'] ?? '', // Wichtig für clientseitige Sortierung, falls noch genutzt
                'notes_html' => $notesHtml,
            ];
        }

        return new JsonResponse([
            'data' => $dataForTable,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered
            // 'draw' => isset($queryParams['draw']) ? (int)$queryParams['draw'] : 1 // Falls MDB das braucht
        ]);
    }

    /**
     * Hilfsmethode zum Rendern der Notizen (oder als ViewHelper auslagern)
     * @param \BucheggerOnline\Publicrelations\Domain\Model\Accreditation $accreditation
     * @return string
     */
    private function renderNotesForAccreditation(\BucheggerOnline\Publicrelations\Domain\Model\Accreditation $accreditation): string
    {
        // Hier Logik zum Sammeln und Formatieren der Notizen
        // - $accreditation->getNotes() (manuell)
        // - $accreditation->getNotesSelect() (ObjectStorage<SysCategory>)
        // - $accreditation->getNotesReceived() (Check-in Notiz - HIGHLIGHTEN!)

        $output = '';
        if ($accreditation->getNotes()) {
            $output .= '<p class="mb-1 note-manual">' . nl2br(htmlspecialchars($accreditation->getNotes())) . '</p>';
        }

        if ($accreditation->getNotesSelect() && $accreditation->getNotesSelect()->count() > 0) {
            $output .= '<div class="mb-1 note-select-tags">';
            foreach ($accreditation->getNotesSelect() as $noteCategory) {
                $output .= '<span class="badge badge-light me-1">' . htmlspecialchars($noteCategory->getTitle()) . '</span>';
            }
            $output .= '</div>';
        }

        if ($accreditation->getNotesReceived()) {
            // HIGHLIGHTED
            $output .= '<div class="alert alert-warning p-1 mt-1 mb-0 note-checkin"><strong>Check-In Info:</strong><br>' . nl2br(htmlspecialchars($accreditation->getNotesReceived())) . '</div>';
        }
        return $output ?: '-'; // Fallback, wenn keine Notizen
    }

    /**
     * Lädt Details für eine Akkreditierung und validiert sie.
     * Gibt HTML für das Modal oder eine Fehlermeldung als JSON zurück.
     *
     * Erwartet Parameter:
     * - 'accreditationUid'
     * - 'eventUid' (aus dem Kontext des aktuellen Check-in-Events)
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function showAccreditationDetailsAction(ServerRequestInterface $request): ResponseInterface
    {
        $queryParams = $request->getQueryParams();
        $accreditationUid = (int) ($queryParams['tx_publicrelations_eventcenter']['accreditation'] ?? $queryParams['accreditationUid'] ?? 0);
        $currentEventUid = (int) ($queryParams['tx_publicrelations_eventcenter']['eventContext'] ?? $queryParams['eventUid'] ?? 0);

        if ($accreditationUid <= 0) {
            return new JsonResponse(['error' => true, 'message' => 'Ungültige Akkreditierungs-ID.', 'type' => 'error']);
        }
        if ($currentEventUid <= 0) {
            return new JsonResponse(['error' => true, 'message' => 'Event-Kontext fehlt.', 'type' => 'error']);
        }

        /** @var Accreditation|null $accreditation */
        $accreditation = $this->accreditationRepository->findByUid($accreditationUid);

        if (!$accreditation) {
            return new JsonResponse(['error' => true, 'message' => 'Akkreditierung (ID: ' . $accreditationUid . ') nicht gefunden.', 'type' => 'error']);
        }

        if (!$accreditation->getEvent() || $accreditation->getEvent()->getUid() !== $currentEventUid) {
            return new JsonResponse(['error' => true, 'message' => 'Akkreditierung (ID: ' . $accreditationUid . ') gehört nicht zum aktuellen Event.', 'type' => 'error']);
        }

        // --- Sperrlogik ---
        $currentBeUserUid = $this->generalFunctions->getCurrentBackendUserUid();
        $now = time();
        $lockConflict = false;
        $lockedByUserName = '';

        if (
            $accreditation->getLockingBeUserUid() > 0 &&
            $accreditation->getLockingBeUserUid() !== $currentBeUserUid &&
            ($now - $accreditation->getLockingTstamp()) < 300 // 5 Minuten Sperre
        ) {
            $lockConflict = true;
            $lockedByUserName = $this->generalFunctions->getBackendUserDisplayNameByUid($accreditation->getLockingBeUserUid()) ?: 'einem anderen Benutzer';
        }

        if ($lockConflict) {
            return new JsonResponse([
                'warning' => true,
                'message' => 'Diese Akkreditierung wird gerade von ' . $lockedByUserName . ' bearbeitet (seit ' . date('H:i', (int) $accreditation->getLockingTstamp()) . ' Uhr). Möchtest du trotzdem fortfahren?',
                'type' => 'lock_conflict',
                'needsConfirmationToOpen' => true,
                'guestName' => $accreditation->getFullName(),
                'html' => $this->renderModalContent($accreditation) // HTML trotzdem senden
            ]);
        } else {
            // Keine Sperre oder Sperre abgelaufen/eigener User: Sperre setzen/aktualisieren
            $accreditation->setLockingBeUserUid($currentBeUserUid);
            $accreditation->setLockingTstamp($now);
            $this->accreditationRepository->update($accreditation);
            $this->persistenceManager->persistAll(); // Sofort in DB schreiben!
        }
        // --- Ende Sperrlogik ---


        if ($accreditation->getStatus() < 1) {
            return new JsonResponse([
                'warning' => true,
                'message' => 'Für Akkreditierung (ID: ' . $accreditationUid . ') wurde kein Platz bestätigt (Status ' . $accreditation->getStatusOutput() . '). Check-In nur mit Bedacht durchführen.',
                'type' => 'warning',
                'guestName' => $accreditation->getFullName(),
                'html' => $this->renderModalContent($accreditation)
            ]);
        }

        return new JsonResponse([
            'success' => true,
            'guestName' => $accreditation->getFullName(),
            'html' => $this->renderModalContent($accreditation)
        ]);
    }

    // /**
    //  * Hilfsmethode zum Rendern des Modal-Inhalts
    //  * @param Accreditation $accreditation
    //  * @return string
    //  */
    // private function renderModalContent(Accreditation $accreditation): string
    // {
    //     /** @var StandaloneView $view */
    //     $view = GeneralUtility::makeInstance(StandaloneView::class);
    //     $view->setFormat('html');
    //     $view->setTemplateRootPaths(['EXT:publicrelations/Resources/Private/Templates/']);
    //     $view->setPartialRootPaths(['EXT:publicrelations/Resources/Private/Partials/']);
    //     $view->setLayoutRootPaths(['EXT:publicrelations/Resources/Private/Layouts/']);
    //     $view->setTemplate('Checkin/ShowDetailsForModal');

    //     $view->assignMultiple([
    //         'accreditation' => $accreditation,
    //         'currentBeUser' => $this->generalFunctions->getCurrentBackendUserRecord()
    //     ]);

    //     return $view->render();
    // }

    /**
     * Hilfsmethode zum Rendern des Modal-Inhalts
     * @param Accreditation $accreditation
     * @return string
     */
    private function renderModalContent(Accreditation $accreditation): string
    {
        /** @var ViewFactoryInterface $viewFactory */
        $viewFactory = GeneralUtility::makeInstance(ViewFactoryInterface::class);

        $viewFactoryData = new ViewFactoryData(
            ['EXT:publicrelations/Resources/Private/Templates/'],
            ['EXT:publicrelations/Resources/Private/Partials/'],
            ['EXT:publicrelations/Resources/Private/Layouts/']
        );
        $view = $viewFactory->create($viewFactoryData);
        $view->assignMultiple([
            'accreditation' => $accreditation,
            'currentBeUser' => $this->generalFunctions->getCurrentBackendUserRecord()
        ]);

        return $view->render('Checkin/ShowDetailsForModal');
    }

    /**
     * Verarbeitet den eigentlichen Check-in Vorgang.
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function processCheckinAction(ServerRequestInterface $request): ResponseInterface
    {
        $parsedBody = $request->getParsedBody(); // Für POST-Daten
        $accreditationUid = (int) ($parsedBody['tx_publicrelations_eventcenter']['accreditation'] ?? 0);
        $notesReceived = trim($parsedBody['tx_publicrelations_eventcenter']['notesReceived'] ?? '');
        // Der Wert von ticketsReceivedCount ist die *Anzahl der Tickets, die in diesem Vorgang abgeholt werden*,
        // nicht der neue Gesamtstand von accreditation.ticketsReceived!
        $ticketsReceivedInThisStep = (int) ($parsedBody['tx_publicrelations_eventcenter']['ticketsReceivedCount'] ?? 0);

        if ($accreditationUid <= 0) {
            return new JsonResponse(['success' => false, 'message' => 'Ungültige Akkreditierungs-ID.']);
        }

        /** @var Accreditation|null $accreditation */
        $accreditation = $this->accreditationRepository->findByUid($accreditationUid);

        if (!$accreditation) {
            return new JsonResponse(['success' => false, 'message' => 'Akkreditierung nicht gefunden.']);
        }

        // Validierung: nicht 0 Tickets UND keine Notiz
        if ($ticketsReceivedInThisStep === 0 && empty($notesReceived)) {
            return new JsonResponse(['success' => false, 'message' => 'Bitte geben Sie entweder eine Ticketanzahl (>0) oder eine Anmerkung ein.']);
        }

        // --- Sperrprüfung (optional, aber gut für Konsistenz) ---
        $currentBeUserUid = $this->generalFunctions->getCurrentBackendUserUid();
        if (
            $accreditation->getLockingBeUserUid() > 0 &&
            $accreditation->getLockingBeUserUid() !== $currentBeUserUid &&
            (time() - $accreditation->getLockingTstamp()) < 300
        ) {
            // Normalerweise sollte das JS dies schon verhindern, aber als serverseitige Sicherheit
            return new JsonResponse(['success' => false, 'message' => 'Konflikt: Wird von anderem Benutzer bearbeitet.']);
        }
        // --- Ende Sperrprüfung ---

        // Logik für Ticket-Anpassung:
        // ticketsReceivedInThisStep kann positiv (Tickets werden abgeholt)
        // oder negativ (Tickets werden zurückgenommen) sein.
        $newTotalTicketsReceived = $accreditation->getTicketsReceived() + $ticketsReceivedInThisStep;

        // Sicherstellen, dass die neue Anzahl nicht negativ und nicht größer als genehmigt ist
        if ($newTotalTicketsReceived < 0) {
            return new JsonResponse(['success' => false, 'message' => 'Ungültige Aktion: Es können nicht mehr Tickets zurückgenommen werden, als bisher abgeholt wurden.']);
        }
        if ($newTotalTicketsReceived > $accreditation->getTicketsApproved()) {
            return new JsonResponse(['success' => false, 'message' => 'Ungültige Aktion: Es können nicht mehr Tickets abgeholt werden, als genehmigt wurden.']);
        }

        $accreditation->setTicketsReceived($newTotalTicketsReceived);
        $accreditation->setNotesReceived($notesReceived); // Überschreibt alte Check-in-Notiz, oder anfügen? Fürs Erste überschreiben.

        // Status anpassen
        if ($newTotalTicketsReceived >= $accreditation->getTicketsApproved() && $accreditation->getTicketsApproved() > 0) {
            $accreditation->setStatus(2); // Vollständig eingecheckt
        } elseif ($newTotalTicketsReceived > 0) {
            $accreditation->setStatus(2); // Teilweise eingecheckt (Status bleibt 2)
        } elseif ($newTotalTicketsReceived === 0 && $accreditation->getTicketsApproved() > 0) {
            // Wenn alle Tickets zurückgenommen wurden, aber welche genehmigt waren
            $accreditation->setStatus(1); // Zurück auf "Akkreditiert, aber nicht abgeholt"
        }
        // Wenn ticketsApproved == 0, bleibt der Status wie er ist (wahrscheinlich 1)

        // Sperre aufheben
        $accreditation->setLockingBeUserUid(0);
        $accreditation->setLockingTstamp(0);


        // LOG
        $report = '<strong>Tickets:</strong> ' . $newTotalTicketsReceived . ' von ' . $accreditation->getTicketsApproved();
        $report .= '<br><strong>Ausstehende Tickets:</strong> ' . $accreditation->getTicketsPrepared();
        if ($accreditation->getNotesReceived())
            $report .= '<br><strong>Anmerkungen:</strong> ' . $accreditation->getNotesReceived();
        $log = $this->logGenerator->createAccreditationLog('A-CheckIn', $accreditation, $report);
        $accreditation->addLog($log);

        $this->accreditationRepository->update($accreditation);
        $this->persistenceManager->persistAll();

        return new JsonResponse([
            'success' => true,
            'message' => $accreditation->getGuestOutput()['fullName'] . ': ' . abs($ticketsReceivedInThisStep) .
                ($ticketsReceivedInThisStep >= 0 ? ' Ticket(s) erfolgreich abgeholt.' : ' Ticket(s) zurückgenommen.'),
            'newState' => $accreditation->getStatusOutput(), // Optional, für Debugging
            'newTicketsReceived' => $accreditation->getTicketsReceived()
        ]);
    }

    /**
     * Releases the lock on an accreditation if the current user holds it.
     * Expects 'tx_publicrelations_eventcenter[accreditation]' (UID) via POST.
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function releaseAccreditationLockAction(ServerRequestInterface $request): ResponseInterface
    {
        $parsedBody = $request->getParsedBody();
        $accreditationUid = (int) ($parsedBody['tx_publicrelations_eventcenter']['accreditation'] ?? 0);
        $currentBeUserUid = $this->generalFunctions->getCurrentBackendUserUid();

        if ($accreditationUid <= 0) {
            return new JsonResponse(['success' => false, 'message' => 'Ungültige Akkreditierungs-ID.']);
        }

        /** @var \BucheggerOnline\Publicrelations\Domain\Model\Accreditation|null $accreditation */
        $accreditation = $this->accreditationRepository->findByUid($accreditationUid);

        if (!$accreditation) {
            return new JsonResponse(['success' => false, 'message' => 'Akkreditierung nicht gefunden.']);
        }

        // Nur der User, der gesperrt hat, kann die Sperre aktiv freigeben.
        // Admins könnten eine Sonderrolle haben, hier nicht implementiert.
        if ($accreditation->getLockingBeUserUid() === $currentBeUserUid && $currentBeUserUid > 0) {
            $accreditation->setLockingBeUserUid(0);
            $accreditation->setLockingTstamp(0);
            $this->accreditationRepository->update($accreditation);
            $this->persistenceManager->persistAll();

            // LogGenerator::logLockReleased($accreditation, $currentBeUserUid); // Optionales Logging
            return new JsonResponse(['success' => true, 'message' => 'Sperre für Akkreditierung ' . $accreditationUid . ' wurde freigegeben.']);
        } elseif ($accreditation->getLockingBeUserUid() === 0) {
            return new JsonResponse(['success' => true, 'message' => 'Akkreditierung war nicht gesperrt.']); // Kein Fehler, einfach Info
        } else {
            return new JsonResponse(['success' => false, 'message' => 'Sperre kann nicht von diesem Benutzer freigegeben werden.'], 403);
        }
    }
}