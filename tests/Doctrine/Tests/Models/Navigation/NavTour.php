<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Navigation;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\JoinTable;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\Table;

/**
 * @Entity
 * @Table(name="navigation_tours")
 */
class NavTour
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    private int $id;

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
    private Collection $pois;

    public function __construct(
        /** @Column(type="string", length=255) */
        private string $name,
    ) {
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
