<?php
declare(strict_types=1);

namespace BucheggerOnline\Publicrelations\Backend\Ajax;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Backend\Routing\UriBuilder as BackendUriBuilder;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Resource\ResourceFactory;

use BucheggerOnline\Publicrelations\Domain\Repository\ReportRepository;
use BucheggerOnline\Publicrelations\Domain\Repository\ClientRepository;
use BucheggerOnline\Publicrelations\Domain\Repository\CampaignRepository;
use BucheggerOnline\Publicrelations\Domain\Repository\ClippingRouteRepository;

class ReportAjaxController extends ActionController
{
    private const TABLE_REPORT = 'tx_publicrelations_domain_model_report';
    private const TABLE_LINK = 'tx_publicrelations_domain_model_link';
    private const TABLE_FILE_REF = 'sys_file_reference';
    private const TABLE_FILE = 'sys_file';

    private ReportRepository $reportRepository;
    private ClientRepository $clientRepository;
    private CampaignRepository $campaignRepository;
    private ClippingRouteRepository $clippingRouteRepository;
    private ResourceFactory $resourceFactory;
    private ConnectionPool $connectionPool;

    // Hybrid-Konstruktor
    public function __construct(
        ?ReportRepository $reportRepository = null,
        ?ClientRepository $clientRepository = null,
        ?CampaignRepository $campaignRepository = null,
        ?ClippingRouteRepository $clippingRouteRepository = null,
        ?ResourceFactory $resourceFactory = null,
        ?ConnectionPool $connectionPool = null
    ) {
        $this->reportRepository = $reportRepository ?? GeneralUtility::makeInstance(ReportRepository::class);
        $this->clientRepository = $clientRepository ?? GeneralUtility::makeInstance(ClientRepository::class);
        $this->campaignRepository = $campaignRepository ?? GeneralUtility::makeInstance(CampaignRepository::class);
        $this->clippingRouteRepository = $clippingRouteRepository ?? GeneralUtility::makeInstance(ClippingRouteRepository::class);
        $this->resourceFactory = $resourceFactory ?? GeneralUtility::makeInstance(ResourceFactory::class);
        $this->connectionPool = $connectionPool ?? GeneralUtility::makeInstance(ConnectionPool::class);
    }

    /**
     * Holt die Daten für das Grid.js-Dashboard.
     */
    public function listReports(ServerRequestInterface $request): ResponseInterface
    {
        $params = $request->getQueryParams();
        $searchQuery = $params['query'] ?? '';

        $uriBuilder = GeneralUtility::makeInstance(BackendUriBuilder::class);

        // 1. Hole alle Lookups EINMAL
        $clientLookup = $this->clientRepository->findAllForLookup();
        $campaignLookup = $this->campaignRepository->findAllForLookup();
        $clippingRouteLookup = $this->clippingRouteRepository->findAllForLookup();

        // 2. Hole die Reports
        $reportsArray = $this->reportRepository->findFilteredForBackend($searchQuery);
        $reportUids = array_column($reportsArray, 'uid');
        if (empty($reportUids)) {
            return new JsonResponse(['data' => [], 'total' => 0]);
        }

        // 3. Hole alle zugehörigen Links (IRRE)
        $linksByReportUid = $this->findAllLinksForReports($reportUids);

        // 4. Hole alle zugehörigen Files (FAL)
        $filesByReportUid = $this->findAllFilesForReports($reportUids);

        // 5. Baue die returnUrl für den Edit-Button
        $returnUrl = (string) $uriBuilder->buildUriFromRoute(
            'allegria_reports_list', // <-- Die neue Modul-Route
            ['query' => $searchQuery]
        );

        $reportData = [];
        foreach ($reportsArray as $report) {
            $reportUid = (int) $report['uid'];

            // 6. Edit-URL
            $editParams = [
                'edit' => [self::TABLE_REPORT => [$reportUid => 'edit']],
                'returnUrl' => $returnUrl
            ];
            $report['edit_url'] = (string) $uriBuilder->buildUriFromRoute('record_edit', $editParams);

            // 7. UIDs durch OBJEKTE ERSETZEN
            $report['client'] = $clientLookup[(int) $report['client']] ?? null;
            $report['campaign'] = $campaignLookup[(int) $report['campaign']] ?? null;
            $routeUid = (int) $report['clippingroute'];
            $report['clippingroute'] = $clippingRouteLookup[$routeUid] ?? null;

            $route = $clippingRouteLookup[$routeUid] ?? null;

            $keywordString = 'N/A';
            $keywordQuery = '';

            if ($route && !empty($route['keyword'])) {
                $keywordString = $route['keyword'];

                if (str_contains($keywordString, ' ')) {
                    $keywordQuery = 'keyword:"' . $keywordString . '"';
                } else {
                    $keywordQuery = 'keyword:' . $keywordString;
                }
            }

            $report['keyword_string'] = $keywordString;

            // Der UriBuilder kümmert sich um das korrekte Encoding (z.B. " -> %22)
            $report['keyword_filter_url'] = (string) $uriBuilder->buildUriFromRoute(
                'allegria_reports_clippingroutes', // Die Route zur clippingRoutesAction
                ['query' => $keywordQuery]
            );

            // 8. NEU: Files & Links anhängen
            $report['links_data'] = $linksByReportUid[$reportUid] ?? [];

            $filesData = $filesByReportUid[$reportUid] ?? [];
            $processedFiles = [];
            foreach ($filesData as $fileRecord) {
                try {
                    // Nutze die injizierte ResourceFactory
                    $fileObject = $this->resourceFactory->getFileObject($fileRecord['uid_local']);
                    // Generiere die URL dynamisch
                    $fileRecord['public_url'] = $fileObject->getPublicUrl();
                } catch (\Exception $e) {
                    $fileRecord['public_url'] = '#FEHLER-DATEI-NICHT-GEFUNDEN';
                }
                $processedFiles[] = $fileRecord;
            }
            $report['files_data'] = $processedFiles;

            // 9. Datum formatieren
            $timestamp = (int) $report['date'];
            if ($timestamp > 0) {
                $dateTime = (new \DateTime())->setTimestamp($timestamp);
                $timeString = $dateTime->format('H:i');

                if ($timeString === '00:00') {
                    $dateString = $dateTime->format('d.m.Y');
                } else {
                    $dateString = $dateTime->format('d.m.Y, H:i \U\h\r');
                }

                $report['date'] = [
                    'string' => $dateString,
                    'timestamp' => $timestamp
                ];
            } else {
                $report['date'] = ['string' => '-', 'timestamp' => 0];
            }

            $reportData[] = $report;
        }

        return new JsonResponse([
            'data' => $reportData,
            'total' => count($reportData),
        ]);
    }

    /**
     * Holt alle IRRE-Links für die gegebenen Report-UIDs
     */
    private function findAllLinksForReports(array $reportUids): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE_LINK);
        $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $rows = $queryBuilder
            ->select('uid', 'report', 'title', 'url')
            ->from(self::TABLE_LINK)
            ->where(
                $queryBuilder->expr()->in('report', $queryBuilder->createNamedParameter($reportUids, Connection::PARAM_INT_ARRAY))
            )
            ->executeQuery()
            ->fetchAllAssociative();

        // Gruppiere nach 'report' (UID)
        $grouped = [];
        foreach ($rows as $row) {
            $grouped[$row['report']][] = $row;
        }
        return $grouped;
    }

    /**
     * Holt alle FAL-Files für die gegebenen Report-UIDs
     */
    private function findAllFilesForReports(array $reportUids): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE_FILE_REF);
        $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $rows = $queryBuilder
            ->select(
                'fr.uid',
                'fr.uid_foreign',
                'fr.title',
                'fr.uid_local',
                'f.name',
                'f.extension'
            )
            // --- ENDE FIX ---
            ->from(self::TABLE_FILE_REF, 'fr')
            ->join(
                'fr',
                self::TABLE_FILE,
                'f',
                $queryBuilder->expr()->eq('fr.uid_local', $queryBuilder->quoteIdentifier('f.uid'))
            )
            ->where(
                $queryBuilder->expr()->eq('fr.tablenames', $queryBuilder->createNamedParameter(self::TABLE_REPORT)),
                $queryBuilder->expr()->eq('fr.fieldname', $queryBuilder->createNamedParameter('files')),
                $queryBuilder->expr()->in('fr.uid_foreign', $queryBuilder->createNamedParameter($reportUids, Connection::PARAM_INT_ARRAY))
            )
            ->executeQuery()
            ->fetchAllAssociative();

        // Gruppiere nach 'uid_foreign' (der Report-UID)
        $grouped = [];
        foreach ($rows as $row) {
            // Wir mappen 'uid_foreign' auf 'report' für Konsistenz
            $reportUid = $row['uid_foreign'];
            $grouped[$reportUid][] = $row;
        }
        return $grouped;
    }
}