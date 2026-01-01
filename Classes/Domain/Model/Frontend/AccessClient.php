<?php
declare(strict_types=1);
namespace BucheggerOnline\Publicrelations\Domain\Model\Frontend;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Extbase\Domain\Model\FrontendUser;
use TYPO3\CMS\Extbase\Domain\Model\FrontendUserGroup;

class AccessClient extends AbstractEntity
{
    protected ?Client $client = null;

    protected bool $viewClippings = false;
    protected bool $viewContacts = false;
    protected bool $editContacts = false;
    protected bool $deleteContacts = false;
    protected bool $viewMedia = false;
    protected bool $viewNews = false;
    protected bool $viewEvents = false;

    /**
     * @var ObjectStorage<AccessEvent>
     * @TYPO3\CMS\Extbase\Annotation\ORM\Lazy
     * @TYPO3\CMS\Extbase\Annotation\ORM\Cascade("remove")
     */
    protected ObjectStorage $advancedEvents;

    public function __construct()
    {
        $this->advancedEvents = new ObjectStorage();
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(?Client $client): void
    {
        $this->client = $client;
    }

    public function isViewClippings(): bool
    {
        return $this->viewClippings;
    }

    public function setViewClippings(bool $viewClippings): void
    {
        $this->viewClippings = $viewClippings;
    }

    public function isViewContacts(): bool
    {
        return $this->viewContacts;
    }

    public function setViewContacts(bool $viewContacts): void
    {
        $this->viewContacts = $viewContacts;
    }

    public function isEditContacts(): bool
    {
        return $this->editContacts;
    }

    public function setEditContacts(bool $editContacts): void
    {
        $this->editContacts = $editContacts;
    }

    public function isDeleteContacts(): bool
    {
        return $this->deleteContacts;
    }

    public function setDeleteContacts(bool $deleteContacts): void
    {
        $this->deleteContacts = $deleteContacts;
    }

    public function isViewMedia(): bool
    {
        return $this->viewMedia;
    }

    public function setViewMedia(bool $viewMedia): void
    {
        $this->viewMedia = $viewMedia;
    }

    public function isViewNews(): bool
    {
        return $this->viewNews;
    }

    public function setViewNews(bool $viewNews): void
    {
        $this->viewNews = $viewNews;
    }

    public function isViewEvents(): bool
    {
        return $this->viewEvents;
    }

    public function setViewEvents(bool $viewEvents): void
    {
        $this->viewEvents = $viewEvents;
    }

    public function getAdvancedEvents(): ObjectStorage
    {
        return $this->advancedEvents;
    }

    public function setAdvancedEvents(ObjectStorage $advancedEvents): void
    {
        $this->advancedEvents = $advancedEvents;
    }

    public function addAdvancedEvent(AccessEvent $event): void
    {
        $this->advancedEvents->attach($event);
    }

    public function removeAdvancedEvent(AccessEvent $event): void
    {
        $this->advancedEvents->detach($event);
    }
}