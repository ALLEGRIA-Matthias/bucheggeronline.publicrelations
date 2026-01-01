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
 * Contact
 */
class Contact extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
{

    /**
     * types
     *
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BucheggerOnline\Publicrelations\Domain\Model\SysCategory>
     */
    protected $types;

    /**
     * typeOverwrite
     *
     * @var string
     */
    protected $typesOverwrite = '';

    /**
     * staff
     *
     * @var \FriendsOfTYPO3\TtAddress\Domain\Model\Address
     */
    protected $staff;

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
        $this->types = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
    }

    /**
     * Adds a Type
     *
     * @return void
     */
    public function addType(\BucheggerOnline\Publicrelations\Domain\Model\SysCategory $type)
    {
        $this->types->attach($type);
    }

    /**
     * Removes a Category
     *
     * @param \BucheggerOnline\Publicrelations\Domain\Model\SysCategory $typeToRemove The Type to be removed
     * @return void
     */
    public function removeType(\BucheggerOnline\Publicrelations\Domain\Model\SysCategory $typeToRemove)
    {
        $this->types->detach($typeToRemove);
    }

    /**
     * Returns the types
     *
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BucheggerOnline\Publicrelations\Domain\Model\SysCategory> types
     */
    public function getTypes()
    {
        return $this->types;
    }

    /**
     * Sets the types
     *
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BucheggerOnline\Publicrelations\Domain\Model\SysCategory> $types
     * @return void
     */
    public function setTypes(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $types)
    {
        $this->types = $types;
    }

    /**
     * Returns the typesOverwrite
     *
     * @return string $typesOverwrite
     */
    public function getTypesOverwrite()
    {
        return $this->typesOverwrite;
    }

    /**
     * Sets the typesOverwrite
     *
     * @param string $typesOverwrite
     * @return void
     */
    public function setTypesOverwrite($typesOverwrite)
    {
        $this->typesOverwrite = $typesOverwrite;
    }

    /**
     * Returns the typesOutput
     *
     * @return array typesOutput
     */
    public function getTypesOutput()
    {
        if ($this->types->count() && $this->getTypesOverwrite()) {

          foreach ($this->types as $type)
          $typesOutput[] = $type->getTitle();

          $typesOutput = array_filter($typesOutput);
          $typeOverwrites = array_map('trim', explode("\n",$this->getTypesOverwrite()));
          $typeOverwrites = array_filter($typeOverwrites);

          $output = array_replace($typesOutput, $typeOverwrites);

        } elseif ($this->types->count()) {

          foreach ($this->types as $type)
          $output[] = $type->getTitle();

        } elseif ($this->getTypesOverwrite()) {

          $output = array_map('trim', explode("\n",$this->getTypesOverwrite()));

        } else {

          $output = NULL;

        }

        return $output;
    }

    /**
     * Returns the staff
     *
     * @return \FriendsOfTYPO3\TtAddress\Domain\Model\Address staff
     */
    public function getStaff()
    {
        return $this->staff;
    }

    /**
     * Sets the staff
     *
     * @param string $staff
     * @return void
     */
    public function setStaff($staff)
    {
        $this->staff = $staff;
    }
}
