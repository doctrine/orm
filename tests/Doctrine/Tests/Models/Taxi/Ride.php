<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Taxi;

/**
 * Test model that contains only Id-columns
 *
 * @Entity
 * @Table(name="taxi_ride")
 */
class Ride
{
    /**
     * @var Driver
     * @Id
     * @ManyToOne(targetEntity="Driver", inversedBy="freeDriverRides")
     * @JoinColumn(name="driver_id", referencedColumnName="id")
     */
    private $driver;

    /**
     * @var Car
     * @Id
     * @ManyToOne(targetEntity="Car", inversedBy="freeCarRides")
     * @JoinColumn(name="car", referencedColumnName="brand")
     */
    private $car;

    public function __construct(Driver $driver, Car $car)
    {
        $this->driver = $driver;
        $this->car    = $car;
    }
}
