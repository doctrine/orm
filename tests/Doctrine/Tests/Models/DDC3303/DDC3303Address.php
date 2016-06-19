<?php
namespace Doctrine\Tests\Models\DDC3303;

/**
 * @Embeddable
 */
class DDC3303Address
{
    /**
     * @Column(type="string")
     *
     * @var string
     */
    private $street;

    /**
     * @Column(type="integer")
     *
     * @var int
     */
    private $number;

    /**
     * @Column(type="string")
     *
     * @var string
     */
    private $city;

    public function __construct($street, $number, $city)
    {
        $this->street = $street;
        $this->number = $number;
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
     * @return mixed
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * @return string
     */
    public function getCity()
    {
        return $this->city;
    }
}
