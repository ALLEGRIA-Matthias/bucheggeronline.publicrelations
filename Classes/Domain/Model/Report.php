<?php
namespace BucheggerOnline\Publicrelations\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;

/**
 * Model Report
 *
 * Hält alle Reporting-Einträge wie Clippings, PR-Berichte, etc.
 */
class Report extends AbstractEntity
{
    /**
     * @var \BucheggerOnline\Publicrelations\Domain\Model\Client
     */
    protected $client;

    /**
     * @var \BucheggerOnline\Publicrelations\Domain\Model\Campaign
     */
    protected $campaign;

    /**
     * @var \DateTime
     */
    protected $date;

    /**
     * @var string
     */
    protected $title = '';

    /**
     * @var string
     */
    protected $subtitle = '';

    /**
     * @var string
     */
    protected $status = 'new';

    /**
     * @var string
     */
    protected $notes = '';

    /**
     * @var string
     */
    protected $content = '';

    /**
     * @var string
     */
    protected $data = '';

    /**
     * @var string
     */
    protected $type = 'clipping';

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\TYPO3\CMS\Extbase\Domain\Model\FileReference>
     * @TYPO3\CMS\Extbase\Annotation\ORM\Lazy
     */
    protected $files;

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BucheggerOnline\Publicrelations\Domain\Model\Link>
     * @TYPO3\CMS\Extbase\Annotation\ORM\Lazy
     */
    protected $links;

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BucheggerOnline\Publicrelations\Domain\Model\Log>
     * @TYPO3\CMS\Extbase\Annotation\ORM\Lazy
     */
    protected $logs;

    /**
     * @var string
     */
    protected $medium = '';

    /**
     * @var string
     */
    protected $department = '';

    /**
     * @var string
     */
    protected $mediaType = '';

    /**
     * @var string
     */
    protected $publicationFrequency = '';

    /**
     * @var string
     */
    protected $publicationId = '';

    /**
     * @var string
     */
    protected $pageNumber = '';

    /**
     * @var int
     */
    protected $reach = 0;

    /**
     * @var int
     */
    protected $adValue = 0;

    /**
     * @var string
     */
    protected $apaGuid = '';

    /**
     * @var string
     */
    protected $apaLink = '';

    /**
     * clippingroute
     *
     * @var \BucheggerOnline\Publicrelations\Domain\Model\ClippingRoute
     */
    protected $clippingroute;

    /**
     * @var bool
     */
    protected $reported = false;

    /**
     * @var string
     */
    protected $approvalToken = '';

    /**
     * __construct
     */
    public function __construct()
    {
        // ObjectStorage initialisieren, damit sie nie null sind
        $this->files = new ObjectStorage();
        $this->links = new ObjectStorage();
        $this->logs = new ObjectStorage();
    }

    /**
     * @return Client
     */
    public function getClient(): ?Client
    {
        return $this->client;
    }

    /**
     * @param Client $client
     */
    public function setClient(Client $client): void
    {
        $this->client = $client;
    }

    /**
     * @return Campaign
     */
    public function getCampaign(): ?Campaign
    {
        return $this->campaign;
    }

    /**
     * @param Campaign $campaign
     */
    public function setCampaign(Campaign $campaign): void
    {
        $this->campaign = $campaign;
    }

    /**
     * @return \DateTime
     */
    public function getDate(): ?\DateTime
    {
        return $this->date;
    }

    /**
     * @param \DateTime $date
     */
    public function setDate(\DateTime $date): void
    {
        $this->date = $date;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getSubtitle(): string
    {
        return $this->subtitle;
    }

    public function setSubtitle(string $subtitle): void
    {
        $this->subtitle = $subtitle;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @param string $status
     */
    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    /**
     * @return string
     */
    public function getNotes(): string
    {
        return $this->notes;
    }

    /**
     * @param string $notes
     */
    public function setNotes(string $notes): void
    {
        $this->notes = $notes;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): void
    {
        $this->content = $content;
    }

    public function getData(): string
    {
        return $this->data;
    }

    public function setData(string $data): void
    {
        $this->data = $data;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType(string $type): void
    {
        $this->type = $type;
    }

    /**
     * @return ObjectStorage<FileReference>
     */
    public function getFiles(): ObjectStorage
    {
        return $this->files;
    }

    /**
     * @param ObjectStorage<FileReference> $files
     */
    public function setFiles(ObjectStorage $files): void
    {
        $this->files = $files;
    }

    /**
     * @return ObjectStorage<Link>
     */
    public function getLinks(): ObjectStorage
    {
        return $this->links;
    }

    /**
     * @param ObjectStorage<Link> $links
     */
    public function setLinks(ObjectStorage $links): void
    {
        $this->links = $links;
    }

    /**
     * @return ObjectStorage<Log>
     */
    public function getLogs(): ObjectStorage
    {
        return $this->logs;
    }

    /**
     * @param ObjectStorage<Log> $logs
     */
    public function setLogs(ObjectStorage $logs): void
    {
        $this->logs = $logs;
    }

    /**
     * @return string
     */
    public function getMedium(): string
    {
        return $this->medium;
    }

    /**
     * @param string $medium
     */
    public function setMedium(string $medium): void
    {
        $this->medium = $medium;
    }

    /**
     * @return string
     */
    public function getDepartment(): string
    {
        return $this->department;
    }

    /**
     * @param string $department
     */
    public function setDepartment(string $department): void
    {
        $this->department = $department;
    }

    /**
     * @return string
     */
    public function getMediaType(): string
    {
        return $this->mediaType;
    }

    /**
     * @param string $mediaType
     */
    public function setMediaType(string $mediaType): void
    {
        $this->mediaType = $mediaType;
    }

    /**
     * @return string
     */
    public function getPublicationFrequency(): string
    {
        return $this->publicationFrequency;
    }

    /**
     * @param string $publicationFrequency
     */
    public function setPublicationFrequency(string $publicationFrequency): void
    {
        $this->publicationFrequency = $publicationFrequency;
    }

    /**
     * @return string
     */
    public function getPublicationId(): string
    {
        return $this->publicationId;
    }

    /**
     * @param string $publicationId
     */
    public function setPublicationId(string $publicationId): void
    {
        $this->publicationId = $publicationId;
    }

    /**
     * @return string
     */
    public function getPageNumber(): string
    {
        return $this->pageNumber;
    }

    /**
     * @param string $pageNumber
     */
    public function setPageNumber(string $pageNumber): void
    {
        $this->pageNumber = $pageNumber;
    }

    /**
     * @return int
     */
    public function getReach(): int
    {
        return $this->reach;
    }

    /**
     * @param int $reach
     */
    public function setReach(int $reach): void
    {
        $this->reach = $reach;
    }

    /**
     * @return int
     */
    public function getAdValue(): int
    {
        return $this->adValue;
    }

    /**
     * @param int $adValue
     */
    public function setAdValue(int $adValue): void
    {
        $this->adValue = $adValue;
    }

    /**
     * @return string
     */
    public function getApaGuid(): string
    {
        return $this->apaGuid;
    }

    /**
     * @param string $apaGuid
     */
    public function setApaGuid(string $apaGuid): void
    {
        $this->apaGuid = $apaGuid;
    }

    /**
     * @return string
     */
    public function getApaLink(): string
    {
        return $this->apaLink;
    }

    /**
     * @param string $apaLink
     */
    public function setApaLink(string $apaLink): void
    {
        $this->apaLink = $apaLink;
    }

    /**
     * @return ClippingRoute
     */
    public function getClippingRoute(): ?ClippingRoute
    {
        return $this->clippingroute;
    }

    /**
     * @param ClippingRoute $clippingroute
     */
    public function setClippingRoute(ClippingRoute $clippingroute): void
    {
        $this->clippingroute = $clippingroute;
    }

    /**
     * @return bool
     */
    public function isReported(): bool
    {
        return $this->reported;
    }

    /**
     * @param bool $reported
     */
    public function setReported(bool $reported): void
    {
        $this->reported = $reported;
    }

    /**
     * @return string
     */
    public function getApprovalToken(): string
    {
        return $this->approvalToken;
    }

    /**
     * @param string $approvalToken
     */
    public function setApprovalToken(string $approvalToken): void
    {
        $this->approvalToken = $approvalToken;
    }
}