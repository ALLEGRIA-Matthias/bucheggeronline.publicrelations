<?php
declare(strict_types=1);

namespace BucheggerOnline\Publicrelations\Domain\Repository\Frontend;

use TYPO3\CMS\Extbase\Persistence\Repository;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;

class MailingListsRepository extends Repository
{
    /**
     * Override default settings
     */
    public function initializeObject(): void
    {
        $querySettings = $this->createQuery()->getQuerySettings();
        $querySettings->setRespectStoragePage(false);
        $this->setDefaultQuerySettings($querySettings);
    }
}