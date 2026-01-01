<?php

declare(strict_types=1);

namespace BucheggerOnline\Publicrelations\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

class SocialProfile extends AbstractEntity
{
    protected string $type = '';

    protected string $handle = '';

    protected int $follower = 0;

    protected ?\DateTime $followerUpdated = null;

    protected string $notes = '';

    /**
     * Der übergeordnete Kontakt (TtAddress)
     *
     * @var \BucheggerOnline\Publicrelations\Domain\Model\TtAddress
     */
    protected $contact = null;

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getHandle(): string
    {
        return $this->handle;
    }

    public function setHandle(string $handle): void
    {
        $this->handle = $handle;
    }

    public function getFollower(): int
    {
        return $this->follower;
    }

    public function setFollower(int $follower): void
    {
        $this->follower = $follower;
    }

    public function getFollowerUpdated(): ?\DateTime
    {
        return $this->followerUpdated;
    }

    public function setFollowerUpdated(?\DateTime $followerUpdated): void
    {
        $this->followerUpdated = $followerUpdated;
    }

    public function getNotes(): string
    {
        return $this->notes;
    }

    public function setNotes(string $notes): void
    {
        $this->notes = $notes;
    }

    /**
     * @return \BucheggerOnline\Publicrelations\Domain\Model\TtAddress|null
     */
    public function getContact(): ?\BucheggerOnline\Publicrelations\Domain\Model\TtAddress
    {
        return $this->contact;
    }

    /**
     * @param \BucheggerOnline\Publicrelations\Domain\Model\TtAddress|null $contact
     */
    public function setContact(?\BucheggerOnline\Publicrelations\Domain\Model\TtAddress $contact): void
    {
        $this->contact = $contact;
    }

}