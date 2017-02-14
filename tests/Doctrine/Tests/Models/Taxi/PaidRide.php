<?php

namespace Doctrine\Tests\Models\Taxi;

use Doctrine\ORM\Annotation as ORM;

/**
 * Same as Ride but with an extra column that is not part of the composite primary key
 *
 * @ORM\Entity
 * @ORM\Table(name="taxi_paid_ride")
 */
class PaidRide
{
    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Driver", inversedBy="driverRides")
     * @ORM\JoinColumn(name="driver_id", referencedColumnName="id")
     */
    private $driver;

    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Car", inversedBy="carRides")
     * @ORM\JoinColumn(name="car", referencedColumnName="brand")
     */
    private $car;

    /**
     * @ORM\Column(type="decimal", precision=6, scale=2)
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
