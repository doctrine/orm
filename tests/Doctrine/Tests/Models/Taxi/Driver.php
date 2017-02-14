<?php

namespace Doctrine\Tests\Models\Taxi;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="taxi_driver")
 */
class Driver
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255);
     */
    private $name;

    /**
     * @ORM\OneToMany(targetEntity="Ride", mappedBy="driver")
     */
    private $freeDriverRides;

    /**
     * @ORM\OneToMany(targetEntity="PaidRide", mappedBy="driver")
     */
    private $driverRides;
    
    public function getId()
    {
        return $this->id;
    }

    public function setName($name)
    {
        $this->name = $name;
    }
}
