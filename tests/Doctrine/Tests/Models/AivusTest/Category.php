<?php

namespace Doctrine\Tests\Models\AivusTest;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * @Entity
 * @Table(name="categories")
 */
class Category
{
    /**
     * @Column(type="integer")
     * @Id
     * @GeneratedValue
     *
     * @var int
     */
    protected $id;

    /**
     * @ManyToMany(targetEntity="Product")
     * @JoinTable(name="category_to_products",
     *      joinColumns={@JoinColumn(name="category_id", referencedColumnName="id")},
     *      inverseJoinColumns={@JoinColumn(name="product_id", referencedColumnName="id", unique=true)}
     * )
     */
    protected $products;

    public function __construct()
    {
        $this->products = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Collection|Product[]
     */
    public function getProducts()
    {
        return $this->products;
    }

    /**
     * @param Product $product
     *
     * @return $this
     */
    public function addProduct(Product $product)
    {
        if (!$this->products->contains($product)) {
            $this->products->add($product);
        }

        return $this;
    }

    /**
     * @param Product $product
     *
     * @return $this
     */
    public function removeProduct(Product $product)
    {
        if ($this->products->contains($product)) {
            $this->products->removeElement($product);
        }

        return $this;
    }
}
