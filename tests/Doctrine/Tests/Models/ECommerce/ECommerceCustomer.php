<?php

namespace Doctrine\Tests\Models\ECommerce;

/**
 * ECommerceCustomer
 * Represents a registered user of a shopping application.
 *
 * @author Giorgio Sironi
 * @Entity
 * @Table(name="ecommerce_customers")
 */
class ECommerceCustomer
{
    /**
     * @Column(type="integer")
     * @Id
     * @GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @Column(type="string", length=50)
     */
    public $name;

    /**
     * @OneToOne(targetEntity="ECommerceCart", mappedBy="customer", cascade={"save"})
     */
    public $cart;

    public function setCart(ECommerceCart $cart)
    {
        $this->cart = $cart;
        $cart->customer = $this;
    }

    public function removeCart()
    {
        $this->cart->customer = null;
        $this->cart = null;
    }
}
