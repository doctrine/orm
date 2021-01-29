<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Cache;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

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

    /** @Column */
    protected $name;

    /**
     * @Cache("NONSTRICT_READ_WRITE")
     * @OneToMany(targetEntity="Travel", mappedBy="traveler", cascade={"persist", "remove"}, orphanRemoval=true)
     * @var Collection
     */
    public $travels;

    /**
     * @Cache
     * @OneToOne(targetEntity="TravelerProfile")
     */
     protected $profile;

    public function __construct(string $name)
    {
        $this->name    = $name;
        $this->travels = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function setId($id): void
    {
        $this->id = $id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name): void
    {
        $this->name = $name;
    }

    public function getProfile(): TravelerProfile
    {
        return $this->profile;
    }

    public function setProfile(TravelerProfile $profile): void
    {
        $this->profile = $profile;
    }

    public function getTravels()
    {
        return $this->travels;
    }

    public function addTravel(Travel $item): void
    {
        if (! $this->travels->contains($item)) {
            $this->travels->add($item);
        }

        if ($item->getTraveler() !== $this) {
            $item->setTraveler($this);
        }
    }

    public function removeTravel(Travel $item): void
    {
        $this->travels->removeElement($item);
    }
}
