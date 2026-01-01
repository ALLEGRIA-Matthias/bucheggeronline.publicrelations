<?php
namespace BucheggerOnline\Publicrelations\Domain\Repository;


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
class LocationRepository extends \TYPO3\CMS\Extbase\Persistence\Repository
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

}
