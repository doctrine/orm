<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Navigation;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\InverseJoinColumn;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\JoinTable;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\Table;

#[Table(name: 'navigation_pois')]
#[Entity]
class NavPointOfInterest
{
    #[Id]
    #[Column(type: 'integer', name: 'nav_long')]
    private int $long;

    #[Id]
    #[Column(type: 'integer', name: 'nav_lat')]
    private int $lat;

    /** @phpstan-var Collection<int, NavUser> */
    #[JoinTable(name: 'navigation_pois_visitors')]
    #[JoinColumn(name: 'poi_long', referencedColumnName: 'nav_long')]
    #[JoinColumn(name: 'poi_lat', referencedColumnName: 'nav_lat')]
    #[InverseJoinColumn(name: 'user_id', referencedColumnName: 'id')]
    #[ManyToMany(targetEntity: 'NavUser', cascade: ['persist'])]
    private $visitors;

    public function __construct(
        int $lat,
        int $long,
        #[Column(type: 'string', length: 255)]
        private string $name,
        #[ManyToOne(targetEntity: 'NavCountry', inversedBy: 'pois')]
        private NavCountry $country,
    ) {
        $this->lat      = $lat;
        $this->long     = $long;
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

    /** @phpstan-var Collection<int, NavUser> */
    public function getVisitors(): Collection
    {
        return $this->visitors;
    }
}
