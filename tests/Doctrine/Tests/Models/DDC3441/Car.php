<?php

namespace Doctrine\Tests\Models\DDC3441;

/**
 * @Entity
 * @Table(name="DDC3441_cars")
 */
class Car 
{
    /**
     * @var int
     * @Id
     * @Column(name="id", type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     * @Column(name="make", type="string")
     */
    private $make;

    /**
     * @var string
     * @Column(name="model", type="string")
     */
    private $model;

    /**
     * @var int
     * @Column(name="year", type="integer")
     */
    private $year;

    /**
     * @param string $make
     * @param string $model
     * @param int    $year
     */
    public function __construct($make = 'dodge', $model = 'neon', $year = 2003)
    {
        $this->make  = $make;
        $this->model = $model;
        $this->year  = $year;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $make
     */
    public function setMake($make)
    {
        $this->make = $make;
    }

    /**
     * @return string
     */
    public function getMake()
    {
        return $this->make;
    }

    /**
     * @param string $model
     */
    public function setModel($model)
    {
        $this->model = $model;
    }

    /**
     * @return string
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * @param int $year
     */
    public function setYear($year)
    {
        $this->year = $year;
    }

    /**
     * @return int
     */
    public function getYear()
    {
        return $this->year;
    }
}