<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Navigation;

use Doctrine\Common\Collections\Collection;

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
     * @var string
     * @Column(type="string")
     */
    private $name;

    /**
     * @psalm-var Collection<int, NavPointOfInterest>
     * @OneToMany(targetEntity="NavPointOfInterest", mappedBy="country")
     */
    private $pois;

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }
}
