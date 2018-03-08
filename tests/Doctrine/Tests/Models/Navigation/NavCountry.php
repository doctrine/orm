<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Navigation;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="navigation_countries")
 */
class NavCountry
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    private $id;

    /** @ORM\Column(type="string") */
    private $name;

    /** @ORM\OneToMany(targetEntity=NavPointOfInterest::class, mappedBy="country") */
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
