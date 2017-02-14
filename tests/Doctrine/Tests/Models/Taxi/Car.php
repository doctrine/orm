<?php

namespace Doctrine\Tests\Models\Taxi;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="taxi_car")
 */
class Car
{
    /**
     * @ORM\Id
     * @ORM\Column(type="string", length=25)
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $brand;

    /**
     * @ORM\Column(type="string", length=255);
     */
    private $model;

    /**
     * @ORM\OneToMany(targetEntity="Ride", mappedBy="car")
     */
    private $freeCarRides;

    /**
     * @ORM\OneToMany(targetEntity="PaidRide", mappedBy="car")
     */
    private $carRides;
    
    public function getBrand() 
    {
        return $this->brand;
    }

    public function setBrand($brand)
    {
        $this->brand = $brand;
    }

    public function setModel($model)
    {
        $this->model = $model;
    }
}
