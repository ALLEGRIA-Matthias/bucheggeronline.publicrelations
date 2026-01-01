<?php
namespace BucheggerOnline\Publicrelations\Domain\Model;

use TYPO3\CMS\Core\Utility\GeneralUtility;

use TYPO3\CMS\Beuser\Domain\Repository\BackendUserRepository;


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
 * Log
 */
class Log extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
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
     * code
     *
     * @var string
     */
    protected $code = '';

    /**
     * function
     *
     * @var string
     */
    protected $function = '';

    /**
     * subject
     *
     * @var string
     */
    protected $subject = '';

    /**
     * notes
     *
     * @var string
     */
    protected $notes = '';

    /**
     * accreditation
     *
     * @var \BucheggerOnline\Publicrelations\Domain\Model\Accreditation
     */
    protected $accreditation;

    /**
     * event
     *
     * @var \BucheggerOnline\Publicrelations\Domain\Model\Event
     */
    protected $event;

    /**
     * address
     *
     * @var \BucheggerOnline\Publicrelations\Domain\Model\TtAddress
     */
    protected $address;

    /**
     * mail
     *
     * @var \BucheggerOnline\Publicrelations\Domain\Model\Mail
     */
    protected $mail;

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
     * Get creator user
     *
     * @return void
     */
    public function getCruser()
    {

        return GeneralUtility::makeInstance(BackendUserRepository::class)->findByUid($this->cruserId);
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
     * Returns the function
     *
     * @return string $function
     */
    public function getFunction()
    {
        return $this->function;
    }

    /**
     * Sets the function
     *
     * @param string $function
     * @return void
     */
    public function setFunction($function)
    {
        $this->function = $function;
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
     * Returns the accreditation
     *
     * @return \BucheggerOnline\Publicrelations\Domain\Model\Accreditation $accreditation
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
     * Returns the address
     *
     * @return \BucheggerOnline\Publicrelations\Domain\Model\TtAddress $address
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * Sets the address
     *
     * @return void
     */
    public function setAddress(\BucheggerOnline\Publicrelations\Domain\Model\TtAddress|\BucheggerOnline\Publicrelations\Domain\Model\Frontend\TtAddress $address)
    {
        $this->address = $address;
    }

    /**
     * Returns the mail
     *
     * @return \BucheggerOnline\Publicrelations\Domain\Model\Mail $mail
     */
    public function getMail()
    {
        return $this->mail;
    }

    /**
     * Sets the mail
     *
     * @return void
     */
    public function setMail(\BucheggerOnline\Publicrelations\Domain\Model\Mail $mail)
    {
        $this->mail = $mail;
    }
}
