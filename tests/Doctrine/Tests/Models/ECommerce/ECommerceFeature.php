<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\ECommerce;

/**
 * Describes a product feature.
 *
 * @Entity
 * @Table(name="ecommerce_features")
 */
class ECommerceFeature
{
    /**
     * @var int
     * @Column(type="integer")
     * @Id
     * @GeneratedValue
     */
    private $id;

    /**
     * @var string|null
     * @Column(length=50)
     */
    private $description;

    /**
     * @var ECommerceProduct|null
     * @ManyToOne(targetEntity="ECommerceProduct", inversedBy="features")
     * @JoinColumn(name="product_id", referencedColumnName="id")
     */
    private $product;

    public function getId(): int
    {
        return $this->id;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function setProduct(ECommerceProduct $product): void
    {
        $this->product = $product;
    }

    public function removeProduct(): void
    {
        if ($this->product !== null) {
            $product       = $this->product;
            $this->product = null;
            $product->removeFeature($this);
        }
    }

    public function getProduct(): ?ECommerceProduct
    {
        return $this->product;
    }
}
