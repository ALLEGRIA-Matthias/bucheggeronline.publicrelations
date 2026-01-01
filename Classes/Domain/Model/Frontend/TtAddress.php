<?php
namespace BucheggerOnline\Publicrelations\Domain\Model\Frontend;

use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use BucheggerOnline\Publicrelations\Domain\Model\Log;
use BucheggerOnline\Publicrelations\Domain\Model\Frontend\TtAddress;
use BucheggerOnline\Publicrelations\Domain\Model\Tag;
use BucheggerOnline\Publicrelations\Domain\Model\ContactGroup;
use BucheggerOnline\Publicrelations\Domain\Model\SocialProfile;
use BucheggerOnline\Publicrelations\Domain\Model\SysCategory;


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
 * TtAddress
 */
class TtAddress extends \FriendsOfTYPO3\TtAddress\Domain\Model\Address
{


    /**
     * client
     *
     * @var \BucheggerOnline\Publicrelations\Domain\Model\Client
     */
    protected $client;

    /**
     * contactTypes
     *
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BucheggerOnline\Publicrelations\Domain\Model\SysCategory>
     */
    protected $contactTypes;

    protected ?string $workingFor = null;

    protected bool $personally = false;

    protected string $specialTitle = '';

    protected bool $mailingExclude = false;

    protected bool $mailingNoHtml = false;

    protected ?\DateTime $age = null;

    protected ?int $crdate = null;

    protected ?int $tstamp = null;

    protected ?int $cruserId = null;

    protected ?int $pid = null;

    /**
     * @var ObjectStorage<Log>
     * @TYPO3\CMS\Extbase\Annotation\ORM\Lazy
     */
    protected ObjectStorage $logs;

    protected bool $valid = false;

    /**
     * @var ObjectStorage<TtAddress>
     * @TYPO3\CMS\Extbase\Annotation\ORM\Lazy
     */
    protected ObjectStorage $duplicates;

    protected ?int $copyToPid = null;

    /**
     * @var ObjectStorage<Tag>
     */
    protected $tags;

    /**
     * @var ObjectStorage<ContactGroup>
     */
    protected $groups;

    /**
     * @var ObjectStorage<SocialProfile>
     */
    protected $socialProfiles;

    public function __construct()
    {
        parent::__construct();

        $this->logs = new ObjectStorage();
        $this->duplicates = new ObjectStorage();
        $this->contactTypes = new ObjectStorage();
        $this->tags = new ObjectStorage();
        $this->groups = new ObjectStorage();
        $this->socialProfiles = new ObjectStorage();
    }

    /**
     * Returns the client
     *
     * @return \BucheggerOnline\Publicrelations\Domain\Model\Client client
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
     * Adds a ContactType
     *
     * @return void
     */
    public function addContactType(\BucheggerOnline\Publicrelations\Domain\Model\SysCategory $contactType)
    {
        $this->contactTypes->attach($contactType);
    }

    /**
     * Removes a Category
     *
     * @param \BucheggerOnline\Publicrelations\Domain\Model\SysCategory $contactTypeToRemove The ContactType to be removed
     * @return void
     */
    public function removeContactType(\BucheggerOnline\Publicrelations\Domain\Model\SysCategory $contactTypeToRemove)
    {
        $this->contactTypes->detach($contactTypeToRemove);
    }

    /**
     * Returns the contactTypes
     *
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BucheggerOnline\Publicrelations\Domain\Model\SysCategory> contactTypes
     */
    public function getContactTypes()
    {
        return $this->contactTypes;
    }

    /**
     * Sets the contactTypes
     *
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BucheggerOnline\Publicrelations\Domain\Model\SysCategory> $contactTypes
     * @return void
     */
    public function setContactTypes(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $contactTypes)
    {
        $this->contactTypes = $contactTypes;
    }

    /**
     * Getter für "arbeitet für".
     *
     * @return string|null
     */
    public function getWorkingFor(): ?string
    {
        return $this->workingFor;
    }

    /**
     * Setter für "arbeitet für".
     *

    * @param string|null $workingFor
    */
    public function setWorkingFor(?string $workingFor): void
    {
        $this->workingFor = $workingFor;
    }

    /**
     * Returns the personally
     *
     * @return bool $personally
     */
    public function getPersonally()
    {
        return $this->personally;
    }

    /**
     * Sets the personally
     *
     * @param bool $personally
     * @return void
     */
    public function setPersonally($personally)
    {
        $this->personally = $personally;
    }

    /**
     * Returns the boolean state of personally
     *
     * @return bool
     */
    public function isPersonally()
    {
        return $this->personally;
    }

    /**
     * Returns the specialTitle
     *
     * @return string $specialTitle
     */
    public function getSpecialTitle()
    {
        return $this->specialTitle;
    }

    /**
     * Sets the specialTitle
     *
     * @param string $specialTitle
     * @return void
     */
    public function setSpecialTitle($specialTitle)
    {
        $this->specialTitle = $specialTitle;
    }

    /**
     * Returns the salutation
     *
     * @return string $salutation
     */
    public function getSalutation()
    {
        if ($this->getSpecialTitle()) {
            $salutation = $this->getSpecialTitle();
        } else {
            $name = implode(' ', array_filter([
                $this->getFirstNames(),
                $this->getLastName()
            ]));

            switch ($this->getGender()) {
                case 'f':
                    $salutation = 'Sehr geehrte Frau ' . $this->getLastName();
                    break;
                case 'm':
                    $salutation = 'Sehr geehrter Herr ' . $this->getLastName();
                    break;
                case 'v':
                    $salutation = 'Guten Tag ' . $name;
                    break;

                default:
                    $salutation = 'Guten Tag';
                    break;
            }
        }
        return $salutation;
    }

    /**
     * Returns the mailingExclude
     *
     * @return bool $mailingExclude
     */
    public function getMailingExclude()
    {
        return $this->mailingExclude;
    }

    /**
     * Sets the mailingExclude
     *
     * @param bool $mailingExclude
     * @return void
     */
    public function setMailingExclude($mailingExclude)
    {
        $this->mailingExclude = $mailingExclude;
    }

    /**
     * Returns the boolean state of mailingExclude
     *
     * @return bool
     */
    public function isMailingExclude()
    {
        return $this->mailingExclude;
    }

    /**
     * Returns the mailingNoHtml
     *
     * @return bool $mailingNoHtml
     */
    public function getMailingNoHtml()
    {
        return $this->mailingNoHtml;
    }

    /**
     * Sets the mailingNoHtml
     *
     * @param bool $mailingNoHtml
     * @return void
     */
    public function setMailingNoHtml($mailingNoHtml)
    {
        $this->mailingNoHtml = $mailingNoHtml;
    }

    /**
     * Returns the boolean state of mailingNoHtml
     *
     * @return bool
     */
    public function isMailingNoHtml()
    {
        return $this->mailingNoHtml;
    }

    /**
     * Get age
     *
     * @return \DateTime
     */
    public function getAge()
    {
        $now = new \DateTime('now');
        return ($this->getBirthday()) ? $now->diff($this->getBirthday())->y : 0;
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
     * @return int
     */
    public function getTstamp()
    {
        return $this->tstamp;
    }

    /**
     * Set time stamp
     *
     * @param int $tstamp
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
     * Returns the selectLabel
     *
     * @return bool
     */
    public function getSelectLabel()
    {
        if ($this->getCompany() && $this->getFullName()) {
            return $this->getFullName() . ' • ' . $this->getCompany();
        } elseif ($this->getCompany()) {
            return $this->getCompany();
        } else {
            return $this->getFullName();
        }
    }

    /**
     * Returns the selectLabel
     *
     * @return bool
     */
    public function getSortLabel()
    {
        if ($this->getCompany() && $this->getLastName()) {
            return trim($this->getLastName() . ' ' . $this->getFirstName() . ' ' . $this->getMiddleName()) . ' ' . $this->getCompany();
        } elseif ($this->getCompany()) {
            return $this->getCompany();
        } else {
            return trim($this->getLastName() . ' ' . $this->getFirstName() . ' ' . $this->getMiddleName());
        }
    }

    /**
     * Adds a Log
     *
     * @return void
     */
    public function addLog(\BucheggerOnline\Publicrelations\Domain\Model\Log $log)
    {
        $this->logs->attach($log);
    }

    /**
     * Removes a Log
     *
     * @param \BucheggerOnline\Publicrelations\Domain\Model\Log $logToRemove The Log to be removed
     * @return void
     */
    public function removeLog(\BucheggerOnline\Publicrelations\Domain\Model\Log $logToRemove)
    {
        $this->logs->detach($logToRemove);
    }

    /**
     * Returns the logs
     *
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BucheggerOnline\Publicrelations\Domain\Model\Log> $logs
     */
    public function getLogs()
    {
        return $this->logs;
    }

    /**
     * Sets the logs
     *
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BucheggerOnline\Publicrelations\Domain\Model\Log> $logs
     * @return void
     */
    public function setLogs(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $logs)
    {
        $this->logs = $logs;
    }

    /**
     * Adds a duplicate
     *
     * @return void
     */
    public function addDuplicate(\BucheggerOnline\Publicrelations\Domain\Model\TtAddress $duplicate)
    {
        $this->duplicates->attach($duplicate);
    }

    /**
     * Removes a duplicate
     *
     * @param \BucheggerOnline\Publicrelations\Domain\Model\TtAddress $logToRemove The duplicate to be removed
     * @return void
     */
    public function removeDuplicate(\BucheggerOnline\Publicrelations\Domain\Model\TtAddress $duplicateToRemove)
    {
        $this->duplicates->detach($duplicateToRemove);
    }

    /**
     * Returns the duplicates
     *
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BucheggerOnline\Publicrelations\Domain\Model\TtAddress> $duplicates
     */
    public function getDuplicates()
    {
        return $this->duplicates;
    }

    /**
     * Sets the duplicates
     *
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BucheggerOnline\Publicrelations\Domain\Model\TtAddress> $duplicates
     * @return void
     */
    public function setDuplicates(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $duplicates)
    {
        $this->duplicates = $duplicates;
    }

    /**
     * Returns the valid
     *
     * @return bool $valid
     */
    public function getValid()
    {
        return $this->valid;
    }

    /**
     * Sets the valid
     *
     * @param bool $valid
     * @return void
     */
    public function setValid($valid)
    {
        $this->valid = $valid;
    }

    /**
     * Returns the boolean state of valid
     *
     * @return bool
     */
    public function isValid()
    {
        return $this->valid;
    }

    /**
     * Adds a categoryToAdd
     *
     * @param \BucheggerOnline\Publicrelations\Domain\Model\SysCategory $categoryToAdd
     * @return void
     */
    public function addCategory($categoryToAdd)
    {
        $this->categories->attach($categoryToAdd);
    }

    /**
     * Removes a sysCategory
     *
     * @param \BucheggerOnline\Publicrelations\Domain\Model\SysCategory $categoryToRemove The  to be removed
     * @return void
     */
    public function removeCategory($categoryToRemove)
    {
        $this->categories->detach($categoryToRemove);
    }

    /**
     * Get id of creator copyToPid
     *
     * @return int
     */
    public function getCopyToPid()
    {
        return $this->copyToPid;
    }

    /**
     * Set cruser copyToPid
     *
     * @param int $cruserId id of creator copyToPid
     */
    public function setCopyToPid($copyToPid)
    {
        $this->copyToPid = $copyToPid;
    }

    /**
     * Adds a Tag
     *
     * @param Tag $tag
     * @return void
     */
    public function addTag(Tag $tag)
    {
        $this->tags->attach($tag);
    }

    /**
     * Removes a Tag
     *
     * @param Tag $tagToRemove The Tag to be removed
     * @return void
     */
    public function removeTag(Tag $tagToRemove)
    {
        $this->tags->detach($tagToRemove);
    }

    /**
     * Returns the tags
     *
     * @return ObjectStorage<Tag>
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * Sets the tags
     *
     * @param ObjectStorage<Tag> $tags
     * @return void
     */
    public function setTags(ObjectStorage $tags)
    {
        $this->tags = $tags;
    }

    /**
     * Adds a SocialProfile
     *
     * @param SocialProfile $socialProfile
     * @return void
     */
    public function addSocialProfile(SocialProfile $socialProfile)
    {
        $this->socialProfiles->attach($socialProfile);
    }

    /**
     * Removes a SocialProfile
     *
     * @param SocialProfile $socialProfileToRemove The SocialProfile to be removed
     * @return void
     */
    public function removeSocialProfile(SocialProfile $socialProfileToRemove)
    {
        $this->socialProfiles->detach($socialProfileToRemove);
    }

    /**
     * Returns the socialProfiles
     *
     * @return ObjectStorage<SocialProfile>
     */
    public function getSocialProfiles()
    {
        return $this->socialProfiles;
    }

    /**
     * Sets the socialProfiles
     *
     * @param ObjectStorage<SocialProfile> $socialProfiles
     * @return void
     */
    public function setSocialProfiles(ObjectStorage $socialProfiles)
    {
        $this->socialProfiles = $socialProfiles;
    }

    /**
     * Adds a ContactGroup
     *
     * @param ContactGroup $contactGroup
     * @return void
     */
    public function addContactGroup(ContactGroup $contactGroup)
    {
        $this->groups->attach($contactGroup);
    }

    /**
     * Removes a ContactGroup
     *
     * @param ContactGroup $contactGroupToRemove The ContactGroup to be removed
     * @return void
     */
    public function removeContactGroup(ContactGroup $contactGroupToRemove)
    {
        $this->groups->detach($contactGroupToRemove);
    }

    /**
     * Returns the groups
     *
     * @return ObjectStorage<ContactGroup>
     */
    public function getGroups()
    {
        return $this->groups;
    }

    /**
     * Sets the groups
     *
     * @param ObjectStorage<ContactGroup> $groups
     * @return void
     */
    public function setGroups(ObjectStorage $groups)
    {
        $this->groups = $groups;
    }
}
