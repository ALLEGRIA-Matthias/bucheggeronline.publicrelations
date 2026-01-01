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
 *  (c) 2020 Matthias Buchegger <matthias@buchegger.online>, Multiattachementsagentur Matthias Buchegger
 *
 ***/
/**
 * News
 */
class Content extends AbstractEntity
{

    /**
     * @var \DateTime
     */
    protected $crdate;

    /**
     * @var \DateTime
     */
    protected $tstamp;

    /**
     * @var int
     */
    protected $cruserId;

    /**
     * type
     *
     * @var int
     *
     */
    protected $type = 0;

    /**
     * content
     *
     * @var string
     *
     */
    protected $content = '';

    /**
     * padding
     *
     * @var string
     *
     */
    protected $padding = '';

    /**
     * color
     *
     * @var string
     *
     */
    protected $color = '';

    /**
     * bgcolor
     *
     * @var string
     *
     */
    protected $bgcolor = '';

    /**
     * contentElement
     *
     * @var \BucheggerOnline\Publicrelations\Domain\Model\TtContent
     */
    protected $contentElement;

    /**
     * image
     *
     * @var \TYPO3\CMS\Extbase\Domain\Model\FileReference
     * @TYPO3\CMS\Extbase\Annotation\ORM\Cascade("remove")
     */
    protected $image;

    /**
     * imageFullWidth
     *
     * @var bool
     */
    protected $imageFullWidth = false;

    /**
     * media
     *
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\TYPO3\CMS\Extbase\Domain\Model\FileReference>
     * @TYPO3\CMS\Extbase\Annotation\ORM\Cascade("remove")
     */
    protected $media;

    /**
     * news
     *
     * @var \BucheggerOnline\Publicrelations\Domain\Model\News
     */
    protected $news;

    /**
     * newsMedia
     *
     * @var bool
     */
    protected $newsMedia = false;

    /**
     * event
     *
     * @var \BucheggerOnline\Publicrelations\Domain\Model\Event
     */
    protected $event;

    /**
     * eventTitle
     *
     * @var string
     *
     */
    protected $eventTitle = '';

    /**
     * eventDate
     *
     * @var string
     *
     */
    protected $eventDate = '';

    /**
     * eventLocation
     *
     * @var string
     *
     */
    protected $eventLocation = '';

    /**
     * eventDescription
     *
     * @var string
     *
     */
    protected $eventDescription = '';

    /**
     * eventLink
     *
     * @var bool
     */
    protected $eventLink = false;

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
        $this->media = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
    }

    /**
     * Get creation date
     *
     * @return int
     */
    public function getCrdate()
    {
        return $this->crdate;
    }

    /**
     * Set creation date
     *
     * @param int $crdate
     */
    public function setCrdate($crdate)
    {
        $this->crdate = $crdate;
    }

    /**
     * Get timestamp
     *
     * @return \DateTime
     */
    public function getTstamp()
    {
        return $this->tstamp;
    }

    /**
     * Set time stamp
     *
     * @param \DateTimeInterface $tstamp time stamp
     */
    public function setTstamp($tstamp)
    {
        $this->tstamp = $tstamp;
    }

    /**
     * Get id of creator user
     *
     * @return int
     */
    public function getCruserId()
    {
        return $this->cruserId;
    }

    /**
     * Set cruser id
     *
     * @param int $cruserId id of creator user
     */
    public function setCruserId($cruserId)
    {
        $this->cruserId = $cruserId;
    }

    /**
     * Returns the type
     *
     * @return int type
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Sets the type
     *
     * @param bool $type
     * @return void
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * Returns the content
     *
     * @return string $content
     */
    public function getContent()
    {
      $output = str_replace('src="', 'src="https://www.allegria.at/', $this->content);
      $output = str_replace('https://www.allegria.at/https://www.allegria.at/', 'https://www.allegria.at/', $output);
      $output = str_replace('<h4>', '<h3>', $output);
      $output = str_replace('</h4>', '</h3>', $output);
      $output = str_replace('<hr />', '<hr style="border-top: 1px solid #7a7a7a;" />', $output);

      return str_replace('class="text-center"', 'style="text-align:center;"', $output);
        // return $this->content;
    }

    /**
     * Sets the content
     *
     * @param string $content
     * @return void
     */
    public function setContent($content)
    {

        $this->content = $content;
    }

    /**
     * Returns the padding
     *
     * @return string $padding
     */
    public function getPadding()
    {
      return $this->padding;
    }

    /**
     * Sets the padding
     *
     * @param string $padding
     * @return void
     */
    public function setPadding($padding)
    {
        $this->padding = $padding;
    }

    /**
     * Returns the color
     *
     * @return string $color
     */
    public function getColor()
    {
      return $this->color;
    }

    /**
     * Sets the color
     *
     * @param string $color
     * @return void
     */
    public function setColor($color)
    {
        $this->color = $color;
    }

    /**
     * Returns the bgcolor
     *
     * @return string $bgcolor
     */
    public function getBgcolor()
    {
      return $this->bgcolor;
    }

    /**
     * Sets the bgcolor
     *
     * @param string $bgcolor
     * @return void
     */
    public function setBgcolor($bgcolor)
    {
        $this->bgcolor = $bgcolor;
    }

    /**
     * Returns the contentElement
     *
     * @return \BucheggerOnline\Publicrelations\Domain\Model\TtContent $contentElement
     */
    public function getContentElement()
    {
        return $this->contentElement;
    }

    /**
     * Sets the contentElement
     *
     * @param \BucheggerOnline\Publicrelations\Domain\Model\TtContent $contentElement
     * @return void
     */
    public function setContentElement(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $contentElement)
    {
        $this->contentElement = $contentElement;
    }

    /**
     * Returns the image
     *
     * @return \TYPO3\CMS\Extbase\Domain\Model\FileReference $image
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * Sets the image
     *
     * @return void
     */
    public function setImage(\TYPO3\CMS\Extbase\Domain\Model\FileReference $image)
    {
        $this->image = $image;
    }

    /**
     * Returns the imageFullWidth
     *
     * @return bool $imageFullWidth
     */
    public function getImageFullWidth()
    {
        return $this->imageFullWidth;
    }

    /**
     * Sets the imageFullWidth
     *
     * @param bool $imageFullWidth
     * @return void
     */
    public function setImageFullWidth($imageFullWidth)
    {
        $this->imageFullWidth = $imageFullWidth;
    }

    /**
     * Returns the boolean state of imageFullWidth
     *
     * @return bool
     */
    public function isImageFullWidth()
    {
        return $this->imageFullWidth;
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
     * Returns the news
     *
     * @return \BucheggerOnline\Publicrelations\Domain\Model\News $news
     */
    public function getNews()
    {
        return $this->news;
    }

    /**
     * Sets the news
     *
     * @return void
     */
    public function setNews(\BucheggerOnline\Publicrelations\Domain\Model\News $news)
    {
        $this->news = $news;
    }

    /**
     * Returns the newsMedia
     *
     * @return bool $newsMedia
     */
    public function getNewsMedia()
    {
        return $this->newsMedia;
    }

    /**
     * Sets the newsMedia
     *
     * @param bool $newsMedia
     * @return void
     */
    public function setNewsMedia($newsMedia)
    {
        $this->newsMedia = $newsMedia;
    }

    /**
     * Returns the boolean state of newsMedia
     *
     * @return bool
     */
    public function isNewsMedia()
    {
        return $this->newsMedia;
    }

    /**
     * Returns the event
     *
     * @return \BucheggerOnline\Publicrelations\Domain\Model\Event $event
     */
    public function getEvent()
    {
        return $this->event;
    }

    /**
     * Sets the event
     *
     * @return void
     */
    public function setEvent(\BucheggerOnline\Publicrelations\Domain\Model\Event $event)
    {
        $this->event = $event;
    }

    /**
     * Returns the eventTitle
     *
     * @return string $eventTitle
     */
    public function getEventTitle()
    {
      return $this->eventTitle;
    }

    /**
     * Sets the eventTitle
     *
     * @param string $eventTitle
     * @return void
     */
    public function setEventTitle($eventTitle)
    {

        $this->eventTitle = $eventTitle;
    }

    /**
     * Returns the eventDate
     *
     * @return string $eventDate
     */
    public function getEventDate()
    {
      return $this->eventDate;
    }

    /**
     * Sets the eventDate
     *
     * @param string $eventDate
     * @return void
     */
    public function setEventDate($eventDate)
    {

        $this->eventDate = $eventDate;
    }

    /**
     * Returns the eventLocation
     *
     * @return string $eventLocation
     */
    public function getEventLocation()
    {
      return $this->eventLocation;
    }

    /**
     * Sets the eventLocation
     *
     * @param string $eventLocation
     * @return void
     */
    public function setEventLocation($eventLocation)
    {

        $this->eventLocation = $eventLocation;
    }

    /**
     * Returns the eventDescription
     *
     * @return string $eventDescription
     */
    public function getEventDescription()
    {
      return $this->eventDescription;
    }

    /**
     * Sets the eventDescription
     *
     * @param string $eventDescription
     * @return void
     */
    public function setEventDescription($eventDescription)
    {

        $this->eventDescription = $eventDescription;
    }

    /**
     * Returns the eventLink
     *
     * @return bool $eventLink
     */
    public function getEventLink()
    {
        return $this->eventLink;
    }

    /**
     * Sets the eventLink
     *
     * @param bool $eventLink
     * @return void
     */
    public function setEventLink($eventLink)
    {
        $this->eventLink = $eventLink;
    }

    /**
     * Returns the boolean state of eventLink
     *
     * @return bool
     */
    public function isEventLink()
    {
        return $this->eventLink;
    }
}
