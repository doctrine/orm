<?php

namespace Doctrine\Tests\Models\Taxi;

/**
 * @Entity
 * @Table(name="taxi_driver")
 */
class Driver
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @Column(type="string", length=255);
     */
    private $name;

    /**
     * @OneToMany(targetEntity="Ride", mappedBy="driver")
     */
    private $freeDriverRides;

    /**
     * @OneToMany(targetEntity="PaidRide", mappedBy="driver")
     */
    private $driverRides;

    public function setName($name)
    {
        $this->name = $name;
    }
}
