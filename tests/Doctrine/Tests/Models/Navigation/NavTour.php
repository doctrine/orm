<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Navigation;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * @Entity
 * @Table(name="navigation_tours")
 */
class NavTour
{
    /**
     * @var int
     * @Id
     * @Column(type="integer")
     * @generatedValue
     */
    private $id;

    /**
     * @var string
     * @column(type="string")
     */
    private $name;

    /**
     * @var Collection<int, NavPointOfInterest>
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

    public function __construct(string $name)
    {
        $this->name = $name;
        $this->pois = new ArrayCollection();
    }

    public function addPointOfInterest(NavPointOfInterest $poi): void
    {
        $this->pois[] = $poi;
    }

    public function getPointOfInterests(): Collection
    {
        return $this->pois;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getId(): int
    {
        return $this->id;
    }
}
