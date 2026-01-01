<?php
namespace BucheggerOnline\Publicrelations\Controller;

use Psr\Http\Message\ResponseInterface;

use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use BucheggerOnline\Publicrelations\Domain\Repository\ClientRepository;
use BucheggerOnline\Publicrelations\Domain\Repository\EventRepository;
use BucheggerOnline\Publicrelations\Domain\Repository\SysCategoryRepository;
use BucheggerOnline\Publicrelations\Domain\Model\Client;
use BucheggerOnline\Publicrelations\Utility\SEOProvider;
use BucheggerOnline\Publicrelations\Utility\GeneralFunctions;
use BucheggerOnline\Publicrelations\Domain\Model\Dto\EmConfiguration;

class ClientController extends AbstractPublicrelationsController
{
    public function __construct(
        private readonly ClientRepository $clientRepository,
        private readonly EventRepository $eventRepository,
        private readonly SysCategoryRepository $sysCategoryRepository,
        private readonly SEOProvider $seoProvider,
        private readonly GeneralFunctions $generalFunctions,
        private readonly EmConfiguration $emConfiguration
    ) {
    }

    public function listAction(): ResponseInterface
    {
        $clients = $this->clientRepository->findCurrent();
        $this->view->assign('clients', $clients);

        return $this->frontendResponse();
    }

    public function backendListAction(): ResponseInterface
    {
        $clients = $this->clientRepository->findAll();
        $this->view->assign('clients', $clients);

        $this->setModuleTitle('Kundenübersicht');
        return $this->backendResponse();
    }

    public function referencesAction(): ResponseInterface
    {
        $clients = $this->clientRepository->findAll();
        $this->view->assign('clients', $clients);

        return $this->frontendResponse();
    }

    public function showAction(?Client $client = null): ResponseInterface
    {
        // 1. Prüfen, ob ein gültiger Client übergeben wurde
        if ($client === null || $client->getUid() === 0 || $client->getUid() === null) {
            $baseUri = $this->generalFunctions->getBaseUri(); 
            return $this->redirectToUri((string)$baseUri);
        }

        $ip = $this->generalFunctions->getIp();

        // Load Metadata
        $this->seoProvider->setClientSEO(
            $client,
            $this->generalFunctions->getBaseUri(),
            $this->generalFunctions->getRequestUrl()
        );

        $this->view->assign('client', $client);

        $schedule = [
            'preview' => $this->eventRepository->findSchedule('client', $client->getUid(), 5),
            'events' => $this->eventRepository->findSchedule('client', $client->getUid())
        ];
        $this->view->assign('schedule', $schedule);

        $mediumTypes = $this->sysCategoryRepository->findByParentUid($this->emConfiguration->getMediumRootUid());
        $this->view->assign('mediumTypes', $mediumTypes);
        $this->view->assign('ip', $ip);

        return $this->frontendResponse();
    }
}

