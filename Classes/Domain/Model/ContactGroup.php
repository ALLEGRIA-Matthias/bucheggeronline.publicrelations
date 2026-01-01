<?php

declare(strict_types=1);

namespace BucheggerOnline\Publicrelations\Domain\Model;

use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

class ContactGroup extends AbstractEntity
{
    protected string $name = '';

    protected string $description = '';

    protected ?FileReference $logo = null;

    protected ?ContactGroup $parent = null;

    /**
     * @var ObjectStorage<\FriendsOfTYPO3\TtAddress\Domain\Model\Address>
     */
    protected ObjectStorage $contacts;

    public function __construct(string $name = '')
    {
        $this->name = $name;
        $this->contacts = new ObjectStorage();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getLogo(): ?FileReference
    {
        return $this->logo;
    }

    public function setLogo(?FileReference $logo): void
    {
        $this->logo = $logo;
    }

    public function getParent(): ?ContactGroup
    {
        return $this->parent;
    }

    public function setParent(?ContactGroup $parent): void
    {
        $this->parent = $parent;
    }

    /**
     * @return ObjectStorage<\FriendsOfTYPO3\TtAddress\Domain\Model\Address>
     */
    public function getContacts(): ObjectStorage
    {
        return $this->contacts;
    }

    /**
     * @param ObjectStorage<\FriendsOfTYPO3\TtAddress\Domain\Model\Address> $contacts
     */
    public function setContacts(ObjectStorage $contacts): void
    {
        $this->contacts = $contacts;
    }
}