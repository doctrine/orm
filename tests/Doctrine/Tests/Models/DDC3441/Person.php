<?php

namespace Doctrine\Tests\Models\DDC3441;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Entity
 * @Table(name="DDC3441_people")
 */
class Person
{
    /**
     * @var int
     * @Id
     * @Column(name="id", type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var ArrayCollection
     * @ManyToOne(targetEntity="Car")
     * @JoinColumn(name="carId", referencedColumnName="id")
     */
    private $car;


    public function getId()
    {
        return $this->id;
    }

    public function getCar()
    {
        return $this->car;
    }

    public function setCar(Car $car)
    {
        $this->car = $car;

        return $this;
    }
}