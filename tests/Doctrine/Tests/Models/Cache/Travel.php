<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Cache;

use DateTime;
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

    /** @ORM\Column(type="date") */
    protected $createdAt;

    /**
     * @ORM\Cache
     * @ORM\ManyToOne(targetEntity=Traveler::class, inversedBy="travels")
     * @ORM\JoinColumn(name="traveler_id", referencedColumnName="id")
     */
    protected $traveler;

    /**
     * @ORM\Cache
     * @ORM\ManyToMany(targetEntity=City::class, inversedBy="travels", cascade={"persist", "remove"})
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
        $this->createdAt     = new DateTime('now');
        $this->visitedCities = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Traveler
     */
    public function getTraveler()
    {
        return $this->traveler;
    }

    public function setTraveler(Traveler $traveler)
    {
        $this->traveler = $traveler;
    }

    /**
     * @return ArrayCollection
     */
    public function getVisitedCities()
    {
        return $this->visitedCities;
    }

    public function addVisitedCity(City $city)
    {
        $this->visitedCities->add($city);
    }

    public function removeVisitedCity(City $city)
    {
        $this->visitedCities->removeElement($city);
    }

    /**
     * @return DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }
}
