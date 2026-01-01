<?php
namespace BucheggerOnline\Publicrelations\Task;

use Dba\Connection as DbaConnection;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Scheduler\Task\AbstractTask;

class CleanupTask extends AbstractTask
{
    public bool $dryRun = true;
    public int $facieTypeUid = 596;
    public int $fotografenTypeUid = 598;
    public int $kundenTypeUid = 600;
    public int $mitarbeitendeTypeUid = 601;
    public int $partnerTypeUid = 599;
    public int $redakteureTypeUid = 597;
    public int $contentCreatorTypeUid = 602;

    public function __construct()
    {
        parent::__construct();
        // Da wir nicht die moderne Service-Registrierung nutzen, holen wir uns den Logger manuell.
        $this->logger = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Log\LogManager::class)->getLogger(__CLASS__);
    }


    public function execute(): bool
    {
        $this->logger->notice('Scheduler gestartet!');

        $facieUids = $this->getCategoryUidsWithSubcategories(205);

        $redakteureBaseUids = $this->getCategoryUidsWithSubcategories(79);
        $redakteureExcludeUids1 = $this->getCategoryUidsWithSubcategories(366);
        $redakteureExcludeUids2 = $this->getCategoryUidsWithSubcategories(116);
        $redakteureExcludeUids3 = $this->getCategoryUidsWithSubcategories(434);
        $allRedakteureExcludeUids = array_merge(
            $redakteureExcludeUids1,
            $redakteureExcludeUids2,
            $redakteureExcludeUids3
        );

        // Schritt 2: Alle ausgeschlossenen UIDs von der Basis-Liste abziehen.
        // Das Ergebnis ist die finale, saubere Liste der Redakteur-Kategorie-UIDs.
        $redakteureFinalUids = array_diff($redakteureBaseUids, $allRedakteureExcludeUids);

        $contentCreatorUids = $redakteureExcludeUids1;

        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $queryBuilder = $connectionPool->getQueryBuilderForTable('tt_address');

        $allContactsToProcess = $queryBuilder
            ->select('uid', 'pid')
            ->from('tt_address')
            ->executeQuery()
            ->fetchAllAssociative();

        $contactsToProcess = array_column($allContactsToProcess, null, 'uid');

        if (empty($contactsToProcess)) {
            $this->logger->notice('Perfekt! Alle Kontakte haben bereits einen Typ.');
            return true;
        }

        $contactUidsToProcess = array_keys($contactsToProcess);

        if (empty($contactUidsToProcess)) {
            $this->logger->notice('Keine Kontakte zur Verarbeitung gefunden. Task wird erfolgreich beendet.');
            return true;
        }

        $mmQueryBuilder = $connectionPool->getQueryBuilderForTable('sys_category_record_mm');
        $categoryRelations = $mmQueryBuilder
            ->select('uid_local', 'uid_foreign')
            ->from('sys_category_record_mm')
            ->where(
                $mmQueryBuilder->expr()->eq('tablenames', $mmQueryBuilder->createNamedParameter('tt_address')),
                $mmQueryBuilder->expr()->eq('fieldname', $mmQueryBuilder->createNamedParameter('categories')),
                $mmQueryBuilder->expr()->in('uid_foreign', $mmQueryBuilder->createNamedParameter($contactUidsToProcess, Connection::PARAM_INT_ARRAY))
            )
            ->executeQuery()
            ->fetchAllAssociative();

        $contactsWithCategories = [];
        foreach ($categoryRelations as $relation) {
            $contactsWithCategories[$relation['uid_foreign']][] = $relation['uid_local'];
        }
        $contactsToUpdate = [];
        foreach ($contactsToProcess as $uid => $contact) {
            $contactCategoryUids = $contactsWithCategories[$uid] ?? [];

            // GEÄNDERT: Wir initialisieren ein leeres Array für alle gefundenen Typen
            $matchedTypeUids = [];

            // Content Creators
            if (!empty(array_intersect($contactCategoryUids, $contentCreatorUids))) {
                $matchedTypeUids[] = $this->contentCreatorTypeUid;
            }

            // Redakteure
            if (!empty(array_intersect($contactCategoryUids, $redakteureFinalUids))) {
                $matchedTypeUids[] = $this->redakteureTypeUid;
            }

            // Facies
            if (!empty(array_intersect($contactCategoryUids, $facieUids)) || (int) $contact['pid'] === 40) {
                $matchedTypeUids[] = $this->facieTypeUid;
            }

            // Fotografen
            if (in_array(197, $contactCategoryUids, true) || in_array(442, $contactCategoryUids, true)) {
                $matchedTypeUids[] = $this->fotografenTypeUid;
            }

            // Kunden
            if (in_array(259, $contactCategoryUids, true) || in_array(526, $contactCategoryUids, true)) {
                $matchedTypeUids[] = $this->kundenTypeUid;
            }

            // Mitarbeitende
            if (in_array(434, $contactCategoryUids, true)) {
                $matchedTypeUids[] = $this->mitarbeitendeTypeUid;
            }

            // Partner
            if (in_array(260, $contactCategoryUids, true) || in_array(255, $contactCategoryUids, true) || in_array(527, $contactCategoryUids, true)) {
                $matchedTypeUids[] = $this->partnerTypeUid;
            }

            // GEÄNDERT: Wenn mindestens ein Typ gefunden wurde...
            if (!empty($matchedTypeUids)) {
                // ...säubern wir die Liste von eventuellen Duplikaten und speichern das Array.
                $contactsToUpdate[$uid] = array_unique($matchedTypeUids);
            }
        }
        if (empty($contactsToUpdate)) {
            $this->logger->notice('Alle Kontakte geprüft, aber keine passenden Regeln für eine Zuweisung gefunden.');
            return true;
        }

        if ($this->dryRun) {
            $count = count($contactsToUpdate);
            // KORREKTUR 3: Wir verwenden jetzt $this->logger->notice()
            \TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump(sprintf('Dry Run: %d Kontakte würden einen neuen Typ (als Kategorie-Relation) erhalten.', $count));
            // \TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump($contactsToUpdate);
            return true;
        }

        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataMap = [];
        foreach ($contactsToUpdate as $uid => $typeUidsArray) {
            $dataMap['tt_address'][$uid] = ['contact_types' => implode(',', $typeUidsArray)];
        }
        $dataHandler->start($dataMap, []);
        $dataHandler->process_datamap();

        if (!empty($dataHandler->errorLog)) {
            // KORREKTUR 4: Wir verwenden jetzt $this->logger->error()
            $this->logger->error('Fehler beim Aktualisieren der Kontakte.', ['errors' => $dataHandler->errorLog]);
            return false;
        }

        $this->logger->notice(sprintf('%d Kontakte erfolgreich aktualisiert.', count($contactsToUpdate)));
        return true;
    }

    private function getCategoryUidsWithSubcategories(int $startingPointUid): array
    {
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $queryBuilder = $connectionPool->getQueryBuilderForTable('sys_category');
        $uidsToProcess = [$startingPointUid];
        $allUids = [$startingPointUid];

        while (!empty($uidsToProcess)) {
            $children = $queryBuilder
                ->select('uid')
                ->from('sys_category')
                ->where($queryBuilder->expr()->in('parent', $queryBuilder->createNamedParameter($uidsToProcess, Connection::PARAM_INT_ARRAY)))
                ->executeQuery()
                ->fetchFirstColumn();

            if (!empty($children)) {
                $allUids = array_merge($allUids, $children);
                $uidsToProcess = $children;
            } else {
                $uidsToProcess = [];
            }
        }
        return array_unique($allUids);
    }
}