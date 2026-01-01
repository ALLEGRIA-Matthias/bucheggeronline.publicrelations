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
 * Client
 */
class Client extends AbstractEntity
{

    /**
     * types
     *
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BucheggerOnline\Publicrelations\Domain\Model\SysCategory>
     */
    protected $types;

    /**
     * activities
     *
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BucheggerOnline\Publicrelations\Domain\Model\SysCategory>
     */
    protected $activities;

    /**
     * name
     *
     * @var string
     */
    protected $name = '';

    /**
     * shortName
     *
     * @var string
     */
    protected $shortName = '';

    /**
     * alsoKnownAs
     *
     * @var string
     */
    protected $alsoKnownAs = '';

    /**
     * shortinfo
     *
     * @var string
     */
    protected $shortinfo = '';

    /**
     * sort
     *
     * @var int
     */
    protected $sort = 0;

    /**
     * top
     *
     * @var bool
     */
    protected $top = false;

    /**
     * archive
     *
     * @var bool
     */
    protected $archive = false;

    /**
     * description
     *
     * @var string
     */
    protected $description = '';

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
     * since
     *
     * @var \DateTime
     */
    protected $since;

    /**
     * until
     *
     * @var \DateTime
     */
    protected $until;

    /**
     * phone
     *
     * @var string
     */
    protected $phone = '';

    /**
     * email
     *
     * @var string
     */
    protected $email = '';

    /**
     * location
     *
     * @var \BucheggerOnline\Publicrelations\Domain\Model\Location
     */
    protected $location;

    /**
     * links
     *
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BucheggerOnline\Publicrelations\Domain\Model\Link>
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
     * campaigns
     *
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BucheggerOnline\Publicrelations\Domain\Model\Campaign>
     * @TYPO3\CMS\Extbase\Annotation\ORM\Cascade("remove")
     * @Lazy
     */
    protected $campaigns;

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
     * events
     *
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BucheggerOnline\Publicrelations\Domain\Model\Event>
     * @TYPO3\CMS\Extbase\Annotation\ORM\Cascade("remove")
     * @Lazy
     */
    protected $events;

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
     * Die Zugriffsrechte, die für diesen Client definiert wurden.
     *
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BucheggerOnline\Publicrelations\Domain\Model\AccessClient>
     * @TYPO3\CMS\Extbase\Annotation\ORM\Lazy
     * @TYPO3\CMS\Extbase\Annotation\ORM\Cascade("remove")
     */
    protected $accessRights;

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
        $this->types = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
        $this->activities = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
        $this->covers = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
        $this->links = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
        $this->contacts = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
        $this->campaigns = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
        $this->news = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
        $this->mediagroups = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
        $this->events = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
        $this->accessRights = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
    }

    /**
     * Adds a Type
     *
     * @return void
     */
    public function addType(\BucheggerOnline\Publicrelations\Domain\Model\SysCategory $type)
    {
        $this->types->attach($type);
    }

    /**
     * Removes a Category
     *
     * @param \BucheggerOnline\Publicrelations\Domain\Model\SysCategory $typeToRemove The Type to be removed
     * @return void
     */
    public function removeType(\BucheggerOnline\Publicrelations\Domain\Model\SysCategory $typeToRemove)
    {
        $this->types->detach($typeToRemove);
    }

    /**
     * Returns the types
     *
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BucheggerOnline\Publicrelations\Domain\Model\SysCategory> types
     */
    public function getTypes()
    {
        return $this->types;
    }

    /**
     * Sets the types
     *
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BucheggerOnline\Publicrelations\Domain\Model\SysCategory> $types
     * @return void
     */
    public function setTypes(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $types)
    {
        $this->types = $types;
    }

    /**
     * Adds a Activity
     *
     * @return void
     */
    public function addActivity(\BucheggerOnline\Publicrelations\Domain\Model\SysCategory $activity)
    {
        $this->activities->attach($activity);
    }

    /**
     * Removes a Activities
     *
     * @param \BucheggerOnline\Publicrelations\Domain\Model\SysCategory $activityToRemove The Activity to be removed
     * @return void
     */
    public function removeActivity(\BucheggerOnline\Publicrelations\Domain\Model\SysCategory $activityToRemove)
    {
        $this->activities->detach($activityToRemove);
    }

    /**
     * Returns the activities
     *
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BucheggerOnline\Publicrelations\Domain\Model\SysCategory> activities
     */
    public function getActivities()
    {
        return $this->activities;
    }

    /**
     * Sets the activities
     *
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BucheggerOnline\Publicrelations\Domain\Model\SysCategory> $activities
     * @return void
     */
    public function setActivities(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $activities)
    {
        $this->activities = $activities;
    }

    /**
     * Returns the name
     *
     * @return string $name
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Sets the name
     *
     * @param string $name
     * @return void
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Gibt den shortName zurück, falls vorhanden und nicht leer.
     * Ansonsten wird der name zurückgegeben.
     * Gibt einen leeren String zurück, wenn beide effektiv leer sind.
     *
     * @return string
     */
    public function getShortName(): string
    {
        // Prüfe, ob shortName nach dem Entfernen von Leerzeichen am Anfang/Ende noch Inhalt hat
        if (isset($this->shortName) && trim((string) $this->shortName) !== '') {
            return $this->shortName; // Gib den originalen shortName zurück (mit ggf. internem Whitespace)
        }

        // Fallback auf name. Stelle sicher, dass immer ein String zurückgegeben wird.
        return (string) ($this->name ?? '');
    }

    /**
     * Sets the shortName
     *
     * @param string $shortName
     * @return void
     */
    public function setShortName($shortName)
    {
        $this->shortName = $shortName;
    }

    /**
     * Gibt den vollständigen Namen inklusive des Shortnames zurück, falls vorhanden.
     * Beispiel: "Max Mustermann AG | MMA"
     *
     * @return string
     */
    public function getFullName(): string
    {
        // Beginne immer mit dem Hauptnamen
        $fullName = $this->name;

        // Wenn ein 'shortName' existiert und nicht leer ist, hänge ihn an
        if (!empty($this->shortName)) {
            $fullName .= ' | ' . $this->shortName;
        }

        return $fullName;
    }

    /**
     * Returns the sortName
     *
     * @return string $sortName
     */
    public function getSortName()
    {

        return GeneralFunctions::makeSortable($this->getShortName());
    }

    /**
     * Returns the sort
     *
     * @return int $sort
     */
    public function getSort()
    {
        return $this->sort;
    }

    /**
     * Sets the sort
     *
     * @param int $sort
     * @return void
     */
    public function setSort($sort)
    {
        $this->sort = $sort;
    }

    /**
     * Returns the top
     *
     * @return bool $top
     */
    public function getTop()
    {
        return $this->top;
    }

    /**
     * Sets the top
     *
     * @param bool $top
     * @return void
     */
    public function setTop($top)
    {
        $this->top = $top;
    }

    /**
     * Returns the boolean state of top
     *
     * @return bool
     */
    public function isTop()
    {
        return $this->top;
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
     * Returns the logo
     *
     * @return \TYPO3\CMS\Extbase\Domain\Model\FileReference $logo
     */
    public function getLogo()
    {
        return $this->logo;
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
     * Returns the since
     *
     * @return \DateTimeInterface $since
     */
    public function getSince()
    {
        return $this->since;
    }

    /**
     * Sets the since
     *
     * @return void
     */
    public function setSince(\DateTimeInterface $since)
    {
        $this->since = $since;
    }

    /**
     * Returns the until
     *
     * @return \DateTimeInterface $until
     */
    public function getUntil()
    {
        return $this->until;
    }

    /**
     * Sets the until
     *
     * @return void
     */
    public function setUntil(\DateTimeInterface $until)
    {
        $this->until = $until;
    }

    /**
     * Returns the phone
     *
     * @return string $phone
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * Sets the phone
     *
     * @param string $phone
     * @return void
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;
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
     * Adds a
     *
     * @param \BucheggerOnline\Publicrelations\Domain\Model\Campaign $campaign
     * @return void
     */
    public function addCampaign($campaign)
    {
        $this->campaigns->attach($campaign);
    }

    /**
     * Removes a
     *
     * @param \BucheggerOnline\Publicrelations\Domain\Model\Campaign $campaignToRemove The Campaign to be removed
     * @return void
     */
    public function removeCampaign($campaignToRemove)
    {
        $this->campaigns->detach($campaignToRemove);
    }

    /**
     * Returns the campaigns
     *
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BucheggerOnline\Publicrelations\Domain\Model\Campaign> campaigns
     */
    public function getCampaigns()
    {
        return $this->campaigns;
    }

    /**
     * Sets the campaigns
     *
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BucheggerOnline\Publicrelations\Domain\Model\Campaign> $campaigns
     * @return void
     */
    public function setCampaigns(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $campaigns)
    {
        $this->campaigns = $campaigns;
    }

    public function getOngoingCampaigns(): ?array
    {
        $campaigns = [];

        foreach ($this->getCampaigns() as $campaign) {
            if ($campaign->isOngoing()) {
                $campaigns[] = $campaign;
            }
        }

        return $campaigns ?: null;
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
        $releasedNews = null;
        if ($this->getNews()->count()) {
            foreach ($this->getNews() as $news) {
                if ($news->getDate() <= new \DateTime('now'))
                    $releasedNews[] = $news;
            }
        } else {
            $releasedNews = null;
        }

        return $releasedNews;

    }

    /**
     * Returns the archive
     *
     * @return bool $archive
     */
    public function getArchive()
    {
        return $this->archive;
    }

    /**
     * Sets the archive
     *
     * @param bool $archive
     * @return void
     */
    public function setArchive($archive)
    {
        $this->archive = $archive;
    }

    /**
     * Returns the boolean state of archive
     *
     * @return bool
     */
    public function isArchive()
    {
        return $this->archive;
    }

    /**
     * Returns the location
     *
     * @return \BucheggerOnline\Publicrelations\Domain\Model\Location location
     */
    public function getLocation()
    {
        return $this->location;
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
     * Adds a
     *
     * @param \BucheggerOnline\Publicrelations\Domain\Model\Link $link
     * @return void
     */
    public function addLink($link)
    {
        $this->links->attach($link);
    }

    /**
     * Removes a
     *
     * @param \BucheggerOnline\Publicrelations\Domain\Model\Link $linkToRemove The Link to be removed
     * @return void
     */
    public function removeLink($linkToRemove)
    {
        $this->links->detach($linkToRemove);
    }

    /**
     * Returns the links
     *
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BucheggerOnline\Publicrelations\Domain\Model\Link> links
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
     * Adds a
     *
     * @param \BucheggerOnline\Publicrelations\Domain\Model\Contact $contact
     * @return void
     */
    public function addContact($contact)
    {
        $this->contacts->attach($contact);
    }

    /**
     * Removes a
     *
     * @param \BucheggerOnline\Publicrelations\Domain\Model\Contact $contactToRemove The Contact to be removed
     * @return void
     */
    public function removeContact($contactToRemove)
    {
        $this->contacts->detach($contactToRemove);
    }

    /**
     * Returns the contacts
     *
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BucheggerOnline\Publicrelations\Domain\Model\Contact> contacts
     */
    public function getContacts()
    {
        return $this->contacts;
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
     * Adds a
     *
     * @param \BucheggerOnline\Publicrelations\Domain\Model\MediaGroup $mediagroup
     * @return void
     */
    public function addMediagroup($mediagroup)
    {
        $this->mediagroups->attach($mediagroup);
    }

    /**
     * Removes a
     *
     * @param \BucheggerOnline\Publicrelations\Domain\Model\MediaGroup $mediagroupToRemove The MediaGroup to be removed
     * @return void
     */
    public function removeMediagroup($mediagroupToRemove)
    {
        $this->mediagroups->detach($mediagroupToRemove);
    }

    /**
     * Returns the mediagroups
     *
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BucheggerOnline\Publicrelations\Domain\Model\MediaGroup> mediagroups
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
     * Returns the alsoKnownAs
     *
     * @return string $alsoKnownAs
     */
    public function getAlsoKnownAs()
    {
        return $this->alsoKnownAs;
    }

    /**
     * Sets the alsoKnownAs
     *
     * @param string $alsoKnownAs
     * @return void
     */
    public function setAlsoKnownAs($alsoKnownAs)
    {
        $this->alsoKnownAs = $alsoKnownAs;
    }

    /**
     * Returns the shortinfo
     *
     * @return string $shortinfo
     */
    public function getShortinfo()
    {
        return $this->shortinfo;
    }

    /**
     * Sets the shortinfo
     *
     * @param string $shortinfo
     * @return void
     */
    public function setShortinfo($shortinfo)
    {
        $this->shortinfo = $shortinfo;
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
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage
     */
    public function getAccessRights()
    {
        return $this->accessRights;
    }

    /**
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage $accessRights
     */
    public function setAccessRights($accessRights)
    {
        $this->accessRights = $accessRights;
    }

    /**
     * @param \BucheggerOnline\Publicrelations\Domain\Model\AccessClient $accessRight
     */
    public function addAccessRight(\BucheggerOnline\Publicrelations\Domain\Model\AccessClient $accessRight)
    {
        $this->accessRights->attach($accessRight);
    }

    /**
     * @param \BucheggerOnline\Publicrelations\Domain\Model\AccessClient $accessRight
     */
    public function removeAccessRight(\BucheggerOnline\Publicrelations\Domain\Model\AccessClient $accessRight)
    {
        $this->accessRights->detach($accessRight);
    }
}
