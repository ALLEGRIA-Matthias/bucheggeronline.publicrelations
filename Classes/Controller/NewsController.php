<?php
declare(strict_types=1);

namespace BucheggerOnline\Publicrelations\Controller;

use Psr\Http\Message\ResponseInterface;

use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use BucheggerOnline\Publicrelations\Domain\Repository\NewsRepository;
use BucheggerOnline\Publicrelations\Domain\Model\Client;
use BucheggerOnline\Publicrelations\Domain\Model\Campaign;
use BucheggerOnline\Publicrelations\Domain\Model\News;
use BucheggerOnline\Publicrelations\Utility\SEOProvider;
use BucheggerOnline\Publicrelations\Utility\GeneralFunctions;

class NewsController extends AbstractPublicrelationsController
{
    public function __construct(
        private readonly NewsRepository $newsRepository,
        private readonly SEOProvider $seoProvider,
        private readonly GeneralFunctions $generalFunctions
    ) {
    }

    public function listAction(?Client $client = null, ?Campaign $campaign = null): ResponseInterface
    {
        if ($client) {
            $newsitems = $this->newsRepository->findByProperty('client', $client->getUid());
            $this->view->assignMultiple([
                'newsitems' => $newsitems,
                'client' => $client,
            ]);

            $this->setModuleTitle($client->getName() . ' – Pressemitteilungen');
        } elseif ($campaign) {
            $filteredNewsitems = [];
            $newsitems = $this->newsRepository->findByProperty('client', $campaign->getClient()->getUid());

            foreach ($newsitems as $news) {
                foreach ($news->getCampaigns() as $newsCampaign) {
                    if ($newsCampaign->getUid() === $campaign->getUid()) {
                        $filteredNewsitems[] = $news;
                        break;
                    }
                }
            }

            $this->view->assignMultiple([
                'newsitems' => $filteredNewsitems,
                'campaign' => $campaign,
            ]);

            $this->setModuleTitle($campaign->getTitle() . ' – Pressemitteilungen');
        } else {
            $newsitems = $this->newsRepository->findAll();
            $this->view->assign('newsitems', $newsitems);
        }
        return $this->backendResponse();
    }

    public function showAction(?News $news = null): ResponseInterface
    {
        // 1. Prüfen, ob eine gültige News übergeben wurde
        if ($news === null || !$news->getUid() || !$news->getClient()) {
            $baseUri = $this->generalFunctions->getBaseUri();
            return $this->redirectToUri((string) $baseUri);
        }

        $this->seoProvider->setNewsSEO(
            $news,
            $this->generalFunctions::getBaseUri(),
            $this->generalFunctions::getRequestUrl()
        );

        $this->view->assign('news', $news);

        return $this->frontendResponse();
    }
}
