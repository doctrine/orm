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
     * @var int
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

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
