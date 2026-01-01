<?php
namespace BucheggerOnline\Publicrelations\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * ClippingRoute
 */
class ClippingRoute extends AbstractEntity
{
    /**
     * @var string
     * @TYPO3\CMS\Extbase\Annotation\Validate("NotEmpty")
     */
    protected $keyword = '';

    /**
     * @var \BucheggerOnline\Publicrelations\Domain\Model\Client
     */
    protected $client;

    /**
     * @var \BucheggerOnline\Publicrelations\Domain\Model\Campaign
     */
    protected $project;

    /**
     * @var string
     */
    protected $drive = '';

    /**
     * @var bool
     */
    protected $sendImmediate = true;

    /**
     * @var string
     */
    protected $toEmails = '';

    /**
     * @var string
     */
    protected $ccEmails = '';

    /**
     * @var string
     */
    protected $bccEmails = '';

    /**
     * @return string
     */
    public function getKeyword(): string
    {
        return $this->keyword;
    }

    /**
     * @param string $keyword
     */
    public function setKeyword(string $keyword): void
    {
        $this->keyword = $keyword;
    }

    /**
     * @return \BucheggerOnline\Publicrelations\Domain\Model\Client $client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @param \BucheggerOnline\Publicrelations\Domain\Model\Client $client
     */
    public function setClient(\BucheggerOnline\Publicrelations\Domain\Model\Client $client): void
    {
        $this->client = $client;
    }

    /**
     * @return \BucheggerOnline\Publicrelations\Domain\Model\Campaign $project
     */
    public function getProject()
    {
        return $this->project;
    }

    /**
     * @param \BucheggerOnline\Publicrelations\Domain\Model\Campaign $project
     */
    public function setProject(\BucheggerOnline\Publicrelations\Domain\Model\Campaign $project): void
    {
        $this->project = $project;
    }

    /**
     * @return string
     */
    public function getDrive(): string
    {
        return $this->drive;
    }

    /**
     * @param string $drive
     */
    public function setDrive(string $drive): void
    {
        $this->drive = $drive;
    }

    /**
     * @return bool
     */
    public function isSendImmediate(): bool
    {
        return $this->sendImmediate;
    }

    /**
     * @param bool $sendImmediate
     */
    public function setSendImmediate(bool $sendImmediate): void
    {
        $this->sendImmediate = $sendImmediate;
    }

    /**
     * @return string
     */
    public function getToEmails(): string
    {
        return $this->toEmails;
    }

    /**
     * @param string $toEmails
     */
    public function setToEmails(string $toEmails): void
    {
        $this->toEmails = $toEmails;
    }

    /**
     * @return string
     */
    public function getCcEmails(): string
    {
        return $this->ccEmails;
    }

    /**
     * @param string $ccEmails
     */
    public function setCcEmails(string $ccEmails): void
    {
        $this->ccEmails = $ccEmails;
    }

    /**
     * @return string
     */
    public function getBccEmails(): string
    {
        return $this->bccEmails;
    }

    /**
     * @param string $bccEmails
     */
    public function setBccEmails(string $bccEmails): void
    {
        $this->bccEmails = $bccEmails;
    }
}