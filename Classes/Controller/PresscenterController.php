<?php
declare(strict_types=1);

namespace BucheggerOnline\Publicrelations\Controller;

use Psr\Http\Message\ResponseInterface;

use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use BucheggerOnline\Publicrelations\Domain\Repository\EventRepository;
use BucheggerOnline\Publicrelations\Domain\Repository\NewsRepository;
use BucheggerOnline\Publicrelations\Domain\Repository\ClientRepository;

class PresscenterController extends AbstractPublicrelationsController
{
    public function __construct(
        private readonly EventRepository $eventRepository,
        private readonly NewsRepository $newsRepository,
        private readonly ClientRepository $clientRepository
    ) {
    }

    public function overviewAction(): ResponseInterface
    {
        $pressnews = $this->newsRepository->findLatest(5);
        $events = $this->eventRepository->findUpcoming(5, 'press');

        $this->view->assignMultiple([
            'pressnews' => $pressnews,
            'events' => $events,
        ]);

        return $this->frontendResponse();
    }

    public function homeAction(): ResponseInterface
    {
        $news = $this->newsRepository->findLatest(1);
        $event = $this->eventRepository->findUpcoming(1, 'press', 1);
        $topClients = $this->clientRepository->findTops();

        $this->view->assignMultiple([
            'news' => $news[0] ?? null,
            'event' => $event[0] ?? null,
            'clients' => $topClients,
        ]);

        return $this->frontendResponse();
    }
}
