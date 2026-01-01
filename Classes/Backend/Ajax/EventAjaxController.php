<?php

declare(strict_types=1);

namespace BucheggerOnline\Publicrelations\Backend\Ajax;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use TYPO3\CMS\Backend\Routing\UriBuilder as BackendUriBuilder;

use BucheggerOnline\Publicrelations\Service\ImageProcessingService;

class EventAjaxController
{

    public function findEvents(ServerRequestInterface $request): ResponseInterface
    {
        $queryParams = $request->getQueryParams();
        $searchTerm = $queryParams['q'] ?? '';

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_publicrelations_domain_model_event');

        // Prüft, ob "+archiv" im Suchbegriff enthalten ist
        $isArchivIncluded = strpos($searchTerm, '+archiv') !== false;

        // Entfernt "+archiv" aus dem Suchbegriff, falls vorhanden
        $searchTerm = str_replace('+archiv', '', $searchTerm);

        // Teilt den bereinigten Suchbegriff in einzelne Wörter
        $searchTerms = explode(' ', $searchTerm);

        // Beginnt mit der Basis-Query
        $queryBuilder->select('e.uid', 'e.date', 'e.title', 'c.uid AS clientId', 'c.name AS clientName', 'c.logo AS clientLogo', 'p.uid AS productId', 'p.title AS productName', 'p.logo AS productLogo', 'l.name AS locationName', 'l.city')
            ->from('tx_publicrelations_domain_model_event', 'e')
            ->leftJoin('e', 'tx_publicrelations_domain_model_client', 'c', 'e.client = c.uid')
            ->leftJoin('e', 'tx_publicrelations_domain_model_campaign', 'p', 'e.campaign = p.uid')
            ->leftJoin('e', 'tx_publicrelations_domain_model_location', 'l', 'e.location = l.uid')
            ->where(
                $queryBuilder->expr()->eq('e.deleted', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('e.hidden', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT))
            );

        // Fügt eine Bedingung für das Datum hinzu, abhängig davon, ob "+archiv" im Suchbegriff enthalten war
        if (!$isArchivIncluded) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->gte('e.date', $queryBuilder->createNamedParameter(strtotime('today midnight'), Connection::PARAM_INT))
            );
        }

        // Fügt Suchbedingungen für jeden Suchbegriff hinzu
        foreach ($searchTerms as $term) {
            // Annahme: $queryBuilder ist deine aktive QueryBuilder-Instanz, auf der du die Query aufbaust.
            // Manchmal wird die Instanz auch $query genannt, nachdem ->select() und ->from() aufgerufen wurden.
            // Stelle sicher, dass du die Methode auf der richtigen Variable aufrufst.
            $queryBuilder->andWhere(
                $queryBuilder->expr()->or( // ERSETZT: orX() wurde zu or()
                    $queryBuilder->expr()->like('e.title', $queryBuilder->createNamedParameter('%' . $term . '%')),
                    $queryBuilder->expr()->like('l.name', $queryBuilder->createNamedParameter('%' . $term . '%')),
                    $queryBuilder->expr()->like('l.city', $queryBuilder->createNamedParameter('%' . $term . '%')),
                    $queryBuilder->expr()->like('c.name', $queryBuilder->createNamedParameter('%' . $term . '%')),
                    $queryBuilder->expr()->like('p.title', $queryBuilder->createNamedParameter('%' . $term . '%'))
                )
            );
        }

        // Führt die Query aus und holt die Ergebnisse
        $events = $queryBuilder
            ->groupBy('e.uid')
            ->orderBy('e.date', 'ASC')
            ->executeQuery()
            ->fetchAllAssociative();

        $groupedEvents = [];
        foreach ($events as $event) {
            // Bestimme die Gruppierung: Produkt, falls vorhanden, sonst Kunde
            $groupKey = $event['productId'] ?? $event['clientId'];
            $groupName = $event['productName'] ?? $event['clientName'];
            $cropVariant = $event['productId'] ? 'thumb' : 'default';

            // Konfiguration um das Bild zu bearbeiten
            $processingInstructions = [
                'width' => '50c',
                'height' => '50c',
                'cropArea' => $cropVariant
            ];

            $groupLogo = GeneralUtility::makeInstance(ImageProcessingService::class)->getImage((int) $event['productId'], 'tx_publicrelations_domain_model_campaign', 'logo', $processingInstructions) ?? GeneralUtility::makeInstance(ImageProcessingService::class)->getImage((int) $event['clientId'], 'tx_publicrelations_domain_model_client', 'logo', $processingInstructions); // Pfad zum Logo anpassen

            if (!array_key_exists($groupKey, $groupedEvents)) {
                $groupedEvents[$groupKey] = [
                    'groupName' => $groupName,
                    'groupLogo' => $groupLogo,
                    'events' => []
                ];
            }

            // Erstelle den BackendUriBuilder
            $backendUriBuilder = GeneralUtility::makeInstance(BackendUriBuilder::class);
            $url = (string) $backendUriBuilder
                ->buildUriFromRoute(
                    'allegria_eventcenter',
                    [
                        'event' => (int) $event['uid'],
                        'controller' => 'Event',
                        'action' => 'show',
                    ]
                );

            $groupedEvents[$groupKey]['events'][] = [
                'id' => $event['uid'],
                'title' => $event['title'],
                'date' => $event['date'],
                'guestCount' => $this->getTicketsApproved((int) $event['uid']),
                'location' => $event['locationName'],
                'city' => $event['city'],
                'url' => $url
            ];
        }

        return new JsonResponse(array_values($groupedEvents)); // Die Gruppen-Keys sind nicht notwendig für das Frontend, daher `array_values`
    }

    protected function getTicketsApproved($eventId)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_publicrelations_domain_model_accreditation');
        $totalTickets = $queryBuilder
            ->selectLiteral('SUM(tickets_approved) AS tickets_approved')
            ->from('tx_publicrelations_domain_model_accreditation')
            ->where(
                $queryBuilder->expr()->in('status', [1, 2]), // Status 1 und 2 berücksichtigen
                $queryBuilder->expr()->eq('event', $queryBuilder->createNamedParameter($eventId, Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('hidden', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT))
            )
            ->executeQuery()
            ->fetchOne();

        return (int) $totalTickets;
    }

}
