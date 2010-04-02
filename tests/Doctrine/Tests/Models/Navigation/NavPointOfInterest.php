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
     * @ManyToOne(targetEntity="NavCountry")
     */
    private $country;

    public function __construct($lat, $long, $name, $country)
    {
        $this->lat = $lat;
        $this->long = $long;
        $this->name = $name;
        $this->country = $country;
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
}