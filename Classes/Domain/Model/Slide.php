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
 * Slide
 */
class Slide extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
{

    /**
     * type
     *
     * @var int
     */
    protected $type = 0;

    /**
     * image
     *
     * @var \TYPO3\CMS\Extbase\Domain\Model\FileReference
     *
     * @TYPO3\CMS\Extbase\Annotation\ORM\Cascade("remove")
     */
    protected $image;

    /**
     * titleOverwrite
     *
     * @var string
     */
    protected $titleOverwrite = '';

    /**
     * subtitleOverwrite
     *
     * @var string
     */
    protected $subtitleOverwrite = '';

    /**
     * worksOverwrite
     *
     * @var string
     */
    protected $worksOverwrite = '';

    /**
     * noWorks
     *
     * @var bool
     */
    protected $noWorks = false;

    /**
     * logoOverwrite
     *
     * @var \TYPO3\CMS\Extbase\Domain\Model\FileReference
     * @TYPO3\CMS\Extbase\Annotation\ORM\Cascade("remove")
     */
    protected $logoOverwrite;

    /**
     * works
     *
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\TYPO3\CMS\Extbase\Domain\Model\Category>
     * @TYPO3\CMS\Extbase\Annotation\ORM\Lazy
     */
    protected $works;

    /**
     * buttons
     *
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BucheggerOnline\Publicrelations\Domain\Model\Link>
     * @TYPO3\CMS\Extbase\Annotation\ORM\Cascade("remove")
     * @TYPO3\CMS\Extbase\Annotation\ORM\Lazy
     */
    protected $buttons;

    /**
     * client
     *
     * @var \BucheggerOnline\Publicrelations\Domain\Model\Client
     */
    protected $client;

    /**
     * campaign
     *
     * @var \BucheggerOnline\Publicrelations\Domain\Model\Campaign
     */
    protected $campaign;

    /**
     * news
     *
     * @var \BucheggerOnline\Publicrelations\Domain\Model\News
     */
    protected $news;

    /**
     * manual
     *
     * @var string
     */
    protected $manual = '';

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
        $this->works = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
        $this->buttons = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
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
     * Returns the titleOverwrite
     *
     * @return string $titleOverwrite
     */
    public function getTitleOverwrite()
    {
        return $this->titleOverwrite;
    }

    /**
     * Sets the titleOverwrite
     *
     * @param string $titleOverwrite
     * @return void
     */
    public function setTitleOverwrite($titleOverwrite)
    {
        $this->titleOverwrite = $titleOverwrite;
    }

    /**
     * Returns the title
     *
     * @return string $title
     */
    public function getTitle()
    {
        if ($this->getType()==1) {
          $title = strtoupper($this->getClient()->getName());
        } elseif ($this->getType()==2) {
          $title = strtoupper($this->getCampaign()->getTitle());
        } elseif ($this->getType()==3) {
          $title = strtoupper($this->getNews()->getTitle());
        } else {
          $title = '';
        }

        return ($this->getTitleOverwrite()) ? strtoupper($this->getTitleOverwrite()) : $title;
    }

    /**
     * Returns the subtitleOverwrite
     *
     * @return string $subtitleOverwrite
     */
    public function getSubtitleOverwrite()
    {
        return $this->subtitleOverwrite;
    }

    /**
     * Sets the subtitleOverwrite
     *
     * @param string $subtitleOverwrite
     * @return void
     */
    public function setSubtitleOverwrite($subtitleOverwrite)
    {
        $this->subtitleOverwrite = $subtitleOverwrite;
    }

    /**
     * Returns the subtitle
     *
     * @return string $subtitle
     */
    public function getSubtitle()
    {
        if ($this->getType()==1) {
          $locationinfo = '';
        } elseif ($this->getType()==2) {
          $locationinfo = ($this->getCampaign()->getLocation()) ? ' in '.$this->getCampaign()->getLocation()->getCity() : '';
        } else {
          $locationinfo = '';
        }

        return $this->getSubtitleOverwrite() ?? $locationinfo;
    }

    /**
     * Returns the worksOverwrite
     *
     * @return string $worksOverwrite
     */
    public function getWorksOverwrite()
    {
        return $this->worksOverwrite;
    }

    /**
     * Sets the worksOverwrite
     *
     * @param string $worksOverwrite
     * @return void
     */
    public function setWorksOverwrite($worksOverwrite)
    {
        $this->worksOverwrite = $worksOverwrite;
    }

    /**
     * Returns the worksOutput
     *
     * @return string $worksOutput
     */
    public function getWorksOutput()
    {
        if ($this->getWorks()->count() && $this->getWorksOverwrite()) {

          foreach ($this->getWorks() as $work)
          $worksOutput[] = $work->getTitle();

          $worksOutput = array_filter($worksOutput);
          $worksOverwrites = array_map('trim', explode("\n",$this->getWorksOverwrite()));
          $worksOverwrites = array_filter($worksOverwrites);

          $output = array_replace($worksOutput, $worksOverwrites);

        } elseif ($this->getWorks()->count()) {

          foreach ($this->getWorks() as $work)
          $output[] = $work->getTitle();

        } elseif ($this->getTypesOverwrite()) {

          $output = array_map('trim', explode("\n",$this->getWorksOverwrite()));

        } else {

          $output = NULL;

        }

        return $output;
    }

    /**
     * Returns the noWorks
     *
     * @return bool $noWorks
     */
    public function getNoWorks()
    {
        return $this->noWorks;
    }

    /**
     * Sets the noWorks
     *
     * @param bool $noWorks
     * @return void
     */
    public function setNoWorks($noWorks)
    {
        $this->noWorks = $noWorks;
    }

    /**
     * Returns the boolean state of noWorks
     *
     * @return bool
     */
    public function isNoWorks()
    {
        return $this->noWorks;
    }

    /**
     * Returns the logo
     *
     * @return \TYPO3\CMS\Extbase\Domain\Model\FileReference $logo
     */
    public function getLogo()
    {
        if ($this->getType()==1) {
          $logo = $this->getClient()->getLogo();
        } elseif ($this->getType()==2) {
          $logo = $this->getCampaign()->getClient()->getLogo();
        } else {
          $logo = $this->getLogoOverwrite();
        }

        return $this->getLogoOverwrite() ?? $logo;
    }

    /**
     * Returns the logoOverwrite
     *
     * @return \TYPO3\CMS\Extbase\Domain\Model\FileReference $logoOverwrite
     */
    public function getLogoOverwrite()
    {
        return $this->logoOverwrite;
    }

    /**
     * Sets the logoOverwrite
     *
     * @return void
     */
    public function setLogoOverwrite(\TYPO3\CMS\Extbase\Domain\Model\FileReference $logoOverwrite)
    {
        $this->logoOverwrite = $logoOverwrite;
    }

    /**
     * Adds a Category
     *
     * @return void
     */
    public function addWork(\TYPO3\CMS\Extbase\Domain\Model\Category $work)
    {
        $this->works->attach($work);
    }

    /**
     * Removes a Category
     *
     * @param \TYPO3\CMS\Extbase\Domain\Model\Category $workToRemove The Category to be removed
     * @return void
     */
    public function removeWork(\TYPO3\CMS\Extbase\Domain\Model\Category $workToRemove)
    {
        $this->works->detach($workToRemove);
    }

    /**
     * Returns the works
     *
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\TYPO3\CMS\Extbase\Domain\Model\Category> $works
     */
    public function getWorks()
    {
        return $this->works;
    }

    /**
     * Sets the works
     *
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\TYPO3\CMS\Extbase\Domain\Model\Category> $works
     * @return void
     */
    public function setWorks(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $works)
    {
        $this->works = $works;
    }

    /**
     * Adds a Link
     *
     * @return void
     */
    public function addButton(\BucheggerOnline\Publicrelations\Domain\Model\Link $button)
    {
        $this->buttons->attach($button);
    }

    /**
     * Removes a Link
     *
     * @param \BucheggerOnline\Publicrelations\Domain\Model\Link $buttonToRemove The Link to be removed
     * @return void
     */
    public function removeButton(\BucheggerOnline\Publicrelations\Domain\Model\Link $buttonToRemove)
    {
        $this->buttons->detach($buttonToRemove);
    }

    /**
     * Returns the buttons
     *
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BucheggerOnline\Publicrelations\Domain\Model\Link> $buttons
     */
    public function getButtons()
    {
        return $this->buttons;
    }

    /**
     * Sets the buttons
     *
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BucheggerOnline\Publicrelations\Domain\Model\Link> $buttons
     * @return void
     */
    public function setButtons(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $buttons)
    {
        $this->buttons = $buttons;
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
     * Returns the campaign
     *
     * @return \BucheggerOnline\Publicrelations\Domain\Model\Campaign $campaign
     */
    public function getCampaign()
    {
        return $this->campaign;
    }

    /**
     * Sets the campaign
     *
     * @return void
     */
    public function setCampaign(\BucheggerOnline\Publicrelations\Domain\Model\Campaign $campaign)
    {
        $this->campaign = $campaign;
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
     * Returns the manual
     *
     * @return string $manual
     */
    public function getManual()
    {
        return $this->manual;
    }

    /**
     * Sets the manual
     *
     * @param string $manual
     * @return void
     */
    public function setManual($manual)
    {
        $this->manual = $manual;
    }
}
