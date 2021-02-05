<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Navigation;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Entity
 * @Table(name="navigation_pois")
 */
class NavPointOfInterest
{
    /**
     * @var int
     * @Id
     * @Column(type="integer", name="nav_long")
     */
    private $long;

    /**
     * @var int
     * @Id
     * @Column(type="integer", name="nav_lat")
     */
    private $lat;

    /**
     * @var string
     * @Column(type="string")
     */
    private $name;

    /**
     * @var NavCountry
     * @ManyToOne(targetEntity="NavCountry", inversedBy="pois")
     */
    private $country;

     /**
      * @var Collection<NavUser>
      * @ManyToMany(targetEntity="NavUser", cascade={"persist"})
      * @JoinTable(name="navigation_pois_visitors",
      *      inverseJoinColumns={@JoinColumn(name="user_id", referencedColumnName="id")},
      *      joinColumns={
      *          @JoinColumn(name="poi_long", referencedColumnName="nav_long"),
      *          @JoinColumn(name="poi_lat", referencedColumnName="nav_lat")
      *      }
      * )
      */
    private $visitors;

    public function __construct(int $lat, int $long, string $name, NavCountry $country)
    {
        $this->lat      = $lat;
        $this->long     = $long;
        $this->name     = $name;
        $this->country  = $country;
        $this->visitors = new ArrayCollection();
    }

    public function getLong(): int
    {
        return $this->long;
    }

    public function getLat(): int
    {
        return $this->lat;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCountry(): NavCountry
    {
        return $this->country;
    }

    public function addVisitor(NavUser $user): void
    {
        $this->visitors[] = $user;
    }

    public function getVisitors()
    {
        return $this->visitors;
    }
}
