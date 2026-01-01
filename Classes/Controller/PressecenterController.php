<?php
declare(strict_types=1);
namespace BucheggerOnline\Publicrelations\Controller;

use Psr\Http\Message\ResponseInterface;

use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use BucheggerOnline\Publicrelations\Utility\TitleProvider;
use BucheggerOnline\Publicrelations\Domain\Model\Client;
use BucheggerOnline\Publicrelations\Domain\Model\Event;
use BucheggerOnline\Publicrelations\Domain\Repository\AccessClientRepository;
use In2code\Femanager\Domain\Repository\UserRepository;

class PressecenterController extends AbstractPublicrelationsController
{
    public function __construct(
        private readonly AccessClientRepository $accessClientRepository,
        private readonly UserRepository $userRepository,
        private readonly TitleProvider $titleProvider,
    ) {
    }

    /**
     * Menü für das Pressecenter-Dashboard
     */
    public function menuAction(): ResponseInterface
    {
        $context = GeneralUtility::makeInstance(Context::class);
        $userId = $context->getPropertyFromAspect('frontend.user', 'id');

        $accessPermissions = [];
        // Einfache Prüfung: Wenn die ID > 0 ist, ist der User eingeloggt.
        if ($userId > 0) {
            // Hole die Gruppen-IDs
            $userGroupIds = $context->getPropertyFromAspect('frontend.user', 'groupIds');
            $accessPermissions = $this->accessClientRepository->findForUserAndGroups($userId, $userGroupIds);
        }

        $this->view->assign('accessPermissions', $accessPermissions);

        return $this->frontendResponse();
    }

    /**
     * Action für das User-Menü
     */
    public function userMenuAction(): ResponseInterface
    {
        $context = GeneralUtility::makeInstance(Context::class);

        if ($context->getPropertyFromAspect('frontend.user', 'isLoggedIn')) {
            $userId = $context->getPropertyFromAspect('frontend.user', 'id');
            $user = $this->userRepository->findByUid($userId);
            $userNameParts = [
                $user->getFirstName(),
                $user->getMiddleName(),
                $user->getLastName()
            ];

            // 2. Filter out any empty values and then join the rest with a space
            $userFullName = implode(' ', array_filter($userNameParts));
            $this->view->assignMultiple([
                'isLoggedIn' => true,
                'initials' => $this->getInitials($userFullName),
                'fullName' => $userFullName,
                'image' => $user->getImage(),
                // Annahme: Logout-Seite ist in den TypoScript-Settings konfiguriert
                'logoutLink' => $this->uriBuilder->setTargetPageUid((int) $this->settings['pages']['logout'])->build(),
            ]);
        } else {
            $this->view->assignMultiple([
                'isLoggedIn' => false,
                // Annahme: Login-Seite ist in den TypoScript-Settings konfiguriert
                'loginLink' => $this->uriBuilder->setTargetPageUid((int) $this->settings['pages']['login'])->build(),
            ]);
        }

        return $this->frontendResponse();
    }

    /**
     * Zeigt die Kontakte für einen bestimmten Client an.
     *
     * @param Client $client Der Client, dessen Kontakte angezeigt werden sollen
     */
    public function myContactsAction(Client $client): ResponseInterface
    {
        $clientName = $client->getShortName() ?: $client->getName();
        $this->titleProvider->setTitle('Kontaktübersicht von ' . $clientName);

        $this->view->assign('client', $client);
        return $this->frontendResponse();
    }

    /**
     * Zeigt die Events für einen bestimmten Client an.
     *
     * @param Client $client Der Client, dessen Events angezeigt werden sollen
     */
    public function myEventsAction(Client $client): ResponseInterface
    {
        $clientName = $client->getShortName() ?: $client->getName();
        $this->titleProvider->setTitle('Eventübersicht von ' . $clientName);

        $this->view->assign('client', $client);
        return $this->frontendResponse();
    }

    /**
     * Zeigt die Akkreditierungen für einen bestimmten Client an.
     *
     * @param Event $event Der Event, dessen Akkreditierungen angezeigt werden sollen
     * @param Client $client Der Client, dessen Akkreditierungen angezeigt werden sollen
     */
    public function myAccreditationsAction(Event $event, Client $client): ResponseInterface
    {
        $clientName = $client->getShortName() ?: $client->getName();
        $this->titleProvider->setTitle(sprintf(
            'Gästeliste zu %s von %s',
            $event->getTitle(),
            $clientName
        ));

        $this->view->assign('client', $client);
        $this->view->assign('event', $event);
        return $this->frontendResponse();
    }

    /**
     * Private Hilfsfunktion, um Initialen aus einem Namen zu generieren.
     */
    private function getInitials(string $name): string
    {
        $words = explode(' ', $name);
        if (count($words) >= 2) {
            return strtoupper(substr($words[0], 0, 1) . substr(end($words), 0, 1));
        }
        return strtoupper(substr($name, 0, 2));
    }

}