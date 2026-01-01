<?php
declare(strict_types=1);
namespace BucheggerOnline\Publicrelations\Domain\Repository;

use TYPO3\CMS\Extbase\Persistence\Repository;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ClippingRouteRepository extends Repository
{

    private const TABLE_ROUTE = 'tx_publicrelations_domain_model_clippingroute';
    private const TABLE_CLIENT = 'tx_publicrelations_domain_model_client';
    private const TABLE_CAMPAIGN = 'tx_publicrelations_domain_model_campaign';

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
     * Holt alle Routen für das Backend-Grid, optional gefiltert.
     * Unterstützt erweiterte Such-Syntax.
     *
     * @param ?string $searchQuery
     * @return array
     */
    public function findFilteredForBackend(?string $searchQuery = null): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(self::TABLE_ROUTE);

        $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        // Wir brauchen JOINs, um nach Kunde und Projekt zu filtern
        $queryBuilder->select('cr.*')
            ->from(self::TABLE_ROUTE, 'cr')
            ->leftJoin(
                'cr',
                self::TABLE_CLIENT,
                'cl',
                $queryBuilder->expr()->eq('cr.client', $queryBuilder->quoteIdentifier('cl.uid'))
            )
            ->leftJoin(
                'cr',
                self::TABLE_CAMPAIGN,
                'ca',
                $queryBuilder->expr()->eq('cr.project', $queryBuilder->quoteIdentifier('ca.uid'))
            );

        if (!empty($searchQuery)) {
            // --- START: NEUE FILTER-LOGIK ---
            $constraints = [];

            // 1. Phrasen-Suche (in Anführungszeichen)
            if (preg_match_all('/"([^"]+)"/', $searchQuery, $matches)) {
                foreach ($matches[1] as $phrase) {
                    $constraints[] = $this->buildLikeConstraint($queryBuilder, $phrase);
                }
                // Entferne die Phrasen aus der normalen Suche
                $searchQuery = preg_replace('/"([^"]+)"/', '', $searchQuery);
            }

            // 2. Normale Wörter (split bei Leerzeichen)
            $terms = array_filter(explode(' ', $searchQuery));

            foreach ($terms as $term) {
                $term = trim($term);
                if (empty($term))
                    continue;

                // 3. Keyword-Filter (kunde:, projekt:, email:, keyword:)
                if (str_contains($term, ':')) {
                    [$key, $value] = explode(':', $term, 2);
                    if (empty($value))
                        continue;

                    switch (strtolower($key)) {
                        case 'keyword':
                            $constraints[] = $queryBuilder->expr()->like('cr.keyword', $queryBuilder->quote('%' . $value . '%'));
                            break;
                        case 'uid':
                            $constraints[] = $queryBuilder->expr()->like('cr.uid', $queryBuilder->quote('%' . $value . '%'));
                            break;
                        case 'email':
                            $constraints[] = $queryBuilder->expr()->or(
                                $queryBuilder->expr()->like('cr.to_emails', $queryBuilder->quote('%' . $value . '%')),
                                $queryBuilder->expr()->like('cr.cc_emails', $queryBuilder->quote('%' . $value . '%')),
                                $queryBuilder->expr()->like('cr.bcc_emails', $queryBuilder->quote('%' . $value . '%'))
                            );
                            break;
                        case 'kunde':
                            $constraints[] = $queryBuilder->expr()->or(
                                $queryBuilder->expr()->like('cl.name', $queryBuilder->quote('%' . $value . '%')),
                                $queryBuilder->expr()->like('cl.short_name', $queryBuilder->quote('%' . $value . '%')),
                                $queryBuilder->expr()->like('cl.also_known_as', $queryBuilder->quote('%' . $value . '%'))
                            );
                            break;
                        case 'projekt':
                            $constraints[] = $queryBuilder->expr()->or(
                                $queryBuilder->expr()->like('ca.title', $queryBuilder->quote('%' . $value . '%')),
                                $queryBuilder->expr()->like('ca.subtitle', $queryBuilder->quote('%' . $value . '%')),
                                $queryBuilder->expr()->like('ca.also_known_as', $queryBuilder->quote('%' . $value . '%'))
                            );
                            break;
                    }
                } else {
                    // 4. Allgemeiner Term (kein Keyword)
                    $constraints[] = $this->buildLikeConstraint($queryBuilder, $term);
                }
            }

            if (!empty($constraints)) {
                // Alle Bedingungen müssen zutreffen
                $queryBuilder->andWhere(...$constraints);
            }
            // --- ENDE: NEUE FILTER-LOGIK ---
        }

        $queryBuilder->orderBy('cr.keyword', 'ASC');

        // Wichtig: group by uid, da die JOINs Duplikate erzeugen könnten
        return $queryBuilder->groupBy('cr.uid')
            ->executeQuery()
            ->fetchAllAssociative();
    }

    /**
     * Baut eine OR-Bedingung für einen allgemeinen Suchbegriff.
     */
    private function buildLikeConstraint(QueryBuilder $queryBuilder, string $term): object
    {
        $searchTerm = $queryBuilder->quote('%' . $term . '%');
        return $queryBuilder->expr()->or(
            $queryBuilder->expr()->like('cr.keyword', $searchTerm),
            $queryBuilder->expr()->like('cr.to_emails', $searchTerm),
            $queryBuilder->expr()->like('cr.cc_emails', $searchTerm),
            $queryBuilder->expr()->like('cr.bcc_emails', $searchTerm)
        );
    }

    /**
     * Holt alle Kampagnen als schnelles Array [uid => [data]] für Lookups.
     * @return array
     */
    public function findAllForLookup(): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(self::TABLE_ROUTE);

        $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $rows = $queryBuilder
            // --- HIER: Alle gewünschten Felder ---
            ->select('uid', 'keyword') //
            ->from(self::TABLE_ROUTE)
            ->executeQuery()
            ->fetchAllAssociative();

        return array_combine(array_column($rows, 'uid'), $rows);
    }
}