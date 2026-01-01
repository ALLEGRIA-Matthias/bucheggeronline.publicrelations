<?php
declare(strict_types=1);

namespace BucheggerOnline\Publicrelations\Domain\Repository\Frontend;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Repository;
use BucheggerOnline\Publicrelations\Domain\Model\TtAddress;
use BucheggerOnline\Publicrelations\Domain\Repository\TtAddressRepository as BackendTtAddressRepository;

/**
 * Repository für Frontend-spezifische Kontakt-Abfragen (tt_address)
 */
class TtAddressRepository extends BackendTtAddressRepository
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

    public function findByClient($client, string $search = '', int $mailinglist = 0): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tt_address');

        // Die Spalten, die wir für die Anzeige benötigen
        $queryBuilder->select(
            'uid',
            'first_name',
            'middle_name',
            'last_name',
            'title',
            'email',
            'phone',
            'mobile',
            'gender',
            'company',
            'position',
            'mailing_exclude'
        )->from('tt_address');

        // Basis-Bedingung: Nur Kontakte des aktuellen Kunden
        $queryBuilder->where(
            $queryBuilder->expr()->eq('client', $queryBuilder->createNamedParameter($client, Connection::PARAM_INT))
        );

        // HINZUGEFÜGT: Die Suchlogik
        if (!empty($search)) {
            // Wir teilen den Suchbegriff in einzelne Wörter auf (z.B. "Max Mustermann")
            $searchWords = GeneralUtility::trimExplode(' ', $search, true);
            foreach ($searchWords as $word) {
                // Für jedes Wort muss eine der folgenden Bedingungen zutreffen
                $queryBuilder->andWhere(
                    $queryBuilder->expr()->or(
                        $queryBuilder->expr()->like('gender', $queryBuilder->createNamedParameter('%' . $word . '%')),
                        $queryBuilder->expr()->like('title', $queryBuilder->createNamedParameter('%' . $word . '%')),
                        $queryBuilder->expr()->like('first_name', $queryBuilder->createNamedParameter('%' . $word . '%')),
                        $queryBuilder->expr()->like('middle_name', $queryBuilder->createNamedParameter('%' . $word . '%')),
                        $queryBuilder->expr()->like('last_name', $queryBuilder->createNamedParameter('%' . $word . '%')),
                        $queryBuilder->expr()->like('title_suffix', $queryBuilder->createNamedParameter('%' . $word . '%')),
                        $queryBuilder->expr()->like('company', $queryBuilder->createNamedParameter('%' . $word . '%')),
                        $queryBuilder->expr()->like('position', $queryBuilder->createNamedParameter('%' . $word . '%')),
                        $queryBuilder->expr()->like('email', $queryBuilder->createNamedParameter('%' . $word . '%')),
                        $queryBuilder->expr()->like('phone', $queryBuilder->createNamedParameter('%' . $word . '%')),
                        $queryBuilder->expr()->like('mobile', $queryBuilder->createNamedParameter('%' . $word . '%'))
                    )
                );
            }
        }

        $contacts = $queryBuilder->executeQuery()->fetchAllAssociative();

        if (!empty($contacts)) {
            $contactUids = array_column($contacts, 'uid');
            $categoriesByContactUid = $this->findCategoriesForContacts($contactUids);

            // Füge die Kategorien zu den Kontaktdaten hinzu
            foreach ($contacts as &$contact) {
                $contact['categories'] = $categoriesByContactUid[$contact['uid']] ?? [];
            }
        }

        return $contacts;
    }

    /**
     * HINZUGEFÜGT: Neue private Methode, um Kategorien für eine Liste von Kontakten zu holen.
     *
     * @param int[] $contactUids
     * @return array
     */
    private function findCategoriesForContacts(array $contactUids): array
    {
        if (empty($contactUids)) {
            return [];
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_category_record_mm');

        $rows = $queryBuilder
            ->select('mm.uid_local', 'cat.uid', 'cat.title')
            ->from('sys_category_record_mm', 'mm')
            ->join(
                'mm',
                'sys_category',
                'cat',
                $queryBuilder->expr()->eq('mm.uid_foreign', $queryBuilder->quoteIdentifier('cat.uid'))
            )
            ->where(
                // --- HIER IST DIE KORREKTUR ---
                // Wir filtern nach der lokalen UID (dem Kontakt), nicht der fremden UID.
                $queryBuilder->expr()->in('mm.uid_local', $queryBuilder->createNamedParameter($contactUids, Connection::PARAM_INT_ARRAY)),
                $queryBuilder->expr()->eq('mm.tablenames', $queryBuilder->createNamedParameter('tt_address')),
                $queryBuilder->expr()->eq('mm.fieldname', $queryBuilder->createNamedParameter('categories'))
            )
            ->executeQuery()
            ->fetchAllAssociative();

        // Gruppiere die Ergebnisse nach Kontakt-UID
        $categoriesByUid = [];
        foreach ($rows as $row) {
            $categoriesByUid[$row['uid_local']][] = [
                'uid' => $row['uid'],
                'title' => $row['title']
            ];
        }

        return $categoriesByUid;
    }

    public function countByClient($client): int
    {
        $query = $this->createQuery();
        $query->matching($query->equals('client', $client));
        return $query->count();
    }
}