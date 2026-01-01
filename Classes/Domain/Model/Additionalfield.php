<?php
namespace BucheggerOnline\Publicrelations\Domain\Model;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

use BucheggerOnline\Publicrelations\Domain\Model\Dto\EmConfiguration;
use BucheggerOnline\Publicrelations\Utility\GeneralFunctions;

use BucheggerOnline\Publicrelations\Domain\Repository\AdditionalanswerRepository;

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
 * Accreditation
 */
class Additionalfield extends AbstractEntity
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
     * position
     *
     * @var int
     *
     */
    protected $position = 0;

    /**
     * label
     *
     * @var string
     *
     */
    protected $label = '';

    /**
     * description
     *
     * @var string
     *
     */
    protected $description = '';

    /**
     * icon
     *
     * @var string
     */
    protected $icon = '';

    /**
     * options
     *
     * @var string
     */
    protected $options = '';

    /**
     * type
     *
     * @var int
     *
     */
    protected $type = 0;

    /**
     * required
     *
     * @var bool
     */
    protected $required = false;

    /**
     * summary
     *
     * @var bool
     */
    protected $summary = false;

    /**
     * accreditation
     *
     * @var bool
     */
    protected $accreditation = false;

    /**
     * invitation
     *
     * @var bool
     */
    protected $invitation = false;

    /**
     * confirmation
     *
     * @var bool
     */
    protected $confirmation = false;

    /**
     * event
     *
     * @var \BucheggerOnline\Publicrelations\Domain\Model\Event
     */
    protected $event;


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
     * Returns the position
     *
     * @return int $position
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * Sets the position
     *
     * @param int $position
     * @return void
     */
    public function setPosition($position)
    {
        $this->position = $position;
    }

    /**
     * Returns the label
     *
     * @return string $label
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Sets the label
     *
     * @param string $label
     * @return void
     */
    public function setLabel($label)
    {
        $this->label = $label;
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
     * Returns the icon
     *
     * @return string $icon
     */
    public function getIcon()
    {
        return $this->icon;
    }

    /**
     * Sets the icon
     *
     * @param string $icon
     * @return void
     */
    public function setIcon($icon)
    {
        $this->icon = $icon;
    }

    /**
     * Returns the options
     *
     * @return $options
     */
    public function getOptions()
    {
        if ($this->getType() == 3) {
            $options = array_filter(array_map('trim', explode("\n", $this->options)));

            foreach ($options as $option) {

                $tempOption = explode("|", $option);

                $optionsToReturn[] = [
                    'value' => $tempOption[0],
                    'label' => $tempOption[1] ?? $tempOption[0],
                    'frontend' => (isset($tempOption[2]) && $tempOption[2]) ? 0 : 1
                ];
            }
            ;
            return $optionsToReturn;
        } elseif ($this->getType() == 4) {
            $options = array_filter(array_map('trim', explode("\n", $this->options)));

            foreach ($options as $option) {

                $tempOption = explode("|", $option);

                $optionsToReturn[] = [
                    'value' => $tempOption[0],
                    'label' => $tempOption[1] ?? $tempOption[0],
                    'frontend' => (isset($tempOption[2]) && $tempOption[2]) ? 0 : 1
                ];
            }
            ;
            return $optionsToReturn;
        } elseif ($this->getType() == 6) {
            $options = array_filter(array_map('trim', explode("\n", $this->options)));

            foreach ($options as $option) {

                $tempOption = explode("|", $option);

                $optionsToReturn[] = [
                    'value' => $tempOption[0],
                    'label' => $tempOption[1],
                    'sort' => $tempOption[2],
                    'width' => $tempOption[3],
                    'color' => $tempOption[4],
                    'icon' => $tempOption[5]
                ];
            }
            ;
            return $optionsToReturn;
        } else {
            return [];
        }
    }

    /**
     * Sets the options
     *
     * @param string $options
     * @return void
     */
    public function setOptions($options)
    {
        $this->options = $options;
    }

    /**
     * Returns the type
     *
     * @return int $type
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Sets the type
     *
     * @param int $type
     * @return void
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * Returns the required
     *
     * @return bool $required
     */
    public function getRequired()
    {
        return $this->required;
    }

    /**
     * Sets the required
     *
     * @param bool $required
     * @return void
     */
    public function setRequired($required)
    {
        $this->required = $required;
    }

    /**
     * Returns the boolean state of required
     *
     * @return bool
     */
    public function isRequired()
    {
        return $this->required;
    }

    /**
     * Returns the summary
     *
     * @return bool $summary
     */
    public function getSummary()
    {
        return $this->summary;
    }

    /**
     * Sets the summary
     *
     * @param bool $summary
     * @return void
     */
    public function setSummary($summary)
    {
        $this->summary = $summary;
    }

    /**
     * Returns the boolean state of summary
     *
     * @return bool
     */
    public function isSummary()
    {
        return $this->summary;
    }

    /**
     * Returns the accreditation
     *
     * @return bool $accreditation
     */
    public function getAccreditation()
    {
        return $this->accreditation;
    }

    /**
     * Sets the accreditation
     *
     * @param bool $accreditation
     * @return void
     */
    public function setAccreditation($accreditation)
    {
        $this->accreditation = $accreditation;
    }

    /**
     * Returns the boolean state of accreditation
     *
     * @return bool
     */
    public function isAccreditation()
    {
        return $this->accreditation;
    }

    /**
     * Returns the invitation
     *
     * @return bool $invitation
     */
    public function getInvitation()
    {
        return $this->invitation;
    }

    /**
     * Sets the invitation
     *
     * @param bool $invitation
     * @return void
     */
    public function setInvitation($invitation)
    {
        $this->invitation = $invitation;
    }

    /**
     * Returns the boolean state of invitation
     *
     * @return bool
     */
    public function isInvitation()
    {
        return $this->invitation;
    }

    /**
     * Returns the confirmation
     *
     * @return bool $confirmation
     */
    public function getConfirmation()
    {
        return $this->confirmation;
    }

    /**
     * Sets the confirmation
     *
     * @param bool $confirmation
     * @return void
     */
    public function setConfirmation($confirmation)
    {
        $this->confirmation = $confirmation;
    }

    /**
     * Returns the boolean state of confirmation
     *
     * @return bool
     */
    public function isConfirmation()
    {
        return $this->confirmation;
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
}
