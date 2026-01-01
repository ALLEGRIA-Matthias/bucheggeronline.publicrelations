<?php
declare(strict_types=1);
namespace BucheggerOnline\Publicrelations\Domain\Model\Frontend;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

class AccessEvent extends AbstractEntity
{
    // Wichtig für die Rück-Beziehung
    protected ?AccessClient $accessClient = null;

    // Annahme: Du hast ein Event-Model
    protected ?Event $event = null;

    protected string $accessLevel = '';

    // --- GETTER & SETTER ---

    public function getAccessClient(): ?AccessClient
    {
        return $this->accessClient;
    }

    public function setAccessClient(?AccessClient $accessClient): void
    {
        $this->accessClient = $accessClient;
    }

    public function getEvent(): ?Event
    {
        return $this->event;
    }

    public function setEvent(?Event $event): void
    {
        $this->event = $event;
    }

    public function getAccessLevel(): string
    {
        return $this->accessLevel;
    }

    public function setAccessLevel(string $accessLevel): void
    {
        $this->accessLevel = $accessLevel;
    }
}