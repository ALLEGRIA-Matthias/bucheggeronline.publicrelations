<?php
namespace BucheggerOnline\Publicrelations\Domain\Model;

use Allegria\AcBase\Utility\GeneralFunctions as UtilityGeneralFunctions;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Annotation\ORM\Lazy;

use BucheggerOnline\Publicrelations\Domain\Model\Dto\EmConfiguration;
use BucheggerOnline\Publicrelations\Domain\Model\SysCategory;
use BucheggerOnline\Publicrelations\Utility\GeneralFunctions;

use Allegria\AcDistribution\Domain\Model\Job;

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
 * Accreditation
 */
class Accreditation extends AbstractEntity
{

    /**
     * @var \DateTime
     */
    protected $crdate;

    /**
     * @var \DateTime
     */
    protected $tstamp;

    /**
     * @var int
     */
    protected $cruserId;

    /**
     * status
     *
     * @var int
     *
     */
    protected $status = 0;

    /**
     * invitationType
     *
     * @var \BucheggerOnline\Publicrelations\Domain\Model\Invitation
     */
    protected $invitationType;

    /**
     * invitationStatus
     *
     * @var int
     *
     */
    protected $invitationStatus = 0;

    /**
     * type
     *
     * @var int
     *
     */
    protected $type = 0;

    /**
     * guestType
     *
     * @var int
     *
     */
    protected $guestType = 0;

    /**
     * facie
     *
     * @var bool
     */
    protected $facie = false;

    /**
     * gender
     *
     * @var string
     *
     */
    protected $gender = '';

    /**
     * title
     *
     * @var string
     */
    protected $title = '';

    /**
     * firstName
     *
     * @var string
     *
     */
    protected $firstName = '';

    /**
     * middleName
     *
     * @var string
     */
    protected $middleName = '';

    /**
     * lastName
     *
     * @var string
     *
     */
    protected $lastName = '';

    /**
     * email
     *
     * @var string
     *
     */
    protected $email = '';

    /**
     * phone
     *
     * @var string
     */
    protected $phone = '';

    /**
     * requestNote
     *
     * @var string
     */
    protected $requestNote = '';

    /**
     * dsgvo
     *
     * @var bool
     */
    protected $dsgvo = false;

    /**
     * ip
     *
     * @var string
     */
    protected $ip = '';

    /**
     * medium
     *
     * @var string
     *
     */
    protected $medium = '';

    /**
     * ticketsWish
     *
     * @var int
     *
     */
    protected $ticketsWish = 0;

    /**
     * ticketsApproved
     *
     * @var int
     *
     */
    protected $ticketsApproved = 0;

    /**
     * ticketsReceived
     *
     * @var int
     *
     */
    protected $ticketsReceived = 0;

    /**
     * notes
     *
     * @var string
     */
    protected $notesReceived = '';

    /**
     * UID of the backend user currently locking this accreditation for check-in.
     * 0 if not locked.
     *
     * @var int
     */
    protected $lockingBeUserUid = 0; // Oder null, wenn das Feld nullable ist

    /**
     * Timestamp when the lock was acquired.
     * 0 if not locked.
     *
     * @var int
     */
    protected $lockingTstamp = 0; // Oder null

    /**
     * program
     *
     * @var int
     *
     */
    protected $program = 0;

    /**
     * pass
     *
     * @var int
     *
     */
    protected $pass = 0;

    /**
     * notes
     *
     * @var string
     */
    protected $notes = '';

    /**
     * notesSelect
     *
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<SysCategory>
     * @Lazy
     */
    protected $notesSelect;

    /**
     * photographer
     *
     * @var bool
     */
    protected $photographer = false;

    /**
     * camerateam
     *
     * @var bool
     */
    protected $camerateam = false;

    /**
     * seats
     *
     * @var string
     */
    protected $seats = '';

    /**
     * tickets
     *
     * @var string
     */
    protected $tickets = '';

    /**
     * event
     *
     * @var \BucheggerOnline\Publicrelations\Domain\Model\Event
     */
    protected $event;

    /**
     * guest
     *
     * @var \BucheggerOnline\Publicrelations\Domain\Model\TtAddress
     */
    protected $guest;

    /**
     * mediumType
     *
     * @var SysCategory
     */
    protected $mediumType;

    /**
     * logs
     *
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BucheggerOnline\Publicrelations\Domain\Model\Log>
     * @Lazy
     */
    protected $logs;

    /**
     * additionalAnswers
     *
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BucheggerOnline\Publicrelations\Domain\Model\Additionalanswer>
     * @TYPO3\CMS\Extbase\Annotation\ORM\Cascade("remove")
     * @Lazy
     */
    protected $additionalAnswers;

    /**
     * opened
     *
     * @var bool
     */
    protected $opened = false;

    /**
     * duplicateOf
     *
     * @var \BucheggerOnline\Publicrelations\Domain\Model\Accreditation
     */
    protected $duplicateOf;
    protected bool $isMaster = false;

    /**
     * @var string
     */
    protected $ignoredDuplicates = '';

    protected ?Job $distributionJob = null;

    /**
     * __construct
     */
    public function __construct()
    {

        //Do not remove the next line: It would break the functionality
        $this->initStorageObjects();
    }

    /**
     * Initializes all ObjectStorage properties
     * Do not modify this method!
     * It will be rewritten on each save in the extension builder
     * You may modify the constructor of this class instead
     *
     * @return void
     */
    protected function initStorageObjects()
    {
        $this->notesSelect = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
        $this->additionalAnswers = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
        $this->logs = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
    }

    /**
     * Get creation date
     *
     * @return int
     */
    public function getCrdate()
    {
        return $this->crdate;
    }

    /**
     * Set creation date
     *
     * @param int $crdate
     */
    public function setCrdate($crdate)
    {
        $this->crdate = $crdate;
    }

    /**
     * Get timestamp
     *
     * @return \DateTime
     */
    public function getTstamp()
    {
        return $this->tstamp;
    }

    /**
     * Set time stamp
     *
     * @param \DateTimeInterface $tstamp time stamp
     */
    public function setTstamp($tstamp)
    {
        $this->tstamp = $tstamp;
    }

    /**
     * Get id of creator user
     *
     * @return int
     */
    public function getCruserId()
    {
        return $this->cruserId;
    }

    /**
     * Set cruser id
     *
     * @param int $cruserId id of creator user
     */
    public function setCruserId($cruserId)
    {
        $this->cruserId = $cruserId;
    }

    /**
     * Returns the status
     *
     * @return int $status
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Returns the statusOutput
     */
    public function getStatusOutput()
    {
        switch ($this->status) {
            case 0:
                return 'ausstehend';
            case 1:
                return 'abgeschlossen';
            case -1:
                return 'abgelehnt';
            default:
                return 'unbekannt';
        }
    }

    /**
     * Gibt einen für den Check-in-Prozess spezifischen Statustext zurück.
     *
     * @return string
     */
    public function getCheckinStatusOutput(): string
    {
        $status = $this->getStatus();
        $ticketsReceived = $this->getTicketsReceived();
        $ticketsApproved = $this->getTicketsApproved();

        if ($status === 1) {
            // Status 1: Akkreditiert, aber noch keine Tickets als abgeholt markiert.
            // Auch wenn ticketsApproved = 0 ist, wäre es "Zur Abholung bereit" (um z.B. eine Anwesenheit zu markieren).
            // Oder "Keine Tickets zugeordnet", wenn ticketsApproved = 0. Fürs Erste:
            if ($ticketsApproved > 0) {
                if ($ticketsReceived === 0) { // Explizite Prüfung, dass noch nichts abgeholt wurde
                    return 'Zur Abholung bereit';
                } elseif ($ticketsReceived < $ticketsApproved) {
                    // Dieser Fall sollte eigentlich Status 2 sein, aber als Absicherung
                    return 'Teilweise abgeholt (Status prüfen)';
                } elseif ($ticketsReceived >= $ticketsApproved) {
                    // Dieser Fall sollte eigentlich Status 2 sein
                    return 'Tickets abgeholt (Status prüfen)';
                }
            } else { // ticketsApproved <= 0
                // Wenn keine Tickets genehmigt wurden, aber Status 1 (bestätigt)
                // Könnte "Anwesenheit bestätigt" oder "Keine Tickets" bedeuten.
                // Für den Check-in-Kontext ist "Keine Tickets abzuholen" vielleicht passend.
                return 'Keine Tickets abzuholen';
            }
        } elseif ($status === 2) {
            // Status 2: Bereits eingecheckt (zumindest teilweise)
            if ($ticketsApproved > 0 && $ticketsReceived < $ticketsApproved) {
                return 'Teilweise abgeholt'; // Deine Logik war "Teilweise zur Abholung bereit", aber wenn Status 2 ist, sind sie schon dabei oder waren da.
                // Ich interpretiere Status 2 als "mindestens ein Ticket wurde bereits als 'received' markiert".
            } elseif ($ticketsApproved > 0 && $ticketsReceived >= $ticketsApproved) {
                // >= um den Fall abzudecken, dass versehentlich mehr eingetragen wurden als genehmigt (sollte nicht passieren)
                return 'Tickets abgeholt';
            } elseif ($ticketsApproved <= 0) { // Keine Tickets genehmigt, aber Status 2 (sollte nicht vorkommen)
                return 'Eingecheckt (Keine Tickets)';
            } else { // Fallback für Status 2, falls ticketsReceived = 0 (sollte nicht vorkommen, wenn Status 2 korrekt gesetzt wird)
                return 'Eingecheckt (Status unklar)';
            }
        } elseif ($status === 0) {
            return 'Ausstehend (Noch nicht akkreditiert)';
        } elseif ($status === -1) {
            return 'Abgelehnt';
        }
        // Weitere Status, falls vorhanden (z.B. storniert)

        return 'Unbekannter Status (' . $status . ')'; // Fallback
    }

    /**
     * Gibt die entsprechende Bootstrap Badge-Klasse für den Check-in-Status zurück.
     *
     * @return string Die Bootstrap-Klasse (z.B. 'badge-success', 'badge-warning text-dark', 'badge-danger').
     */
    public function getCheckinStatusBadgeClass(): string
    {
        $status = $this->getStatus();
        $ticketsReceived = $this->getTicketsReceived();
        $ticketsApproved = $this->getTicketsApproved();

        if ($status === 1) {
            // "Zur Abholung bereit"
            // Dies gilt auch, wenn ticketsApproved === 0 und der Gast einfach nur als "bestätigt" gilt.
            // Wenn ticketsReceived > 0, sollte der Status eigentlich schon 2 sein.
            if ($ticketsApproved > 0 && $ticketsReceived === 0) {
                return 'success'; // Deine Vorgabe: Grün für "Zur Abholung bereit"
            } elseif ($ticketsApproved <= 0) { // Keine Tickets zugeordnet, aber bestätigt
                return 'secondary'; // Oder 'info' - nicht explizit von dir definiert, daher Fallback
            } else { // Unerwarteter Zustand bei Status 1
                return 'light text-dark'; // Neutral, da unklar
            }
        } elseif ($status === 2) {
            // Bereits eingecheckt (zumindest teilweise)
            if ($ticketsApproved > 0 && $ticketsReceived < $ticketsApproved) {
                // "Teilweise abgeholt"
                return 'warning text-dark'; // Deine Vorgabe: Gelb
            } elseif ($ticketsApproved > 0 && $ticketsReceived >= $ticketsApproved) {
                // "Tickets abgeholt" (vollständig)
                return 'danger'; // Deine Vorgabe: Rot für "Tickets abgeholt"
                // Überlegung: Ist 'success' hier nicht passender für "erledigt"?
            } elseif ($ticketsApproved <= 0) { // Eingecheckt, aber keine Tickets genehmigt (Sonderfall)
                return 'secondary'; // Oder 'info'
            } else { // Status 2, aber ticketsReceived === 0 (sollte nicht sein)
                return 'light text-dark';
            }
        } elseif ($status === 0) { // Ausstehend
            return 'light text-dark'; // Oder eine andere neutrale Farbe
        } elseif ($status === -1) { // Abgelehnt
            return 'dark'; // Oder 'danger', wenn es hervorgehoben werden soll
        }

        return 'secondary'; // Fallback für unbekannte Status
    }

    /**
     * Sets the status
     *
     * @param int $status
     * @return void
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * Returns the invitationType
     *
     * @return \BucheggerOnline\Publicrelations\Domain\Model\Invitation $invitationType
     */
    public function getInvitationType()
    {
        return $this->invitationType;
    }

    /**
     * Sets the invitationType
     *
     * @return void
     */
    public function setInvitationType(\BucheggerOnline\Publicrelations\Domain\Model\Invitation $invitationType)
    {
        $this->invitationType = $invitationType;
    }

    /**
     * Returns the invitationStatus
     *
     * @return int $invitationStatus
     */
    public function getInvitationStatus()
    {
        return $this->invitationStatus;
    }

    /**
     * Returns the invitationStatusOutput
     */
    public function getInvitationStatusOutput()
    {
        switch ($this->invitationStatus) {
            case 0:
                return 'vorbereitet';
            case 1:
                return 'eingeladen';
            case 2:
                return '1. Erinnerung';
            case 3:
                return '2. Erinnerung';
            case -1:
                return 'rückgemeldet';
            default:
                return 'unbekannt';
        }
    }

    /**
     * Sets the invitationStatus
     *
     * @param int $invitationStatus
     * @return void
     */
    public function setInvitationStatus($invitationStatus)
    {
        $this->invitationStatus = $invitationStatus;
    }

    /**
     * Returns the reportAllowed
     *
     * @return int $reportAllowed
     */
    public function isReportAllowed()
    {
        $hours = (GeneralUtility::makeInstance(EmConfiguration::class)->getInvitationReportStop()) + ($this->getEvent()->getInvitationReportStop());
        $datecompare = new \DateTime('+' . $hours . ' hours');

        if ($this->getInvitationStatus() == -1 || $this->getStatus() != 0 || $this->getEvent()->getDate() < $datecompare)
            $output = false;
        else
            $output = true;
        return $output;
    }

    /**
     * Returns the type
     *
     * @return int $type
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Returns the typeOutput
     */
    public function getTypeOutput()
    {
        switch ($this->type) {
            case 1:
                return 'Anfrage';
            case 2:
                return 'Einladung';
            default:
                return 'unbekannt';
        }
    }

    /**
     * Sets the type
     *
     * @param int $type
     * @return void
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * Returns the guestType
     *
     * @return int $guestType
     */
    public function getGuestType()
    {
        return $this->guestType;
    }

    /**
     * Returns the guestTypeOutput
     */
    public function getGuestTypeOutput()
    {
        switch ($this->guestType) {
            case 1:
                return 'Promi';
            case 2:
                return 'Presse';
            case 3:
                return 'Gewinnspiel';
            case 4:
                return 'Füllkarten';
            case 5:
                return 'Personal';
            case 6:
                return 'Talent';
            default:
                return 'unbekannt';
        }
    }

    /**
     * Sets the guestType
     *
     * @param int $guestType
     * @return void
     */
    public function setGuestType($guestType)
    {
        $this->guestType = $guestType;
    }

    // /**
    //  * Returns the facie
    //  *
    //  * @return bool $facie
    //  */
    // public function getFacie()
    // {
    // return $this->facie;
    // }

    /**
     * Sets the facie
     *
     * @param bool $facie
     * @return void
     */
    public function setFacie($facie)
    {
        $this->facie = $facie;
    }

    /**
     * Returns the boolean state of facie.
     * Dynamically checks if the linked guest has the 'facie' contact type (UID 604).
     *
     * @return bool
     */
    public function isFacie()
    {
        // 1. Zuerst prüfen, ob ein Gast-Objekt überhaupt verknüpft ist.
        //    Wenn nicht, geben wir den manuell gesetzten Wert zurück (Standard: false).
        if ($this->guest === null) {
            return $this->facie;
        }

        // 2. Hole die ContactTypes des Gastes. Dank Lazy Loading ist das performant.
        $contactTypes = $this->guest->getContactTypes();

        // 3. Schleife durch die (meist wenigen) ContactTypes des Gastes.
        foreach ($contactTypes as $contactType) {
            // 4. Prüfe, ob ein ContactType die UID 604 hat.
            if ($contactType->getUid() === 604) {
                // Wenn ja, ist es ein Facie. Sofort true zurückgeben und die Funktion beenden.
                return true;
            }
        }

        // 5. Wenn die Schleife durchläuft, ohne die UID 604 zu finden,
        //    geben wir den ursprünglich in der Datenbank gespeicherten Wert zurück.
        //    Das erlaubt manuelle Übersteuerungen.
        return $this->facie;
    }

    /**
     * Returns the gender
     *
     * @return string $gender
     */
    public function getGender()
    {
        return $this->gender;
    }

    /**
     * Sets the gender
     *
     * @param string $gender
     * @return void
     */
    public function setGender($gender)
    {
        $this->gender = $gender;
    }

    /**
     * Returns the title
     *
     * @return string $title
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Sets the title
     *
     * @param string $title
     * @return void
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * Returns the firstName
     *
     * @return string $firstName
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    public function getFirstNames()
    {
        $firstName = $this->getFirstName();
        $middleName = $this->getMiddleName();
        $firstNames = implode(' ', array_filter([$firstName, $middleName]));

        return $firstNames;
    }

    /**
     * Sets the firstName
     *
     * @param string $firstName
     * @return void
     */
    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;
    }

    /**
     * Returns the middleName
     *
     * @return string $middleName
     */
    public function getMiddleName()
    {
        return $this->middleName;
    }

    /**
     * Sets the middleName
     *
     * @param string $middleName
     * @return void
     */
    public function setMiddleName($middleName)
    {
        $this->middleName = $middleName;
    }

    /**
     * Returns the lastName
     *
     * @return string $lastName
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * Sets the lastName
     *
     * @param string $lastName
     * @return void
     */
    public function setLastName($lastName)
    {
        $this->lastName = $lastName;
    }

    /**
     * Get full name including title, first, middle and last name
     */
    public function getFullName(): string
    {
        $list = [
            $this->getTitle(),
            $this->getFirstName(),
            $this->getMiddleName(),
            $this->getLastName(),
        ];
        // if ($this->titleSuffix) {
        //     $name .= ', ' . $this->titleSuffix;
        // }

        return implode(' ', array_filter($list));
    }

    /**
     * Returns the email
     *
     * @return string $email
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Sets the email
     *
     * @param string $email
     * @return void
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }

    /**
     * Returns the phone
     *
     * @return string $phone
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * Sets the phone
     *
     * @param string $phone
     * @return void
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;
    }

    /**
     * Returns the requestNote
     *
     * @return string $requestNote
     */
    public function getRequestNote()
    {
        return $this->requestNote;
    }

    /**
     * Sets the requestNote
     *
     * @param string $requestNote
     * @return void
     */
    public function setRequestNote($requestNote)
    {
        $this->requestNote = $requestNote;
    }

    /**
     * Returns the dsgvo
     *
     * @return bool $dsgvo
     */
    public function getDsgvo()
    {
        return $this->dsgvo;
    }

    /**
     * Sets the dsgvo
     *
     * @param bool $dsgvo
     * @return void
     */
    public function setDsgvo($dsgvo)
    {
        $this->dsgvo = $dsgvo;
    }

    /**
     * Returns the boolean state of dsgvo
     *
     * @return bool
     */
    public function isDsgvo()
    {
        return $this->dsgvo;
    }

    /**
     * Returns the ip
     *
     * @return string $ip
     */
    public function getIp()
    {
        return $this->ip;
    }

    /**
     * Sets the ip
     *
     * @param string $ip
     * @return void
     */
    public function setIp($ip)
    {
        $this->ip = $ip;
    }

    /**
     * Returns the medium
     *
     * @return string $medium
     */
    public function getMedium()
    {
        return $this->medium;
    }

    /**
     * Sets the medium
     *
     * @param string $medium
     * @return void
     */
    public function setMedium($medium)
    {
        $this->medium = $medium;
    }

    /**
     * Returns the ticketsWish
     *
     * @return int $ticketsWish
     */
    public function getTicketsWish()
    {
        return $this->ticketsWish;
    }

    /**
     * Sets the ticketsWish
     *
     * @param int $ticketsWish
     * @return void
     */
    public function setTicketsWish($ticketsWish)
    {
        $this->ticketsWish = $ticketsWish;
    }

    /**
     * Returns the ticketsApproved
     *
     * @return int $ticketsApproved
     */
    public function getTicketsApproved()
    {
        return $this->ticketsApproved;
    }

    /**
     * Sets the ticketsApproved
     *
     * @param int $ticketsApproved
     * @return void
     */
    public function setTicketsApproved($ticketsApproved)
    {
        $this->ticketsApproved = $ticketsApproved;
    }

    /**
     * Returns the ticketsApprovedPlus
     *
     * @return int $ticketsApprovedPlus
     */
    public function getTicketsApprovedPlus()
    {
        return ($this->ticketsApproved) ? ($this->ticketsApproved - 1) : 0;
    }

    /**
     * Returns the ticketsReceived
     *
     * @return int $ticketsReceived
     */
    public function getTicketsReceived()
    {
        return $this->ticketsReceived;
    }

    /**
     * Sets the ticketsReceived
     *
     * @param int $ticketsReceived
     * @return void
     */
    public function setTicketsReceived($ticketsReceived)
    {
        $this->ticketsReceived = $ticketsReceived;
    }

    /**
     * Returns the ticketsPrepared
     *
     * @return int $ticketsPrepared
     */
    public function getTicketsPrepared()
    {
        return $this->getTicketsApproved() - $this->getTicketsReceived();
    }

    /**
     * Returns the notesReceived
     *
     * @return string $notesReceived
     */
    public function getNotesReceived()
    {
        return $this->notesReceived;
    }

    /**
     * Sets the notesReceived
     *
     * @param string $notesReceived
     * @return void
     */
    public function setNotesReceived($notesReceived)
    {
        $this->notesReceived = $notesReceived;
    }

    public function getLockingBeUserUid(): int
    {
        return $this->lockingBeUserUid;
    }

    public function setLockingBeUserUid(int $lockingBeUserUid): self
    {
        $this->lockingBeUserUid = $lockingBeUserUid;
        return $this;
    }

    public function getLockingBeUserName(): string
    {
        return GeneralFunctions::getBackendUserDisplayNameByUid($this->lockingBeUserUid);
    }

    public function getLockingTstamp(): int
    {
        return $this->lockingTstamp;
    }

    public function setLockingTstamp(int $lockingTstamp): self
    {
        $this->lockingTstamp = $lockingTstamp;
        return $this;
    }

    /**
     * Returns the program
     *
     * @return int $program
     */
    public function getProgram()
    {
        return $this->program;
    }

    /**
     * Sets the program
     *
     * @param int $program
     * @return void
     */
    public function setProgram($program)
    {
        $this->program = $program;
    }

    /**
     * Returns the pass
     *
     * @return int $pass
     */
    public function getPass()
    {
        return $this->pass;
    }

    /**
     * Sets the pass
     *
     * @param int $pass
     * @return void
     */
    public function setPass($pass)
    {
        $this->pass = $pass;
    }

    /**
     * Returns the notes
     *
     * @return string $notes
     */
    public function getNotes()
    {
        return $this->notes;
    }

    /**
     * Sets the notes
     *
     * @param string $notes
     * @return void
     */
    public function setNotes($notes)
    {
        $this->notes = $notes;
    }

    /**
     * Adds a notesSelect
     *
     * @param SysCategory $mediumType
     * @return void
     */
    public function addNotesSelect($notesSelect)
    {
        $this->notesSelect->attach($notesSelect);
    }

    /**
     * Removes a notesSelect
     *
     * @param SysCategory $notesSelectToRemove The Category to be removed
     * @return void
     */
    public function removeNotesSelect($notesSelectToRemove)
    {
        $this->notesSelect->detach($notesSelectToRemove);
    }

    /**
     * Returns the notesSelect
     *
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<SysCategory> notesSelect
     */
    public function getNotesSelect()
    {
        return $this->notesSelect;
    }

    /**
     * Sets the notesSelect
     *
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage<SysCategory> $notesSelect
     * @return void
     */
    public function setNotesSelect(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $notesSelect)
    {
        $this->notesSelect = $notesSelect;
    }

    /**
     * Returns the photographer
     *
     * @return bool $photographer
     */
    public function getPhotographer()
    {
        return $this->photographer;
    }

    /**
     * Sets the photographer
     *
     * @param bool $photographer
     * @return void
     */
    public function setPhotographer($photographer)
    {
        $this->photographer = $photographer;
    }

    /**
     * Returns the boolean state of photographer
     *
     * @return bool
     */
    public function isPhotographer()
    {
        return $this->photographer;
    }

    /**
     * Returns the camerateam
     *
     * @return bool $camerateam
     */
    public function getCamerateam()
    {
        return $this->camerateam;
    }

    /**
     * Sets the camerateam
     *
     * @param bool $camerateam
     * @return void
     */
    public function setCamerateam($camerateam)
    {
        $this->camerateam = $camerateam;
    }

    /**
     * Returns the boolean state of camerateam
     *
     * @return bool
     */
    public function isCamerateam()
    {
        return $this->camerateam;
    }

    /**
     * Returns the seats
     *
     * @return string $seats
     */
    public function getSeats()
    {
        return $this->seats;
    }

    /**
     * Sets the seats
     *
     * @param string $seats
     * @return void
     */
    public function setSeats($seats)
    {
        $this->seats = $seats;
    }

    /**
     * Returns the tickets
     *
     * @return string $tickets
     */
    public function getTickets()
    {
        return $this->tickets;
    }

    /**
     * Sets the tickets
     *
     * @param string $tickets
     * @return void
     */
    public function setTickets($tickets)
    {
        $this->tickets = $tickets;
    }

    /**
     * Returns the event
     *
     * @return \BucheggerOnline\Publicrelations\Domain\Model\Event $event
     */
    public function getEvent()
    {
        return $this->event;
    }

    /**
     * Sets the event
     *
     * @return void
     */
    public function setEvent(\BucheggerOnline\Publicrelations\Domain\Model\Event $event)
    {
        $this->event = $event;
    }

    /**
     * Returns the guest
     *
     * @param \BucheggerOnline\Publicrelations\Domain\Model\TtAddress $guest
     * @return void
     */
    public function getGuest()
    {
        return $this->guest;
    }

    /**
     * Returns the guestOutput (Refactored for PHP 8.4+)
     *
     * @return array $guestOutput
     */
    public function getGuestOutput(): array
    {
        // 1. Datenquelle bestimmen
        $source = $this->guest; // TtAddress object or null

        // 2. Daten normalisieren (PHP 8 null-safe ?: operator)
        $company = $source?->getCompany() ?? $this->getMedium();
        $firstName = $source?->getFirstName() ?? $this->getFirstName();
        $middleName = $source?->getMiddleName() ?? $this->getMiddleName();
        $firstNames = implode(' ', array_filter([$firstName, $middleName]));
        $lastName = $source?->getLastName() ?? $this->getLastName();
        $title = $source?->getTitle() ?? $this->getTitle();
        $titleSuffix = $source?->getTitleSuffix() ?? '';
        $phone = $source?->getMobile() ?? $this->getPhone();
        $email = $source?->getEmail() ?? $this->getEmail();
        $fullName = $source?->getFullName() ?? $this->getFullName();

        // Spezielle Felder
        $specialTitle = $source?->getSpecialTitle() ?? '';
        $rawGender = $source?->getGender() ?? $this->getGender();

        // 3. Gender normalisieren (String 'm'/'f' -> Int 1/2) für Ausgabe im Frontend
        $gender = $this->normalizeGender($rawGender);

        // 4. Sortiername (effizienter)
        $sortNameBase = $lastName ?: $company;
        $sort = GeneralFunctions::makeSortable($sortNameBase . ' ' . $firstName . ' ' . $middleName);

        // 5. Array bauen (nur einmal)
        return [
            'company' => $company,
            'name' => $fullName,
            'fullName' => $fullName,
            'gender' => $gender,
            'title' => $title,
            'titleSuffix' => $titleSuffix,
            'specialTitle' => $specialTitle,
            'firstName' => $firstName,
            'middleName' => $middleName,
            'firstNames' => $firstNames,
            'lastName' => $lastName,
            'phone' => $phone,
            'email' => $email,
            'sortName' => $sort,
            'salutation' => $this->getSalutation(),
            'salutationInformal' => $this->getSalutationInformal(),
        ];
    }

    /**
     * Normalisiert Gender auf Strings: 'f', 'm', 'v' oder '' (undefiniert)
     */
    private function normalizeGender(string|int|null $rawGender): string
    {
        // Cast auf String und lowercase macht das Matching robust
        return match (strtolower((string) $rawGender)) {
            'f', '1' => 'f',
            'm', '2' => 'm',
            'v', '0' => 'v',
            default => '', // Explizit "undefiniert" (weder m, f, noch v)
        };
    }

    /**
     * Formelle Anrede (Ohne Titel)
     */
    public function getSalutation(): string
    {
        // Special Title Check
        $source = $this->guest;
        $specialTitle = $source?->getSpecialTitle() ?? '';
        if (!empty($specialTitle)) {
            return $specialTitle;
        }

        // Daten holen
        $gender = $this->normalizeGender($source?->getGender() ?? $this->getGender());
        $lastName = trim($source?->getLastName() ?? $this->getLastName());

        // Full Name für Fallback
        $firstNames = trim($source?->getFirstNames() ?? $this->getFirstNames());
        $fullName = implode(' ', array_filter([$firstNames, $lastName]));

        return match ($gender) {
            'f' => 'Sehr geehrte Frau ' . $lastName,
            'm' => 'Sehr geehrter Herr ' . $lastName,
            'v' => 'Guten Tag ' . $fullName,
            // Aktuell identisch zu 'v', aber logisch getrennt für die Zukunft
            default => 'Guten Tag ' . $fullName,
        };
    }

    /**
     * Informelle Anrede
     */
    public function getSalutationInformal(): string
    {
        // Special Title Check
        $source = $this->guest;
        $specialTitle = $source?->getSpecialTitle() ?? '';
        if (!empty($specialTitle)) {
            return $specialTitle;
        }

        // Daten holen
        $gender = $this->normalizeGender($source?->getGender() ?? $this->getGender());
        $firstName = trim($source?->getFirstName() ?? $this->getFirstName());
        $lastName = trim($source?->getLastName() ?? $this->getLastName());

        $name = implode(' ', array_filter([$firstName, $lastName]));

        if (empty($name)) {
            return 'Hallo';
        }

        return match ($gender) {
            'f' => 'Liebe ' . $name,
            'm' => 'Lieber ' . $name,
            'v' => 'Hallo ' . $name,
            // Aktuell identisch zu 'v', aber logisch getrennt
            default => 'Hallo ' . $name,
        };
    }

    /**
     * Sets the guest
     *
     * @return void
     */
    public function setGuest(\BucheggerOnline\Publicrelations\Domain\Model\TtAddress $guest)
    {
        $this->guest = $guest;
    }

    /**
     * Adds a Log
     *
     * @return void
     */
    public function addLog(\BucheggerOnline\Publicrelations\Domain\Model\Log $log)
    {
        $this->logs->attach($log);
    }

    /**
     * Removes a Log
     *
     * @param \BucheggerOnline\Publicrelations\Domain\Model\Log $logToRemove The Log to be removed
     * @return void
     */
    public function removeLog(\BucheggerOnline\Publicrelations\Domain\Model\Log $logToRemove)
    {
        $this->logs->detach($logToRemove);
    }

    /**
     * Returns the logs
     *
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BucheggerOnline\Publicrelations\Domain\Model\Log> $logs
     */
    public function getLogs()
    {
        return $this->logs;
    }

    /**
     * Sets the logs
     *
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BucheggerOnline\Publicrelations\Domain\Model\Log> $logs
     * @return void
     */
    public function setLogs(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $logs)
    {
        $this->logs = $logs;
    }

    protected function getLogByCode($code)
    {
        $output = null;
        if ($this->getLogs()->count()) {
            foreach ($this->getLogs() as $log) {
                if ($log->getCode() == $code) {
                    $output = $log;
                    break;
                }
            }
        }
        return $output;
    }

    /**
     * Adds an Additionalanswer
     *
     * @param \BucheggerOnline\Publicrelations\Domain\Model\Additionalanswer $additionalAnswer
     * @return void
     */
    public function addAdditionalanswer($additionalAnswer)
    {
        $this->additionalAnswers->attach($additionalAnswer);
    }

    /**
     * Removes an Additionalanswer
     *
     * @param \BucheggerOnline\Publicrelations\Domain\Model\Additionalanswer $additionalAnswerToRemove The Link to be removed
     * @return void
     */
    public function removeAdditionalanswer($additionalAnswerToRemove)
    {
        $this->additionalAnswers->detach($additionalAnswerToRemove);
    }

    /**
     * Returns the additionalAnswers
     *
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BucheggerOnline\Publicrelations\Domain\Model\Additionalanswer> $additionalAnswers
     */
    public function getAdditionalanswers()
    {
        return $this->additionalAnswers;
    }

    /**
     * Sets the additionalAnswers
     *
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BucheggerOnline\Publicrelations\Domain\Model\Additionalanswer> $additionalAnswers
     * @return void
     */
    public function setAdditionalanswers(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $additionalAnswers)
    {
        $this->additionalAnswers = $additionalAnswers;
    }

    /**
     * Returns the opened
     *
     * @return bool $opened
     */
    public function getOpened()
    {
        return $this->opened;
    }

    /**
     * Sets the opened
     *
     * @param bool $opened
     * @return void
     */
    public function setOpened($opened)
    {
        $this->opened = $opened;
    }

    /**
     * Returns the boolean state of opened
     *
     * @return bool
     */
    public function isOpened()
    {
        return $this->opened;
    }

    /**
     * Adds a
     *
     * @param SysCategory $mediumType
     * @return void
     */
    public function addMediumType($mediumType)
    {
        $this->mediumType->attach($mediumType);
    }

    /**
     * Removes a
     *
     * @param SysCategory $mediumTypeToRemove The Category to be removed
     * @return void
     */
    public function removeMediumType($mediumTypeToRemove)
    {
        $this->mediumType->detach($mediumTypeToRemove);
    }

    /**
     * Returns the mediumType
     *
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<SysCategory> mediumType
     */
    public function getMediumType()
    {
        return $this->mediumType;
    }

    /**
     * Sets the mediumType
     *
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage<SysCategory> $mediumType
     * @return void
     */
    public function setMediumType(SysCategory $mediumType)
    {
        $this->mediumType = $mediumType;
    }

    /**
     * Returns the timeToReact
     *
     * @return bool $timeToReact
     */
    public function getTimeToReact()
    {
        if ($this->getStatus() === 0) {
            if ($this->getInvitationStatus() === 0) {
                return true;
                // } elseif ($this->getInvitationStatus()===1) {
                //   if ($this->getLogByCode('A-IC1') && ($this->getLogByCode('A-IC1')->getCrdate()->sub(new \DateInterval('PT'.$configuration->getInvitationRemindAfter().'D')) >= new \DateTime())) {
                //     return true;
                //   } elseif ($this->getLogByCode('A-IC2') && ($this->getLogByCode('A-IC2')->getCrdate()->sub(new \DateInterval('PT'.$configuration->getInvitationRemindAfter().'D')) >= new \DateTime())) {
                //     return true;
                //   } else {
                //     return false;
                //   }
            }
        } else {
            return false;
        }
    }

    /**
     * Returns the duplicateOf
     *
     * @return \BucheggerOnline\Publicrelations\Domain\Model\Accreditation $duplicateOf
     */
    public function getDuplicateOf()
    {
        return $this->duplicateOf;
    }

    /**
     * Sets the invitationType
     *
     * @return void
     */
    public function setDuplicateOf(\BucheggerOnline\Publicrelations\Domain\Model\Accreditation $duplicateOf): void
    {
        $this->duplicateOf = $duplicateOf;
    }

    public function getIsMaster(): bool
    {
        return $this->isMaster;
    }
    public function setIsMaster(bool $isMaster): void
    {
        $this->isMaster = $isMaster;
    }

    /**
     * Gibt die UIDs der ignorierten Duplikate als Array zurück.
     * @return array<int>
     */
    public function getIgnoredDuplicates(): array
    {
        return array_map('intval', GeneralUtility::trimExplode(',', $this->ignoredDuplicates, true));
    }

    /**
     * Fügt eine oder mehrere UIDs zur Liste der ignorierten Duplikate hinzu.
     * @param int|array<int> $uidsToAdd
     * @return void
     */
    public function addIgnoredDuplicates($uidsToAdd): void
    {
        $currentUids = $this->getIgnoredDuplicates();

        if (!is_array($uidsToAdd)) {
            $uidsToAdd = [$uidsToAdd];
        }

        foreach ($uidsToAdd as $uid) {
            if (!in_array($uid, $currentUids, true)) {
                $currentUids[] = $uid;
            }
        }

        $this->setIgnoredDuplicates(implode(',', $currentUids));
    }

    /**
     * Entfernt eine oder mehrere UIDs von der Liste der ignorierten Duplikate.
     * @param int|array<int> $uidsToRemove
     * @return void
     */
    public function removeIgnoredDuplicates($uidsToRemove): void
    {
        $currentUids = $this->getIgnoredDuplicates();

        if (!is_array($uidsToRemove)) {
            $uidsToRemove = [$uidsToRemove];
        }

        $updatedUids = array_diff($currentUids, $uidsToRemove);

        $this->setIgnoredDuplicates(implode(',', $updatedUids));
    }

    /**
     * Setzt die UIDs der ignorierten Duplikate (intern).
     * @param string $ignoredDuplicates
     * @return void
     */
    protected function setIgnoredDuplicates(string $ignoredDuplicates): void
    {
        $this->ignoredDuplicates = $ignoredDuplicates;
    }

    public function getDistributionJob(): ?Job
    {
        return $this->distributionJob;
    }

    public function setDistributionJob(?Job $distributionJob): void
    {
        $this->distributionJob = $distributionJob;
    }
}
