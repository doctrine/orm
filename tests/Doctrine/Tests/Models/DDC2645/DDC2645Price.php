<?php

namespace Doctrine\Tests\Models\DDC2645;


/**
 * @Entity
 * @Table(name="ddc2645_price")
 */
class DDC2645Price
{
    /**
     * @ManyToOne(targetEntity="DDC2645Variant", inversedBy="prices")
     * @JoinColumn(name="productVariant")
     * @Id
     */
    private $variant;

    /**
     * @Column(type="integer")
     * @Id
     */
    private $type;

    /**
     * @Column(type="string")
     * @Id
     */
    private $country;

    /**
     * @Column(type="float")
     */
    private $value;

    /**
     * @param mixed $country
     */
    public function setCountry($country)
    {
        $this->country = $country;
    }

    /**
     * @return mixed
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * @param mixed $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param mixed $variant
     */
    public function setVariant($variant)
    {
        $this->variant = $variant;
    }

    /**
     * @return mixed
     */
    public function getVariant()
    {
        return $this->variant;
    }

    /**
     * @param mixed $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }
}
