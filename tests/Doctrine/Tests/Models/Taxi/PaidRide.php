<?php

namespace Doctrine\Tests\Models\Taxi;

/**
 * Same as Ride but with an extra column that is not part of the composite primary key
 *
 * @Entity
 * @Table(name="taxi_paid_ride")
 */
class PaidRide
{
    /**
     * @Id
     * @ManyToOne(targetEntity="Driver", inversedBy="driverRides")
     * @JoinColumn(name="driver_id", referencedColumnName="id")
     */
    private $driver;

    /**
     * @Id
     * @ManyToOne(targetEntity="Car", inversedBy="carRides")
     * @JoinColumn(name="car", referencedColumnName="brand")
     */
    private $car;

    /**
     * @Column(type="decimal", precision=6, scale=2)
     */
    private $fare;

    public function __construct(Driver $driver, Car $car)
    {
        $this->driver = $driver;
        $this->car = $car;
    }

    public function setFare($fare)
    {
        $this->fare = $fare;
    }
}
