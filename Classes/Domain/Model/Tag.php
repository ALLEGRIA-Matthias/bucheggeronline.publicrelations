<?php

namespace BucheggerOnline\Publicrelations\Domain\Model;

use TYPO3\CMS\Extbase\Annotation as Extbase;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * This model represents a tag for anything.
 */
class Tag extends \TYPO3\CMS\Extbase\Domain\Model\Tag
{
    /**
     * @var string
     */
    protected $icon = '';

    /**
     * @var string
     */
    protected $color = '';

    protected $level = 1;

    /**
     * @var \BucheggerOnline\Publicrelations\Domain\Model\Tag
     */
    protected $parent = null;

    /**
     * @var ObjectStorage<Tag>
     * @TYPO3\CMS\Extbase\Annotation\ORM\Lazy
     * @TYPO3\CMS\Extbase\Annotation\ORM\Cascade("remove")
     */
    protected $children;

    public function __construct()
    {
        $this->initializeObject();
    }

    /**
     * Initializes all ObjectStorage properties
     */
    public function initializeObject()
    {
        $this->children = $this->children ?: new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
    }

    /**
     * Gets the icon.
     *
     * @return string the icon, might be empty
     */
    public function getIcon()
    {
        return $this->icon;
    }

    /**
     * Sets the icon.
     *
     * @param string $icon the icon to set, may be empty
     */
    public function setIcon($icon)
    {
        $this->icon = $icon;
    }

    /**
     * Gets the color.
     *
     * @return string the color, might be empty
     */
    public function getColor()
    {
        return $this->color;
    }

    /**
     * Sets the color.
     *
     * @param string $color the color to set, may be empty
     */
    public function setColor($color)
    {
        $this->color = $color;
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    public function setLevel(int $level): void
    {
        $this->level = $level;
    }

    /**
     * Gets the parent tag.
     *
     * @return \BucheggerOnline\Publicrelations\Domain\Model\Tag the parent tag, might be null
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Sets the parent tag.
     *
     * @param \BucheggerOnline\Publicrelations\Domain\Model\Tag $parent the parent tag to set, may be null
     */
    public function setParent($parent)
    {
        $this->parent = $parent;
    }

    /**
     * Gibt die Kinder-Tags zurück.
     *
     * @return ObjectStorage<Tag>
     */
    public function getChildren(): ObjectStorage
    {
        return $this->children;
    }

    /**
     * Setzt die Kinder für den aktuellen Tag.
     *
     * @param ObjectStorage<Tag> $children
     * @return void
     */
    public function setChildren(ObjectStorage $children): void
    {
        $this->children = $children;
    }

    /**
     * Adds a child Tag
     *
     * @param Tag $child
     * @return void
     */
    public function addChild(Tag $child): void
    {
        $this->children->attach($child);
    }

    /**
     * Entfernt ein Kind.
     *
     * @param Tag $child
     */
    public function removeChild(Tag $child): void
    {
        $this->children->detach($child);
    }

    /**
     * Gibt die Farbe des Tags zurück. Falls keine Farbe gesetzt ist,
     * wird die Farbe des ersten übergeordneten Parents zurückgegeben.
     *
     * @return string|null
     */
    public function getGroupColor(): ?string
    {
        // Wenn das aktuelle Tag eine Farbe hat, gebe diese zurück
        if (!empty($this->color)) {
            return $this->color;
        }

        // Wenn es einen Parent gibt, rufe rekursiv die Farbe des Parents ab
        if ($this->parent) {
            return $this->parent->getGroupColor();
        }

        // Wenn weder das Tag noch ein Parent eine Farbe hat, gebe null zurück
        return null;
    }


    /**
     * Gibt die Farbe des Tags zurück. Falls keine Farbe gesetzt ist,
     * wird die Farbe des ersten übergeordneten Parents zurückgegeben.
     *
     * @return string|null
     */
    public function getGroupIcon(): ?string
    {
        // Wenn das aktuelle Tag eine Farbe hat, gebe diese zurück
        if (!empty($this->icon)) {
            return $this->icon;
        }

        // Wenn es einen Parent gibt, rufe rekursiv die Farbe des Parents ab
        if ($this->parent) {
            return $this->parent->getGroupIcon();
        }

        // Wenn weder das Tag noch ein Parent eine Farbe hat, gebe null zurück
        return null;
    }

    public function getHierarchyTitle(): string
    {
        $titles = [];
        $currentTag = $this;

        // Rekursiv Eltern-Tags sammeln, bis kein Parent mehr vorhanden ist
        while ($currentTag->getParent() && $currentTag->getParent()->getParent()) {
            $titles[] = $currentTag->getParent()->getTitle();
            $currentTag = $currentTag->getParent();
        }

        // Reihenfolge umkehren, damit die Hierarchie von oben nach unten dargestellt wird
        $titles = array_reverse($titles);

        return implode(' / ', $titles);
    }

}
