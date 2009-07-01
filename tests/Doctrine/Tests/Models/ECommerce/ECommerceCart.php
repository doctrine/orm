<?php

namespace Doctrine\Tests\Models\ECommerce;

/**
 * ECommerceCart
 * Represents a typical cart of a shopping application.
 *
 * @author Giorgio Sironi
 * @Entity
 * @Table(name="ecommerce_carts")
 */
class ECommerceCart
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
    public $payment;

    /**
     * @OneToOne(targetEntity="ECommerceCustomer")
     * @JoinColumn(name="customer_id", referencedColumnName="id")
     */
    public $customer;
}
