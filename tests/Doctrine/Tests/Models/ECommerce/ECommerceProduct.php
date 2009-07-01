<?php

namespace Doctrine\Tests\Models\ECommerce;

/**
 * ECommerceProduct
 * Represents a type of product of a shopping application.
 *
 * @author Giorgio Sironi
 * @Entity
 * @Table(name="ecommerce_products")
 */
class ECommerceProduct
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
    private $name;

    /**
     * @ManyToMany(targetEntity="ECommerceCategory", cascade={"save"})
     * @JoinTable(name="ecommerce_products_categories",
            joinColumns={{"name"="product_id", "referencedColumnName"="id"}},
            inverseJoinColumns={{"name"="category_id", "referencedColumnName"="id"}})
    private $categories;
     */

    /**
     * @OneToOne(targetEntity="ECommerceShipping", cascade={"save"})
     * @JoinColumn(name="shipping_id", referencedColumnName="id")
     */
    private $shipping;

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getPrice()
    {
        return $this->price;
    }

    public function setPrice($price)
    {
        $this->price = $price;
    }

    public function getShipping()
    {
        return $this->shipping;
    }

    public function setShipping(ECommerceShipping $shipping)
    {
        $this->shipping = $shipping;
    }

    public function removeShipping()
    {
        $this->shipping = null;
    }
}
