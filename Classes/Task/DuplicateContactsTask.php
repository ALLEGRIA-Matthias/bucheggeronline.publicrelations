<?php
namespace BucheggerOnline\Publicrelations\Task;

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Scheduler\Task\AbstractTask;

#[AsTask(
    identifier: 'contact.find_duplicates',
    label: 'PR: Finde doppelte Kontakte (intern/pro Kunde)',
    description: 'Erstellt eine CSV im fileadmin mit E-Mail-Duplikaten. Trennt dabei zwischen internen Kontakten (kein Kunde) und Kontakten pro Kunde.'
)]
class DuplicateContactsTask extends AbstractTask
{
    public bool $dryRun = true;
    private ConnectionPool $connectionPool;
    private Environment $environment;
    private DataHandler $dataHandler;

    public function __construct()
    {
        parent::__construct();
    }

    public function execute(): bool
    {
        set_time_limit(7200);

        $this->connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $this->environment = GeneralUtility::makeInstance(Environment::class);
        $this->dataHandler = GeneralUtility::makeInstance(DataHandler::class);

        if ($this->dryRun) {
            $this->logger->warning('DRY RUN GESTARTET: Es werden nur CSV-Reports über den aktuellen Zustand erstellt.');
            $this->createReportByEmail('duplicates_by_email_DRYRUN_');
            $this->createReportByEmailAndName('duplicates_by_email_and_name_DRYRUN_');
            $this->logger->warning('DRY RUN BEENDET.');
        } else {
            $this->logger->error('!!! AKTIONS-MODUS GESTARTET: Änderungen an der Datenbank werden vorgenommen !!!');

            // 1. Eindeutige Duplikate (Email+Name) finden
            $this->logger->error('Schritt 1: Suche eindeutige Duplikate (Email+Name) zur Zusammenführung...');
            $groupsToMerge = $this->findDuplicateGroupsByName();

            if (empty($groupsToMerge)) {
                $this->logger->error('Keine eindeutigen Duplikate zum Zusammenführen gefunden.');
            } else {
                // 2. Jede Duplikat-Gruppe zusammenführen
                $this->logger->error(sprintf('%d Duplikat-Gruppen werden zusammengeführt...', count($groupsToMerge)));
                foreach ($groupsToMerge as $group) {
                    $this->mergeDuplicateGroup($group);
                }
                $this->logger->error('Schritt 2: Zusammenführung abgeschlossen.');
            }

            // 3. Reports nach der Bereinigung erstellen
            $this->logger->error('Schritt 3: Erstelle Reports über den Zustand NACH der Bereinigung...');
            $this->createReportByEmailAndName('duplicates_by_email_and_name_NACH_BEREINIGUNG_');
            $this->createReportByEmail('duplicates_by_email_UNRESOLVED_');
            $this->logger->error('!!! AKTIONS-MODUS BEENDET !!!');
        }
        return true;
    }

    /**
     * Führt eine Gruppe von Duplikat-UIDs zu einem Master-Datensatz zusammen.
     */
    private function mergeDuplicateGroup(array $duplicateUids): void
    {
        if (count($duplicateUids) < 2) {
            return;
        }

        // 1. Alle Daten der Duplikat-Gruppe holen und nach Erstellungsdatum sortieren (neueste zuerst)
        $qb = $this->connectionPool->getQueryBuilderForTable('tt_address');
        $allDuplicatesData = $qb->select('*') // Wichtig: Alle Spalten holen für die Anreicherung
            ->from('tt_address')
            ->where($qb->expr()->in('uid', $duplicateUids))
            ->orderBy('crdate', 'DESC')
            ->executeQuery()
            ->fetchAllAssociative();

        $masterRecord = array_shift($allDuplicatesData); // Der erste ist der Master
        $masterUid = (int) $masterRecord['uid'];
        $masterPid = (int) $masterRecord['pid'];
        $uidsToDelete = array_map('intval', array_column($allDuplicatesData, 'uid'));
        $pidsOfDuplicates = array_column($allDuplicatesData, 'pid', 'uid');

        $this->logger->error(sprintf('--> Gruppe wird zusammengeführt: Master ist %d, Duplikate sind %s', $masterUid, implode(',', $uidsToDelete)));

        // #################################################################
        // ## NEU: Logik zur Anreicherung von Master-Daten ##
        // #################################################################

        $fieldsToConsolidate = [
            'phone',
            'mobile',
            'www',
            'address',
            'zip',
            'city',
            'region',
            'country',
            'company',
            'position'
        ];
        $dataToUpdateOnMaster = [];

        foreach ($fieldsToConsolidate as $field) {
            // Prüfen, ob das Feld im Master-Datensatz leer ist.
            if (empty(trim((string) $masterRecord[$field]))) {
                // Die Duplikate durchgehen (sind bereits nach "jüngste zuerst" sortiert)
                foreach ($allDuplicatesData as $duplicateRecord) {
                    // Den ersten nicht-leeren Wert aus einem Duplikat finden
                    if (!empty(trim((string) $duplicateRecord[$field]))) {
                        $valueToTransfer = $duplicateRecord[$field];
                        $dataToUpdateOnMaster[$field] = $valueToTransfer;

                        $this->logger->error(sprintf(
                            '    -> Fülle leeres Feld "%s" bei Master %d mit Wert von Duplikat %d.',
                            $field,
                            $masterUid,
                            $duplicateRecord['uid']
                        ));

                        // Wert für dieses Feld gefunden, innere Schleife abbrechen und zum nächsten Feld gehen.
                        break;
                    }
                }
            }
        }


        // 2. Relationen auf den Master umleiten
        // ... (Dieser Teil bleibt unverändert) ...
        $tablesToUpdate = [
            'tx_publicrelations_domain_model_accreditation' => 'guest',
            'tx_publicrelations_domain_model_contact' => 'staff',
            'tx_publicrelations_domain_model_log' => 'address',
            'tx_publicrelations_domain_model_mail' => 'receiver',
        ];
        foreach ($uidsToDelete as $deletedUid) {
            foreach ($tablesToUpdate as $table => $field) {
                $this->connectionPool->getConnectionForTable($table)->update($table, [$field => $masterUid], [$field => $deletedUid]);
            }
        }
        $this->transferRelations('sys_category_record_mm', 'uid_local', 'uid_foreign', $masterUid, $uidsToDelete);
        $this->transferRelations('sys_file_reference', 'uid_local', 'uid_foreign', $masterUid, $uidsToDelete);

        // --- PHASE 3: Duplikate markieren (1. DataHandler-Lauf: NUR UPDATES) ---
        $this->logger->error('    -> Setze Verweise (duplicate_of)...');
        $updateDataMap = [];
        $updateDataMap['tt_address'][(int) $masterUid] = [
            'pid' => $masterPid,
            'duplicates' => implode(',', $uidsToDelete),
        ];
        foreach ($uidsToDelete as $deletedUid) {
            $updateDataMap['tt_address'][(int) $deletedUid] = [
                'pid' => $pidsOfDuplicates[$deletedUid],
                'duplicate_of' => $masterUid,
            ];
        }

        $updateHandler = GeneralUtility::makeInstance(DataHandler::class);
        $updateHandler->start($updateDataMap, []);
        $updateHandler->process_datamap();

        if (!empty($updateHandler->errorLog)) {
            $this->logger->error('Fehler beim Setzen der Duplikat-Verweise für Master-UID ' . $masterUid, ['errors' => $updateHandler->errorLog]);
            // Wir brechen hier ab, damit wir nicht versehentlich Datensätze löschen, die nicht korrekt markiert wurden.
            return;
        }

        // --- PHASE 4: Duplikate löschen (2. DataHandler-Lauf: NUR DELETE) ---
        $this->logger->error('    -> Lösche Duplikate...');
        $deleteCmdMap = [];
        foreach ($uidsToDelete as $deletedUid) {
            $deleteCmdMap['tt_address'][(int) $deletedUid]['delete'] = true;
        }

        $deleteHandler = GeneralUtility::makeInstance(DataHandler::class);
        $deleteHandler->start([], $deleteCmdMap);
        $deleteHandler->process_cmdmap();

        if (!empty($deleteHandler->errorLog)) {
            $this->logger->error('Fehler beim Löschen der Duplikate für Master-UID ' . $masterUid, ['errors' => $deleteHandler->errorLog]);
        }
    }

    /**
     * Überträgt Relationen von Duplikaten auf einen Master und verhindert dabei doppelte Einträge,
     * indem jede Relation einzeln geprüft wird.
     *
     * @param string $tableName Die MM- oder FAL-Tabelle
     * @param string $relationKeyColumn Die Spalte, die die ID der Relation selbst enthält (z.B. Kategorie-UID)
     * @param string $recordUidColumn Die Spalte, die die UID des tt_address-Kontakts enthält
     * @param int $masterUid Die UID des Master-Kontakts
     * @param array $uidsToDelete Array mit den UIDs der zu löschenden Kontakte
     */
    private function transferRelations(string $tableName, string $relationKeyColumn, string $recordUidColumn, int $masterUid, array $uidsToDelete): void
    {
        $qb = $this->connectionPool->getQueryBuilderForTable($tableName);
        $connection = $this->connectionPool->getConnectionForTable($tableName);

        // 1. Hole alle Relationen, die der Master bereits hat, in ein Set für schnellen Zugriff.
        $masterRelationsSet = array_flip(
            $qb->select($relationKeyColumn)
                ->from($tableName)
                ->where($qb->expr()->eq($recordUidColumn, $qb->createNamedParameter($masterUid, Connection::PARAM_INT)))
                ->executeQuery()
                ->fetchFirstColumn()
        );

        // 2. Hole alle Relationen von allen Duplikaten (inkl. ihrer eigenen UID in der MM-Tabelle).
        $allDuplicateRelations = $qb->select($relationKeyColumn, $recordUidColumn)
            ->from($tableName)
            ->where($qb->expr()->in($recordUidColumn, $qb->createNamedParameter($uidsToDelete, Connection::PARAM_INT_ARRAY)))
            ->executeQuery()
            ->fetchAllAssociative();

        // 3. Verarbeite jede einzelne Duplikat-Relation.
        foreach ($allDuplicateRelations as $relationRow) {
            $relationId = $relationRow[$relationKeyColumn];
            $originalRecordUid = $relationRow[$recordUidColumn];

            if (isset($masterRelationsSet[$relationId])) {
                // Master already has it, so this relation is redundant. Delete it from the duplicate.
                $connection->delete($tableName, [
                    $recordUidColumn => $originalRecordUid,
                    $relationKeyColumn => $relationId
                ]);
            } else {
                // Master doesn't have it. Re-assign it to the master.
                $connection->update(
                    $tableName,
                    [$recordUidColumn => $masterUid],
                    [$recordUidColumn => $originalRecordUid, $relationKeyColumn => $relationId]
                );
                // And remember that the master now has it for the rest of this run.
                $masterRelationsSet[$relationId] = true;
            }
        }
    }

    /**
     * Findet alle eindeutigen Duplikat-Gruppen (Email+Name) und gibt ihre UIDs zurück.
     */
    private function findDuplicateGroupsByName(): array
    {
        $allGroups = [];
        // Interne
        $qb_internal = $this->connectionPool->getQueryBuilderForTable('tt_address');
        $internalDuplicates = $qb_internal
            ->selectLiteral('GROUP_CONCAT(uid) AS uids')
            ->from('tt_address')
            ->where(
                $qb_internal->expr()->eq('deleted', 0),
                $qb_internal->expr()->neq('email', $qb_internal->createNamedParameter('')),
                $qb_internal->expr()->neq('first_name', $qb_internal->createNamedParameter('')),
                $qb_internal->expr()->neq('last_name', $qb_internal->createNamedParameter('')),
                $qb_internal->expr()->eq('client', 0)
            )
            ->groupBy('email', 'first_name', 'last_name')->having('COUNT(uid) > 1')
            ->executeQuery()->fetchFirstColumn();
        foreach ($internalDuplicates as $groupString) {
            $allGroups[] = GeneralUtility::intExplode(',', $groupString);
        }

        // Pro Kunde
        // BITTE PRÜFEN: Dies ist eine Annahme für den Namen deiner Kundentabelle.
        $clientTableName = 'tx_publicrelations_domain_model_client';
        $clientQueryBuilder = $this->connectionPool->getQueryBuilderForTable('tt_address');
        $clients = $clientQueryBuilder->select('c.uid')->from('tt_address', 't')
            ->join('t', $clientTableName, 'c', $clientQueryBuilder->expr()->eq('t.client', $clientQueryBuilder->quoteIdentifier('c.uid')))
            ->where($clientQueryBuilder->expr()->gt('t.client', 0), $clientQueryBuilder->expr()->eq('t.deleted', 0))
            ->distinct()->executeQuery()->fetchFirstColumn();

        foreach ($clients as $clientId) {
            $qb_client = $this->connectionPool->getQueryBuilderForTable('tt_address');
            $clientDuplicates = $qb_client
                ->selectLiteral('GROUP_CONCAT(uid) AS uids')
                ->from('tt_address')
                ->where(
                    $qb_client->expr()->eq('deleted', 0),
                    $qb_client->expr()->neq('email', $qb_client->createNamedParameter('')),
                    $qb_client->expr()->neq('first_name', $qb_client->createNamedParameter('')),
                    $qb_client->expr()->neq('last_name', $qb_client->createNamedParameter('')),
                    $qb_client->expr()->eq('client', $clientId)
                )
                ->groupBy('email', 'first_name', 'last_name')->having('COUNT(uid) > 1')
                ->executeQuery()->fetchFirstColumn();
            foreach ($clientDuplicates as $groupString) {
                $allGroups[] = GeneralUtility::intExplode(',', $groupString);
            }
        }
        return $allGroups;
    }

    private function createReportByEmail(string $filePrefix): void
    {
        $this->logger->error('Erstelle Report 1: Duplikate nur nach E-Mail...');
        $fullPath = $this->getNewCsvPath($filePrefix);
        if (!$fullPath) {
            return;
        }

        try {
            $fileHandle = fopen($fullPath, 'w');
            fputcsv($fileHandle, ['typ', 'kunde', 'email', 'anzahl', 'uids']);
            $this->findInternalDuplicatesByEmail($fileHandle);
            $this->findClientDuplicatesByEmail($fileHandle);
            fclose($fileHandle);
            $this->logger->error('Report 1 erfolgreich erstellt: ' . basename($fullPath));
        } catch (\Exception $e) {
            $this->logger->critical('Fehler bei Report 1: ' . $e->getMessage());
        }
    }

    private function createReportByEmailAndName(string $filePrefix): void
    {
        $this->logger->error('Erstelle Report 2: Duplikate nach E-Mail, Vor- & Nachname...');
        $fullPath = $this->getNewCsvPath($filePrefix);
        if (!$fullPath) {
            return;
        }

        try {
            $fileHandle = fopen($fullPath, 'w');
            fputcsv($fileHandle, ['typ', 'kunde', 'email', 'first_name', 'last_name', 'anzahl', 'uids']);
            $this->findInternalDuplicatesByName($fileHandle);
            $this->findClientDuplicatesByName($fileHandle);
            fclose($fileHandle);
            $this->logger->error('Report 2 erfolgreich erstellt: ' . basename($fullPath));
        } catch (\Exception $e) {
            $this->logger->critical('Fehler bei Report 2: ' . $e->getMessage());
        }
    }

    /**
     * Findet Duplikate bei Kontakten OHNE zugewiesenen Kunden.
     * @param resource $fileHandle
     */
    private function findInternalDuplicatesByEmail($fileHandle): void
    {
        $this->logger->error('Phase 1: Suche Duplikate bei internen Kontakten (ohne Kunde)...');
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tt_address');

        $duplicates = $queryBuilder
            ->select('email')
            ->addSelectLiteral( // Dann die SQL-Funktionen als Literale hinzufügen
                'COUNT(uid) AS count',
                'GROUP_CONCAT(uid ORDER BY uid) AS uids'
            )
            ->from('tt_address')
            ->where(
                $queryBuilder->expr()->eq('deleted', 0),
                $queryBuilder->expr()->neq('email', $queryBuilder->createNamedParameter('')),
                $queryBuilder->expr()->eq('client', 0) // Die entscheidende Bedingung
            )
            ->groupBy('email')
            ->having($queryBuilder->expr()->gt('count', 1))
            ->executeQuery()
            ->fetchAllAssociative();

        if (empty($duplicates)) {
            $this->logger->error('-> Keine Duplikate bei internen Kontakten gefunden.');
            return;
        }

        $this->logger->error(sprintf('-> %d E-Mail-Adressen mit Duplikaten bei internen Kontakten gefunden.', count($duplicates)));

        foreach ($duplicates as $row) {
            fputcsv($fileHandle, ['intern', '', $row['email'], $row['count'], $row['uids']]);
        }
    }

    private function findInternalDuplicatesByName($fileHandle): void
    {
        $this->logger->error('Phase 1/Name: Suche Duplikate bei internen Kontakten...');
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tt_address');
        $duplicates = $queryBuilder
            ->select('email', 'first_name', 'last_name') // NEU: Namen auswählen
            ->addSelectLiteral('COUNT(uid) AS count', 'GROUP_CONCAT(uid) AS uids')
            ->from('tt_address')
            ->where(
                $queryBuilder->expr()->eq('deleted', 0),
                $queryBuilder->expr()->neq('email', $queryBuilder->createNamedParameter('')),
                $queryBuilder->expr()->neq('first_name', $queryBuilder->createNamedParameter('')), // NEU
                $queryBuilder->expr()->neq('last_name', $queryBuilder->createNamedParameter('')),  // NEU
                $queryBuilder->expr()->eq('client', 0)
            )
            ->groupBy('email', 'first_name', 'last_name') // NEU: Nach allen drei Feldern gruppieren
            ->having('COUNT(uid) > 1')
            ->executeQuery()
            ->fetchAllAssociative();

        if (empty($duplicates)) {
            $this->logger->error('-> Keine Namens-Duplikate bei internen Kontakten gefunden.');
            return;
        }
        $this->logger->error(sprintf('-> %d Namens-Duplikate bei internen Kontakten gefunden.', count($duplicates)));
        foreach ($duplicates as $row) {
            fputcsv($fileHandle, ['intern', '', $row['email'], $row['first_name'], $row['last_name'], $row['count'], $row['uids']]);
        }
    }

    /**
     * Finds duplicates per client and writes them to the CSV.
     * @param resource $fileHandle
     */
    private function findClientDuplicatesByEmail($fileHandle): void
    {
        $this->logger->error('Phase 2: Suche Duplikate pro Kunde...');

        // KORREKTUR: Ersetze die alten Abfragen durch einen JOIN, um den echten Kundennamen zu holen.
        $clientQueryBuilder = $this->connectionPool->getQueryBuilderForTable('tt_address');

        // WICHTIGE ANNAHME: Bitte den Tabellennamen für deine Kunden hier prüfen und ggf. anpassen!
        $clientTableName = 'tx_publicrelations_domain_model_client';

        $clients = $clientQueryBuilder
            ->select('c.uid', 'c.name')
            ->from('tt_address', 't')
            ->join(
                't',
                $clientTableName,
                'c',
                $clientQueryBuilder->expr()->eq('t.client', $clientQueryBuilder->quoteIdentifier('c.uid'))
            )
            ->where(
                $clientQueryBuilder->expr()->gt('t.client', 0),
                $clientQueryBuilder->expr()->eq('t.deleted', 0)
            )
            ->distinct()
            ->executeQuery()
            ->fetchAllKeyValue();

        if (empty($clients)) {
            $this->logger->error('-> Keine Kontakte mit Kunden-Zuweisung gefunden.');
            return;
        }

        // Schritt 3 aus dem vorigen Code: Gehe jeden Kunden durch und suche nach Duplikaten.
        // Dieser Teil bleibt gleich, da er bereits die korrekte $clients-Map erwartet.
        foreach ($clients as $clientId => $clientName) {
            $clientLabel = sprintf('%s [%d]', $clientName, $clientId);
            $this->logger->error(sprintf('--> Prüfe Kunde: %s', $clientLabel));

            $clientDuplicatesQb = $this->connectionPool->getQueryBuilderForTable('tt_address');
            $duplicates = $clientDuplicatesQb
                ->select('email')
                ->addSelectLiteral(
                    'COUNT(uid) AS count',
                    'GROUP_CONCAT(uid ORDER BY uid) AS uids'
                )
                ->from('tt_address')
                ->where(
                    $clientDuplicatesQb->expr()->eq('deleted', 0),
                    $clientDuplicatesQb->expr()->neq('email', $clientDuplicatesQb->createNamedParameter('')),
                    $clientDuplicatesQb->expr()->eq('client', $clientId) // Nur für diesen einen Kunden
                )
                ->groupBy('email')
                ->having($clientDuplicatesQb->expr()->gt('count', 1))
                ->executeQuery()
                ->fetchAllAssociative();

            if (empty($duplicates)) {
                $this->logger->error('    Keine Duplikate für diesen Kunden gefunden.');
                continue;
            }

            $this->logger->error(sprintf('    %d E-Mail-Adressen mit Duplikaten für diesen Kunden gefunden.', count($duplicates)));

            foreach ($duplicates as $row) {
                fputcsv($fileHandle, ['kundenkontakt', $clientLabel, $row['email'], $row['count'], $row['uids']]);
            }
        }
    }

    private function findClientDuplicatesByName($fileHandle): void
    {
        $this->logger->error('Phase 2/Name: Suche Duplikate pro Kunde...');
        $clientQueryBuilder = $this->connectionPool->getQueryBuilderForTable('tt_address');

        // BITTE PRÜFEN: Dies ist eine Annahme für den Namen deiner Kundentabelle.
        $clientTableName = 'tx_publicrelations_domain_model_client';

        // Schritt 1: Hole alle Kunden (UID und Name), die Kontakten zugeordnet sind.
        $clients = $clientQueryBuilder
            ->select('c.uid', 'c.name')
            ->from('tt_address', 't')
            ->join(
                't',
                $clientTableName,
                'c',
                $clientQueryBuilder->expr()->eq('t.client', $clientQueryBuilder->quoteIdentifier('c.uid'))
            )
            ->where(
                $clientQueryBuilder->expr()->gt('t.client', 0),
                $clientQueryBuilder->expr()->eq('t.deleted', 0)
            )
            ->distinct()
            ->executeQuery()
            ->fetchAllKeyValue();

        if (empty($clients)) {
            $this->logger->error('-> Keine Kontakte mit Kunden-Zuweisung gefunden.');
            return;
        }

        // Schritt 2: Gehe jeden Kunden durch und suche nach Duplikaten.
        foreach ($clients as $clientId => $clientName) {
            $clientLabel = sprintf('%s [%d]', $clientName, $clientId);
            $this->logger->error(sprintf('--> Prüfe Kunde (Name): %s', $clientLabel));

            $clientDuplicatesQb = $this->connectionPool->getQueryBuilderForTable('tt_address');
            $duplicates = $clientDuplicatesQb
                ->select('email', 'first_name', 'last_name')
                ->addSelectLiteral(
                    'COUNT(uid) AS count',
                    'GROUP_CONCAT(uid) AS uids'
                )
                ->from('tt_address')
                ->where(
                    $clientDuplicatesQb->expr()->eq('deleted', 0),
                    $clientDuplicatesQb->expr()->neq('email', $clientDuplicatesQb->createNamedParameter('')),
                    $clientDuplicatesQb->expr()->neq('first_name', $clientDuplicatesQb->createNamedParameter('')),
                    $clientDuplicatesQb->expr()->neq('last_name', $clientDuplicatesQb->createNamedParameter('')),
                    $clientDuplicatesQb->expr()->eq('client', $clientId)
                )
                ->groupBy('email', 'first_name', 'last_name')
                ->having('COUNT(uid) > 1')
                ->executeQuery()
                ->fetchAllAssociative();

            if (empty($duplicates)) {
                $this->logger->error('    Keine Namens-Duplikate für diesen Kunden gefunden.');
                continue;
            }

            $this->logger->error(sprintf('    %d Namens-Duplikate für diesen Kunden gefunden.', count($duplicates)));

            foreach ($duplicates as $row) {
                fputcsv($fileHandle, [
                    'kundenkontakt',
                    $clientLabel,
                    $row['email'],
                    $row['first_name'],
                    $row['last_name'],
                    $row['count'],
                    $row['uids']
                ]);
            }
        }
    }

    private function getNewCsvPath(string $prefix): ?string
    {
        $fileadminPath = $this->environment->getPublicPath() . '/fileadmin/';
        if (!is_dir($fileadminPath) && !mkdir($fileadminPath, 0775, true)) {
            $this->logger->critical('fileadmin-Verzeichnis existiert nicht und konnte nicht erstellt werden.');
            return null;
        }
        $fileName = $prefix . date('Y-m-d_H-i-s') . '.csv';
        return $fileadminPath . $fileName;
    }
}