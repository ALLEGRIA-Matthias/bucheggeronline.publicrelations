<?php
namespace BucheggerOnline\Publicrelations\Domain\Repository;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Connection;

use FriendsOfTYPO3\TtAddress\Domain\Repository\AddressRepository;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;

use BucheggerOnline\Publicrelations\Utility\SearchQueryUtility;

/**
 * Repository für tt_address mit ein paar Custom-Methoden
 */
class TtAddressRepository extends AddressRepository
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

  /**
   * Standard-Sortierung: Nach Name aufsteigend
   *
   * @var array
   */
  protected $defaultOrderings = [
    'lastName' => QueryInterface::ORDER_ASCENDING,
    'firstName' => QueryInterface::ORDER_ASCENDING,
    'middleName' => QueryInterface::ORDER_ASCENDING,
    'company' => QueryInterface::ORDER_ASCENDING,
  ];

  /**
   * Findet alle TtAddress-Objekte, deren UIDs in dem übergebenen Array enthalten sind.
   *
   * @param array $uids Ein Array von UIDs, nach denen gesucht werden soll.
   * @return QueryResultInterface<\Your\Extension\Domain\Model\TtAddress> Eine Sammlung der gefundenen TtAddress-Objekte.
   * Leere QueryResult, wenn keine UIDs übergeben wurden
   * oder keine Objekte gefunden wurden.
   */
  public function findByUids(array $uids): QueryResultInterface
  {
    // Wenn das $uids Array leer ist, gibt es nichts zu suchen.
    // Eine leere IN()-Klausel würde zu einem SQL-Fehler führen.
    if (empty($uids)) {
      // Erzeugt ein leeres QueryResult, um einen konsistenten Rückgabetyp zu gewährleisten.
      /** @var \TYPO3\CMS\Extbase\Persistence\Generic\QueryResult $emptyResult */
      $emptyResult = $this->objectManager->get(\TYPO3\CMS\Extbase\Persistence\Generic\QueryResult::class);
      $emptyResult->initialize([]); // Initialisiert mit einem leeren Array
      return $emptyResult;

      // Alternativ, je nach Präferenz, könnte man auch eine Exception werfen
      // oder null zurückgeben, aber ein leeres QueryResult ist oft am praktischsten.
    }

    $query = $this->createQuery();

    // Stellt sicher, dass die UIDs als Integer behandelt werden, um SQL-Injection vorzubeugen
    // und den korrekten Datentyp für die Abfrage zu gewährleisten.
    $integerUids = array_map('intval', $uids);
    // Entfernt Duplikate, was die IN-Klausel potenziell etwas effizienter machen kann.
    $uniqueIntegerUids = array_unique($integerUids);

    $query->matching(
      $query->in('uid', $uniqueIntegerUids)
    );

    // Standardmäßig ignoriert Extbase die storagePid und enableFields nicht,
    // was in den meisten Fällen erwünscht ist. Wenn Sie das Verhalten ändern müssen:
    // $query->getQuerySettings()->setRespectStoragePage(false);
    // $query->getQuerySettings()->setIgnoreEnableFields(true);

    return $query->execute();
  }

  /**
   * Alle Kontakte eines PID holen
   *
   * @param int $page
   * @return \TYPO3\CMS\Extbase\Persistence\QueryResultInterface
   */
  public function findByPage(int $page)
  {
    $query = $this->createQuery();
    $query->matching(
      $query->equals('pid', $page)
    );
    return $query->execute();
  }

  /**
   * Nach einem beliebigen Feld filtern und wahlweise erstes Objekt oder als Array zurückgeben.
   *
   * @param string $property
   * @param mixed  $value
   * @param bool   $asArray
   * @return object|null
   */
  public function findByProperty(string $property, $value, bool $asArray = false)
  {
    $query = $this->createQuery();
    $query->matching(
      $query->equals($property, $value)
    );
    $result = $query->execute();
    if ($asArray) {
      $arrayResult = $result->toArray();
      return $arrayResult[0] ?? null;
    }
    return $result->getFirst();
  }

  /**
   * Verschiedene Filterkriterien kombinieren (mailingExclude, personally, categories, client, freier Suchtext)
   *
   * @param array $filter
   * @return \TYPO3\CMS\Extbase\Persistence\QueryResultInterface
   */
  public function findFiltered(array $filter)
  {
    $query = $this->createQuery();
    $constraints = [];

    if (isset($filter['mailingExclude']) && !empty($filter['mailingExclude'])) {
      $constraints[] = $query->equals('mailingExclude', (bool) $filter['mailingExclude']);
    }
    if (isset($filter['personally']) && !empty($filter['personally'])) {
      $constraints[] = $query->equals('personally', (bool) $filter['personally']);
    }
    if (!empty($filter['categories'])) {
      $constraints[] = $query->contains('categories', (int) $filter['categories']);
    }
    if (!empty($filter['client'])) {
      $constraints[] = $query->equals('client', (int) $filter['client']);
    }
    if (!empty($filter['query'])) {
      $constraints[] = $query->logicalOr(
        $query->like('firstName', '%' . $filter['query'] . '%'),
        $query->like('middleName', '%' . $filter['query'] . '%'),
        $query->like('lastName', '%' . $filter['query'] . '%'),
        $query->like('name', '%' . $filter['query'] . '%'),
        $query->like('company', '%' . $filter['query'] . '%'),
        $query->like('email', '%' . $filter['query'] . '%'),
        $query->like('description', '%' . $filter['query'] . '%'),
        $query->like('phone', '%' . $filter['query'] . '%'),
        $query->like('mobile', '%' . $filter['query'] . '%')
      );
    }

    if (!empty($constraints)) {
      $query->matching(
        $query->logicalAnd(...$constraints)
      );
    }


    return $query->execute();
  }

  private const FIELD_MAP = [
    // Allgemeine Felder
    '*' => ['first_name', 'middle_name', 'last_name', 'company', 'phone', 'mobile', 'email'],

    // Feldspezifische Suche
    'firma' => ['company'],
    'company' => ['company'],
    'name' => ['first_name', 'middle_name', 'last_name'],
    // 'number' => ['phoneNumbers.number'],
    'mobil' => ['mobile'],
    'email' => ['email'],
    'telefon' => ['phone', 'mobile'],
    // 'email' => ['emails.email', 'email'],
    'tag' => ['tags.title'],
    'tags' => ['tags.title'],
    'interesse' => ['tags.title'],
    'interessen' => ['tags.title'],
    'category' => ['categories.title'],
    'kategorie' => ['categories.title'],
    'kategorien' => ['categories.title'],
    'verteiler' => ['categories.title'],
    'kunde' => ['client.name', 'client.short_name'],
    'social' => ['socialProfiles.handle', 'socialProfiles.type', 'socialProfiles.notes'],
    // 'group' => ['groups.contactGroup.name'],
    'gruppe' => ['groups.name']
  ];

  private const DEFAULT_FIELDS = ['first_name', 'middle_name', 'last_name', 'company', 'email'];

  /**
   * REFAKTORED: Führt die Suche durch und gibt die Ergebnisse als Array zurück.
   *
   * @param string $searchTerm
   * @param bool $showClients Wenn false, werden nur Kontakte mit client=0 zurückgegeben
   * @return array
   */
  public function findBySearchTerm(string $searchTerm, bool $showClients = true): array
  {
    $query = $this->createQuery();

    // Bestimme den Filter-Modus für die Utility
    $clientFilter = $showClients ? 'all' : 'no_clients';

    // Delegiere die gesamte Logik an die Utility
    SearchQueryUtility::apply(
      $query,
      $searchTerm,
      self::FIELD_MAP,
      self::DEFAULT_FIELDS,
      $clientFilter // Übergib den korrekten Modus
    );

    return $query->execute(true);
  }

  /**
   * REFAKTORED: Zählt nur Kundenkontakte, die dem Suchbegriff entsprechen.
   *
   * @param string $searchTerm
   * @return int
   */
  public function countClientContactsBySearchTerm(string $searchTerm): int
  {
    $query = $this->createQuery();

    // Delegiere die gesamte Logik an die Utility mit dem Modus 'only_clients'
    SearchQueryUtility::apply(
      $query,
      $searchTerm,
      self::FIELD_MAP,
      self::DEFAULT_FIELDS,
      'only_clients' // Harter Modus für diese Funktion
    );

    return $query->count();
  }

  public function findGroupsOfContact(int $contactUid, bool $asArray = false)
  {
    $query = $this->createQuery();
    $query->matching(
      $query->equals('contact.uid', $contactUid)
    );

    /** @var \Allegria\AcContacts\Domain\Model\ContactGroupAssignment[] $assignments */
    $assignments = $query->execute()->toArray();

    if (empty($assignments)) {
      return $asArray ? [] : new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
    }

    $groups = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();

    foreach ($assignments as $assignment) {
      $groups->attach($assignment->getContactGroup());
    }

    return $asArray
      ? array_map(fn($group) => [
        'uid' => $group->getUid(),
        'name' => $group->getName(),
        'hierarchy_name' => $group->getHierarchyName()
      ], $groups->toArray())
      : $groups;
  }

  public function findTagsOfContact(int $contactUid, bool $asArray = false)
  {
    $query = $this->createQuery();
    $query->matching(
      $query->equals('uid', $contactUid)
    );

    $contact = $query->execute()->getFirst();
    if ($contact === null) {
      return $asArray ? [] : new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
    }

    $tags = $contact->getTags();

    // Rückgabe je nach $asArray - jetzt null-sicher
    return $asArray
      // 1. Versuche $tags->toArray() aufzurufen. Wenn $tags null ist, gibt `?->` null zurück.
      // 2. Wenn das Ergebnis null ist, nutze `??` um stattdessen ein leeres Array `[]` zu nehmen.
      ? array_map(fn($tag) => ['uid' => $tag->getUid(), 'title' => $tag->getTitle(), 'color' => $tag->getGroupColor(), 'icon' => $tag->getGroupIcon(), 'hierarchy_title' => $tag->getHierarchyTitle()], $tags?->toArray() ?? [])
      // Wenn $tags null ist, gib ein neues, leeres ObjectStorage zurück.
      : ($tags ?? new \TYPO3\CMS\Extbase\Persistence\ObjectStorage());
  }

  public function findCategoriesOfContact(int $contactUid, bool $asArray = false)
  {
    $query = $this->createQuery();
    $query->matching(
      $query->equals('uid', $contactUid)
    );

    $contact = $query->execute()->getFirst();
    if ($contact === null) {
      return $asArray ? [] : new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
    }

    $categories = $contact->getCategories();


    // Rückgabe je nach $asArray
    return $asArray
      ? array_map(fn($category) => [
        'uid' => $category->getUid(),
        'title' => $category->getTitle()
      ], $categories->toArray())
      : $categories;
  }

  /**
   * Findet alle Social-Media-Profile eines Kontakts.
   *
   * @param int $contactUid Die UID des Kontakts
   * @param bool $asArray Ob das Ergebnis als Array zurückgegeben werden soll
   * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage|array
   */
  public function findSocialProfilesOfContact(int $contactUid, bool $asArray = false)
  {
    $query = $this->createQuery();
    $query->matching(
      $query->equals('uid', $contactUid)
    );

    $contact = $query->execute()->getFirst();
    if ($contact === null) {
      return $asArray ? [] : new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
    }

    $socialProfiles = $contact->getSocialProfiles();

    // Rückgabe je nach $asArray - jetzt null-sicher
    return $asArray
      // 1. Versuche $socialProfiles->toArray() aufzurufen. Wenn $socialProfiles null ist, gibt `?->` null zurück.
      // 2. Wenn das Ergebnis null ist, nutze `??` um stattdessen ein leeres Array `[]` zu nehmen.
      ? array_map(fn($profile) => ['uid' => $profile->getUid(), 'type' => $profile->getType(), 'handle' => $profile->getHandle(), 'follower' => $profile->getFollower()], $socialProfiles?->toArray() ?? [])
      // Wenn $socialProfiles null ist, gib ein neues, leeres ObjectStorage zurück.
      : ($socialProfiles ?? new \TYPO3\CMS\Extbase\Persistence\ObjectStorage());
  }

  public function findContactTypesOfContact(int $contactUid, bool $asArray = false)
  {
    $query = $this->createQuery();
    $query->matching(
      $query->equals('uid', $contactUid)
    );

    $contact = $query->execute()->getFirst();
    if ($contact === null) {
      return $asArray ? [] : new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
    }

    $contactTypes = $contact->getContactTypes();

    if ($contactTypes === null) {
      $contactTypes = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
    }

    // Rückgabe je nach $asArray
    return $asArray
      ? array_map(fn($contactType) => [
        'uid' => $contactType->getUid(),
        'title' => $contactType->getTitle(),
        'icon' => $contactType->getIcon(),
        'svg' => $contactType->getSvg(),
        'css_class' => $contactType->getCssClass()
      ], $contactTypes->toArray())
      : $contactTypes;
  }

  public function findDuplicatesByEmail(string $email, string $contactType, ?int $client = null): QueryResultInterface
  {
    $query = $this->createQuery();

    $constraints = [];

    // Bedingung 1: Die E-Mail-Adresse muss immer übereinstimmen
    $constraints[] = $query->equals('email', $email);

    // Bedingung 2: Der Kontext muss stimmen
    if ($contactType === 'internal') {
      $constraints[] = $query->equals('client', 0);
    } elseif ($contactType === 'client' && $client > 0) {
      $constraints[] = $query->equals('client', $client);
    } else {
      // Wenn der Kontext ungültig ist, einfach ein leeres Ergebnis zurückgeben
      return $this->createQuery()->execute();
    }

    // Alle Bedingungen mit UND verknüpfen
    $query->matching(
      $query->logicalAnd(...$constraints)
    );

    return $query->execute();
  }

  /**
   * Findet exakte Duplikate (E-Mail, Vorname, Nachname) in einem bestimmten Kontext.
   * Die Prüfung erfolgt case-insensitiv (ignoriert Groß-/Kleinschreibung).
   *
   * @param string $email
   * @param string $firstName
   * @param string $lastName
   * @param string $contactType 'internal' oder 'client'
   * @param int|null $client UID des Kunden, falls $contactType 'client' ist
   */
  public function findStrictDuplicates(
    string $email,
    string $firstName,
    string $lastName,
    string $contactType,
    ?int $client = null
  ): QueryResultInterface {
    $query = $this->createQuery();

    $constraints = [];

    // Bedingung 1: Felder müssen übereinstimmen (case-insensitiv)
    // Der dritte Parameter `false` bei `equals()` schaltet die Groß-/Kleinschreibung-Prüfung aus.
    $constraints[] = $query->equals('email', $email, false);
    $constraints[] = $query->equals('first_name', $firstName, false);
    $constraints[] = $query->equals('last_name', $lastName, false);

    // Bedingung 2: Der Kontext muss stimmen
    if ($contactType === 'internal') {
      $constraints[] = $query->equals('client', 0);
    } elseif ($contactType === 'client' && $client > 0) {
      $constraints[] = $query->equals('client', $client);
    } else {
      // Bei ungültigem Kontext geben wir ein leeres Ergebnis zurück
      return $this->createQuery()->execute();
    }

    // Alle Bedingungen mit einem logischen UND verknüpfen
    $query->matching(
      $query->logicalAnd(...$constraints)
    );

    return $query->execute();
  }

  /**
   * Findet alle UIDs von Kontakten, die mit einer oder mehreren Kategorien verknüpft sind.
   *
   * @param array<int> $categoryUids UIDs der Kategorien
   * @return array<int> Ein Array von TtAddress UIDs
   */
  public function findUidsByCategoryUids(array $categoryUids): array
  {
    if (empty($categoryUids)) {
      return [];
    }

    $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_category_record_mm');

    $results = $queryBuilder
      ->select('uid_foreign')
      ->from('sys_category_record_mm')
      ->where(
        $queryBuilder->expr()->eq('tablenames', $queryBuilder->createNamedParameter('tt_address')),
        $queryBuilder->expr()->in('uid_local', $queryBuilder->createNamedParameter($categoryUids, Connection::PARAM_INT_ARRAY)),
      )
      ->executeQuery()
      ->fetchAllAssociative();

    return array_column($results, 'uid_foreign');
  }

  /**
   * Holt eine Liste von tt_address Datensätzen (als einfaches Array) basierend auf UIDs.
   *
   * @param array<int> $uids UIDs der zu suchenden Kontakte.
   * @return array<array<string, mixed>> Eine Liste von Kontakten mit den benötigten Feldern.
   */
  public function findForSummaryByUids(array $uids): array
  {
    if (empty($uids)) {
      return [];
    }

    $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tt_address');

    $results = $queryBuilder
      ->select('uid', 'first_name', 'middle_name', 'last_name', 'company', 'email')
      ->from('tt_address')
      ->where(
        $queryBuilder->expr()->in('uid', $queryBuilder->createNamedParameter($uids, Connection::PARAM_INT_ARRAY)),
        $queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
      )
      ->executeQuery()
      ->fetchAllAssociative();

    return $results;
  }

  /**
   * Findet TtAddress Datensätze für die Massenverarbeitung und gibt nur
   * die notwendigen Felder als Array zurück, um Hydration zu vermeiden.
   *
   * @param array<int> $uids Die UIDs der zu suchenden Kontakte.
   * @return array<int, array<string, mixed>> Ein assoziatives Array mit den Gastdaten, indiziert nach UID.
   */
  public function findGuestsForBulkProcessing(array $uids): array
  {
    if (empty($uids)) {
      return [];
    }

    $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tt_address');

    $results = $queryBuilder
      ->select('uid', 'first_name', 'middle_name', 'last_name', 'company', 'email', 'mailing_exclude')
      ->from('tt_address')
      ->where(
        $queryBuilder->expr()->in('uid', $queryBuilder->createNamedParameter($uids, Connection::PARAM_INT_ARRAY)),
        $queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT))
      )
      ->executeQuery()
      ->fetchAllAssociative();

    // Indexiere die Ergebnisse nach UID für schnellen Zugriff
    return array_column($results, null, 'uid');
  }




  // FRONTEND!!!


  public function feFindByClient($client, string $search = '', int $mailinglist = 0): array
  {
    $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
      ->getQueryBuilderForTable('tt_address');
    $expr = $queryBuilder->expr();

    // Die Spalten, die wir für die Anzeige benötigen
    $queryBuilder->select(
      'tt_address.uid',
      'tt_address.first_name',
      'tt_address.middle_name',
      'tt_address.last_name',
      'tt_address.title',
      'tt_address.email',
      'tt_address.phone',
      'tt_address.mobile',
      'tt_address.gender',
      'tt_address.company',
      'tt_address.position',
      'tt_address.mailing_exclude'
    )->from('tt_address');

    $queryBuilder->addSelectLiteral(
      'GROUP_CONCAT(DISTINCT mm.uid_local) AS category_uids'
    );

    // 2. Wir nutzen einen LEFT JOIN, damit auch Kontakte ohne Kategorien gefunden werden
    $queryBuilder->leftJoin(
      'tt_address',
      'sys_category_record_mm',
      'mm',
      $expr->and(
        $expr->eq('tt_address.uid', $queryBuilder->quoteIdentifier('mm.uid_foreign')),
        $expr->eq('mm.tablenames', $queryBuilder->createNamedParameter('tt_address')),
        $expr->eq('mm.fieldname', $queryBuilder->createNamedParameter('categories'))
      )
    );

    // 3. Constraints sammeln
    $constraints = [];
    $constraints[] = $expr->eq('tt_address.client', $queryBuilder->createNamedParameter($client, Connection::PARAM_INT));

    // Basis-Bedingung: Nur Kontakte des aktuellen Kunden
    $queryBuilder->where(
      $queryBuilder->expr()->eq('tt_address.client', $queryBuilder->createNamedParameter($client, Connection::PARAM_INT))
    );

    // 3. Optional den Such-Constraint hinzufügen
    if (!empty($search)) {
      $searchWords = GeneralUtility::trimExplode(' ', $search, true);
      foreach ($searchWords as $word) {
        $constraints[] = $expr->or(
          $expr->like('tt_address.gender', $queryBuilder->createNamedParameter('%' . $word . '%')),
          $expr->like('tt_address.title', $queryBuilder->createNamedParameter('%' . $word . '%')),
          $expr->like('tt_address.first_name', $queryBuilder->createNamedParameter('%' . $word . '%')),
          $expr->like('tt_address.middle_name', $queryBuilder->createNamedParameter('%' . $word . '%')),
          $expr->like('tt_address.last_name', $queryBuilder->createNamedParameter('%' . $word . '%')),
          $expr->like('tt_address.title_suffix', $queryBuilder->createNamedParameter('%' . $word . '%')),
          $expr->like('tt_address.company', $queryBuilder->createNamedParameter('%' . $word . '%')),
          $expr->like('tt_address.position', $queryBuilder->createNamedParameter('%' . $word . '%')),
          $expr->like('tt_address.email', $queryBuilder->createNamedParameter('%' . $word . '%')),
          $expr->like('tt_address.phone', $queryBuilder->createNamedParameter('%' . $word . '%')),
          $expr->like('tt_address.mobile', $queryBuilder->createNamedParameter('%' . $word . '%'))
        );
      }
    }

    // 4. Optional den Mailinglisten-Constraint hinzufügen
    if ($mailinglist > 0) {
      // Wir verknüpfen die MM-Tabelle direkt mit der Hauptabfrage.
      // Ein INNER JOIN sorgt automatisch dafür, dass nur Kontakte übrig bleiben,
      // die überhaupt in der MM-Tabelle vorkommen.
      $queryBuilder->join(
        'tt_address',               // Alias der "von"-Tabelle
        'sys_category_record_mm',   // Tabelle, die wir joinen
        'mm_lists',                       // Alias für die gejointe Tabelle
        // Die JOIN-Bedingung
        $expr->eq('tt_address.uid', $queryBuilder->quoteIdentifier('mm_lists.uid_foreign'))
      );

      // Jetzt fügen wir die Filter-Bedingungen für die gejointe Tabelle hinzu.
      // Diese werden Teil der normalen WHERE-Klausel.
      $constraints[] = $expr->eq('mm_lists.uid_local', $queryBuilder->createNamedParameter($mailinglist, Connection::PARAM_INT));
      $constraints[] = $expr->eq('mm_lists.tablenames', $queryBuilder->createNamedParameter('tt_address'));
      $constraints[] = $expr->eq('mm_lists.fieldname', $queryBuilder->createNamedParameter('categories'));
    }

    // 5. Alle gesammelten Constraints mit AND verknüpfen und anwenden
    $queryBuilder->where(...$constraints);

    $queryBuilder->groupBy('tt_address.uid');

    return $queryBuilder->executeQuery()->fetchAllAssociative();
  }

  /**
   * HINZUGEFÜGT: Neue private Methode, um Kategorien für eine Liste von Kontakten zu holen.
   *
   * @param int[] $contactUids
   * @return array
   */
  private function findCategoriesForContacts(array $contactUids): array
  {
    if (empty($contactUids)) {
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
        $queryBuilder->expr()->in('mm.uid_local', $queryBuilder->createNamedParameter($contactUids, Connection::PARAM_INT_ARRAY)),
        $queryBuilder->expr()->eq('mm.tablenames', $queryBuilder->createNamedParameter('tt_address')),
        $queryBuilder->expr()->eq('mm.fieldname', $queryBuilder->createNamedParameter('categories'))
      )
      ->executeQuery()
      ->fetchAllAssociative();

    // Gruppiere die Ergebnisse nach Kontakt-UID
    $categoriesByUid = [];
    foreach ($rows as $row) {
      $categoriesByUid[$row['uid_local']][] = [
        'uid' => $row['uid'],
        'title' => $row['title']
      ];
    }

    return $categoriesByUid;
  }

  public function countByClient($client): int
  {
    $query = $this->createQuery();
    $query->matching($query->equals('client', $client));
    return $query->count();
  }

  /**
   * Findet Kontakte anhand der E-Mail-Adresse, optional gefiltert nach Client.
   *
   * @param string $email
   * @param int|null $clientScope UID des Clients, 0 für interne, null für alle
   * @return QueryResultInterface<TtAddress>
   */
  public function findByEmail(string $email, ?int $clientScope = null): QueryResultInterface
  {
    $query = $this->createQuery();
    $constraints = [$query->equals('email', $email)];

    if ($clientScope !== null) {
      $constraints[] = $query->equals('client', $clientScope);
    }

    return $query->matching($query->logicalAnd(...$constraints))->execute();
  }

  /**
   * Findet Kontakte anhand von Namensbestandteilen, optional gefiltert nach Client.
   * Prüft verschiedene Kombinationen.
   *
   * @param string $firstName
   * @param string $lastName
   * @param int|null $clientScope UID des Clients, 0 für interne, null für alle
   * @return QueryResultInterface<TtAddress>
   */
  public function findByNameParts(string $firstName, string $lastName, ?int $clientScope = null): QueryResultInterface
  {
    $query = $this->createQuery();
    $nameConstraints = [];

    // Namenskombinationen: (Vorname=firstName AND Nachname=lastName) ODER vertauscht
    $nameConstraints = [
      $query->logicalAnd(
        $query->equals('firstName', $firstName),
        $query->equals('lastName', $lastName)
      ),
      $query->logicalAnd(
        $query->equals('firstName', $lastName),
        $query->equals('lastName', $firstName)
      ),
    ];

    // Gesamtkonstraints als Array sammeln
    $allConstraints = [
      $query->logicalOr(...$nameConstraints),
    ];

    // Optional: Mandanten-/Client-Filter hinzufügen
    if ($clientScope !== null) {
      $allConstraints[] = $query->equals('client', $clientScope);
    }

    // AND über alle gesammelten Constraints
    $query->matching($query->logicalAnd(...$allConstraints));

    return $query->execute();
  }

}
