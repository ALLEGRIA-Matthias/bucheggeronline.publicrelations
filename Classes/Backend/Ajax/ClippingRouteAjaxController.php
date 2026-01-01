<?php

declare(strict_types=1);

namespace BucheggerOnline\Publicrelations\Backend\Ajax;

use Allegria\AcDistribution\Controller\MessagePreviewController;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Backend\Routing\UriBuilder as BackendUriBuilder;

use BucheggerOnline\Publicrelations\Domain\Repository\ClippingRouteRepository;
use BucheggerOnline\Publicrelations\Domain\Repository\ReportRepository;
use BucheggerOnline\Publicrelations\Domain\Repository\ClientRepository;
use BucheggerOnline\Publicrelations\Domain\Repository\CampaignRepository;
use BucheggerOnline\Publicrelations\DataResolver\ReportDataResolver;
use Allegria\AcDistribution\Service\DistributionService;

class ClippingRouteAjaxController extends ActionController
{
    private const TABLE_REPORT = 'tx_publicrelations_domain_model_report';
    private const TABLE_ROUTE = 'tx_publicrelations_domain_model_clippingroute';

    private ClippingRouteRepository $clippingRouteRepository;
    private ReportRepository $reportRepository;
    private ClientRepository $clientRepository;
    private CampaignRepository $campaignRepository;
    private DistributionService $distributionService;

    public function __construct(
        ?ClippingRouteRepository $clippingRouteRepository = null,
        ?ReportRepository $reportRepository = null,
        ?ClientRepository $clientRepository = null,
        ?CampaignRepository $campaignRepository = null,
        ?DistributionService $distributionService = null,
    ) {
        $this->clippingRouteRepository = $clippingRouteRepository ?? GeneralUtility::makeInstance(ClippingRouteRepository::class);
        $this->reportRepository = $reportRepository ?? GeneralUtility::makeInstance(ReportRepository::class);
        $this->clientRepository = $clientRepository ?? GeneralUtility::makeInstance(ClientRepository::class);
        $this->campaignRepository = $campaignRepository ?? GeneralUtility::makeInstance(CampaignRepository::class);
        $this->distributionService = $distributionService ?? GeneralUtility::makeInstance(DistributionService::class);
    }

    /**
     * Holt die Daten für das Grid.js-Dashboard.
     */
    public function listClippingRoutes(ServerRequestInterface $request): ResponseInterface
    {
        $params = $request->getQueryParams();
        $searchQuery = $params['query'] ?? null;

        $uriBuilder = GeneralUtility::makeInstance(BackendUriBuilder::class);

        // 1. Hole alle Lookups EINMAL
        $clientLookup = $this->clientRepository->findAllForLookup();
        $campaignLookup = $this->campaignRepository->findAllForLookup();

        // 2. Hole die Routen
        $routesArray = $this->clippingRouteRepository->findFilteredForBackend($searchQuery);

        // 3. Baue die returnUrl für den Edit-Button (inkl. Suche)
        $returnUrl = (string) $uriBuilder->buildUriFromRoute(
            'allegria_reports', // Deine Modul-Route
            [
                'controller' => 'Report',
                'action' => 'clippingRoutes',
                'query' => $searchQuery
            ] // Übergibt die Suche
        );

        $routeData = [];
        foreach ($routesArray as $route) {

            // 4. Zählung
            $route['unreported_count'] = $this->reportRepository->countUnreportedByRoute($route['uid']);

            // 5. Vorschau-URL
            $route['preview_url'] = (string) $uriBuilder->buildUriFromRoute(
                'ac_distribution', // Die generische Route aus DashboardController
                [
                    'controller' => 'MessagePreview',
                    'action' => 'preview',
                    'dataResolverClass' => ReportDataResolver::class,
                    'dataSourceUid' => (int) $route['uid'],
                    'function' => 'send_clipping' // Die Aktion
                ]
            );

            // 6. Edit-URL (mit returnUrl)
            $editParams = [
                'edit' => [
                    self::TABLE_ROUTE => [
                        (int) $route['uid'] => 'edit'
                    ]
                ],
                'returnUrl' => $returnUrl
            ];
            $route['edit_url'] = (string) $uriBuilder->buildUriFromRoute('record_edit', $editParams);

            // 7. FILTER-LINK
            $keyword = $route['keyword'] ?? '';
            $filterQuery = '';
            if (!empty($keyword)) {
                // Füge Anführungszeichen hinzu, falls Leerzeichen enthalten sind
                if (str_contains($keyword, ' ')) {
                    $filterQuery = 'keyword:"' . $keyword . '"';
                } else {
                    $filterQuery = 'keyword:' . $keyword;
                }
            }
            
            $route['filter_reports_url'] = (string) $uriBuilder->buildUriFromRoute(
                'allegria_reports_list', // <-- Die Route zur ReportsAction
                ['query' => $filterQuery]
            );

            // 8. Zuordnung (Client/Projekt)
            $clientId = (int) $route['client'];
            $route['client'] = $clientLookup[$clientId] ?? null; // Ersetzt die UID durch das Client-Array

            $projectId = (int) $route['project'];
            $route['project'] = $campaignLookup[$projectId] ?? null; // Ersetzt die UID durch das Campaign-Array

            $routeData[] = $route;
        }

        return new JsonResponse([
            'data' => $routeData,
            'total' => count($routeData),
        ]);
    }

    /**
     * Triggert den Versand für EINE Route über den DistributionService.
     * (Diese Methode bleibt unverändert, sie war korrekt)
     */
    public function sendClippingRoute(ServerRequestInterface $request): ResponseInterface
    {
        $body = $request->getParsedBody();
        $routeUid = (int) ($body['routeUid'] ?? 0);

        if ($routeUid === 0) {
            return new JsonResponse(['success' => false, 'message' => 'Missing routeUid'], 400);
        }

        try {

            // 2. Das "Rezept" (Context) erstellen
            $context = [
                'context' => 'Manueller Clipping-Versand (Route ' . $routeUid . ')',
                'sender_profile' => 1, // TODO: Korrekte Sender-Profil-UID eintragen

                'dataSource' => [
                    'uids' => [$routeUid], // Die UID der ClippingRoute
                    'function' => 'send_clipping',
                    'dataResolverClass' => ReportDataResolver::class,
                ],

                'report' => [
                    'job_title' => 'Manueller Versand Route ' . $routeUid,
                ]
            ];

            // 3. Job an ac_distribution übergeben
            $result = $this->distributionService->send($context);

            if ($result['status'] === 'queued' || $result['status'] === 'sent') {
                return new JsonResponse([
                    'success' => true,
                    'message' => 'Versand-Job erfolgreich erstellt.',
                    'result' => $result
                ]);
            } else {
                return new JsonResponse([
                    'success' => false,
                    'message' => $result['message'] ?? 'Fehler beim Erstellen des Jobs.',
                    'result' => $result
                ], 500);
            }

        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}