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

#[Table(name: 'taxi_car')]
#[Entity]
class Car
{
    #[Id]
    #[Column(type: 'string', length: 25)]
    #[GeneratedValue(strategy: 'NONE')]
    private string|null $brand = null;

    #[Column(type: 'string', length: 255)]
    private string|null $model = null;

    /** @phpstan-var Collection<int, Ride> */
    #[OneToMany(targetEntity: 'Ride', mappedBy: 'car')]
    private $freeCarRides;

    /** @phpstan-var Collection<int, PaidRide> */
    #[OneToMany(targetEntity: 'PaidRide', mappedBy: 'car')]
    private $carRides;

    public function getBrand(): string
    {
        return $this->brand;
    }

    public function setBrand(string $brand): void
    {
        $this->brand = $brand;
    }

    public function setModel(string $model): void
    {
        $this->model = $model;
    }
}
