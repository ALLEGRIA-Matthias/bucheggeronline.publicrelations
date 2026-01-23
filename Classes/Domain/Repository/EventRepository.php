<?php
namespace BucheggerOnline\Publicrelations\Domain\Repository;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Repository;

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
 * The repository for Events
 */
class EventRepository extends Repository
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

  public function findByProperty($property, $uid, $filter = null)
  {
    $query = $this->createQuery();

    if ($filter == 'upcoming') {
      $query->matching(
        $query->logicalAnd(
          $query->equals($property, $uid),
          $query->equals('deleted', 0),
          $query->greaterThanOrEqual('date', strtotime('today midnight'))
        )
      );
      $query->setOrderings(["date" => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_ASCENDING]);
    } elseif ($filter == 'archived') {
      $query->matching(
        $query->logicalAnd(
          $query->equals($property, $uid),
          $query->equals('deleted', 0),
          $query->lessThan('date', time())
        )
      );
      $query->setOrderings(["date" => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_DESCENDING]);
    } else {
      $query->matching(
        $query->logicalAnd(
          $query->equals($property, $uid),
          $query->equals('deleted', 0)
        )
      );
      $query->setOrderings(["date" => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_ASCENDING]);
    }

    return $query->execute();
  }

  /*
   * typical method
   */
  public function findAllBackend($filter = null)
  {

    $query = $this->createQuery();
    $query->getQuerySettings()->setIgnoreEnableFields(true);

    if ($filter == 'upcoming') {
      $query->matching(
        $query->greaterThanOrEqual('date', strtotime('today midnight'))
      );
      $query->setOrderings(["date" => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_ASCENDING]);
    } elseif ($filter == 'archived') {
      $query->matching(
        $query->lessThan('date', time())
      );
      $query->setOrderings(["date" => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_DESCENDING]);
    }

    return $query->execute();

  }

  /*
   * typical method
   */
  public function findNextWeeks($weeks = 2)
  {

    $query = $this->createQuery();

    return $query->matching(
      $query->logicalAnd(
        $query->greaterThanOrEqual('date', strtotime('today midnight')),
        $query->lessThanOrEqual('date', strtotime('+' . $weeks . ' weeks'))
      )
    )->execute();

  }

  /*
   * typical method
   */
  public function findUpcoming($limit = 10, $type = 'press', $noPrivate = 0)
  {

    $query = $this->createQuery();

    if ($type == 'press') {

      $query->matching(
        $query->logicalAnd(
          $query->greaterThanOrEqual('date', strtotime('today midnight')),
          $query->equals('accreditation', 1),
          $query->equals('private', $noPrivate)
        )
      );

      $query->setOrderings(["date" => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_ASCENDING])->setLimit($limit);

      return $query->execute();

    } else {

      return null;

    }

  }

  /*
   * typical method
   */
  public function findForCheckin()
  {

    $query = $this->createQuery();

    return $query->matching(
      $query->logicalAnd(
        $query->greaterThanOrEqual('date', strtotime('today midnight -7 days')),
        $query->equals('checkin', 1)
      )
    )->execute();

  }

  /*
   * typical method
   */
  public function findSchedule($property, $value, $limit = 0)
  {

    $query = $this->createQuery();

    $query->matching(
      $query->logicalAnd(
        $query->greaterThanOrEqual('date', strtotime('today midnight')),
        $query->equals($property, $value),
        $query->equals('private', 0)
      )
    );

    if ($limit)
      $query->setOrderings(["date" => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_ASCENDING])->setLimit($limit);
    else
      $query->setOrderings(["date" => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_ASCENDING]);

    return $query->execute();

  }

  /*
   * typical method
   */
  public function findByQuery(array $search)
  {

    $query = $this->createQuery();

    $contraints = [];

    $contraints[] = $query->greaterThanOrEqual('date', strtotime('today midnight'));

    // Sicherer Zugriff auf den Suchbegriff
    $searchTermInput = trim((string) ($search['query'] ?? ''));

    if (!empty($searchTermInput)) {
      $searchTermParam = '%' . $searchTermInput . '%'; // Suchbegriff für LIKE vorbereiten
      $constraints[] = $query->logicalOr(
        $query->like('title', $searchTermParam),
        $query->like('location.name', $searchTermParam)
        // Falls die zu durchsuchenden Felder von Objekten stammen, z.B. relationale Felder:
        // $query->like('relatedObject.name', $searchTermParam),
      );
    }

    if (!empty($constraints)) {
      $query->matching(
        $query->logicalAnd(...$constraints)
      );
    }

    $query->setOrderings(["date" => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_ASCENDING]);

    return $query->execute();

  }

  /**
   * findFiltered method
   */
  public function findFiltered(array $filter)
  {
    $query = $this->createQuery();

    $constraints = []; // Korrekter Variablenname (constraints statt contraints)

    if (!empty($filter['manualDateStart'])) { // Sicherer: !empty() verwenden
      $constraints[] = $query->greaterThanOrEqual('date', strtotime($filter['manualDateStart']));
    }
    if (!empty($filter['manualDateStop'])) {
      // Für das Enddatum ist es oft besser, bis zum Ende des Tages zu gehen,
      // oder den nächsten Tag zu nehmen und "lessThan" zu verwenden.
      // z.B. $endOfDay = (new \DateTimeImmutable($filter['manualDateStop']))->setTime(23, 59, 59)->getTimestamp();
      // $constraints[] = $query->lessThanOrEqual('date', $endOfDay);
      // Deine aktuelle Methode ist auch ein gängiger Ansatz:
      $constraints[] = $query->lessThanOrEqual('date', strtotime($filter['manualDateStop'] . ' 23:59:59')); // Oder +1 day und dann <
    }
    if (isset($filter['canceled']) && $filter['canceled'] !== '') { // Besser prüfen, ob der Key existiert und nicht leer ist
      $constraints[] = $query->equals('canceled', (int) $filter['canceled']);
    }
    if (!empty($filter['type'])) {
      $constraints[] = $query->equals('type', (int) $filter['type']);
    }
    if (!empty($filter['accreditation'])) {
      $constraints[] = $query->equals('accreditation', (int) $filter['accreditation']);
    }
    if (isset($filter['guests']) && $filter['guests'] === '1') { // Striker Vergleich, falls 'guests' immer ein String ist
      $constraints[] = $query->equals('accreditations.status', 1);
    }
    if (!empty($filter['client'])) {
      $constraints[] = $query->equals('client', (int) $filter['client']);
    }
    if (!empty($filter['query'])) {
      $constraints[] = $query->logicalOr( // logicalOr erwartet auch einzelne Argumente, nicht ein Array
        $query->like('title', '%' . $filter['query'] . '%'),
        $query->like('location.name', '%' . $filter['query'] . '%'),
        $query->like('client.name', '%' . $filter['query'] . '%'),
        $query->like('campaign.title', '%' . $filter['query'] . '%'),
        $query->like('campaign.subtitle', '%' . $filter['query'] . '%')
      );
    }

    if (!empty($constraints)) { // Prüfe, ob das Array nicht leer ist
      $query->matching(
        $query->logicalAnd(...$constraints) // << HIER den Splat-Operator verwenden
      );
    }

    $query->setOrderings(["date" => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_ASCENDING]);

    return $query->execute();
  }

  /*
   * typical method
   */
  public function findICal()
  {

    $query = $this->createQuery();

    $query->matching(
      $query->logicalAnd(
        $query->greaterThanOrEqual('date', strtotime('today -1 year')),
        $query->logicalOr(
          $query->equals('accreditation', 1),
          $query->equals('accreditations.status', 1),
          $query->greaterThanOrEqual('ticketsQuota', 1)
        )
      )
    );

    $query->setOrderings(["date" => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_ASCENDING]);

    return $query->execute();

  }



  public function findICalForRendering() // Evtl. eine neue Methode für optimiertes Laden
  {
    // --- QUERY 1: Checkboxen und Quoten (Die "Einfachen" Felder) ---
    $query1 = $this->createQuery();
    $constraints1 = [
        $query1->greaterThanOrEqual('date', new \DateTimeImmutable('today')),
        $query1->logicalOr(
            $query1->equals('accreditation', 1),
            $query1->greaterThan('ticketsQuota', 0)
        )
    ];
    $results1 = $query1->matching($query1->logicalAnd(...$constraints1))->execute()->toArray();

    // --- QUERY 2: Existenz von Akkreditierungen (Die Relation) ---
    $query2 = $this->createQuery();
    $constraints2 = [
        $query2->greaterThanOrEqual('date', new \DateTimeImmutable('today')),
        $query2->greaterThan('accreditations.uid', 0) // Erzwingt den Join separat
    ];
    $results2 = $query2->matching($query2->logicalAnd(...$constraints2))->execute()->toArray();

    // --- MERGE: Ergebnisse kombinieren und Duplikate entfernen ---
    // Wir nutzen die UID als Key im Array, um doppelte Events (die beide Kriterien erfüllen) zu filtern.
    $combinedResults = [];
    foreach (array_merge($results1, $results2) as $event) {
        $combinedResults[$event->getUid()] = $event;
    }

    // Sortierung wiederherstellen (da durch den Merge verloren gegangen)
    usort($combinedResults, function($a, $b) {
        return $a->getDate() <=> $b->getDate();
    });

    return $combinedResults;
  }

// public function findICalForRendering()
// {
//     $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
//         ->getQueryBuilderForTable('tx_publicrelations_domain_model_event');

//     $today = (new \DateTimeImmutable('today'))->getTimestamp();

//     // Wir nutzen hier Connection::PARAM_INT oder lassen den Typ weg, 
//     // damit Doctrine ihn selbst erkennt (was meist stabiler ist).
//     $todayParam = $queryBuilder->createNamedParameter($today, Connection::PARAM_INT);
//     $activeParam = $queryBuilder->createNamedParameter(1, Connection::PARAM_INT);

//     $result = $queryBuilder
//         ->select('e.*')
//         ->from('tx_publicrelations_domain_model_event', 'e')
//         ->where(
//             $queryBuilder->expr()->gte('e.date', $todayParam),
//             $queryBuilder->expr()->or(
//                 $queryBuilder->expr()->eq('e.accreditation', $activeParam),
//                 $queryBuilder->expr()->gt('e.tickets_quota', $activeParam),
//                 // Der EXISTS-Teil als sauberer String
//                 'EXISTS (SELECT 1 FROM tx_publicrelations_domain_model_accreditation a WHERE a.event = e.uid AND a.deleted = 0)'
//             )
//         )
//         ->orderBy('e.date', 'ASC')
//         ->executeQuery();

//     return $result->fetchAllAssociative();
// }



  // FRONTEND!!

  /**
   * Findet Events für einen Client basierend auf einem Filter-Modus (ohne Gäste-Daten).
   *
   * @param int $clientUid
   * @param string $filterMode ('upcoming', 'archived')
   * @return array
   */
  public function feFindByClient(int $clientUid, string $filterMode = 'upcoming', ?array $allowedEventUids = null): array
  {
    $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
      ->getQueryBuilderForTable('tx_publicrelations_domain_model_event');
    $expr = $queryBuilder->expr();

    $queryBuilder
      ->select(
        'e.uid',
        'e.date',
        'e.title',
        'loc.name as location_name'
      )
      ->addSelectLiteral(
        'SUM(IF(acc.status IN (1, 2), acc.tickets_approved, 0)) AS guest_count'
      )
      ->from('tx_publicrelations_domain_model_event', 'e')
      // LEFT JOIN für die Location bleibt erhalten
      ->leftJoin(
        'e',
        'tx_publicrelations_domain_model_location',
        'loc',
        $expr->eq('e.location', $queryBuilder->quoteIdentifier('loc.uid'))
      )
      ->leftJoin(
        'e',
        'tx_publicrelations_domain_model_accreditation',
        'acc',
        $expr->eq('e.uid', $queryBuilder->quoteIdentifier('acc.event'))
      )
      ->where($expr->eq('e.client', $queryBuilder->createNamedParameter($clientUid, Connection::PARAM_INT)))
      ->groupBy('e.uid');

    // Wenn eine Liste von erlaubten UIDs übergeben wird, fügen wir diese als Bedingung hinzu.
    if (is_array($allowedEventUids)) {
      if (empty($allowedEventUids)) {
        return []; // Wenn die Liste leer ist, gibt es keine Ergebnisse.
      }
      $queryBuilder->andWhere($expr->in('e.uid', $queryBuilder->createNamedParameter($allowedEventUids, Connection::PARAM_INT_ARRAY)));
    }

    // Vereinfachten Filter anwenden
    $this->feApplyFilterMode($queryBuilder, $filterMode);

    // Sortierung für eine sinnvolle Anzeige
    $queryBuilder->orderBy('e.date', $filterMode === 'archived' ? 'DESC' : 'ASC');

    return $queryBuilder->executeQuery()->fetchAllAssociative();
  }

  /**
   * Zählt Events für die Filter-Buttons.
   */
  public function feCountByClient(int $clientUid, ?array $allowedEventUids = null): array
  {
    return [
      'upcoming' => $this->feGetCountForFilter($clientUid, 'upcoming', $allowedEventUids),
      'upcoming_with_guests' => $this->feGetCountForFilter($clientUid, 'upcoming_with_guests', $allowedEventUids),
      'archived' => $this->feGetCountForFilter($clientUid, 'archived', $allowedEventUids),
    ];
  }

  private function feGetCountForFilter(int $clientUid, string $filterMode, ?array $allowedEventUids = null): int
  {
    $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
      ->getQueryBuilderForTable('tx_publicrelations_domain_model_event');
    $expr = $queryBuilder->expr();

    $queryBuilder->from('tx_publicrelations_domain_model_event', 'e');

    // If we only count events with guests, we need a JOIN and a DISTINCT count
    if ($filterMode === 'upcoming_with_guests') {

      $queryBuilder->addSelectLiteral('COUNT(DISTINCT e.uid) as guest_count');
      $queryBuilder->join(
        'e',
        'tx_publicrelations_domain_model_accreditation',
        'acc',
        $expr->and(
          $expr->eq('e.uid', $queryBuilder->quoteIdentifier('acc.event')),
          $expr->in('acc.status', [1, 2]) // Only count for approved/checked-in guests
        )
      );
    } else {
      $queryBuilder->count('e.uid');
    }

    $constraints = [];
    $constraints[] = $expr->eq('e.client', $queryBuilder->createNamedParameter($clientUid, Connection::PARAM_INT));

    // If a list of allowed UIDs is provided, add it as a constraint.
    if (is_array($allowedEventUids)) {
      if (empty($allowedEventUids)) {
        return 0; // Optimization: If the list is empty, the count is zero.
      }
      $constraints[] = $expr->in('e.uid', $queryBuilder->createNamedParameter($allowedEventUids, Connection::PARAM_INT_ARRAY));
    }

    $queryBuilder->where(...$constraints);

    // Apply the date filter from the other function
    $this->feApplyFilterMode($queryBuilder, $filterMode);

    return (int) $queryBuilder->executeQuery()->fetchOne();
  }

  /**
   * Vereinfachte Filterlogik ohne 'upcoming_with_guests'.
   */
  private function feApplyFilterMode($queryBuilder, string $filterMode): void
  {
    $expr = $queryBuilder->expr();
    switch ($filterMode) {
      case 'upcoming':
        $queryBuilder->andWhere($expr->gte('e.date', time()));
        break;
      case 'upcoming_with_guests':
        $queryBuilder->andWhere($expr->gte('e.date', time()));
        $queryBuilder->having($expr->gt('guest_count', 0));
        break;
      case 'archived':
        $queryBuilder->andWhere($expr->lt('e.date', time()));
        break;
    }
  }

}
