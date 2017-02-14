<?php

namespace Doctrine\Tests\Models\ECommerce;

use Doctrine\ORM\Annotation as ORM;

/**
 * Describes a product feature.
 *
 * @author Giorgio Sironi
 * @ORM\Entity
 * @ORM\Table(name="ecommerce_features")
 */
class ECommerceFeature
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @ORM\Column(length=50)
     */
    private $description;

    /**
     * @ORM\ManyToOne(targetEntity="ECommerceProduct", inversedBy="features")
     * @ORM\JoinColumn(name="product_id", referencedColumnName="id")
     */
    private $product;

    public function getId() {
        return $this->id;
    }

    public function getDescription() {
        return $this->description;
    }

    public function setDescription($description) {
        $this->description = $description;
    }

    public function setProduct(ECommerceProduct $product) {
        $this->product = $product;
    }

    public function removeProduct() {
        if ($this->product !== null) {
            $product = $this->product;
            $this->product = null;
            $product->removeFeature($this);
        }
    }

    public function getProduct() {
        return $this->product;
    }
}
