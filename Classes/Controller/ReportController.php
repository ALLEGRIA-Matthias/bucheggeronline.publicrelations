<?php
declare(strict_types=1);

namespace BucheggerOnline\Publicrelations\Controller;

use Psr\Http\Message\ResponseInterface;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Page\AssetCollector;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\DataHandling\DataHandler;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;

use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;

use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Routing\UriBuilder as BackendUriBuilder;

use BucheggerOnline\Publicrelations\Domain\Repository\ReportRepository;
use BucheggerOnline\Publicrelations\Domain\Repository\ClippingRouteRepository;

class ReportController extends ActionController
{
    // Inject Repositories and ModuleTemplateFactory
    public function __construct(
        private readonly ModuleTemplateFactory $moduleTemplateFactory,
        private readonly IconFactory $iconFactory,
        private readonly AssetCollector $assetCollector,
        private readonly BackendUriBuilder $backendUriBuilder,
        private readonly PersistenceManagerInterface $persistenceManager,
        private readonly DataHandler $dataHandler,

        private readonly ReportRepository $reportRepository,
        private readonly ClippingRouteRepository $clippingRouteRepository
    ) {
    }

    /**
     * Renders the initial view for the ClippingRoutes list.
     */
    public function clippingRoutesAction(string $query = ''): ResponseInterface
    {
        // Prepare the view, add JS module, etc.
        $view = $this->moduleTemplateFactory->create($this->request);
        $this->registerModuleMenuInDocHeader($view, 'clippingRoutes');

        $docHeader = $view->getDocHeaderComponent();
        $buttonBar = $docHeader->getButtonBar();

        // 1. PID holen (wir nehmen die aktuelle Seiten-ID oder -1)
        $pid = $this->request->getQueryParams()['id'] ?? -1;

        // 2. URI für "Neuen Datensatz" erstellen
        $newRecordUrl = (string) $this->backendUriBuilder->buildUriFromRoute('record_edit', [
            'edit' => [
                'tx_publicrelations_domain_model_clippingroute' => [ // Tabelle
                    $pid => 'new' // 'new' auf der aktuellen PID
                ]
            ],
            // Wichtig: returnUrl setzen, damit wir hierher zurückkommen
            'returnUrl' => (string) $this->backendUriBuilder->buildUriFromRoute('allegria_reports_clippingroutes')
        ]);

        // 3. Einen normalen Link-Button erstellen
        $newButton = $buttonBar->makeLinkButton()
            ->setHref($newRecordUrl)
            ->setTitle('Neue Clipping Route anlegen')
            ->setShowLabelText('Neue Clipping Route anlegen')
            ->setIcon($this->iconFactory->getIcon('actions-plus', IconSize::SMALL))
            ->setClasses('btn-success');

        $buttonBar->addButton($newButton, ButtonBar::BUTTON_POSITION_LEFT, 1);

        $this->assetCollector->addStyleSheet('icons-bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css?v=1.0');
        $this->assetCollector->addStyleSheet('icons-font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css');
        $this->assetCollector->addStyleSheet('gridjs', 'EXT:ac_base/Resources/Public/Libs/grid-js/gridjs.css');
        $this->assetCollector->addStyleSheet('backend-css', 'EXT:ac_base/Resources/Public/Css/styles.css');

        $this->assetCollector->addJavaScriptModule('@typo3/core/ajax/ajax-request.js');
        $this->assetCollector->addJavaScriptModule('@ac/libs/grid-js/gridjs.js');
        $this->assetCollector->addJavaScriptModule('@allegria/publicrelations/library/ClippingRoutes.js');

        $view->assign('initialQuery', $query);

        return $view->renderResponse('Report/ClippingRoutes'); // Renders Jobs.html
    }

    /**
     * Renders the initial view for the Reports list. (NEU)
     */
    public function reportsAction(string $query = ''): ResponseInterface
    {
        $view = $this->moduleTemplateFactory->create($this->request);
        $this->registerModuleMenuInDocHeader($view, 'reports');

        $docHeader = $view->getDocHeaderComponent();
        $buttonBar = $docHeader->getButtonBar();

        // 1. PID holen (wir nehmen die aktuelle Seiten-ID oder -1)
        $pid = $this->request->getQueryParams()['id'] ?? -1;

        // 2. URI für "Neuen Datensatz" erstellen
        $newRecordUrl = (string) $this->backendUriBuilder->buildUriFromRoute('record_edit', [
            'edit' => [
                'tx_publicrelations_domain_model_report' => [ // Tabelle
                    $pid => 'new' // 'new' auf der aktuellen PID
                ]
            ],
            // Wichtig: returnUrl setzen, damit wir hierher zurückkommen
            'returnUrl' => (string) $this->backendUriBuilder->buildUriFromRoute('allegria_reports_list')
        ]);

        // 3. Einen normalen Link-Button erstellen
        $newButton = $buttonBar->makeLinkButton()
            ->setHref($newRecordUrl)
            ->setTitle('Neuen Report anlegen')
            ->setShowLabelText('Neuen Report anlegen')
            ->setIcon($this->iconFactory->getIcon('actions-plus', IconSize::SMALL))
            ->setClasses('btn-success');

        $buttonBar->addButton($newButton, ButtonBar::BUTTON_POSITION_LEFT, 1);

        $this->assetCollector->addStyleSheet('icons-bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css?v=1.0');
        $this->assetCollector->addStyleSheet('gridjs', 'EXT:ac_base/Resources/Public/Libs/grid-js/gridjs.css');
        $this->assetCollector->addStyleSheet('backend-css', 'EXT:ac_base/Resources/Public/Css/styles.css');

        $this->assetCollector->addJavaScriptModule('@typo3/core/ajax/ajax-request.js');
        $this->assetCollector->addJavaScriptModule('@ac/libs/grid-js/gridjs.js');
        $this->assetCollector->addJavaScriptModule('@allegria/publicrelations/library/Reports.js');

        $view->assign('initialQuery', $query);

        return $view->renderResponse('Report/Reports');
    }

    /**
     * Aktion zur Migration: Füllt das 'medium'-Feld aus 'data[author]'.
     */
    public function migrateClippingsAction(): ResponseInterface
    {
        $migratedReportCount = 0;
        $migratedAvLinkCount = 0;
        $errorCount = 0;

        // 1. Nur die relevanten Datensätze holen
        $reportsToMigrate = $this->reportRepository->findAllClippingsForMigration();

        $updateDataMap = [];
        $tableNameReport = 'tx_publicrelations_domain_model_report';
        $tableNameLink = 'tx_publicrelations_domain_model_link';

        foreach ($reportsToMigrate as $report) {
            $uid = (int) $report['uid'];
            $pid = (int) $report['pid'];
            $jsonData = (string) $report['data'];

            if (empty($jsonData)) {
                continue;
            }

            try {
                $data = json_decode($jsonData, true, 512, JSON_THROW_ON_ERROR);

                // 1. (Step 2) 'apa_link' (aus 'link') setzen
                $apaLink = $data['link'] ?? null;
                if (!empty($apaLink) && is_string($apaLink)) {
                    $updateDataMap[$tableNameReport][$uid]['apa_link'] = $apaLink;
                }

                // 2. (Step 3) 'publication_itemOrigin' (AV-Link) als neuen Link anlegen
                $avLink = $data['publication_itemOrigin'] ?? null;
                if (!empty($avLink) && is_string($avLink)) {
                    $reportTitle = $data['title'] ?? 'AV-Beitrag';
                    $tempLinkId = 'NEW_LINK_' . $uid;

                    $updateDataMap[$tableNameLink][$tempLinkId] = [
                        'pid' => $pid, // Wichtig: Im selben Ordner wie der Report anlegen
                        'url' => $avLink,
                        'title' => $reportTitle,
                        'report' => $uid
                    ];
                    $migratedAvLinkCount++;
                }

                $migratedReportCount++;

            } catch (\JsonException $e) {
                $errorCount++;
            }
        }

        // 3. DataHandler für Updates/Inserts ausführen
        if (!empty($updateDataMap)) {
            $this->dataHandler->start($updateDataMap, []);
            $this->dataHandler->process_datamap();
            $this->dataHandler->clear_cacheCmd("All");
        }

        // 4. Feedback geben
        $this->addFlashMessage(
            "Migration abgeschlossen: " . $deletedLinkCount . " alte Links gelöscht. " .
            $migratedReportCount . " Reports geprüft. " .
            $migratedAvLinkCount . " AV-Links neu erstellt. (" . $errorCount . " JSON-Fehler).",
            'Migration abgeschlossen',
            ContextualFeedbackSeverity::OK
        );

        return $this->redirect('clippingRoutes');
    }

    /**
     * NEU: Migriert die alten datetime-Strings in int-Timestamps
     */
    public function migrateDatesAction(): ResponseInterface
    {
        $reportsToMigrate = $this->reportRepository->findAllClippingsForMigration();

        if (empty($reportsToMigrate)) {
            $this->addFlashMessage('Keine Einträge zur Datums-Migration gefunden (oder bereits migriert).', 'Info', ContextualFeedbackSeverity::INFO);
            return $this->redirect('reports');
        }

        $dataMap = [];
        $migratedCount = 0;

        foreach ($reportsToMigrate as $report) {
            try {
                // 2. Konvertiere den DB-String in einen Timestamp
                $dateTime = new \DateTime($report['dateold']);
                $timestamp = $dateTime->getTimestamp();

                // 3. DataMap für DataHandler vorbereiten
                $dataMap['tx_publicrelations_domain_model_report'][(int) $report['uid']] = [
                    'date' => (string) $timestamp
                ];
                $migratedCount++;
            } catch (\Exception $e) {
                // Fängt kaputte Datums-Strings ab
            }
        }

        // 4. Per DataHandler speichern
        if (!empty($dataMap)) {
            // Sicherheitshalber (wie im DataHandlerService)
            $this->dataHandler->bypassAccessCheckForRecords = true;

            $this->dataHandler->start($dataMap, []);
            $this->dataHandler->process_datamap();

            if (!empty($this->dataHandler->errorLog)) {
                \TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump($this->dataHandler->errorLog);
                die();
            }
        }

        $this->addFlashMessage($migratedCount . ' Report-Datensätze wurden erfolgreich auf Timestamps migriert.', 'Migration erfolgreich', ContextualFeedbackSeverity::OK);
        return $this->redirect('reports');
    }

    public function registerModuleMenuInDocHeader(ModuleTemplate $moduleTemplate, string $activeIdentifier): void
    {
        // 1. Hole die MenuRegistry aus dem DocHeader
        $docHeader = $moduleTemplate->getDocHeaderComponent();
        $menuRegistry = $docHeader->getMenuRegistry();

        // 2. Erstelle ein neues Menü-Objekt
        $menu = $menuRegistry->makeMenu();
        $menu->setIdentifier('publicrelations');
        $menu->setLabel('Navigation'); // Der sichtbare Name des Dropdown-Buttons

        // 3. Füge die einzelnen Dropdown-Einträge hinzu
        $reportsUri = $this->backendUriBuilder->buildUriFromRoute('allegria_reports_list');
        $reportsItem = $menu->makeMenuItem()
            ->setTitle('Report-Center')
            ->setHref($reportsUri);
        if ($activeIdentifier === 'reports') {
            $reportsItem->setActive(true);
        }
        $menu->addMenuItem($reportsItem);

        $clippingRoutesUri = $this->backendUriBuilder->buildUriFromRoute('allegria_reports_clippingroutes');
        $clippingRoutesItem = $menu->makeMenuItem()
            ->setTitle('Clipping Routen')
            ->setHref($clippingRoutesUri);
        if ($activeIdentifier === 'clippingRoutes') {
            $clippingRoutesItem->setActive(true);
        }
        $menu->addMenuItem($clippingRoutesItem);

        // Hier können später weitere Einträge hinzugefügt werden

        // 4. Füge das fertige Menü zur Registry hinzu
        $menuRegistry->addMenu($menu);
    }
}