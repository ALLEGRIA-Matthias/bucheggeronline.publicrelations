<?php
namespace BucheggerOnline\Publicrelations\Domain\Repository;

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
 * The repository for Campaigns
 */
class CampaignRepository extends \TYPO3\CMS\Extbase\Persistence\Repository
{
  private const TABLE_CAMPAIGN = 'tx_publicrelations_domain_model_campaign';

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

  public function findByClient($client)
  {
    $query = $this->createQuery();

    return $query->matching(
      $query->logicalAnd(
        $query->equals('client', $client),
        $query->equals('deleted', 0)
      )
    )->execute();
  }

  /**
   * Findet Kampagnen anhand der UID des zugehörigen Kunden.
   *
   * @param int $clientUid
   * @return array|\TYPO3\CMS\Extbase\Persistence\QueryResultInterface
   */
  public function findByClientUid(int $clientUid)
  {
    $query = $this->createQuery();
    $query->matching(
      // Annahme: Dein Campaign-Modell hat eine Property 'client',
      // die auf ein Client-Objekt verweist, welches eine 'uid'-Property hat.
      // Passe 'client.uid' ggf. an den Namen deiner Properties an.
      // Wenn 'client' direkt die UID des Kunden speichert: $query->equals('client', $clientUid)
      $query->equals('client', $clientUid)
    );

    // Optional: Weitere Einschränkungen wie 'hidden' und 'deleted'
    // $query->logicalAnd([
    //     $query->equals('client.uid', $clientUid),
    //     $query->equals('hidden', false),
    //     $query->equals('deleted', false)
    // ]);

    // Optional: Sortierung
    $query->setOrderings(['title' => QueryInterface::ORDER_ASCENDING]);

    return $query->execute(); // Gibt QueryResultInterface zurück
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
        $query->like('title', $searchTermParam),
        $query->like('subtitle', $searchTermParam),
        $query->like('alsoKnownAs', $searchTermParam),
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
   * Holt alle Kampagnen als schnelles Array [uid => [data]] für Lookups.
   * @return array
   */
  public function findAllForLookup(): array
  {
    $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
      ->getQueryBuilderForTable(self::TABLE_CAMPAIGN);

    $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));

    $rows = $queryBuilder
      // --- HIER: Alle gewünschten Felder ---
      ->select('uid', 'title', 'subtitle', 'also_known_as') //
      ->from(self::TABLE_CAMPAIGN)
      ->executeQuery()
      ->fetchAllAssociative();

    return array_combine(array_column($rows, 'uid'), $rows);
  }

}
