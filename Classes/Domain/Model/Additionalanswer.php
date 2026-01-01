<?php
namespace BucheggerOnline\Publicrelations\Domain\Model;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

use BucheggerOnline\Publicrelations\Domain\Model\Dto\EmConfiguration;
use BucheggerOnline\Publicrelations\Utility\GeneralFunctions;

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
class Additionalanswer extends AbstractEntity
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
     * value
     *
     * @var string
     *
     */
    protected $value = '';

    /**
     * type
     *
     * @var int
     *
     */
    protected $type = 0;

    /**
     * accreditation
     *
     * @var \BucheggerOnline\Publicrelations\Domain\Model\Accreditation
     */
    protected $accreditation;

    /**
     * field
     *
     * @var \BucheggerOnline\Publicrelations\Domain\Model\Additionalfield
     */
    protected $field;


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
     * Returns the value
     *
     * @return string $value
     */
    public function getValue()
    {
        switch ($this->getType()) {
          case 4:
            $values = array_filter(array_map('trim',explode("\n",$this->value)));
            foreach ($values as $value) {
              $tempValue = explode("|",$value);
              $valuesToReturn[] = [
                'value' => $tempValue[0]
              ];
            };
            return $valuesToReturn;
          case 6:
            $values = array_filter(array_map('trim',explode("\n",$this->value)));
            foreach ($values as $value) {
              $tempValue = explode("|",$value);
              $valuesToReturn[] = [
                'key' => $tempValue[0],
                'value' => $tempValue[1]
              ];
            };
            return $valuesToReturn;
          default:
            return $this->value;
        }
    }

    /**
     * Sets the value
     *
     * @param string $value
     * @return void
     */
    public function setValue($value)
    {
        $this->value = $value;
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
     * Returns the accreditation
     *
     * @return \BucheggerOnline\Publicrelations\Domain\Model\Accreditation $accreditation
     */
    public function getAccreditation()
    {
        return $this->accreditation;
    }

    /**
     * Sets the accreditation
     *
     * @return void
     */
    public function setAccreditation(\BucheggerOnline\Publicrelations\Domain\Model\Accreditation $accreditation)
    {
        $this->accreditation = $accreditation;
    }

    /**
     * Returns the field
     *
     * @return \BucheggerOnline\Publicrelations\Domain\Model\Additionalfield $field
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * Sets the field
     *
     * @return void
     */
    public function setField(\BucheggerOnline\Publicrelations\Domain\Model\Additionalfield $field)
    {
        $this->field = $field;
    }
}
