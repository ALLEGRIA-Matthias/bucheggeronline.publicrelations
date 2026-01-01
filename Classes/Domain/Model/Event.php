<?php
namespace BucheggerOnline\Publicrelations\Domain\Model;


use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Annotation\ORM;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use BucheggerOnline\Publicrelations\Domain\Model\Dto\EmConfiguration;


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
 * Event
 */
class Event extends AbstractEntity
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

    protected ?int $pid = null;

    /**
     * date
     *
     * @var \DateTime
     *
     */
    protected $date;

    /**
     * dateFulltime
     *
     * @var bool
     */
    protected $dateFulltime = false;

    /**
     * types
     *
     * @var \BucheggerOnline\Publicrelations\Domain\Model\SysCategory
     */
    protected $type;

    /**
     * title
     *
     * @var string
     */
    protected $title = '';

    /**
     * overwriteTheaterevent
     *
     * @var int
     */
    protected $overwriteTheaterevent = 0;

    /**
     * duration
     *
     * @var int
     */
    protected $duration = 0;

    /**
     * durationApprox
     *
     * @var bool
     */
    protected $durationApprox = false;

    /**
     * durationWithBreak
     *
     * @var bool
     */
    protected $durationWithBreak = false;

    /**
     * accreditation
     *
     * @var int
     */
    protected $accreditation = 0;

    /**
     * online
     *
     * @var bool
     */
    protected $online = false;

    /**
     * canceled
     *
     * @var bool
     */
    protected $canceled = false;

    /**
     * private
     *
     * @var bool
     */
    protected $private = false;

    /**
     * checkin
     *
     * @var bool
     */
    protected $checkin = false;

    /**
     * typeOverwrite
     *
     * @var string
     */
    protected $typeOverwrite = '';

    /**
     * notesOverwrite
     *
     * @var string
     */
    protected $notesOverwrite = '';

    /**
     * notesManual
     *
     * @var string
     */
    protected $notesManual = '';

    /**
     * invitations
     *
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BucheggerOnline\Publicrelations\Domain\Model\Invitation>
     */
    protected $invitations;

    /**
     * invitationSubject
     *
     * @var string
     *
     */
    protected $invitationSubject = '';

    /**
     * invitationFrom
     *
     * @var string
     *
     */
    protected $invitationFrom = '';

    /**
     * invitationFromPersonally
     *
     * @var string
     *
     */
    protected $invitationFromPersonally = '';

    /**
     * invitationText
     *
     * @var string
     *
     */
    protected $invitationText = '';

    /**
     * invitationTextPersonally
     *
     * @var string
     *
     */
    protected $invitationTextPersonally = '';

    /**
     * invitationNotesTitle
     *
     * @var string
     *
     */
    protected $invitationNotesTitle = '';

    /**
     * invitationNotesDescription
     *
     * @var string
     *
     */
    protected $invitationNotesDescription = '';

    /**
     * invitationNotesRequired
     *
     * @var bool
     *
     */
    protected $invitationNotesRequired = false;

    /**
     * invitationImage
     *
     * @var \TYPO3\CMS\Extbase\Domain\Model\FileReference
     * @TYPO3\CMS\Extbase\Annotation\ORM\Cascade("remove")
     */
    protected $invitationImage;

    /**
     * invitationLogo
     *
     * @var \TYPO3\CMS\Extbase\Domain\Model\FileReference
     * @TYPO3\CMS\Extbase\Annotation\ORM\Cascade("remove")
     */
    protected $invitationLogo;

    /**
     * invitationReportStop
     *
     * @var int
     */
    protected $invitationReportStop = 0;

    /**
     * manualConfirmation
     *
     * @var bool
     *
     */
    protected $manualConfirmation = false;

    /**
     * ticketsQuota
     *
     * @var int
     *
     */
    protected $ticketsQuota = 0;

    /**
     * waitingQuota
     *
     * @var int
     *
     */
    protected $waitingQuota = 0;

    /**
     * locationNote
     *
     * @var string
     */
    protected $locationNote = '';

    /**
     * location
     *
     * @var \BucheggerOnline\Publicrelations\Domain\Model\Location
     */
    protected $location;

    /**
     * notes
     *
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BucheggerOnline\Publicrelations\Domain\Model\SysCategory>
     */
    protected $notes;

    /**
     * links
     *
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BucheggerOnline\Publicrelations\Domain\Model\Link>
     * @TYPO3\CMS\Extbase\Annotation\ORM\Cascade("remove")
     * @TYPO3\CMS\Extbase\Annotation\ORM\Lazy
     */
    protected $links;

    /**
     * additionalFields
     *
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BucheggerOnline\Publicrelations\Domain\Model\Additionalfield>
     * @TYPO3\CMS\Extbase\Annotation\ORM\Cascade("remove")
     * @TYPO3\CMS\Extbase\Annotation\ORM\Lazy
     */
    protected $additionalFields;

    /**
     * client
     *
     * @var \BucheggerOnline\Publicrelations\Domain\Model\Client
     */
    protected $client;

    #[ORM\Lazy]
    protected ObjectStorage $partners;

    /**
     * campaign
     *
     * @var \BucheggerOnline\Publicrelations\Domain\Model\Campaign
     */
    protected $campaign;

    #[ORM\Lazy]
    protected ObjectStorage $accreditations;

    /**
     * event
     *
     * @var \BucheggerOnline\Publicrelations\Domain\Model\Event
     */
    protected $oldEvent;

    /**
     * event
     *
     * @var \BucheggerOnline\Publicrelations\Domain\Model\Event
     */
    protected $newEvent;

    /**
     * logs
     *
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BucheggerOnline\Publicrelations\Domain\Model\Log>
     * @TYPO3\CMS\Extbase\Annotation\ORM\Lazy
     */
    protected $logs;

    /**
     * @var array
     *
     * @internal cache property for query results
     */
    protected $cache = [];

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
        $this->partners = new ObjectStorage();
        $this->notes = new ObjectStorage();
        $this->links = new ObjectStorage();
        $this->additionalFields = new ObjectStorage();
        $this->accreditations = new ObjectStorage();
        $this->logs = new ObjectStorage();
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
     * Gibt die PID des Events zurück.
     *
     * @return int|null
     */
    public function getPid(): ?int
    {
        return $this->pid;
    }

    /**
     * Setzt die PID des Events.
     *
     * @param int|null $pid 
     */
    public function setPid(?int $pid): void
    {
        $this->pid = $pid;
    }

    /**
     * Returns the date
     *
     * @return \DateTimeInterface $date
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Sets the date
     *
     * @return void
     */
    public function setDate(\DateTimeInterface $date)
    {
        $this->date = $date;
    }

    /**
     * Returns the dateGroup
     *
     * @return string $dateGroup
     */
    public function getDateGroup()
    {
        $fmt = datefmt_create('de_DE', \IntlDateFormatter::FULL, \IntlDateFormatter::FULL, null, null, "dd. LLLL yyyy");
        return datefmt_format($fmt, $this->getDate()->getTimestamp());
    }

    /**
     * Returns the boolean state of upcoming
     *
     * @return bool
     */
    public function isUpcoming()
    {
        return ($this->getDate() >= new \DateTime('today midnight'));
    }

    /**
     * Returns the dateFulltime
     *
     * @return bool $dateFulltime
     */
    public function getDateFulltime()
    {
        return $this->dateFulltime;
    }

    /**
     * Sets the dateFulltime
     *
     * @param bool $dateFulltime
     * @return void
     */
    public function setDateFulltime($dateFulltime)
    {
        $this->dateFulltime = $dateFulltime;
    }

    /**
     * Returns the boolean state of dateFulltime
     *
     * @return bool
     */
    public function isDateFulltime()
    {
        return $this->dateFulltime;
    }

    /**
     * Returns the type
     *
     * @return \BucheggerOnline\Publicrelations\Domain\Model\SysCategory type
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Sets the type
     *
     * @return void
     */
    public function setType(\BucheggerOnline\Publicrelations\Domain\Model\SysCategory $type)
    {
        $this->type = $type;
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
     * Returns the selectLabel
     *
     * @return string $selectLabel
     */
    public function getSelectLabel()
    {
        $fmt = datefmt_create('de_DE', \IntlDateFormatter::FULL, \IntlDateFormatter::FULL, null, null, "dd.MM.YYYY 'um' HH:mm");
        return datefmt_format($fmt, $this->getDate()->getTimestamp()) . ' | ' . $this->getTitle() . ' (' . ($this->getClient() && $this->getClient()->getName()) ? $this->getClient()->getName() : ' ' . ') [' . $this->getAccreditations()->count() . ' Akkr.]';
    }

    /**
     * Returns the sortLabel
     *
     * @return string $sortLabel
     */
    public function getSortLabel()
    {
        $fmt = datefmt_create('de_DE', \IntlDateFormatter::FULL, \IntlDateFormatter::FULL, null, null, "YYYYMMddHHmm");
        return datefmt_format($fmt, $this->getDate()->getTimestamp());
    }

    /**
     * Returns the typeOverwrite
     *
     * @return string $typeOverwrite
     */
    public function getTypeOverwrite()
    {
        return $this->typeOverwrite;
    }

    /**
     * Sets the typeOverwrite
     *
     * @param string $typeOverwrite
     * @return void
     */
    public function setTypeOverwrite($typeOverwrite)
    {
        $this->typeOverwrite = $typeOverwrite;
    }

    /**
     * Returns the notesOverwrite
     *
     * @return string $notesOverwrite
     */
    public function getNotesOverwrite()
    {
        return $this->notesOverwrite;
    }

    /**
     * Sets the notesOverwrite
     *
     * @param string $notesOverwrite
     * @return void
     */
    public function setNotesOverwrite($notesOverwrite)
    {
        $this->notesOverwrite = $notesOverwrite;
    }

    /**
     * Returns the notesManual
     *
     * @return string $notesManual
     */
    public function getNotesManual()
    {
        return $this->notesManual;
    }

    /**
     * Sets the notesManual
     *
     * @param string $notesManual
     * @return void
     */
    public function setNotesManual($notesManual)
    {
        $this->notesManual = $notesManual;
    }

    /**
     * Returns the invitationSubject
     *
     * @return string $invitationSubject
     */
    public function getInvitationSubject()
    {
        return $this->invitationSubject;
    }

    /**
     * Sets the invitationSubject
     *
     * @param string $invitationSubject
     * @return void
     */
    public function setInvitationSubject($invitationSubject)
    {
        $this->invitationSubject = $invitationSubject;
    }

    /**
     * Returns the invitationFrom
     *
     * @return string $invitationFrom
     */
    public function getInvitationFrom()
    {
        return $this->invitationFrom;
    }

    /**
     * Sets the invitationFrom
     *
     * @param string $invitationFrom
     * @return void
     */
    public function setInvitationFrom($invitationFrom)
    {
        $this->invitationFrom = $invitationFrom;
    }

    /**
     * Returns the invitationFrom
     *
     * @return string $invitationFromPersonally
     */
    public function getInvitationFromPersonally()
    {
        ($this->invitationFromPersonally) ? $output = $this->invitationFromPersonally : $output = $this->invitationFrom;
        return $output;
    }

    /**
     * Sets the invitationFromPersonally
     *
     * @param string $invitationFromPersonally
     * @return void
     */
    public function setInvitationFromPersonally($invitationFromPersonally)
    {
        $this->invitationFromPersonally = $invitationFromPersonally;
    }

    /**
     * Returns the invitationText
     *
     * @return string $invitationText
     */
    public function getInvitationText()
    {
        return $this->invitationText;
    }

    /**
     * Sets the invitationText
     *
     * @param string $invitationText
     * @return void
     */
    public function setInvitationText($invitationText)
    {
        $this->invitationText = $invitationText;
    }

    /**
     * Returns the invitationTextPersonally
     *
     * @return string $invitationTextPersonally
     */
    public function getInvitationTextPersonally()
    {
        ($this->invitationTextPersonally) ? $output = $this->invitationTextPersonally : $output = $this->invitationText;
        return $output;
    }

    /**
     * Sets the invitationTextPersonally
     *
     * @param string $invitationTextPersonally
     * @return void
     */
    public function setInvitationTextPersonally($invitationTextPersonally)
    {
        $this->invitationTextPersonally = $invitationTextPersonally;
    }

    /**
     * Returns the invitationImage
     *
     * @return \TYPO3\CMS\Extbase\Domain\Model\FileReference $invitationImage
     */
    public function getInvitationImage()
    {
        if ($this->invitationImage) {
            $image = $this->invitationImage;
        } elseif ($this->getCampaign()) {
            $image = $this->getCampaign()->getLogo();
        } else {
            $image = $this->getClient()->getLogo();
        }
        return $image;
    }

    /**
     * Returns the invitationLogo
     *
     * @return \TYPO3\CMS\Extbase\Domain\Model\FileReference $invitationLogo
     */
    public function getInvitationLogo()
    {
        return $this->invitationLogo;
    }

    /**
     * Returns the invitationNotesTitle
     *
     * @return string $invitationNotesTitle
     */
    public function getInvitationNotesTitle()
    {
        return $this->invitationNotesTitle;
    }

    /**
     * Sets the invitationNotesTitle
     *
     * @param string $invitationNotesTitle
     * @return void
     */
    public function setInvitationNotesTitle($invitationNotesTitle)
    {
        $this->invitationNotesTitle = $invitationNotesTitle;
    }

    /**
     * Returns the invitationNotesDescription
     *
     * @return string $invitationNotesDescription
     */
    public function getInvitationNotesDescription()
    {
        return $this->invitationNotesDescription;
    }

    /**
     * Sets the invitationNotesDescription
     *
     * @param string $invitationNotesDescription
     * @return void
     */
    public function setInvitationNotesDescription($invitationNotesDescription)
    {
        $this->invitationNotesDescription = $invitationNotesDescription;
    }

    /**
     * Returns the invitations
     *
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BucheggerOnline\Publicrelations\Domain\Model\Invitation> $invitations
     */
    public function getInvitations()
    {
        return $this->invitations;
    }

    /**
     * Sets the invitations
     *
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BucheggerOnline\Publicrelations\Domain\Model\Invitation> $invitations
     * @return void
     */
    public function setInvitations($invitations)
    {
        $this->invitations = $invitations;
    }

    /**
     * Checks if sending invitations is possible based on the configuration
     * of the first associated Invitation design.
     *
     * @return bool True if invitations can be sent, false otherwise.
     */
    public function isInvitationAllowed(): bool
    {
        // Get the collection of invitation designs
        $invitations = $this->getInvitations();

        // Check if there is at least one invitation design associated
        if ($invitations->count() === 0) {
            return false;
        }

        // Get the first invitation design (assuming the first one determines usability)
        $firstInvitation = $invitations[0];

        if (!$firstInvitation) {
            return false; // Should not happen if count > 0, but safe check
        }

        // Check the conditions for allowing invitations:
        // 1. Old Fluid override: alt_template is set
        if (!empty($firstInvitation->getAltTemplate())) {
            return true;
        }

        // 2. New HTML type: type is 'html' AND there are variants defined
        if ($firstInvitation->getType() === 'html' && $firstInvitation->getVariants()->count() > 0) {
            return true;
        }

        // 3. Original Fluid type: There are content elements defined
        if ($firstInvitation->getContents()->count() > 0) {
            return true;
        }

        // If none of the conditions match, invitations are not allowed
        return false;
    }

    /**
     * Returns the invitationReportStop
     *
     * @return int invitationReportStop
     */
    public function getInvitationReportStop()
    {
        return $this->invitationReportStop;
    }

    /**
     * Sets the invitationReportStop
     *
     * @param bool $invitationReportStop
     * @return void
     */
    public function setInvitationReportStop($invitationReportStop)
    {
        $this->invitationReportStop = $invitationReportStop;
    }

    /**
     * Returns the invitationNotesRequired
     *
     * @return bool $invitationNotesRequired
     */
    public function getInvitationNotesRequired()
    {
        return $this->invitationNotesRequired;
    }

    /**
     * Sets the invitationNotesRequired
     *
     * @param bool $invitationNotesRequired
     * @return void
     */
    public function setInvitationNotesRequired($invitationNotesRequired)
    {
        $this->invitationNotesRequired = $invitationNotesRequired;
    }

    /**
     * Returns the boolean state of invitationNotesRequired
     *
     * @return bool
     */
    public function isInvitationNotesRequired()
    {
        return $this->invitationNotesRequired;
    }

    /**
     * Adds a FileReference
     *
     * @return void
     */
    public function addCover(\TYPO3\CMS\Extbase\Domain\Model\FileReference $cover)
    {
        $this->covers->attach($cover);
    }

    /**
     * Adds a Category
     *
     * @return void
     */
    public function addNote(\BucheggerOnline\Publicrelations\Domain\Model\SysCategory $note)
    {
        $this->notes->attach($note);
    }

    /**
     * Removes a Category
     *
     * @param \BucheggerOnline\Publicrelations\Domain\Model\SysCategory $noteToRemove The Category to be removed
     * @return void
     */
    public function removeNote(\BucheggerOnline\Publicrelations\Domain\Model\SysCategory $noteToRemove)
    {
        $this->notes->detach($noteToRemove);
    }

    /**
     * Returns the notes
     *
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BucheggerOnline\Publicrelations\Domain\Model\SysCategory> $notes
     */
    public function getNotes()
    {
        return $this->notes;
    }

    /**
     * Sets the notes
     *
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BucheggerOnline\Publicrelations\Domain\Model\SysCategory> $notes
     * @return void
     */
    public function setNotes(ObjectStorage $notes)
    {
        $this->notes = $notes;
    }

    /**
     * Returns the notesOutput
     *
     * @return string $notesOutput
     */
    public function getNotesOutput()
    {
        if ($this->notes->count() && $this->getNotesOverwrite()) {

            foreach ($this->notes as $note)
                $notesOutput[] = $note->getTitle();

            $notesOutput = array_filter($notesOutput);
            $notesOverwrites = array_map('trim', explode("\n", $this->getNotesOverwrite()));
            $notesOverwrites = array_filter($notesOverwrites);

            $output = array_replace($notesOutput, $notesOverwrites);

        } elseif ($this->notes->count()) {

            foreach ($this->notes as $note)
                $output[] = $note->getTitle();

        } elseif ($this->getNotesOverwrite()) {

            $output = array_map('trim', explode("\n", $this->getNotesOverwrite()));

        } else {

            $output = NULL;

        }

        return $output;
    }

    /**
     * Returns the private
     *
     * @return bool $private
     */
    public function getPrivate()
    {
        return $this->private;
    }

    /**
     * Sets the private
     *
     * @param bool $private
     * @return void
     */
    public function setPrivate($private)
    {
        $this->private = $private;
    }

    /**
     * Returns the boolean state of private
     *
     * @return bool
     */
    public function isPrivate()
    {
        return $this->private;
    }

    /**
     * Returns the online
     *
     * @return bool $online
     */
    public function getOnline()
    {
        return $this->online;
    }

    /**
     * Sets the online
     *
     * @param bool $online
     * @return void
     */
    public function setOnline($online)
    {
        $this->online = $online;
    }

    /**
     * Returns the boolean state of online
     *
     * @return bool
     */
    public function isOnline()
    {
        return $this->online;
    }

    /**
     * Returns the canceled
     *
     * @return bool $canceled
     */
    public function getCanceled()
    {
        return $this->canceled;
    }

    /**
     * Sets the canceled
     *
     * @param bool $canceled
     * @return void
     */
    public function setCanceled($canceled)
    {
        $this->canceled = $canceled;
    }

    /**
     * Returns the boolean state of canceled
     *
     * @return bool
     */
    public function isCanceled()
    {
        return $this->canceled;
    }

    /**
     * Returns the boolean state of opening
     *
     * @return bool
     */
    public function isOpening()
    {
        $output = false;

        if ($this->getNotes()->count()) {
            foreach ($this->getNotes() as $note) {
                if ($note->getUid() == 45) {
                    $output = true;
                    break;
                }
            }
        }

        return $output;
    }

    /**
     * Returns the boolean state of openingOutput
     *
     * @return bool
     */
    public function getOpeningOutput()
    {
        if ($this->getNotes()->count()) {
            foreach ($this->getNotes()->toArray() as $key => $note) {
                if ($note->getUid() == 45) {
                    $neededKey = $key;
                    break;
                }
            }
        }

        return $this->getNotesOutput()[$neededKey];
    }

    /**
     * Returns the locationNote
     *
     * @return string $locationNote
     */
    public function getLocationNote()
    {
        return $this->locationNote;
    }

    /**
     * Sets the locationNote
     *
     * @param string $locationNote
     * @return void
     */
    public function setLocationNote($locationNote)
    {
        $this->locationNote = $locationNote;
    }

    /**
     * Returns the location
     *
     * @return \BucheggerOnline\Publicrelations\Domain\Model\Location location
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * Sets the location
     *
     * @param string $location
     * @return void
     */
    public function setLocation($location)
    {
        $this->location = $location;
    }

    /**
     * Adds a
     *
     * @param \BucheggerOnline\Publicrelations\Domain\Model\Link $link
     * @return void
     */
    public function addLink($link)
    {
        $this->links->attach($link);
    }

    /**
     * Removes a
     *
     * @param \BucheggerOnline\Publicrelations\Domain\Model\Link $linkToRemove The Link to be removed
     * @return void
     */
    public function removeLink($linkToRemove)
    {
        $this->links->detach($linkToRemove);
    }

    /**
     * Returns the links
     *
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BucheggerOnline\Publicrelations\Domain\Model\Link> links
     */
    public function getLinks()
    {
        return $this->links;
    }

    /**
     * Sets the links
     *
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BucheggerOnline\Publicrelations\Domain\Model\Link> $links
     * @return void
     */
    public function setLinks(ObjectStorage $links)
    {
        $this->links = $links;
    }

    /**
     * Adds an Additionalfield
     *
     * @param \BucheggerOnline\Publicrelations\Domain\Model\Additionalfield $additionalField
     * @return void
     */
    public function addAdditionalfield($additionalField)
    {
        $this->additionalFields->attach($additionalField);
    }

    /**
     * Removes an Additionalfield
     *
     * @param \BucheggerOnline\Publicrelations\Domain\Model\Additionalfield $additionalFieldToRemove The Link to be removed
     * @return void
     */
    public function removeAdditionalfield($additionalFieldToRemove)
    {
        $this->additionalFields->detach($additionalFieldToRemove);
    }

    /**
     * Returns the additionalFields
     *
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BucheggerOnline\Publicrelations\Domain\Model\Additionalfield> $additionalFields
     */
    public function getAdditionalfields()
    {
        return $this->additionalFields;
    }

    /**
     * Sets the additionalFields
     *
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BucheggerOnline\Publicrelations\Domain\Model\Additionalfield> $additionalFields
     * @return void
     */
    public function setAdditionalfields(ObjectStorage $additionalFields)
    {
        $this->additionalFields = $additionalFields;
    }

    /**
     * Returns the additionalfieldsInvitation
     *
     * @return ObjectStorage<Additionalfield>
     */
    public function getAdditionalfieldsInvitation(): ObjectStorage
    {
        $storage = new ObjectStorage();
        foreach ($this->getAdditionalfields() as $additionalField) {
            if ($additionalField->isInvitation()) {
                $storage->attach($additionalField);
            }
        }
        return $storage;
    }

    /**
     * Returns the additionalfieldsAccreditation
     *
     * @return ObjectStorage<Additionalfield>
     */
    public function getAdditionalfieldsAccreditation(): ObjectStorage
    {
        $storage = new ObjectStorage();
        foreach ($this->getAdditionalfields() as $additionalField) {
            if ($additionalField->isAccreditation()) {
                $storage->attach($additionalField);
            }
        }
        return $storage;
    }

    /**
     * Returns the additionalColumns (position == 1)
     *
     * @return ObjectStorage<Additionalfield>
     */
    public function getAdditionalColumns(): ObjectStorage
    {
        $storage = new ObjectStorage();
        foreach ($this->getAdditionalfields() as $field) {
            if ($field->getPosition() === 1) {
                $storage->attach($field);
            }
        }
        return $storage;
    }

    /**
     * Returns the additionalFieldsWithSum (summary flag)
     *
     * @return ObjectStorage<Additionalfield>
     */
    public function getAdditionalFieldsWithSum(): ObjectStorage
    {
        $storage = new ObjectStorage();
        foreach ($this->getAdditionalfields() as $field) {
            if ($field->getSummary()) {
                $storage->attach($field);
            }
        }
        return $storage;
    }

    /**
     * Returns the additionalNotes (position == 0)
     *
     * @return ObjectStorage<Additionalfield>
     */
    public function getAdditionalNotes(): ObjectStorage
    {
        $storage = new ObjectStorage();
        foreach ($this->getAdditionalfields() as $field) {
            if ($field->getPosition() === 0) {
                $storage->attach($field);
            }
        }
        return $storage;
    }

    /**
     * Returns the overwriteTheaterevent
     *
     * @return int overwriteTheaterevent
     */
    public function getOverwriteTheaterevent()
    {
        return $this->overwriteTheaterevent;
    }

    /**
     * Sets the overwriteTheaterevent
     *
     * @param int $overwriteTheaterevent
     * @return void
     */
    public function setOverwriteTheaterevent($overwriteTheaterevent)
    {
        $this->overwriteTheaterevent = $overwriteTheaterevent;
    }

    /**
     * Returns the theaterevent
     *
     * @return int theaterevent
     */
    public function getTheaterevent()
    {
        if ($this->getOverwriteTheaterevent() == 1 || (!$this->getOverwriteTheaterevent() && $this->getType()->isTheaterevent()))
            return true;
        else
            return false;
    }

    /**
     * Returns the duration
     *
     * @return int duration
     */
    public function getDuration()
    {
        return $this->duration;
    }

    /**
     * Sets the duration
     *
     * @param int $duration
     * @return void
     */
    public function setDuration($duration)
    {
        $this->duration = $duration;
    }

    public function getEndtime()
    {
        if ($this->getDuration())
            return $this->getDate()->add(new \DateInterval('PT' . $this->getDuration() . 'M'));
        else
            return $this->getDate()->add(new \DateInterval('PT120M'));
    }

    /**
     * Returns the durationApprox
     *
     * @return bool $durationApprox
     */
    public function getDurationApprox()
    {
        return $this->durationApprox;
    }

    /**
     * Sets the durationApprox
     *
     * @param bool $durationApprox
     * @return void
     */
    public function setDurationApprox($durationApprox)
    {
        $this->durationApprox = $durationApprox;
    }

    /**
     * Returns the boolean state of durationApprox
     *
     * @return bool
     */
    public function isDurationApprox()
    {
        return $this->durationApprox;
    }

    /**
     * Returns the durationWithBreak
     *
     * @return bool $durationWithBreak
     */
    public function getDurationWithBreak()
    {
        return $this->durationWithBreak;
    }

    /**
     * Sets the durationWithBreak
     *
     * @param bool $durationWithBreak
     * @return void
     */
    public function setDurationWithBreak($durationWithBreak)
    {
        $this->durationWithBreak = $durationWithBreak;
    }

    /**
     * Returns the boolean state of durationWithBreak
     *
     * @return bool
     */
    public function isDurationWithBreak()
    {
        return $this->durationWithBreak;
    }

    /**
     * Returns the checkin
     *
     * @return bool $checkin
     */
    public function getCheckin()
    {
        return $this->checkin;
    }

    /**
     * Sets the checkin
     *
     * @param bool $checkin
     * @return void
     */
    public function setCheckin($checkin)
    {
        $this->checkin = $checkin;
    }

    /**
     * Returns the boolean state of checkin
     *
     * @return bool
     */
    public function isCheckin()
    {
        return $this->checkin;
    }

    /**
     * Returns the checkinStarted
     *
     * @return bool $checkinStarted
     */
    public function getCheckinStarted()
    {
        if (new \DateTime() >= $this->getDate())
            $output = true;
        else
            $output = false;
        return $output;
    }

    /**
     * Returns the boolean state of checkinStarted
     *
     * @return bool
     */
    public function isCheckinStarted()
    {
        return $this->getCheckinStarted();
    }

    /**
     * Returns the accreditation
     *
     * @return int accreditation
     */
    public function getAccreditation()
    {
        return $this->accreditation;
    }

    /**
     * Sets the accreditation
     *
     * @param bool $accreditation
     * @return void
     */
    public function setAccreditation($accreditation)
    {
        $this->accreditation = $accreditation;
    }

    /**
     * Returns the accreditationAllowed
     *
     * @return int accreditationAllowed
     */
    public function getAccreditationAllowed()
    {
        $configuration = GeneralUtility::makeInstance(EmConfiguration::class);

        if ($this->getAccreditation() && $this->getDate()->sub(new \DateInterval('PT' . $configuration->getAccreditationStop() . 'S')) >= new \DateTime() && ($this->getTicketsQuota() === 0 || ($this->getTicketsApproved() < $this->getTicketsQuota()))) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Returns the client
     *
     * @return \BucheggerOnline\Publicrelations\Domain\Model\Client $client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * Sets the client
     *
     * @return void
     */
    public function setClient(\BucheggerOnline\Publicrelations\Domain\Model\Client $client)
    {
        $this->client = $client;
    }

    /**
     * Adds a Partner
     *
     * @return void
     */
    public function addPartner(\BucheggerOnline\Publicrelations\Domain\Model\Client $partner)
    {
        $this->partners->attach($partner);
    }

    /**
     * Removes a Partner
     *
     * @param \BucheggerOnline\Publicrelations\Domain\Model\Client $partnerToRemove The Partner to be removed
     * @return void
     */
    public function removePartner(\BucheggerOnline\Publicrelations\Domain\Model\Client $partnerToRemove)
    {
        $this->partners->detach($partnerToRemove);
    }

    /**
     * Returns the partners
     *
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BucheggerOnline\Publicrelations\Domain\Model\Client> $partners
     */
    public function getPartners()
    {
        return $this->partners;
    }

    /**
     * Sets the partners
     *
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BucheggerOnline\Publicrelations\Domain\Model\Accreditation> $partners
     * @return void
     */
    public function setPartners(ObjectStorage $partners)
    {
        $this->partners = $partners;
    }

    /**
     * Returns the campaign
     *
     * @return \BucheggerOnline\Publicrelations\Domain\Model\Campaign $campaign
     */
    public function getCampaign()
    {
        return $this->campaign;
    }

    /**
     * Sets the campaign
     *
     * @return void
     */
    public function setCampaign(\BucheggerOnline\Publicrelations\Domain\Model\Campaign $campaign)
    {
        $this->campaign = $campaign;
    }

    /**
     * Adds a Accreditation
     *
     * @return void
     */
    public function addAccreditation(\BucheggerOnline\Publicrelations\Domain\Model\Accreditation $accreditation)
    {
        $this->accreditations->attach($accreditation);
    }

    /**
     * Removes a Accreditation
     *
     * @param \BucheggerOnline\Publicrelations\Domain\Model\Accreditation $accreditationToRemove The Accreditation to be removed
     * @return void
     */
    public function removeAccreditation(\BucheggerOnline\Publicrelations\Domain\Model\Accreditation $accreditationToRemove)
    {
        $this->accreditations->detach($accreditationToRemove);
    }

    /**
     * Returns the accreditations
     *
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BucheggerOnline\Publicrelations\Domain\Model\Accreditation> $accreditations
     */
    public function getAccreditations()
    {
        return $this->accreditations;
    }

    /**
     * Sets the accreditations
     *
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BucheggerOnline\Publicrelations\Domain\Model\Accreditation> $accreditations
     * @return void
     */
    public function setAccreditations(ObjectStorage $accreditations)
    {
        $this->accreditations = $accreditations;
    }


    /**
     * Returns accreditations by status (and optionally type).
     *
     * @param int|int[] $status     One or multiple status values
     * @param int|int[]|null $type  One or multiple type values (optional)
     * @return array|null
     */
    protected function getAccreditationsByStatus($status, $type = null): ?array
    {
        if (!$this->accreditations || $this->accreditations->count() === 0) {
            return null;
        }

        $statuses = (array) $status;
        $types = $type !== null ? (array) $type : null;

        $filtered = [];

        foreach ($this->accreditations as $accreditation) {
            $statusMatch = in_array($accreditation->getStatus(), $statuses, true);
            $typeMatch = $types === null || in_array($accreditation->getType(), $types, true);

            if ($statusMatch && $typeMatch) {
                $filtered[] = $accreditation;
            }
        }

        return $filtered ?: null;
    }

    public function getPendingAccreditations(): ?array
    {
        return $this->getAccreditationsByStatus(0, 2); // status = 0 AND type = 2
    }

    public function getWaitingAccreditations(): ?array
    {
        return $this->getAccreditationsByStatus([0, -2], [1, 2]);
        // (status = 0 AND type = 1) OR (status = -2 AND type = 2)
    }

    public function getApprovedAccreditations(): ?array
    {
        return $this->getAccreditationsByStatus([1, 2]); // any approved
    }

    public function getRejectedAccreditations(): ?array
    {
        return $this->getAccreditationsByStatus(-1);
    }


    public function getFacies()
    {
        $facies = []; // <-- Initialisierung

        if ($this->getApprovedAccreditations()) {
            foreach ($this->getApprovedAccreditations() as $accreditation) {
                if ($accreditation->isFacie()) {
                    $facies[] = $accreditation;
                }
            }
        }

        return $facies;
    }


    public function getTicketsApproved(): int
    {
        //   $tickets = 0;

        //   if ($this->getApprovedAccreditations()) {
        //     foreach ($this->getApprovedAccreditations() as $accreditation) {
        //       $tickets += $accreditation->getTicketsApproved();
        //     }
        //   }

        //  return $tickets;

        $cacheIdentifier = 'ticketsApproved';
        if (!array_key_exists($cacheIdentifier, $this->cache)) {
            /** @var QueryBuilder $queryBuilder */
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_publicrelations_domain_model_accreditation');
            $this->cache[$cacheIdentifier] = (int) $queryBuilder
                ->addSelectLiteral(
                    $queryBuilder->expr()->sum('tickets_approved', 'ticketsApproved')
                )
                ->from('tx_publicrelations_domain_model_accreditation')
                ->orWhere(
                    $queryBuilder->expr()->eq('status', $queryBuilder->createNamedParameter(1, Connection::PARAM_INT)),
                    $queryBuilder->expr()->eq('status', $queryBuilder->createNamedParameter(2, Connection::PARAM_INT))
                )
                ->andWhere(
                    $queryBuilder->expr()->eq('event', $queryBuilder->createNamedParameter($this->getUid(), Connection::PARAM_INT))
                )
                ->executeQuery()
                ->fetchOne();
        }

        return $this->cache[$cacheIdentifier];
    }

    public function getTicketsPending(): int
    {
        //   $tickets = 0;

        //   if ($this->getPendingAccreditations()) {
        //     foreach ($this->getPendingAccreditations() as $accreditation) {
        //       $tickets += $accreditation->getTicketsWish();
        //     }
        //   }

        $cacheIdentifier = 'ticketsPending';
        if (!array_key_exists($cacheIdentifier, $this->cache)) {
            /** @var QueryBuilder $queryBuilder */
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_publicrelations_domain_model_accreditation');
            $this->cache[$cacheIdentifier] = (int) $queryBuilder
                ->addSelectLiteral(
                    $queryBuilder->expr()->sum('tickets_wish', 'ticketsWish')
                )
                ->from('tx_publicrelations_domain_model_accreditation')
                ->where(
                    $queryBuilder->expr()->eq('event', $queryBuilder->createNamedParameter($this->getUid(), Connection::PARAM_INT)),
                    $queryBuilder->expr()->eq('status', $queryBuilder->createNamedParameter(0))
                )
                ->executeQuery()
                ->fetchOne();
        }

        return $this->cache[$cacheIdentifier];
    }

    public function getTicketsWaiting(): int
    {
        //   $tickets = 0;

        //   if ($this->getWaitingAccreditations()) {
        //     foreach ($this->getWaitingAccreditations() as $accreditation) {
        //       $tickets += $accreditation->getTicketsWish();
        //     }
        //   }

        $cacheIdentifier = 'ticketsWaiting';
        if (!array_key_exists($cacheIdentifier, $this->cache)) {
            /** @var QueryBuilder $queryBuilder */
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_publicrelations_domain_model_accreditation');
            $this->cache[$cacheIdentifier] = (int) $queryBuilder
                ->addSelectLiteral(
                    $queryBuilder->expr()->sum('tickets_wish', 'ticketsWish')
                )
                ->from('tx_publicrelations_domain_model_accreditation')
                ->where(
                    $queryBuilder->expr()->eq('event', $queryBuilder->createNamedParameter($this->getUid(), Connection::PARAM_INT)),
                    $queryBuilder->expr()->eq('status', $queryBuilder->createNamedParameter(-2))
                )
                ->executeQuery()
                ->fetchOne();
        }

        return $this->cache[$cacheIdentifier];
    }

    public function getTicketsReceived(): int
    {
        //   $tickets = 0;

        //   if ($this->getCheckedinAccreditations()) {
        //     foreach ($this->getCheckedinAccreditations() as $accreditation) {
        //       $tickets += $accreditation->getTicketsReceived();
        //     }
        //   }

        $cacheIdentifier = 'ticketsReceived';
        if (!array_key_exists($cacheIdentifier, $this->cache)) {
            /** @var QueryBuilder $queryBuilder */
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_publicrelations_domain_model_accreditation');
            $this->cache[$cacheIdentifier] = (int) $queryBuilder
                ->addSelectLiteral(
                    $queryBuilder->expr()->sum('tickets_received', 'ticketsReceived')
                )
                ->from('tx_publicrelations_domain_model_accreditation')
                ->where(
                    $queryBuilder->expr()->eq('event', $queryBuilder->createNamedParameter($this->getUid(), Connection::PARAM_INT)),
                    $queryBuilder->expr()->eq('status', $queryBuilder->createNamedParameter(2))
                )
                ->executeQuery()
                ->fetchOne();
        }

        return $this->cache[$cacheIdentifier];
    }

    public function getTicketsPrepared()
    {
        return $this->getTicketsApproved() - $this->getTicketsReceived();
    }

    public function getPrograms(): int
    {
        //   $programs = 0;

        //   if ($this->getApprovedAccreditations()) {
        //     foreach ($this->getApprovedAccreditations() as $accreditation) {
        //       $programs += $accreditation->getProgram();
        //     }
        //   }

        //   return $programs;

        $cacheIdentifier = 'programs';
        if (!array_key_exists($cacheIdentifier, $this->cache)) {
            /** @var QueryBuilder $queryBuilder */
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_publicrelations_domain_model_accreditation');
            $this->cache[$cacheIdentifier] = (int) $queryBuilder
                ->addSelectLiteral(
                    $queryBuilder->expr()->sum('program', 'programs')
                )
                ->from('tx_publicrelations_domain_model_accreditation')
                ->orWhere(
                    $queryBuilder->expr()->eq('status', $queryBuilder->createNamedParameter(1, Connection::PARAM_INT)),
                    $queryBuilder->expr()->eq('status', $queryBuilder->createNamedParameter(2, Connection::PARAM_INT))
                )
                ->andWhere(
                    $queryBuilder->expr()->eq('event', $queryBuilder->createNamedParameter($this->getUid(), Connection::PARAM_INT))
                )
                ->executeQuery()
                ->fetchOne();
        }

        return $this->cache[$cacheIdentifier];
    }

    public function getPasses(): int
    {
        //   $passes = 0;

        //   if ($this->getApprovedAccreditations()) {
        //     foreach ($this->getApprovedAccreditations() as $accreditation) {
        //       $passes += $accreditation->getPass();
        //     }
        //   }

        //   return $passes;

        $cacheIdentifier = 'passes';
        if (!array_key_exists($cacheIdentifier, $this->cache)) {
            /** @var QueryBuilder $queryBuilder */
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_publicrelations_domain_model_accreditation');
            $this->cache[$cacheIdentifier] = (int) $queryBuilder
                ->addSelectLiteral(
                    $queryBuilder->expr()->sum('pass', 'passes')
                )
                ->from('tx_publicrelations_domain_model_accreditation')
                ->orWhere(
                    $queryBuilder->expr()->eq('status', $queryBuilder->createNamedParameter(1, Connection::PARAM_INT)),
                    $queryBuilder->expr()->eq('status', $queryBuilder->createNamedParameter(2, Connection::PARAM_INT))
                )
                ->andWhere(
                    $queryBuilder->expr()->eq('event', $queryBuilder->createNamedParameter($this->getUid(), Connection::PARAM_INT))
                )
                ->executeQuery()
                ->fetchOne();
        }

        return $this->cache[$cacheIdentifier];
    }

    /**
     * Returns the oldEvent
     *
     * @return \BucheggerOnline\Publicrelations\Domain\Model\Event $oldEvent
     */
    public function getOldEvent()
    {
        return $this->oldEvent;
    }

    /**
     * Sets the oldEvent
     *
     * @return void
     */
    public function setOldEvent(\BucheggerOnline\Publicrelations\Domain\Model\Event $oldEvent)
    {
        $this->oldEvent = $oldEvent;
    }

    /**
     * Returns the newEvent
     *
     * @return \BucheggerOnline\Publicrelations\Domain\Model\Event $newEvent
     */
    public function getNewEvent()
    {
        return $this->newEvent;
    }

    /**
     * Sets the newEvent
     *
     * @return void
     */
    public function setNewEvent(\BucheggerOnline\Publicrelations\Domain\Model\Event $newEvent)
    {
        $this->newEvent = $newEvent;
    }

    /**
     * Returns the manualConfirmation
     *
     * @return bool $manualConfirmation
     */
    public function getManualConfirmation()
    {
        return $this->manualConfirmation;
    }

    /**
     * Sets the manualConfirmation
     *
     * @param bool $manualConfirmation
     * @return void
     */
    public function setManualConfirmation($manualConfirmation)
    {
        $this->dateFulltime = $manualConfirmation;
    }

    /**
     * Returns the boolean state of manualConfirmation
     *
     * @return bool
     */
    public function isManualConfirmation()
    {
        return $this->manualConfirmation;
    }

    /**
     * Returns the ticketsQuota
     *
     * @return int $ticketsQuota
     */
    public function getTicketsQuota()
    {
        return $this->ticketsQuota;
    }

    /**
     * Sets the ticketsQuota
     *
     * @param int $ticketsQuota
     * @return void
     */
    public function setTicketsQuota($ticketsQuota)
    {
        $this->ticketsQuota = $ticketsQuota;
    }

    /**
     * Returns the waitingQuota
     *
     * @return int $waitingQuota
     */
    public function getWaitingQuota()
    {
        return $this->waitingQuota;
    }

    /**
     * Sets the waitingQuota
     *
     * @param int $waitingQuota
     * @return void
     */
    public function setWaitingQuota($waitingQuota)
    {
        $this->waitingQuota = $waitingQuota;
    }

    /**
     * Returns the ticketsAvailable
     *
     * @return int $ticketsAvailable
     */
    public function getTicketsAvailable()
    {
        return $this->getTicketsQuota() - $this->getTicketsApproved();
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
    public function setLogs(ObjectStorage $logs)
    {
        $this->logs = $logs;
    }
}
