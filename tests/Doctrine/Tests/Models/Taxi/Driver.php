<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Taxi;

use Doctrine\Common\Collections\Collection;

/**
 * @Entity
 * @Table(name="taxi_driver")
 */
class Driver
{
    /**
     * @var int
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     * @Column(type="string", length=255);
     */
    private $name;

    /**
     * @psalm-var Collection<int, Ride>
     * @OneToMany(targetEntity="Ride", mappedBy="driver")
     */
    private $freeDriverRides;

    /**
     * @psalm-var Collection<int, PaidRide>
     * @OneToMany(targetEntity="PaidRide", mappedBy="driver")
     */
    private $driverRides;

    public function getId()
    {
        return $this->id;
    }

    public function setName($name): void
    {
        $this->name = $name;
    }
}
