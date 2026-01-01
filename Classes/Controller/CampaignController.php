<?php
namespace BucheggerOnline\Publicrelations\Controller;

use Psr\Http\Message\ResponseInterface;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

use BucheggerOnline\Publicrelations\Domain\Model\Dto\EmConfiguration;

use BucheggerOnline\Publicrelations\Utility\SEOProvider;
use BucheggerOnline\Publicrelations\Utility\GeneralFunctions;

use BucheggerOnline\Publicrelations\Domain\Repository\CampaignRepository;
use BucheggerOnline\Publicrelations\Domain\Model\Campaign;

use BucheggerOnline\Publicrelations\Domain\Repository\EventRepository;
use BucheggerOnline\Publicrelations\Domain\Model\Event;

use BucheggerOnline\Publicrelations\Domain\Model\Client;

use BucheggerOnline\Publicrelations\Domain\Repository\SysCategoryRepository;
use BucheggerOnline\Publicrelations\Domain\Model\SysCategory;

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
 * CampaignController
 */
class CampaignController extends AbstractPublicrelationsController
{
    public function __construct(
        private readonly CampaignRepository $campaignRepository,
        private readonly EventRepository $eventRepository,
        private readonly SysCategoryRepository $sysCategoryRepository,
        private readonly EmConfiguration $emConfiguration,
        private readonly GeneralFunctions $generalFunctions,
        private readonly SEOProvider $seoProvider
    ) {
    }

    public function listAction(Client $client = null): ResponseInterface
    {
        $campaigns = $client
            ? $this->campaignRepository->findByClient($client->getUid())
            : $this->campaignRepository->findAll();

        $this->view->assignMultiple([
            'client' => $client,
            'campaigns' => $campaigns
        ]);

        $this->setModuleTitle($client->getName() . ' – Produktliste');
        return $this->backendResponse();
    }

    /**
     * action show
     *
     * @return void
     */
    public function showAction(?Campaign $campaign = null): ResponseInterface
    {
        // 1. Prüfen, ob eine gültige Campaign übergeben wurde
        if ($campaign === null || $campaign->getUid() === 0 || $campaign->getUid() === null) {
            $baseUri = $this->generalFunctions->getBaseUri();
            return $this->redirectToUri((string) $baseUri);
        }

        $ip = $this->generalFunctions->getIp();

        // Load Metadata
        $this->seoProvider->setCampaignSEO($campaign, $this->generalFunctions->getBaseUri(), $this->generalFunctions->getRequestUrl());

        $this->view->assign('campaign', $campaign);

        $schedule = [
            'preview' => $this->eventRepository->findSchedule('campaign', $campaign->getUid(), 5),
            'events' => $this->eventRepository->findSchedule('campaign', $campaign->getUid())
        ];
        $this->view->assign('schedule', $schedule);

        $mediumTypes = $this->sysCategoryRepository->findByParentUid($this->emConfiguration->getMediumRootUid());
        $this->view->assign('mediumTypes', $mediumTypes);
        $this->view->assign('ip', $ip);

        return $this->frontendResponse();
    }
}
