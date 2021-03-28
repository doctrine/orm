<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Taxi;

use Doctrine\Common\Collections\Collection;

/**
 * @Entity
 * @Table(name="taxi_car")
 */
class Car
{
    /**
     * @var string
     * @Id
     * @Column(type="string", length=25)
     * @GeneratedValue(strategy="NONE")
     */
    private $brand;

    /**
     * @var string
     * @Column(type="string", length=255);
     */
    private $model;

    /**
     * @psalm-var Collection<int, Ride>
     * @OneToMany(targetEntity="Ride", mappedBy="car")
     */
    private $freeCarRides;

    /**
     * @psalm-var Collection<int, PaidRide>
     * @OneToMany(targetEntity="PaidRide", mappedBy="car")
     */
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
