<?php
declare(strict_types=1);

namespace BucheggerOnline\Publicrelations\Controller;

use Psr\Http\Message\ResponseInterface;

use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Pagination\SimplePagination;
use TYPO3\CMS\Core\Pagination\NumberedPagination;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Extbase\Pagination\QueryResultPaginator;
use BucheggerOnline\Publicrelations\Utility\GeneralFunctions;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;

use BucheggerOnline\Publicrelations\Domain\Model\Mail;
use BucheggerOnline\Publicrelations\Domain\Model\Mailing;
use BucheggerOnline\Publicrelations\Domain\Model\Event;
use BucheggerOnline\Publicrelations\Domain\Model\Accreditation;
use BucheggerOnline\Publicrelations\Domain\Repository\MailRepository;
use BucheggerOnline\Publicrelations\Domain\Repository\MailingRepository;
use BucheggerOnline\Publicrelations\Domain\Repository\EventRepository;
use BucheggerOnline\Publicrelations\Domain\Repository\AccreditationRepository;

class MailController extends AbstractPublicrelationsController
{
    public function __construct(
        private readonly MailRepository $mailRepository,
        private readonly MailingRepository $mailingRepository,
        private readonly EventRepository $eventRepository,
        private readonly AccreditationRepository $accreditationRepository,
        private readonly GeneralFunctions $generalFunctions,
        private readonly PersistenceManager $persistenceManager
    ) {
    }

    public function listAction(): ResponseInterface
    {
        $mails = $this->mailRepository->findAll();

        $paginationConfig = $this->settings['list']['paginate'] ?? [];
        $itemsPerPage = (int) ($paginationConfig['itemsPerPage'] ?? 100);
        $maximumNumberOfLinks = (int) ($paginationConfig['maximumNumberOfLinks'] ?? 0);
        $currentPage = $this->request->hasArgument('currentPage')
            ? (int) $this->request->getArgument('currentPage')
            : 1;

        $paginator = GeneralUtility::makeInstance(QueryResultPaginator::class, $mails, $currentPage, $itemsPerPage);
        $paginationClass = $paginationConfig['class'] ?? SimplePagination::class;

        $pagination = match (true) {
            $paginationClass === NumberedPagination::class && $maximumNumberOfLinks > 0 => GeneralUtility::makeInstance(NumberedPagination::class, $paginator, $maximumNumberOfLinks),
            class_exists($paginationClass) => GeneralUtility::makeInstance($paginationClass, $paginator),
            default => GeneralUtility::makeInstance(SimplePagination::class, $paginator)
        };

        $this->view->assignMultiple([
            'mails' => $mails,
            'settings' => $this->settings,
            'pagination' => [
                'currentPage' => $currentPage,
                'paginator' => $paginator,
                'pagination' => $pagination,
            ],
        ]);


        $this->setModuleTitle('Mail Protokoll');
        return $this->backendResponse();
    }

    public function deleteAction(Mail $mail): ResponseInterface
    {
        if ($mail->getType() === 0) {
            $mailing = $mail->getMailing();
            $this->mailRepository->remove($mail);

            $remaining = $this->mailRepository->findMailsToSend($mailing->getUid())->count();
            if ($remaining <= 1) {
                $mailing->setStatus(-1);
                $this->mailingRepository->update($mailing);
            }

            $this->addModuleFlashMessage('Der Empfänger wurde entfernt!', 'EMPFÄNGER GELÖSCHT!');
            return $this->redirect('show', 'Mailing', null, ['mailing' => $mailing]);
        }

        $this->addModuleFlashMessage(
            'Bereits versandte Mails können nicht entfernt werden!',
            'EMPFÄNGER KANN NICHT GELÖSCHT WERDEN!',
            'ERROR'
        );
        return $this->redirect('show', 'Mailing', null, ['mailing' => $mail->getMailing()]);
    }

    public function viewAction(
        ?string $type = '',
        ?Accreditation $accreditation = null,
        ?Event $event = null,
        ?Mailing $mailing = null,
        ?Mail $mail = null,
        string $content = 'mailing'
    ): ResponseInterface {

        // 1. Prüfen, ob ein gültiger Client übergeben wurde
        if ($type) {
            $layout = match ($type) {
                'accreditation' => $accreditation?->getInvitationType()?->getAltTemplate() ?: 'ALLEGRIA_Neu',
                'mail' => $mail?->getMailing()?->getAltTemplate() ?: 'ALLEGRIA_Neu',
                'mailing' => $mailing?->getAltTemplate() ?: 'ALLEGRIA_Neu',
                default => 'ALLEGRIA_Neu',
            };
        } else {
            $baseUri = $this->generalFunctions->getBaseUri(); 
            return $this->redirectToUri((string)$baseUri);
        }

        

        $guestOutput = [];

        if ($mail !== null && $receiver = $mail->getReceiver()) {
            $first = $receiver->getFirstName() ?? '';
            $middle = $receiver->getMiddleName() ?? '';
            $last = $receiver->getLastName() ?? '';
            $company = $receiver->getCompany() ?? '';
            $fullName = trim("$first $middle $last");
            $sortName = $last !== '' ? trim("$last $first $middle") : $company;
            $genderMap = ['m' => 2, 'f' => 1, 'v' => 0];
            $gender = $genderMap[$receiver->getGender()] ?? 0;

            $personally = (bool) (
                $mailing?->getPersonally()
                && $receiver->getPersonally()
                && ($this->getCurrentBackendUserUid() ?? 0) === 5
            );

            $guestOutput = [
                'company' => $company,
                'name' => $fullName,
                'fullName' => $receiver->getFullName(),
                'gender' => $gender,
                'title' => $receiver->getTitle(),
                'specialTitle' => $receiver->getSpecialTitle(),
                'firstName' => $first,
                'middleName' => $middle,
                'lastName' => $last,
                'phone' => $receiver->getMobile(),
                'email' => $receiver->getEmail(),
                'sortName' => $sortName,
                'personally' => $personally,
            ];

            $this->view->assign('guestOutput', $guestOutput);
        }

        $this->view->assignMultiple([
            'type' => $type,
            'event' => $event,
            'accreditation' => $accreditation,
            'guestOutput' => $accreditation?->getGuestOutput() ?? $guestOutput,
            'mailing' => $mailing,
            'mail' => $mail,
            'layout' => $layout,
            'template' => $layout,
            'content' => $content,
            'backend' => (bool) ($this->getCurrentBackendUserUid() ?? false),
        ]);

        return $this->frontendResponse();
    }
}
