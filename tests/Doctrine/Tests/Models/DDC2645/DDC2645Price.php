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
    public $variant;

    /**
     * @Column(type="integer")
     * @Id
     */
    public $type;

    /**
     * @Column(type="string")
     * @Id
     */
    public $country;

    /**
     * @Column(type="float")
     */
    public $value;
}
