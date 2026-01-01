<?php
declare(strict_types=1);

namespace BucheggerOnline\Publicrelations\Utility;

use TYPO3\CMS\Core\MetaTag\MetaTagManagerRegistry;
use BucheggerOnline\Publicrelations\Domain\Model\Client;
use BucheggerOnline\Publicrelations\Domain\Model\Campaign;
use BucheggerOnline\Publicrelations\Domain\Model\News;

class SEOProvider
{
    public function __construct(
        private readonly TitleProvider $titleProvider,
        private readonly MetaTagManagerRegistry $metaTagManagerRegistry
    ) {}

    /**
     * Setzt die wichtigsten Meta-Tags (Standard, Open Graph, Twitter).
     */
    public function setMeta(string $title, string $description, string $uri, ?string $imageUrl = null, array $imageData = []): void
    {
        $this->titleProvider->setTitle($title);

        $tags = [
            'description' => $description,
            'og:title' => $title,
            'og:description' => $description,
            'og:type' => 'website',
            'og:url' => $uri,
            'twitter:card' => 'summary',
            'twitter:title' => $title,
            'twitter:description' => $description,
        ];

        foreach ($tags as $property => $value) {
            $this->metaTagManagerRegistry
                ->getManagerForProperty($property)
                ->addProperty($property, $value);
        }

        if ($imageUrl !== null) {
            $this->metaTagManagerRegistry
                ->getManagerForProperty('og:image')
                ->addProperty('og:image', $imageUrl, array_merge(['alt' => $title . ' - Pressefoto'], $imageData));

            $this->metaTagManagerRegistry
                ->getManagerForProperty('twitter:image')
                ->addProperty('twitter:image', $imageUrl);
        }
    }

    public function setClientSEO(Client $client, string $baseUri, string $uri): void
    {
        $baseUri = rtrim($baseUri, '/');
        $title = $client->getName();

        $desc = $client->getName() . ' ist';
        if ($client->getSince() !== null) {
            $desc .= ' seit ' . $client->getSince()->format('Y');
        }
        $desc .= ' Kunde von Allegria • Public Relations & Events. Auf der Kundenseite finden Sie kommende Pressetermine, Pressematerial sowie Presseinformationen.';

        $imageUrl = null;
        $imageData = [];

        if ($client->getLogo()?->getOriginalResource() !== null) {
            $publicUrl = $client->getLogo()->getOriginalResource()->getPublicUrl();
            $imageUrl = $baseUri . $publicUrl;

            // Optional: Bildmaße über File-Properties ermitteln (falls gepflegt)
            $props = $client->getLogo()->getOriginalResource()->getProperties();
            $imageData = [
                'width' => $props['width'] ?? null,
                'height' => $props['height'] ?? null,
            ];
        }

        $this->setMeta($title, $desc, $uri, $imageUrl, $imageData);
    }

    public function setCampaignSEO(Campaign $campaign, string $baseUri, string $uri): void
    {
        $baseUri = rtrim($baseUri, '/');
        $clientName = $campaign->getClient()->getName();
        $title = $campaign->getTitle() . ' (' . $campaign->getType()->getTitle() . ' von ' . $clientName . ')';

        $desc = $campaign->getType()->getTitle() . ': ' . $campaign->getTitle();
        if ($campaign->getSubtitle()) {
            $desc .= ' ' . $campaign->getSubtitle();
        }
        $desc .= ' • ' . $clientName . ' ist Kunde von Allegria • Public Relations & Events. Hier finden Sie Pressematerial und -informationen zu ' . $campaign->getTitle() . '.';

        $imageUrl = null;
        $imageData = [];

        if ($campaign->getLogo()?->getOriginalResource() !== null) {
            $publicUrl = $campaign->getLogo()->getOriginalResource()->getPublicUrl();
            $imageUrl = $baseUri . $publicUrl;
            $props = $campaign->getLogo()->getOriginalResource()->getProperties();
            $imageData = [
                'width' => $props['width'] ?? null,
                'height' => $props['height'] ?? null,
            ];
        }

        $this->setMeta($title, $desc, $uri, $imageUrl, $imageData);
    }

    public function setNewsSEO(News $news, string $baseUri, string $uri): void
    {
        $baseUri = rtrim($baseUri, '/');
        $clientName = $news->getClient()->getName();
        $title = $news->getTitle() . ' (Presseinformation zu ' . $clientName . ')';

        $desc = 'Presseinformation zu ' . $clientName . ', Kunde von Allegria • Public Relations & Events.';

        if (($news->getDate() instanceof \DateTime) && $news->getDate() > new \DateTime()) {
            $this->metaTagManagerRegistry
                ->getManagerForProperty('robots')
                ->addProperty('robots', 'noindex, nofollow');
        }

        $imageUrl = null;
        $imageData = [];

        if ($news->getMedia()->count() > 0) {
            $resource = $news->getMedia()->toArray()[0]->getOriginalResource();
            if ($resource !== null) {
                $publicUrl = $resource->getPublicUrl();
                $imageUrl = $baseUri . $publicUrl;
                $props = $resource->getProperties();
                $imageData = [
                    'width' => $props['width'] ?? null,
                    'height' => $props['height'] ?? null,
                ];
            }
        }

        $this->setMeta($title, $desc, $uri, $imageUrl, $imageData);
    }
}
