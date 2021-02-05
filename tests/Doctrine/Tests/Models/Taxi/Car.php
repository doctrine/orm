<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Taxi;

/**
 * @Entity
 * @Table(name="taxi_car")
 */
class Car
{
    /**
     * @Id
     * @Column(type="string", length=25)
     * @GeneratedValue(strategy="NONE")
     */
    private $brand;

    /** @Column(type="string", length=255); */
    private $model;

    /** @OneToMany(targetEntity="Ride", mappedBy="car") */
    private $freeCarRides;

    /** @OneToMany(targetEntity="PaidRide", mappedBy="car") */
    private $carRides;

    public function getBrand()
    {
        return $this->brand;
    }

    public function setBrand($brand): void
    {
        $this->brand = $brand;
    }

    public function setModel($model): void
    {
        $this->model = $model;
    }
}
