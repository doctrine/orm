<?php

namespace Doctrine\Tests\Models\Cache;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Entity
 * @Table("cache_state")
 * @Cache("NONSTRICT_READ_WRITE")
 */
class State
{
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
     * @ManyToOne(targetEntity="Country")
     * @JoinColumn(name="country_id", referencedColumnName="id")
     */
    protected $country;

    /**
     * @Cache("NONSTRICT_READ_WRITE")
     * @OneToMany(targetEntity="City", mappedBy="state")
     */
    protected $cities;

    public function __construct($name, Country $country = null)
    {
        $this->name     = $name;
        $this->country  = $country;
        $this->cities   = new ArrayCollection();
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

    public function getCountry()
    {
        return $this->country;
    }

    public function setCountry(Country $country)
    {
        $this->country = $country;
    }

    public function getCities()
    {
        return $this->cities;
    }

    public function setCities(ArrayCollection $cities)
    {
        $this->cities = $cities;
    }

    public function addCity(City $city)
    {
        $this->cities[] = $city;
    }
}
