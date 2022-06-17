<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\ProductsRomanCollation;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Table;

/**
 * @Entity
 * @Table(name="productroman_colors", options={"charset":"utf8", "collate":"utf8_roman_ci"})
 */
class ProductColor extends ProductFeature
{
    /**
     * @var string
     * @Column(type="string", length=50)
     */
    private $colorName;

    /**
     * @var int
     * @Column(type="integer", nullable=false)
     */
    private $quantity;

    public function __construct(string $sku, string $colorName, int $quantity)
    {
        parent::__construct($sku);
        $this->colorName = $colorName;
        $this->quantity  = $quantity;
    }

    public function getColorName(): string
    {
        return $this->colorName;
    }

    public function setColorName(string $value): void
    {
        $this->colorName = $value;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $value): void
    {
        $this->quantity = $value;
    }
}
