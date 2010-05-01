<?php

namespace Doctrine\Tests\Models\ECommerce;

use Doctrine\Common\Collections\ArrayCollection;

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
     * @GeneratedValue
     */
    private $id;

    /**
     * @Column(length=50, nullable=true)
     */
    private $payment;

    /**
     * @OneToOne(targetEntity="ECommerceCustomer", inversedBy="cart")
     * @JoinColumn(name="customer_id", referencedColumnName="id")
     */
    private $customer;

    /**
     * @ManyToMany(targetEntity="ECommerceProduct", cascade={"persist"})
     * @JoinTable(name="ecommerce_carts_products",
            joinColumns={@JoinColumn(name="cart_id", referencedColumnName="id")},
            inverseJoinColumns={@JoinColumn(name="product_id", referencedColumnName="id")})
     */
    private $products;

    public function __construct()
    {
        $this->products = new ArrayCollection;
    }
    
    public function getId() {
        return $this->id;
    }
    
    public function getPayment() {
        return $this->payment;
    }
    
    public function setPayment($payment) {
        $this->payment = $payment;
    }
    
    public function setCustomer(ECommerceCustomer $customer) {
        if ($this->customer !== $customer) {
            $this->customer = $customer;
            $customer->setCart($this);
        }
    }
    
    public function removeCustomer() {
        if ($this->customer !== null) {
            $customer = $this->customer;
            $this->customer = null;
            $customer->removeCart();
        }
    }
    
    public function getCustomer() {
        return $this->customer;
    }

    public function getProducts()
    {
        return $this->products;
    }

    public function addProduct(ECommerceProduct $product) {
        $this->products[] = $product;
    }

    public function removeProduct(ECommerceProduct $product) {
        return $this->products->removeElement($product);
    }
}
