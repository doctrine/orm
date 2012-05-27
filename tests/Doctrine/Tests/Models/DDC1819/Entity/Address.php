<?php

namespace Doctrine\Tests\Models\DDC1819\Entity;

/**
 * This entity represents an address. 
 *
 * @Entity
 * @Table(name="ddc1819_address")
 */
class Address
{
    /**
     * @Column(type="integer")
     * @Id @GeneratedValue
     */
    private $id;

    /**
     * @Column(length=100)
     */
    private $street;

    /**
     * @Column(length=10)
     */
    private $number;

    /**
     * @Column(length=50)
     */
    private $city;

    /**
     * @Column(length=10)
     */
    private $code;

    public function __construct($street, $number, $city, $code)
    {
        $this->street = $street;
        $this->number = $number;
        $this->city = $city;
        $this->code = $code;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getStreet()
    {
        return $this->street;
    }

    public function getNumber()
    {
        return $this->number;
    }

    public function getCity()
    {
        return $this->city;
    }

    public function getCode()
    {
        return $this->code;
    }
}
