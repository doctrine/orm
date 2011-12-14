<?php

namespace Doctrine\Tests\Models\DDC964;

/**
 * @Entity
 */
class DDC964Address
{

    /**
     * @GeneratedValue
     * @Id @Column(type="integer")
     */
    private $id;

    /**
     * @Column
     */
    private $country;

    /**
     * @Column
     */
    private $zip;

    /**
     * @Column
     */
    private $city;

    /**
     * @Column
     */
    private $street;

    /**
     * @param string $zip
     * @param string $country
     * @param string $city
     * @param string $street
     */
    public function __construct($zip = null, $country = null, $city = null, $street = null)
    {
        $this->zip      = $zip;
        $this->country  = $country;
        $this->city     = $city;
        $this->street   = $street;
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * @param string $country
     */
    public function setCountry($country)
    {
        $this->country = $country;
    }

    /**
     * @return string
     */
    public function getZip()
    {
        return $this->zip;
    }

    /**
     * @param string $zip
     */
    public function setZip($zip)
    {
        $this->zip = $zip;
    }

    /**
     * @return string
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * @param string $city
     */
    public function setCity($city)
    {
        $this->city = $city;
    }

    /**
     * @return string
     */
    public function getStreet()
    {
        return $this->street;
    }

    /**
     * @param string $street
     */
    public function setStreet($street)
    {
        $this->street = $street;
    }

}