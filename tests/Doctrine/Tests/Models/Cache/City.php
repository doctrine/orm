<?php

namespace Doctrine\Tests\Models\Cache;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Cache
 * @Entity
 * @Table("cache_city")
 */
class City
{
    /**
     * @Id
     * @GeneratedValue
     * @Column(type="integer")
     */
    protected $id;

    /**
     * @Column
     */
    protected $name;

    /**
     * @Cache
     * @ManyToOne(targetEntity="State", inversedBy="cities")
     * @JoinColumn(name="state_id", referencedColumnName="id")
     */
    protected $state;

     /**
     * @ManyToMany(targetEntity="Travel", mappedBy="visitedCities")
     */
    public $travels;

    public function __construct($name, State $state)
    {
        $this->name     = $name;
        $this->state    = $state;
        $this->travels  = new ArrayCollection();
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
}