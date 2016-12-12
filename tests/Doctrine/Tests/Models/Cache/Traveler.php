<?php

namespace Doctrine\Tests\Models\Cache;

use Doctrine\Common\Collections\ArrayCollection;

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
     * @Cache("NONSTRICT_READ_WRITE")
     * @OneToMany(targetEntity="Travel", mappedBy="traveler", cascade={"persist", "remove"}, orphanRemoval=true)
     *
     * @var \Doctrine\Common\Collections\Collection
     */
    public $travels;

    /**
     * @Cache
     * @OneToOne(targetEntity="TravelerProfile")
     */
     protected $profile;

    /**
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name     = $name;
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

    /**
     * @return \Doctrine\Tests\Models\Cache\TravelerProfile
     */
    public function getProfile()
    {
        return $this->profile;
    }

    /**
     * @param \Doctrine\Tests\Models\Cache\TravelerProfile $profile
     */
    public function setProfile(TravelerProfile $profile)
    {
        $this->profile = $profile;
    }

    public function getTravels()
    {
        return $this->travels;
    }

    /**
     * @param \Doctrine\Tests\Models\Cache\Travel $item
     */
    public function addTravel(Travel $item)
    {
        if ( ! $this->travels->contains($item)) {
            $this->travels->add($item);
        }

        if ($item->getTraveler() !== $this) {
            $item->setTraveler($this);
        }
    }

    /**
     * @param \Doctrine\Tests\Models\Cache\Travel $item
     */
    public function removeTravel(Travel $item)
    {
        $this->travels->removeElement($item);
    }
}
