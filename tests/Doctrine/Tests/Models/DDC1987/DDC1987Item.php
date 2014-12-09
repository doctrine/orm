<?php

namespace Doctrine\Tests\Models\DDC1987;

/**
 * @Table(name="ddc1987_item")
 * @Entity
 */
class DDC1987Item
{
    const CLASSNAME = __CLASS__;

    /**
     * @Id
     * @GeneratedValue
     * @Column(type="integer")
     */
    public $id;

    /**
     * @Column(type="decimal", scale=4, precision=15, nullable=false)
     */
    public $price;

    /**
     * @ManyToOne(targetEntity="DDC1987Order", inversedBy="items")
     * @JoinColumn(name="order_id", referencedColumnName="order_id", onDelete="CASCADE", nullable=false)
     */
    public $order;

    public function __construct(DDC1987Order $order, $price)
    {
        $this->order = $order;
        $this->price = $price;
    }
}