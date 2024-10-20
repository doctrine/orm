<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\ECommerce;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\Table;

/**
 * Describes a product feature.
 */
#[Table(name: 'ecommerce_features')]
#[Entity]
class ECommerceFeature
{
    #[Column(type: 'integer')]
    #[Id]
    #[GeneratedValue]
    private int $id;

    #[Column(length: 50)]
    private string|null $description = null;

    #[ManyToOne(targetEntity: 'ECommerceProduct', inversedBy: 'features')]
    #[JoinColumn(name: 'product_id', referencedColumnName: 'id')]
    private ECommerceProduct|null $product = null;

    public function getId(): int
    {
        return $this->id;
    }

    public function getDescription(): string|null
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

    public function getProduct(): ECommerceProduct|null
    {
        return $this->product;
    }
}
