<?php
namespace BucheggerOnline\Publicrelations\Domain\Repository;

use TYPO3\CMS\Extbase\Persistence\Repository;


class SysCategoryRepository extends Repository
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

  /*
   * typical method
   */
  public function findByParentUid($parent)
  {

    $query = $this->createQuery();

    $query->matching(
      $query->logicalAnd(
        $query->equals('parent', $parent),
        $query->equals('client', 0)
      )
    );

    return $query->execute();
  }

  /*
   * typical method
   */
  public function findByProperty(string $property, $value, bool $condition = true)
  {
    $query = $this->createQuery();

    // 1. Erstelle die passende Bedingung für die Eigenschaft,
    //    je nachdem, ob $value ein Array oder ein Einzelwert ist.
    if (is_array($value)) {
      // Wenn $value ein Array ist, nutze die 'in'-Bedingung (SQL: IN (...))
      $propertyConstraint = $query->in($property, $value);
    } else {
      // Andernfalls nutze die normale 'equals'-Bedingung
      $propertyConstraint = $query->equals($property, $value);
    }

    // 2. Wende die Negation an, falls $condition false ist
    if (!$condition) {
      $propertyConstraint = $query->logicalNot($propertyConstraint);
    }

    // 3. Kombiniere die Bedingung mit der 'deleted'-Prüfung
    $query->matching(
      $query->logicalAnd(
        $propertyConstraint,
        $query->equals('deleted', 0)
      )
    );

    return $query->execute();
  }



  /*
   * typical method
   */
  public function feFindByClient(int $client)
  {

    $query = $this->createQuery();

    $query->matching(
      $query->logicalAnd(
        $query->equals('client', $client)
      )
    );

    return $query->execute();
  }

}
