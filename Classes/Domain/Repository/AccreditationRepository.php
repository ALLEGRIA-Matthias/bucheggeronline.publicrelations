<?php
namespace BucheggerOnline\Publicrelations\Domain\Repository;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use BucheggerOnline\Publicrelations\Domain\Model\Event;


/***
 *
 * This file is part of the "Public Relations" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2020 Matthias Buchegger <matthias@buchegger.online>, Multimediaagentur Matthias Buchegger
 *
 ***/
/**
 * The repository for Accreditations
 */
class AccreditationRepository extends \TYPO3\CMS\Extbase\Persistence\Repository
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

    public function findByUid($uid, $respectEnableFields = true)
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setIgnoreEnableFields(!$respectEnableFields);

        return $query->matching(
            $query->logicalAnd(
                $query->equals('uid', $uid),
                $query->equals('deleted', 0)
            )
        )->execute()->getFirst();
    }

    /**
     * Findet Datensätze anhand eines UID-Arrays.
     * Effizienter als die magische findByUids() Methode.
     *
     * @param int[] $uids Array von UIDs
     * @return QueryResultInterface|array
     */
    public function findByUidsArray(array $uids): QueryResultInterface|array
    {
        // EFFIZIENZ: Leeres IN() Statement vermeiden, das crasht.
        if (empty($uids)) {
            // Schnellster Weg: Leeres Array zurückgeben.
            // Alternativ: new \TYPO3\CMS\Extbase\Persistence\Generic\QueryResult(true);
            return [];
        }

        $query = $this->createQuery();

        // Die Kernlogik: WHERE uid IN (...)
        $query->matching(
            $query->in('uid', $uids)
        );

        return $query->execute();
    }

    /**
     * Findet die UIDs von Gästen (TtAddress UIDs), die bereits eine Akkreditierung
     * für ein spezifisches Event haben.
     *
     * @param array $guestUids Array von TtAddress UIDs (potenzielle Gäste)
     * @param int $eventUid UID des Events
     * @return array Ein Array, das nur die UIDs derjenigen Gäste aus $guestUids enthält,
     * die bereits für das angegebene Event akkreditiert sind.
     */
    public function findGuestUidsByEvent(array $guestUids, int $eventUid): array
    {
        if (empty($guestUids)) {
            return []; // Keine Gäste-UIDs zum Prüfen vorhanden
        }

        $query = $this->createQuery();

        $constraintEvent = $query->equals('event.uid', $eventUid);
        $constraintGuests = $query->in('guest.uid', $guestUids);

        // KORREKTUR HIER:
        // Übergeben Sie die Constraint-Objekte direkt als Argumente an logicalAnd.
        // Wenn Sie ein Array von Constraints haben, können Sie den Spread-Operator (...) verwenden.
        $query->matching(
            $query->logicalAnd(
                $constraintEvent,
                $constraintGuests
            )
        );
        // Die obige Zeile ist die, die wahrscheinlich Zeile 68 in Ihrer Datei war.

        // Führt die Abfrage aus und holt die vollständigen Accreditation-Objekte
        $accreditationObjects = $query->execute();

        $foundGuestUids = [];
        foreach ($accreditationObjects as $accreditation) {
            /** @var Accreditation $accreditation */
            if ($accreditation->getGuest()) { // Sicherstellen, dass ein Gastobjekt vorhanden ist
                $foundGuestUids[] = $accreditation->getGuest()->getUid();
            }
        }

        // Gibt nur die eindeutigen Gast-UIDs zurück, die gefunden wurden.
        return array_unique($foundGuestUids);
    }

    public function findBy(array $criteria, array $orderBy = null, int $limit = null, int $offset = null): QueryResultInterface
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);
        $constraints = [];
        foreach ($criteria as $propertyName => $propertyValue) {
            $constraints[] = $query->equals($propertyName, $propertyValue);
        }

        if (($numberOfConstraints = count($constraints)) === 1) {
            $query->matching(...$constraints);
        } elseif ($numberOfConstraints > 1) {
            $query->matching($query->logicalAnd(...$constraints));
        }

        if (is_array($orderBy)) {
            $query->setOrderings($orderBy);
        }

        if (is_int($limit)) {
            $query->setLimit($limit);
        }

        if (is_int($offset)) {
            $query->setOffset($offset);
        }

        return $query->execute();
    }

    public function findOneBy(array $criteria, ?array $orderBy = null): ?object
    {
        return $this->findBy($criteria, $orderBy, 1)->getFirst();
    }

    public function count(array $criteria): int
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);
        $constraints = [];

        foreach ($criteria as $propertyName => $propertyValue) {
            $constraints[] = $query->equals($propertyName, $propertyValue);
        }

        if (count($constraints) === 1) {
            $query->matching(...$constraints);
        } elseif (count($constraints) > 1) {
            $query->matching($query->logicalAnd(...$constraints));
        }

        // Führt "SELECT COUNT(* FROM ...)" aus. Super schnell.
        return $query->count();
    }

    public function findGuestByEvent($guest, $event): ?object
    {
        $query = $this->createQuery();

        return $query->matching(
            $query->logicalAnd(
                $query->equals('event', $event),
                $query->equals('guest', $guest)
            )
        )->execute()->getFirst();
    }

    public function findGuestsByEvent($event): QueryResultInterface
    {
        $query = $this->createQuery();

        return $query->matching(
            $query->logicalAnd(
                $query->equals('event', $event),
                $query->greaterThan('status', 0),
                $query->lessThan('status', 9)
            )
        )->execute();
    }

    public function findPendingByEvent($event): QueryResultInterface
    {
        $query = $this->createQuery();

        return $query->matching(
            $query->logicalAnd(
                $query->equals('event', $event),
                $query->equals('status', 0),
                $query->equals('type', 2)
            )
        )->execute();
    }

    public function findWaitingByEvent($event): QueryResultInterface
    {
        $query = $this->createQuery();

        return $query->matching(
            $query->logicalAnd(
                $query->equals('event', $event),
                $query->equals('status', -2)
            )
        )->execute();
    }

    public function findAllPending(): QueryResultInterface
    {
        $query = $this->createQuery();

        return $query->matching(
            $query->logicalOr(
                $query->logicalAnd(
                    $query->equals('status', 0),
                    $query->equals('type', 1)
                ),
                $query->logicalAnd(
                    $query->equals('status', -2),
                    $query->equals('type', 2)
                )
            )
        )->execute();
    }

    public function findRejectedByEvent($event): QueryResultInterface
    {
        $query = $this->createQuery();

        return $query->matching(
            $query->logicalAnd(
                $query->equals('event', $event),
                $query->equals('status', -1)
            )
        )->execute();
    }

    public function findErrorsByEvent($event): QueryResultInterface
    {
        $query = $this->createQuery();

        return $query->matching(
            $query->logicalAnd(
                $query->equals('event', $event),
                $query->equals('status', 99)
            )
        )->execute();
    }

    public function findDuplicatesByEvent($event): QueryResultInterface
    {
        $query = $this->createQuery();

        $query->matching(
            $query->logicalAnd(
                $query->equals('event', $event),
                $query->equals('deleted', 0),
                $query->equals('status', 9),
                // $query->logicalOr(
                //     $query->equals('status', 9),
                //     $query->greaterThan('duplicate_of', 0)
                // )
            )
        );

        return $query->execute();
    }

    /**
     * findFiltered method
     */
    public function findFiltered(array $filter = []): QueryResultInterface
    {
        $query = $this->createQuery();
        $constraints = [];

        // 1) Event‐Filter (nur, wenn > 0)
        $eventId = (int) ($filter['event'] ?? 0);
        if ($eventId > 0) {
            $constraints[] = $query->equals('event', $eventId);
        }

        // 2) guestType
        $guestType = $filter['guestType'] ?? '';
        if ($guestType !== '') {
            $constraints[] = $query->equals('guestType', (int) $guestType);
        }

        // 3) facie
        $facie = $filter['facie'] ?? '';
        if ($facie !== '') {
            // Fall 1: Der User will NUR FACIES sehen.
            if ((int) $facie === 1) {
                $constraints[] = $query->logicalOr(
                    // Entweder das Flag ist manuell gesetzt...
                    $query->equals('facie', 1),
                    // ...oder der Gast hat den entsprechenden ContactType.
                    $query->contains('guest.contactTypes', 604)
                );
            }

            // Fall 2: Der User will NUR NICHT-FACIES sehen.
            if ((int) $facie === 0) {
                $constraints[] = $query->logicalAnd(
                    // Das Flag darf NICHT gesetzt sein...
                    $query->equals('facie', 0),
                    // ...UND der Gast darf den ContactType NICHT haben.
                    $query->logicalNot(
                        $query->contains('guest.contactTypes', 604)
                    )
                );
            }
        }

        // 4) pass (0 = equals, sonst >=)
        $pass = $filter['pass'] ?? '';
        if ($pass !== '') {
            if ($pass === '0') {
                $constraints[] = $query->equals('pass', 0);
            } else {
                $constraints[] = $query->greaterThanOrEqual('pass', (int) $pass);
            }
        }

        // 5) program (analog zu pass)
        $program = $filter['program'] ?? '';
        if ($program !== '') {
            if ($program === '0') {
                $constraints[] = $query->equals('program', 0);
            } else {
                $constraints[] = $query->greaterThanOrEqual('program', (int) $program);
            }
        }

        // 6) status (1 = status 1 oder 2; 3 = nur 1; sonst equals)
        $status = $filter['status'] ?? '';
        if ($status === '1') {
            $constraints[] = $query->logicalOr(
                $query->equals('status', 1),
                $query->equals('status', 2)
            );
        } elseif ($status === '3') {
            $constraints[] = $query->equals('status', 1);
        } elseif ($status !== '') {
            $constraints[] = $query->equals('status', (int) $status);
        }

        // 7) invitationStatus
        $invitationStatus = $filter['invitationStatus'] ?? '';
        if ($invitationStatus !== '') {
            $constraints[] = $query->equals('invitationStatus', (int) $invitationStatus);
        }

        // 8) invitationType
        $invitationType = $filter['invitationType'] ?? '';
        if ($invitationType !== '') {
            $constraints[] = $query->equals('invitationType', (int) $invitationType);
        }

        // 9) tickets als LIKE-String
        $tickets = $filter['tickets'] ?? '';
        if ($tickets !== '') {
            $constraints[] = $query->like('tickets', '%' . $tickets . '%');
        }

        // 10) Freitext‐Suche über mehrere Felder
        $search = trim($filter['query'] ?? '');
        if ($search !== '') {
            $orConstraints = [
                $query->like('guest.name', '%' . $search . '%'),
                $query->like('guest.firstName', '%' . $search . '%'),
                $query->like('guest.middleName', '%' . $search . '%'),
                $query->like('guest.lastName', '%' . $search . '%'),
                $query->like('guest.company', '%' . $search . '%'),
                $query->like('guest.email', '%' . $search . '%'),
                $query->like('guest.phone', '%' . $search . '%'),
                $query->like('guest.mobile', '%' . $search . '%'),
                $query->like('firstName', '%' . $search . '%'),
                $query->like('middleName', '%' . $search . '%'),
                $query->like('lastName', '%' . $search . '%'),
                $query->like('medium', '%' . $search . '%'),
                $query->like('email', '%' . $search . '%'),
                $query->like('phone', '%' . $search . '%'),
                $query->like('notes', '%' . $search . '%'),
                $query->like('uid', '%' . $search . '%'),
            ];
            $constraints[] = $query->logicalOr(...$orConstraints);
        }

        // 11) Matching nur, wenn es überhaupt Constraints gibt
        if (count($constraints) > 0) {
            $query->matching(
                $query->logicalAnd(...$constraints)
            );
        }

        return $query->execute();
    }

    public function findByEventAndSearchTerm(Event $event, ?string $searchTerm = null)
    {
        $query = $this->createQuery();

        // 1. Basiskriterien: Richtiges Event UND Status >= 1
        $baseConstraints = [
            $query->equals('event', $event->getUid()),
            $query->greaterThanOrEqual('status', 1) // NEU: Status muss 1 oder größer sein
        ];
        $mainEventAndStatusConstraint = $query->logicalAnd(...$baseConstraints);

        // 2. Suchbedingungen (optional)
        $searchConstraintsGroup = null;
        if ($searchTerm !== null && trim($searchTerm) !== '') {
            $termConstraints = [];
            $searchTermLike = '%' . trim($searchTerm) . '%';

            $termConstraints[] = $query->like('uid', $searchTermLike);
            $termConstraints[] = $query->like('firstName', $searchTermLike);
            $termConstraints[] = $query->like('middleName', $searchTermLike);
            $termConstraints[] = $query->like('lastName', $searchTermLike);
            $termConstraints[] = $query->like('medium', $searchTermLike);
            $termConstraints[] = $query->like('email', $searchTermLike);
            $termConstraints[] = $query->like('phone', $searchTermLike);
            $termConstraints[] = $query->like('guest.name', $searchTermLike);
            $termConstraints[] = $query->like('guest.firstName', $searchTermLike);
            $termConstraints[] = $query->like('guest.middleName', $searchTermLike);
            $termConstraints[] = $query->like('guest.lastName', $searchTermLike);
            $termConstraints[] = $query->like('guest.company', $searchTermLike);
            $termConstraints[] = $query->like('guest.email', $searchTermLike);
            $termConstraints[] = $query->like('guest.phone', $searchTermLike);
            $termConstraints[] = $query->like('guest.mobile', $searchTermLike);
            $termConstraints[] = $query->like('notes', $searchTermLike);
            $termConstraints[] = $query->like('notesReceived', $searchTermLike);

            if (is_numeric($searchTerm)) {
                $termConstraints[] = $query->equals('uid', (int) $searchTerm);
            }

            if (!empty($termConstraints)) {
                // KORREKTUR: Spread-Operator verwenden, um das Array zu entpacken
                $searchConstraintsGroup = $query->logicalOr(...$termConstraints);
            }
        }

        // 3. Alle Bedingungen kombinieren
        if ($searchConstraintsGroup !== null) {
            // Event+Status MUSS stimmen UND (mindestens eine Suchbedingung muss stimmen)
            $query->matching(
                $query->logicalAnd( // Explizites AND für die Hauptgruppen
                    $mainEventAndStatusConstraint,
                    $searchConstraintsGroup
                )
            );
        } else {
            // Nur nach Event und Status filtern, wenn kein Suchbegriff vorhanden ist
            $query->matching($mainEventAndStatusConstraint);
        }

        return $query->execute();
    }

    public function countByEventAndSearchTerm(Event $event, ?string $searchTerm = null): int
    {
        $query = $this->createQuery();
        $eventConstraint = $query->equals('event', $event->getUid());
        $searchConstraintsGroup = null;

        if ($searchTerm !== null && trim($searchTerm) !== '') {
            $termConstraints = [];
            $searchTermLike = '%' . trim($searchTerm) . '%';
            $termConstraints[] = $query->like('firstName', $searchTermLike);
            $termConstraints[] = $query->like('lastName', $searchTermLike);
            $termConstraints[] = $query->like('middleName', $searchTermLike);
            $termConstraints[] = $query->like('email', $searchTermLike);
            $termConstraints[] = $query->like('medium', $searchTermLike);
            $termConstraints[] = $query->like('notes', $searchTermLike);
            $termConstraints[] = $query->like('notesReceived', $searchTermLike);
            if (is_numeric($searchTerm)) {
                $termConstraints[] = $query->equals('uid', (int) $searchTerm);
            }

            if (!empty($termConstraints)) {
                $searchConstraintsGroup = $query->logicalOr(...$termConstraints);
            }
        }

        if ($searchConstraintsGroup !== null) {
            $query->matching($query->logicalAnd($eventConstraint, $searchConstraintsGroup));
        } else {
            $query->matching($eventConstraint);
        }
        return $query->count();
    }

    public function countByEvent(Event $event): int
    {
        $query = $this->createQuery();
        $query->matching($query->equals('event', $event->getUid()));
        return $query->count();
    }

    public function findFaciesByEvent(Event $event)
    {
        $query = $this->createQuery();
        $query->matching(
            $query->logicalAnd(
                // Bedingung 1: Muss zum richtigen Event gehören
                $query->equals('event', $event->getUid()),

                // Bedingung 2: EINER der folgenden Punkte muss zutreffen
                $query->logicalOr(
                    // A) Das "facie"-Flag ist manuell in der DB gesetzt
                    $query->equals('facie', 1),

                    // ODER

                    // B) Der verknüpfte Gast (guest) hat in seiner Relation "contactTypes"
                    //    einen Eintrag mit der UID 604.
                    $query->contains('guest.contactTypes', 604)
                )
            )
        );
        return $query->execute();
    }

    public function findCheckedinFaciesByEvent(Event $event)
    {
        $query = $this->createQuery();
        $query->matching(
            $query->logicalAnd(
                // Bedingung 1: Muss zum richtigen Event gehören & es ausgegebene Tickets gibt
                $query->equals('event', $event->getUid()),
                $query->greaterThan('ticketsReceived', 0),

                // Bedingung 2: EINER der folgenden Punkte muss zutreffen
                $query->logicalOr(
                    // A) Das "facie"-Flag ist manuell in der DB gesetzt
                    $query->equals('facie', 1),

                    // ODER

                    // B) Der verknüpfte Gast (guest) hat in seiner Relation "contactTypes"
                    //    einen Eintrag mit der UID 604.
                    $query->contains('guest.contactTypes', 604)
                )
            )
        );
        return $query->execute();
    }

    /**
     * Finds all accreditations for an event and returns a simplified data array for duplicate checking.
     *
     * @param \BucheggerOnline\Publicrelations\Domain\Model\Event $event
     * @return array<int, array<string, mixed>> Returns an associative array of all accreditation data.
     */
    public function findForDuplicateCheck(Event $event): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_publicrelations_domain_model_accreditation');

        $accreditations = $queryBuilder
            ->select(
                'tx_publicrelations_domain_model_accreditation.uid',
                'tx_publicrelations_domain_model_accreditation.email',
                'tx_publicrelations_domain_model_accreditation.first_name',
                'tx_publicrelations_domain_model_accreditation.middle_name',
                'tx_publicrelations_domain_model_accreditation.last_name',
                'tx_publicrelations_domain_model_accreditation.status',
                'tx_publicrelations_domain_model_accreditation.invitation_status',
                'tx_publicrelations_domain_model_accreditation.duplicate_of',
                'tx_publicrelations_domain_model_accreditation.is_master',
                'tx_publicrelations_domain_model_accreditation.ignored_duplicates',
                'tx_publicrelations_domain_model_accreditation.guest',
                'tx_publicrelations_domain_model_accreditation.crdate',
                'tt_address.email AS guest_email',
                'tt_address.first_name AS guest_first_name',
                'tt_address.middle_name AS guest_middle_name',
                'tt_address.last_name AS guest_last_name',
                'tt_address.company AS guest_company',
                'tt_address.client AS guest_client',
            )
            ->from('tx_publicrelations_domain_model_accreditation')
            ->leftJoin(
                'tx_publicrelations_domain_model_accreditation',
                'tt_address',
                'tt_address',
                $queryBuilder->expr()->eq(
                    'tx_publicrelations_domain_model_accreditation.guest',
                    $queryBuilder->quoteIdentifier('tt_address.uid')
                )
            )
            ->where(
                $queryBuilder->expr()->and(
                    $queryBuilder->expr()->eq(
                        'tx_publicrelations_domain_model_accreditation.event',
                        $queryBuilder->createNamedParameter($event->getUid(), Connection::PARAM_INT)
                    ),
                    $queryBuilder->expr()->eq(
                        'tx_publicrelations_domain_model_accreditation.deleted',
                        $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)
                    )
                )
            )
            ->orderBy('tx_publicrelations_domain_model_accreditation.crdate', 'ASC')
            ->executeQuery()
            ->fetchAllAssociative();

        return $accreditations;
    }

    /**
     * Finds duplicates for one or more accreditations within an event.
     *
     * @param \BucheggerOnline\Publicrelations\Domain\Model\Event $event
     * @param \TYPO3\CMS\Extbase\Persistence\QueryResultInterface|\BucheggerOnline\Publicrelations\Domain\Model\Accreditation $accreditationsToValidate
     * @return array<int, array<string, mixed>> Returns an associative array of found duplicates.
     */
    public function findDuplicates(Event $event, $accreditationsToValidate): array
    {
        $query = $this->createQuery();
        $constraints = [];

        // Nur innerhalb des gleichen Events suchen
        $constraints[] = $query->equals('event', $event->getUid());

        // Finde Duplikate basierend auf dem Modus (Einzelprüfung vs. Massenprüfung)
        $orConstraints = [];
        $validationUids = [];

        // Modus 1: Einzelne Akkreditierung prüfen
        if ($accreditationsToValidate instanceof \BucheggerOnline\Publicrelations\Domain\Model\Accreditation) {
            $accreditation = $accreditationsToValidate;
            $validationUids[] = $accreditation->getUid();

            $email = trim(strtolower($accreditation->getEmail() ?: $accreditation->getGuest()?->getEmail() ?? ''));
            $firstName = trim(strtolower($accreditation->getFirstName() ?: $accreditation->getGuest()?->getFirstName() ?? ''));
            $lastName = trim(strtolower($accreditation->getLastName() ?: $accreditation->getGuest()?->getLastName() ?? ''));

            if ($email !== '') {
                $orConstraints[] = $query->logicalOr(
                    $query->equals('email', $email),
                    $query->equals('guest.email', $email)
                );
            }
            if ($firstName !== '' && $lastName !== '') {
                $orConstraints[] = $query->logicalAnd(
                    $query->equals('firstName', $firstName),
                    $query->equals('lastName', $lastName)
                );
            }
        }
        // Modus 2: Massenprüfung (z.B. nach einem Import)
        elseif ($accreditationsToValidate instanceof \TYPO3\CMS\Extbase\Persistence\QueryResultInterface) {
            foreach ($accreditationsToValidate as $accr) {
                /** @var \BucheggerOnline\Publicrelations\Domain\Model\Accreditation $accr */
                $validationUids[] = $accr->getUid();

                $email = trim(strtolower($accr->getEmail() ?: $accr->getGuest()?->getEmail() ?? ''));
                $firstName = trim(strtolower($accr->getFirstName() ?: $accr->getGuest()?->getFirstName() ?? ''));
                $lastName = trim(strtolower($accr->getLastName() ?: $accr->getGuest()?->getLastName() ?? ''));

                if ($email !== '') {
                    $orConstraints[] = $query->logicalOr(
                        $query->equals('email', $email),
                        $query->equals('guest.email', $email)
                    );
                }
                if ($firstName !== '' && $lastName !== '') {
                    $orConstraints[] = $query->logicalAnd(
                        $query->equals('firstName', $firstName),
                        $query->equals('lastName', $lastName)
                    );
                }
            }
        }

        // Wenn keine Suchkriterien vorhanden sind, leere Liste zurückgeben
        if (empty($orConstraints)) {
            return [];
        }

        $constraints[] = $query->logicalOr(...$orConstraints);

        // Die zu validierenden Datensätze von der Suche ausschließen
        $constraints[] = $query->logicalNot($query->in('uid', $validationUids));

        $query->matching($query->logicalAnd(...$constraints));

        // Die Ergebnisse sammeln und in einem strukturierten Array zurückgeben
        $duplicates = [];
        foreach ($query->execute() as $duplicate) {
            $duplicates[] = [
                'uid' => $duplicate->getUid(),
                'email' => trim(strtolower($duplicate->getEmail() ?: $duplicate->getGuest()?->getEmail() ?? '')),
                'firstName' => trim(strtolower($duplicate->getFirstName() ?: $duplicate->getGuest()?->getFirstName() ?? '')),
                'lastName' => trim(strtolower($duplicate->getLastName() ?: $duplicate->getGuest()?->getLastName() ?? ''))
            ];
        }

        return $duplicates;
    }

    /**
     * Retrieves all accreditation records that are duplicates of a given master UID.
     *
     * @param int $masterUid The UID of the master record.
     * @return \TYPO3\CMS\Extbase\Persistence\QueryResultInterface<\BucheggerOnline\Publicrelations\Domain\Model\Accreditation>
     */
    public function findByDuplicateOf(int $masterUid)
    {
        $query = $this->createQuery();
        $query->matching(
            $query->equals('duplicate_of', $masterUid)
        );

        return $query->execute();
    }


    /**
     * Findet alle akkreditierten Gäste (Status 1 oder 2) für ein Event.
     */
    public function feFindByEvent(int $eventUid, string $search = '', string $statusFilter = 'accredited'): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_publicrelations_domain_model_accreditation');
        $expr = $queryBuilder->expr();

        $queryBuilder
            ->select(
                'acc.uid',
                'acc.status',
                'acc.invitation_status',
                'acc.guest_type',
                'acc.tickets_approved',
                'acc.tickets_wish',
                'acc.notes',
                'acc.seats',
                'acc.title',
                'acc.first_name',
                'acc.middle_name',
                'acc.last_name',
                'acc.medium as company',
                'acc.distribution_job',
                'guest.uid AS guest_uid',
                'guest.client AS guest_client',
                'guest.title AS guest_title',
                'guest.first_name AS guest_first_name',
                'guest.middle_name AS guest_middle_name',
                'guest.last_name AS guest_last_name',
                'guest.title_suffix AS guest_title_suffix',
                'guest.company AS guest_company',
                'guest.position AS guest_position',
                'guest.email AS guest_email',
                'guest.phone AS guest_phone',
                'guest.mobile AS guest_mobile'
            )
            ->from('tx_publicrelations_domain_model_accreditation', 'acc')
            // LEFT JOIN auf tt_address, um den Namen des Gastes zu holen
            ->leftJoin(
                'acc',
                'tt_address',
                'guest',
                $expr->eq('acc.guest', $queryBuilder->quoteIdentifier('guest.uid'))
            );

        // --- SEARCH LOGIC ---
        $constraints = [
            $expr->eq('acc.event', $queryBuilder->createNamedParameter($eventUid, Connection::PARAM_INT))
        ];

        // Status-Filter anwenden
        switch ($statusFilter) {
            case 'accredited':
                $constraints[] = $expr->in('acc.status', $queryBuilder->createNamedParameter([1, 2], Connection::PARAM_INT_ARRAY));
                break;
            case 'pending':
                $constraints[] = $expr->eq('acc.status', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT));
                break;
            case 'reject':
                $constraints[] = $expr->eq('acc.status', $queryBuilder->createNamedParameter(-1, Connection::PARAM_INT));
                break;
            // Bei 'all' wird einfach kein Status-Constraint hinzugefügt
        }

        if (!empty($search)) {
            $searchWords = GeneralUtility::trimExplode(' ', $search, true);
            foreach ($searchWords as $word) {
                $constraints[] = $expr->or(
                    // Search in accreditation fields
                    $expr->like('acc.first_name', $queryBuilder->createNamedParameter('%' . $word . '%')),
                    $expr->like('acc.middle_name', $queryBuilder->createNamedParameter('%' . $word . '%')),
                    $expr->like('acc.last_name', $queryBuilder->createNamedParameter('%' . $word . '%')),
                    $expr->like('acc.medium', $queryBuilder->createNamedParameter('%' . $word . '%')),
                    $expr->like('acc.seats', $queryBuilder->createNamedParameter('%' . $word . '%')),
                    $expr->like('acc.notes', $queryBuilder->createNamedParameter('%' . $word . '%')),
                    // Search in linked guest fields
                    $expr->like('guest.first_name', $queryBuilder->createNamedParameter('%' . $word . '%')),
                    $expr->like('guest.middle_name', $queryBuilder->createNamedParameter('%' . $word . '%')),
                    $expr->like('guest.last_name', $queryBuilder->createNamedParameter('%' . $word . '%')),
                    $expr->like('guest.company', $queryBuilder->createNamedParameter('%' . $word . '%')),
                    $expr->like('guest.position', $queryBuilder->createNamedParameter('%' . $word . '%'))
                );
            }
        }

        $queryBuilder->where(...$constraints);

        $accreditations = $queryBuilder->executeQuery()->fetchAllAssociative();

        // Lade die "notes_select" Kategorien für alle gefundenen Akkreditierungen
        if (!empty($accreditations)) {
            $accreditationUids = array_column($accreditations, 'uid');
            $notesSelectByCategory = $this->findNotesSelectForAccreditations($accreditationUids);
            foreach ($accreditations as &$accreditation) {
                $accreditation['notes_select'] = $notesSelectByCategory[$accreditation['uid']] ?? [];
            }
        }
        return $accreditations;
    }

    /**
     * Holt die "notes_select" Kategorien für eine Liste von Akkreditierungs-UIDs.
     */
    private function findNotesSelectForAccreditations(array $accreditationUids): array
    {
        if (empty($accreditationUids)) {
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
                $queryBuilder->expr()->in('mm.uid_local', $queryBuilder->createNamedParameter($accreditationUids, Connection::PARAM_INT_ARRAY)),
                $queryBuilder->expr()->eq('mm.tablenames', $queryBuilder->createNamedParameter('tx_publicrelations_domain_model_accreditation')),
                $queryBuilder->expr()->eq('mm.fieldname', $queryBuilder->createNamedParameter('notes_select'))
            )
            ->executeQuery()
            ->fetchAllAssociative();

        // Gruppiere die Ergebnisse nach Kontakt-UID
        $notesByUid = [];
        foreach ($rows as $row) {
            $notesByUid[$row['uid_local']][] = [
                'uid' => $row['uid'],
                'title' => $row['title']
            ];
        }

        return $notesByUid;
    }

    /**
     * Holt alle relevanten Zähl-Statistiken für ein Event in einer einzigen Abfrage.
     */
    public function feGetStatsForEvent(int $eventUid): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_publicrelations_domain_model_accreditation');
        $expr = $queryBuilder->expr();

        // Subquery für das Kontingent aus der Event-Tabelle
        $quotaSubquery = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_publicrelations_domain_model_event')
            ->select('tickets_quota')
            ->from('tx_publicrelations_domain_model_event')
            ->where($expr->eq('uid', $queryBuilder->createNamedParameter($eventUid, Connection::PARAM_INT)))
            ->setMaxResults(1)
            ->getSQL();

        $queryBuilder
            ->selectLiteral(
                // Zählt alle Gäste mit Status 1 oder 2
                "COUNT(CASE WHEN status IN (1, 2) THEN 1 END) AS guests",
                // Summiert die Tickets nur für Gäste mit Status 1 oder 2
                "SUM(CASE WHEN status IN (1, 2) THEN tickets_approved ELSE 0 END) AS tickets",
                // Zählt alle Akkreditierungen mit Status 0
                "COUNT(CASE WHEN status = 0 THEN 1 END) AS pending",
                // Zählt alle Akkreditierungen mit Status -1
                "COUNT(CASE WHEN status = -1 THEN 1 END) AS rejected",
                "($quotaSubquery) AS quota"
            )
            ->from('tx_publicrelations_domain_model_accreditation')
            ->where($expr->eq('event', $queryBuilder->createNamedParameter($eventUid, Connection::PARAM_INT)));

        $stats = $queryBuilder->executeQuery()->fetchAssociative();

        // Stelle sicher, dass die Werte immer Zahlen sind, auch wenn keine Ergebnisse da sind
        return [
            'guests' => (int) ($stats['guests'] ?? 0),
            'tickets' => (int) ($stats['tickets'] ?? 0),
            'pending' => (int) ($stats['pending'] ?? 0),
            'rejected' => (int) ($stats['rejected'] ?? 0),
            'quota' => (int) ($stats['quota'] ?? 0)
        ];
    }

    /**
     * Prüft, ob ein Gast (tt_address UID) bereits für ein Event akkreditiert ist.
     * Gibt die UID der bestehenden Akkreditierung zurück oder null.
     */
    public function findExistingAccreditation(int $guestUid, int $eventUid): ?int
    {
        $query = $this->createQuery();
        $result = $query->matching(
            $query->logicalAnd(
                $query->equals('deleted', 0),
                $query->equals('guest', $guestUid),
                $query->equals('event', $eventUid)
            )
        )->execute()->getFirst();

        return $result ? $result->getUid() : null;
    }

    /**
     * Finds accreditation UIDs from a given list that are currently linked
     * to *any* distribution job (distribution_job > 0).
     *
     * @param array<int> $accreditationUids The list of accreditation UIDs to check.
     * @return array<int> An array containing the UIDs of accreditations linked to a job.
     */
    public function findUidsWithActiveDistributionJob(array $accreditationUids): array
    {
        if (empty($accreditationUids)) {
            return [];
        }

        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $queryBuilder = $connectionPool->getQueryBuilderForTable('tx_publicrelations_domain_model_accreditation');
        $queryBuilder->getRestrictions()->removeAll();

        $result = $queryBuilder
            ->select('uid')
            ->from('tx_publicrelations_domain_model_accreditation')
            ->where(
                // Filter by the provided accreditation UIDs
                $queryBuilder->expr()->in('uid', $queryBuilder->createNamedParameter($accreditationUids, Connection::PARAM_INT_ARRAY)),
                // *** SIMPLIFIED CHECK: Is any job linked? ***
                $queryBuilder->expr()->gt('distribution_job', 0)
            )
            ->executeQuery()
            ->fetchFirstColumn(); // fetchFirstColumn() directly returns an array of UIDs

        return array_map('intval', $result);
    }

    public function findUidsByDistributionJob(int $jobUid): array
    {

        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $queryBuilder = $connectionPool->getQueryBuilderForTable('tx_publicrelations_domain_model_accreditation');
        $queryBuilder->getRestrictions()->removeAll();

        return $queryBuilder
            ->select('uid')
            ->from('tx_publicrelations_domain_model_accreditation')
            ->where($queryBuilder->expr()->eq('distribution_job', $queryBuilder->createNamedParameter($jobUid, Connection::PARAM_INT)))
            ->executeQuery()
            ->fetchFirstColumn();
    }

}
