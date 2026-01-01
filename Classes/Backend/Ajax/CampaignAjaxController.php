<?php

declare(strict_types=1);

namespace BucheggerOnline\Publicrelations\Backend\Ajax; // ## DEIN NAMESPACE ANPASSEN ##

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use BucheggerOnline\Publicrelations\Domain\Repository\CampaignRepository; // ## ANPASSEN ##
use BucheggerOnline\Publicrelations\Domain\Model\Campaign; // ## ANPASSEN, falls du Typ-Hinting für $campaign nutzt ##

class CampaignAjaxController extends ActionController
{
    protected CampaignRepository $campaignRepository;

    public function __construct(CampaignRepository $campaignRepository)
    {
        $this->campaignRepository = $campaignRepository;
    }

    /**
     * Sucht Kampagnen basierend auf 'clientUid' und gibt sie als JSON zurück.
     * Erwartet den GET-Parameter 'clientUid'.
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function searchByClient(ServerRequestInterface $request): ResponseInterface
    {
        $queryParams = $request->getQueryParams();
        $clientUid = isset($queryParams['clientUid']) ? (int) $queryParams['clientUid'] : 0;
        // Der 'q'-Parameter von TomSelect wird hier ignoriert, da wir primär nach clientUid filtern.
        // Du könntest ihn für eine zusätzliche Textsuche innerhalb der Kampagnen dieses Kunden verwenden.

        $campaignData = [];

        if ($clientUid > 0) {
            $campaigns = $this->campaignRepository->findByClientUid($clientUid);

            /** @var Campaign $campaign */ // ## ANPASSEN an dein Campaign Model Namespace ##
            foreach ($campaigns as $campaign) {
                // Stelle sicher, dass die Getter-Methoden in deinem Campaign-Modell existieren
                // oder greife direkt auf Properties zu, falls das Repository ein Array von Arrays liefert.
                $campaignData[] = [
                    'uid' => $campaign->getUid(),
                    'title' => $campaign->getTitle(),
                    'subtitle' => $campaign->getSubtitle(), // Methode getSubtitle() muss im Model existieren
                ];
            }
        }

        return new JsonResponse($campaignData);
    }
}