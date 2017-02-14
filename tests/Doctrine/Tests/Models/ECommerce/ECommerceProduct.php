<?php

namespace Doctrine\Tests\Models\ECommerce;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Annotation as ORM;

/**
 * ECommerceProduct
 * Represents a type of product of a shopping application.
 *
 * @author Giorgio Sironi
 * @ORM\Entity
 * @ORM\Table(name="ecommerce_products",indexes={@ORM\Index(name="name_idx", columns={"name"})})
 */
class ECommerceProduct
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    private $name;

    /**
     * @ORM\OneToOne(targetEntity="ECommerceShipping", cascade={"persist"})
     * @ORM\JoinColumn(name="shipping_id", referencedColumnName="id")
     */
    private $shipping;

    /**
     * @ORM\OneToMany(targetEntity="ECommerceFeature", mappedBy="product", cascade={"persist"})
     */
    private $features;

    /**
     * @ORM\ManyToMany(targetEntity="ECommerceCategory", cascade={"persist"}, inversedBy="products")
     * @ORM\JoinTable(name="ecommerce_products_categories",
     *      joinColumns={@ORM\JoinColumn(name="product_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="category_id", referencedColumnName="id")})
     */
    private $categories;

    /**
     * This relation is saved with two records in the association table for
     * simplicity.
     * @ORM\ManyToMany(targetEntity="ECommerceProduct", cascade={"persist"})
     * @ORM\JoinTable(name="ecommerce_products_related",
     *      joinColumns={@ORM\JoinColumn(name="product_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="related_id", referencedColumnName="id")})
     */
    private $related;

    public $isCloned = false;
    public $wakeUp = false;

    public function __construct()
    {
        $this->features = new ArrayCollection;
        $this->categories = new ArrayCollection;
        $this->related = new ArrayCollection;
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
        $removed = $this->features->removeElement($feature);
        if ($removed) {
            $feature->removeProduct();
        }
        return $removed;
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
        $removed = $this->categories->removeElement($category);
        if ($removed) {
            $category->removeProduct($this);
        }
    }

    public function setCategories($categories)
    {
        $this->categories = $categories;
    }

    public function getCategories()
    {
        return $this->categories;
    }

    public function getRelated()
    {
        return $this->related;
    }

    public function addRelated(ECommerceProduct $related)
    {
        if (!$this->related->contains($related)) {
            $this->related[] = $related;
            $related->addRelated($this);
        }
    }

    public function removeRelated(ECommerceProduct $related)
    {
        $removed = $this->related->removeElement($related);
        if ($removed) {
            $related->removeRelated($this);
        }
    }

    public function __clone()
    {
        $this->isCloned = true;
        if ($this->categories) {
            $this->categories = clone $this->categories;
        }
    }

    /**
     * Testing docblock contents here
     */
    public function __wakeup()
    {
        $this->wakeUp = true;
    }
}
