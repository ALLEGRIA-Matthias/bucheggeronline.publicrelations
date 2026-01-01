<?php
declare(strict_types=1);

namespace BucheggerOnline\Publicrelations\Controller;

use Psr\Http\Message\ResponseInterface;

use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use BucheggerOnline\Publicrelations\Domain\Repository\EventRepository;
use BucheggerOnline\Publicrelations\Domain\Repository\AccreditationRepository;

class PressroomController extends AbstractPublicrelationsController
{
    public function __construct(
        private readonly EventRepository $eventRepository,
        private readonly AccreditationRepository $accreditationRepository
    ) {
    }

    public function overviewAction(): ResponseInterface
    {
        $eventOverview = $this->eventRepository->findNextWeeks(2);
        $pendingOverview = $this->accreditationRepository->findAllPending();

        $this->view->assignMultiple([
            'eventOverview' => $eventOverview,
            'pendingOverview' => $pendingOverview,
        ]);

        $this->setModuleTitle('Termincenter Übersicht');
        return $this->backendResponse();
    }
}
