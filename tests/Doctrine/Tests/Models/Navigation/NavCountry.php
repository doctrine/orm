<?php

namespace Doctrine\Tests\Models\Navigation;

/**
 * @Entity
* @Table(name="navigation_countries")
 */
class NavCountry
{
    /**
     * @Id
     * @Column(type="integer")
     * @generatedValue
     */
    private $id;

    /**
     * @Column(type="string")
     */
    private $name;

    /**
     * @OneToMany(targetEntity="NavPointOfInterest", mappedBy="country")
     */
    private $pois;

    function __construct($name) {
        $this->name = $name;
    }

    public function getId() {
        return $this->id;
    }

    public function getName() {
        return $this->name;
    }
}