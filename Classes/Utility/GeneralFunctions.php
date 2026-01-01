<?php
namespace BucheggerOnline\Publicrelations\Utility;

use Psr\Http\Message\ServerRequestInterface;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;

use BucheggerOnline\Publicrelations\Utility\LogGenerator;

use BucheggerOnline\Publicrelations\Domain\Model\Event;
use BucheggerOnline\Publicrelations\Domain\Model\TtAddress;
use BucheggerOnline\Publicrelations\Domain\Model\SysCategory;
use BucheggerOnline\Publicrelations\Domain\Repository\TtAddressRepository;


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
 * Client
 */
class GeneralFunctions extends AbstractEntity
{
  public function __construct(
    private readonly TtAddressRepository $ttAddressRepository,
    private readonly LogGenerator $logGenerator
  ) {
  }

  public static function replaceMutated(string $string): string
  {
    $mutatedVowels = ["ä" => "ae", "ü" => "ue", "ö" => "oe", "Ä" => "Ae", "Ü" => "Ue", "Ö" => "Oe"];
    return strtr($string, $mutatedVowels);
  }

  public static function replaceSigns(string $string): string
  {
    return str_replace('&', 'und', $string);
  }

  public static function removeSpecialCharacters(string $string): string
  {
    return preg_replace("/[^A-Za-z0-9 ]/", '', $string);
  }

  public static function decodeLanguages(string $string): string
  {
    return iconv('UTF-8', 'ISO-8859-1//TRANSLIT//IGNORE', $string);
  }

  public static function lowercase(string $string): string
  {
    return strtolower($string);
  }


  public static function makeSortable(string $string): string
  {

    $string = self::replaceMutated($string);
    $string = self::replaceSigns($string);
    $string = self::decodeLanguages($string);
    $string = self::removeSpecialCharacters($string);
    $string = self::lowercase($string);

    return trim($string);
  }

  /**
   * Liefert die Base-URL der aktuellen Site (PSR-7 Request → normalizedParams).
   */
  public static function getBaseUri(): string
  {
    /** @var ServerRequestInterface|null $psrRequest */
    $psrRequest = $GLOBALS['TYPO3_REQUEST'] ?? null;
    if (!$psrRequest) {
      throw new \RuntimeException('TYPO3 PSR-7 Request nicht verfügbar');
    }

    $normalizedParams = $psrRequest->getAttribute('normalizedParams');
    if (!$normalizedParams) {
      throw new \RuntimeException('NormalizedParams nicht im Request vorhanden');
    }

    // getSiteUrl() liefert z.B. "https://example.com/"
    return rtrim($normalizedParams->getSiteUrl(), '/') . '/';
  }

  /**
   * Liefert die komplette Request-URI (Pfad + Query).
   */
  public static function getRequestUrl(): string
  {
    /** @var ServerRequestInterface $psrRequest */
    $psrRequest = $GLOBALS['TYPO3_REQUEST'] ?? null;
    if (!$psrRequest) {
      throw new \RuntimeException('TYPO3 PSR-7 Request nicht verfügbar');
    }

    $normalizedParams = $psrRequest->getAttribute('normalizedParams');
    if (!$normalizedParams) {
      throw new \RuntimeException('NormalizedParams nicht im Request vorhanden');
    }

    // getRequestUrl() liefert z.B. "/seite/unterseite?foo=bar"
    return $normalizedParams->getRequestUrl();
  }

  public static function getPressEvents($givenEvents)
  {
    $events = [];

    if ($givenEvents->count()) {
      foreach ($givenEvents as $event) {
        if (
          $event->getAccreditation()
          && $event->isUpcoming()
          && !$event->isCanceled()
          && !$event->getNewEvent()
        ) {
          $events[] = $event;
        }
      }
    }

    return $events ?: null;
  }

  public static function getUpcomingEvents($givenEvents)
  {
    $events = [];

    if ($givenEvents->count()) {
      foreach ($givenEvents as $event) {
        if ($event->isUpcoming()) {
          $events[] = $event;
        }
      }
    }

    return $events ?: null;
  }

  /**
   * Get IP from Webuser
   *
   * @return IP-Adresse
   */
  public static function getIp()
  {

    if (!empty($_SERVER['TYPO3_DB'])) {
      $ip = $_SERVER['HTTP_CLIENT_IP'];            // Check ip from share internet
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
      $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];      //to check ip is pass from proxy
    } else {
      $ip = $_SERVER['REMOTE_ADDR'];
    }

    return $ip;
  }


  // DATE HELPERS

  public static function validateDate($date, $format = 'd.m.Y H:i')
  {
    $d = date_create_from_format($format, $date);
    // The Y ( 4 digits year ) returns TRUE for any integer with any number of digits so changing the comparison from == to === fixes the issue.
    return $d && $d->format($format) === $date;
  }

  public static function createDatesFromSchedule($schedule, $daterange)
  {
    $dayVariants = [
      'Mo ',
      'Mon ',
      'Montag ',

      'Di ',
      'Die ',
      'Dienstag ',

      'Mi ',
      'Mit ',
      'Mittwoch ',

      'Do ',
      'Don ',
      'Donnerstag ',

      'Fr ',
      'Fre ',
      'Freitag ',

      'Sa ',
      'Sam ',
      'Samstag',

      'So ',
      'Son ',
      'Sonntag '
    ];

    $dayReplace = [
      'Mon ',
      'Mon ',
      'Mon ',

      'Tue ',
      'Tue ',
      'Tue ',

      'Wed ',
      'Wed ',
      'Wed ',

      'Thu ',
      'Thu ',
      'Thu ',

      'Fri ',
      'Fri ',
      'Fri ',

      'Sat ',
      'Sat ',
      'Sat ',

      'Sun ',
      'Sun ',
      'Sun '
    ];

    $schedule = str_replace($dayVariants, $dayReplace, $schedule);
    $schedule = array_filter(array_map('trim', explode("\n", $schedule)));

    $period = [];
    $curDate = clone $daterange['start'];
    while (true) {
      $nextDate = clone $daterange['end'];
      foreach ($schedule as $modifier) {
        $nextTime = (clone $curDate)->modify($modifier);
        if ($nextTime > $curDate and $nextTime < $nextDate) {
          $nextDate = $nextTime;
        }
      }
      if ($nextDate >= $daterange['end'])
        break;
      $period[] = $nextDate;
      $curDate = $nextDate;
    }
    return $period;
  }


  /**
   * Create new Contact
   */
  public function createContact(array $contactData, string $logCode = 'C-CR1'): TtAddress
  {
    // 1) Gender aufbereiten (int → m/f/v, sonst direkt übernehmen oder Default)
    $gender = $contactData['gender'] ?? 'v';
    if (is_int($gender)) {
      switch ($gender) {
        case 1:
          $gender = 'f';
          break;
        case 2:
          $gender = 'm';
          break;
        case 0:
          $gender = 'v';
          break;
        default: /* bleibt, was es ist */
          ;
          break;
      }
    }

    // 2) Basis-Objekt anlegen
    $now = new \DateTimeImmutable();
    /** @var TtAddress $newContact */
    $newContact = GeneralUtility::makeInstance(TtAddress::class);
    $newContact->setPid((int) ($contactData['pid'] ?? 0));
    // $newContact->setCruserId((int) ($contactData['cruserId'] ?? 0));
    $newContact->setCrdate($now->getTimestamp());
    $newContact->setTstamp($now->getTimestamp());
    $newContact->setGender($gender);

    // 3) Optional-Felder nur setzen, wenn sie existieren und nicht leer sind
    if (!empty($contactData['title'])) {
      $newContact->setTitle($contactData['title']);
    }
    if (!empty($contactData['titleSuffix'])) {
      $newContact->setTitleSuffix($contactData['titleSuffix']);
    }
    if (!empty($contactData['specialTitle'])) {
      $newContact->setSpecialTitle($contactData['specialTitle']);
    }
    if (!empty($contactData['firstName'])) {
      $newContact->setFirstName($contactData['firstName']);
    }
    if (!empty($contactData['middleName'])) {
      $newContact->setMiddleName($contactData['middleName']);
    }
    if (!empty($contactData['lastName'])) {
      $newContact->setLastName($contactData['lastName']);
    }

    // 4) Name-Feld zusammenbauen
    $name = $contactData['name']
      ?? trim(
        ($contactData['firstName'] ?? '') . ' ' .
        ($contactData['middleName'] ?? '') . ' ' .
        ($contactData['lastName'] ?? '')
      );
    $newContact->setName(preg_replace('/\s+/', ' ', $name));

    // 5) Weitere optionale Standard-Felder
    foreach (['email', 'personally', 'phone', 'mobile', 'company', 'address', 'zip', 'city', 'country', 'description'] as $field) {
      if (!empty($contactData[$field])) {
        $setter = 'set' . ucfirst($field);
        $newContact->$setter($contactData[$field]);
      }
    }

    // 6) Kategorien zuordnen
    if (!empty($contactData['categories']) && is_array($contactData['categories'])) {
      foreach ($contactData['categories'] as $category) {
        if ($category instanceof \BucheggerOnline\Publicrelations\Domain\Model\SysCategory) {
          $newContact->addCategory($category);
        }
      }
    }

    // 7) Duplikate verarbeiten
    if (!empty($contactData['duplicates']) && is_array($contactData['duplicates'])) {
      foreach ($contactData['duplicates'] as $duplicateUid) {
        $dup = $this->ttAddressRepository->findByUid((int) $duplicateUid);
        if ($dup !== null) {
          $newContact->addDuplicate($dup);
        }
      }
    }

    // 8) CopyToPid & Valid flag
    if (isset($contactData['copyToPid'])) {
      $newContact->setCopyToPid((int) $contactData['copyToPid']);
    }
    if (isset($contactData['valid'])) {
      $newContact->setValid((bool) $contactData['valid']);
    }

    // 9) Log anlegen und Kontakt speichern
    $logContact = $this->logGenerator->createAddressLog($logCode, $newContact);
    $newContact->addLog($logContact);

    $this->ttAddressRepository->add($newContact);

    return $newContact;
  }

  /**
   * Bearbeitet/Merged einen Kontakt mit Daten von einem anderen Kontaktobjekt.
   *
   * @param TtAddress $targetContact Der zu aktualisierende Kontakt (Ziel).
   * @param TtAddress $sourceContact Der Kontakt, von dem die Daten stammen (Quelle).
   * @param string $logCode Code für den Log-Eintrag.
   * @return TtAddress Der aktualisierte Zielkontakt.
   */
  public function editContact(TtAddress $targetContact, TtAddress $sourceContact, string $logCode = 'C-E'): TtAddress
  {
    // Zeitstempel des Ziels aktualisieren
    $targetContact->setTstamp((new \DateTimeImmutable())->getTimestamp());

    // Felder vom sourceContact auf targetContact übertragen (Merge-Logik definieren!)
    // Strategie: Überschreibe im Ziel, wenn die Quelle einen "sinnvollen" Wert hat.
    // Für Strings: nicht null und nicht leer. Für Booleans: direkt übernehmen.

    // Persönliche Daten
    if ($sourceContact->getGender() !== null && $sourceContact->getGender() !== '') {
      $targetContact->setGender($sourceContact->getGender());
    }
    if ($sourceContact->getTitle() !== null && $sourceContact->getTitle() !== '') {
      $targetContact->setTitle($sourceContact->getTitle());
    }
    // Beachte die Feldnamen (firstName vs. first_name) - hier camelCase gemäß TtAddress Model von FriendsOfTYPO3
    if ($sourceContact->getFirstName() !== null && trim($sourceContact->getFirstName()) !== '') {
      $targetContact->setFirstName($sourceContact->getFirstName());
    }
    if ($sourceContact->getMiddleName() !== null && trim($sourceContact->getMiddleName()) !== '') {
      $targetContact->setMiddleName($sourceContact->getMiddleName());
    }
    if ($sourceContact->getLastName() !== null && trim($sourceContact->getLastName()) !== '') {
      $targetContact->setLastName($sourceContact->getLastName());
    }
    // Das 'name'-Feld wird oft automatisch aus Vorname/Nachname/Firma generiert.
    // Überlege, ob du es hier manuell setzen oder die Logik der Elternklasse nutzen willst.
    // Wenn es eine explizite 'name'-Eigenschaft im sourceContact gibt, die du übernehmen willst:
    if ($sourceContact->getName() !== null && trim($sourceContact->getName()) !== '' && $sourceContact->getName() !== $targetContact->getName()) {
      // Nur setzen, wenn es sich vom automatisch generierten Namen des Ziels unterscheiden könnte
      // oder wenn es eine explizite Override-Logik gibt.
      // Vorsicht, wenn getName() im TtAddress-Modell dynamisch ist!
      // Wenn 'name' eine echte Property ist:
      // $targetContact->setName($sourceContact->getName());
    }


    if ($sourceContact->getEmail() !== null && trim($sourceContact->getEmail()) !== '') {
      $targetContact->setEmail($sourceContact->getEmail());
    }
    // Für boolesche Werte wie 'personally'
    $targetContact->setPersonally($sourceContact->isPersonally()); // Direkt den Wert übernehmen


    // Kontaktdaten
    if ($sourceContact->getPhone() !== null && trim($sourceContact->getPhone()) !== '') {
      $targetContact->setPhone($sourceContact->getPhone());
    }
    if ($sourceContact->getMobile() !== null && trim($sourceContact->getMobile()) !== '') {
      $targetContact->setMobile($sourceContact->getMobile());
    }
    if ($sourceContact->getFax() !== null && trim($sourceContact->getFax()) !== '') {
      $targetContact->setFax($sourceContact->getFax());
    }
    if ($sourceContact->getWww() !== null && trim($sourceContact->getWww()) !== '') {
      $targetContact->setWww($sourceContact->getWww());
    }

    // Firma & Position
    if ($sourceContact->getCompany() !== null && trim($sourceContact->getCompany()) !== '') {
      $targetContact->setCompany($sourceContact->getCompany());
    }
    if ($sourceContact->getPosition() !== null && trim($sourceContact->getPosition()) !== '') {
      $targetContact->setPosition($sourceContact->getPosition());
    }

    // Adresse
    if ($sourceContact->getAddress() !== null && trim($sourceContact->getAddress()) !== '') {
      $targetContact->setAddress($sourceContact->getAddress());
    }
    if ($sourceContact->getZip() !== null && trim($sourceContact->getZip()) !== '') {
      $targetContact->setZip($sourceContact->getZip());
    }
    if ($sourceContact->getCity() !== null && trim($sourceContact->getCity()) !== '') {
      $targetContact->setCity($sourceContact->getCity());
    }
    if ($sourceContact->getCountry() !== null && trim($sourceContact->getCountry()) !== '') {
      $targetContact->setCountry($sourceContact->getCountry());
    }

    // Beschreibung - hier ein Beispiel, wie man Beschreibungen zusammenführen könnte
    if ($sourceContact->getDescription() !== null && trim($sourceContact->getDescription()) !== '') {
      $currentDescription = $targetContact->getDescription() ?? '';
      $sourceDescription = trim($sourceContact->getDescription());
      if ($currentDescription !== $sourceDescription) { // Nur wenn unterschiedlich
        $targetContact->setDescription(
          trim($currentDescription . ($currentDescription ? "\n---\nDaten gemerged von UID " . $sourceContact->getUid() . ":\n" : "") . $sourceDescription)
        );
      }
    }

    // Kategorien zusammenführen (füge Kategorien vom Quellkontakt hinzu, wenn sie im Zielkontakt noch nicht existieren)
    if ($sourceContact->getCategories() instanceof ObjectStorage) {
      /** @var SysCategory $categoryFromSource */
      foreach ($sourceContact->getCategories() as $categoryFromSource) {
        $categoryExistsInTarget = false;
        if ($targetContact->getCategories() instanceof ObjectStorage) {
          /** @var SysCategory $categoryInTarget */
          foreach ($targetContact->getCategories() as $categoryInTarget) {
            if ($categoryInTarget->getUid() === $categoryFromSource->getUid()) {
              $categoryExistsInTarget = true;
              break;
            }
          }
        }
        if (!$categoryExistsInTarget) {
          $targetContact->addCategory($categoryFromSource); // Verwendet deine addCategory Methode
        }
      }
    }

    // Log-Eintrag erstellen
    $changes = '';
    if ($logCode === 'C-M1' || $logCode === 'C-O') {
      $changes = ($sourceContact->getUid())
        ? 'Daten von Kontakt UID ' . $sourceContact->getUid() . ' übernommen.'
        : 'Manuelle Datenänderung (Quelle hatte keine UID).'; // Sollte bei Merge nicht passieren
    }
    $logEntry = $this->logGenerator->createAddressLog($logCode, $targetContact, $changes);
    $targetContact->addLog($logEntry);

    $this->ttAddressRepository->update($targetContact);

    return $targetContact;
  }

  /**
   * Ruft das Daten-Array des aktuell eingeloggten Backend-Benutzers ab.
   * Enthält Felder wie 'uid', 'username', 'realName', 'email' etc.
   *
   * @return array|null Das Benutzerdaten-Array oder null, wenn kein Benutzer eingeloggt ist.
   */
  public function getCurrentBackendUserRecord(): ?array
  {
    $backendUserAuthentication = $this->getBackendUserAuthentication();
    if ($backendUserAuthentication && $backendUserAuthentication->user && isset($backendUserAuthentication->user['uid'])) {
      return $backendUserAuthentication->user;
    }
    return null;
  }

  /**
   * Ruft die UID des aktuell eingeloggten Backend-Benutzers ab.
   *
   * @return int Die UID des Benutzers oder 0, wenn kein Benutzer eingeloggt ist.
   */
  public function getCurrentBackendUserUid(): int
  {
    $userRecord = $this->getCurrentBackendUserRecord();
    return (int) ($userRecord['uid'] ?? 0);
  }

  /**
   * Ruft den RealName (oder als Fallback den Username) des aktuell eingeloggten Backend-Benutzers ab.
   *
   * @return string|null Der Name oder null, wenn kein Benutzer eingeloggt ist.
   */
  public function getCurrentBackendUserDisplayName(): ?string
  {
    $userRecord = $this->getCurrentBackendUserRecord();
    if ($userRecord) {
      return $userRecord['realName'] ?: $userRecord['username'] ?: null;
    }
    return null;
  }

  /**
   * Ruft spezifische Felder eines Backend-Benutzers anhand seiner UID ab.
   * Standardmäßig werden 'uid', 'username', 'realName', 'email' geladen.
   * Es werden nur aktive (nicht gelöschte, nicht deaktivierte) Benutzer berücksichtigt.
   *
   * @param int $userUid Die UID des zu ladenden Backend-Benutzers.
   * @param array $fieldsToSelect Ein Array der Felder, die aus der be_users Tabelle selektiert werden sollen.
   * @return array|null Ein assoziatives Array mit den Benutzerdaten oder null, wenn nicht gefunden oder inaktiv.
   */
  public static function getBackendUserRecordByUid(int $userUid, array $fieldsToSelect = ['uid', 'username', 'realName', 'email']): ?array
  {
    if ($userUid <= 0) {
      return null;
    }

    $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
      ->getQueryBuilderForTable('be_users');

    $userRecord = $queryBuilder
      ->select(...$fieldsToSelect) // Spread-Operator für flexible Feldauswahl
      ->from('be_users')
      ->where(
        $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($userUid, Connection::PARAM_INT))
      )
      ->andWhere($queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)))
      ->andWhere($queryBuilder->expr()->eq('disable', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)))
      ->setMaxResults(1)
      ->executeQuery()
      ->fetchAssociative();

    return $userRecord ?: null; // Gibt das Array oder null zurück
  }

  /**
   * Ruft den RealName (oder als Fallback den Username) eines Backend-Benutzers anhand seiner UID ab.
   *
   * @param int $userUid Die UID des zu ladenden Backend-Benutzers.
   * @return string|null Der Name oder null, wenn nicht gefunden oder inaktiv.
   */
  public static function getBackendUserDisplayNameByUid(int $userUid): ?string
  {
    $userRecord = self::getBackendUserRecordByUid($userUid, ['realName', 'username']);
    if ($userRecord) {
      return $userRecord['realName'] ?: $userRecord['username'] ?: null;
    }
    return null;
  }

  /**
   * Ruft spezifische Userdaten eines Backend-Benutzers anhand seiner UID ab.
   *
   * @param int $userUid Die UID des zu ladenden Backend-Benutzers.
   * @return array<string, mixed>|null Ein Array mit Benutzerdaten (uid, email, realName, username)
   * oder null, wenn der Benutzer nicht gefunden wurde oder die UID ungültig ist.
   */
  public static function getBackendUserDataByUid(int $userUid): ?array
  {
    if ($userUid <= 0) {
      return null;
    }

    $requestedFields = ['uid', 'realName', 'username', 'email'];
    $userRecord = self::getBackendUserRecordByUid($userUid, $requestedFields);

    if ($userRecord) {
      return [
        'uid' => (int) ($userRecord['uid'] ?? $userUid), // UID sollte immer die $userUid sein oder aus dem Record kommen
        'email' => $userRecord['email'] ?? null,
        'realName' => $userRecord['realName'] ?? null,
        'username' => $userRecord['username'] ?? null
      ];
    }
    return null;
  }

  /**
   * Hilfsfunktion, um die BackendUserAuthentication-Instanz zu holen.
   * Kann für Caching oder Mocking in Tests erweitert werden.
   *
   * @return BackendUserAuthentication|null
   */
  protected static function getBackendUserAuthentication(): ?BackendUserAuthentication
  {
    // $GLOBALS['BE_USER'] ist im Backend-Kontext normalerweise verfügbar.
    if (isset($GLOBALS['BE_USER']) && $GLOBALS['BE_USER'] instanceof BackendUserAuthentication) {
      return $GLOBALS['BE_USER'];
    }

    // Fallback über den Context API (nützlich, aber $GLOBALS['BE_USER'] ist direkter für das volle Objekt)
    // $context = GeneralUtility::makeInstance(Context::class);
    // $beUserAspect = $context->getAspect('backend.user');
    // if ($beUserAspect->isLoggedIn()) {
    //     // Gibt aber nicht direkt das $backendUser->user Array zurück, nur ID, Username etc.
    // }
    return null;
  }



}
