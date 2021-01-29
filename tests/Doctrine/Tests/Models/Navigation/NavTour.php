<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Navigation;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Entity
 * @Table(name="navigation_tours")
 */
class NavTour
{
    /**
     * @Id
     * @Column(type="integer")
     * @generatedValue
     */
    private $id;

    /** @column(type="string") */
    private $name;

    /**
     * @ManyToMany(targetEntity="NavPointOfInterest")
     * @JoinTable(name="navigation_tour_pois",
     *      joinColumns={@JoinColumn(name="tour_id", referencedColumnName="id")},
     *      inverseJoinColumns={
     *          @JoinColumn(name="poi_long", referencedColumnName="nav_long"),
     *          @JoinColumn(name="poi_lat", referencedColumnName="nav_lat")
     *      }
     * )
     */
    private $pois;

    public function __construct($name)
    {
        $this->name = $name;
        $this->pois = new ArrayCollection();
    }

    public function addPointOfInterest(NavPointOfInterest $poi): void
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
