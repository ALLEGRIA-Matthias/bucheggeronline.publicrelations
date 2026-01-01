<?php
namespace BucheggerOnline\Publicrelations\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Annotation\ORM\Lazy;

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
 * Receiver
 */
class Mail extends AbstractEntity
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
     * mailing
     *
     * @var \BucheggerOnline\Publicrelations\Domain\Model\Mailing
     */
    protected $mailing;

    /**
     * accreditation
     *
     * @var \BucheggerOnline\Publicrelations\Domain\Model\Accreditation
     */
    protected $accreditation;

    /**
     * type
     *
     * @var int
     *
     */
    protected $type = 0;

    /**
     * code
     *
     * @var string
     *
     */
    protected $code = '';

    /**
     * receiver
     *
     * @var \BucheggerOnline\Publicrelations\Domain\Model\TtAddress
     */
    protected $receiver;

    /**
     * email
     *
     * @var string
     *
     */
    protected $email = '';

    /**
     * subject
     *
     * @var string
     *
     */
    protected $subject = '';

    /**
     * content
     *
     * @var string
     *
     */
    protected $content = '';

    /**
     * sent
     *
     * @var \DateTime
     *
     */
    protected $sent;

    /**
     * opened
     *
     * @var \DateTime
     *
     */
    protected $opened;

    /**
     * logs
     *
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BucheggerOnline\Publicrelations\Domain\Model\Log>
     * @TYPO3\CMS\Extbase\Annotation\ORM\Lazy
     */
    protected $logs;

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
     * Returns the mailing
     *
     * @param \BucheggerOnline\Publicrelations\Domain\Model\Mailing $mailing
     * @return void
     */
    public function getMailing()
    {
        return $this->mailing;
    }

    /**
     * Sets the mailing
     *
     * @return void
     */
    public function setMailing(\BucheggerOnline\Publicrelations\Domain\Model\Mailing $mailing)
    {
        $this->mailing = $mailing;
    }

    /**
     * Returns the accreditation
     *
     * @param \BucheggerOnline\Publicrelations\Domain\Model\Accreditation $accreditation
     * @return void
     */
    public function getAccreditation()
    {
        return $this->accreditation;
    }

    /**
     * Sets the accreditation
     *
     * @return void
     */
    public function setAccreditation(\BucheggerOnline\Publicrelations\Domain\Model\Accreditation $accreditation)
    {
        $this->accreditation = $accreditation;
    }

    /**
     * Returns the type
     *
     * @return int type
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
            case 0:
                return 'vorbereitet';
            case 1:
                return 'versandt';
            case -1:
                return 'fehlerhaft';
            default:
                return 'unbekannt';
        }
    }

    /**
     * Sets the type
     *
     * @param bool $type
     * @return void
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * Returns the code
     *
     * @return string $code
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Sets the code
     *
     * @param string $code
     * @return void
     */
    public function setCode($code)
    {
        $this->code = $code;
    }

    /**
     * Returns the receiver
     *
     * @param \BucheggerOnline\Publicrelations\Domain\Model\TtAddress $receiver
     * @return void
     */
    public function getReceiver()
    {
        return $this->receiver;
    }

    /**
     * Sets the receiver
     *
     * @return void
     */
    public function setReceiver(\BucheggerOnline\Publicrelations\Domain\Model\TtAddress $receiver)
    {
        $this->receiver = $receiver;
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
     * Returns the content
     *
     * @return string $content
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Sets the content
     *
     * @param string $content
     * @return void
     */
    public function setContent($content)
    {
        $this->content = $content;
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
     * Returns the opened
     *
     * @return \DateTimeInterface $opened
     */
    public function getOpened()
    {
        return $this->opened;
    }

    /**
     * Sets the opened
     *
     * @return void
     */
    public function setOpened(\DateTimeInterface $opened)
    {
        $this->opened = $opened;
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
}
