<?php

namespace Doctrine\Tests\Models\Navigation;

/**
 * @Entity
 * @Table(name="navigation_photos")
 */
class NavPhotos
{
    /**
     * @Id
     * @column(type="integer")
     * @generatedValue
     */
    private $id;

    /**
     * @ManyToOne(targetEntity="NavPointOfInterest")
     * @JoinColumns({
     *   @JoinColumn(name="poi_long", referencedColumnName="nav_long"),
     *   @JoinColumn(name="poi_lat", referencedColumnName="nav_lat")
     * })
     */
    private $poi;

    /**
     * @column(type="string", name="file_name")
     */
    private $file;

    function __construct($poi, $file) {
        $this->poi = $poi;
        $this->file = $file;
    }

    public function getId() {
        return $this->id;
    }

    public function getPointOfInterest() {
        return $this->poi;
    }

    public function getFile() {
        return $this->file;
    }
}