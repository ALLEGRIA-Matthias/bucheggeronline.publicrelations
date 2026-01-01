<?php
namespace BucheggerOnline\Publicrelations\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Annotation\ORM;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/***
 *
 * This file is part of the "Public Relations" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2020 Matthias Buchegger <matthias@buchegger.online>, Multiattachementsagentur Matthias Buchegger
 *
 ***/
/**
 * News
 */
class Mailing extends AbstractEntity
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
     * client
     *
     * @var \BucheggerOnline\Publicrelations\Domain\Model\Client
     */
    protected $client;

    /**
     * type
     *
     * @var string
     *
     */
    protected $type = '';

    /**
     * title
     *
     * @var string
     *
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
     * preview
     *
     * @var string
     *
     */
    protected $preview = '';

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
     * noLogo
     *
     * @var bool
     */
    protected $noLogo = false;

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
     * personally
     *
     * @var bool
     */
    protected $personally = false;

    #[ORM\Lazy]
    protected ObjectStorage $contents;

    /**
     * attachment
     * 
     * @var \TYPO3\CMS\Extbase\Domain\Model\FileReference
     * @TYPO3\CMS\Extbase\Annotation\ORM\Cascade("remove")
     * @ORM\Lazy
     */
    protected $attachment;

    /**
     * status
     *
     * @var int
     *
     */
    protected $status = 0;

    /**
     * test
     *
     * @var bool
     */
    protected $test = false;

    /**
     * planed
     *
     * @var \DateTime
     */
    protected $planed;

    /**
     * started
     *
     * @var \DateTime
     *
     */
    protected $started;

    /**
     * sent
     *
     * @var \DateTime
     *
     */
    protected $sent;

    #[ORM\Lazy]
    protected ObjectStorage $logs;

    #[ORM\Lazy]
    protected ObjectStorage $mails;

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
        $this->contents = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
        $this->attachements = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
        $this->logs = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
        $this->mails = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
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
     * Gibt die PID des Mailings zurück.
     *
     * @return int|null
     */
    public function getPid(): ?int
    {
        return $this->pid;
    }

    /**
     * Setzt die PID des Mailings.
     *
     * @param int|null $pid 
     */
    public function setPid(?int $pid): void
    {
        $this->pid = $pid;
    }

    /**
     * Returns the type
     *
     * @return \BucheggerOnline\Publicrelations\Domain\Model\Client client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * Sets the type
     *
     * @return void
     */
    public function setClient(\BucheggerOnline\Publicrelations\Domain\Model\Client $client)
    {
        $this->client = $client;
    }

    /**
     * Returns the typeOutput
     */
    public function getTypeOutput()
    {
        return $this->getType();
    }

    /**
     * Returns the type
     *
     * @return string type
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Sets the type
     *
     * @param string $type
     * @return void
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * Returns the title
     *
     * @return string title
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
     * Returns the subjectOutput
     *
     * @return string $subjectOutput
     */
    public function getSubjectOutput()
    {
        if ($this->getSubject()) {
            return $this->getSubject();
        } else {
            return 'UNBEKANNT!';
        }
    }

    /**
     * Returns the preview
     *
     * @return string $preview
     */
    public function getPreview()
    {
        return $this->preview;
    }

    /**
     * Sets the preview
     *
     * @param string $preview
     * @return void
     */
    public function setPreview($preview)
    {
        $this->preview = $preview;
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
     * Returns the noLogo
     *
     * @return bool $noLogo
     */
    public function getNoLogo()
    {
        return $this->noLogo;
    }

    /**
     * Sets the noLogo
     *
     * @param bool $noLogo
     * @return void
     */
    public function setNoLogo($noLogo)
    {
        $this->noLogo = $noLogo;
    }

    /**
     * Returns the boolean state of noLogo
     *
     * @return bool
     */
    public function isNoLogo()
    {
        return $this->noLogo;
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
        return $this->header ?? $this->getClient()->getLogo();
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
     * Returns the personally
     *
     * @return bool $personally
     */
    public function getPersonally()
    {
        return $this->personally;
    }

    /**
     * Sets the personally
     *
     * @param bool $personally
     * @return void
     */
    public function setPersonally($personally)
    {
        $this->personally = $personally;
    }

    /**
     * Returns the boolean state of personally
     *
     * @return bool
     */
    public function isPersonally()
    {
        return $this->personally;
    }

    /**
     * Adds a
     *
     * @param \BucheggerOnline\Publicrelations\Domain\Model\Content $content
     * @return void
     */
    public function addContent($content)
    {
        $this->contents->attach($content);
    }

    /**
     * Removes a
     *
     * @param \BucheggerOnline\Publicrelations\Domain\Model\Content $contentToRemove The  to be removed
     * @return void
     */
    public function removeContent($contentToRemove)
    {
        $this->contents->detach($contentToRemove);
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
     * Returns the attachment
     *
     * @return \TYPO3\CMS\Extbase\Domain\Model\FileReference $attachment
     */
    public function getAttachment()
    {
        return $this->attachment;
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
                return 'in Vorbereitung';
            case 1:
                return 'Empfänger zugewiesen';
            case 2:
                return 'Versand geplant';
            case 3:
                return 'Teilversandt';
            case -1:
                return 'Abgeschlossen';
            default:
                return 'unbekannt';
        }
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
     * Returns the test
     *
     * @return bool $test
     */
    public function getTest()
    {
        return $this->test;
    }

    /**
     * Sets the test
     *
     * @param bool $test
     * @return void
     */
    public function setTest($test)
    {
        $this->test = $test;
    }

    /**
     * Returns the boolean state of test
     *
     * @return bool
     */
    public function isTest()
    {
        return $this->test;
    }

    /**
     * Returns the planed
     *
     * @return \DateTimeInterface $planed
     */
    public function getPlaned()
    {
        return $this->planed;
    }

    /**
     * Sets the planed
     *
     * @return void
     */
    public function setPlaned(\DateTimeInterface $planed)
    {
        $this->planed = $planed;
    }

    /**
     * Returns the started
     *
     * @return \DateTimeInterface $started
     */
    public function getStarted()
    {
        return $this->started;
    }

    /**
     * Sets the started
     *
     * @return void
     */
    public function setStarted(\DateTimeInterface $started)
    {
        $this->started = $started;
    }

    /**
     * Returns the sent
     *
     * @return \DateTimeInterface $sent
     */
    public function getSent()
    {
        return $this->sent;
    }

    /**
     * Sets the sent
     *
     * @return void
     */
    public function setSent(\DateTimeInterface $sent)
    {
        $this->sent = $sent;
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

    /**
     * Adds a Log
     *
     * @return void
     */
    public function addMail(\BucheggerOnline\Publicrelations\Domain\Model\Mail $mail)
    {
        $this->mails->attach($mail);
    }

    /**
     * Removes a Log
     *
     * @param \BucheggerOnline\Publicrelations\Domain\Model\Mail $mailToRemove The Log to be removed
     * @return void
     */
    public function removeMail(\BucheggerOnline\Publicrelations\Domain\Model\Mail $mailToRemove)
    {
        $this->mails->detach($mailToRemove);
    }

    /**
     * Returns the mails
     *
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BucheggerOnline\Publicrelations\Domain\Model\Mail> $mails
     */
    public function getMails()
    {
        return $this->mails;
    }

    /**
     * Sets the mails
     *
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BucheggerOnline\Publicrelations\Domain\Model\Mail> $mails
     * @return void
     */
    public function setMails(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $mails)
    {
        $this->mails = $mails;
    }
}
