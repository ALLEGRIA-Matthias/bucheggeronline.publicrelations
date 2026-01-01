<?php

namespace BucheggerOnline\Publicrelations\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;

class InvitationVariant extends AbstractEntity
{
    protected string $code = '';
    protected string $html = '';
    protected string $subject = '';
    protected string $fromName = '';
    protected string $replyEmail = '';
    protected string $replyName = '';
    protected string $preheader = '';

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\TYPO3\CMS\Extbase\Domain\Model\FileReference>
     * @TYPO3\CMS\Extbase\Annotation\ORM\Cascade("remove")
     */
    protected $attachments;
    protected ?Invitation $invitation = null;

    public function __construct()
    {
        // Initialisiere die ObjectStorage
        $this->attachments = new ObjectStorage();
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): void
    {
        $this->code = $code;
    }

    public function getHtml(): string
    {
        return $this->html;
    }

    public function setHtml(string $html): void
    {
        $this->html = $html;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function setSubject(string $subject): void
    {
        $this->subject = $subject;
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

    public function getReplyEmail(): string
    {
        return $this->replyEmail;
    }

    public function setReplyEmail(string $replyEmail): void
    {
        $this->replyEmail = $replyEmail;
    }

    public function getReplyName(): string
    {
        return $this->replyName;
    }

    public function setReplyName(string $replyName): void
    {
        $this->replyName = $replyName;
    }

    public function getPreheader(): string
    {
        return $this->preheader;
    }

    public function setPreheader(string $preheader): void
    {
        $this->preheader = $preheader;
    }

    /**
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\TYPO3\CMS\Extbase\Domain\Model\FileReference>
     */
    public function getAttachments()
    {
        return $this->attachments;
    }

    /**
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\TYPO3\CMS\Extbase\Domain\Model\FileReference> $attachments
     */
    public function setAttachments(ObjectStorage $attachments)
    {
        $this->attachments = $attachments;
    }

    public function getInvitation(): ?Invitation
    {
        return $this->invitation;
    }

    public function setInvitation(?Invitation $invitation): void
    {
        $this->invitation = $invitation;
    }
}