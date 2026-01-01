<?php

declare(strict_types=1);

namespace BucheggerOnline\Publicrelations\Controller;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\View\ViewFactoryInterface;
use TYPO3\CMS\Core\View\ViewFactoryData;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

abstract class AbstractPublicrelationsController extends ActionController
{
    // 1. Konstante für den Queue Identifier definieren
    // Es ist gut, einen präfixierten oder spezifischen Namen zu wählen.
    protected const MODULE_FLASHMESSAGE_QUEUE_ID = 'allegria.publicrelations.flashMessages';

    protected ?ModuleTemplate $moduleTemplate = null;
    protected string $controllerName = '';
    protected ModuleTemplateFactory $moduleTemplateFactory;
    protected ?FlashMessageService $flashMessageService = null;
    protected int $backendUserUid = 0;
    protected string $backendUserName = '';
    protected string $backendUserEmail = '';

    public function injectModuleTemplateFactory(ModuleTemplateFactory $moduleTemplateFactory): void
    {
        $this->moduleTemplateFactory = $moduleTemplateFactory;
    }

    public function injectFlashMessageService(FlashMessageService $flashMessageService): void
    {
        $this->flashMessageService = $flashMessageService;
    }

    public function initializeAction(): void
    {
        parent::initializeAction();

        if (ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST'])->isBackend()) {
            $this->initializeBackend();
        }

        if ($GLOBALS['BE_USER']) {
            $this->backendUserUid = (int) $GLOBALS['BE_USER']->user['uid'];
            $this->backendUserName = (string) $GLOBALS['BE_USER']->user['realName'];
            $this->backendUserEmail = (string) $GLOBALS['BE_USER']->user['email'];
        }
    }


    /**
     * Gibt die UID des aktuellen Backend-Benutzers zurück.
     */
    protected function getCurrentBackendUserUid(): int
    {
        return $this->backendUserUid;
    }

    /**
     * Gibt den RealName (oder Benutzernamen als Fallback) des aktuellen Backend-Benutzers zurück.
     */
    protected function getCurrentBackendUserName(): string
    {
        return $this->backendUserName;
    }

    /**
     * Gibt den RealName (oder Benutzernamen als Fallback) des aktuellen Backend-Benutzers zurück.
     */
    protected function getCurrentBackendUserEmail(): string
    {
        return $this->backendUserEmail;
    }

    protected function initializeBackend(): void
    {
        $this->controllerName = $this->request->getControllerName();

        // Wichtig: create() mit Request übergeben, damit Links korrekt aufgelöst werden
        $this->moduleTemplate = $this->moduleTemplateFactory->create($this->request);

        $viewFactory = GeneralUtility::makeInstance(ViewFactoryInterface::class);
        $view = $viewFactory->create(new ViewFactoryData(
            ['EXT:publicrelations/Resources/Private/Backend/Templates/'],
            ['EXT:publicrelations/Resources/Private/Backend/Partials/'],
            ['EXT:publicrelations/Resources/Private/Backend/Layouts/'],
            null,
            $this->request
        ));

        $this->view = $view;
        $this->view->setRequest($this->request);

        // Den Queue Identifier der View für Fluid Templates verfügbar machen (optional, aber nützlich)
        $this->view->assign('publicrelationsFlashMessageQueueId', self::MODULE_FLASHMESSAGE_QUEUE_ID);

        $this->addAssets();
    }

    protected function addAssets(): void
    {
        $this->addCssAssets();
        $this->addJsAssets();
        $this->loadJavaScriptModules();
    }

    protected function addCssAssets(): void
    {
        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        $pageRenderer->addCssFile('EXT:publicrelations/Resources/Public/Css/bootstrap_allegria_required.css');
        $pageRenderer->addCssFile('EXT:publicrelations/Resources/Public/JavaScript/tomselect/tom-select.bootstrap5.css');
        $pageRenderer->addCssFile('EXT:allegria_communications/Resources/Public/Css/styles_backend.css');
        $pageRenderer->addCssFile('EXT:allegria_communications/Resources/Public/Css/styles.css');
        $pageRenderer->addCssFile('EXT:publicrelations/Resources/Public/Css/mdb_required.css');
        $pageRenderer->addCssFile('EXT:ac_base/Resources/Public/Css/styles.css');
    }

    protected function addJsAssets(): void
    {
        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        $pageRenderer->addJsFile('EXT:publicrelations/Resources/Public/JavaScript/scripts.js', 'module');
    }

    protected function loadJavaScriptModules(): void
    {
        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        $pageRenderer->loadJavaScriptModule('@typo3/core/ajax/ajax-request.js');
        $pageRenderer->loadJavaScriptModule('@typo3/backend/date-time-picker.js');
        $pageRenderer->loadJavaScriptModule('@typo3/backend/modal.js');
        $pageRenderer->loadJavaScriptModule('@typo3/backend/element/progress-bar-element.js');
        $pageRenderer->loadJavaScriptModule('@typo3/backend/sortable-table.js');
        // $pageRenderer->loadJavaScriptModule('@allegria/tomselect/tomselect.js');
        // $pageRenderer->loadJavaScriptModule('@allegria/publicrelations/scripts.js');
    }

    protected function addCustomAsset(string $path, string $type = 'js'): void
    {
        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);

        switch ($type) {
            case 'js':
                $pageRenderer->addJsFile($path);
                break;
            case 'css':
                $pageRenderer->addCssFile($path);
                break;
            case 'module':
                $pageRenderer->loadJavaScriptModule($path);
                break;
            default:
                throw new \InvalidArgumentException('Unsupported asset type: ' . $type);
        }
    }


    /**
     * Adds a flash message to the extension's specific queue using a string for severity.
     * The message is always stored in the session.
     *
     * @param string $messageBody The message body
     * @param string $messageTitle The message title (optional)
     * @param string $severityString Severity as a string (z.B. 'OK', 'SUCCESS', 'INFO', 'NOTICE', 'WARNING', 'ERROR'). Standard ist 'OK'.
     */
    protected function addModuleFlashMessage(
        string $messageBody,
        string $messageTitle = '',
        string $severityString = 'OK', // Standard auf 'OK' (oft als Erfolg interpretiert)
        bool $storeInSession = true // Standard auf true
    ): void {
        // Konvertiere den Severity-String in die entsprechende TYPO3 Konstante/Enum-Wert
        // Wir verwenden strtoupper, um die Eingabe Case-Insensitiv zu machen
        $severity = match (strtoupper($severityString)) {
            'SUCCESS', 'OK' => ContextualFeedbackSeverity::OK, // OK ist oft grün/Erfolg
            'INFO' => ContextualFeedbackSeverity::INFO,
            'NOTICE' => ContextualFeedbackSeverity::NOTICE,
            'WARNING' => ContextualFeedbackSeverity::WARNING,
            'ERROR' => ContextualFeedbackSeverity::ERROR,
            default => ContextualFeedbackSeverity::OK, // Sicherer Standard, falls ein ungültiger String übergeben wird
        };

        $flashMessage = GeneralUtility::makeInstance(
            FlashMessage::class,
            $messageBody,
            $messageTitle,
            $severity, // setze ContextualFeedbackSeverity
            $storeInSession // storeInSession default true
        );

        // Hole die benutzerdefinierte Queue über die ActionController-Methode
        // self::MODULE_FLASHMESSAGE_QUEUE_ID ist deine definierte Konstante (z.B. 'tx_publicrelations_module_messages')
        $customQueue = $this->getFlashMessageQueue(self::MODULE_FLASHMESSAGE_QUEUE_ID);
        $customQueue->enqueue($flashMessage);
    }

    /**
     * Für Backend-Module
     */
    protected function backendResponse(string $templateName = ''): ResponseInterface
    {
        $templatePath = sprintf(
            '%s/%s',
            $this->controllerName,
            $templateName !== '' ? $templateName : ucfirst($this->request->getControllerActionName())
        );

        if ($this->moduleTemplate !== null && method_exists($this->view, 'getRenderingContext')) {
            $variables = $this->view->getRenderingContext()->getVariableProvider()->getAll();
            $this->moduleTemplate->assignMultiple($variables);
        }

        $this->view->setPartialRootPaths(['EXT:publicrelations/Resources/Private/Backend/Partials/']);
        $this->view->setLayoutRootPaths(['EXT:publicrelations/Resources/Private/Backend/Layouts/']);

        return $this->moduleTemplate?->renderResponse($templatePath);
    }

    /**
     * Für Frontend-Plugins
     */
    protected function frontendResponse(): ResponseInterface
    {
        return new HtmlResponse($this->view->render());
    }

    protected function setModuleTitle(string $title): void
    {
        $this->moduleTemplate?->setTitle($title);
    }
}
