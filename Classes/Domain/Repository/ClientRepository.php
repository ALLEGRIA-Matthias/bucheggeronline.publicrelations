<?php
namespace BucheggerOnline\Publicrelations\Domain\Repository;

use TYPO3\CMS\Extbase\Persistence\Repository;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;

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
 * The repository for Clients
 */
class ClientRepository extends Repository
{
  private const TABLE_CLIENT = 'tx_publicrelations_domain_model_client';

  /**
   * Override default settings
   */
  public function initializeObject(): void
  {
    $querySettings = $this->createQuery()->getQuerySettings();
    $querySettings->setRespectStoragePage(false);
    $this->setDefaultQuerySettings($querySettings);
  }

  /*
   * typical method
   */
  public function findAllBackend()
  {
    $query = $this->createQuery();
    $query->getQuerySettings()->setIgnoreEnableFields(true);

    return $query->execute();
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

  public function findCurrent()
  {
    $query = $this->createQuery();

    return $query->matching(
      $query->logicalAnd(
        $query->equals('archive', 0),
        $query->equals('until', NULL),
        $query->equals('deleted', 0),
        $query->equals('hidden', 0)
      )
    )->execute();
  }

  public function findTops()
  {
    $query = $this->createQuery();

    return $query->matching(
      $query->logicalAnd(
        $query->equals('top', 1),
        $query->equals('deleted', 0),
        $query->equals('hidden', 0)
      )
    )->execute();
  }

  /*
   * typical method
   */
  public function findByQuery(array $search)
  {

    $query = $this->createQuery();

    $contraints = [];

    // Sicherer Zugriff auf den Suchbegriff
    $searchTermInput = trim((string) ($search['query'] ?? ''));

    if (!empty($searchTermInput)) {
      $searchTermParam = '%' . $searchTermInput . '%'; // Suchbegriff für LIKE vorbereiten
      $constraints[] = $query->logicalOr(
        $query->like('name', $searchTermParam),
        $query->like('alsoKnownAs', $searchTermParam),
        $query->like('shortinfo', $searchTermParam),
        $query->like('description', $searchTermParam)
        // Falls die zu durchsuchenden Felder von Objekten stammen, z.B. relationale Felder:
        // $query->like('relatedObject.name', $searchTermParam),
      );
    }

    if (!empty($constraints)) {
      $query->matching(
        $query->logicalAnd(...$constraints)
      );
    }

    return $query->execute();

  }

  /**
   * Holt alle Clients als schnelles Array [uid => [data]] für Lookups.
   * @return array
   */
  public function findAllForLookup(): array
  {
    $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
      ->getQueryBuilderForTable(self::TABLE_CLIENT);

    $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));

    $rows = $queryBuilder
      // --- HIER: Alle gewünschten Felder ---
      ->select('uid', 'name', 'short_name', 'also_known_as') //
      ->from(self::TABLE_CLIENT)
      ->executeQuery()
      ->fetchAllAssociative();

    // Wandelt [ ['uid' => 1, 'name' => 'Test', ...], ... ]
    // in [ 1 => ['uid' => 1, 'name' => 'Test', ...], ... ] um
    return array_combine(array_column($rows, 'uid'), $rows);
  }

}
