<?php

declare(strict_types=1);

namespace BucheggerOnline\Publicrelations\Service;

use BucheggerOnline\Publicrelations\Domain\Repository\AccreditationRepository;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Core\Database\Connection;

/**
 * Service zum Auflösen von Akkreditierungen in Kontakte für den Transfer.
 * Prüft auf Opt-Outs, Pausen, Mandanten-Berechtigungen und Duplikate.
 */
class AccreditationSelectionService
{
    public function __construct(
        private readonly AccreditationRepository $accreditationRepository,
        private readonly PersistenceManager $persistenceManager
    ) {
    }

    /**
     * Löst eine Auswahl an Akkreditierungen in Kontakte auf und prüft Berechtigungen.
     * * @param array $filters Filterkriterien aus dem Wizard
     * @param string|null $dupTable Tabelle für den Duplikat-Check
     * @param string|null $dupField Feld in der Zieltabelle
     * @param array $dupScope Zusätzliche WHERE Bedingungen
     * @param array $allowedClientUids Liste der erlaubten Client-UIDs (Mandanten-Security)
     */
    public function resolveSelection(
        array $filters,
        ?string $dupTable,
        ?string $dupField,
        array $dupScope,
        array $allowedClientUids = []
    ): array {
        // 1. Akkreditierungen finden
        $accreditations = $this->findFiltered($filters);

        // Security: Intern (0) ist immer erlaubt
        $allowedClientUids[] = 0;
        $allowedClientUids = array_unique(array_map('intval', $allowedClientUids));

        $contacts = [];
        $seenUids = [];
        $now = time();

        // 2. Kontakte extrahieren und anreichern
        foreach ($accreditations as $accreditation) {
            $guest = $accreditation->getGuest();
            if ($guest === null) {
                continue;
            }

            $uid = (int) $guest->getUid();
            if (isset($seenUids[$uid])) {
                continue;
            }

            $statusLabel = $this->getStatusLabel($accreditation->getStatus());
            $eventTitle = $accreditation->getEvent() ? $accreditation->getEvent()->getTitle() : 'Unbekanntes Event';

            // A. Mandanten-Check (Security)

            // A. Mandanten-Check (Security)
            $guestClient = $guest->getClient();
            $guestClientUid = is_object($guestClient) ? (int) $guestClient->getUid() : (int) $guestClient;

            $isIllegal = !in_array($guestClientUid, $allowedClientUids, true);

            // B. Check auf Blockierung (Opt-Out / Banned)
            $isBlocked = (
                (bool) $guest->isNoMailing() === true ||
                (bool) $guest->isBanned() === true
                // || ($guest->getPause() && $guest->getPause()->getTimestamp() > $now)
            );

            $contacts[] = [
                'uid' => $uid,
                'first_name' => $guest->getFirstName(),
                'last_name' => $guest->getLastName(),
                'email' => $guest->getEmail(),
                'company' => $guest->getCompany(),
                'info' => substr($eventTitle, 0, 30) . '... [' . $statusLabel . ']',
                'is_illegal' => $isIllegal,
                'is_blocked' => $isBlocked,
                'is_duplicate' => false // Initialer Wert
            ];
            $seenUids[$uid] = true;
        }

        $stats = [
            'total' => 0,
            'blocked' => 0,
            'duplicates' => 0,
            'illegal' => 0
        ];

        // 3. Duplikats-Prüfung gegen Mailing (wenn Config vorhanden)
        $existingMap = [];
        if ($dupTable && $dupField && !empty($contacts)) {
            $uids = array_column($contacts, 'uid');
            $qb = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($dupTable);

            $qb->select($dupField)
                ->from($dupTable)
                ->where(
                    $qb->expr()->in($dupField, $qb->createNamedParameter($uids, Connection::PARAM_INT_ARRAY)),
                    $qb->expr()->eq('deleted', $qb->createNamedParameter(0, Connection::PARAM_INT))
                );

            foreach ($dupScope as $field => $value) {
                $qb->andWhere($qb->expr()->eq($field, $qb->createNamedParameter($value, Connection::PARAM_INT)));
            }

            $existing = $qb->executeQuery()->fetchFirstColumn();
            $existingMap = array_flip(array_map('intval', $existing));
        }

        // 4. Finale Statistik-Berechnung
        foreach ($contacts as &$contact) {
            $contact['is_duplicate'] = isset($existingMap[$contact['uid']]);

            if ($contact['is_illegal']) {
                $stats['illegal']++;
            } elseif ($contact['is_blocked']) {
                $stats['blocked']++;
            } elseif ($contact['is_duplicate']) {
                $stats['duplicates']++;
            } else {
                $stats['total']++;
            }
        }

        return ['contacts' => $contacts, 'stats' => $stats];
    }

    private function findFiltered(array $filter): iterable
    {
        $query = $this->accreditationRepository->createQuery();
        $constraints = [];

        $eventId = (int) ($filter['event'] ?? 0);
        if ($eventId > 0) {
            $constraints[] = $query->equals('event', $eventId);
        }

        $statusInput = $filter['status'] ?? [];
        if (is_string($statusInput) && $statusInput !== '') {
            $statusInput = [$statusInput];
        }
        if (!empty($statusInput)) {
            $statusList = array_map('intval', $statusInput);
            $constraints[] = $query->in('status', $statusList);
        }

        $guestType = $filter['guestType'] ?? '';
        if ($guestType !== '') {
            $constraints[] = $query->equals('guestType', (int) $guestType);
        }

        if (!empty($constraints)) {
            $query->matching($query->logicalAnd(...$constraints));
        }

        return $query->execute();
    }

    private function getStatusLabel(int $status): string
    {
        return match ($status) {
            0 => 'Ausstehend',
            1 => 'Akkreditiert',
            2 => 'Eingecheckt',
            -1 => 'Abgelehnt',
            -2 => 'Warteliste',
            default => (string) $status
        };
    }
}