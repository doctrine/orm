<?php

namespace Doctrine\Tests\Models\Cache;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @Cache
 * @Entity
 * @Table("cache_city")
 */
#[ORM\Entity, ORM\Table(name: "cache_city"), ORM\Cache]
class City
{
    /**
     * @Id
     * @GeneratedValue
     * @Column(type="integer")
     */
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: "integer")]
    protected $id;

    /**
     * @Column(unique=true)
     */
    #[ORM\Column(unique: true)]
    protected $name;

    /**
     * @Cache
     * @ManyToOne(targetEntity="State", inversedBy="cities")
     * @JoinColumn(name="state_id", referencedColumnName="id")
     */
    #[ORM\Cache]
    #[ORM\ManyToOne(targetEntity: "State", inversedBy: "citities")]
    #[ORM\JoinColumn(name: "state_id", referencedColumnName: "id")]
    protected $state;

    /**
     * @ManyToMany(targetEntity="Travel", mappedBy="visitedCities")
     */
    #[ORM\ManyToMany(targetEntity: "Travel", mappedBy: "visitedCities")]
    public $travels;

    /**
     * @Cache
     * @OrderBy({"name" = "ASC"})
     * @OneToMany(targetEntity="Attraction", mappedBy="city")
     */
    #[ORM\Cache, ORM\OrderBy(["name" => "ASC"])]
    #[ORM\OneToMany(targetEntity: "Attraction", mappedBy: "city")]
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

    public static function loadMetadata(\Doctrine\ORM\Mapping\ClassMetadataInfo $metadata)
    {
        include __DIR__ . '/../../ORM/Mapping/php/Doctrine.Tests.Models.Cache.City.php';
    }
}
