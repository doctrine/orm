<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\GH10334;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\OneToMany;

#[Entity]
class GH10334ProductType
{
    #[Id]
    #[Column(type: 'string', enumType: 'Doctrine\Tests\Models\GH10334\GH10334ProductTypeId', length: 255)]
    protected GH10334ProductTypeId $id;

    #[Column(type: 'float')]
    private float $value;

    #[OneToMany(targetEntity: 'GH10334Product', mappedBy: 'productType', cascade: ['persist', 'remove'])]
    private Collection $products;

    public function __construct(GH10334ProductTypeId $id, float $value)
    {
        $this->id       = $id;
        $this->value    = $value;
        $this->products = new ArrayCollection();
    }

    public function getId(): GH10334ProductTypeId
    {
        return $this->id;
    }

    public function addProduct(GH10334Product $product): void
    {
        $product->setProductType($this);
        $this->products->add($product);
    }
}
