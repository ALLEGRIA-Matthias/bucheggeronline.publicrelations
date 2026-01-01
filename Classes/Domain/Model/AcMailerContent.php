<?php

namespace BucheggerOnline\Publicrelations\Domain\Model;

use Allegria\AcMailer\Domain\Model\Content as OriginalContent;

/**
 * This model represents a tag for anything.
 */
class AcMailerContent extends OriginalContent
{
    protected ?Event $event = null;
    protected ?string $eventTitle = null;
    protected ?string $eventDate = null;
    protected ?string $eventLocation = null;
    protected ?string $eventDescription = null;
    protected bool $eventLink = false;

    protected ?News $news = null;
    protected bool $newsMedia = false;

    public function getEvent(): ?Event
    {
        return $this->event;
    }

    public function setEvent(?Event $event): void
    {
        $this->event = $event;
    }

    public function getEventDate(): ?string
    {
        return $this->eventDate;
    }

    public function setEventDate(?string $eventDate): void
    {
        $this->eventDate = $eventDate;
    }

    public function getEventDescription(): ?string
    {
        return $this->eventDescription;
    }

    public function setEventDescription(?string $eventDescription): void
    {
        $this->eventDescription = $eventDescription;
    }

    public function isEventLink(): bool
    {
        return $this->eventLink;
    }

    public function setEventLink(bool $eventLink): void
    {
        $this->eventLink = $eventLink;
    }

    public function getEventLocation(): ?string
    {
        return $this->eventLocation;
    }

    public function setEventLocation(?string $eventLocation): void
    {
        $this->eventLocation = $eventLocation;
    }

    public function getEventTitle(): ?string
    {
        return $this->eventTitle;
    }

    public function setEventTitle(?string $eventTitle): void
    {
        $this->eventTitle = $eventTitle;
    }

    public function getNews(): ?News
    {
        return $this->news;
    }

    public function setNews(?News $news): void
    {
        $this->news = $news;
    }

    public function isNewsMedia(): bool
    {
        return $this->newsMedia;
    }

    public function setNewsMedia(bool $newsMedia): void
    {
        $this->newsMedia = $newsMedia;
    }
}