<?php
namespace BucheggerOnline\Publicrelations\Domain\Model;

use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

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
class Invitation extends AbstractEntity
{

    /**
     * title
     *
     * @var string
     */
    protected $title = '';

    /**
     * subject
     *
     * @var string
     *
     */
    protected $subject = '';

    /**
     * from
     *
     * @var string
     *
     */
    protected $from = '';

    /**
     * fromPersonally
     *
     * @var string
     *
     */
    protected $fromPersonally = '';

    /**
     * blank
     *
     * @var bool
     */
    protected $blank = false;

    /**
     * noSalutation
     *
     * @var bool
     */
    protected $noSalutation = false;

    /**
     * noSignature
     *
     * @var bool
     */
    protected $noSignature = false;

    /**
     * noHeader
     *
     * @var bool
     */
    protected $noHeader = false;

    /**
     * header
     *
     * @var \TYPO3\CMS\Extbase\Domain\Model\FileReference
     * @TYPO3\CMS\Extbase\Annotation\ORM\Cascade("remove")
     */
    protected $header;

    /**
     * @var \DateTimeImmutable|null
     */
    protected ?\DateTimeImmutable $feedbackDate = null;

    /**
     * noEventOverview
     *
     * @var bool
     */
    protected $noEventOverview = false;

    /**
     * contents
     *
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BucheggerOnline\Publicrelations\Domain\Model\Content>
     *
     */
    protected $contents;

    /**
     * contentsPersonally
     *
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BucheggerOnline\Publicrelations\Domain\Model\Content>
     *
     */
    protected $contentsPersonally;

    /**
     * image
     *
     * @var \TYPO3\CMS\Extbase\Domain\Model\FileReference
     * @TYPO3\CMS\Extbase\Annotation\ORM\Cascade("remove")
     */
    protected $image;

    /**
     * logo
     *
     * @var \TYPO3\CMS\Extbase\Domain\Model\FileReference
     * @TYPO3\CMS\Extbase\Annotation\ORM\Cascade("remove")
     */
    protected $logo;

    /**
     * attachment
     *
     * @var \TYPO3\CMS\Extbase\Domain\Model\FileReference
     * @TYPO3\CMS\Extbase\Annotation\ORM\Cascade("remove")
     */
    protected $attachment;

    /**
     * event
     *
     * @var \BucheggerOnline\Publicrelations\Domain\Model\Event
     */
    protected $event;

    /**
     * altSender
     *
     * @var string
     *
     */
    protected $altSender = '';

    /**
     * altTemplate
     *
     * @var string
     *
     */
    protected $altTemplate = '';

    /**
     * fromName
     *
     * @var string
     *
     */
    protected $fromName = '';

    /**
     * replyName
     *
     * @var string
     *
     */
    protected $replyName = '';

    /**
     * replyEmail
     *
     * @var string
     *
     */
    protected $replyEmail = '';

    /**
     * invitationTitleOverwrite
     *
     * @var string
     *
     */
    protected $invitationTitleOverwrite = '';

    /**
     * invitationSubtitleOverwrite
     *
     * @var string
     *
     */
    protected $invitationSubtitleOverwrite = '';

    /**
     * invitationHeaderOverwrite
     *
     * @var string
     *
     */
    protected $invitationHeaderOverwrite = '';

    protected string $type = '';

    /**
     * @var ObjectStorage<InvitationVariant>
     */
    protected ObjectStorage $variants;

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
        $this->contents = new ObjectStorage();
        $this->contentsPersonally = new ObjectStorage();
        $this->variants = new ObjectStorage();
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
     * Returns the society
     *
     * @return bool $society
     */
    public function getSociety()
    {
        return str_contains(strtolower($this->getTitle()), 'society');
    }

    /**
     * Returns the society
     *
     * @return bool $society
     */
    public function isSociety()
    {
        return $this->getSociety();
    }

    /**
     * Returns the subject
     *
     * @return string $subject
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * Sets the subject
     *
     * @param string $subject
     * @return void
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;
    }

    /**
     * Returns the from
     *
     * @return string $from
     */
    public function getFrom()
    {
        return $this->from;
    }

    /**
     * Sets the from
     *
     * @param string $from
     * @return void
     */
    public function setFrom($from)
    {
        $this->from = $from;
    }

    /**
     * Returns the fromPersonally
     *
     * @return string $fromPersonally
     */
    public function getFromPersonally()
    {
        ($this->fromPersonally) ? $output = $this->fromPersonally : $output = $this->from;
        return $output;
    }

    /**
     * Sets the fromPersonally
     *
     * @param string $fromPersonally
     * @return void
     */
    public function setFromPersonally($fromPersonally)
    {
        $this->fromPersonally = $fromPersonally;
    }

    /**
     * Returns the blank
     *
     * @return bool $blank
     */
    public function getBlank()
    {
        return $this->blank;
    }

    /**
     * Sets the blank
     *
     * @param bool $blank
     * @return void
     */
    public function setBlank($blank)
    {
        $this->blank = $blank;
    }

    /**
     * Returns the boolean state of blank
     *
     * @return bool
     */
    public function isBlank()
    {
        return $this->blank;
    }

    /**
     * Returns the noSalutation
     *
     * @return bool $noSalutation
     */
    public function getNoSalutation()
    {
        return $this->noSalutation;
    }

    /**
     * Sets the noSalutation
     *
     * @param bool $noSalutation
     * @return void
     */
    public function setNoSalutation($noSalutation)
    {
        $this->noLogo = $noSalutation;
    }

    /**
     * Returns the boolean state of noSalutation
     *
     * @return bool
     */
    public function isNoSalutation()
    {
        return $this->noSalutation;
    }

    /**
     * Returns the noSignature
     *
     * @return bool $noSignature
     */
    public function getNoSignature()
    {
        return $this->noSignature;
    }

    /**
     * Sets the noSignature
     *
     * @param bool $noSignature
     * @return void
     */
    public function setNoSignature($noSignature)
    {
        $this->noSignature = $noSignature;
    }

    /**
     * Returns the boolean state of noSignature
     *
     * @return bool
     */
    public function isNoSignature()
    {
        return $this->noSignature;
    }

    /**
     * Returns the noHeader
     *
     * @return bool $noHeader
     */
    public function getNoHeader()
    {
        return $this->noHeader;
    }

    /**
     * Sets the noHeader
     *
     * @param bool $noHeader
     * @return void
     */
    public function setNoHeader($noHeader)
    {
        $this->noHeader = $noHeader;
    }

    /**
     * Returns the boolean state of noHeader
     *
     * @return bool
     */
    public function isNoHeader()
    {
        return $this->noHeader;
    }

    /**
     * Returns the header
     *
     * @return \TYPO3\CMS\Extbase\Domain\Model\FileReference $header
     */
    public function getHeader()
    {
        return $this->header ?? $this->getEvent()->getClient()->getLogo();
    }

    /**
     * Sets the header
     *
     * @return void
     */
    public function setHeader(\TYPO3\CMS\Extbase\Domain\Model\FileReference $header)
    {
        $this->header = $header;
    }

    /**
     * Gibt das Feedback-Datum zurück.
     * Wenn keines gesetzt ist, wird der Freitag 18:00 Uhr
     * in der Woche zwei Wochen vor dem Event-Datum berechnet.
     *
     * @return \DateTimeInterface|null Das Feedback-Datum oder null
     */
    public function getFeedbackDate(): ?\DateTimeInterface
    {
        // Fall 1: Ein explizites Datum ist gesetzt
        if ($this->feedbackDate !== null) {
            return $this->feedbackDate;
        }

        // Fall 2: Kein Datum gesetzt, versuche vom Event zu berechnen
        $event = $this->getEvent();
        if ($event instanceof Event) {
            $eventDate = $event->getDate();

            if ($eventDate instanceof \DateTimeInterface) {
                // 1. Klone das Event-Datum, um das Original nicht zu ändern
                $calculatedDate = \DateTime::createFromInterface($eventDate);

                // 2. Zwei Wochen zurückrechnen
                $calculatedDate->modify('-2 weeks');

                // 3. Auf den Freitag DIESER Woche gehen (ISO-8601: 5 = Freitag)
                // Wenn der Tag > Freitag ist (Sa=6, So=7), gehe zum "vorherigen Freitag"
                // Wenn der Tag < Freitag ist (Mo=1 bis Do=4), gehe zum "nächsten Freitag" (was der in dieser Woche ist)
                // Wenn der Tag = Freitag ist, bleibe dabei.
                $dayOfWeek = (int) $calculatedDate->format('N'); // 1 (Mo) bis 7 (So)

                if ($dayOfWeek < 5) {
                    $calculatedDate->modify('next friday');
                } elseif ($dayOfWeek > 5) {
                    $calculatedDate->modify('previous friday');
                }
                // Wenn $dayOfWeek === 5, ist es bereits Freitag.

                // 4. Uhrzeit auf 18:00 Uhr setzen
                $calculatedDate->setTime(18, 0, 0);

                return $calculatedDate;
            }
        }

        // Fall 3: Kein explizites Datum und kein Event-Datum vorhanden
        return null;
    }

    /**
     * Sets the feedbackDate
     *
     * @return void
     */
    public function setFeedbackDate(\DateTimeImmutable $feedbackDate)
    {
        $this->feedbackDate = $feedbackDate;
    }

    /**
     * Returns the noEventOverview
     *
     * @return bool $noEventOverview
     */
    public function getNoEventOverview()
    {
        return $this->noEventOverview;
    }

    /**
     * Sets the noEventOverview
     *
     * @param bool $noEventOverview
     * @return void
     */
    public function setNoEventOverview($noEventOverview)
    {
        $this->noEventOverview = $noEventOverview;
    }

    /**
     * Returns the boolean state of noEventOverview
     *
     * @return bool
     */
    public function isNoEventOverview()
    {
        return $this->noEventOverview;
    }

    /**
     * Returns the contents
     *
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BucheggerOnline\Publicrelations\Domain\Model\Content> $contents
     */
    public function getContents()
    {
        return $this->contents;
    }

    /**
     * Sets the contents
     *
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BucheggerOnline\Publicrelations\Domain\Model\Content> $contents
     * @return void
     */
    public function setContents(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $contents)
    {
        $this->contents = $contents;
    }

    /**
     * Returns the contentsPersonally
     *
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BucheggerOnline\Publicrelations\Domain\Model\Content> $contentsPersonally
     */
    public function getContentsPersonally()
    {
        return ($this->contentsPersonally->count()) ? $this->contentsPersonally : $this->contents;
    }

    /**
     * Sets the contentsPersonally
     *
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BucheggerOnline\Publicrelations\Domain\Model\Content> $contentsPersonally
     * @return void
     */
    public function setContentsPersonally(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $contentsPersonally)
    {
        $this->contents = $contentsPersonally;
    }

    /**
     * Returns the image
     *
     * @return \TYPO3\CMS\Extbase\Domain\Model\FileReference $image
     */
    public function getImage()
    {
        if ($this->image) {
            $image = $this->image;
        } elseif ($this->getEvent()->getCampaign()) {
            $image = $this->getEvent()->getCampaign()->getLogo();
        } else {
            $image = $this->getEvent()->getClient()->getLogo();
        }
        return $image;
    }

    /**
     * Returns the logo
     *
     * @return \TYPO3\CMS\Extbase\Domain\Model\FileReference $logo
     */
    public function getLogo()
    {
        return $this->logo;
    }

    /**
     * Returns the attachment
     *
     * @return \TYPO3\CMS\Extbase\Domain\Model\FileReference $attachment
     */
    public function getAttachment()
    {
        return $this->attachment;
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
     * Returns the altSender
     *
     * @return string $altSender
     */
    public function getAltSender()
    {
        return $this->altSender;
    }

    /**
     * Sets the altSender
     *
     * @param string $altSender
     * @return void
     */
    public function setAltSender($altSender)
    {
        $this->altSender = $altSender;
    }

    /**
     * Returns the altTemplate
     *
     * @return string $altTemplate
     */
    public function getAltTemplate()
    {
        return $this->altTemplate;
    }

    /**
     * Sets the altTemplate
     *
     * @param string $altTemplate
     * @return void
     */
    public function setAltTemplate($altTemplate)
    {
        $this->altTemplate = $altTemplate;
    }

    /**
     * Returns the fromName
     *
     * @return string $fromName
     */
    public function getFromName()
    {
        return $this->fromName;
    }

    /**
     * Sets the fromName
     *
     * @param string $fromName
     * @return void
     */
    public function setFromName($fromName)
    {
        $this->fromName = $fromName;
    }

    /**
     * Returns the replyName
     *
     * @return string $replyName
     */
    public function getReplyName()
    {
        return $this->replyName;
    }

    /**
     * Sets the replyName
     *
     * @param string $replyName
     * @return void
     */
    public function setReplyName($replyName)
    {
        $this->replyName = $replyName;
    }

    /**
     * Returns the replyEmail
     *
     * @return string $replyEmail
     */
    public function getReplyEmail()
    {
        return $this->replyEmail;
    }

    /**
     * Sets the replyEmail
     *
     * @param string $replyEmail
     * @return void
     */
    public function setReplyEmail($replyEmail)
    {
        $this->replyEmail = $replyEmail;
    }

    /**
     * Returns the invitationTitleOverwrite
     *
     * @return string $invitationTitleOverwrite
     */
    public function getInvitationTitleOverwrite()
    {
        return $this->invitationTitleOverwrite;
    }

    /**
     * Sets the invitationTitleOverwrite
     *
     * @param string $invitationTitleOverwrite
     * @return void
     */
    public function setInvitationTitleOverwrite($invitationTitleOverwrite)
    {
        $this->invitationTitleOverwrite = $invitationTitleOverwrite;
    }

    /**
     * Returns the invitationSubtitleOverwrite
     *
     * @return string $invitationSubtitleOverwrite
     */
    public function getInvitationSubtitleOverwrite()
    {
        return $this->invitationSubtitleOverwrite;
    }

    /**
     * Sets the invitationSubtitleOverwrite
     *
     * @param string $invitationSubtitleOverwrite
     * @return void
     */
    public function setInvitationSubtitleOverwrite($invitationSubtitleOverwrite)
    {
        $this->invitationSubtitleOverwrite = $invitationSubtitleOverwrite;
    }

    /**
     * Returns the invitationHeaderOverwrite
     *
     * @return string $invitationHeaderOverwrite
     */
    public function getInvitationHeaderOverwrite()
    {
        return $this->invitationHeaderOverwrite;
    }

    /**
     * Sets the invitationHeaderOverwrite
     *
     * @param string $invitationHeaderOverwrite
     * @return void
     */
    public function setInvitationHeaderOverwrite($invitationHeaderOverwrite)
    {
        $this->invitationHeaderOverwrite = $invitationHeaderOverwrite;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    /**
     * @return ObjectStorage<InvitationVariant>
     */
    public function getVariants(): ObjectStorage
    {
        return $this->variants;
    }

    /**
     * @param ObjectStorage<InvitationVariant> $variants
     */
    public function setVariants(ObjectStorage $variants): void
    {
        $this->variants = $variants;
    }
}
