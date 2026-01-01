<?php
namespace BucheggerOnline\Publicrelations\Task;

use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Scheduler\Task\AbstractTask;

#[AsTask(
    identifier: 'contact.split' // Muss mit dem Identifier in Services.yaml übereinstimmen
)]
class SplitContactsTask extends AbstractTask
{
    public bool $dryRun = true;
    private array $customerCategoryUids = [];

    // KORREKTUR: Eigenschaften für Dienste deklarieren, aber nicht im Konstruktor injecten.
    private ConnectionPool $connectionPool;
    private DataHandler $dataHandler;

    // KORREKTUR: Der Konstruktor wird nicht mehr für die Dependency Injection benötigt.
    // AbstractTask hat bereits eine `logger` Eigenschaft, die vom Framework gefüllt wird.
    public function __construct()
    {
        parent::__construct();
    }

    public function execute(): bool
    {
        // KORREKTUR: Dienste zur Laufzeit über makeInstance() holen.
        $this->connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $this->dataHandler = GeneralUtility::makeInstance(DataHandler::class);

        // Wir können $this->logger direkt verwenden, da es von AbstractTask bereitgestellt wird.
        $this->logger->error('Starte Task: Kundenkontakte bereinigen...');
        if ($this->dryRun) {
            $this->logger->warning('DRY RUN: Es werden keine Änderungen an der Datenbank vorgenommen.');
        }

        // Zuerst alle UIDs der Kundenkategorien (rekursiv von 309) holen
        $this->customerCategoryUids = $this->getCategoryUidsWithSubcategories(309);

        // Die Schritte nacheinander ausführen
        $this->splitMixedContacts();

        $this->logger->error('Task beendet.');
        return true;
    }

    /**
     * SCHRITT 1: Findet Kontakte, die sowohl Kunden- als auch interne Kategorien haben, und splittet sie.
     */
    private function splitMixedContacts(): void
    {
        $this->logger->error('[Schritt 1] Suche nach Kontakten mit gemischten Kategorien (intern/extern)...');

        // Abfrage aller Kontakte und ihrer Kategorien
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tt_address');
        $relationsRaw = $queryBuilder
            ->select(
                'mm.uid_foreign AS contact_uid', // Wir nehmen uid_foreign, weil das der KONTAKT ist.
                'mm.uid_local AS category_uid'   // Wir nehmen uid_local, weil das die KATEGORIE ist.
            )
            ->from('sys_category_record_mm', 'mm')
            ->join(
                'mm',
                'tt_address',
                't',
                $queryBuilder->expr()->eq('t.uid', $queryBuilder->quoteIdentifier('mm.uid_foreign'))
            )
            ->join(
                'mm',
                'sys_category',
                'c',
                $queryBuilder->expr()->eq('c.uid', $queryBuilder->quoteIdentifier('mm.uid_local'))
            )
            ->where(
                $queryBuilder->expr()->eq('mm.tablenames', $queryBuilder->createNamedParameter('tt_address')),
                $queryBuilder->expr()->eq('mm.fieldname', $queryBuilder->createNamedParameter('categories')),
                $queryBuilder->expr()->eq('t.deleted', 0),
                $queryBuilder->expr()->eq('c.deleted', 0)
            )
            ->executeQuery()
            ->fetchAllAssociative();

        // KORREKTUR: Wir gruppieren die rohen Relationsdaten in ein sauberes Array pro Kontakt.
        // Ergebnis-Format: [ contactUid => [catUid1, catUid2, ...], ... ]
        $contactsWithCategories = [];
        foreach ($relationsRaw as $row) {
            $contactsWithCategories[$row['contact_uid']][] = $row['category_uid'];
        }

        $contactsToSplit = [];
        // KORREKTUR: Wir iterieren nun über unser neu aufgebautes, sauberes Array.
        foreach ($contactsWithCategories as $contactUid => $allCats) {
            // Die Logik zur Unterscheidung von internen und externen Kategorien bleibt gleich.
            $customerCats = array_intersect($allCats, $this->customerCategoryUids);
            $internalCats = array_diff($allCats, $this->customerCategoryUids);

            if (!empty($customerCats) && !empty($internalCats)) {
                $this->logger->error(sprintf(
                    'Kontakt %d wird gesplittet: %d Kunden-Kats, %d interne Kats.',
                    $contactUid,
                    count($customerCats),
                    count($internalCats)
                ));
                $contactsToSplit[$contactUid] = [
                    'customer' => $customerCats,
                    'internal' => $internalCats
                ];
            }
        }
        // \TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump($contactsToSplit);

        if (empty($contactsToSplit)) {
            $this->logger->error('[Schritt 1] Keine gemischten Kontakte gefunden.');
            return;
        }
        $this->logger->error(sprintf('[Schritt 1] %d gemischte Kontakte gefunden. Starte Splitting...', count($contactsToSplit)));

        // Hole die kompletten Daten der zu splittenden Kontakte
        $contactDataQueryBuilder = $this->connectionPool->getQueryBuilderForTable('tt_address');
        $allContacts = $contactDataQueryBuilder
            ->select('*')
            ->from('tt_address')
            ->where(
                $contactDataQueryBuilder->expr()->in('uid', array_keys($contactsToSplit))
            )
            ->executeQuery()
            ->fetchAllAssociative();

        $contactDataResult = array_column($allContacts, null, 'uid');

        $dataMap = [];
        $cmdMap = [];
        foreach ($contactsToSplit as $uid => $categories) {
            $originalData = $contactDataResult[$uid];
            $tempId = 'NEW_CONTACT_' . $uid;

            // 1. Kopie vorbereiten
            $copyData = $originalData;
            unset($copyData['uid']); // Wichtig: UID entfernen!
            $copyData['original_address'] = $uid;
            $copyData['categories'] = implode(',', $categories['customer']);
            $dataMap['tt_address'][$tempId] = $copyData;

            // 2. Original bereinigen
            $dataMap['tt_address'][$uid]['categories'] = implode(',', $categories['internal']);

            $this->logger->error(sprintf('  -> Kontakt %d wird gesplittet. Kopie %s wird erstellt.', $uid, $tempId));
        }

        if (!$this->dryRun) {
            $this->dataHandler->start($dataMap, $cmdMap);
            $this->dataHandler->process_datamap();
            // Fehler-Logging etc.
        }
    }

    private function getCategoryUidsWithSubcategories(int $startingPointUid): array
    {
        // KORREKTUR: ConnectionPool muss auch hier zur Laufzeit geholt werden, falls die Methode
        // von außerhalb von execute() aufgerufen wird (was hier nicht der Fall ist, aber es ist guter Stil).
        // Da wir es bereits in execute() initialisiert haben, ist es hier verfügbar.
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