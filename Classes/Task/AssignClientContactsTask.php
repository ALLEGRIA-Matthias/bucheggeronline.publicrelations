<?php
namespace BucheggerOnline\Publicrelations\Task;

use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Scheduler\Task\AbstractTask;

#[AsTask(
    identifier: 'contact.assign'
)]
class AssignClientContactsTask extends AbstractTask
{
    public bool $dryRun = true;
    private array $customerCategoryUids = [];

    private ConnectionPool $connectionPool;
    private DataHandler $dataHandler;

    public function __construct()
    {
        parent::__construct();
    }

    public function execute(): bool
    {
        $this->connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $this->dataHandler = GeneralUtility::makeInstance(DataHandler::class);

        $this->logger->error('Starte Task: Kontakte Clients zuweisen...');
        if ($this->dryRun) {
            $this->logger->warning('DRY RUN: Es werden keine Änderungen an der Datenbank vorgenommen.');
        }

        $this->customerCategoryUids = $this->getCategoryUidsWithSubcategories(309);

        $this->assignClientsAndSplitMultiClientContacts();

        $this->logger->error('Task beendet.');
        return true;
    }

    private function assignClientsAndSplitMultiClientContacts(): void
    {
        $this->logger->error('[Schritt 2/3] Suche nach Kontakten mit ungeklärter Client-Zugehörigkeit...');

        // 1. Lade alle Kundenkategorien und deren Client-Zuordnung (unverändert)
        $catQuery = $this->connectionPool->getQueryBuilderForTable('sys_category');
        $categoryToClientMap = $catQuery
            ->select('uid', 'client')
            ->from('sys_category')
            ->where($catQuery->expr()->in('uid', $this->customerCategoryUids))
            ->executeQuery()
            ->fetchAllKeyValue();

        // KORREKTUR: Lade alle relevanten Kontakte und ihre Kategorien über eine robuste Abfrage
        $relationsQueryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_category_record_mm');
        $relationsRaw = $relationsQueryBuilder
            ->select(
                'mm.uid_foreign AS contact_uid',
                'mm.uid_local AS category_uid'
            )
            ->from('sys_category_record_mm', 'mm')
            ->join(
                'mm',
                'tt_address',
                't',
                $relationsQueryBuilder->expr()->eq('t.uid', $relationsQueryBuilder->quoteIdentifier('mm.uid_foreign'))
            )
            ->join(
                'mm',
                'sys_category',
                'c',
                $relationsQueryBuilder->expr()->eq('c.uid', $relationsQueryBuilder->quoteIdentifier('mm.uid_local'))
            )
            ->where(
                $relationsQueryBuilder->expr()->eq('mm.tablenames', $relationsQueryBuilder->createNamedParameter('tt_address')),
                $relationsQueryBuilder->expr()->eq('mm.fieldname', $relationsQueryBuilder->createNamedParameter('categories')),
                $relationsQueryBuilder->expr()->in('mm.uid_local', $this->customerCategoryUids),
                $relationsQueryBuilder->expr()->eq('t.client', 0), // Nur die, die noch keinen Client haben
                $relationsQueryBuilder->expr()->eq('t.deleted', 0),
                $relationsQueryBuilder->expr()->eq('c.deleted', 0)
            )
            ->executeQuery()
            ->fetchAllAssociative();

        // Bereite die Daten auf: Ein Array pro Kontakt
        $contactsWithCategories = [];
        foreach ($relationsRaw as $row) {
            $contactsWithCategories[$row['contact_uid']][] = $row['category_uid'];
        }

        if (empty($contactsWithCategories)) {
            $this->logger->error('[Schritt 2/3] Keine Kontakte mit ungeklärter Client-Zugehörigkeit gefunden.');
            return;
        }

        $multiClientContacts = [];
        $singleClientContactsDataMap = [];

        // Gehe die aufbereiteten Kontakte durch
        foreach ($contactsWithCategories as $contactUid => $contactCats) {
            $clientsOnContact = [];
            foreach ($contactCats as $catUid) {
                if (isset($categoryToClientMap[$catUid]) && $categoryToClientMap[$catUid] > 0) {
                    $clientsOnContact[$categoryToClientMap[$catUid]][] = $catUid;
                }
            }

            if (count($clientsOnContact) === 1) {
                // Fall 2: Nur ein Kunde, alles gut. Client zuweisen.
                $clientId = array_key_first($clientsOnContact);
                $singleClientContactsDataMap['tt_address'][$contactUid] = [
                    'client' => $clientId
                ];
                $this->logger->error(sprintf('  -> Kontakt %d wird Client %d zugewiesen.', $contactUid, $clientId));
            } elseif (count($clientsOnContact) > 1) {
                // Fall 3: Mehrere Kunden, muss gesplittet werden.
                $multiClientContacts[$contactUid] = $clientsOnContact;
            }
        }

        // Zuerst die einfachen Zuweisungen durchführen
        if (!empty($singleClientContactsDataMap) && !$this->dryRun) {
            $this->dataHandler->start($singleClientContactsDataMap, []);
            $this->dataHandler->process_datamap();
        }

        if (empty($multiClientContacts)) {
            $this->logger->error('[Schritt 2/3] Keine Kontakte mit mehreren Kunden zum Splitten gefunden.');
            return;
        }

        // Jetzt die komplexen Fälle splitten
        $this->logger->error(sprintf('[Schritt 3] %d Kontakte mit mehreren Kunden gefunden. Starte Splitting...', count($multiClientContacts)));

        // KORREKTUR: Neuen, sauberen QueryBuilder verwenden und `fetchAllAssociativeIndexedBy` ersetzen
        $contactDataQueryBuilder = $this->connectionPool->getQueryBuilderForTable('tt_address');
        $allContactsRaw = $contactDataQueryBuilder->select('*')->from('tt_address')
            ->where($contactDataQueryBuilder->expr()->in('uid', array_keys($multiClientContacts)))
            ->executeQuery()
            ->fetchAllAssociative();
        $contactDataResult = array_column($allContactsRaw, null, 'uid');

        $dataMap = [];
        foreach ($multiClientContacts as $uid => $clients) {
            $originalData = $contactDataResult[$uid];
            $isFirstClient = true;

            foreach ($clients as $clientId => $catsForClient) {
                if ($isFirstClient) {
                    // Der erste Kunde bleibt auf dem Original-Kontakt
                    $dataMap['tt_address'][$uid] = [
                        'client' => $clientId,
                        'categories' => implode(',', $catsForClient)
                    ];
                    $isFirstClient = false;
                    $this->logger->error(sprintf('  -> Kontakt %d: Haupt-Client wird %d.', $uid, $clientId));
                } else {
                    // Für jeden weiteren Kunden eine Kopie erstellen (flache Kopie, wie besprochen)
                    $tempId = 'NEW_MULTI_CLIENT_' . $uid . '_' . $clientId;
                    $copyData = $originalData;
                    unset($copyData['uid']);
                    $copyData['original_address'] = $uid;
                    $copyData['client'] = $clientId;
                    $copyData['categories'] = implode(',', $catsForClient);
                    $dataMap['tt_address'][$tempId] = $copyData;
                    $this->logger->error(sprintf('  -> Kontakt %d: Kopie %s für Client %d wird erstellt.', $uid, $tempId, $clientId));
                }
            }
        }

        if (!$this->dryRun) {
            $this->dataHandler->start($dataMap, []);
            $this->dataHandler->process_datamap();
            if (!empty($this->dataHandler->errorLog)) {
                $this->logger->error(
                    'Fehler beim Splitten der Multi-Client-Kontakte.',
                    ['errors' => $this->dataHandler->errorLog]
                );
            }
        }
    }

    private function getCategoryUidsWithSubcategories(int $startingPointUid): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_category');
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