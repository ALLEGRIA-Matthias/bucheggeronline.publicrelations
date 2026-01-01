<?php
namespace BucheggerOnline\Publicrelations\Domain\Repository;

use TYPO3\CMS\Extbase\Persistence\Repository;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\QueryResult;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use BucheggerOnline\Publicrelations\Domain\Model\Mail;
use BucheggerOnline\Publicrelations\Domain\Model\Mailing;

/**
 * The repository for Mail
 */
class MailRepository extends Repository
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
    protected $defaultOrderings = [
        'sent' => QueryInterface::ORDER_DESCENDING
    ];

    public function findMailByReceiver($mailing, $contact, $email = ''): ?object
    {
        $query = $this->createQuery();

        return $query->matching(
            $query->logicalAnd(
                $query->equals('mailing', $mailing),
                $query->logicalOr(
                    $query->equals('receiver', $contact),
                    $query->equals('email', $email)
                )
            )
        )->execute()->getFirst();
    }

    public function findMailsByReceiver($mailing, $contact, $email = ''): int
    {
        $query = $this->createQuery();

        if ($email) {
            return $query->matching(
                $query->logicalAnd(
                    $query->equals('mailing', $mailing),
                    $query->logicalOr(
                        $query->equals('receiver', $contact),
                        $query->equals('email', $email)
                    )
                )
            )->count();
        } else {
            return $query->matching(
                $query->logicalAnd(
                    $query->equals('mailing', $mailing),
                    $query->equals('receiver', $contact)
                )
            )->count();
        }
    }

    public function findMailsToSend($mailing): QueryResultInterface
    {
        $query = $this->createQuery();

        return $query->matching(
            $query->logicalAnd(
                $query->equals('mailing', $mailing),
                $query->equals('type', 0)
            )
        )->execute();
    }

    public function findSentMails($mailing): QueryResultInterface
    {
        $query = $this->createQuery();

        return $query->matching(
            $query->logicalAnd(
                $query->equals('mailing', $mailing),
                $query->equals('type', 1)
            )
        )->execute();
    }

    public function findErrors($mailing): QueryResultInterface
    {
        $query = $this->createQuery();

        return $query->matching(
            $query->logicalAnd(
                $query->equals('mailing', $mailing),
                $query->equals('type', -1)
            )
        )->execute();
    }

    /**
     * Findet die UIDs von Empfänger:innen, die bereits einem Mailing zugewiesen wurden.
     * Nutzt den QueryBuilder für optimale Performance.
     *
     * @param array $receiverUids Array von TtAddress UIDs.
     * @param int $mailingUid UID des Mailings.
     * @return array Ein Array von UIDs der bereits zugewiesenen Empfänger:innen.
     */
    public function findReceiverUidsByMailing(array $receiverUids, int $mailingUid): array
    {
        if (empty($receiverUids)) {
            return [];
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_publicrelations_domain_model_mail');

        $results = $queryBuilder
            ->select('receiver')
            ->from('tx_publicrelations_domain_model_mail')
            ->where(
                $queryBuilder->expr()->eq('mailing', $queryBuilder->createNamedParameter($mailingUid, Connection::PARAM_INT)),
                $queryBuilder->expr()->in('receiver', $queryBuilder->createNamedParameter($receiverUids, Connection::PARAM_INT_ARRAY)),
                $queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT))
            )
            ->executeQuery()
            ->fetchAllAssociative();

        return array_unique(array_column($results, 'receiver'));
    }

    public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): QueryResultInterface
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

        if ($orderBy !== null) {
            $query->setOrderings($orderBy);
        }

        if ($limit !== null) {
            $query->setLimit($limit);
        }

        if ($offset !== null) {
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

    /**
     * Findet Receiver-UIDs, die bereits ein Mail-Objekt für ein spezifisches Mailing haben.
     * Nützlich für listType '5', wo nur nach der UID des Empfängers gesucht wird.
     *
     * @param int   $mailingUid Die UID des Mailings
     * @param array $receiverUids Ein Array von potenziellen Empfänger-UIDs
     * @return array Ein Array von Empfänger-UIDs, die bereits ein Mail-Objekt für dieses Mailing haben.
     */
    public function findMailUidsByReceiverUids(int $mailingUid, array $receiverUids): array
    {
        if (empty($receiverUids)) {
            return [];
        }

        $query = $this->createQuery();
        $query->matching(
            $query->logicalAnd(...[
                $query->equals('mailing', $mailingUid),
                $query->in('receiver.uid', $receiverUids) // Greift auf die UID des TtAddress-Objekts zu
            ])
        );

        // Wir wollen nur die UIDs der Empfänger, nicht die ganzen Mail-Objekte
        // Direktes Selecten von Eigenschaften ist mit Extbase-Query nicht trivial für Relationen.
        // Daher holen wir die Mail-Objekte und extrahieren die UIDs.
        // Für sehr große Datenmengen könnte eine DQL- oder native SQL-Abfrage performanter sein.

        /** @var QueryResult $result */
        $result = $query->execute();
        $foundReceiverUids = [];

        if ($result->count() > 0) {
            foreach ($result as $mail) {
                /** @var \Your\Extension\Domain\Model\Mail $mail */
                if ($mail->getReceiver()) {
                    $foundReceiverUids[] = $mail->getReceiver()->getUid();
                }
            }
        }

        return array_unique($foundReceiverUids);
    }

    /**
     * Findet existierende Mail-Einträge für ein spezifisches Mailing,
     * basierend auf einer Liste von Empfänger-UIDs und/oder E-Mail-Adressen.
     *
     * Gibt eine Struktur zurück, die anzeigt, welche UIDs und/oder E-Mails
     * bereits im Kontext des gegebenen Mailings existieren.
     *
     * @param int   $mailingUid Die UID des Mailings
     * @param array $uids Ein Array von potenziellen Empfänger-UIDs
     * @param array $emails Ein Array von potenziellen Empfänger-E-Mail-Adressen
     * @return array ['uids' => [uid1 => true, ...], 'emails' => [email1 => true, ...]]
     */
    public function findExistingMailsByUidOrEmail(int $mailingUid, array $uids, array $emails): array
    {
        $query = $this->createQuery();

        // Die Hauptbedingung: Muss immer zum Mailing gehören
        $mainConstraints = [$query->equals('mailing', $mailingUid)];

        $receiverOrConstraints = []; // Für die ODER-verknüpften Empfängerbedingungen

        if (!empty($uids)) {
            $validUids = array_filter($uids, static fn($uid) => is_numeric($uid) && (int) $uid > 0);
            if (!empty($validUids)) {
                $receiverOrConstraints[] = $query->in('receiver.uid', $validUids);
            }
        }

        if (!empty($emails)) {
            $validEmails = array_filter($emails, static fn($email) => !empty($email) && is_string($email) && filter_var($email, FILTER_VALIDATE_EMAIL));
            if (!empty($validEmails)) {
                $receiverOrConstraints[] = $query->in('receiver.email', $validEmails);
            }
        }

        // Wenn es Bedingungen für Empfänger gibt (UIDs oder E-Mails), füge sie als OR-Gruppe hinzu
        if (!empty($receiverOrConstraints)) {
            // Hier den Splat-Operator verwenden, wenn $receiverOrConstraints mehrere Elemente haben kann
            $mainConstraints[] = $query->logicalOr(...$receiverOrConstraints);
        } else {
            // Keine UIDs und keine E-Mails zum Filtern angegeben.
            // Was soll in diesem Fall passieren? Alle Mails des Mailings zurückgeben?
            // Oder einen Fehler werfen/leeres Array zurückgeben, da die Filterkriterien fehlen?
            // Deine aktuelle Logik gibt hier ein leeres Array zurück, was oft sinnvoll ist,
            // wenn man erwartet, dass nach UID *oder* E-Mail gefiltert wird.
            // Wenn du in diesem Fall alle Mails des Mailings willst, die $mainConstraints einfach so lassen.
            // Für deine Logik "return ['uids' => [], 'emails' => []];" ist es besser, hier frühzeitig zu returnen,
            // wenn die Funktion spezifisch existierende Mails basierend auf UIDs/E-Mails finden soll.
            // Die Prüfung if (empty($receiverConstraints)) vom Originalcode war also an sich korrekt.
            // Ich lasse sie hier drin zur Verdeutlichung des alten Verhaltens:
            if (empty($receiverOrConstraints)) { // Wenn nach Filterung immer noch leer
                return ['uids' => [], 'emails' => []];
            }
        }

        // Alle Hauptbedingungen (Mailing-ID UND (Empfänger-UID ODER Empfänger-E-Mail)) mit AND verknüpfen
        if (!empty($mainConstraints)) { // Sollte immer mindestens die mailingUid-Bedingung enthalten
            $query->matching($query->logicalAnd(...$mainConstraints));
        } else {
            // Dieser Fall sollte nicht eintreten, wenn mailingUid immer gesetzt wird.
            // Falls doch, könnte man hier eine Exception werfen oder alle Mails laden (Achtung!)
            return ['uids' => [], 'emails' => []]; // Sicherer Fallback
        }


        /** @var \TYPO3\CMS\Extbase\Persistence\QueryResultInterface $result */ // Korrekter Typ
        $result = $query->execute();

        $existingIdentifiers = [
            'uids' => [],
            'emails' => [],
        ];

        if ($result->count() > 0) {
            /** @var Mail $mail */ // Korrekter Typ für dein Model
            foreach ($result as $mail) {
                if ($mail->getReceiver()) {
                    $existingIdentifiers['uids'][$mail->getReceiver()->getUid()] = true;
                    if ($mail->getReceiver()->getEmail()) {
                        // E-Mails sollten für den Vergleich und als Array-Key normalisiert werden (z.B. Kleinschreibung)
                        $existingIdentifiers['emails'][strtolower($mail->getReceiver()->getEmail())] = true;
                    }
                }
            }
        }
        return $existingIdentifiers;
    }

    /**
     * Counts the number of mails for a given list of mailing UIDs.
     * This method uses a raw QueryBuilder for maximum performance.
     *
     * @param array<int> $mailingUids
     * @return array<int, int> An associative array where keys are mailing UIDs and values are the counts.
     */
    public function countByMailingUids(array $mailingUids): array
    {
        if (empty($mailingUids)) {
            return [];
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_publicrelations_domain_model_mail');

        $results = $queryBuilder
            ->selectLiteral('COUNT(uid) as mail_count, mailing')
            ->from('tx_publicrelations_domain_model_mail')
            ->where(
                $queryBuilder->expr()->in('mailing', $queryBuilder->createNamedParameter($mailingUids, Connection::PARAM_INT_ARRAY)),
                $queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT))
            )
            ->groupBy('mailing')
            ->executeQuery()
            ->fetchAllAssociative();

        return array_column($results, 'mail_count', 'mailing');
    }
}
