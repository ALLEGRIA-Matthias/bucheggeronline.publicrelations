<?php
namespace BucheggerOnline\Publicrelations\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Annotation\ORM\Lazy;

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
 * News
 */
class News extends AbstractEntity
{

    /**
     * date
     *
     * @var \DateTime
     *
     */
    protected $date;

    /**
     * retentionDate
     *
     * @var \DateTime
     */
    protected $retentionDate;

    /**
     * retentionInfo
     *
     * @var string
     *
     */
    protected $retentionInfo = '';

    /**
     * title
     *
     * @var string
     *
     */
    protected $title = '';

    /**
     * text
     *
     * @var string
     */
    protected $text = '';

    /**
     * client
     *
     * @var \BucheggerOnline\Publicrelations\Domain\Model\Client
     */
    protected $client;

    /**
     * contentElements
     *
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BucheggerOnline\Publicrelations\Domain\Model\TtContent>
     * @TYPO3\CMS\Extbase\Annotation\ORM\Cascade("remove")
     * @TYPO3\CMS\Extbase\Annotation\ORM\Lazy
     */
    protected $contentElements;

    /**
     * media
     *
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\TYPO3\CMS\Extbase\Domain\Model\FileReference>
     * @TYPO3\CMS\Extbase\Annotation\ORM\Cascade("remove")
     */
    protected $media;

    /**
     * links
     *
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BucheggerOnline\Publicrelations\Domain\Model\Link>
     * @TYPO3\CMS\Extbase\Annotation\ORM\Cascade("remove")
     * @TYPO3\CMS\Extbase\Annotation\ORM\Lazy
     */
    protected $links;

    /**
     * contacts
     *
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BucheggerOnline\Publicrelations\Domain\Model\Contact>
     * @TYPO3\CMS\Extbase\Annotation\ORM\Cascade("remove")
     * @TYPO3\CMS\Extbase\Annotation\ORM\Lazy
     */
    protected $contacts;

    /**
     * campaigns
     *
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BucheggerOnline\Publicrelations\Domain\Model\Campaign>
     * @TYPO3\CMS\Extbase\Annotation\ORM\Lazy
     */
    protected $campaigns;

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
        $this->contentElements = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
        $this->media = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
        $this->links = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
        $this->contacts = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
        $this->campaigns = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
    }

    /**
     * Returns the date
     *
     * @return \DateTimeInterface $date
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Sets the date
     *
     * @return void
     */
    public function setDate(\DateTimeInterface $date)
    {
        $this->date = $date;
    }

    /**
     * Returns the retentionDate
     *
     * @return \DateTimeInterface $retentionDate
     */
    public function getRetentionDate()
    {
        return $this->retentionDate;
    }

    /**
     * Sets the retentionDate
     *
     * @return void
     */
    public function setRetentionDate(\DateTimeInterface $retentionDate)
    {
        $this->retentionDate = $retentionDate;
    }

    /**
     * Returns the retentionInfo
     *
     * @return string $retentionInfo
     */
    public function getRetentionInfo()
    {
        return $this->retentionInfo;
    }

    /**
     * Sets the retentionInfo
     *
     * @param string $retentionInfo
     * @return void
     */
    public function setRetentionInfo($retentionInfo)
    {
        $this->retentionInfo = $retentionInfo;
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
     * Returns the text
     *
     * @return string $text
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * Sets the text
     *
     * @param string $text
     * @return void
     */
    public function setText($text)
    {
        $this->text = $text;
    }

    /**
     * Returns the textToMail
     *
     * @return string $textToMail
     */
    public function getTextToMail()
    {
        $output = str_replace('src="', 'src="https://www.allegria.at/', $this->getText());
        $output = str_replace('https://www.allegria.at/https://www.allegria.at/', 'https://www.allegria.at/', $output);
        $output = str_replace('<h4>', '<h3>', $output);
        $output = str_replace('</h4>', '</h3>', $output);
        $output = str_replace('<hr />', '<hr style="border-top: 1px solid #7a7a7a;" />', $output);

        return str_replace('class="text-center"', 'style="text-align:center;"', $output);
    }

    /**
     * Adds a
     *
     * @param \BucheggerOnline\Publicrelations\Domain\Model\TtContent $contentElement
     * @return void
     */
    public function addContentElement($contentElement)
    {
        $this->contentElements->attach($contentElement);
    }

    /**
     * Removes a
     *
     * @param \BucheggerOnline\Publicrelations\Domain\Model\TtContent $contentElementToRemove The  to be removed
     * @return void
     */
    public function removeContentElement($contentElementToRemove)
    {
        $this->contentElements->detach($contentElementToRemove);
    }

    /**
     * Returns the contentElements
     *
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BucheggerOnline\Publicrelations\Domain\Model\TtContent> $contentElements
     */
    public function getContentElements()
    {
        return $this->contentElements;
    }

    /**
     * Sets the contentElements
     *
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BucheggerOnline\Publicrelations\Domain\Model\TtContent> $contentElements
     * @return void
     */
    public function setContentElements(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $contentElements)
    {
        $this->contentElements = $contentElements;
    }

    /**
     * Adds a FileReference
     *
     * @return void
     */
    public function addMedium(\TYPO3\CMS\Extbase\Domain\Model\FileReference $medium)
    {
        $this->media->attach($medium);
    }

    /**
     * Removes a FileReference
     *
     * @param \TYPO3\CMS\Extbase\Domain\Model\FileReference $mediumToRemove The FileReference to be removed
     * @return void
     */
    public function removeMedium(\TYPO3\CMS\Extbase\Domain\Model\FileReference $mediumToRemove)
    {
        $this->media->detach($mediumToRemove);
    }

    /**
     * Returns the media
     *
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\TYPO3\CMS\Extbase\Domain\Model\FileReference> $media
     */
    public function getMedia()
    {
        return $this->media;
    }

    /**
     * Sets the media
     *
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\TYPO3\CMS\Extbase\Domain\Model\FileReference> $media
     * @return void
     */
    public function setMedia(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $media)
    {
        $this->media = $media;
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
     * Returns the contacts based on a priority:
     * 1. Contacts directly assigned.
     * 2. Contacts from the single associated campaign (if exactly one exists).
     * 3. Contacts from the associated client.
     *
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BucheggerOnline\Publicrelations\Domain\Model\Contact>
     */
    public function getContacts()
    {
        // 1. Priority: Direct contacts on this object
        if ($this->contacts && $this->contacts->count() > 0) {
            return $this->contacts;
        }

        // 2. Priority: Contacts from a single associated campaign
        $campaigns = $this->getCampaigns();
        if ($campaigns && $campaigns->count() === 1) {
            $campaign = $campaigns->current();
            if ($campaign) {
                return $campaign->getContacts();
            }
        }

        // 3. Priority: Contacts from the associated client
        $client = $this->getClient();
        if ($client) {
            return $client->getContacts();
        }

        // Fallback: Return a new, empty ObjectStorage to satisfy the return type
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

    /**
     * Returns the client
     *
     * @return \BucheggerOnline\Publicrelations\Domain\Model\Client $client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * Sets the client
     *
     * @return void
     */
    public function setClient(\BucheggerOnline\Publicrelations\Domain\Model\Client $client)
    {
        $this->client = $client;
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
}
