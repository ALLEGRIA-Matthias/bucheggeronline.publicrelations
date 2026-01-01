<?php
declare(strict_types=1);
namespace BucheggerOnline\Publicrelations\Domain\Repository;

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
        // Spezifische TYPO3-Gruppen-IDs (0 und -1) entfernen.
        $filteredGroupIds = array_filter($feUserGroupIds, function ($groupId) {
            return $groupId > 0;
        });

        // Abfrage 1: Direkte Berechtigungen des Benutzers holen
        $queryUser = $this->createQuery();
        $userPermissions = $queryUser->matching($queryUser->contains('feUsers', $feUserId))->execute();

        $groupPermissions = [];
        // Abfrage 2: Nur ausführen, wenn der User in validen Gruppen ist.
        if (!empty($filteredGroupIds)) {
            $queryGroup = $this->createQuery();

            // Ein Array für die einzelnen ODER-Bedingungen erstellen
            $groupConstraints = [];
            foreach ($filteredGroupIds as $groupId) {
                // Für jede Gruppen-ID eine "contains"-Bedingung hinzufügen.
                // Das prüft, ob die feGroups-Relation ein Objekt mit dieser UID enthält.
                $groupConstraints[] = $queryGroup->contains('feGroups', $groupId);
            }

            // Alle Bedingungen mit ODER verknüpfen und die Abfrage ausführen
            $groupPermissions = $queryGroup->matching(
                $queryGroup->logicalOr(...$groupConstraints)
            )->execute();
        }

        // Ergebnisse zusammenführen und Duplikate entfernen
        $uniquePermissions = [];
        foreach ($userPermissions as $permission) {
            $uniquePermissions[$permission->getUid()] = $permission;
        }
        foreach ($groupPermissions as $permission) {
            $uniquePermissions[$permission->getUid()] = $permission;
        }

        // Ein sauberes, indiziertes Array mit den einzigartigen Berechtigungsobjekten zurückgeben
        return array_values($uniquePermissions);
    }
}