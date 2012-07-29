<?php

namespace Doctrine\Tests\Models\Navigation;

/**
 * @Entity
 * @Table(name="navigation_pois")
 */
class NavPointOfInterest
{
    /**
     * @Id
     * @Column(type="integer", name="nav_long")
     */
    private $long;

    /**
     * @Id
     * @Column(type="integer", name="nav_lat")
     */
    private $lat;

    /**
     * @Column(type="string")
     */
    private $name;

    /**
     * @ManyToOne(targetEntity="NavCountry", inversedBy="pois")
     */
    private $country;

     /**
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

    public function __construct($lat, $long, $name, $country)
    {
        $this->lat = $lat;
        $this->long = $long;
        $this->name = $name;
        $this->country = $country;
        $this->visitors = new \Doctrine\Common\Collections\ArrayCollection;
    }

    public function getLong() {
        return $this->long;
    }

    public function getLat() {
        return $this->lat;
    }

    public function getName() {
        return $this->name;
    }

    public function getCountry() {
        return $this->country;
    }

    public function addVisitor(NavUser $user)
    {
        $this->visitors[] = $user;
    }

    public function getVisitors()
    {
        return $this->visitors;
    }
}
