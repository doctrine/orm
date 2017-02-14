<?php

namespace Doctrine\Tests\Models\Navigation;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="navigation_photos")
 */
class NavPhotos
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="NavPointOfInterest")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="poi_long", referencedColumnName="nav_long"),
     *   @ORM\JoinColumn(name="poi_lat", referencedColumnName="nav_lat")
     * })
     */
    private $poi;

    /**
     * @ORM\Column(type="string", name="file_name")
     */
    private $file;

    public function __construct($poi, $file) {
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