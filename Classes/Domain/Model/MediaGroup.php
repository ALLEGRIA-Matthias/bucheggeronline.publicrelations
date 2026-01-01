<?php
namespace BucheggerOnline\Publicrelations\Domain\Model;


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
 * MediaGroup
 */
class MediaGroup extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
{

    /**
     * title
     * 
     * @var string
     */
    protected $title = '';

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
        $this->links = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
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
}
