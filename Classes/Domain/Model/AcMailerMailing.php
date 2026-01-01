<?php

namespace BucheggerOnline\Publicrelations\Domain\Model;

use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

use Allegria\AcMailer\Domain\Model\Mailing as OriginalMailing;
use Allegria\AcMailer\Domain\Model\Receiver;
use Allegria\AcMailer\Domain\Model\Content;


/**
 * This model represents a tag for anything.
 */
class AcMailerMailing extends OriginalMailing
{
    /**
     * client
     *
     * @var \BucheggerOnline\Publicrelations\Domain\Model\Client
     */
    protected $client;
    
    /**
     * client
     *
     * @var \BucheggerOnline\Publicrelations\Domain\Model\Campaign
     */
    protected $project;

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
     * Returns the project
     *
     * @return \BucheggerOnline\Publicrelations\Domain\Model\Campaign $project
     */
    public function getProject()
    {
        return $this->project;
    }

    /**
     * Sets the project
     *
     * @return void
     */
    public function setProject(\BucheggerOnline\Publicrelations\Domain\Model\Campaign $project)
    {
        $this->project = $project;
    }

}
