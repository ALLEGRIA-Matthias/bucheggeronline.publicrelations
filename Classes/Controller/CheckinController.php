<?php
namespace BucheggerOnline\Publicrelations\Controller;

use Psr\Http\Message\ResponseInterface;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Connection;

use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;

use BucheggerOnline\Publicrelations\Domain\Model\Dto\EmConfiguration;
use BucheggerOnline\Publicrelations\Utility\LogGenerator;
use BucheggerOnline\Publicrelations\Helper\HiddenObjectHelper;

use BucheggerOnline\Publicrelations\Domain\Repository\EventRepository;
use BucheggerOnline\Publicrelations\Domain\Model\Event;

use BucheggerOnline\Publicrelations\Domain\Repository\AccreditationRepository;
use BucheggerOnline\Publicrelations\Domain\Model\Accreditation;
use BucheggerOnline\Publicrelations\Utility\GeneralFunctions;

/***
 *
 * This file is part of the "Public Relations" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2020 Matthias Buchegger <matthias@buchegger.online>, Multimediaagentur Matthias Buchegger
 *
 ***/
/**
 * EventController
 */
class CheckinController extends AbstractPublicrelationsController
{
    public function __construct(
        private readonly PersistenceManager $persistenceManager,
        private readonly GeneralFunctions $generalFunctions,
        private readonly EventRepository $eventRepository,
        private readonly AccreditationRepository $accreditationRepository
    ) {
    }


    // ACTIONS

    /**
     * action list
     *
     * @return void
     */
    public function listAction()
    {
        $events = $this->eventRepository->findForCheckin();
        $this->view->assign('events', $events);

        $this->setModuleTitle('CheckIn-Liste');
        return $this->backendResponse();
    }

    /**
     * action show
     *
     * @return void
     */
    public function showAction(Event $event)
    {
        $this->view->assign('event', $event);

        $guests = $this->accreditationRepository->findGuestsByEvent($event->getUid());
        $this->view->assign('guests', $guests);

        $this->setModuleTitle($event->getTitle() . ' – CheckIn');
        return $this->backendResponse();

    }

    /**
     * action checkin
     *
     * @return void
     */
    public function checkinAction(Accreditation $accreditation)
    {
        $this->view->assign('accreditation', $accreditation);

        $this->setModuleTitle($accreditation->getGuestOutput()['fullName'] . ' einchecken');
        return $this->backendResponse();
    }

    /**
     * Show the accreditation details for modal
     *
     * @return ResponseInterface
     */
    public function showDetailsForModalAction(int $accreditationId, int $eventUid)
    {
        $accreditation = $this->accreditationRepository->findByUid($accreditationId);

        // Prüft, ob die Akkreditierung existiert und zum Event gehört
        if ($accreditation !== null && $accreditation->getEvent()->getUid() === $eventUid) {
            // Zusätzliche Prüfung, ob die Akkreditierung bestätigt wurde (status > 0)
            if ($accreditation->getStatus() > 0) {
                // Vorbereitung für das Modal...
                $this->view->assign('accreditation', $accreditation);
            } else {
                // Sende eine JSON-Antwort mit Warnung, dass die Akkreditierung nicht bestätigt wurde
                $response = $this->responseFactory->createResponse();
                $response = $response->withHeader('Content-Type', 'application/json; charset=utf-8');
                $response->getBody()->write(json_encode([
                    'warning' => true,
                    'message' => 'Diese Akkreditierung wurde noch nicht bestätigt!'
                ]));
                return $response;
            }
        } else {
            // Sende eine JSON-Antwort mit Fehlermeldung
            $response = $this->responseFactory->createResponse();
            $response = $response->withHeader('Content-Type', 'application/json; charset=utf-8');
            $response->getBody()->write(json_encode([
                'error' => true,
                'message' => 'Diese Akkreditierung gehört nicht zum gewählten Event!'
            ]));
            return $response;
        }
    }


}
