<?php
declare(strict_types=1);

namespace BucheggerOnline\Publicrelations\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use BucheggerOnline\Publicrelations\Domain\Repository\ClientRepository;
use BucheggerOnline\Publicrelations\Domain\Repository\CampaignRepository;
use BucheggerOnline\Publicrelations\Domain\Repository\EventRepository;
use BucheggerOnline\Publicrelations\Domain\Repository\SysCategoryRepository;

class SearchController extends AbstractPublicrelationsController
{
    public function __construct(
        private readonly ClientRepository $clientRepository,
        private readonly CampaignRepository $campaignRepository,
        private readonly EventRepository $eventRepository,
        private readonly SysCategoryRepository $sysCategoryRepository
    ) {
    }

    /**
     * Zeigt Suchergebnisse an.
     *
     * @param string|null $query Der Suchbegriff (wird von Extbase aus dem Request-Parameter 'query' gemappt)
     */
    public function resultAction(?string $query = null): ResponseInterface
    {

        $actualSearchTerm = '';

        /** @var ServerRequestInterface|null $httpRequest */
        $httpRequest = $this->request->getAttribute('originalRequest');
        
        // Fallback auf $GLOBALS['TYPO3_REQUEST'], falls 'originalRequest' nicht im Extbase-Request vorhanden ist
        if (!$httpRequest instanceof ServerRequestInterface && isset($GLOBALS['TYPO3_REQUEST']) && $GLOBALS['TYPO3_REQUEST'] instanceof ServerRequestInterface) {
            // Diese Meldung ist eher für dich beim Debuggen, falls der erste Weg nicht klappt:
            // error_log('[SearchController] Fallback: $GLOBALS[\'TYPO3_REQUEST\'] wird als PSR-7 Request verwendet.');
            $httpRequest = $GLOBALS['TYPO3_REQUEST'];
        }

        // 1. Versuche, den 'query'-Parameter direkt aus den GET-Parametern des PSR-7 Requests zu lesen
        if ($httpRequest instanceof ServerRequestInterface) {
            $queryParams = $httpRequest->getQueryParams();
            if (isset($queryParams['query'])) { // Dein ?query=... Parameter
                $actualSearchTerm = trim((string)$queryParams['query']);
            }
        } else {
            // Dies sollte in einem normalen Frontend-Request-Kontext nicht passieren.
            // Wenn doch, logge es, da etwas Grundlegendes mit dem Request-Handling nicht stimmt.
            // GeneralUtility::makeInstance(\TYPO3\CMS\Core\Log\LogManager::class)->getLogger(__CLASS__)->error('PSR-7 ServerRequestInterface konnte in SearchController::resultAction nicht ermittelt werden.');
        }

        /** @var ServerRequestInterface|null $psr7Request */
        $psr7Request = $this->request->getAttribute('originalRequest');

        if ($psr7Request instanceof ServerRequestInterface) {
            $queryParams = $psr7Request->getQueryParams();
            if (isset($queryParams['query'])) {
                $actualSearchTerm = trim((string)$queryParams['query']);
            }
        }

        // Den Suchbegriff für die Vorbelegung des Suchfelds im Fluid-Template an die View übergeben
        $this->view->assign('searchQuery', $actualSearchTerm); // Verwende einen konsistenten Variablennamen

        $searchParamsForRepository = null;
        if (!empty($actualSearchTerm)) {
            $searchParamsForRepository = ['query' => $actualSearchTerm]; // Deine Repositories erwarten ['query' => 'begriff']
            
            $clients = $this->clientRepository->findByQuery($searchParamsForRepository);
            $campaigns = $this->campaignRepository->findByQuery($searchParamsForRepository);
            $schedule = [
                'preview' => null,
                'events' => $this->eventRepository->findByQuery($searchParamsForRepository)
            ];
        } else {
            // Keine Suchanfrage, leere Ergebnisse oder Standardansicht
            $clients = null; // Oder leeres QueryResult/ArrayCollection
            $campaigns = null;
            $schedule = ['preview' => null, 'events' => null];
        }

        $this->view->assign('clients', $clients);
        $this->view->assign('campaigns', $campaigns);
        $this->view->assign('schedule', $schedule);

        return $this->frontendResponse(); // Deine Methode zum Rendern der View
    }
}
