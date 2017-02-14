<?php

namespace Doctrine\Tests\Models\Cache;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Cache
 * @ORM\Entity
 * @ORM\Table("cache_travel")
 */
class Travel
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * @ORM\Column(type="date")
     */
    protected $createdAt;

    /**
     * @ORM\Cache
     * @ORM\ManyToOne(targetEntity="Traveler", inversedBy="travels")
     * @ORM\JoinColumn(name="traveler_id", referencedColumnName="id")
     */
    protected $traveler;

    /**
     * @ORM\Cache
     * @ORM\ManyToMany(targetEntity="City", inversedBy="travels", cascade={"persist", "remove"})
     * @ORM\JoinTable(name="cache_visited_cities",
     *  joinColumns={
     *      @ORM\JoinColumn(name="travel_id", referencedColumnName="id")
     *  },
     *  inverseJoinColumns={
     *      @ORM\JoinColumn(name="city_id", referencedColumnName="id")
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
     * @return \Doctrine\Tests\Models\Cache\Traveler
     */
    public function getTraveler()
    {
        return $this->traveler;
    }

    /**
     * @param \Doctrine\Tests\Models\Cache\Traveler $traveler
     */
    public function setTraveler(Traveler $traveler)
    {
        $this->traveler = $traveler;
    }

    /**
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getVisitedCities()
    {
        return $this->visitedCities;
    }

    /**
     * @param \Doctrine\Tests\Models\Cache\City $city
     */
    public function addVisitedCity(City $city)
    {
        $this->visitedCities->add($city);
    }

    /**
     * @param \Doctrine\Tests\Models\Cache\City $city
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
