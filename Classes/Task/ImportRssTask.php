<?php
namespace BucheggerOnline\Publicrelations\Task;

use BucheggerOnline\Publicrelations\Domain\Model\Report;
use BucheggerOnline\Publicrelations\Domain\Repository\ClippingRouteRepository;
use BucheggerOnline\Publicrelations\Domain\Repository\ReportRepository;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use Allegria\AcDistribution\Service\DistributionService;
use Allegria\AcDistribution\Service\DirectSendService;
use Allegria\AcDistribution\Service\MessageProcessingService;
use Allegria\AcDistribution\Service\SmtpService;
use Allegria\AcDistribution\Service\MailBuildService;
use Allegria\AcDistribution\Service\LinkTrackingService;
use Allegria\AcDistribution\Utility\DataHandlerService;
use Allegria\AcDistribution\Domain\Repository\JobRepository;
use Allegria\AcDistribution\Domain\Repository\SenderProfileRepository;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use BucheggerOnline\Publicrelations\DataResolver\ReportDataResolver;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;
use TYPO3\CMS\Scheduler\Task\AbstractTask;
use TYPO3\CMS\Core\DataHandling\DataHandler;

class ImportRssTask extends AbstractTask
{
    private const RSS_FEED_URL = 'https://rss.crs.apa.at/feed/allegria/bjpCjFU2TFLqXIOZybpE';

    private RequestFactory $requestFactory;
    private ReportRepository $reportRepository;
    private ClippingRouteRepository $clippingRouteRepository;
    private ResourceFactory $resourceFactory;
    private DataHandler $dataHandler;
    private DistributionService $distributionService;
    private $smtpService;
    public $report_receiver = '';
    public $send_immediately = false;

    /**
     * Hier werden alle Services per Constructor Injection (wie in deinem Controller) geladen.
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function execute(): bool
    {
        // === 0. Services und Repositories manuell holen ===
        $this->requestFactory = GeneralUtility::makeInstance(RequestFactory::class);
        $this->dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $this->resourceFactory = GeneralUtility::makeInstance(ResourceFactory::class);

        $persistenceManager = GeneralUtility::makeInstance(PersistenceManager::class);
        $jobRepository = GeneralUtility::makeInstance(JobRepository::class);
        $senderProfileRepository = GeneralUtility::makeInstance(SenderProfileRepository::class);

        $dataHandlerService = GeneralUtility::makeInstance(DataHandlerService::class);
        $linkTrackingService = GeneralUtility::makeInstance(LinkTrackingService::class);
        $smtpService = GeneralUtility::makeInstance(SmtpService::class);
        $this->smtpService = $smtpService;

        // Ebene 2 (haben Abhängigkeiten)
        $mailBuildService = GeneralUtility::makeInstance(
            MailBuildService::class,
            $linkTrackingService,
            $smtpService
        );

        $messageProcessingService = GeneralUtility::makeInstance(
            MessageProcessingService::class,
            $smtpService,
            $mailBuildService,
            $dataHandlerService
        );

        // Ebene 1
        $directSendService = GeneralUtility::makeInstance(
            DirectSendService::class,
            $dataHandlerService,
            $messageProcessingService
        );

        // Ebene 0 (Zieldienst)
        $this->distributionService = GeneralUtility::makeInstance(
            DistributionService::class,
            $directSendService,
            $dataHandlerService,
            $jobRepository,
            $senderProfileRepository,
            $persistenceManager
        );

        // Repositories für diesen Task
        $this->reportRepository = GeneralUtility::makeInstance(\BucheggerOnline\Publicrelations\Domain\Repository\ReportRepository::class);
        $this->clippingRouteRepository = GeneralUtility::makeInstance(\BucheggerOnline\Publicrelations\Domain\Repository\ClippingRouteRepository::class);

        // Konfiguration
        $storagePid = 2; // <-- DEINE PID, WO DIE REPORTS GESPEICHERT WERDEN
        $fileStorageUid = 2; // <-- DEINE FILE STORAGE UID (meist 1 für fileadmin/)
        $fileStorageBasePath = 'clippingservice/'; // <-- Unterordner in fileadmin/

        $this->logger->info('RSS Importer gestarted');

        try {
            $storage = $this->resourceFactory->getStorageObject($fileStorageUid);
            if (!$storage->hasFolder($fileStorageBasePath))
                $storage->createFolder($fileStorageBasePath);
            $baseFolderObject = $storage->getFolder($fileStorageBasePath);
        } catch (\Exception $e) {
            $this->logger->error('FEHLER: File Storage (UID ' . $fileStorageUid . ') oder Basis-Ordner "' . $fileStorageBasePath . '" nicht gefunden.', ['exception' => $e]);
            return false; // Task fehlschlagen lassen
        }

        $allRoutes = $this->clippingRouteRepository->findAll();
        $importReportData = [];
        $totalImportCount = 0;

        $routesToNotify = [];

        // Registry holen (zum Zählen von Fehlern)
        $registry = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Registry::class);
        $registryNamespace = 'tx_publicrelations_rss';
        $registryKey = 'fail_count';

        // === 1. RSS-Feed abrufen ===
        try {
            // Optional: User-Agent setzen, reduziert die Chance auf 403er (wie vorhin besprochen)
            $response = $this->requestFactory->request(self::RSS_FEED_URL, 'GET', [
                'headers' => ['User-Agent' => 'Mozilla/5.0 (Compatible; TYPO3 RSS Importer)']
            ]);
            $xmlString = $response->getBody()->getContents();

            // WENN ERFOLGREICH: Zähler auf 0 zurücksetzen
            $registry->set($registryNamespace, $registryKey, 0);

        } catch (\Exception $e) {
            // FEHLERFALL: Zähler erhöhen
            $currentFailCount = (int) $registry->get($registryNamespace, $registryKey, 0);
            $currentFailCount++;
            $registry->set($registryNamespace, $registryKey, $currentFailCount);

            // SCHWELLENWERT: 12 Versuche * 5 Minuten = 1 Stunde Toleranz
            $threshold = 12;

            if ($currentFailCount >= $threshold) {
                // Schwelle überschritten -> ECHTER FEHLER -> E-Mail wird gesendet
                $this->logger->error('RSS-Feed seit ' . $currentFailCount . ' Versuchen nicht erreichbar. Alarm ausgelöst.', [
                    'last_error' => $e->getMessage()
                ]);
                return false; // Task wird ROT markiert -> E-Mail geht raus
            } else {
                // Schwelle noch nicht erreicht -> WARNUNG -> Keine E-Mail
                $this->logger->warning('RSS-Feed temporär nicht erreichbar (Versuch ' . $currentFailCount . '/' . $threshold . '). Ignoriere Fehler.', [
                    'msg' => $e->getMessage()
                ]);
                return true; // Task wird GRÜN markiert -> Keine E-Mail
            }
        }

        // === 2. XML Parsen ===
        // Wir "platten" die Namespaces (article:page -> article_page), damit json_decode sie frisst

        // // === BUGFIX FÜR KAPUTTEN FEED ===
        // // Der Anbieter hat Typos in den Closing-Tags (artice statt article).
        // // Das müssen wir manuell flicken, sonst stirbt der XML-Parser.
        // $xmlString = str_replace(
        //     ['</artice:time>', '</artice:page>', '</artice:issue>', '</artice:readership>', '</artice:advertising_value>'],
        //     ['</article:time>', '</article:page>', '</article:issue>', '</article:readership>', '</article:advertising_value>'],
        //     $xmlString
        // );
        // // Genereller Fallback, falls sie es woanders auch falsch geschrieben haben:
        // $xmlString = str_replace('</artice:', '</article:', $xmlString);

        // === 2. XML Parsen ===
        // Jetzt erst dein Namespace-Hack
        $xmlStringFixed = preg_replace('/<(\/?)(\w+):(\w+)(.*?)>/', '<$1$2_$3$4>', $xmlString);
        $xml = simplexml_load_string($xmlStringFixed, 'SimpleXMLElement', LIBXML_NOCDATA);
        if ($xml === false) {
            $this->logger->error('Import failed: Could not parse XML.');
            return false;
        }
        $json = json_encode($xml);
        $array = json_decode($json, TRUE);
        $items = $array['channel']['item'] ?? [];
        if (isset($items['guid']))
            $items = [$items];

        // === 3. Alle Items durchlaufen ===
        foreach ($items as $item) {

            $itemGuid = $item['guid'] ?? null;
            if (!$itemGuid)
                continue;

            // Helper Funktion (Lambda) für Basis-Reinigung (Newlines, Tabs, NBSP)
            $cleanAndFixText = function ($text) {
                if (empty($text) || !is_string($text))
                    return '';

                // 1. ENCODING FIX (Das löst deinen DB-Crash!)
                // Wenn der String NICHT valides UTF-8 ist, konvertieren wir ihn von Latin-1 (ISO-8859-1)
                if (!mb_check_encoding($text, 'UTF-8')) {
                    $text = mb_convert_encoding($text, 'UTF-8', 'ISO-8859-1');
                }

                // 2. Whitespace Cleanup
                // &nbsp; und UTF-8 NBSP zu Space
                $text = str_replace(['&nbsp;', "\xc2\xa0"], ' ', $text);
                // Zeilenumbrüche/Tabs zu Space
                $text = str_replace(["\r", "\t"], ' ', $text);
                // Mehrfache Leerzeichen zu einem
                $text = preg_replace('/\s+/', ' ', $text);

                return trim($text);
            };

            // 1. CONTENT (HTML) reinigen
            $rawContent = $item['content_encoded'] ?? '';
            $cleanContent = $cleanAndFixText($rawContent);
            // Spezifisch für HTML-Content: Leerzeichen innerhalb von P-Tags fixen
            $cleanContent = preg_replace('/<p>\s+/', '<p>', $cleanContent);
            $cleanContent = preg_replace('/\s+<\/p>/', '</p>', $cleanContent);

            // 2. SUBTITLE (Plain) reinigen
            $rawSubtitle = $item['article_subtitle'] ?? '';
            $cleanSubtitle = $cleanAndFixText($rawSubtitle);

            // 3. Title und Author reinigen
            $itemTitle = $cleanAndFixText($item['title'] ?? '');
            $itemAuthor = $cleanAndFixText($item['author'] ?? '');

            // --- 3.1 Kategorien & Routen finden ---
            $itemCategories = $item['category'] ?? [];
            if (!is_array($itemCategories) && !empty($itemCategories))
                $itemCategories = [$itemCategories];
            $matchedRoutes = [];
            if (!empty($itemCategories)) {
                foreach ($itemCategories as $itemCategory) {
                    foreach ($allRoutes as $route) {
                        if (stripos($itemCategory, $route->getKeyword()) !== false) {
                            $matchedRoutes[$route->getUid()] = $route;
                        }
                    }
                }
            }
            $routesToProcess = empty($matchedRoutes) ? [null] : array_values($matchedRoutes);

            // === HAUPT-SCHLEIFE (pro gefundener Route) ===
            foreach ($routesToProcess as $matchedRoute) {

                $clientId = $matchedRoute?->getClient()?->getUid() ?? 0;
                $campaignId = $matchedRoute?->getProject()?->getUid() ?? 0;
                $clippingrouteId = $matchedRoute?->getUid() ?? 0;

                // === 3.4 Duplikat-Check pro Route ===
                $existingReport = $this->reportRepository->findOneByApaGuidAndRoute($itemGuid, $clippingrouteId);
                if ($existingReport) {
                    continue;
                }

                // === Report-Daten-Sammler initialisieren ===
                $reportKey = $matchedRoute ? $matchedRoute->getKeyword() : 'Nicht zugewiesen';
                if (!isset($importReportData[$reportKey])) {
                    $importReportData[$reportKey] = [
                        'clippings' => [],
                        'sendImmediate' => $matchedRoute ? $matchedRoute->isSendImmediate() : false,
                    ];
                }

                // === 3.5 Ziel-Ordner bestimmen ===
                $currentTargetFolder = $baseFolderObject;
                $client = null;
                if ($matchedRoute && $matchedRoute->getClient())
                    $client = $matchedRoute->getClient();
                if ($client) {
                    $safeFolderName = $client->getSlug();
                    if (!empty($safeFolderName)) {
                        try {
                            if (!$baseFolderObject->hasFolder($safeFolderName))
                                $currentTargetFolder = $baseFolderObject->createFolder($safeFolderName);
                            else
                                $currentTargetFolder = $baseFolderObject->getSubfolder($safeFolderName);
                        } catch (\Exception $e) {
                            $this->logger->warning('Konnte Ordner nicht erstellen/finden: ' . $safeFolderName, ['exception' => $e]);
                        }
                    }
                }


                // === 4. SCHRITT 1: NUR DEN REPORT ERSTELLEN ===
                $tempReportId = 'NEW_REPORT_' . uniqid();

                $pubDateForDataHandler = 0;
                $pubDateString = 'UnknownDate';
                if (isset($item['pubDate'])) {
                    try {
                        $dateTime = new \DateTime($item['pubDate']);
                        $pubDateTimestamp = $dateTime->getTimestamp();
                        $pubDateString = $dateTime->format('Y-m-d');
                    } catch (\Exception $e) {
                        $this->logger->warning('Konnte pubDate nicht parsen', ['guid' => $itemGuid]);
                        $pubDateTimestamp = time();
                    }
                }
                $source = $item['source'] ?? 'UnknownSource';
                $sourceSafe = preg_replace('/[^A-Za-z0-9_-]/', '', $source);
                $baseFileName = $pubDateString . '_APA_' . $sourceSafe;

                // --- NEUE FELDER MAPPING ---
                // Durch den Regex oben heißen die Keys im Array z.B. article_readership
                $adValue = isset($item['article_advertising_value']) ? (int) $item['article_advertising_value'] : 0;
                $reach = isset($item['article_readership']) ? (int) $item['article_readership'] : 0;

                // MAPPING: Issue -> publication_id (wie angefordert)
                $issue = $item['article_issue'] ?? '';

                $currentClippingData = [
                    'title' => $itemTitle,
                    'uid' => 0,
                    'files' => [],
                    'link' => $item['link'] ?? '',
                    'pubDate' => $pubDateString,
                    'medium' => $itemAuthor,
                ];

                $reportRecord = [
                    'pid' => $storagePid,
                    'type' => 'clipping',
                    'status' => 'clipped',
                    'title' => $itemTitle,
                    'subtitle' => $cleanSubtitle,
                    'medium' => $itemAuthor,

                    // KPIs & Metadaten
                    'ad_value' => $adValue,
                    'reach' => $reach,
                    'page_number' => $item['article_page'] ?? '',
                    'department' => $item['article_ressort'] ?? '',
                    'publication_id' => $issue, // Mapping issue -> publication_id
                    'media_type' => $item['publication_type'] ?? '',
                    'publication_frequency' => $item['publication_release_frequency'] ?? '',

                    'content' => $cleanContent,
                    'apa_guid' => $itemGuid,
                    'apa_link' => $item['link'] ?? '',
                    'data' => $item, // Als JSON speichern

                    'client' => $clientId,
                    'campaign' => $campaignId,
                    'clippingroute' => $clippingrouteId,
                    'date' => $pubDateTimestamp,
                ];

                $dataMapStep1 = [
                    'tx_publicrelations_domain_model_report' => [$tempReportId => $reportRecord]
                ];

                $this->dataHandler->start($dataMapStep1, []);
                $this->dataHandler->process_datamap();

                if (!empty($this->dataHandler->errorLog)) {
                    $this->logger->error('FEHLER beim Speichern von Report GUID ' . $itemGuid, ['errors' => $this->dataHandler->errorLog]);
                    $this->dataHandler->clear_cacheCmd("All");
                    continue;
                }

                // === 5. SCHRITT 2: ECHTE UID HOLEN UND KINDER ERSTELLEN ===
                $newReportUid = $this->dataHandler->substNEWwithIDs[$tempReportId];
                if (!$newReportUid) {
                    $this->logger->error('FEHLER: Konnte neue Report-UID nicht abrufen für GUID ' . $itemGuid);
                    $this->dataHandler->clear_cacheCmd("All");
                    continue;
                }

                // === NEU: UID für E-Mail-Report speichern ===
                $currentClippingData['uid'] = $newReportUid;
                // === ENDE NEU ===

                $this->dataHandler->clear_cacheCmd("All");
                $dataMapStep2 = [
                    'tx_publicrelations_domain_model_link' => [],
                    'sys_file_reference' => []
                ];

                // === HIER DIE NEUE LOGIK: FALL A ODER FALL B ===

                $mediaItems = $item['media_content'] ?? []; // PDFs

                // ⚠️ WICHTIG: Normalisierung für Einzel-Elemente
                // Wenn media_content direkt '@attributes' hat, ist es kein Array von Items, sondern ein Einzel-Item.
                // Wir packen es in [ ... ], damit die foreach-Schleife korrekt funktioniert.
                if (isset($mediaItems['@attributes'])) {
                    $mediaItems = [$mediaItems];
                }

                $avLink = $item['publication_itemOrigin'] ?? null; // AV-Link

                if (!empty($mediaItems)) {
                    $pdfCounter = 1;
                    foreach ($mediaItems as $media) {
                        if (($media['@attributes']['type'] ?? '') === 'application/pdf') {
                            $tempPdfPath = null;
                            $tempThumbPath = null;
                            $thumbUrl = null;

                            try {
                                $mediaTitle = $media['media_title'] ?? '';

                                // ⚠️ FIX: Array zu String Konvertierung für leere XML-Tags
                                // Wenn <media:title type="plain" /> leer ist, liefert der JSON-Parser ein Array mit @attributes.
                                // preg_match stürzt dann ab.
                                if (is_array($mediaTitle)) {
                                    // Versuch, Text-Content zu finden (bei SimpleXML oft nicht direkt im Array, wenn Attribute da sind)
                                    // Im Kontext dieses Feeds bedeutet ein Array hier meist: Es gibt keinen Titel.
                                    $mediaTitle = '';
                                }

                                // Jetzt ist es sicher ein String
                                $pageSuffix = '';
                                if (preg_match('/Seite\s*(\d+)/i', $mediaTitle, $matches))
                                    $pageSuffix = 'Seite-' . $matches[1];
                                else
                                    $pageSuffix = 'File-' . $pdfCounter;
                                $pdfCounter++;

                                $pdfUrl = $media['@attributes']['url'];
                                if (isset($media['media_thumbnail']['@attributes']['url'])) {
                                    $thumbUrl = $media['media_thumbnail']['@attributes']['url'];
                                } elseif (isset($media['media_thumbnail']) && is_string($media['media_thumbnail'])) {
                                    // Fallback, falls URL direkt drin steht (manchmal bei RSS der Fall)
                                    $thumbUrl = $media['media_thumbnail'];
                                }

                                $pdfFileName = $baseFileName . '_' . $pageSuffix . '.pdf';
                                $tempPdfPath = GeneralUtility::tempnam('rss_import_pdf_');
                                $this->requestFactory->request($pdfUrl, 'GET', ['sink' => $tempPdfPath]);
                                $newPdfFile = $currentTargetFolder->addFile($tempPdfPath, $pdfFileName, \TYPO3\CMS\Core\Resource\DuplicationBehavior::RENAME);

                                // FAL-Link für E-Mail-Report speichern ===
                                $currentClippingData['files'][] = $newPdfFile->getPublicUrl();

                                $tempFileRefId = 'NEW_FILEREF_' . uniqid();
                                $dataMapStep2['sys_file_reference'][$tempFileRefId] = [
                                    'uid_local' => $newPdfFile->getUid(),
                                    'uid_foreign' => $newReportUid,
                                    'tablenames' => 'tx_publicrelations_domain_model_report',
                                    'fieldname' => 'files',
                                    'pid' => $storagePid,
                                    'source' => 'APA Defacto',
                                    // Description (Thumb URL) tragen wir gleich unten nach, falls es klappt
                                    'description' => '',
                                    'title' => is_string($mediaTitle) ? $mediaTitle : '', // Sicherheits-Check nutzen
                                ];

                                $thumbPublicUrl = '';
                                if ($thumbUrl) {
                                    $thumbExtension = pathinfo(parse_url($thumbUrl, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpg';
                                    $thumbFileName = $baseFileName . '_' . $pageSuffix . '_thumb.' . $thumbExtension;
                                    $tempThumbPath = GeneralUtility::tempnam('rss_import_thumb_');
                                    $this->requestFactory->request($thumbUrl, 'GET', ['sink' => $tempThumbPath]);
                                    $newThumbFile = $currentTargetFolder->addFile($tempThumbPath, $thumbFileName, \TYPO3\CMS\Core\Resource\DuplicationBehavior::RENAME);
                                    $thumbPublicUrl = $newThumbFile->getPublicUrl();

                                    // URL nachträglich ins Array schreiben
                                    $dataMapStep2['sys_file_reference'][$tempFileRefId]['description'] = $thumbPublicUrl;
                                }

                            } catch (\Exception $e) {
                                $this->logger->warning('FEHLER bei Datei-Download für Report UID ' . $newReportUid, ['exception' => $e]);
                            } finally {
                                if ($tempPdfPath && file_exists($tempPdfPath))
                                    GeneralUtility::unlink_tempfile($tempPdfPath);
                                if ($tempThumbPath && file_exists($tempThumbPath))
                                    GeneralUtility::unlink_tempfile($tempThumbPath);
                            }
                        }
                    }

                    // === NEUER FALLBACK: Wenn in media_content kein PDF war, aber ressource_pdflink existiert ===
                    $fallbackPdfLink = $item['ressource_pdflink'] ?? null;

                    // Prüfen: Haben wir noch keine Files gesammelt? Und gibt es den Link?
                    if (empty($currentClippingData['files']) && !empty($fallbackPdfLink)) {
                        try {
                            $pdfFileName = $baseFileName . '_Fallback.pdf';
                            $tempPdfPath = GeneralUtility::tempnam('rss_import_fallback_');

                            // Download
                            $this->requestFactory->request($fallbackPdfLink, 'GET', ['sink' => $tempPdfPath]);
                            $newPdfFile = $currentTargetFolder->addFile($tempPdfPath, $pdfFileName, \TYPO3\CMS\Core\Resource\DuplicationBehavior::RENAME);

                            // Ins Array für E-Mail
                            $currentClippingData['files'][] = $newPdfFile->getPublicUrl();

                            // ⚠️ FIX VOM VORHERIGEN SCHRITT (Sofort zuordnen)
                            $tempFileRefId = 'NEW_FILEREF_' . uniqid();
                            $dataMapStep2['sys_file_reference'][$tempFileRefId] = [
                                'uid_local' => $newPdfFile->getUid(),
                                'uid_foreign' => $newReportUid,
                                'tablenames' => 'tx_publicrelations_domain_model_report',
                                'fieldname' => 'files',
                                'pid' => $storagePid,
                                'source' => 'APA Defacto Fallback',
                                'description' => '', // Fallback hat meist kein Thumb
                                'title' => $itemTitle, // Nehmen wir den Artikeltitel
                            ];

                        } catch (\Exception $e) {
                            $this->logger->warning('FEHLER bei Fallback-PDF Download für Report UID ' . $newReportUid, ['exception' => $e]);
                        } finally {
                            if (isset($tempPdfPath) && file_exists($tempPdfPath)) {
                                GeneralUtility::unlink_tempfile($tempPdfPath);
                            }
                        }
                    }
                } elseif (!empty($avLink)) {
                    // --- FALL B: AV (publication_itemOrigin ist vorhanden) ---
                    $tempLinkId = 'NEW_LINK_' . uniqid();
                    $dataMapStep2['tx_publicrelations_domain_model_link'][$tempLinkId] = [
                        'pid' => $storagePid,
                        'url' => $avLink,
                        'title' => $itemTitle,
                        'report' => $newReportUid
                    ];
                }

                // === 6. DataHandler (Schritt 2) ausführen ===
                if (!empty($dataMapStep2['tx_publicrelations_domain_model_link']) || !empty($dataMapStep2['sys_file_reference'])) {
                    $this->dataHandler->start($dataMapStep2, []);
                    $this->dataHandler->process_datamap();

                    if (!empty($this->dataHandler->errorLog)) {
                        $this->logger->error('FEHLER beim Speichern von Links/Files für Report UID ' . $newReportUid, ['errors' => $this->dataHandler->errorLog]);
                    }
                }

                $this->dataHandler->clear_cacheCmd("All");

                if ($matchedRoute && $matchedRoute->isSendImmediate()) {
                    // Merke dir diese Route für den Auto-Versand
                    $routesToNotify[$matchedRoute->getUid()] = true;
                }

                // === Fertiges Clipping zum E-Mail-Report hinzufügen ===
                $importReportData[$reportKey]['clippings'][] = $currentClippingData;
                $totalImportCount++;
                // === ENDE NEU ===

            } // Ende der inneren Schleife (pro Route)
        } // Ende der äußeren Schleife (pro Item)

        // === 7. ZWEI GETRENNTE SCHRITTE (Auto-Send UND Report) ===
        $sendImmediately = (bool) $this->send_immediately;
        $reportReceiver = (string) $this->report_receiver;
        $routeUidsToNotify = array_keys($routesToNotify);

        // --- SCHRITT A: Automatische Kunden-Verteilung (Wenn aktiv) ---
        if ($sendImmediately === true && !empty($routeUidsToNotify)) {
            $this->logger->info('Erstelle Verteilungs-Job für ' . count($routeUidsToNotify) . ' Clipping-Routen (SendImmediate).');
            try {
                $context = [
                    'context' => 'Automatischer Clipping-Versand (' . date('d.m.Y H:i') . ')',
                    'sender_profile' => 1,
                    'scheduled_at' => time(),
                    'dataSource' => [
                        'uids' => $routeUidsToNotify,
                        'function' => 'send_clipping',
                        'dataResolverClass' => ReportDataResolver::class,
                    ],
                    'subject' => 'Neue Clippings aus der Medienbeobachtung',
                    'template_name' => 'Clippingservice',
                    'template_layout' => 'AC_Clippingservice',
                    'report' => ['job_title' => 'Clipping-Versand: ' . count($routeUidsToNotify) . ' Routen']
                ];
                $this->distributionService->send($context);
            } catch (\Exception $e) {
                $this->logger->error('FEHLER: Konnte ac_distribution Job nicht erstellen.', ['exception' => $e]);
            }
        }

        // --- SCHRITT B: Admin-Report per E-Mail (IMMER, wenn Clippings da sind) ---
        if ($totalImportCount > 0 && !empty($reportReceiver)) {
            $this->logger->info('Sende Admin-Report für ' . $totalImportCount . ' neue Clippings an ' . $reportReceiver);
            // Wir übergeben $sendImmediately (das globale Flag), damit die Mail-Funktion weiß,
            // welche Clippings oben (manuell) und welche unten (automatisch erledigt) angezeigt werden sollen.
            $this->sendEmailReport($importReportData, $reportReceiver, $totalImportCount, $sendImmediately);

        } elseif ($totalImportCount > 0) {
            $this->logger->warning('Clippings wurden importiert, aber kein Report-Empfänger definiert.');
        } else {
            $this->logger->info('Keine neuen Clippings gefunden.');
        }

        $this->logger->info('RSS Importer abgeschlossen');

        return true;
    }

    /**
     * NEU: Baut und sendet den überarbeiteten HTML-Report.
     * Berücksichtigt jetzt den globalen $globalSendActive-Schalter.
     *
     * @param array $importReportData
     * @param string $reportReceiver
     * @param int $totalImportCount
     * @param bool $globalSendActive Der globale Schalter (aus $this->send_immediately)
     */
    private function sendEmailReport(array $importReportData, string $reportReceiver, int $totalImportCount, bool $globalSendActive): void
    {
        // 1. Daten vorsortieren in zwei "Buckets"
        $reportsManualAction = []; // Das, was du tun musst
        $reportsAutomaticSent = []; // Das, was "erledigt" ist

        foreach ($importReportData as $keyword => $data) {

            // Die Route will automatisch senden
            $routeWantsAutoSend = $data['sendImmediate'];

            // ECHTE PRÜFUNG: Wird NUR gesendet, wenn BEIDES aktiv ist
            $willBeSentAutomatically = ($globalSendActive && $routeWantsAutoSend);

            if ($willBeSentAutomatically) {
                // Automatisch -> kommt in den "Erledigt"-Stapel (unten)
                $reportsAutomaticSent[$keyword] = $data;
            } else {
                // Manuell -> kommt in den "Aktion"-Stapel (oben)
                $reportsManualAction[$keyword] = $data;
            }
        }

        // 2. HTML bauen
        $html = '<h1>RSS-Import Report (' . date('d.m.Y H:i') . ')</h1>';
        $html .= '<p>' . $totalImportCount . ' neue Clippings importiert.</p>';

        if ($globalSendActive === false && !empty($reportsAutomaticSent)) {
            $html .= '<p style="background-color: #fff8e1; border: 1px solid #ffc107; padding: 10px;"><strong>Hinweis:</strong> Der automatische Versand ist global deaktiviert. Alle Clippings werden als "manuell" behandelt.</p>';
        }

        // === SEKTION 1 (OBEN): Manuell / Aktion erforderlich ===
        if (!empty($reportsManualAction)) {
            $html .= '<h2 style="margin-top: 20px; color: red; border-bottom: 2px solid red; padding-bottom: 5px;">!! Aktion erforderlich (Manuelle Clippings) !!</h2>';
            $html .= '<p>Diese Clippings wurden importiert, aber NICHT für den automatischen Versand vorgemerkt und müssen manuell geprüft werden.</p>';
            // Helfer-Funktion aufrufen (false = "ist nicht automatisch")
            $html .= $this->buildHtmlForReportSection($reportsManualAction, false);
        }

        // === SEKTION 2 (UNTEN): Automatisch versendet (Protokoll) ===
        if (!empty($reportsAutomaticSent)) {
            $html .= '<h2 style="margin-top: 30px; border-bottom: 2px solid #ccc; padding-bottom: 5px;">Automatisch versendet (Protokoll)</h2>';
            $html .= '<p>Diese Clippings wurden importiert und werden automatisch über ac_distribution versendet.</p>';
            // Helfer-Funktion aufrufen (true = "ist automatisch")
            $html .= $this->buildHtmlForReportSection($reportsAutomaticSent, true);
        }

        if ($totalImportCount === 0) {
            $html .= '<p>Keine neuen Clippings gefunden.</p>';
        }

        try {
            // 3. Mail-Objekt bauen (Rest bleibt gleich)
            $recipientArray = GeneralUtility::trimExplode(',', $reportReceiver, true);
            if (empty($recipientArray)) {
                throw new \RuntimeException('Kein gültiger Report-Empfänger (reportReceiver) gefunden.');
            }

            $mail = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Mail\MailMessage::class);
            $mail->to(...$recipientArray);
            $mail->subject('RSS-Import Report: ' . $totalImportCount . ' neue Clippings');
            $mail->html($html);

            // 4. Sender-Profil holen (Rest bleibt gleich)
            // ... (Rest der Funktion ist unverändert) ...
            $systemSenderProfile = $this->smtpService->getSenderProfile(['sender_profile' => 1]);
            if ($systemSenderProfile === null) {
                throw new \RuntimeException('System-Sender-Profil (UID 1) für Report nicht gefunden.');
            }

            $fromEmail = $this->smtpService->getSenderProfileConfigValue($systemSenderProfile, 'from_email');
            $fromName = $this->smtpService->getSenderProfileConfigValue($systemSenderProfile, 'from_name');
            $mail->from(new \Symfony\Component\Mime\Address($fromEmail, $fromName ?? 'TYPO3 System'));

            // 5. Senden
            $this->smtpService->sendMail($mail, $systemSenderProfile);

        } catch (\Exception $e) {
            $this->logger->error('Konnte E-Mail-Report nicht senden: ' . $e->getMessage());
        }
    }

    /**
     * HILFSFUNKTION
     *
     * @param array $reportData Der Daten-Bucket (entweder manuell oder auto)
     * @param bool $isAutoSendSection Wenn true, wird es grün. Wenn false, wird es rot.
     */
    private function buildHtmlForReportSection(array $reportData, bool $isAutoSendSection): string
    {
        $html = '';
        foreach ($reportData as $keyword => $data) {

            $clippings = $data['clippings'];

            $statusText = $isAutoSendSection ? 'Automatisch via Job – Hinweis: wird beim nächsten Durchlauf verschickt' : 'Manuell / Aktion erforderlich';

            // HIER ist die Anpassung für die Farben:
            // Rot für Manuell, Grün für Automatisch
            $rowStyle = $isAutoSendSection
                ? 'style="background-color: #e6fffa; border-left: 3px solid #00aa00;"' // Grünlich
                : 'style="background-color: #ffeaea; border-left: 3px solid #e00;"';  // Rötlich

            $html .= '<h3 style="margin-top: 20px; background-color: #f9f9f9; padding: 5px; border-top: 1px solid #ddd;">';
            $html .= 'Keyword: ' . htmlspecialchars($keyword) . ' (' . count($clippings) . ' Clippings)';
            $html .= '</h3>';

            $html .= '<table border="1" cellpadding="5" cellspacing="0" style="border-collapse: collapse; width: 100%; font-size: 12px;">';
            $html .= '<thead><tr style="background-color: #eee;">';
            $html .= '<th style="width: 60px;">UID</th>';
            $html .= '<th style="width: 100px;">Datum</th>';
            $html .= '<th style="width: 150px;">Medium</th>';
            $html .= '<th>Titel (mit APA-Link)</th>';
            $html .= '<th style="width: 180px;">Status</th>';
            $html .= '</tr></thead><tbody>';

            foreach ($clippings as $clip) {
                $html .= '<tr ' . $rowStyle . '>';
                $html .= '<td>' . (int) $clip['uid'] . '</td>';
                $html .= '<td>' . htmlspecialchars($clip['pubDate']) . '</td>';
                $html .= '<td>' . htmlspecialchars($clip['medium']) . '</td>';
                $html .= '<td><a href="' . htmlspecialchars($clip['link']) . '" target="_blank">' . htmlspecialchars($clip['title']) . '</a></td>';
                $html .= '<td>' . htmlspecialchars($statusText) . '</td>';
                $html .= '</tr>';
            }
            $html .= '</tbody></table>';
        }
        return $html;
    }
}