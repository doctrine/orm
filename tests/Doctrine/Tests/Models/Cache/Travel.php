<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Cache;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * @Cache
 * @Entity
 * @Table("cache_travel")
 */
class Travel
{
    /**
     * @Id
     * @GeneratedValue
     * @Column(type="integer")
     */
    protected $id;

    /** @Column(type="date") */
    protected $createdAt;

    /**
     * @Cache
     * @ManyToOne(targetEntity="Traveler", inversedBy="travels")
     * @JoinColumn(name="traveler_id", referencedColumnName="id")
     */
    protected $traveler;

    /**
     * @Cache
     * @ManyToMany(targetEntity="City", inversedBy="travels", cascade={"persist", "remove"})
     * @JoinTable(name="cache_visited_cities",
     *  joinColumns={
     *      @JoinColumn(name="travel_id", referencedColumnName="id")
     *  },
     *  inverseJoinColumns={
     *      @JoinColumn(name="city_id", referencedColumnName="id")
     *  }
     * )
     */
    public $visitedCities;

    public function __construct(Traveler $traveler)
    {
        $this->traveler      = $traveler;
        $this->createdAt     = new DateTime('now');
        $this->visitedCities = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getTraveler(): Traveler
    {
        return $this->traveler;
    }

    public function setTraveler(Traveler $traveler): void
    {
        $this->traveler = $traveler;
    }

    public function getVisitedCities(): Collection
    {
        return $this->visitedCities;
    }

    public function addVisitedCity(City $city): void
    {
        $this->visitedCities->add($city);
    }

    public function removeVisitedCity(City $city): void
    {
        $this->visitedCities->removeElement($city);
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }
}
