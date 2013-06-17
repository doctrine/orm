<?php

namespace Doctrine\Tests\Models\Cache;

/**
 * @Cache
 * @Entity
 * @Table("cache_attraction")
 * @InheritanceType("SINGLE_TABLE")
 * @DiscriminatorMap({
 *  1  = "Restaurant",
 *  2  = "Beach",
 *  3  = "Bar"
 * })
 */
abstract class Attraction
{
    const CLASSNAME = __CLASS__;

    /**
     * @Id
     * @GeneratedValue
     * @Column(type="integer")
     */
    protected $id;

    /**
     * @Column(unique=true)
     */
    protected $name;

    /**
     * @Cache
     * @ManyToOne(targetEntity="City", inversedBy="attractions")
     * @JoinColumn(name="city_id", referencedColumnName="id")
     */
    protected $city;

    public function __construct($name, City $city)
    {
        $this->name = $name;
        $this->city = $city;
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

    public function getCity()
    {
        return $this->city;
    }

    public function setCity(City $city)
    {
        $this->city = $city;
    }
}