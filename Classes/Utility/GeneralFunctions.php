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
use BucheggerOnline\Publicrelations\Domain\Model\SysCategory;


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
