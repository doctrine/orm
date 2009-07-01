<?php

namespace Doctrine\Tests\Models\ECommerce;

/**
 * ECommerceShipping
 * Represents a shipping method.
 *
 * @author Giorgio Sironi
 * @Entity
 * @Table(name="ecommerce_shippings")
 */
class ECommerceShipping
{
    /**
     * @Column(type="integer")
     * @Id
     * @GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @Column(type="integer")
     */
    private $days;

    public function getId()
    {
        return $this->id;
    }

    public function getDays()
    {
        return $this->days;
    }

    public function setDays($days)
    {
        $this->days = $days;
    }
}
