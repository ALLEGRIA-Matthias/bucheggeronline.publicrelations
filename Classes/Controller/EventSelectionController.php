<?php

declare(strict_types=1);

namespace BucheggerOnline\Publicrelations\Controller;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Page\AssetCollector;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use BucheggerOnline\Publicrelations\Service\AccreditationSelectionService;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Backend\Routing\UriBuilder as BackendUriBuilder;

class EventSelectionController extends ActionController
{
    public function __construct(
        private readonly ModuleTemplateFactory $moduleTemplateFactory,
        private readonly AssetCollector $assetCollector,
        private readonly AccreditationSelectionService $selectionService,
        private readonly BackendUriBuilder $backendUriBuilder
    ) {
    }

    /**
     * Schritt 1: Das Suchformular (Event, Status, Tickets etc.)
     */
    public function wizardAction(
        array $finishConfiguration = [],
        array $allowedClientUids = []
    ): ResponseInterface {

        // RESET-LOGIK: Wenn keine Konfiguration vorhanden ist, kehren wir zur Haupt-Action zurück
        if (empty($finishConfiguration)) {
            return $this->redirectToContextMainAction();
        }

        $view = $this->moduleTemplateFactory->create($this->request);
        $this->loadAssets();
        $this->assetCollector->addJavaScript('legacy-publicrelations-scripts', 'EXT:publicrelations/Resources/Public/JavaScript/scripts.js', ['type' => 'module']);
        $this->assetCollector->addJavaScriptModule('@allegria/publicrelations/EventSelectionWizard.js');

        $view->assignMultiple([
            'finishConfiguration' => $finishConfiguration,
            'allowedClientUids' => $allowedClientUids,
            'moduleTitle' => 'Empfänger aus Events wählen'
        ]);

        return $view->renderResponse('Selection/EventWizard');
    }

    /**
     * Zentrale Methode für den Reset des Wizards.
     * Ermittelt anhand des aktuellen Modul-Identifiers die Ziel-Action.
     */
    private function redirectToContextMainAction(): ResponseInterface
    {
        $module = $this->request->getAttribute('module');
        $identifier = $module?->getIdentifier() ?? '';

        $route = 'contacts_list';
        // Wir übergeben keine Controller/Action Parameter im Reset-Fall.
        // Die Route selbst ist in der Modules.php bereits fest mit dem Default-Controller verknüpft.
        // Das verhindert, dass Extbase die Parameter gegen das falsche Modul prüft.
        $params = [];

        // Case Mailer
        if ($identifier === 'ac_mailer_event_selection') {
            $route = 'ac_mailer_show';
        }
        // Case Eventcenter
        elseif ($identifier === 'allegria_eventcenter_event_selection') {
            $route = 'allegria_eventcenter';
        }

        try {
            $uri = $this->backendUriBuilder->buildUriFromRoute($route, $params);
            return new RedirectResponse((string) $uri);
        } catch (\Exception $e) {
            // Letzter Fallback zur Standardliste
            return new RedirectResponse((string) $this->backendUriBuilder->buildUriFromRoute('contacts_list'));
        }
    }

    /**
     * Schritt 2: Suche ausführen und Vorschau anzeigen
     */
    public function summaryAction(
        array $filters = [],
        array $finishConfiguration = [],
        array $allowedClientUids = []
    ): ResponseInterface {

        $view = $this->moduleTemplateFactory->create($this->request);
        $this->loadAssets(); // Reuse JS/CSS for Table & UI
        $this->assetCollector->addJavaScriptModule('@ac/contacts/SelectionSummary.js'); // Reuse Table Logic

        // Logik an Service delegieren (findFiltered + Kontakt-Extraktion)
        $dupConfig = $finishConfiguration['duplicateCheck'] ?? [];

        $resolved = $this->selectionService->resolveSelection(
            $filters,
            $dupConfig['table'] ?? null,
            $dupConfig['field'] ?? null,
            $dupConfig['scope'] ?? [],
            $allowedClientUids
        );

        $view->assignMultiple([
            'contacts' => $resolved['contacts'],
            'stats' => $resolved['stats'],
            'filters' => $filters, // Für die Anzeige im Header oder "Zurück" State
            'finishConfiguration' => $finishConfiguration,
            'allowedClientUids' => $allowedClientUids,
            'selectionJson' => json_encode($filters), // State für "Zurück"
            'moduleTitle' => 'Auswahl bestätigen'
        ]);

        return $view->renderResponse('Selection/EventSummary');
    }

    /**
     * Schritt 3: Transfer (Identisch zu AcContacts, aber eigener Namespace)
     */
    public function transferAction(string $selectionData, string $finishConfiguration): ResponseInterface
    {
        $decodedConfig = json_decode($finishConfiguration, true);

        if (!is_array($decodedConfig) || empty($selectionData) || $selectionData === '[]') {
            $this->addFlashMessage('Fehler bei der Übertragung.', 'Error', ContextualFeedbackSeverity::ERROR);
            return $this->redirect('wizard', null, null, ['finishConfiguration' => $decodedConfig]);
        }

        // Save to Session
        $transferId = StringUtility::getUniqueId('transfer_pr_');
        $GLOBALS['BE_USER']->setAndSaveSessionData($transferId, $selectionData);

        // Build Redirect
        $arguments = $decodedConfig['redirectArguments'] ?? [];
        $arguments['selectionTransferId'] = $transferId;
        if (!empty($decodedConfig['targetAction']))
            $arguments['action'] = $decodedConfig['targetAction'];
        if (!empty($decodedConfig['targetController']))
            $arguments['controller'] = $decodedConfig['targetController'];

        $uri = $this->backendUriBuilder->buildUriFromRoute($decodedConfig['redirectRoute'] ?? 'ac_mailer_show', $arguments);
        return new RedirectResponse((string) $uri);
    }

    private function loadAssets(): void
    {
        // Wir nutzen die gleichen Assets wie AcContacts für Konsistenz
        $this->assetCollector->addStyleSheet('icons-bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css');
        $this->assetCollector->addStyleSheet('tomselect', 'EXT:ac_base/Resources/Public/Libs/tom-select/tom-select.bootstrap5.css');
        $this->assetCollector->addStyleSheet('backend-css', 'EXT:ac_base/Resources/Public/Css/styles.css');

        // JS Modules
        $this->assetCollector->addJavaScriptModule('@ac/base/TomSelectEngine.js');
    }
}