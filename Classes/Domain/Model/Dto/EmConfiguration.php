<?php

namespace BucheggerOnline\Publicrelations\Domain\Model\Dto;

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This file is part of the "news" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

/**
 * Extension Manager configuration
 */
class EmConfiguration
{

    /**
     * Fill the properties properly
     *
     * @param array $configuration em configuration
     */
    public function __construct(array $configuration = [])
    {
        if (empty($configuration)) {
            try {
                $extensionConfiguration = GeneralUtility::makeInstance(ExtensionConfiguration::class);
                $configuration = $extensionConfiguration->get('publicrelations') ?? [];
            } catch (\Exception $exception) {
                // do nothing
            }
        }
        foreach ($configuration as $key => $value) {
            if (property_exists(self::class, $key)) {
                $propertyType = (new \ReflectionProperty(self::class, $key))->getType()?->getName();
                if ($propertyType === 'int') {
                    $this->$key = (int) $value;
                } elseif ($propertyType === 'string') {
                    $this->$key = (string) $value;
                } else {
                    $this->$key = $value;
                }
            }
        }
    }

    protected int $activitiesRootUid = 0;
    protected int $clientRootUid = 0;
    protected int $campaignRootUid = 0;
    protected int $newsRootUid = 0;
    protected int $eventRootUid = 0;
    protected int $eventNoteRootUid = 0;
    protected int $contactRootUid = 0;
    protected int $linkRootUid = 0;
    protected int $accreditationNotesRootUid = 0;
    protected int $mediumRootUid = 0;
    protected int $slideWorkRootUid = 0;
    protected int $journalistsRootUid = 0;
    protected int $celebRootUid = 0;
    protected int $mailingListsRootUid = 0;
    protected int $contactTypesRootUid = 0;
    protected int $staffPid = 0;
    protected int $journalistsPid = 0;
    protected int $celebPid = 0;
    protected int $mailingListsPid = 0;
    protected int $clientPid = 0;
    protected int $contactPid = 0;
    protected string $mediagroups = '';
    protected string $mailingtypes = '';
    protected string $emailTemplates = '';
    protected int $accreditationStop = 0;
    protected int $invitationReportStop = 0;
    protected string $emailFromAddress = '';
    protected string $emailFromName = '';
    protected string $emailToAddress = '';
    protected string $emailToName = '';
    protected string $emailReplyAddress = '';
    protected string $emailReplyName = '';
    protected int $invitationRemindAfter = 0;
    protected int $invitationPushAfter = 0;
    protected int $invitationCancelAfter = 0;
    protected int $importerPid = 0;
    protected string $importableColumns = '';


    /** @var int */
    public function getActivitiesRootUid(): int
    {
        return (int) $this->activitiesRootUid;
    }

    /** @var int */
    public function getClientRootUid(): int
    {
        return (int) $this->clientRootUid;
    }

    /** @var int */
    public function getCampaignRootUid(): int
    {
        return (int) $this->campaignRootUid;
    }

    /** @var int */
    public function getNewsRootUid(): int
    {
        return (int) $this->newsRootUid;
    }

    /** @var int */
    public function getEventRootUid(): int
    {
        return (int) $this->eventRootUid;
    }

    /** @var int */
    public function getEventNoteRootUid(): int
    {
        return (int) $this->eventNoteRootUid;
    }

    /** @var int */
    public function getContactRootUid(): int
    {
        return (int) $this->contactRootUid;
    }

    /** @var int */
    public function getLinkRootUid(): int
    {
        return (int) $this->linkRootUid;
    }

    /** @var int */
    public function getAccreditationNotesRootUid(): int
    {
        return (int) $this->accreditationNotesRootUid;
    }

    /** @var int */
    public function getMediumRootUid(): int
    {
        return (int) $this->mediumRootUid;
    }

    /** @var int */
    public function getSlideWorkRootUid(): int
    {
        return (int) $this->slideWorkRootUid;
    }

    /** @var int */
    public function getJournalistsRootUid(): int
    {
        return (int) $this->journalistsRootUid;
    }

    /** @var int */
    public function getCelebRootUid(): int
    {
        return (int) $this->celebRootUid;
    }

    /** @var int */
    public function getMailingListsRootUid(): int
    {
        return (int) $this->mailingListsRootUid;
    }

    /** @var int */
    public function getContactTypesRootUid(): int
    {
        return (int) $this->contactTypesRootUid;
    }

    /** @var int */
    public function getStaffPid(): int
    {
        return (int) $this->staffPid;
    }

    /** @var int */
    public function getJournalistsPid(): int
    {
        return (int) $this->journalistsPid;
    }

    /** @var int */
    public function getCelebPid(): int
    {
        return (int) $this->celebPid;
    }

    /** @var int */
    public function getMailingListsPid(): int
    {
        return (int) $this->mailingListsPid;
    }

    /** @var int */
    public function getClientPid(): int
    {
        return (int) $this->clientPid;
    }

    /** @var int */
    public function getContactPid(): int
    {
        return (int) $this->contactPid;
    }

    /** @var array */
    public function getMediagroups(): array
    {
        $groups = explode(";", (string) $this->mediagroups);
        $mediagroups = [];
        foreach ($groups as $group) {
            $mediagroups[] = [trim($group), trim($group)];
        }
        return $mediagroups;
    }

    /** @var array */
    public function getMailingtypes(): array
    {
        $groups = explode(";", (string) $this->mailingtypes);
        $mailingtypes = [];
        foreach ($groups as $group) {
            $mailingtypes[] = [trim($group), trim($group)];
        }
        return $mailingtypes;
    }

    /** @var array */
    public function getEmailTemplates(): array
    {
        $groups = explode(";", (string) $this->emailTemplates);
        $emailTemplates = [];
        foreach ($groups as $group) {
            $emailTemplates[] = [trim($group), trim($group)];
        }
        return $emailTemplates;
    }

    /** @var int */
    public function getAccreditationStop(): int
    {
        return (int) $this->accreditationStop * 60;
    }

    /** @var int */
    public function getInvitationReportStop(): int
    {
        return (int) $this->invitationReportStop;
    }

    /** @var string */
    public function getEmailFromAddress(): string
    {
        return $this->emailFromAddress;
    }

    /** @var string */
    public function getEmailFromName(): string
    {
        return $this->emailFromName;
    }

    /** @var string */
    public function getEmailToAddress(): string
    {
        return $this->emailToAddress;
    }

    /** @var string */
    public function getEmailToName(): string
    {
        return $this->emailToName;
    }

    /** @var string */
    public function getEmailReplyAddress(): string
    {
        return $this->emailReplyAddress;
    }

    /** @var string */
    public function getEmailReplyName(): string
    {
        return $this->emailReplyName;
    }

    /** @var int */
    public function getInvitationRemindAfter(): int
    {
        return (int) $this->invitationRemindAfter;
    }

    /** @var int */
    public function getInvitationPushAfter(): int
    {
        return (int) $this->invitationPushAfter;
    }

    /** @var int */
    public function getInvitationCancelAfter(): int
    {
        return (int) $this->invitationCancelAfter;
    }

    /** @var int */
    public function getImporterPid(): int
    {
        return (int) $this->importerPid;
    }

    /** @var array */
    public function getImportableColumns(): array
    {
        return (array) explode(',', $this->importableColumns);
    }

}
