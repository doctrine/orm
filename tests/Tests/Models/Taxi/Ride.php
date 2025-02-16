<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Taxi;

use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\Table;

/**
 * Test model that contains only Id-columns
 */
#[Table(name: 'taxi_ride')]
#[Entity]
class Ride
{
    public function __construct(
        #[Id]
        #[ManyToOne(targetEntity: 'Driver', inversedBy: 'freeDriverRides')]
        #[JoinColumn(name: 'driver_id', referencedColumnName: 'id')]
        private Driver $driver,
        #[Id]
        #[ManyToOne(targetEntity: 'Car', inversedBy: 'freeCarRides')]
        #[JoinColumn(name: 'car', referencedColumnName: 'brand')]
        private Car $car,
    ) {
    }
}
