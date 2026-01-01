<?php
namespace BucheggerOnline\Publicrelations\Domain\Model;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\Generic\LazyLoadingProxy;

use BucheggerOnline\Publicrelations\Domain\Repository\SysCategoryRepository;


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
 * SysCategory
 */
class SysCategory extends AbstractEntity
{

    /**
     * @var string
     */
    protected $title;

    /**
     * icon
     *
     * @var \TYPO3\CMS\Extbase\Domain\Model\FileReference
     * @TYPO3\CMS\Extbase\Annotation\ORM\Cascade("remove")
     */
    protected $icon;

    /**
     * svg
     *
     * @var string
     */
    protected $svg = '';

    /**
     * plural
     *
     * @var string
     */
    protected $plural = '';

    /**
     * cssClass
     *
     * @var string
     */
    protected $cssClass = '';

    /**
     * @var \BucheggerOnline\Publicrelations\Domain\Model\SysCategory
     */
    protected $parentcategory;

    /**
     * schedule
     *
     * @var bool
     */
    protected $schedule = false;

    /**
     * theaterevent
     *
     * @var bool
     */
    protected $theaterevent = false;

    /**
     * client
     *
     * @var \BucheggerOnline\Publicrelations\Domain\Model\Client
     */
    protected $client;

    /**
     * Get category title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set category title
     *
     * @param string $title title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * Returns the icon
     *
     * @return \TYPO3\CMS\Extbase\Domain\Model\FileReference $icon
     */
    public function getIcon()
    {
        return $this->icon;
    }

    /**
     * Sets the icon
     *
     * @return void
     */
    public function setIcon(\TYPO3\CMS\Extbase\Domain\Model\FileReference $icon)
    {
        $this->icon = $icon;
    }

    /**
     * Returns the svg
     *
     * @return string $svg
     */
    public function getSvg()
    {
        return $this->svg;
    }

    /**
     * Sets the svg
     *
     * @param string $svg
     * @return void
     */
    public function setSvg($svg)
    {
        $this->svg = $svg;
    }

    /**
     * Returns the plural
     *
     * @return string $plural
     */
    public function getPlural()
    {
        return $this->plural;
    }

    /**
     * Sets the plural
     *
     * @param string $plural
     * @return void
     */
    public function setPlural($plural)
    {
        $this->plural = $plural;
    }

    /**
     * Returns the cssClass
     *
     * @return string $cssClass
     */
    public function getCssClass()
    {
        return $this->cssClass;
    }

    /**
     * Sets the cssClass
     *
     * @param string $cssClass
     * @return void
     */
    public function setCssClass($cssClass)
    {
        $this->cssClass = $cssClass;
    }

    /**
     * Get parent category
     *
     * @return \BucheggerOnline\Publicrelations\Domain\Model\SysCategory
     */
    public function getParentcategory()
    {
        return $this->parentcategory instanceof LazyLoadingProxy ? $this->parentcategory->_loadRealInstance() : $this->parentcategory;
    }

    /**
     * Set parent category
     *
     * @param \BucheggerOnline\Publicrelations\Domain\Model\SysCategory $category parent category
     */
    public function setParentcategory(self $category)
    {
        $this->parentcategory = $category;
    }

    /**
     * Get child categories
     *
     * @return \BucheggerOnline\Publicrelations\Domain\Model\SysCategory
     */
    public function getChilds()
    {

        return GeneralUtility::makeInstance(SysCategoryRepository::class)->findByParentUid($this->getUid());

    }

    /**
     * Returns the schedule
     *
     * @return bool $schedule
     */
    public function getSchedule()
    {
        return $this->schedule;
    }

    /**
     * Sets the schedule
     *
     * @param bool $schedule
     * @return void
     */
    public function setSchedule($schedule)
    {
        $this->schedule = $schedule;
    }

    /**
     * Returns the boolean state of schedule
     *
     * @return bool
     */
    public function isSchedule()
    {
        return $this->schedule;
    }

    /**
     * Returns the theaterevent
     *
     * @return bool $theaterevent
     */
    public function getTheaterevent()
    {
        return $this->theaterevent;
    }

    /**
     * Sets the theaterevent
     *
     * @param bool $theaterevent
     * @return void
     */
    public function setTheaterevent($theaterevent)
    {
        $this->theaterevent = $theaterevent;
    }

    /**
     * Returns the boolean state of theaterevent
     *
     * @return bool
     */
    public function isTheaterevent()
    {
        return $this->theaterevent;
    }

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
}
