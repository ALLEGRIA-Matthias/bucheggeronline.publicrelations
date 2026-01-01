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
 * Address
 */
class Location extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
{

    /**
     * name
     *
     * @var string
     *
     */
    protected $name = '';

    /**
     * notes
     *
     * @var string
     */
    protected $notes = '';

    /**
     * street
     *
     * @var string
     *
     */
    protected $street = '';

    /**
     * additional
     *
     * @var string
     *
     */
    protected $additional = '';

    /**
     * zip
     *
     * @var string
     *
     */
    protected $zip = '';

    /**
     * city
     *
     * @var string
     *
     */
    protected $city = '';

    /**
     * typeOverwrite
     *
     * @var string
     */
    protected $typeOverwrite = '';

    /**
     * country
     *
     * @var \BucheggerOnline\Publicrelations\Domain\Model\StaticInfoCountry
     */
    protected $country;

    /**
     * Returns the name
     *
     * @return string $name
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Sets the name
     *
     * @param string $name
     * @return void
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Returns the street
     *
     * @return string $street
     */
    public function getStreet()
    {
        return $this->street;
    }

    /**
     * Sets the street
     *
     * @param string $street
     * @return void
     */
    public function setStreet($street)
    {
        $this->street = $street;
    }

    /**
     * Returns the additional
     *
     * @return string $additional
     */
    public function getAdditional()
    {
        return $this->additional;
    }

    /**
     * Sets the additional
     *
     * @param string $additional
     * @return void
     */
    public function setAdditional($additional)
    {
        $this->additional = $additional;
    }

    /**
     * Returns the zip
     *
     * @return string $zip
     */
    public function getZip()
    {
        return $this->zip;
    }

    /**
     * Sets the zip
     *
     * @param string $zip
     * @return void
     */
    public function setZip($zip)
    {
        $this->zip = $zip;
    }

    /**
     * Returns the city
     *
     * @return string $city
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * Sets the city
     *
     * @param string $city
     * @return void
     */
    public function setCity($city)
    {
        $this->city = $city;
    }

    /**
     * Returns the typeOverwrite
     *
     * @return string $typeOverwrite
     */
    public function getTypeOverwrite()
    {
        return $this->typeOverwrite;
    }

    /**
     * Sets the typeOverwrite
     *
     * @param string $typeOverwrite
     * @return void
     */
    public function setTypeOverwrite($typeOverwrite)
    {
        $this->typeOverwrite = $typeOverwrite;
    }

    /**
     * Returns the notes
     *
     * @return string $notes
     */
    public function getNotes()
    {
        return $this->notes;
    }

    /**
     * Sets the notes
     *
     * @param string $notes
     * @return void
     */
    public function setNotes($notes)
    {
        $this->notes = $notes;
    }

    /**
     * Returns the country
     *
     * @return \BucheggerOnline\Publicrelations\Domain\Model\StaticInfoCountry $country
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * Sets the country
     *
     * @param \BucheggerOnline\Publicrelations\Domain\Model\StaticInfoCountry $country
     * @return void
     */
    public function setCountry(\SJBR\StaticInfoTables\Domain\Model\Country $country)
    {
        $this->country = $country;
    }

    /**
     * Returns the selectTitle
     *
     * @return $selectTitle
     */
    public function getSelectTitle()
    {
        $output = $this->getName();
        $output .= ' [';
        $output .= ($this->getAdditional()) ? $this->getAdditional().' • ' : '';
        $output .= $this->getStreet() ?? '';
        $output .= ($this->getZip()) ? ', '.$this->getZip().' ' : '';
        $output .= $this->getCity() ?? '';

        return $output . ']';
    }
}
