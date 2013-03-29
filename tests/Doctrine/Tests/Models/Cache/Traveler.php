<?php

namespace Doctrine\Tests\Models\Cache;

/**
 * @Cache
 * @Entity
 * @Table("cache_traveler")
 */
class Traveler
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
     * @OneToMany(targetEntity="Travel", mappedBy="traveler")
     */
    public $travels;

    /**
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name = $name;
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

    public function getTravels()
    {
        return $this->travels;
    }
}