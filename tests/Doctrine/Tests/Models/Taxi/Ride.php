<?php

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
     * @Id
     * @ManyToOne(targetEntity="Driver", inversedBy="freeDriverRides")
     * @JoinColumn(name="driver_id", referencedColumnName="id")
     */
    private $driver;

    /**
     * @Id
     * @ManyToOne(targetEntity="Car", inversedBy="freeCarRides")
     * @JoinColumn(name="car", referencedColumnName="brand")
     */
    private $car;

    public function __construct(Driver $driver, Car $car)
    {
        $this->driver = $driver;
        $this->car = $car;
    }
}
