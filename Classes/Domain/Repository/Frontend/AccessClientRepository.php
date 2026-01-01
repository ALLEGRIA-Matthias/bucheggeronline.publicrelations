<?php
declare(strict_types=1);

namespace BucheggerOnline\Publicrelations\Domain\Repository\Frontend;

use TYPO3\CMS\Extbase\Persistence\Repository;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;

class AccessClientRepository extends Repository
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

    /**
     * Findet alle AccessClient-Objekte, die entweder direkt dem User
     * oder einer seiner Gruppen zugeordnet sind.
     *
     * @param int $feUserId
     * @param array $feUserGroupIds
     * @return array|\TYPO3\CMS\Extbase\Persistence\QueryResultInterface
     */
    public function findForUserAndGroups(int $feUserId, array $feUserGroupIds)
    {
        $query = $this->createQuery();

        // Wenn keine Gruppen-IDs vorhanden sind, kann die Bedingung fehlschlagen
        if (empty($feUserGroupIds)) {
            $feUserGroupIds = [0]; // Dummy-Wert, um SQL-Fehler zu vermeiden
        }

        $constraints = [
            // Bedingung 1: Direkte Zuweisung zum User
            $query->contains('feUsers', $feUserId),
            // Bedingung 2: Zuweisung zu einer der Gruppen des Users
            $query->in('feGroups', $feUserGroupIds)
        ];

        // Verknüpfe die Bedingungen mit ODER
        $query->matching($query->logicalOr(...$constraints));

        return $query->execute();
    }
}