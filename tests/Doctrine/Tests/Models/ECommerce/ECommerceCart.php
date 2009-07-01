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
    private $id;

    /**
     * @Column(type="string", length=50)
     */
    private $payment;

    /**
     * @OneToOne(targetEntity="ECommerceCustomer")
     * @JoinColumn(name="customer_id", referencedColumnName="id")
     */
    private $customer;

    /**
     * @ManyToMany(targetEntity="ECommerceProduct", cascade={"save"})
     * @JoinTable(name="ecommerce_carts_products",
            joinColumns={{"name"="cart_id", "referencedColumnName"="id"}},
            inverseJoinColumns={{"name"="product_id", "referencedColumnName"="id"}})
     */
    private $products;
    
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
            if ($customer->getCart() !== null) {
                $customer->removeCart();
            }
        }
    }
    
    public function getCustomer() {
        return $this->customer;
    }

}
