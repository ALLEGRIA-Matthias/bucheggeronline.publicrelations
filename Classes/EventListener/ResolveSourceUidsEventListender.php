<?php

declare(strict_types=1);

namespace BucheggerOnline\Publicrelations\EventListener;

use Allegria\AcContacts\Event\ResolveSourceUidsEvent;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Connection;

/**
 * Löst 'event' und 'accreditation' UIDs in Kontakt-IDs auf.
 */
class ResolveSourceUidsEventListener
{
    public function __construct(
        private readonly ConnectionPool $connectionPool
    ) {
    }

    public function __invoke(ResolveSourceUidsEvent $event): void
    {
        if ($event->hasResolvedContactUids()) {
            return;
        }

        $type = $event->getSourceType();
        $rawIds = $event->getRawIds();

        if (empty($rawIds)) {
            return;
        }

        $contactUids = [];
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tx_publicrelations_domain_model_accreditation');

        // CASE 1: Event UIDs -> Alle Gäste dieses Events
        // Wir suchen in der Akkreditierungs-Tabelle nach allen Einträgen mit dieser Event-ID
        if ($type === 'event') {
            $contactUids = $queryBuilder
                ->select('guest') // Das Feld 'guest' enthält die UID des ac_contact
                ->from('tx_publicrelations_domain_model_accreditation')
                ->where(
                    $queryBuilder->expr()->in('event', $queryBuilder->createNamedParameter($rawIds, Connection::PARAM_INT_ARRAY)),
                    $queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
                    $queryBuilder->expr()->gt('guest', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)) // Nur wenn ein Gast verknüpft ist
                )
                ->executeQuery()
                ->fetchFirstColumn();
        }

        // CASE 2: Accreditation UIDs -> Einzelne Tickets
        // Wir lösen direkt die Accreditation UID zum Gast auf
        elseif ($type === 'accreditation') {
            $contactUids = $queryBuilder
                ->select('guest')
                ->from('tx_publicrelations_domain_model_accreditation')
                ->where(
                    $queryBuilder->expr()->in('uid', $queryBuilder->createNamedParameter($rawIds, Connection::PARAM_INT_ARRAY)),
                    $queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
                    $queryBuilder->expr()->gt('guest', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT))
                )
                ->executeQuery()
                ->fetchFirstColumn();
        }

        if (!empty($contactUids)) {
            $event->setResolvedContactUids($contactUids);
        }
    }
}