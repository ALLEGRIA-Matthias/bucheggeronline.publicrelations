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
 * The repository for Mailing
 */
class MailingRepository extends \TYPO3\CMS\Extbase\Persistence\Repository
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

  public function findByStatus($status, $logicalNot = false)
  {
    $query = $this->createQuery();

    if ($logicalNot == false) {
      $query->matching(
        $query->equals('status', $status)
      );
    } else {
      $query->matching(
        $query->logicalNot($query->equals('status', $status))
      );
    }

    if ($status == -1)
      $query->setOrderings(["sent" => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_DESCENDING]);

    return $query->execute();
  }

}
