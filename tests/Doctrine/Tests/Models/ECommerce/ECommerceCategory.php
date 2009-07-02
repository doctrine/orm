<?php

namespace Doctrine\Tests\Models\ECommerce;

/**
 * ECommerceCategory
 * Represents a tag applied on particular products.
 *
 * @author Giorgio Sironi
 * @Entity
 * @Table(name="ecommerce_categories")
 */
class ECommerceCategory
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
     * @ManyToMany(targetEntity="ECommerceProduct", mappedBy="categories")
     */
    public $products;

    public function __construct()
    {
        $this->products = new \Doctrine\Common\Collections\Collection();
    }

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

    public function addProduct(ECommerceProduct $product)
    {
        if (!$this->products->contains($product)) {
            $this->products[] = $product;
            $product->addCategory($this);
        }
    }

    public function removeProduct(ECommerceProduct $product)
    {
        if ($this->products->contains($product)) {
            $this->products->removeElement($product);
            $product->removeCategory($this);
        }
    }

    public function getProducts()
    {
        return $this->products;
    }
}
