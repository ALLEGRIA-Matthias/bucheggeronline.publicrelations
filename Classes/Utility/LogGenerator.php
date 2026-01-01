<?php
namespace BucheggerOnline\Publicrelations\Utility;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\DataHandling\DataHandler;

use BucheggerOnline\Publicrelations\Domain\Model\Dto\EmConfiguration;

use BucheggerOnline\Publicrelations\Domain\Model\Log;
use BucheggerOnline\Publicrelations\Domain\Repository\LogRepository;

use BucheggerOnline\Publicrelations\Domain\Model\Event;
use BucheggerOnline\Publicrelations\Domain\Model\Accreditation;
use BucheggerOnline\Publicrelations\Domain\Model\TtAddress;
use BucheggerOnline\Publicrelations\Domain\Model\Mail;


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
 * LogGenerator
 */
class LogGenerator extends AbstractEntity
{
  public function __construct(
    private readonly LogRepository $logRepository,
    private readonly PersistenceManager $persistenceManager
  ) {
  }

  /**
   * Returns the createEventLog
   *
   * @return $createEventLog
   */
  protected function createLog($log)
  {
    if ($GLOBALS['BE_USER'])
      $log->setCruserId($GLOBALS['BE_USER']->user['uid']);
    $log->setCrdate(new \DateTime());
    $log->setTstamp(new \DateTime());

    $this->logRepository->add($log);
    $this->persistenceManager->persistAll();

    return $log;
  }

  /**
   * Returns the createEventLog
   *
   * @return $createEventLog
   */
  public function createEventLog($code, Event $event, $changes = null)
  {
    $newLog = new Log();

    $newLog->setEvent($event);
    $newLog->setFunction('Termin');
    $newLog->setCode($code);

    switch ($code) {
      case 'E-CR0':
        $newLog->setSubject('Erstellt - Einzeltermin');
        break;
      case 'E-CR1':
        $newLog->setSubject('Erstellt - manuelle Terminliste');
        break;
      case 'E-CR2':
        $newLog->setSubject('Erstellt - Wochentag-Generator');
        break;
      case 'E-E1';
        $newLog->setSubject('Änderung');
        break;
      case 'E-E2';
        $newLog->setSubject('Änderung - ausgewählte Termine');
        if ($changes) {
          $newLog->setNotes('Änderungen bei: ' . implode(", ", $changes));
        }
        break;
      case 'E-P-OD';
        $newLog->setSubject('Verschiebung');
        $newLog->setNotes('Dieser Termin wurde verschoben auf: ' . $event->getNewEvent()->getDate()->format('d.m.Y H:i'));
        break;
      case 'E-P-ND';
        $newLog->setSubject('Erstellt - Verschiebung');
        $newLog->setNotes('Dieser Termin wurde verschoben vom ' . $event->getOldEvent()->getDate()->format('d.m.Y H:i'));
        break;
      case 'E-P-C';
        $newLog->setSubject('Verschiebung zurückgenommen');
        $newLog->setNotes('Die Terminverschiebung auf ' . $event->getNewEvent()->getDate()->format('d.m.Y H:i') . ' wurde zurückgenommen.');
        break;
      case 'E-P-D';
        $newLog->setSubject('Gelöscht - zurückgenomme Terminverschiebung');
        $newLog->setNotes('Die Terminverschiebung auf ' . $event->getOldEvent()->getDate()->format('d.m.Y H:i') . ' wurde zurückgenommen, daher wird dieser Termin gelöscht.');
        break;
      case 'E-P-AM';
        $newLog->setSubject('Akkreditierungen verlegt');
        $newLog->setNotes('Alle Akkreditierungen wurden auf ' . $event->getNewEvent()->getDate()->format('d.m.Y H:i') . ' verschoben.');
        break;
      case 'E-P-AMU';
        $newLog->setSubject('Akkreditierungen vom Ersatztermin zugeordnet');
        $newLog->setNotes('Alle Akkreditierungen wurden vom ' . $event->getDate()->format('d.m.Y H:i') . ' auf den ursprünglichen Termin verschoben.');
        break;
      case 'E-CA':
        $newLog->setSubject('Absage');
        break;
      case 'E-D':
        $newLog->setSubject('Gelöscht');
        break;
      case 'E-DI':
        $newLog->setSubject('Löschung abgelehnt');
        $newLog->setNotes('Der Termin konnte nicht wie gewünscht gelöscht werden, da noch Akkreditierungen vorhanden sind.');
        break;
    }
    return $this->createLog($newLog);
  }

  /**
   * Returns the createAccreditationLog
   *
   * @return $createAccreditationLog
   */
  public function createAccreditationLog($code, Accreditation $accreditation, $changes = null)
  {
    $newLog = new Log();

    $newLog->setAccreditation($accreditation);
    $newLog->setFunction('Akkreditierung');
    $newLog->setCode($code);

    switch ($code) {
      case 'A-CR1':
        $newLog->setSubject('Erstellt - manuell');
        break;
      case 'A-CR2':
        $newLog->setSubject('Erstellt - Wizzard');
        break;
      case 'A-CRW':
        $newLog->setSubject('Erstellt - Website');
        break;
      case 'A-E';
        $newLog->setSubject('Änderung');
        break;
      case 'A-EC';
        $newLog->setSubject('Änderung - Auswahl');
        if ($changes) {
          $newLog->setNotes('Änderungen bei: ' . implode(", ", $changes));
        }
        break;
      case 'A-M';
        $newLog->setSubject('Verschoben');
        $newLog->setNotes($changes);
        break;
      case 'A-A':
        $newLog->setSubject('Bestätigt');
        break;
      case 'A-AE':
        $newLog->setSubject('Bestätigt - Express');
        break;
      case 'A-R':
        $newLog->setSubject('Abgelehnt');
        break;
      case 'A-D':
        $newLog->setSubject('Gelöscht');
        break;
      case 'A-Email-S':
        $newLog->setSubject('E-Mail - Akkreditierung übermittelt');
        $newLog->setNotes('E-Mail an ' . $accreditation->getGuestOutput()['email']);
        break;
      case 'A-Email-P':
        $newLog->setSubject('E-Mail - Akkreditierung erhalten');
        $newLog->setNotes('E-Mail an Allegria');
        break;
      case 'AI-Email-P':
        $newLog->setSubject('E-Mail - Rückmeldung auf Warteliste');
        $newLog->setNotes('E-Mail an Allegria');
        break;
      case 'A-Email-A':
        $newLog->setSubject('E-Mail - Akkreditierung bestätigt');
        $newLog->setNotes('E-Mail an ' . $accreditation->getGuestOutput()['email']);
        break;
      case 'A-Email-R':
        $newLog->setSubject('E-Mail - Akkreditierung abgelehnt');
        $newLog->setNotes('E-Mail an ' . $accreditation->getGuestOutput()['email']);
        break;
      case 'A-Email-Err':
        $newLog->setSubject('E-Mail - Fehlermeldung');
        $newLog->setNotes($changes);
        break;
      case 'AI-S-C':
        $newLog->setSubject('Neuer Status: erstellt');
        break;
      case 'AI-S-I1':
        $newLog->setSubject('Neuer Status: versandt');
        break;
      case 'AI-S-I2':
        $newLog->setSubject('Neuer Status: erinnert');
        break;
      case 'AI-S-I3':
        $newLog->setSubject('Neuer Status: letzte Erinnerung');
        break;
      case 'AI-S-R':
        $newLog->setSubject('Neuer Status: abgesagt');
        break;
      case 'AI-S-A':
        $newLog->setSubject('Neuer Status: zugesagt');
        break;
      case 'AI-RS-A':
        $newLog->setSubject('Bestätigung erneut versandt.');
        break;
      case 'AI-S-AW':
        $newLog->setSubject('Neuer Status: Warteliste');
        break;
      case 'AI-S-AR':
        $newLog->setSubject('Neuer Status: Kontingent erschöpft');
        break;
      case 'AI-S-X':
        $newLog->setSubject('Neuer Status: abgelegt - keine Rückmeldung');
        break;
      case 'AI-RS':
        $newLog->setSubject('Einladung erneut versandt.');
        break;
      case 'AI-L-O':
        $newLog->setSubject('Link - Geöffnet');
        $newLog->setNotes('IP: ' . $this->getIp());
        break;
      case 'AI-L-A':
        $newLog->setSubject('Link - Neuer Status: zugesagt');
        $newLog->setNotes('IP: ' . $this->getIp());
        break;
      case 'AI-L-R':
        $newLog->setSubject('Link - Neuer Status: abgesagt');
        $newLog->setNotes('IP: ' . $this->getIp());
        break;
      case 'AI-L-AW':
        $newLog->setSubject('Link - Neuer Status: Warteliste');
        $newLog->setNotes('IP: ' . $this->getIp());
        break;
      case 'AI-Reset':
        $newLog->setSubject('Einladung zurückgesetzt');
        $newLog->setNotes('Die Absage wurde zurückgesetzt und als Einladung erneut in die Liste aufgenommen.');
        break;
      case 'AI-Email-I1':
        $newLog->setSubject('E-Mail - Einladung versandt');
        $newLog->setNotes('E-Mail an ' . $accreditation->getGuestOutput()['email']);
        break;
      case 'AI-Email-I2':
        $newLog->setSubject('E-Mail - Erinnerung versandt');
        $newLog->setNotes('E-Mail an ' . $accreditation->getGuestOutput()['email']);
        break;
      case 'AI-Email-I3':
        $newLog->setSubject('E-Mail - Letzte Erinnerung versandt');
        $newLog->setNotes('E-Mail an ' . $accreditation->getGuestOutput()['email']);
        break;
      case 'AI-Email-A':
        $newLog->setSubject('E-Mail - Einladung bestätigt');
        $newLog->setNotes('E-Mail an ' . $accreditation->getGuestOutput()['email']);
        break;
      case 'AI-Email-AW':
        $newLog->setSubject('E-Mail - Warteliste');
        $newLog->setNotes('E-Mail an ' . $accreditation->getGuestOutput()['email']);
        break;
      case 'AI-Email-AW-OK':
        $newLog->setSubject('E-Mail - Warteliste - Kommen doch möglich');
        $newLog->setNotes('E-Mail an ' . $accreditation->getGuestOutput()['email']);
        break;
      case 'AI-Email-AW-NO':
        $newLog->setSubject('E-Mail - Warteliste - Kontingent erschöpft');
        $newLog->setNotes('E-Mail an ' . $accreditation->getGuestOutput()['email']);
        break;
      case 'AI-Email-R':
        $newLog->setSubject('E-Mail - Einladung abgelehnt');
        $newLog->setNotes('E-Mail an ' . $accreditation->getGuestOutput()['email']);
        break;
      case 'AI-Email-W':
        $newLog->setSubject('E-Mail - Einladung auf Warteliste');
        $newLog->setNotes('E-Mail an ' . $accreditation->getGuestOutput()['email']);
        break;
      case 'AI-Email-O':
        $newLog->setSubject('E-Mail - Einladung zurückgenommen - überbucht');
        $newLog->setNotes('E-Mail an ' . $accreditation->getGuestOutput()['email']);
        break;
      case 'A-CheckIn';
        $newLog->setSubject('Eingecheckt');
        $newLog->setNotes($changes);
        break;
    }
    return $this->createLog($newLog);
  }

  /**
   * Erstellt einen Log-Datensatz für eine Akkreditierung über den DataHandler
   * oder gibt die Log-Daten als Array zurück.
   *
   * @param string $code
   * @param int $accreditationUid
   * @param int $eventUid
   * @param int $ttAddressUid
   * @param string $email
   * @param string $notes
   * @param bool $returnAsArray
   * @return array|void Gibt ein Daten-Array zurück, wenn $returnAsArray true ist, sonst void.
   */
  public function createAccreditationLogData(
    string $code,
    int $accreditationUid,
    int $eventUid = 0,
    int $ttAddressUid = 0,
    string $email = '',
    string $notes = '',
    bool $returnAsArray = false
  ): array|null {
    $subject = '';

    switch ($code) {
      case 'A-CR1':
        $subject = 'Erstellt - manuell';
        break;
      case 'A-CR2':
        $subject = 'Erstellt - Wizzard';
        break;
      case 'A-CRW':
        $subject = 'Erstellt - Website';
        break;
      case 'A-E';
        $subject = 'Änderung';
        break;
      case 'A-EC';
        $subject = 'Änderung - Auswahl';
        if ($changes) {
          $notes = 'Änderungen bei: ' . implode(", ", $changes);
        }
        break;
      case 'A-M';
        $subject = 'Verschoben';
        $notes = $changes;
        break;
      case 'A-A':
        $subject = 'Bestätigt';
        break;
      case 'A-AE':
        $subject = 'Bestätigt - Express';
        break;
      case 'A-R':
        $subject = 'Abgelehnt';
        break;
      case 'A-D':
        $subject = 'Gelöscht';
        break;
      case 'A-Email-S':
        $subject = 'E-Mail - Akkreditierung übermittelt';
        $notes = 'E-Mail an ' . $email;
        break;
      case 'A-Email-P':
        $subject = 'E-Mail - Akkreditierung erhalten';
        $notes = 'E-Mail an Allegria';
        break;
      case 'AI-Email-P':
        $subject = 'E-Mail - Rückmeldung auf Warteliste';
        $notes = 'E-Mail an Allegria';
        break;
      case 'A-Email-A':
        $subject = 'E-Mail - Akkreditierung bestätigt';
        $notes = 'E-Mail an ' . $email;
        break;
      case 'A-Email-R':
        $subject = 'E-Mail - Akkreditierung abgelehnt';
        $notes = 'E-Mail an ' . $email;
        break;
      case 'A-Email-Err':
        $subject = 'E-Mail - Fehlermeldung';
        $notes = $changes;
        break;
      case 'AI-S-C':
        $subject = 'Neuer Status: erstellt';
        break;
      case 'AI-S-I1':
        $subject = 'Neuer Status: versandt';
        break;
      case 'AI-S-I2':
        $subject = 'Neuer Status: erinnert';
        break;
      case 'AI-S-I3':
        $subject = 'Neuer Status: letzte Erinnerung';
        break;
      case 'AI-S-R':
        $subject = 'Neuer Status: abgesagt';
        break;
      case 'AI-S-A':
        $subject = 'Neuer Status: zugesagt';
        break;
      case 'AI-RS-A':
        $subject = 'Bestätigung erneut versandt.';
        break;
      case 'AI-S-AW':
        $subject = 'Neuer Status: Warteliste';
        break;
      case 'AI-S-AR':
        $subject = 'Neuer Status: Kontingent erschöpft';
        break;
      case 'AI-S-X':
        $subject = 'Neuer Status: abgelegt - keine Rückmeldung';
        break;
      case 'AI-RS':
        $subject = 'Einladung erneut versandt.';
        break;
      case 'AI-L-O':
        $subject = 'Link - Geöffnet';
        $notes = 'IP: ' . $this->getIp();
        break;
      case 'AI-L-A':
        $subject = 'Link - Neuer Status: zugesagt';
        $notes = 'IP: ' . $this->getIp();
        break;
      case 'AI-L-R':
        $subject = 'Link - Neuer Status: abgesagt';
        $notes = 'IP: ' . $this->getIp();
        break;
      case 'AI-L-AW':
        $subject = 'Link - Neuer Status: Warteliste';
        $notes = 'IP: ' . $this->getIp();
        break;
      case 'AI-Reset':
        $subject = 'Einladung zurückgesetzt';
        $notes = 'Die Absage wurde zurückgesetzt und als Einladung erneut in die Liste aufgenommen.';
        break;
      case 'AI-Email-I1':
        $subject = 'E-Mail - Einladung versandt';
        $notes = 'E-Mail an ' . $email;
        break;
      case 'AI-Email-I2':
        $subject = 'E-Mail - Erinnerung versandt';
        $notes = 'E-Mail an ' . $email;
        break;
      case 'AI-Email-I3':
        $subject = 'E-Mail - Letzte Erinnerung versandt';
        $notes = 'E-Mail an ' . $email;
        break;
      case 'AI-Email-A':
        $subject = 'E-Mail - Einladung bestätigt';
        $notes = 'E-Mail an ' . $email;
        break;
      case 'AI-Email-AW':
        $subject = 'E-Mail - Warteliste';
        $notes = 'E-Mail an ' . $email;
        break;
      case 'AI-Email-AW-OK':
        $subject = 'E-Mail - Warteliste - Kommen doch möglich';
        $notes = 'E-Mail an ' . $email;
        break;
      case 'AI-Email-AW-NO':
        $subject = 'E-Mail - Warteliste - Kontingent erschöpft';
        $notes = 'E-Mail an ' . $email;
        break;
      case 'AI-Email-R':
        $subject = 'E-Mail - Einladung abgelehnt';
        $notes = 'E-Mail an ' . $email;
        break;
      case 'AI-Email-W':
        $subject = 'E-Mail - Einladung auf Warteliste';
        $notes = 'E-Mail an ' . $email;
        break;
      case 'AI-Email-O':
        $subject = 'E-Mail - Einladung zurückgenommen - überbucht';
        $notes = 'E-Mail an ' . $email;
        break;
      case 'A-CheckIn';
        $subject = 'Eingecheckt';
        $notes = $changes;
        break;
      default:
        $subject = 'Unbekannter Log-Code: ' . $code;
        break;
    }

    $logData = [
      'pid' => 2,
      'function' => 'Akkreditierung',
      'code' => $code,
      'subject' => $subject,
      'notes' => $notes,
      'accreditation' => $accreditationUid,
      'tt_address' => $ttAddressUid,
      'event' => $eventUid,
    ];

    // Bedingte Rückgabe des Arrays oder direkte Verarbeitung
    if ($returnAsArray) {
      return $logData;
    }

    $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
    $dataMap['tx_publicrelations_domain_model_log']['NEW'] = $logData;
    $dataHandler->start($dataMap, []);
    $dataHandler->process_datamap();

    return null;
  }

  /**
   * Returns the createAddressLog
   *
   * @return $createAddressLog
   */
  public function createAddressLog($code, TtAddress $address, $changes = null)
  {
    $newLog = new Log();

    $newLog->setTtAddress($address);
    $newLog->setFunction('Kontakt');
    $newLog->setCode($code);

    switch ($code) {
      case 'C-CR1':
        $newLog->setSubject('Erstellt - via Akkreditierung');
        break;
      case 'C-CR2':
        $newLog->setSubject('Erstellt - via Importer');
        break;
      case 'C-E':
        $newLog->setSubject('Geändert - via Importer');
        break;
      case 'C-M1':
        $newLog->setSubject('Zusammengeführt - via Importer');
        $newLog->setNotes($changes);
        break;
      case 'C-M0':
        $newLog->setSubject('Nach Zusammenführung gelöscht - via Importer');
        $newLog->setNotes($changes);
        break;
      case 'C-O':
        $newLog->setSubject('Kontakt Überschrieben - via Importer');
        $newLog->setNotes($changes);
        break;
      case 'C-D':
        $newLog->setSubject('Gelöscht - via Importer');
        $newLog->setNotes($changes);
        break;
    }
    return $this->createLog($newLog);
  }

  /**
   * Returns the createMailLog
   *
   * @return $createMailLog
   */
  public function createMailLog($code, Mail $mail, $notes = '')
  {
    $newLog = new Log();

    $newLog->setMail($mail);
    $newLog->setFunction('Mailing');
    $newLog->setCode($code);
    if ($notes)
      $newLog->setNotes($notes);

    switch ($code) {
      case 'M-Sent':
        $newLog->setSubject('Versandt');
        break;
      case 'M-Error':
        $newLog->setSubject('Fehlermeldung beim Versand');
        break;
    }
    return $this->createLog($newLog);
  }

  /**
   * Get IP from Webuser
   *
   * @return IP-Adresse
   */
  protected static function getIp()
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

}
