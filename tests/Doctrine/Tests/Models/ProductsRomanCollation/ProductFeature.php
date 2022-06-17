<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\ProductsRomanCollation;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\DiscriminatorColumn;
use Doctrine\ORM\Mapping\DiscriminatorMap;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\InheritanceType;
use Doctrine\ORM\Mapping\Table;

/**
 * Description of ProudctFeature
 *
 * @Entity
 * @Table(name="productroman_features", options={"charset":"utf8", "collate":"utf8_roman_ci"}))
 * @InheritanceType("JOINED")
 * @DiscriminatorColumn(name="discr", type="string")
 * @DiscriminatorMap({
 *      "color"    = "ProductColor",
 * })
 */
class ProductFeature
{
    /**
     * @var string
     * @Id
     * @Column(type="string", length=10, nullable=false)
     */
    private $sku;

    public function __construct(string $sku)
    {
        $this->sku = $sku;
    }

    public function getSku(): string
    {
        return $this->sku;
    }
}
