<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\GH10334;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;

#[Entity]
class GH10334Product
{
    #[Id]
    #[Column(name: 'product_id', type: 'integer')]
    #[GeneratedValue]
    protected int $id;

    #[Column(name: 'name', type: 'string')]
    private string $name;

    #[ManyToOne(targetEntity: 'GH10334ProductType', inversedBy: 'products')]
    #[JoinColumn(name: 'product_type_id', referencedColumnName: 'id', nullable: false)]
    private GH10334ProductType $productType;

    public function __construct(string $name, GH10334ProductType $productType)
    {
        $this->name        = $name;
        $this->productType = $productType;
    }

    public function getProductType(): GH10334ProductType
    {
        return $this->productType;
    }

    public function setProductType(GH10334ProductType $productType): void
    {
        $this->productType = $productType;
    }
}
