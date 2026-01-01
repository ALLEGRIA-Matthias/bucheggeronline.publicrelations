<?php
declare(strict_types=1);
namespace BucheggerOnline\Publicrelations\Domain\Repository;

use TYPO3\CMS\Extbase\Persistence\Repository;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ReportRepository extends Repository
{
    private const TABLE_REPORT = 'tx_publicrelations_domain_model_report';
    private const TABLE_CLIENT = 'tx_publicrelations_domain_model_client';
    private const TABLE_CAMPAIGN = 'tx_publicrelations_domain_model_campaign';
    private const TABLE_CLIPPINGROUTE = 'tx_publicrelations_domain_model_clippingroute';

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
     * Findet einen Report anhand seiner eindeutigen APA GUID.
     *
     * @param string $apaGuid Die zu suchende GUID
     * @return \BucheggerOnline\Publicrelations\Domain\Model\Report|null
     */
    public function findOneByApaGuid(string $apaGuid): ?\BucheggerOnline\Publicrelations\Domain\Model\Report
    {
        \TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump($apaGuid, 'ReportRepository');
        $query = $this->createQuery();

        $query->matching(
            $query->equals('apaGuid', $apaGuid)
        );

        \TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump($apaGuid, 'ReportRepository');
        $result = $query->execute()->getFirst();

        return $result;
    }

    /**
     * Findet einen Report anhand der GUID und den Relationen (Client/Campaign).
     * Wird für den Duplikat-Check bei Multi-Kategorie-Import benötigt.
     *
     * @param string $apaGuid
     * @param int $clientId
     * @param int $campaignId
     * @return \BucheggerOnline\Publicrelations\Domain\Model\Report|null
     */
    public function findOneByApaGuidAndRelations(string $apaGuid, int $clientId, int $campaignId): ?\BucheggerOnline\Publicrelations\Domain\Model\Report
    {
        $query = $this->createQuery();

        $query->getQuerySettings()
            ->setIgnoreEnableFields(true)
            ->setIncludeDeleted(true);

        $constraints = [
            $query->equals('apaGuid', $apaGuid),
            $query->equals('client', $clientId),
            $query->equals('campaign', $campaignId)
        ];

        $query->matching($query->logicalAnd(...$constraints));

        /** @var \BucheggerOnline\Publicrelations\Domain\Model\Report|null $result */
        $result = $query->execute()->getFirst();

        return $result;
    }

    public function findOneByApaGuidAndRoute($guid, $routeUid)
    {
        $query = $this->createQuery();
        $query->matching(
            $query->logicalAnd(
                $query->equals('apa_guid', $guid),
                $query->equals('clippingroute', $routeUid)
            )
        );
        $query->setLimit(1);
        return $query->execute()->getFirst();
    }

    /**
     * Findet alle Reports (Clippings), die einer Route zugeordnet
     * sind und noch nicht als 'reported' markiert wurden.
     *
     * @param int $clippingRouteUid
     * @return array|\TYPO3\CMS\Extbase\Persistence\QueryResultInterface
     */
    public function findUnreportedByRoute(int $clippingRouteUid)
    {
        $query = $this->createQuery();
        $query->matching(
            $query->logicalAnd(
                $query->equals('clippingroute', $clippingRouteUid),
                $query->equals('reported', 0),
                $query->equals('type', 'clipping'),
                $query->equals('status', 'clipped')
            )
        );
        // Optional: Sortierung
        $query->setOrderings(['date' => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_DESCENDING]);

        return $query->execute();
    }

    /**
     * Zählt alle Reports für eine Route, die die Kriterien
     * für einen Neu-Versand erfüllen.
     *
     * @param int $clippingRouteUid
     * @return int
     */
    public function countUnreportedByRoute(int $clippingRouteUid): int
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(self::TABLE_REPORT);

        $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $count = $queryBuilder->count('uid')
            ->from(self::TABLE_REPORT)
            ->where(
                // 1. Route muss passen
                $queryBuilder->expr()->eq(
                    'clippingroute',
                    $queryBuilder->createNamedParameter($clippingRouteUid, Connection::PARAM_INT)
                ),
                // 2. Typ muss 'clipping' sein
                $queryBuilder->expr()->eq(
                    'type',
                    $queryBuilder->createNamedParameter('clipping')
                ),
                // 3. Status muss 'clipped' sein
                $queryBuilder->expr()->eq(
                    'status',
                    $queryBuilder->createNamedParameter('clipped')
                ),
                // 4. Darf noch nicht versendet/gemeldet sein
                $queryBuilder->expr()->eq(
                    'reported',
                    $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)
                )
            )
            ->executeQuery()
            ->fetchOne();

        return (int) $count;
    }

    /**
     * Findet alle Clippings, die für die Medium-Migration relevant sind.
     * (Status 'clipped' ODER 'clipping_reported' und 'medium' ist leer)
     *
     * @return array Liefert nur ['uid', 'data']
     */
    public function findAllClippingsForMigration(): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(self::TABLE_REPORT);

        $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        return $queryBuilder
            ->select('uid', 'pid', 'data', 'dateold')
            ->from(self::TABLE_REPORT)
            ->executeQuery()
            ->fetchAllAssociative();
    }

    /**
     * Holt alle Reports für das Backend-Grid, optional gefiltert.
     * Unterstützt erweiterte Such-Syntax.
     *
     * @param ?string $searchQuery
     * @return array
     */
    public function findFilteredForBackend(?string $searchQuery = null): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(self::TABLE_REPORT);

        $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        // Wir brauchen JOINs, um nach Kunde und Projekt zu filtern
        $queryBuilder->select('r.*')
            ->from(self::TABLE_REPORT, 'r')
            ->leftJoin(
                'r',
                self::TABLE_CLIENT,
                'cl',
                $queryBuilder->expr()->eq('r.client', $queryBuilder->quoteIdentifier('cl.uid'))
            )
            ->leftJoin(
                'r',
                self::TABLE_CAMPAIGN,
                'ca',
                $queryBuilder->expr()->eq('r.campaign', $queryBuilder->quoteIdentifier('ca.uid'))
            )
            ->leftJoin(
                'r',
                self::TABLE_CLIPPINGROUTE,
                'cr',
                $queryBuilder->expr()->eq('r.clippingroute', $queryBuilder->quoteIdentifier('cr.uid'))
            );

        if (!empty($searchQuery)) {
            $constraints = [];

            // 1. Keyed-Phrase-Suche (z.B. kunde:"el gaucho")
            // Findet alle key:"value" Paare
            if (preg_match_all('/(?<key>\w+):"([^"]+)"/', $searchQuery, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $constraints[] = $this->buildKeyFilterConstraint($queryBuilder, $match['key'], $match[2]);
                }
                // Entferne diese Treffer aus der Suche
                $searchQuery = preg_replace('/(?<key>\w+):"([^"]+)"/', '', $searchQuery);
            }

            // 2. Normale Key-Value-Suche (z.B. typ:clipping oder datum:>14.11.2025)
            // Findet alle key:value Paare (ohne Anführungszeichen)
            if (preg_match_all('/(?<key>\w+):(\S+)/', $searchQuery, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $constraints[] = $this->buildKeyFilterConstraint($queryBuilder, $match['key'], $match[2]);
                }
                // Entferne diese Treffer
                $searchQuery = preg_replace('/(?<key>\w+):(\S+)/', '', $searchQuery);
            }

            // 3. Phrasen-Suche (z.B. "guten tag")
            // Findet alle "value" (ohne Key)
            if (preg_match_all('/"([^"]+)"/', $searchQuery, $matches)) {
                foreach ($matches[1] as $phrase) {
                    $constraints[] = $this->buildLikeConstraint($queryBuilder, $phrase);
                }
                // Entferne Phrasen
                $searchQuery = preg_replace('/"([^"]+)"/', '', $searchQuery);
            }

            // 4. Restliche (allgemeine) Wörter
            $terms = array_filter(explode(' ', $searchQuery));
            foreach ($terms as $term) {
                $term = trim($term);
                if (empty($term))
                    continue;
                $constraints[] = $this->buildLikeConstraint($queryBuilder, $term);
            }

            // Entferne 'null'-Einträge (z.B. von ungültigem Datum)
            $constraints = array_filter($constraints);

            if (!empty($constraints)) {
                // Alle Bedingungen müssen zutreffen
                $queryBuilder->andWhere(...$constraints);
            }
        }

        $queryBuilder->orderBy('r.date', 'DESC'); // Neueste zuerst

        // Wichtig: group by uid, da die JOINs Duplikate erzeugen könnten
        return $queryBuilder->groupBy('r.uid')
            ->executeQuery()
            ->fetchAllAssociative();
    }

    /**
     * Helfer für die allgemeine Suche (ohne Key)
     */
    private function buildLikeConstraint(QueryBuilder $queryBuilder, string $term): object
    {
        $searchTerm = $queryBuilder->quote('%' . $term . '%');
        return $queryBuilder->expr()->or(
            $queryBuilder->expr()->like('r.title', $searchTerm),
            $queryBuilder->expr()->like('r.subtitle', $searchTerm),
            $queryBuilder->expr()->like('r.medium', $searchTerm),
            $queryBuilder->expr()->or(
                $queryBuilder->expr()->like('cl.name', $searchTerm),
                $queryBuilder->expr()->like('cl.short_name', $searchTerm),
                $queryBuilder->expr()->like('cl.also_known_as', $searchTerm)
            ),
            $queryBuilder->expr()->or(
                $queryBuilder->expr()->like('ca.title', $searchTerm),
                $queryBuilder->expr()->like('ca.subtitle', $searchTerm),
                $queryBuilder->expr()->like('ca.also_known_as', $searchTerm)
            )
        );
    }

    /**
     * NEUER HELFER: Baut den Constraint für einen Key-Value-Filter.
     */
    private function buildKeyFilterConstraint(QueryBuilder $queryBuilder, string $key, string $value): object|string|null
    {
        if (empty($value))
            return null;

        $key = strtolower($key);
        // $value ist bereits der reine String (ohne Anführungszeichen)
        $quotedValue = $queryBuilder->quote('%' . $value . '%');

        switch ($key) {
            case 'uid':
                return $queryBuilder->expr()->eq('r.uid', $queryBuilder->createNamedParameter((int) $value));
            case 'typ':
                return $queryBuilder->expr()->like('r.type', $quotedValue);
            case 'medium':
                return $queryBuilder->expr()->like('r.medium', $quotedValue);
            case 'inhalt':
                return $queryBuilder->expr()->like('r.content', $quotedValue);
            case 'keyword':
                return $queryBuilder->expr()->like('cr.keyword', $quotedValue);
            case 'kunde':
                return $queryBuilder->expr()->or(
                    $queryBuilder->expr()->like('cl.name', $quotedValue),
                    $queryBuilder->expr()->like('cl.short_name', $quotedValue),
                    $queryBuilder->expr()->like('cl.also_known_as', $quotedValue)
                );
            case 'projekt':
                return $queryBuilder->expr()->or(
                    $queryBuilder->expr()->like('ca.title', $quotedValue),
                    $queryBuilder->expr()->like('ca.subtitle', $quotedValue),
                    $queryBuilder->expr()->like('ca.also_known_as', $quotedValue)
                );
            case 'datum':
                return $this->parseDateFilter($queryBuilder, $value);
        }
        return null;
    }

    /**
     * NEUER HELFER: Parst Datums-Filter (z.B. <14.11.2025 oder 14.11.2025-16.11.2025)
     * @return object|null Ein QueryBuilder Constraint oder null bei Fehler
     */
    private function parseDateFilter(QueryBuilder $queryBuilder, string $dateValue): object|string|null
    {
        // 1. Fall: Range (z.B. 14.11.2025-16.11.2025)
        if (str_contains($dateValue, '-')) {
            [$from, $to] = explode('-', $dateValue, 2);
            $fromTs = $this->convertDateToTimestamp(trim($from), 'start');
            $toTs = $this->convertDateToTimestamp(trim($to), 'end');

            if ($fromTs && $toTs) {
                return $queryBuilder->expr()->and(
                    $queryBuilder->expr()->gte('r.date', $fromTs),
                    $queryBuilder->expr()->lte('r.date', $toTs)
                );
            }
        }

        // 2. Fall: Kleiner als (z.B. <14.11.2025)
        if (str_starts_with($dateValue, '<')) {
            $date = substr($dateValue, 1);
            $ts = $this->convertDateToTimestamp($date, 'start'); // "Kleiner als" der Start des Tages
            if ($ts) {
                return $queryBuilder->expr()->lt('r.date', $ts);
            }
        }

        // 3. Fall: Größer als (z.B. >14.11.2025)
        if (str_starts_with($dateValue, '>')) {
            $date = substr($dateValue, 1);
            $ts = $this->convertDateToTimestamp($date, 'end'); // "Größer als" das Ende des Tages
            if ($ts) {
                return $queryBuilder->expr()->gt('r.date', $ts);
            }
        }

        // 4. Fall: Exakter Tag (z.B. 14.11.2025)
        $startTs = $this->convertDateToTimestamp($dateValue, 'start');
        $endTs = $this->convertDateToTimestamp($dateValue, 'end');

        if ($startTs && $endTs) {
            return $queryBuilder->expr()->and(
                $queryBuilder->expr()->gte('r.date', $startTs),
                $queryBuilder->expr()->lte('r.date', $endTs)
            );
        }

        return null; // Ungültiges Format
    }

    /**
     * NEUER HELFER: Wandelt "dd.mm.yyyy" in einen Timestamp um (Start oder Ende des Tages)
     */
    private function convertDateToTimestamp(string $dateStr, string $position = 'start'): ?int
    {
        $dateStr = trim($dateStr);
        $dateTime = \DateTime::createFromFormat('d.m.Y', $dateStr);

        if ($dateTime === false) {
            return null; // Ungültiges Format
        }

        if ($position === 'start') {
            $dateTime->setTime(0, 0, 0);
        } else {
            $dateTime->setTime(23, 59, 59);
        }

        return $dateTime->getTimestamp();
    }
}