<?php

namespace Doctrine\Tests\Models\DDC1987;

/**
 * @Table(name="ddc1987_order")
 * @Entity
 */
class DDC1987Order
{
    const CLASSNAME = __CLASS__;

    /**
     * @Id
     * @GeneratedValue
     * @Column(type="integer", name="order_id")
     */
    public $id;

    /**
     * @OneToMany(targetEntity="DDC1987Item", mappedBy="order", cascade={"refresh", "persist"})
     */
    protected $items;

    public function __construct($id)
    {
        $this->id = $id;
        $this->items = new \Doctrine\Common\Collections\ArrayCollection();
    }

    public function getItems()
    {
        return $this->items;
    }
}