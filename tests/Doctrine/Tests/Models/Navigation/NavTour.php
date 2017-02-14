<?php

namespace Doctrine\Tests\Models\Navigation;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="navigation_tours")
 */
class NavTour
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @ORM\Column(type="string")
     */
    private $name;

    /**
     * @ORM\ManyToMany(targetEntity="NavPointOfInterest")
     * @ORM\JoinTable(name="navigation_tour_pois",
     *      joinColumns={@ORM\JoinColumn(name="tour_id", referencedColumnName="id")},
     *      inverseJoinColumns={
     *          @ORM\JoinColumn(name="poi_long", referencedColumnName="nav_long"),
     *          @ORM\JoinColumn(name="poi_lat", referencedColumnName="nav_lat")
     *      }
     * )
     *
     */
    private $pois;

    public function __construct($name)
    {
        $this->name = $name;
        $this->pois = new \Doctrine\Common\Collections\ArrayCollection;
    }

    public function addPointOfInterest(NavPointOfInterest $poi)
    {
        $this->pois[] = $poi;
    }

    public function getPointOfInterests()
    {
        return $this->pois;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getId()
    {
        return $this->id;
    }
}