<?php
namespace BucheggerOnline\Publicrelations\Domain\Model;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Annotation\ORM\Lazy;

use BucheggerOnline\Publicrelations\Utility\GeneralFunctions;

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
 * Campaign
 */
class Campaign extends AbstractEntity
{

    /**
     * client
     *
     * @var \BucheggerOnline\Publicrelations\Domain\Model\Client
     */
    protected $client;

    /**
     * types
     *
     * @var \BucheggerOnline\Publicrelations\Domain\Model\SysCategory
     */
    protected $type;

    /**
     * title
     *
     * @var string
     *
     */
    protected $title = '';

    /**
     * subtitle
     *
     * @var string
     */
    protected $subtitle = '';

    /**
     * description
     *
     * @var string
     *
     */
    protected $description = '';

    /**
     * openend
     *
     * @var bool
     */
    protected $openend = false;

    /**
     * logo
     *
     * @var \TYPO3\CMS\Extbase\Domain\Model\FileReference
     * @TYPO3\CMS\Extbase\Annotation\ORM\Cascade("remove")
     */
    protected $logo;

    /**
     * covers
     *
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\TYPO3\CMS\Extbase\Domain\Model\FileReference>
     * @TYPO3\CMS\Extbase\Annotation\ORM\Cascade("remove")
     */
    protected $covers;

    /**
     * archiveDate
     *
     * @var \DateTime
     */
    protected $archiveDate;

    /**
     * locationNote
     *
     * @var string
     */
    protected $locationNote = '';

    /**
     * locationManual
     *
     * @var string
     */
    protected $locationManual = '';

    /**
     * location
     *
     * @var \BucheggerOnline\Publicrelations\Domain\Model\Location
     */
    protected $location;

    /**
     * events
     *
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BucheggerOnline\Publicrelations\Domain\Model\Event>
     * @Lazy
     */
    protected $events;

    /**
     * links
     *
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BucheggerOnline\Publicrelations\Domain\Model\Link>
     * @TYPO3\CMS\Extbase\Annotation\ORM\Cascade("remove")
     * @Lazy
     */
    protected $links;

    /**
     * contacts
     *
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BucheggerOnline\Publicrelations\Domain\Model\Contact>
     * @TYPO3\CMS\Extbase\Annotation\ORM\Cascade("remove")
     * @Lazy
     */
    protected $contacts;

    /**
     * news
     *
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BucheggerOnline\Publicrelations\Domain\Model\News>
     * @TYPO3\CMS\Extbase\Annotation\ORM\Cascade("remove")
     * @Lazy
     */
    protected $news;

    /**
     * mediagroups
     *
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BucheggerOnline\Publicrelations\Domain\Model\MediaGroup>
     * @TYPO3\CMS\Extbase\Annotation\ORM\Cascade("remove")
     * @Lazy
     */
    protected $mediagroups;

    /**
     * slug
     *
     * @var string
     */
    protected $slug = '';

    /**
     * seoTitle
     *
     * @var string
     */
    protected $seoTitle = '';

    /**
     * seoDescription
     *
     * @var string
     */
    protected $seoDescription = '';

    /**
     * sorting
     *
     * @var int
     */
    protected $sorting = 0;

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
     * Returns the type
     *
     * @return \BucheggerOnline\Publicrelations\Domain\Model\SysCategory type
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Sets the type
     *
     * @return void
     */
    public function setType(\BucheggerOnline\Publicrelations\Domain\Model\SysCategory $type)
    {
        $this->type = $type;
    }

    /**
     * Returns the title
     *
     * @return string $title
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
     * Returns the first non-cancelled, non-rescheduled event, or null if none.
     */
    public function getFirstEvent(): ?Event
    {
        $firstEvent = null;
        foreach ($this->getEvents() ?? [] as $event) {
            if (!$event->getCanceled() && !$event->getNewEvent()) {
                $firstEvent = $event;
                break;
            }
        }
        return $firstEvent;
    }

    /**
     * Returns the sortTitle
     *
     * @return string $sortTitle
     */
    public function getSortTitle()
    {
        if ($this->getClient()->getSort() === 0) {
            if ($this->getFirstEvent())
                $output = date_timestamp_get($this->getFirstEvent()->getDate());
            else
                $output = GeneralFunctions::makeSortable($this->getTitle());
        }

        if ($this->getClient()->getSort() === 1) {
            $output = GeneralFunctions::makeSortable($this->getTitle());
        }

        if ($this->getClient()->getSort() === 2) {
            $output = $this->getSorting();
        }

        return $output;
    }

    /**
     * Returns the description
     *
     * @return string $description
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Sets the description
     *
     * @param string $description
     * @return void
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * Returns the openend
     *
     * @return bool $openend
     */
    public function getOpenend()
    {
        return $this->openend;
    }

    /**
     * Sets the openend
     *
     * @param bool $openend
     * @return void
     */
    public function setOpenend($openend)
    {
        $this->openend = $openend;
    }

    /**
     * Returns the boolean state of openend
     *
     * @return bool
     */
    public function isOpenend()
    {
        return $this->openend;
    }

    /**
     * Returns the logo
     *
     * @return \TYPO3\CMS\Extbase\Domain\Model\FileReference $logo
     */
    public function getLogo()
    {

        return $this->logo ?? $this->getCLient()->getLogo();
    }

    /**
     * Adds a FileReference
     *
     * @return void
     */
    public function addCover(\TYPO3\CMS\Extbase\Domain\Model\FileReference $cover)
    {
        $this->covers->attach($cover);
    }

    /**
     * Removes a FileReference
     *
     * @param \TYPO3\CMS\Extbase\Domain\Model\FileReference $coverToRemove The FileReference to be removed
     * @return void
     */
    public function removeCover(\TYPO3\CMS\Extbase\Domain\Model\FileReference $coverToRemove)
    {
        $this->covers->detach($coverToRemove);
    }

    /**
     * Returns the covers
     *
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\TYPO3\CMS\Extbase\Domain\Model\FileReference> $covers
     */
    public function getCovers()
    {
        return $this->covers;
    }

    /**
     * Sets the covers
     *
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\TYPO3\CMS\Extbase\Domain\Model\FileReference> $covers
     * @return void
     */
    public function setCovers(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $covers)
    {
        $this->covers = $covers;
    }

    /**
     * Sets the logo
     *
     * @return void
     */
    public function setLogo(\TYPO3\CMS\Extbase\Domain\Model\FileReference $logo)
    {
        $this->logo = $logo;
    }

    /**
     * Returns the archiveDate
     *
     * @return \DateTimeInterface $archiveDate
     */
    public function getArchiveDate()
    {
        return $this->archiveDate;
    }

    /**
     * Sets the archiveDate
     *
     * @return void
     */
    public function setArchiveDate(\DateTimeInterface $archiveDate)
    {
        $this->archiveDate = $archiveDate;
    }

    /**
     * Returns the locationNote
     *
     * @return string $locationNote
     */
    public function getLocationNote()
    {
        return $this->locationNote;
    }

    /**
     * Sets the locationNote
     *
     * @param string $locationNote
     * @return void
     */
    public function setLocationNote($locationNote)
    {
        $this->locationNote = $locationNote;
    }

    /**
     * Returns the locationManual
     *
     * @return string $locationManual
     */
    public function getLocationManual()
    {
        return $this->locationManual;
    }

    /**
     * Sets the locationManual
     *
     * @param string $locationManual
     * @return void
     */
    public function setLocationManual($locationManual)
    {
        $this->locationManual = $locationManual;
    }

    /**
     * Returns the location
     *
     * @return \BucheggerOnline\Publicrelations\Domain\Model\Location location
     */
    public function getLocation()
    {
        return $this->location ?? $this->getClient()->getLocation();
    }

    /**
     * Sets the location
     *
     * @param string $location
     * @return void
     */
    public function setLocation($location)
    {
        $this->location = $location;
    }

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
        $this->events = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
        $this->covers = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
        $this->links = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
        $this->contacts = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
        $this->news = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
        $this->mediagroups = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
    }

    /**
     * Adds a News
     *
     * @return void
     */
    public function addNews(\BucheggerOnline\Publicrelations\Domain\Model\News $news)
    {
        $this->news->attach($news);
    }

    /**
     * Removes a News
     *
     * @param \BucheggerOnline\Publicrelations\Domain\Model\News $newsToRemove The News to be removed
     * @return void
     */
    public function removeNews(\BucheggerOnline\Publicrelations\Domain\Model\News $news)
    {
        $this->news->detach($newsToRemove);
    }

    /**
     * Returns the news
     *
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BucheggerOnline\Publicrelations\Domain\Model\News> news
     */
    public function getNews()
    {
        return $this->news;
    }

    /**
     * Sets the news
     *
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BucheggerOnline\Publicrelations\Domain\Model\News> $news
     * @return void
     */
    public function setNews(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $news)
    {
        $this->news = $news;
    }

    /**
     * Returns the releasedNews
     */
    public function getReleasedNews()
    {
        $releasedNews = [];
        if ($this->getNews()->count()) {
            foreach ($this->getNews() as $news) {
                if ($news->getDate() <= new \DateTime('now'))
                    $releasedNews[] = $news;
            }
        }

        return $releasedNews;

    }

    /**
     * Adds a MediaGroup
     *
     * @return void
     */
    public function addMediagroup(\BucheggerOnline\Publicrelations\Domain\Model\MediaGroup $mediagroup)
    {
        $this->mediagroups->attach($mediagroup);
    }

    /**
     * Removes a MediaGroup
     *
     * @param \BucheggerOnline\Publicrelations\Domain\Model\MediaGroup $mediagroupToRemove The MediaGroup to be removed
     * @return void
     */
    public function removeMediagroup(\BucheggerOnline\Publicrelations\Domain\Model\MediaGroup $mediagroupToRemove)
    {
        $this->mediagroups->detach($mediagroupToRemove);
    }

    /**
     * Returns the mediagroups
     *
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BucheggerOnline\Publicrelations\Domain\Model\MediaGroup> $mediagroups
     */
    public function getMediagroups()
    {
        return $this->mediagroups;
    }

    /**
     * Sets the mediagroups
     *
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BucheggerOnline\Publicrelations\Domain\Model\MediaGroup> $mediagroups
     * @return void
     */
    public function setMediagroups(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $mediagroups)
    {
        $this->mediagroups = $mediagroups;
    }

    /**
     * Adds a Event
     *
     * @return void
     */
    public function addEvent(\BucheggerOnline\Publicrelations\Domain\Model\Event $event)
    {
        $this->events->attach($event);
    }

    /**
     * Removes a Event
     *
     * @param \BucheggerOnline\Publicrelations\Domain\Model\Event $eventToRemove The Event to be removed
     * @return void
     */
    public function removeEvent(\BucheggerOnline\Publicrelations\Domain\Model\Event $eventToRemove)
    {
        $this->events->detach($eventToRemove);
    }

    /**
     * Returns the events
     *
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BucheggerOnline\Publicrelations\Domain\Model\Event> $events
     */
    public function getEvents()
    {
        return $this->events;
    }

    /**
     * Sets the events
     *
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BucheggerOnline\Publicrelations\Domain\Model\Event> $events
     * @return void
     */
    public function setEvents(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $events)
    {
        $this->events = $events;
    }

    /**
     * Returns the pressEvents
     *
     * @return $pressEvents
     */
    public function getPressEvents()
    {
        return GeneralFunctions::getPressEvents($this->getEvents());
    }

    /**
     * Returns the events
     *
     * @return $events
     */
    public function getUpcomingEvents()
    {
        return GeneralFunctions::getUpcomingEvents($this->getEvents());
    }

    /**
     * Returns the schedule
     *
     * @return $schedule
     */
    public function getSchedule()
    {
        $events = [];
        if ($this->getUpcomingEvents()) {
            foreach ($this->getUpcomingEvents() as $event) {
                if ($event->getType()->isSchedule()) {
                    $events[] = $event;
                }
            }
        } else {
            $events = NULL;
        }
        return $events;
    }

    /**
     * Returns the boolean state of ongoing
     *
     * @return bool
     */
    public function isOngoing()
    {
        $output = FALSE;
        if ($this->getUpcomingEvents() || $this->isOpenend() || !$this->getEvents()->count())
            $output = TRUE;
        return $output;
    }

    /**
     * Returns the subtitle
     *
     * @return string $subtitle
     */
    public function getSubtitle()
    {
        return $this->subtitle;
    }

    /**
     * Sets the subtitle
     *
     * @param string $subtitle
     * @return void
     */
    public function setSubtitle($subtitle)
    {
        $this->subtitle = $subtitle;
    }

    /**
     * Adds a Link
     *
     * @return void
     */
    public function addLink(\BucheggerOnline\Publicrelations\Domain\Model\Link $link)
    {
        $this->links->attach($link);
    }

    /**
     * Removes a Link
     *
     * @param \BucheggerOnline\Publicrelations\Domain\Model\Link $linkToRemove The Link to be removed
     * @return void
     */
    public function removeLink(\BucheggerOnline\Publicrelations\Domain\Model\Link $linkToRemove)
    {
        $this->links->detach($linkToRemove);
    }

    /**
     * Returns the links
     *
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BucheggerOnline\Publicrelations\Domain\Model\Link> $links
     */
    public function getLinks()
    {
        return $this->links;
    }

    /**
     * Sets the links
     *
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BucheggerOnline\Publicrelations\Domain\Model\Link> $links
     * @return void
     */
    public function setLinks(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $links)
    {
        $this->links = $links;
    }

    /**
     * Adds a Contact
     *
     * @return void
     */
    public function addContact(\BucheggerOnline\Publicrelations\Domain\Model\Contact $contact)
    {
        $this->contacts->attach($contact);
    }

    /**
     * Removes a Contact
     *
     * @param \BucheggerOnline\Publicrelations\Domain\Model\Contact $contactToRemove The Contact to be removed
     * @return void
     */
    public function removeContact(\BucheggerOnline\Publicrelations\Domain\Model\Contact $contactToRemove)
    {
        $this->contacts->detach($contactToRemove);
    }

    /**
     * Returns the contacts based on a priority:
     * 1. Contacts directly assigned.
     * 2. Contacts from the associated client.
     *
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BucheggerOnline\Publicrelations\Domain\Model\Contact>
     */
    public function getContacts()
    {
        // 1. Priority: Direct contacts
        // Prüfen, ob direkt Kontakte zugewiesen sind und die Collection Elemente enthält.
        if ($this->contacts && $this->contacts->count() > 0) {
            return $this->contacts;
        }

        // 2. Priority: Contacts from the client
        // Zuerst den Client holen und sicher prüfen, ob er existiert.
        $client = $this->getClient();
        if ($client) {
            // Nur wenn ein Client vorhanden ist, dessen Kontakte zurückgeben.
            return $client->getContacts();
        }

        // Fallback: Einen leeren ObjectStorage zurückgeben, um Typsicherheit zu gewährleisten.
        return new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
    }

    /**
     * Sets the contacts
     *
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BucheggerOnline\Publicrelations\Domain\Model\Contact> $contacts
     * @return void
     */
    public function setContacts(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $contacts)
    {
        $this->contacts = $contacts;
    }

    /**
     * Returns the slug
     *
     * @return string $slug
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * Sets the slug
     *
     * @param string $slug
     * @return void
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;
    }

    /**
     * Returns the seoTitle
     *
     * @return string $seoTitle
     */
    public function getSeoTitle()
    {
        return $this->seoTitle;
    }

    /**
     * Sets the seoTitle
     *
     * @param string $seoTitle
     * @return void
     */
    public function setSeoTitle($seoTitle)
    {
        $this->seoTitle = $seoTitle;
    }

    /**
     * Returns the seoDescription
     *
     * @return string $seoDescription
     */
    public function getSeoDescription()
    {
        return $this->seoDescription;
    }

    /**
     * Sets the seoDescription
     *
     * @param string $seoDescription
     * @return void
     */
    public function setSeoDescription($seoDescription)
    {
        $this->seoDescription = $seoDescription;
    }

    /**
     * Returns the sorting
     *
     * @return int $sorting
     */
    public function getSorting()
    {
        return $this->sorting;
    }

    /**
     * Sets the sorting
     *
     * @param int $sorting
     * @return void
     */
    public function setSorting($sorting)
    {
        $this->sorting = $sorting;
    }
}
