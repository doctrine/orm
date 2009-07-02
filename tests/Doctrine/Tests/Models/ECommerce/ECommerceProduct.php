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
     * @Column(type="string", length=50, nullable="true")
     */
    private $name;

    /**
     * @OneToOne(targetEntity="ECommerceShipping", cascade={"save"})
     * @JoinColumn(name="shipping_id", referencedColumnName="id")
     */
    private $shipping;

    /**
     * @OneToMany(targetEntity="ECommerceFeature", mappedBy="product", cascade={"save"})
     */
    private $features;

    /**
     * @ManyToMany(targetEntity="ECommerceCategory", cascade={"save"})
     * @JoinTable(name="ecommerce_products_categories",
            joinColumns={{"name"="product_id", "referencedColumnName"="id"}},
            inverseJoinColumns={{"name"="category_id", "referencedColumnName"="id"}})
     */
    private $categories;

    public function __construct()
    {
        $this->features = new \Doctrine\Common\Collections\Collection;
        $this->categories = new \Doctrine\Common\Collections\Collection;
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

    public function getFeatures()
    {
        return $this->features;
    }

    public function addFeature(ECommerceFeature $feature)
    {
        $this->features[] = $feature;
        $feature->setProduct($this);
    }

    /** does not set the owning side */
    public function brokenAddFeature(ECommerceFeature $feature)
    {
        $this->features[] = $feature;
    }

    public function removeFeature(ECommerceFeature $feature)
    {
        if ($this->features->contains($feature)) {
            $removed = $this->features->removeElement($feature);
            if ($removed) {
                $feature->removeProduct();
                return true;
            }
        }
        return false;
    }

    public function addCategory(ECommerceCategory $category)
    {
        if (!$this->categories->contains($category)) {
            $this->categories[] = $category;
            $category->addProduct($this);
        }
    }

    public function removeCategory(ECommerceCategory $category)
    {
        if ($this->categories->contains($category)) {
            $this->categories->removeElement($category);
            $category->removeProduct($this);
        }
    }

    public function getCategories()
    {
        return $this->categories;
    }
}
