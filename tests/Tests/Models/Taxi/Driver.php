<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Taxi;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\Table;

#[Table(name: 'taxi_driver')]
#[Entity]
class Driver
{
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue(strategy: 'AUTO')]
    private int $id;

    #[Column(type: 'string', length: 255)]
    private string|null $name = null;

    /** @phpstan-var Collection<int, Ride> */
    #[OneToMany(targetEntity: 'Ride', mappedBy: 'driver')]
    private $freeDriverRides;

    /** @phpstan-var Collection<int, PaidRide> */
    #[OneToMany(targetEntity: 'PaidRide', mappedBy: 'driver')]
    private $driverRides;

    public function getId(): int
    {
        return $this->id;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }
}
