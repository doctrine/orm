<?php

namespace Shitty\Tests\Models\Cache;

use Shitty\Common\Collections\ArrayCollection;

/**
 * @Cache
 * @Entity
 * @Table("cache_travel")
 */
class Travel
{
    const CLASSNAME = __CLASS__;

    /**
     * @Id
     * @GeneratedValue
     * @Column(type="integer")
     */
    protected $id;

    /**
     * @Column(type="date")
     */
    protected $createdAt;

    /**
     * @Cache
     * @ManyToOne(targetEntity="Traveler", inversedBy="travels")
     * @JoinColumn(name="traveler_id", referencedColumnName="id")
     */
    protected $traveler;

    /**
     * @Cache
     *
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
        $this->createdAt     = new \DateTime('now');
        $this->visitedCities = new ArrayCollection();
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return \Shitty\Tests\Models\Cache\Traveler
     */
    public function getTraveler()
    {
        return $this->traveler;
    }

    /**
     * @param \Shitty\Tests\Models\Cache\Traveler $traveler
     */
    public function setTraveler(Traveler $traveler)
    {
        $this->traveler = $traveler;
    }

    /**
     * @return \Shitty\Common\Collections\ArrayCollection
     */
    public function getVisitedCities()
    {
        return $this->visitedCities;
    }

    /**
     * @param \Shitty\Tests\Models\Cache\City $city
     */
    public function addVisitedCity(City $city)
    {
        $this->visitedCities->add($city);
    }

    /**
     * @param \Shitty\Tests\Models\Cache\City $city
     */
    public function removeVisitedCity(City $city)
    {
        $this->visitedCities->removeElement($city);
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }
}