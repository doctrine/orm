<?php

namespace Doctrine\Tests\Models\Cache;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Annotation as ORM;
use Doctrine\ORM\Mapping;

/**
 * @ORM\Cache
 * @ORM\Entity
 * @ORM\Table("cache_city")
 */
class City
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * @ORM\Column(unique=true)
     */
    protected $name;

    /**
     * @ORM\Cache
     * @ORM\ManyToOne(targetEntity="State", inversedBy="cities")
     * @ORM\JoinColumn(name="state_id", referencedColumnName="id")
     */
    protected $state;

     /**
     * @ORM\ManyToMany(targetEntity="Travel", mappedBy="visitedCities")
     */
    public $travels;

     /**
     * @ORM\Cache
     * @ORM\OrderBy({"name" = "ASC"})
     * @ORM\OneToMany(targetEntity="Attraction", mappedBy="city")
     */
    public $attractions;

    public function __construct($name, State $state = null)
    {
        $this->name         = $name;
        $this->state        = $state;
        $this->travels      = new ArrayCollection();
        $this->attractions  = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getState()
    {
        return $this->state;
    }

    public function setState(State $state)
    {
        $this->state = $state;
    }

    public function addTravel(Travel $travel)
    {
        $this->travels[] = $travel;
    }

    public function getTravels()
    {
        return $this->travels;
    }

    public function addAttraction(Attraction $attraction)
    {
        $this->attractions[] = $attraction;
    }

    public function getAttractions()
    {
        return $this->attractions;
    }

    public static function loadMetadata(Mapping\ClassMetadata $metadata)
    {
        include __DIR__ . '/../../ORM/Mapping/php/Doctrine.Tests.Models.Cache.City.php';
    }
}
